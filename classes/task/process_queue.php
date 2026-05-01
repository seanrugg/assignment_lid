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
 * Scheduled task for processing LID analysis queue.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid\task;

defined('MOODLE_INTERNAL') || die();

use assignsubmission_lid\analyzer;

/**
 * Scheduled task to process the LID analysis queue.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_queue extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return string Task name.
     */
    public function get_name() {
        return get_string('processqueue', 'assignsubmission_lid');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $analyzer = new analyzer();

        // Phase 0: Cleanup stale claims.
        $this->cleanup_stale_claims();

        // Phase 1: Process pending jobs.
        $maxretries = (int)get_config('assignsubmission_lid', 'maxretries') ?: 3;
        $processed = 0;
        $maxprocessperrun = 10; // Process up to 10 jobs per run to avoid timeout.

        while ($processed < $maxprocessperrun) {
            // Claim next job.
            $job = $this->claim_next_job();

            if (!$job) {
                // No more pending jobs.
                break;
            }

            mtrace("Processing LID analysis job {$job->id} for submission {$job->submissionid}...");

            try {
                // Analyze the submission.
                $result = $analyzer->analyze_submission($job->submissionid);

                // Mark as completed.
                $job->status = 'completed';
                $job->processed_at = time();
                $job->error_message = null;
                $DB->update_record('assignsubmission_lid_queue', $job);

                mtrace("  ✓ Completed successfully (analysis ID: {$result->analysis_id})");

                $processed++;

            } catch (\Exception $e) {
                // Handle failure.
                $job->attempt++;

                if ($job->attempt >= $maxretries) {
                    // Max retries exceeded - mark as failed.
                    $job->status = 'failed';
                    $job->error_message = $e->getMessage();
                    $job->processed_at = time();
                    mtrace("  ✗ Failed permanently: " . $e->getMessage());
                } else {
                    // Re-queue for retry.
                    $job->status = 'pending';
                    $job->claimed_at = null;
                    $job->claimed_by = null;
                    $job->error_message = $e->getMessage();
                    mtrace("  ⚠ Failed (attempt {$job->attempt}/{$maxretries}), re-queued: " . $e->getMessage());
                }

                $DB->update_record('assignsubmission_lid_queue', $job);
            }
        }

        if ($processed > 0) {
            mtrace("Processed {$processed} LID analysis job(s).");
        } else {
            mtrace("No pending LID analysis jobs.");
        }
    }

    /**
     * Cleanup stale job claims.
     *
     * Jobs that have been claimed for longer than the stale threshold are released.
     */
    private function cleanup_stale_claims() {
        global $DB;

        $stalethreshold = (int)get_config('assignsubmission_lid', 'staleclaimseconds') ?: 600;
        $staletime = time() - $stalethreshold;

        $sql = "UPDATE {assignsubmission_lid_queue}
                   SET status = 'pending',
                       claimed_at = NULL,
                       claimed_by = NULL
                 WHERE status = 'processing'
                   AND claimed_at < :staletime";

        $params = ['staletime' => $staletime];
        $count = $DB->execute($sql, $params);

        if ($count > 0) {
            mtrace("Cleaned up {$count} stale LID queue claim(s).");
        }
    }

    /**
     * Claim the next pending job atomically.
     *
     * @return object|null Job record or null if none available.
     */
    private function claim_next_job(): ?object {
        global $DB;

        $instanceid = gethostname() . '_' . getmypid();

        // Use transaction for atomic claim.
        $transaction = $DB->start_delegated_transaction();

        try {
            // Find next pending job.
            $sql = "SELECT *
                      FROM {assignsubmission_lid_queue}
                     WHERE status = 'pending'
                  ORDER BY priority DESC, created_at ASC";

            $jobs = $DB->get_records_sql($sql, null, 0, 1);

            if (empty($jobs)) {
                $transaction->allow_commit();
                return null;
            }

            $job = reset($jobs);

            // Claim the job.
            $job->status = 'processing';
            $job->claimed_at = time();
            $job->claimed_by = $instanceid;

            $DB->update_record('assignsubmission_lid_queue', $job);

            $transaction->allow_commit();

            return $job;

        } catch (\Exception $e) {
            $transaction->rollback($e);
            return null;
        }
    }
}
