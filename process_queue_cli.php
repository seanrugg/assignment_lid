#!/usr/bin/env php
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
 * CLI script for manually processing LID analysis queue.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'verbose' => false,
    ],
    [
        'h' => 'help',
        'v' => 'verbose',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Process LID analysis queue manually.

This script runs the scheduled task immediately, bypassing cron.
Useful for testing and troubleshooting.

Options:
-h, --help          Print out this help
-v, --verbose       Print verbose progress information

Example:
\$ sudo -u www-data php process_queue_cli.php
\$ sudo -u www-data php process_queue_cli.php --verbose
";

    echo $help;
    exit(0);
}

// Set up verbose output.
if ($options['verbose']) {
    set_debugging(DEBUG_DEVELOPER, true);
}

cli_heading('LID Analysis Queue Processor');

// Check if plugin is configured.
$apikey = get_config('assignsubmission_lid', 'apikey');
if (empty($apikey)) {
    cli_error('Error: API key not configured. Please configure in plugin settings.');
}

// Run the task.
try {
    $task = new \assignsubmission_lid\task\process_queue();
    
    cli_writeln('Starting queue processing...');
    cli_writeln('');
    
    $task->execute();
    
    cli_writeln('');
    cli_writeln('Queue processing complete.');
    
    exit(0);
    
} catch (Exception $e) {
    cli_error('Error: ' . $e->getMessage());
}
