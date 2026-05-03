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
 * Error handler for LID plugin.
 *
 * Provides comprehensive error logging, categorization, and recovery strategies.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid;

defined('MOODLE_INTERNAL') || die();

/**
 * Error handler class.
 */
class error_handler {

    /** @var int Error category: API connection */
    const ERROR_API_CONNECTION = 1;

    /** @var int Error category: API authentication */
    const ERROR_API_AUTH = 2;

    /** @var int Error category: API rate limit */
    const ERROR_API_RATE_LIMIT = 3;

    /** @var int Error category: API quota exceeded */
    const ERROR_API_QUOTA = 4;

    /** @var int Error category: Invalid response */
    const ERROR_INVALID_RESPONSE = 5;

    /** @var int Error category: Submission not found */
    const ERROR_SUBMISSION_NOT_FOUND = 6;

    /** @var int Error category: Empty submission */
    const ERROR_EMPTY_SUBMISSION = 7;

    /** @var int Error category: Unsupported file type */
    const ERROR_UNSUPPORTED_FILE_TYPE = 8;

    /** @var int Error category: File extraction failed */
    const ERROR_FILE_EXTRACTION = 9;

    /** @var int Error category: JSON validation failed */
    const ERROR_JSON_VALIDATION = 10;

    /** @var int Error category: Database error */
    const ERROR_DATABASE = 11;

    /** @var int Error category: Unknown/other */
    const ERROR_UNKNOWN = 99;

