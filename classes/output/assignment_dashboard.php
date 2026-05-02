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
 * Assignment-level dashboard renderable.
 *
 * Shows LID analysis results for all students in one assignment.
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
 * Assignment-level dashboard renderable.
 */
class assignment_dashboard implements renderable, templatable {
    
    /** @var int Assignment ID */
    protected $assignid;
    
    /** @var object Course module record */
    protected $cm;
    
    /** @var object Course record */
    protected $course;
    
    /** @var object Assignment instance */
    protected $assign;
    
    /**
     * Constructor.
     *
     * @param int $assignid Assignment ID
     * @param object $cm Course module record
     * @param object $course Course record
     */
    public function __construct($assignid, $cm, $course) {
        $this->assignid = $assignid;
        $this->cm = $cm;
        $this->course = $course;
        
        // Load assignment instance.
        $context = \context_module::instance($cm->id);
        $this->assign = new \assign($context, $cm, $course);
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
        $data->assignmentname = format_string($this->assign->get_instance()->name);
        $data->coursename = format_string($this->course->fullname);
        $data->assignid = $this->assignid;
        $data->courseid = $this->course->id;
        
        // Get all students enrolled in course with submit capability.
        $context = $this->assign->get_context();
        $students = get_enrolled_users($context, 'mod/assign:submit', 0, 'u.*', 'u.lastname ASC, u.firstname ASC');
        
        // Count submissions and analyses.
        $totalstudents = count($students);
        $submittedcount = 0;
        $analyzedcount = 0;
        $pendingcount = 0;
        
        // Build student data array.
        $studentdata = [];
        foreach ($students as $student) {
            $submission = $this->assign->get_user_submission($student->id, false);
            
            $studentrow = new stdClass();
            $studentrow->userid = $student->id;
            $studentrow->fullname = fullname($student);
            $studentrow->userpic = $output->user_picture($student, ['size' => 35]);
            
            // Submission status.
            if ($submission && $submission->status === ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                $submittedcount++;
                $studentrow->submitted = true;
                $studentrow->submission_date = userdate($submission->timemodified, get_string('strftimedatetimeshort'));
                
                // Check for LID analysis.
                $analysis = $DB->get_record('assignsubmission_lid_analysis', [
                    'assignmentid' => $this->assignid,
                    'userid' => $student->id,
                    'submission_version' => $submission->attemptnumber ?? 0,
                ], '*', IGNORE_MULTIPLE);
                
                if ($analysis) {
                    $analyzedcount++;
                    $studentrow->analyzed = true;
                    $studentrow->analysis_date = userdate($analysis->analyzed_at);
                    
                    // Parse analysis JSON to get summary stats.
                    $lid = json_decode($analysis->analysis_json);
                    if ($lid && isset($lid->submission_analysis)) {
                        $studentrow->overall_score = $lid->submission_analysis->overall_quality_score ?? null;
                        $studentrow->has_score = true;
                    }
                    
                    // Top competencies (first 2).
                    if ($lid && isset($lid->competency_demonstration)) {
                        $topcomps = array_slice($lid->competency_demonstration, 0, 2);
                        $studentrow->top_competencies = implode(', ', array_map(function($c) {
                            return $c->competency_name;
                        }, $topcomps));
                    }
                    
                    $studentrow->view_url = new \moodle_url('/mod/assign/submission/lid/view.php', [
                        'assignid' => $this->assignid,
                        'userid' => $student->id,
                    ]);
                    
                } else {
                    // Check if in queue.
                    $queued = $DB->record_exists('assignsubmission_lid_queue', [
                        'assignmentid' => $this->assignid,
                        'userid' => $student->id,
                        'status' => 'pending',
                    ]);
                    
                    if ($queued) {
                        $pendingcount++;
                        $studentrow->pending = true;
                    } else {
                        $studentrow->not_analyzed = true;
                    }
                }
                
            } else {
                $studentrow->not_submitted = true;
            }
            
            $studentdata[] = $studentrow;
        }
        
        $data->students = $studentdata;
        $data->total_students = $totalstudents;
        $data->submitted_count = $submittedcount;
        $data->analyzed_count = $analyzedcount;
        $data->pending_count = $pendingcount;
        $data->has_students = !empty($studentdata);
        
        // Summary statistics (if any analyses exist).
        if ($analyzedcount > 0) {
            $data->has_summary = true;
            $data->summary = $this->calculate_summary_stats();
        }
        
        // API cost summary.
        $totalcost = $DB->get_field_sql(
            "SELECT SUM(api_cost_usd) FROM {assignsubmission_lid_analysis} WHERE assignmentid = ?",
            [$this->assignid]
        );
        $data->total_cost = $totalcost ? '$' . number_format($totalcost, 4) : '$0.00';
        
        // Capabilities.
        $data->can_analyze = has_capability('assignsubmission/lid:analyze', $context);
        $data->can_viewcosts = has_capability('assignsubmission/lid:viewcosts', $context);
        
        // URLs for actions.
        $data->analyze_all_url = new \moodle_url('/mod/assign/submission/lid/actions.php', [
            'action' => 'analyze_all',
            'assignid' => $this->assignid,
            'sesskey' => sesskey(),
        ]);
        
        $data->export_csv_url = new \moodle_url('/mod/assign/submission/lid/export.php', [
            'assignid' => $this->assignid,
            'format' => 'csv',
        ]);
        
        return $data;
    }
    
