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
 * Library of interface functions and constants for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Load assignment library which contains the parent class.
// In Moodle 5.1+, submission plugin classes are in locallib.php
global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Assignment submission plugin class for LID.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_lid extends assign_submission_plugin {

    /**
     * Get the name of the LID submission plugin.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_lid');
    }

    /**
     * Get the settings for LID submission plugin.
     *
     * @param MoodleQuickForm $mform The form to add elements to.
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        // Enable LID analysis checkbox.
        $mform->addElement(
            'selectyesno',
            'assignsubmission_lid_enabled',
            get_string('enabled', 'assignsubmission_lid')
        );
        $mform->addHelpButton(
            'assignsubmission_lid_enabled',
            'enabled',
            'assignsubmission_lid'
        );
        $mform->setDefault('assignsubmission_lid_enabled', 0);
        $mform->disabledIf('assignsubmission_lid_enabled', 'assignsubmission_onlinetext_enabled', 'eq', 0);
        $mform->disabledIf('assignsubmission_lid_enabled', 'assignsubmission_file_enabled', 'eq', 0);

        // Include competency analysis checkbox.
        $mform->addElement(
            'selectyesno',
            'assignsubmission_lid_competencies',
            get_string('includecompetencies', 'assignsubmission_lid')
        );
        $mform->addHelpButton(
            'assignsubmission_lid_competencies',
            'includecompetencies',
            'assignsubmission_lid'
        );
        $mform->setDefault('assignsubmission_lid_competencies', 1);
        $mform->disabledIf('assignsubmission_lid_competencies', 'assignsubmission_lid_enabled', 'eq', 0);

        // Generate rubric score suggestions checkbox.
        $mform->addElement(
            'selectyesno',
            'assignsubmission_lid_rubricscores',
            get_string('generaterubricscores', 'assignsubmission_lid')
        );
        $mform->addHelpButton(
            'assignsubmission_lid_rubricscores',
            'generaterubricscores',
            'assignsubmission_lid'
        );
        $mform->setDefault('assignsubmission_lid_rubricscores', 1);
        $mform->disabledIf('assignsubmission_lid_rubricscores', 'assignsubmission_lid_enabled', 'eq', 0);

        // Auto-analyze on submission (disabled in v0.1.0).
        $mform->addElement(
            'selectyesno',
            'assignsubmission_lid_autoanalyze',
            get_string('autoanalyze', 'assignsubmission_lid')
        );
        $mform->addHelpButton(
            'assignsubmission_lid_autoanalyze',
            'autoanalyze',
            'assignsubmission_lid'
        );
        $mform->setDefault('assignsubmission_lid_autoanalyze', 0);
        $mform->disabledIf('assignsubmission_lid_autoanalyze', 'assignsubmission_lid_enabled', 'eq', 0);
        // Freeze this setting for v0.1.0 (coming in v0.2.0).
        $mform->freeze('assignsubmission_lid_autoanalyze');
    }

    /**
     * Save the settings for LID submission plugin.
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('enabled', !empty($data->assignsubmission_lid_enabled));
        $this->set_config('competencies', !empty($data->assignsubmission_lid_competencies));
        $this->set_config('rubricscores', !empty($data->assignsubmission_lid_rubricscores));
        $this->set_config('autoanalyze', !empty($data->assignsubmission_lid_autoanalyze));

        return true;
    }

    /**
     * Display the LID analysis in the submission summary.
     *
     * This is shown in the single student grading view.
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set to true to show a link to view the full analysis.
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        global $DB, $OUTPUT;

        // Check if LID is enabled for this assignment.
        if (!$this->get_config('enabled')) {
            return '';
        }

        // Get the analysis for this submission.
        $analysis = $DB->get_record('assignsubmission_lid_analysis', [
            'submissionid' => $submission->id,
            'submission_version' => $submission->attemptnumber,
        ]);

        if (!$analysis) {
            // No analysis yet - show analyze button.
            return $OUTPUT->render_from_template('assignsubmission_lid/no_analysis', [
                'submissionid' => $submission->id,
                'cananalyze' => has_capability('assignsubmission/lid:analyze', $this->assignment->get_context()),
            ]);
        }

        // Parse the analysis JSON.
        $data = json_decode($analysis->analysis_json);

        // Prepare data for template.
        $templatedata = [
            'hasanalysis' => true,
            'overallquality' => $data->submission_analysis->overall_quality_score ?? 0,
            'cognitivedepth' => $data->submission_analysis->cognitive_depth_score ?? 0,
            'coherence' => $data->submission_analysis->coherence_score ?? 0,
            'evidencequality' => $data->submission_analysis->evidence_quality_score ?? 0,
            'keystrengths' => $data->formative_feedback->key_strengths ?? [],
            'developmentpriorities' => $data->formative_feedback->development_priorities ?? [],
            'topcompetencies' => array_slice($data->competency_demonstration ?? [], 0, 3),
            'analyzedat' => userdate($analysis->analyzed_at),
            'canreanalyze' => has_capability('assignsubmission/lid:analyze', $this->assignment->get_context()),
            'submissionid' => $submission->id,
        ];

        $showviewlink = true;

        return $OUTPUT->render_from_template('assignsubmission_lid/analysis_summary', $templatedata);
    }

    /**
     * The assignment submission LID plugin has no submission component,
     * so this method always returns false.
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        // LID doesn't add to the submission itself, so always return true.
        return true;
    }

    /**
     * Determine if the plugin is enabled.
     *
     * @return bool
     */
    public function is_enabled() {
        return (bool)$this->get_config('enabled');
    }

    /**
     * Load custom CSS files based on configuration.
     *
     * This is called automatically by Moodle's page rendering system.
     *
     * @param moodle_page $page The page we are going to add requirements to.
     */
    public function add_to_page(moodle_page $page) {
        global $CFG;

        // Always load base styles.
        $page->requires->css('/mod/assign/submission/lid/styles.css');

        // Conditionally load LID branding styles if enabled.
        if (get_config('assignsubmission_lid', 'futuristicui')) {
            $page->requires->css('/mod/assign/submission/lid/styles-lid-brand.css');
        }
    }

    /**
     * Add a custom column to the grading table.
     *
     * This shows LID analysis status for each student in the grading table.
     *
     * @param stdClass $grade The grade record
     * @param stdClass $submission The submission record
     * @return string HTML for the column
     */
    public function format_for_table($submission) {
        global $DB, $OUTPUT;

        // Check if LID is enabled for this assignment.
        if (!$this->get_config('enabled')) {
            return '';
        }

        // Get the analysis for this submission.
        $analysis = $DB->get_record('assignsubmission_lid_analysis', [
            'assignmentid' => $this->assignment->get_instance()->id,
            'userid' => $submission->userid,
            'submission_version' => $submission->attemptnumber ?? 0,
        ]);

        // Check if in queue.
        $queued = $DB->record_exists('assignsubmission_lid_queue', [
            'assignmentid' => $this->assignment->get_instance()->id,
            'userid' => $submission->userid,
            'status' => 'pending',
        ]);

        $processing = $DB->record_exists('assignsubmission_lid_queue', [
            'assignmentid' => $this->assignment->get_instance()->id,
            'userid' => $submission->userid,
            'status' => 'processing',
        ]);

        $cananalyze = has_capability('assignsubmission/lid:analyze', $this->assignment->get_context());

        $data = [
            'analyzed' => (bool)$analysis,
            'pending' => $queued,
            'processing' => $processing,
            'notqueued' => !$analysis && !$queued && !$processing,
            'cananalyze' => $cananalyze,
            'userid' => $submission->userid,
            'assignmentid' => $this->assignment->get_instance()->id,
        ];

        if ($analysis) {
            $data['analyzedat'] = userdate($analysis->analyzed_at, get_string('strftimedatetimeshort'));
            if (has_capability('assignsubmission/lid:viewcosts', $this->assignment->get_context())) {
                $data['cost'] = '$' . number_format($analysis->api_cost_usd, 4);
            }
            $data['viewurl'] = new \moodle_url('/mod/assign/submission/lid/view.php', [
                'assignid' => $this->assignment->get_instance()->id,
                'userid' => $submission->userid,
            ]);
        }

        return $OUTPUT->render_from_template('assignsubmission_lid/grading_table_cell', $data);
    }

    /**
     * Get the name of the grading table column.
     *
     * @return string
     */
    public function get_editor_text() {
        return get_string('lid', 'assignsubmission_lid');
    }
}