    /**
     * Log an error with context and categorization.
     *
     * @param int $category Error category (use ERROR_* constants)
     * @param string $message Error message
     * @param array $context Additional context data
     * @param \Throwable|null $exception Original exception if available
     * @return void
     */
    public static function log_error(int $category, string $message, array $context = [], ?\Throwable $exception = null) {
        global $DB;

        // Build error details.
        $details = [
            'category' => self::get_category_name($category),
            'message' => $message,
            'context' => $context,
            'timestamp' => time(),
        ];

        if ($exception) {
            $details['exception'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        // Log to Moodle's error log.
        mtrace('[LID Error] ' . json_encode($details));

        // Also log to debugging if enabled.
        if (debugging()) {
            debugging('[LID] ' . $message . ' | Category: ' . self::get_category_name($category), DEBUG_DEVELOPER);
        }
    }

    /**
     * Determine if an error is retryable.
     *
     * @param int $category Error category
     * @return bool True if error is retryable
     */
    public static function is_retryable(int $category): bool {
        $retryable = [
            self::ERROR_API_CONNECTION,
            self::ERROR_API_RATE_LIMIT,
            self::ERROR_INVALID_RESPONSE,
            self::ERROR_FILE_EXTRACTION,
        ];

        return in_array($category, $retryable);
    }

    /**
     * Get error category name.
     *
     * @param int $category Error category
     * @return string Category name
     */
    public static function get_category_name(int $category): string {
        $names = [
            self::ERROR_API_CONNECTION => 'API Connection',
            self::ERROR_API_AUTH => 'API Authentication',
            self::ERROR_API_RATE_LIMIT => 'API Rate Limit',
            self::ERROR_API_QUOTA => 'API Quota Exceeded',
            self::ERROR_INVALID_RESPONSE => 'Invalid Response',
            self::ERROR_SUBMISSION_NOT_FOUND => 'Submission Not Found',
            self::ERROR_EMPTY_SUBMISSION => 'Empty Submission',
            self::ERROR_UNSUPPORTED_FILE_TYPE => 'Unsupported File Type',
            self::ERROR_FILE_EXTRACTION => 'File Extraction Failed',
            self::ERROR_JSON_VALIDATION => 'JSON Validation Failed',
            self::ERROR_DATABASE => 'Database Error',
            self::ERROR_UNKNOWN => 'Unknown Error',
        ];

        return $names[$category] ?? 'Unknown Category';
    }

    /**
     * Get retry delay for a given attempt number.
     *
     * Uses exponential backoff: 1min, 5min, 15min, 30min, 60min
     *
     * @param int $attempt Attempt number (0-based)
     * @return int Delay in seconds
     */
    public static function get_retry_delay(int $attempt): int {
        $delays = [60, 300, 900, 1800, 3600]; // 1min, 5min, 15min, 30min, 60min
        $index = min($attempt, count($delays) - 1);
        return $delays[$index];
    }

    /**
     * Handle an error during queue processing.
     *
     * Updates queue entry with error details and determines retry strategy.
     *
     * @param int $queueid Queue entry ID
     * @param int $category Error category
     * @param string $message Error message
     * @param \Throwable|null $exception Original exception
     * @return void
     */
    public static function handle_queue_error(int $queueid, int $category, string $message, ?\Throwable $exception = null) {
        global $DB;

        $queue = $DB->get_record('assignsubmission_lid_queue', ['id' => $queueid]);
        if (!$queue) {
            return;
        }

        // Log the error.
        self::log_error($category, $message, [
            'queueid' => $queueid,
            'submissionid' => $queue->submissionid,
            'attempt' => $queue->attempt,
        ], $exception);

        // Determine if we should retry.
        $maxretries = get_config('assignsubmission_lid', 'maxretries') ?: 3;

        if (self::is_retryable($category) && $queue->attempt < $maxretries) {
            // Retry - reset to pending with incremented attempt.
            $queue->status = 'pending';
            $queue->attempt++;
            $queue->claimed_at = null;
            $queue->claimed_by = null;
            $queue->error_message = self::truncate_error($message);

            $DB->update_record('assignsubmission_lid_queue', $queue);

        } else {
            // Mark as failed.
            $queue->status = 'failed';
            $queue->processed_at = time();
            $queue->error_message = self::truncate_error($message);

            $DB->update_record('assignsubmission_lid_queue', $queue);
        }
    }

    /**
     * Truncate error message to fit in database field.
     *
     * @param string $message Error message
     * @param int $maxlength Maximum length
     * @return string Truncated message
     */
    protected static function truncate_error(string $message, int $maxlength = 500): string {
        if (strlen($message) <= $maxlength) {
            return $message;
        }

        return substr($message, 0, $maxlength - 3) . '...';
    }

    /**
     * Categorize an exception.
     *
     * Determines error category based on exception type and message.
     *
     * @param \Throwable $exception Exception
     * @return int Error category
     */
    public static function categorize_exception(\Throwable $exception): int {
        $message = strtolower($exception->getMessage());

        // API connection errors.
        if (strpos($message, 'connection') !== false ||
            strpos($message, 'timeout') !== false ||
            strpos($message, 'network') !== false) {
            return self::ERROR_API_CONNECTION;
        }

        // Authentication errors.
        if (strpos($message, 'api key') !== false ||
            strpos($message, 'unauthorized') !== false ||
            strpos($message, '401') !== false) {
            return self::ERROR_API_AUTH;
        }

        // Rate limiting.
        if (strpos($message, 'rate limit') !== false ||
            strpos($message, '429') !== false) {
            return self::ERROR_API_RATE_LIMIT;
        }

        // Quota exceeded.
        if (strpos($message, 'quota') !== false ||
            strpos($message, 'limit exceeded') !== false) {
            return self::ERROR_API_QUOTA;
        }

        // JSON validation.
        if (strpos($message, 'json') !== false ||
            strpos($message, 'invalid response') !== false) {
            return self::ERROR_JSON_VALIDATION;
        }

        // Database errors.
        if ($exception instanceof \dml_exception) {
            return self::ERROR_DATABASE;
        }

        // Default: unknown.
        return self::ERROR_UNKNOWN;
    }

    /**
     * Validate API key format.
     *
     * @param string $apikey API key to validate
     * @return array Array with 'valid' (bool) and 'message' (string)
     */
    public static function validate_api_key(string $apikey): array {
        if (empty($apikey)) {
            return [
                'valid' => false,
                'message' => get_string('error:emptyapikey', 'assignsubmission_lid'),
            ];
        }

        if (strlen($apikey) < 20) {
            return [
                'valid' => false,
                'message' => get_string('error:apikeytooshort', 'assignsubmission_lid'),
            ];
        }

        // Google AI Studio API keys typically start with specific prefixes.
        // This is a basic check and may need adjustment.
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $apikey)) {
            return [
                'valid' => false,
                'message' => get_string('error:apikeyinvalidformat', 'assignsubmission_lid'),
            ];
        }

        return [
            'valid' => true,
            'message' => '',
        ];
    }
}
