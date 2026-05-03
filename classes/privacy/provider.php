<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy provider for Assignment LID plugin.
 *
 * Implements GDPR compliance for user data export and deletion.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider class.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata about data stored by the plugin.
     *
     * @param collection $collection The collection to add metadata to
     * @return collection Updated collection
     */
    public static function get_metadata(collection $collection): collection {
        
        // LID queue table.
        $collection->add_database_table(
            'assignsubmission_lid_queue',
            [
                'userid' => 'privacy:metadata:queue:userid',
                'created_at' => 'privacy:metadata:queue:created_at',
            ],
            'privacy:metadata:queue'
        );
        
        // LID analysis table.
        $collection->add_database_table(
            'assignsubmission_lid_analysis',
            [
                'userid' => 'privacy:metadata:analysis:userid',
                'analysis_json' => 'privacy:metadata:analysis:analysis_json',
                'analyzed_at' => 'privacy:metadata:analysis:analyzed_at',
                'analyzed_by_userid' => 'privacy:metadata:analysis:analyzed_by_userid',
            ],
            'privacy:metadata:analysis'
        );
        
        // External service (Google AI Studio / Gemini API).
        $collection->add_external_location_link(
            'google_ai_studio',
            [
                'submission_text' => 'privacy:metadata:google:submission_text',
                'userid' => 'privacy:metadata:google:userid',
            ],
            'privacy:metadata:google'
        );
        
        return $collection;
    }

    /**
     * Get contexts containing user data for a user.
     *
     * @param int $userid User ID
     * @return contextlist Contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        
        // Get module contexts where this user has LID analyses.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                  JOIN {assign} a ON a.id = cm.instance
                  JOIN {assignsubmission_lid_analysis} la ON la.assignmentid = a.id
                 WHERE la.userid = :userid";
        
        $contextlist->add_from_sql($sql, [
            'contextmodule' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);
        
        return $contextlist;
    }

    /**
     * Get users in a context.
     *
     * @param userlist $userlist User list
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        
        if (!$context instanceof \context_module) {
            return;
        }
        
        // Get users who have LID analyses in this context.
        $sql = "SELECT la.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                  JOIN {assign} a ON a.id = cm.instance
                  JOIN {assignsubmission_lid_analysis} la ON la.assignmentid = a.id
                 WHERE cm.id = :cmid";
        
        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    /**
     * Export user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        
        if (empty($contextlist->count())) {
            return;
        }
        
        $userid = $contextlist->get_user()->id;
        
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            
            // Get assignment ID from context.
            $cm = get_coursemodule_from_id('assign', $context->instanceid);
            if (!$cm) {
                continue;
            }
            
            // Get analyses for this user in this assignment.
            $analyses = $DB->get_records('assignsubmission_lid_analysis', [
                'assignmentid' => $cm->instance,
                'userid' => $userid,
            ]);
            
            if (empty($analyses)) {
                continue;
            }
            
            // Export each analysis.
            foreach ($analyses as $analysis) {
                $data = json_decode($analysis->analysis_json, true);
                
                $exportdata = [
                    'analyzed_at' => \core_privacy\local\request\transform::datetime($analysis->analyzed_at),
                    'submission_version' => $analysis->submission_version,
                    'analysis' => $data,
                ];
                
                // Write data.
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'assignsubmission_lid'), 'analysis_' . $analysis->id],
                    (object)$exportdata
                );
            }
        }
    }

    /**
     * Delete user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        
        if (empty($contextlist->count())) {
            return;
        }
        
        $userid = $contextlist->get_user()->id;
        
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            
            // Get assignment ID from context.
            $cm = get_coursemodule_from_id('assign', $context->instanceid);
            if (!$cm) {
                continue;
            }
            
            // Delete analyses.
            $DB->delete_records('assignsubmission_lid_analysis', [
                'assignmentid' => $cm->instance,
                'userid' => $userid,
            ]);
            
            // Delete queue entries.
            $DB->delete_records('assignsubmission_lid_queue', [
                'assignmentid' => $cm->instance,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Delete multiple users data within a single context.
     *
     * @param approved_userlist $userlist Approved user list
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        
        $context = $userlist->get_context();
        
        if (!$context instanceof \context_module) {
            return;
        }
        
        // Get assignment ID from context.
        $cm = get_coursemodule_from_id('assign', $context->instanceid);
        if (!$cm) {
            return;
        }
        
        $userids = $userlist->get_userids();
        
        if (empty($userids)) {
            return;
        }
        
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        
        // Delete analyses.
        $DB->delete_records_select(
            'assignsubmission_lid_analysis',
            "assignmentid = :assignmentid AND userid {$usersql}",
            array_merge(['assignmentid' => $cm->instance], $userparams)
        );
        
        // Delete queue entries.
        $DB->delete_records_select(
            'assignsubmission_lid_queue',
            "assignmentid = :assignmentid AND userid {$usersql}",
            array_merge(['assignmentid' => $cm->instance], $userparams)
        );
    }

    /**
     * Delete all user data in a context.
     *
     * @param \context $context Context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        
        if (!$context instanceof \context_module) {
            return;
        }
        
        // Get assignment ID from context.
        $cm = get_coursemodule_from_id('assign', $context->instanceid);
        if (!$cm) {
            return;
        }
        
        // Delete all analyses for this assignment.
        $DB->delete_records('assignsubmission_lid_analysis', [
            'assignmentid' => $cm->instance,
        ]);
        
        // Delete all queue entries for this assignment.
        $DB->delete_records('assignsubmission_lid_queue', [
            'assignmentid' => $cm->instance,
        ]);
    }
}
