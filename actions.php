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
 * AJAX action handler for LID operations.
 *
 * Handles analyze, batch analyze, and other operations triggered from the UI.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once(__DIR__ . '/classes/queue_manager.php');

// Get parameters.
$action = required_param('action', PARAM_ALPHA);
$assignmentid = optional_param('assignmentid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

// Require login and session key.
require_login();
require_sesskey();

// Set up JSON response header.
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'analyze':
            handle_analyze($assignmentid, $userid);
            break;
            
        case 'analyze_all':
            handle_analyze_all($assignmentid);
            break;
            
        default:
            throw new moodle_exception('invalidaction', 'assignsubmission_lid', '', $action);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}

/**
 * Handle single student analysis request.
 *
 * @param int $assignmentid Assignment ID
 * @param int $userid Student user ID
 */
function handle_analyze($assignmentid, $userid) {
    global $DB, $USER;
    
    if (!$assignmentid || !$userid) {
        throw new moodle_exception('missingparam', 'error');
    }
    
    // Get assignment and check permissions.
    $cm = get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_capability('assignsubmission/lid:analyze', $context);
    
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $assign = new assign($context, $cm, $course);
    
    // Get the student's submission.
    $submission = $assign->get_user_submission($userid, false);
    
    if (!$submission || $submission->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
        throw new moodle_exception('error:emptysubmission', 'assignsubmission_lid');
    }
    
    // Check if already in queue.
    $existing = $DB->get_record('assignsubmission_lid_queue', [
        'assignmentid' => $assignmentid,
        'userid' => $userid,
    ]);
    
    if ($existing && in_array($existing->status, ['pending', 'processing'])) {
        echo json_encode([
            'success' => true,
            'message' => get_string('analysispending', 'assignsubmission_lid'),
            'alreadyqueued' => true,
        ]);
        return;
    }
    
    // Queue the analysis.
    $queuemanager = new \assignsubmission_lid\queue_manager();
    $queueid = $queuemanager->create([
        'assignmentid' => $assignmentid,
        'submissionid' => $submission->id,
        'userid' => $userid,
        'priority' => 5, // Normal priority
        'created_by_userid' => $USER->id,
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => get_string('analysispending', 'assignsubmission_lid'),
        'queueid' => $queueid,
    ]);
}

/**
 * Handle batch analyze all unanalyzed submissions.
 *
 * @param int $assignmentid Assignment ID
 */
function handle_analyze_all($assignmentid) {
    global $DB, $USER;
    
    if (!$assignmentid) {
        throw new moodle_exception('missingparam', 'error');
    }
    
    // Get assignment and check permissions.
    $cm = get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_capability('assignsubmission/lid:analyze', $context);
    
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $assign = new assign($context, $cm, $course);
    
    // Get all students with submissions.
    $students = get_enrolled_users($context, 'mod/assign:submit');
    
    $queued = 0;
    $alreadyanalyzed = 0;
    $alreadyqueued = 0;
    $queuemanager = new \assignsubmission_lid\queue_manager();
    
    foreach ($students as $student) {
        $submission = $assign->get_user_submission($student->id, false);
        
        if (!$submission || $submission->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            continue;
        }
        
        // Check if already analyzed.
        $analyzed = $DB->record_exists('assignsubmission_lid_analysis', [
            'assignmentid' => $assignmentid,
            'userid' => $student->id,
            'submission_version' => $submission->attemptnumber ?? 0,
        ]);
        
        if ($analyzed) {
            $alreadyanalyzed++;
            continue;
        }
        
        // Check if already in queue.
        $inqueue = $DB->get_record('assignsubmission_lid_queue', [
            'assignmentid' => $assignmentid,
            'userid' => $student->id,
        ]);
        
        if ($inqueue && in_array($inqueue->status, ['pending', 'processing'])) {
            $alreadyqueued++;
            continue;
        }
        
        // Queue the analysis.
        $queuemanager->create([
            'assignmentid' => $assignmentid,
            'submissionid' => $submission->id,
            'userid' => $student->id,
            'priority' => 3, // Lower priority for batch
            'created_by_userid' => $USER->id,
        ]);
        
        $queued++;
    }
    
    echo json_encode([
        'success' => true,
        'queued' => $queued,
        'already_analyzed' => $alreadyanalyzed,
        'already_queued' => $alreadyqueued,
        'message' => get_string('batchanalyzequeued', 'assignsubmission_lid', [
            'queued' => $queued,
            'total' => $queued + $alreadyanalyzed + $alreadyqueued,
        ]),
    ]);
}
