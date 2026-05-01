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
 * Prompt builder for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid;

defined('MOODLE_INTERNAL') || die();

/**
 * Builder for constructing LLM prompts from submission data.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_builder {

    /** @var rubric_parser Rubric parser instance */
    private $rubricparser;

    /** @var competency_mapper Competency mapper instance */
    private $competencymapper;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->rubricparser = new rubric_parser();
        $this->competencymapper = new competency_mapper();
    }

    /**
     * Build complete prompt for submission analysis.
     *
     * @param object $submission Submission record.
     * @param object $assignment Assignment instance.
     * @param object $context Assignment context.
     * @param array $options Options (include_competencies, include_rubric).
     * @return string Complete prompt.
     * @throws \moodle_exception If submission text cannot be extracted.
     */
    public function build_prompt(object $submission, object $assignment, object $context, array $options = []): string {
        global $CFG;

        // Load the prompt template.
        $templatepath = $CFG->dirroot . '/mod/assign/submission/lid/prompts/assignment-analyzer-prompt.md';
        $template = file_get_contents($templatepath);

        if ($template === false) {
            throw new \moodle_exception('error:unknown', 'assignsubmission_lid', '', 'Failed to load prompt template');
        }

        // Extract submission text.
        $submissiontext = $this->extract_submission_text($submission, $assignment);

        if (empty($submissiontext)) {
            throw new \moodle_exception('error:emptysubmission', 'assignsubmission_lid');
        }

        // Get assignment description.
        $assignmentdescription = $this->get_assignment_description($assignment);

        // Get rubric data if enabled.
        $rubricdata = '';
        if (!empty($options['include_rubric'])) {
            $rubric = $this->rubricparser->parse_rubric($assignment->get_instance()->id, $context);
            if ($rubric) {
                $rubricdata = $this->rubricparser->format_for_prompt($rubric, 'markdown');
            } else {
                $rubricdata = 'No rubric or marking guide configured for this assignment.';
            }
        } else {
            $rubricdata = 'Rubric analysis not enabled for this assignment.';
        }

        // Get competency data if enabled.
        $competencydata = '';
        if (!empty($options['include_competencies'])) {
            $competencies = $this->competencymapper->get_course_competencies($assignment->get_course()->id);
            if (!empty($competencies)) {
                $competencydata = $this->competencymapper->format_for_prompt($competencies, 'markdown');
            } else {
                $competencydata = 'No competencies configured for this course.';
            }
        } else {
            $competencydata = 'Competency analysis not enabled for this assignment.';
        }

        // Calculate word count.
        $wordcount = str_word_count($submissiontext);

        // Perform substitutions.
        $prompt = str_replace('{ASSIGNMENT_DESCRIPTION}', $assignmentdescription, $template);
        $prompt = str_replace('{SUBMISSION_TEXT}', $submissiontext, $prompt);
        $prompt = str_replace('{RUBRIC_DATA}', $rubricdata, $prompt);
        $prompt = str_replace('{COMPETENCY_DATA}', $competencydata, $prompt);
        $prompt = str_replace('{STUDENT_USERID}', $submission->userid, $prompt);
        $prompt = str_replace('{SUBMISSION_VERSION}', $submission->attemptnumber, $prompt);
        $prompt = str_replace('{WORD_COUNT}', $wordcount, $prompt);

        return $prompt;
    }

    /**
     * Extract submission text from online text or PDF file.
     *
     * @param object $submission Submission record.
     * @param object $assignment Assignment instance.
     * @return string Submission text.
     * @throws \moodle_exception If extraction fails.
     */
    private function extract_submission_text(object $submission, object $assignment): string {
        global $DB;

        // Try online text first.
        $onlinetext = $DB->get_record('assignsubmission_onlinetext', [
            'assignment' => $assignment->get_instance()->id,
            'submission' => $submission->id,
        ]);

        if ($onlinetext && !empty($onlinetext->onlinetext)) {
            // Strip HTML tags and clean up.
            $text = strip_tags($onlinetext->onlinetext);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        }

        // Try file submission (PDF only in v0.1.0).
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $assignment->get_context()->id,
            'assignsubmission_file',
            'submission_files',
            $submission->id,
            'timemodified',
            false
        );

        foreach ($files as $file) {
            $filename = $file->get_filename();
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if (strtolower($ext) === 'pdf') {
                return $this->extract_pdf_text($file);
            }
        }

        // No text found.
        return '';
    }

    /**
     * Extract text from PDF file.
     *
     * @param object $file Stored file object.
     * @return string Extracted text.
     * @throws \moodle_exception If extraction fails.
     */
    private function extract_pdf_text(object $file): string {
        global $CFG;

        // Save file to temp location.
        $tempfile = $CFG->tempdir . '/lid_pdf_' . uniqid() . '.pdf';
        $file->copy_content_to($tempfile);

        try {
            // Try pdftotext command-line tool first.
            if ($this->is_pdftotext_available()) {
                $outputfile = $tempfile . '.txt';
                $cmd = 'pdftotext ' . escapeshellarg($tempfile) . ' ' . escapeshellarg($outputfile);
                exec($cmd, $output, $returncode);

                if ($returncode === 0 && file_exists($outputfile)) {
                    $text = file_get_contents($outputfile);
                    unlink($outputfile);
                    unlink($tempfile);

                    if (!empty($text)) {
                        return trim($text);
                    }
                }
            }

            // Fallback to PHP library (smalot/pdfparser if available).
            if (class_exists('\Smalot\PdfParser\Parser')) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($tempfile);
                $text = $pdf->getText();
                unlink($tempfile);

                if (!empty($text)) {
                    return trim($text);
                }
            }

            // No method succeeded.
            unlink($tempfile);
            throw new \moodle_exception('error:pdfextraction', 'assignsubmission_lid');

        } catch (\Exception $e) {
            if (file_exists($tempfile)) {
                unlink($tempfile);
            }
            throw new \moodle_exception('error:pdfextraction', 'assignsubmission_lid', '', $e->getMessage());
        }
    }

    /**
     * Check if pdftotext command is available.
     *
     * @return bool True if available.
     */
    private function is_pdftotext_available(): bool {
        $output = [];
        $returncode = 0;
        exec('which pdftotext', $output, $returncode);
        return $returncode === 0 && !empty($output);
    }

    /**
     * Get assignment description/instructions.
     *
     * @param object $assignment Assignment instance.
     * @return string Assignment description.
     */
    private function get_assignment_description(object $assignment): string {
        $instance = $assignment->get_instance();

        if (empty($instance->intro)) {
            return 'No assignment instructions provided.';
        }

        // Strip HTML and clean.
        $text = strip_tags($instance->intro);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
