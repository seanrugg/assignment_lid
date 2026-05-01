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
 * Assignment LID plugin version information.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'assignsubmission_lid';
$plugin->version = 2026043000;  // YYYYMMDDXX format.
$plugin->requires = 2023100900; // Requires Moodle 4.5 (2023100900) or later.
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.1.0-dev';

// Dependencies.
$plugin->dependencies = [
    'mod_assign' => ANY_VERSION,
];
