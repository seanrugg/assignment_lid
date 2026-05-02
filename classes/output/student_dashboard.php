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
 * Student-level dashboard renderable.
 *
 * Shows all LID analyses for one student across all assignments in a course.
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
 * Student-level dashboard renderable.
 */
class student_dashboard implements renderable, templatable {
    
    /** @var int Student user ID */
    protected $userid;
    
    /** @var int Course ID */
    protected $courseid;
    
    /** @var object Course record */
    protected $course;
    
    /** @var object User record */
    protected $user;
    
    /**
     * Constructor.
     *
     * @param int $userid Student user ID
     * @param int $courseid Course ID
     * @param object $course Course record
     * @param object $user User record
     */
    public function __construct($userid, $courseid, $course, $user) {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->course = $course;
        $this->user = $user;
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
        $data->studentname = fullname($this->user);
        $data->coursename = format_string($this->course->fullname);
        $data->userid = $this->userid;
        $data->courseid = $this->courseid;
        $data->userpic = $output->user_picture($this->user, ['size' => 100]);
        
        // Get all analyses for this student in this course.
        $analyses = $DB->get_records_sql(
            "SELECT a.*, asn.name as assignmentname, asn.timemodified as assignmentdate
               FROM {assignsubmission_lid_analysis} a
               JOIN {assign} asn ON asn.id = a.assignmentid
              WHERE a.userid = ?
                AND asn.course = ?
           ORDER BY a.analyzed_at ASC",
            [$this->userid, $this->courseid]
        );
        
        $data->total_analyses = count($analyses);
        $data->has_analyses = !empty($analyses);
        
        if (empty($analyses)) {
            return $data;
        }
        
        // Build assignment timeline.
        $timeline = [];
        $allcompetencies = []; // [compname] => ['max_bloom' => X, 'evidence' => [...]]
        
        foreach ($analyses as $analysis) {
            $lid = json_decode($analysis->analysis_json);
            if (!$lid) {
                continue;
            }
            
            // Timeline card.
            $card = new stdClass();
            $card->assignmentname = format_string($analysis->assignmentname);
            $card->submission_date = userdate($analysis->analyzed_at, get_string('strftimedatetimeshort'));
            
            // Overall quality score.
            if (isset($lid->submission_analysis->overall_quality_score)) {
                $card->quality_score = $lid->submission_analysis->overall_quality_score;
                $card->has_quality = true;
            }
            
            // Key competencies (top 3).
            if (isset($lid->competency_demonstration)) {
                $topcomps = array_slice($lid->competency_demonstration, 0, 3);
                $card->competencies = array_map(function($c) {
                    return [
                        'name' => $c->competency_name,
                        'bloom_level' => $c->bloom_level,
                    ];
                }, $topcomps);
                $card->has_competencies = !empty($card->competencies);
                
                // Collect all competencies for radar chart and evidence portfolio.
                foreach ($lid->competency_demonstration as $comp) {
                    $compname = $comp->competency_name;
                    if (!isset($allcompetencies[$compname])) {
                        $allcompetencies[$compname] = [
                            'max_bloom' => 0,
                            'evidence' => [],
                        ];
                    }
                    
                    // Track max Bloom's level.
                    if ($comp->bloom_level > $allcompetencies[$compname]['max_bloom']) {
                        $allcompetencies[$compname]['max_bloom'] = $comp->bloom_level;
                    }
                    
                    // Collect evidence excerpts.
                    if (!empty($comp->evidence_excerpt)) {
                        $allcompetencies[$compname]['evidence'][] = [
                            'assignment' => $analysis->assignmentname,
                            'excerpt' => $comp->evidence_excerpt,
                            'bloom_level' => $comp->bloom_level,
                            'depth_rating' => $comp->depth_rating ?? '',
                        ];
                    }
                }
            }
            
            // Formative feedback summary (first strength + first development priority).
            if (isset($lid->formative_feedback)) {
                $feedback = [];
                if (!empty($lid->formative_feedback->key_strengths)) {
                    $feedback[] = '✓ ' . $lid->formative_feedback->key_strengths[0];
                }
                if (!empty($lid->formative_feedback->development_priorities)) {
                    $feedback[] = '→ ' . $lid->formative_feedback->development_priorities[0];
                }
                if (!empty($feedback)) {
                    $card->feedback_summary = implode(' | ', $feedback);
                }
            }
            
            $timeline[] = $card;
        }
        
        $data->timeline = $timeline;
        $data->has_timeline = !empty($timeline);
        
        // Build competency radar chart data.
        if (!empty($allcompetencies)) {
            $radardata = [];
            foreach ($allcompetencies as $compname => $compdata) {
                $radardata[] = [
                    'label' => $compname,
                    'value' => $compdata['max_bloom'],
                ];
            }
            
            // Sort by max Bloom's level descending.
            usort($radardata, function($a, $b) {
                return $b['value'] - $a['value'];
            });
            
            $data->radar_data = json_encode($radardata);
            $data->has_radar = true;
        }
        
        // Build competency evidence portfolio.
        $portfolio = [];
        foreach ($allcompetencies as $compname => $compdata) {
            if (empty($compdata['evidence'])) {
                continue;
            }
            
            $portfolio[] = [
                'competency' => $compname,
                'max_bloom' => $compdata['max_bloom'],
                'evidence_count' => count($compdata['evidence']),
                'evidence' => $compdata['evidence'],
            ];
        }
        
        // Sort by max Bloom's level descending.
        usort($portfolio, function($a, $b) {
            return $b['max_bloom'] - $a['max_bloom'];
        });
        
        $data->portfolio = $portfolio;
        $data->has_portfolio = !empty($portfolio);
        
        // Export URL (future: PDF generation).
        $data->export_url = new \moodle_url('/mod/assign/submission/lid/export.php', [
            'userid' => $this->userid,
            'courseid' => $this->courseid,
            'format' => 'pdf',
        ]);
        
        // Capabilities.
        $context = \context_course::instance($this->courseid);
        $data->can_export = has_capability('assignsubmission/lid:viewreports', $context);
        
        return $data;
    }
}
