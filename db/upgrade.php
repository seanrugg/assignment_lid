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
 * Database upgrade script for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the Assignment LID plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Always true.
 */
function xmldb_assignsubmission_lid_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Placeholder for future upgrade steps.
    // Example upgrade step (not yet needed in v0.1.0):
    /*
    if ($oldversion < 2026043001) {
        // Define new field to be added to assignsubmission_lid_analysis.
        $table = new xmldb_table('assignsubmission_lid_analysis');
        $field = new xmldb_field('confidence_score', XMLDB_TYPE_INTEGER, '3', null, null, null, null, 'model_version');

        // Conditionally add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // LID savepoint reached.
        upgrade_plugin_savepoint(true, 2026043001, 'assignsubmission', 'lid');
    }
    */

    return true;
}
