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
 * Gemini API client for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Client for interacting with Google Gemini API.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gemini_client {

    /** @var string API endpoint URL */
    private $endpoint;

    /** @var string API key */
    private $apikey;

    /** @var string Model identifier */
    private $model;

    /** @var int Maximum output tokens */
    private $maxoutputtokens;

    /** @var bool Enable cost tracking */
    private $enablecosttracking;

    /** @var float Cost per 1M input tokens */
    private $costperinput;

    /** @var float Cost per 1M output tokens */
    private $costperoutput;

    /** @var float Cost per 1M thought tokens */
    private $costperthought;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->endpoint = get_config('assignsubmission_lid', 'endpoint');
        $this->apikey = get_config('assignsubmission_lid', 'apikey');
        $this->model = get_config('assignsubmission_lid', 'model');
        $this->maxoutputtokens = (int)get_config('assignsubmission_lid', 'maxoutputtokens');
        $this->enablecosttracking = (bool)get_config('assignsubmission_lid', 'enablecosttracking');
        $this->costperinput = (float)get_config('assignsubmission_lid', 'costper1minputtokens');
        $this->costperoutput = (float)get_config('assignsubmission_lid', 'costper1moutputtokens');
        $this->costperthought = (float)get_config('assignsubmission_lid', 'costper1mthoughttokens');
    }

    /**
     * Analyze a submission using the configured LLM.
     *
     * @param string $prompt The complete prompt to send to the LLM.
     * @param array $options Optional parameters (temperature, etc.).
     * @return object Analysis result with json_response, tokens, cost, etc.
     * @throws \moodle_exception If API call fails.
     */
    public function analyze(string $prompt, array $options = []): object {
        $starttime = microtime(true);

        // Validate API key is configured.
        if (empty($this->apikey)) {
            throw new \moodle_exception('error:apikey', 'assignsubmission_lid');
        }

        // Build request payload.
        $payload = $this->build_payload($prompt, $options);

        // Make the API request.
        $response = $this->make_request($payload);

        // Parse the response.
        $result = $this->parse_response($response);

        // Calculate processing time.
        $endtime = microtime(true);
        $result->processing_time_ms = (int)(($endtime - $starttime) * 1000);

        // Calculate cost if tracking is enabled.
        if ($this->enablecosttracking) {
            $result->api_cost_usd = $this->calculate_cost(
                $result->input_tokens,
                $result->output_tokens,
                $result->thought_tokens
            );
        } else {
            $result->api_cost_usd = 0.0;
        }

        $result->model_version = $this->model;

        return $result;
    }

    /**
     * Build the API request payload.
     *
     * @param string $prompt The prompt text.
     * @param array $options Optional parameters.
     * @return array The request payload.
     */
    private function build_payload(string $prompt, array $options): array {
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => $this->maxoutputtokens,
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        return $payload;
    }

    /**
     * Make the HTTP request to the API.
     *
     * @param array $payload The request payload.
     * @return string The raw response body.
     * @throws \moodle_exception If request fails.
     */
    private function make_request(array $payload): string {
        $curl = new \curl();

        // Set headers.
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apikey,
        ];

        $curl->setHeader($headers);

        // Set timeout (60 seconds).
        $curl->setopt([
            'CURLOPT_TIMEOUT' => 60,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ]);

        // Make POST request.
        $response = $curl->post($this->endpoint, json_encode($payload));

        // Check for errors.
        $info = $curl->get_info();
        $httpcode = $info['http_code'];

        if ($httpcode !== 200) {
            // Try to get error message from response.
            $error = 'HTTP ' . $httpcode;
            $decoded = json_decode($response);
            if ($decoded && isset($decoded->error->message)) {
                $error .= ': ' . $decoded->error->message;
            }

            // Check for specific error types.
            if ($httpcode === 401 || $httpcode === 403) {
                throw new \moodle_exception('error:apikey', 'assignsubmission_lid');
            } else if ($httpcode === 429) {
                throw new \moodle_exception('error:ratelimit', 'assignsubmission_lid');
            } else if ($httpcode === 408 || $httpcode === 504) {
                throw new \moodle_exception('error:apitimeout', 'assignsubmission_lid');
            } else {
                throw new \moodle_exception('error:unknown', 'assignsubmission_lid', '', $error);
            }
        }

        if (empty($response)) {
            throw new \moodle_exception('error:unknown', 'assignsubmission_lid', '', 'Empty response from API');
        }

        return $response;
    }

    /**
     * Parse the API response.
     *
     * @param string $response The raw response body.
     * @return object Parsed result with json_response, tokens, etc.
     * @throws \moodle_exception If response parsing fails.
     */
    private function parse_response(string $response): object {
        $decoded = json_decode($response);

        if (!$decoded) {
            throw new \moodle_exception('error:invalidjson', 'assignsubmission_lid', '', 'Failed to decode JSON response');
        }

        // Extract the content from the response.
        if (!isset($decoded->choices[0]->message->content)) {
            throw new \moodle_exception('error:invalidjson', 'assignsubmission_lid', '', 'No content in response');
        }

        $content = $decoded->choices[0]->message->content;

        // Parse the content as JSON (LLM should return JSON).
        $analysisjson = json_decode($content);

        if (!$analysisjson) {
            // Try to clean the content (sometimes LLM adds markdown fences).
            $cleaned = $this->clean_json_response($content);
            $analysisjson = json_decode($cleaned);

            if (!$analysisjson) {
                throw new \moodle_exception('error:invalidjson', 'assignsubmission_lid', '', 
                    'LLM response is not valid JSON: ' . substr($content, 0, 200));
            }
        }

        // Extract token usage.
        $inputtokens = $decoded->usage->prompt_tokens ?? 0;
        $outputtokens = $decoded->usage->completion_tokens ?? 0;
        // Gemini may provide thought tokens separately or as part of completion tokens.
        $thoughttokens = $decoded->usage->completion_tokens_details->reasoning_tokens ?? 0;

        $result = new \stdClass();
        $result->analysis_json = $analysisjson;
        $result->input_tokens = $inputtokens;
        $result->output_tokens = $outputtokens;
        $result->thought_tokens = $thoughttokens;
        $result->raw_response = $content;

        return $result;
    }

    /**
     * Clean JSON response by removing markdown fences and extra whitespace.
     *
     * @param string $content The raw content.
     * @return string Cleaned content.
     */
    private function clean_json_response(string $content): string {
        // Remove markdown code fences.
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);

        // Trim whitespace.
        $content = trim($content);

        return $content;
    }

    /**
     * Calculate the cost of the API call.
     *
     * @param int $inputtokens Input token count.
     * @param int $outputtokens Output token count.
     * @param int $thoughttokens Thought token count.
     * @return float Cost in USD.
     */
    private function calculate_cost(int $inputtokens, int $outputtokens, int $thoughttokens): float {
        $inputcost = ($inputtokens / 1000000) * $this->costperinput;
        $outputcost = ($outputtokens / 1000000) * $this->costperoutput;
        $thoughtcost = ($thoughttokens / 1000000) * $this->costperthought;

        return $inputcost + $outputcost + $thoughtcost;
    }

    /**
     * Test the API connection.
     *
     * @return bool True if connection is successful.
     */
    public function test_connection(): bool {
        try {
            $testprompt = "Return a JSON object with one field 'test' set to true.";
            $result = $this->analyze($testprompt);
            return isset($result->analysis_json->test) && $result->analysis_json->test === true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
