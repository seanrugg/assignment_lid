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
 * Competency mapper for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid;

defined('MOODLE_INTERNAL') || die();

use core_competency\api;
use core_competency\course_competency;

/**
 * Mapper for fetching and formatting course competencies.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_mapper {

    /**
     * Get course competencies.
     *
     * @param int $courseid Course ID.
     * @return array Array of competency data.
     */
    public function get_course_competencies(int $courseid): array {
        global $CFG;

        // Check if competencies are enabled.
        if (empty($CFG->enablecompetencies)) {
            return [];
        }

        try {
            // Get course competencies.
            $coursecompetencies = api::list_course_competencies($courseid);

            $competencies = [];

            foreach ($coursecompetencies as $coursecompetency) {
                $competency = $coursecompetency['competency'];

                $competencies[] = [
                    'id' => $competency->get('id'),
                    'shortname' => $competency->get('shortname'),
                    'description' => $this->clean_description($competency->get('description')),
                    'idnumber' => $competency->get('idnumber'),
                ];
            }

            return $competencies;

        } catch (\Exception $e) {
            // If competency API fails, return empty array.
            debugging('Failed to fetch course competencies: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

    /**
     * Format competencies for inclusion in LLM prompt.
     *
     * @param array $competencies Competency data from get_course_competencies().
     * @param string $format Output format: 'json' or 'markdown'.
     * @return string Formatted competencies string.
     */
    public function format_for_prompt(array $competencies, string $format = 'json'): string {
        if (empty($competencies)) {
            return 'No competencies configured for this course.';
        }

        if ($format === 'markdown') {
            return $this->format_as_markdown($competencies);
        } else {
            return json_encode($competencies, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Format competencies as markdown for better LLM readability.
     *
     * @param array $competencies Competency data.
     * @return string Markdown formatted competencies.
     */
    private function format_as_markdown(array $competencies): string {
        $output = "## Course Competencies\n\n";
        $output .= "| ID | Name | Description |\n";
        $output .= "|----|------|-------------|\n";

        foreach ($competencies as $comp) {
            $id = $comp['id'];
            $name = $comp['shortname'];
            $desc = str_replace("\n", " ", $comp['description']);
            $desc = substr($desc, 0, 200); // Truncate long descriptions.
            $output .= "| {$id} | {$name} | {$desc} |\n";
        }

        $output .= "\n";

        return $output;
    }

    /**
     * Clean competency description (remove HTML, trim whitespace).
     *
     * @param string $description Raw description.
     * @return string Cleaned description.
     */
    private function clean_description(string $description): string {
        // Strip HTML tags.
        $clean = strip_tags($description);

        // Decode HTML entities.
        $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');

        // Normalize whitespace.
        $clean = preg_replace('/\s+/', ' ', $clean);

        // Trim.
        $clean = trim($clean);

        return $clean;
    }

    /**
     * Map analysis results back to competency IDs.
     *
     * This is used when storing competency demonstration evidence in the database.
     *
     * @param object $analysis Analysis JSON object from LLM.
     * @param array $competencies Original competency data.
     * @return array Mapped competency results with validated IDs.
     */
    public function map_results_to_competencies(object $analysis, array $competencies): array {
        if (empty($analysis->competency_demonstration)) {
            return [];
        }

        $competencymap = [];
        foreach ($competencies as $comp) {
            $competencymap[$comp['id']] = $comp;
        }

        $mapped = [];

        foreach ($analysis->competency_demonstration as $demo) {
            // Validate that the competency ID exists.
            if (isset($competencymap[$demo->competency_id])) {
                $mapped[] = [
                    'competency_id' => $demo->competency_id,
                    'competency_name' => $demo->competency_name,
                    'bloom_level' => $demo->bloom_level,
                    'bloom_label' => $demo->bloom_label,
                    'depth_rating' => $demo->depth_rating,
                    'evidence_excerpt' => $demo->evidence_excerpt,
                    'confidence' => $demo->confidence,
                ];
            }
        }

        return $mapped;
    }
}
