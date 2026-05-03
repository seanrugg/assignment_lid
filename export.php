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
 * Export LID analyses to CSV or PDF.
 *
 * Handles both assignment-level CSV exports and student-level PDF portfolio exports.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir . '/csvlib.class.php');

// Get parameters.
$assignmentid = optional_param('assignid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$format = required_param('format', PARAM_ALPHA); // 'csv' or 'pdf'

require_login();

try {
    switch ($format) {
        case 'csv':
            export_csv($assignmentid, $courseid, $userid);
            break;
            
        case 'pdf':
            export_pdf($userid, $courseid);
            break;
            
        default:
            throw new moodle_exception('invalidformat', 'assignsubmission_lid', '', $format);
    }
} catch (Exception $e) {
    print_error('exporterror', 'assignsubmission_lid', '', $e->getMessage());
}

/**
 * Export assignment analyses to CSV.
 *
 * @param int $assignmentid Assignment ID (if single assignment)
 * @param int $courseid Course ID (if course-wide export)
 * @param int $userid User ID (if single student export)
 */
function export_csv($assignmentid, $courseid, $userid) {
    global $DB;
    
    // Validate parameters.
    if (!$assignmentid && !$courseid) {
        throw new moodle_exception('missingparam', 'error', '', 'assignid or courseid');
    }
    
    // Check permissions.
    if ($assignmentid) {
        $cm = get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        require_capability('assignsubmission/lid:viewreports', $context);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    } else {
        $context = context_course::instance($courseid);
        require_capability('assignsubmission/lid:viewreports', $context);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    }
    
    // Build filename.
    $filename = 'lid_export_' . date('Y-m-d_His') . '.csv';
    
    // Set up CSV writer.
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    
    // CSV headers.
    $headers = [
        get_string('student', 'assignsubmission_lid'),
        get_string('assignment', 'assignsubmission_lid'),
        get_string('submissiondate', 'assignsubmission_lid'),
        get_string('overallquality', 'assignsubmission_lid'),
        get_string('cognitivedepth', 'assignsubmission_lid'),
        get_string('coherence', 'assignsubmission_lid'),
        get_string('evidencequality', 'assignsubmission_lid'),
        get_string('topcompetencies', 'assignsubmission_lid'),
        get_string('keystrengths', 'assignsubmission_lid'),
        get_string('developmentpriorities', 'assignsubmission_lid'),
        get_string('analyzeddate', 'assignsubmission_lid'),
    ];
    
    // Add cost column if user can view costs.
    if (has_capability('assignsubmission/lid:viewcosts', $context)) {
        $headers[] = get_string('apicost', 'assignsubmission_lid');
    }
    
    $csvexport->add_data($headers);
    
    // Get analyses.
    $sql = "SELECT a.*, u.firstname, u.lastname, asn.name as assignmentname, sub.timemodified as submissiondate
              FROM {assignsubmission_lid_analysis} a
              JOIN {user} u ON u.id = a.userid
              JOIN {assign} asn ON asn.id = a.assignmentid
              JOIN {assign_submission} sub ON sub.id = a.submissionid
             WHERE 1=1";
    
    $params = [];
    
    if ($assignmentid) {
        $sql .= " AND a.assignmentid = ?";
        $params[] = $assignmentid;
    }
    
    if ($courseid) {
        $sql .= " AND asn.course = ?";
        $params[] = $courseid;
    }
    
    if ($userid) {
        $sql .= " AND a.userid = ?";
        $params[] = $userid;
    }
    
    $sql .= " ORDER BY asn.name ASC, u.lastname ASC, u.firstname ASC";
    
    $analyses = $DB->get_records_sql($sql, $params);
    
    if (empty($analyses)) {
        throw new moodle_exception('noanalysestoexport', 'assignsubmission_lid');
    }
    
    // Process each analysis.
    foreach ($analyses as $analysis) {
        $data = json_decode($analysis->analysis_json);
        
        if (!$data) {
            continue;
        }
        
        // Extract data.
        $studentname = fullname($analysis);
        $assignmentname = $analysis->assignmentname;
        $submissiondate = userdate($analysis->submissiondate, get_string('strftimedatetimeshort'));
        
        $overallquality = $data->submission_analysis->overall_quality_score ?? '';
        $cognitivedepth = $data->submission_analysis->cognitive_depth_score ?? '';
        $coherence = $data->submission_analysis->coherence_score ?? '';
        $evidencequality = $data->submission_analysis->evidence_quality_score ?? '';
        
        // Top 3 competencies.
        $topcomps = [];
        if (isset($data->competency_demonstration)) {
            $comps = array_slice($data->competency_demonstration, 0, 3);
            foreach ($comps as $comp) {
                $topcomps[] = $comp->competency_name . ' (L' . $comp->bloom_level . ')';
            }
        }
        $topcompsstr = implode('; ', $topcomps);
        
        // Key strengths (first 3).
        $strengths = [];
        if (isset($data->formative_feedback->key_strengths)) {
            $strengths = array_slice($data->formative_feedback->key_strengths, 0, 3);
        }
        $strengthsstr = implode('; ', $strengths);
        
        // Development priorities (first 3).
        $devpriorities = [];
        if (isset($data->formative_feedback->development_priorities)) {
            $devpriorities = array_slice($data->formative_feedback->development_priorities, 0, 3);
        }
        $devprioritiesstr = implode('; ', $devpriorities);
        
        $analyzeddate = userdate($analysis->analyzed_at, get_string('strftimedatetimeshort'));
        
        $row = [
            $studentname,
            $assignmentname,
            $submissiondate,
            $overallquality,
            $cognitivedepth,
            $coherence,
            $evidencequality,
            $topcompsstr,
            $strengthsstr,
            $devprioritiesstr,
            $analyzeddate,
        ];
        
        // Add cost if user can view.
        if (has_capability('assignsubmission/lid:viewcosts', $context)) {
            $row[] = '$' . number_format($analysis->api_cost_usd, 4);
        }
        
        $csvexport->add_data($row);
    }
    
    $csvexport->download_file();
    exit;
}

/**
 * Export student portfolio to PDF.
 *
 * @param int $userid Student user ID
 * @param int $courseid Course ID
 */
function export_pdf($userid, $courseid) {
    global $DB, $CFG;
    
    require_once($CFG->libdir . '/pdflib.php');
    
    if (!$userid || !$courseid) {
        throw new moodle_exception('missingparam', 'error', '', 'userid and courseid');
    }
    
    // Check permissions.
    $context = context_course::instance($courseid);
    require_capability('assignsubmission/lid:viewreports', $context);
    
    // Get user and course.
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    
    // Get all analyses for this student in this course.
    $analyses = $DB->get_records_sql(
        "SELECT a.*, asn.name as assignmentname
           FROM {assignsubmission_lid_analysis} a
           JOIN {assign} asn ON asn.id = a.assignmentid
          WHERE a.userid = ? AND asn.course = ?
       ORDER BY a.analyzed_at ASC",
        [$userid, $courseid]
    );
    
    if (empty($analyses)) {
        throw new moodle_exception('noanalysestoexport', 'assignsubmission_lid');
    }
    
    // Create PDF.
    $pdf = new pdf();
    $pdf->SetTitle(get_string('learningportfolio', 'assignsubmission_lid') . ' - ' . fullname($user));
    $pdf->SetAuthor($CFG->wwwroot);
    $pdf->SetCreator('Moodle LID Plugin');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    
    // Add first page.
    $pdf->AddPage();
    
    // Cover page.
    $pdf->SetFont('helvetica', 'B', 24);
    $pdf->Cell(0, 20, get_string('learningportfolio', 'assignsubmission_lid'), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 16);
    $pdf->Cell(0, 10, fullname($user), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, format_string($course->fullname), 0, 1, 'C');
    $pdf->Cell(0, 10, userdate(time(), get_string('strftimedatefullshort')), 0, 1, 'C');
    
    $pdf->Ln(20);
    
    // Summary section.
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, get_string('summary', 'assignsubmission_lid'), 0, 1);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 5, get_string('totalanalyses', 'assignsubmission_lid') . ': ' . count($analyses));
    
    $pdf->Ln(10);
    
    // Competency summary.
    $allcomps = [];
    foreach ($analyses as $analysis) {
        $data = json_decode($analysis->analysis_json);
        if ($data && isset($data->competency_demonstration)) {
            foreach ($data->competency_demonstration as $comp) {
                $compname = $comp->competency_name;
                if (!isset($allcomps[$compname])) {
                    $allcomps[$compname] = ['max_bloom' => 0, 'count' => 0];
                }
                if ($comp->bloom_level > $allcomps[$compname]['max_bloom']) {
                    $allcomps[$compname]['max_bloom'] = $comp->bloom_level;
                }
                $allcomps[$compname]['count']++;
            }
        }
    }
    
    if (!empty($allcomps)) {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, get_string('competencysummary', 'assignsubmission_lid'), 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        
        // Sort by max Bloom's level.
        uasort($allcomps, function($a, $b) {
            return $b['max_bloom'] - $a['max_bloom'];
        });
        
        foreach ($allcomps as $compname => $compdata) {
            $pdf->MultiCell(0, 5, '• ' . $compname . ' (Max Bloom\'s L' . $compdata['max_bloom'] . ', demonstrated ' . $compdata['count'] . ' times)');
        }
        
        $pdf->Ln(10);
    }
    
    // Assignment details.
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, get_string('assignmentdetails', 'assignsubmission_lid'), 0, 1);
    
    foreach ($analyses as $analysis) {
        $data = json_decode($analysis->analysis_json);
        
        if (!$data) {
            continue;
        }
        
        // Assignment header.
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, $analysis->assignmentname, 0, 1);
        
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->Cell(0, 5, get_string('analyzed', 'assignsubmission_lid') . ': ' . userdate($analysis->analyzed_at, get_string('strftimedatetimeshort')), 0, 1);
        
        $pdf->Ln(3);
        
        // Scores.
        $pdf->SetFont('helvetica', '', 10);
        
        if (isset($data->submission_analysis)) {
            $pdf->MultiCell(0, 5, get_string('overallquality', 'assignsubmission_lid') . ': ' . ($data->submission_analysis->overall_quality_score ?? 'N/A'));
            $pdf->MultiCell(0, 5, get_string('cognitivedepth', 'assignsubmission_lid') . ': ' . ($data->submission_analysis->cognitive_depth_score ?? 'N/A'));
        }
        
        $pdf->Ln(3);
        
        // Top competencies.
        if (isset($data->competency_demonstration)) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, get_string('topcompetencies', 'assignsubmission_lid') . ':', 0, 1);
            
            $pdf->SetFont('helvetica', '', 10);
            $topcomps = array_slice($data->competency_demonstration, 0, 3);
            foreach ($topcomps as $comp) {
                $pdf->MultiCell(0, 5, '  • ' . $comp->competency_name . ' (Bloom\'s L' . $comp->bloom_level . ')');
            }
            
            $pdf->Ln(3);
        }
        
        // Feedback.
        if (isset($data->formative_feedback)) {
            if (!empty($data->formative_feedback->key_strengths)) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(0, 5, get_string('keystrengths', 'assignsubmission_lid') . ':', 0, 1);
                
                $pdf->SetFont('helvetica', '', 10);
                foreach (array_slice($data->formative_feedback->key_strengths, 0, 3) as $strength) {
                    $pdf->MultiCell(0, 5, '  • ' . $strength);
                }
                
                $pdf->Ln(2);
            }
            
            if (!empty($data->formative_feedback->development_priorities)) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(0, 5, get_string('developmentpriorities', 'assignsubmission_lid') . ':', 0, 1);
                
                $pdf->SetFont('helvetica', '', 10);
                foreach (array_slice($data->formative_feedback->development_priorities, 0, 3) as $priority) {
                    $pdf->MultiCell(0, 5, '  • ' . $priority);
                }
            }
        }
        
        $pdf->Ln(8);
        
        // Page break if needed.
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
        }
    }
    
    // Output PDF.
    $filename = clean_filename('LID_Portfolio_' . fullname($user) . '_' . date('Y-m-d') . '.pdf');
    $pdf->Output($filename, 'D');
    exit;
}
