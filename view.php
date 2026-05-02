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
 * Learning Intelligence Dashboard (LID) - Main dashboard view controller.
 *
 * Routes to appropriate dashboard based on context:
 * - Assignment-level: All students for one assignment
 * - Course-level: All assignments in course
 * - Student-level: All assignments for one student
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

// Get parameters - determine dashboard type based on what's provided.
$assignid  = optional_param('assignid', 0, PARAM_INT);   // Assignment ID
$courseid  = optional_param('courseid', 0, PARAM_INT);   // Course ID
$userid    = optional_param('userid', 0, PARAM_INT);     // Student user ID
$view      = optional_param('view', '', PARAM_ALPHA);    // Explicit view type override

// Validate and determine context.
if ($assignid) {
    // Assignment-level dashboard.
    $cm = get_coursemodule_from_instance('assign', $assignid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $context = context_module::instance($cm->id);
    $view = 'assignment';
    $courseid = $course->id;
    
} else if ($courseid && $userid) {
    // Student-level dashboard (student's work across all assignments in course).
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    $view = 'student';
    
} else if ($courseid) {
    // Course-level dashboard (all assignments in course).
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    $view = 'course';
    
} else {
    throw new moodle_exception('missingparam', 'error', '', 'assignid or courseid');
}

// Set up page.
$PAGE->set_url('/mod/assign/submission/lid/view.php', [
    'assignid' => $assignid,
    'courseid' => $courseid,
    'userid' => $userid,
    'view' => $view,
]);

require_login($course, false, isset($cm) ? $cm : null);
require_capability('assignsubmission/lid:viewreports', $context);

$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// Load LID branding CSS if enabled.
$futuristicui = get_config('assignsubmission_lid', 'futuristicui');
if ($futuristicui) {
    $PAGE->requires->css('/mod/assign/submission/lid/styles-lid-brand.css');
}

// Route to appropriate dashboard renderer.
switch ($view) {
    case 'assignment':
        require_once(__DIR__ . '/classes/output/assignment_dashboard.php');
        $dashboard = new \assignsubmission_lid\output\assignment_dashboard($assignid, $cm, $course);
        $title = get_string('dashboard_assignment_title', 'assignsubmission_lid');
        break;
        
    case 'course':
        require_once(__DIR__ . '/classes/output/course_dashboard.php');
        $dashboard = new \assignsubmission_lid\output\course_dashboard($courseid, $course);
        $title = get_string('dashboard_course_title', 'assignsubmission_lid');
        break;
        
    case 'student':
        require_once(__DIR__ . '/classes/output/student_dashboard.php');
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $dashboard = new \assignsubmission_lid\output\student_dashboard($userid, $courseid, $course, $user);
        $title = get_string('dashboard_student_title', 'assignsubmission_lid', fullname($user));
        break;
        
    default:
        throw new moodle_exception('invalidview', 'assignsubmission_lid', '', $view);
}

// Set page title and heading.
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

// Render dashboard.
echo $OUTPUT->header();
echo $OUTPUT->render($dashboard);
echo $OUTPUT->footer();