    /**
     * Calculate summary statistics from all analyses.
     *
     * @return stdClass Summary data
     */
    protected function calculate_summary_stats() {
        global $DB;
        
        $summary = new stdClass();
        
        // Get all analyses for this assignment.
        $analyses = $DB->get_records('assignsubmission_lid_analysis', [
            'assignmentid' => $this->assignid,
        ]);
        
        if (empty($analyses)) {
            return $summary;
        }
        
        // Aggregate scores.
        $qualityscores = [];
        $depthscores = [];
        $bloomcounts = array_fill(1, 6, 0);
        $allcomps = [];
        
        foreach ($analyses as $analysis) {
            $lid = json_decode($analysis->analysis_json);
            if (!$lid) {
                continue;
            }
            
            // Quality scores.
            if (isset($lid->submission_analysis->overall_quality_score)) {
                $qualityscores[] = $lid->submission_analysis->overall_quality_score;
            }
            if (isset($lid->submission_analysis->cognitive_depth_score)) {
                $depthscores[] = $lid->submission_analysis->cognitive_depth_score;
            }
            
            // Bloom's levels.
            if (isset($lid->blooms_progression)) {
                foreach ($lid->blooms_progression as $bloom) {
                    if ($bloom->is_active ?? false) {
                        $bloomcounts[$bloom->level]++;
                    }
                }
            }
            
            // Competencies.
            if (isset($lid->competency_demonstration)) {
                foreach ($lid->competency_demonstration as $comp) {
                    $compname = $comp->competency_name;
                    if (!isset($allcomps[$compname])) {
                        $allcomps[$compname] = ['count' => 0, 'sum_bloom' => 0];
                    }
                    $allcomps[$compname]['count']++;
                    $allcomps[$compname]['sum_bloom'] += $comp->bloom_level;
                }
            }
        }
        
        // Average scores.
        $summary->avg_quality = !empty($qualityscores) ? round(array_sum($qualityscores) / count($qualityscores)) : 0;
        $summary->avg_depth = !empty($depthscores) ? round(array_sum($depthscores) / count($depthscores)) : 0;
        
        // Bloom's distribution.
        $summary->bloom_distribution = [];
        foreach ($bloomcounts as $level => $count) {
            $summary->bloom_distribution[] = [
                'level' => $level,
                'count' => $count,
                'percentage' => count($analyses) > 0 ? round(($count / count($analyses)) * 100) : 0,
            ];
        }
        
        // Top competencies (by frequency).
        arsort($allcomps);
        $summary->top_competencies = [];
        $i = 0;
        foreach ($allcomps as $name => $data) {
            if ($i >= 5) break;
            $summary->top_competencies[] = [
                'name' => $name,
                'count' => $data['count'],
                'avg_bloom' => round($data['sum_bloom'] / $data['count'], 1),
            ];
            $i++;
        }
        
        return $summary;
    }
}
