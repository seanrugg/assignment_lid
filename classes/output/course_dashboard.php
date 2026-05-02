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
 * Course-level dashboard renderable.
 *
 * Shows aggregated LID analysis across all assignments in a course.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Course-level dashboard renderable.
 */
class course_dashboard implements renderable, templatable {
    
    /** @var int Course ID */
    protected $courseid;
    
    /** @var object Course record */
    protected $course;
    
    /**
     * Constructor.
     *
     * @param int $courseid Course ID
     * @param object $course Course record
     */
    public function __construct($courseid, $course) {
        $this->courseid = $courseid;
        $this->course = $course;
    }
    
    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        
        $data = new stdClass();
        
        // Header information.
        $data->coursename = format_string($this->course->fullname);
        $data->courseid = $this->courseid;
        
        // Get all assignment activities in this course.
        $assignments = $DB->get_records_sql(
            "SELECT a.id, a.name, a.timemodified, cm.id as cmid
               FROM {assign} a
               JOIN {course_modules} cm ON cm.instance = a.id
               JOIN {modules} m ON m.id = cm.module
              WHERE a.course = ?
                AND m.name = 'assign'
                AND cm.deletioninprogress = 0
           ORDER BY a.timemodified ASC",
            [$this->courseid]
        );
        
        $totalassignments = 0;
        $assignmentswithanalyses = 0;
        $totalanalyses = 0;
        $totalcost = 0;
        $assignmentdata = [];
        
        foreach ($assignments as $assignment) {
            $totalassignments++;
            
            // Check if this assignment has any LID analyses.
            $analysiscount = $DB->count_records('assignsubmission_lid_analysis', [
                'assignmentid' => $assignment->id,
            ]);
            
            if ($analysiscount > 0) {
                $assignmentswithanalyses++;
                $totalanalyses += $analysiscount;
                
                // Get assignment summary data.
                $assignrow = new stdClass();
                $assignrow->id = $assignment->id;
                $assignrow->name = format_string($assignment->name);
                $assignrow->timemodified = userdate($assignment->timemodified, get_string('strftimedatetimeshort'));
                
                // Count total students who submitted.
                $context = \context_module::instance($assignment->cmid);
                $students = get_enrolled_users($context, 'mod/assign:submit');
                $assignrow->total_students = count($students);
                $assignrow->analyzed_count = $analysiscount;
                
                // Calculate average quality score.
                $analyses = $DB->get_records('assignsubmission_lid_analysis', [
                    'assignmentid' => $assignment->id,
                ]);
                
                $qualityscores = [];
                $allcomps = [];
                $assignmentcost = 0;
                
                foreach ($analyses as $analysis) {
                    $lid = json_decode($analysis->analysis_json);
                    if ($lid && isset($lid->submission_analysis->overall_quality_score)) {
                        $qualityscores[] = $lid->submission_analysis->overall_quality_score;
                    }
                    
                    // Track competencies.
                    if ($lid && isset($lid->competency_demonstration)) {
                        foreach ($lid->competency_demonstration as $comp) {
                            $compname = $comp->competency_name;
                            if (!isset($allcomps[$compname])) {
                                $allcomps[$compname] = 0;
                            }
                            $allcomps[$compname]++;
                        }
                    }
                    
                    // Sum cost.
                    if ($analysis->api_cost_usd) {
                        $assignmentcost += $analysis->api_cost_usd;
                    }
                }
                
                $assignrow->avg_quality = !empty($qualityscores) 
                    ? round(array_sum($qualityscores) / count($qualityscores)) 
                    : null;
                $assignrow->has_quality = $assignrow->avg_quality !== null;
                
                // Top competency.
                if (!empty($allcomps)) {
                    arsort($allcomps);
                    $assignrow->top_competency = array_key_first($allcomps);
                } else {
                    $assignrow->top_competency = '—';
                }
                
                $assignrow->cost = '$' . number_format($assignmentcost, 4);
                $totalcost += $assignmentcost;
                
                // Link to assignment dashboard.
                $assignrow->view_url = new \moodle_url('/mod/assign/submission/lid/view.php', [
                    'assignid' => $assignment->id,
                ]);
                
                $assignmentdata[] = $assignrow;
            }
        }
        
        $data->total_assignments = $totalassignments;
        $data->assignments_with_lid = $assignmentswithanalyses;
        $data->total_analyses = $totalanalyses;
        $data->total_cost = '$' . number_format($totalcost, 4);
        $data->has_assignments = !empty($assignmentdata);
        $data->assignments = $assignmentdata;
        
        // Competency progression data.
        $data->has_progression = false;
        if ($assignmentswithanalyses > 1) {
            $progression = $this->calculate_competency_progression($assignmentdata);
            if (!empty($progression)) {
                $data->has_progression = true;
                $data->progression = $progression;
            }
        }
        
        // Capabilities.
        $context = \context_course::instance($this->courseid);
        $data->can_viewcosts = has_capability('assignsubmission/lid:viewcosts', $context);
        
        return $data;
    }
    
    /**
     * Calculate competency progression across assignments.
     *
     * @param array $assignments Assignment data with analyses
     * @return array Progression data for chart
     */
    protected function calculate_competency_progression($assignments) {
        global $DB;
        
        // Collect all competencies across all assignments.
        $compdata = []; // [compname][assignid] = avg_bloom_level
        $assignmentorder = [];
        
        foreach ($assignments as $assignment) {
            $assignmentorder[] = [
                'id' => $assignment->id,
                'name' => $assignment->name,
            ];
            
            $analyses = $DB->get_records('assignsubmission_lid_analysis', [
                'assignmentid' => $assignment->id,
            ]);
            
            $compblooms = []; // [compname] = [bloom_levels...]
            
            foreach ($analyses as $analysis) {
                $lid = json_decode($analysis->analysis_json);
                if ($lid && isset($lid->competency_demonstration)) {
                    foreach ($lid->competency_demonstration as $comp) {
                        $compname = $comp->competency_name;
                        if (!isset($compblooms[$compname])) {
                            $compblooms[$compname] = [];
                        }
                        $compblooms[$compname][] = $comp->bloom_level;
                    }
                }
            }
            
            // Calculate average Bloom's level per competency for this assignment.
            foreach ($compblooms as $compname => $blooms) {
                if (!isset($compdata[$compname])) {
                    $compdata[$compname] = [];
                }
                $compdata[$compname][$assignment->id] = round(array_sum($blooms) / count($blooms), 1);
            }
        }
        
        // Build progression series (top 5 competencies by frequency).
        $compfrequency = array_map('count', $compdata);
        arsort($compfrequency);
        $topcomps = array_slice(array_keys($compfrequency), 0, 5, true);
        
        $series = [];
        foreach ($topcomps as $compname) {
            $points = [];
            foreach ($assignmentorder as $assign) {
                $points[] = $compdata[$compname][$assign['id']] ?? 0;
            }
            
            $series[] = [
                'name' => $compname,
                'data' => $points,
            ];
        }
        
        return [
            'labels' => array_column($assignmentorder, 'name'),
            'series' => $series,
        ];
    }
}
