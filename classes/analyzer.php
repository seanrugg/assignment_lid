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
 * Main analyzer for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Orchestrator for submission analysis workflow.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analyzer {

    /** @var gemini_client LLM client */
    private $client;

    /** @var prompt_builder Prompt builder */
    private $promptbuilder;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->client = new gemini_client();
        $this->promptbuilder = new prompt_builder();
    }

    /**
     * Queue a submission for analysis.
     *
     * @param int $submissionid Submission ID.
     * @param int $priority Priority (0-10, default 0).
     * @return int Queue entry ID.
     * @throws \moodle_exception If submission doesn't exist or already queued.
     */
    public function queue_analysis(int $submissionid, int $priority = 0): int {
        global $DB;

        // Get submission record.
        $submission = $DB->get_record('assign_submission', ['id' => $submissionid], '*', MUST_EXIST);

        // Check if already queued or analyzed.
        $existing = $DB->get_record('assignsubmission_lid_queue', [
            'submissionid' => $submissionid,
            'status' => 'pending',
        ]);

        if ($existing) {
            // Already queued.
            return $existing->id;
        }

        // Check if already analyzed for this version.
        $analyzed = $DB->get_record('assignsubmission_lid_analysis', [
            'submissionid' => $submissionid,
            'submission_version' => $submission->attemptnumber,
        ]);

        if ($analyzed) {
            // Already analyzed - user should use re-analyze if they want fresh analysis.
            return 0;
        }

        // Create queue entry.
        $queue = new \stdClass();
        $queue->assignmentid = $submission->assignment;
        $queue->submissionid = $submissionid;
        $queue->userid = $submission->userid;
        $queue->status = 'pending';
        $queue->priority = $priority;
        $queue->attempt = 0;
        $queue->created_at = time();

        return $DB->insert_record('assignsubmission_lid_queue', $queue);
    }

    /**
     * Analyze a submission (called by queue processor).
     *
     * @param int $submissionid Submission ID.
     * @return object Analysis result.
     * @throws \moodle_exception If analysis fails.
     */
    public function analyze_submission(int $submissionid): object {
        global $DB, $USER;

        // Get submission.
        $submission = $DB->get_record('assign_submission', ['id' => $submissionid], '*', MUST_EXIST);

        // Get assignment instance.
        $cm = get_coursemodule_from_instance('assign', $submission->assignment, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $assignment = new \assign($context, $cm, null);

        // Get plugin configuration.
        $config = $DB->get_record('assign_plugin_config', [
            'assignment' => $submission->assignment,
            'plugin' => 'lid',
            'subtype' => 'assignsubmission',
            'name' => 'enabled',
        ]);

        if (!$config || $config->value != 1) {
            throw new \moodle_exception('error:unknown', 'assignsubmission_lid', '', 'LID not enabled for this assignment');
        }

        // Build options from plugin config.
        $options = $this->get_analysis_options($submission->assignment);

        // Build prompt.
        $prompt = $this->promptbuilder->build_prompt($submission, $assignment, $context, $options);

        // Call LLM.
        $result = $this->client->analyze($prompt);

        // Store result.
        $analysisid = $this->store_analysis($submission, $result, $USER->id);

        // Return combined result.
        $result->analysis_id = $analysisid;

        return $result;
    }

    /**
     * Get analysis options from plugin configuration.
     *
     * @param int $assignmentid Assignment ID.
     * @return array Options array.
     */
    private function get_analysis_options(int $assignmentid): array {
        global $DB;

        $options = [];

        // Check competencies setting.
        $competencies = $DB->get_record('assign_plugin_config', [
            'assignment' => $assignmentid,
            'plugin' => 'lid',
            'subtype' => 'assignsubmission',
            'name' => 'competencies',
        ]);

        $options['include_competencies'] = ($competencies && $competencies->value == 1);

        // Check rubric scores setting.
        $rubricscores = $DB->get_record('assign_plugin_config', [
            'assignment' => $assignmentid,
            'plugin' => 'lid',
            'subtype' => 'assignsubmission',
            'name' => 'rubricscores',
        ]);

        $options['include_rubric'] = ($rubricscores && $rubricscores->value == 1);

        return $options;
    }

    /**
     * Store analysis result in database.
     *
     * @param object $submission Submission record.
     * @param object $result Result from LLM client.
     * @param int $analyzeduserid User ID who triggered analysis.
     * @return int Analysis record ID.
     */
    private function store_analysis(object $submission, object $result, int $analyzeduserid): int {
        global $DB;

        $analysis = new \stdClass();
        $analysis->assignmentid = $submission->assignment;
        $analysis->submissionid = $submission->id;
        $analysis->userid = $submission->userid;
        $analysis->submission_version = $submission->attemptnumber;
        $analysis->analysis_json = json_encode($result->analysis_json);
        $analysis->analyzed_at = time();
        $analysis->analyzed_by_userid = $analyzeduserid;
        $analysis->api_cost_usd = $result->api_cost_usd;
        $analysis->input_tokens = $result->input_tokens;
        $analysis->output_tokens = $result->output_tokens;
        $analysis->thought_tokens = $result->thought_tokens;
        $analysis->processing_time_ms = $result->processing_time_ms;
        $analysis->model_version = $result->model_version;

        return $DB->insert_record('assignsubmission_lid_analysis', $analysis);
    }

    /**
     * Get existing analysis for a submission.
     *
     * @param int $submissionid Submission ID.
     * @param int|null $version Submission version (null for latest).
     * @return object|null Analysis record or null if not found.
     */
    public function get_analysis(int $submissionid, ?int $version = null): ?object {
        global $DB;

        if ($version === null) {
            // Get latest version.
            $submission = $DB->get_record('assign_submission', ['id' => $submissionid]);
            $version = $submission ? $submission->attemptnumber : 0;
        }

        return $DB->get_record('assignsubmission_lid_analysis', [
            'submissionid' => $submissionid,
            'submission_version' => $version,
        ]);
    }

    /**
     * Re-analyze a submission (creates new analysis for current version).
     *
     * @param int $submissionid Submission ID.
     * @param int $priority Priority (0-10).
     * @return int Queue entry ID.
     */
    public function reanalyze_submission(int $submissionid, int $priority = 5): int {
        global $DB;

        // Delete any pending queue entries for this submission.
        $DB->delete_records('assignsubmission_lid_queue', [
            'submissionid' => $submissionid,
            'status' => 'pending',
        ]);

        // Queue with higher priority (default 5).
        return $this->queue_analysis($submissionid, $priority);
    }

    /**
     * Get queue status for a submission.
     *
     * @param int $submissionid Submission ID.
     * @return object|null Queue record or null if not queued.
     */
    public function get_queue_status(int $submissionid): ?object {
        global $DB;

        return $DB->get_record('assignsubmission_lid_queue', [
            'submissionid' => $submissionid,
        ], '*', IGNORE_MULTIPLE);
    }

    /**
     * Batch queue multiple submissions.
     *
     * @param array $submissionids Array of submission IDs.
     * @param int $priority Priority (0-10).
     * @return array Array of queued entry IDs.
     */
    public function batch_queue_analyses(array $submissionids, int $priority = 0): array {
        $queueids = [];

        foreach ($submissionids as $submissionid) {
            try {
                $queueid = $this->queue_analysis($submissionid, $priority);
                if ($queueid > 0) {
                    $queueids[] = $queueid;
                }
            } catch (\Exception $e) {
                // Log error but continue with other submissions.
                debugging("Failed to queue submission {$submissionid}: " . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $queueids;
    }
}
