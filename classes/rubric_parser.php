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
 * Rubric parser for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/grading/lib.php');

/**
 * Parser for extracting rubric and marking guide data.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rubric_parser {

    /**
     * Parse rubric or marking guide for an assignment.
     *
     * @param int $assignmentid Assignment ID.
     * @param object $context Assignment context.
     * @return array|null Rubric data or null if no advanced grading.
     */
    public function parse_rubric(int $assignmentid, $context): ?array {
        $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
        $gradingmethod = $gradingmanager->get_active_method();

        if (empty($gradingmethod)) {
            // No advanced grading method configured.
            return null;
        }

        if ($gradingmethod === 'rubric') {
            return $this->parse_rubric_method($gradingmanager);
        } else if ($gradingmethod === 'guide') {
            return $this->parse_guide_method($gradingmanager);
        }

        // Other grading methods not supported in v0.1.0.
        return null;
    }

    /**
     * Parse rubric grading method.
     *
     * @param object $gradingmanager Grading manager instance.
     * @return array Rubric data.
     */
    private function parse_rubric_method($gradingmanager): array {
        $controller = $gradingmanager->get_controller($gradingmanager->get_active_method());
        $definition = $controller->get_definition();

        $rubric = [
            'type' => 'rubric',
            'name' => $definition->name,
            'description' => $definition->description,
            'criteria' => [],
        ];

        if (!empty($definition->rubric_criteria)) {
            foreach ($definition->rubric_criteria as $criterionid => $criterion) {
                $levels = [];

                if (!empty($criterion['levels'])) {
                    foreach ($criterion['levels'] as $levelid => $level) {
                        $levels[] = [
                            'id' => $levelid,
                            'score' => (float)$level['score'],
                            'definition' => $level['definition'],
                        ];
                    }

                    // Sort levels by score ascending.
                    usort($levels, function($a, $b) {
                        return $a['score'] <=> $b['score'];
                    });
                }

                $rubric['criteria'][] = [
                    'id' => $criterionid,
                    'description' => $criterion['description'],
                    'levels' => $levels,
                    'max_score' => !empty($levels) ? max(array_column($levels, 'score')) : 0,
                ];
            }
        }

        return $rubric;
    }

    /**
     * Parse marking guide method.
     *
     * @param object $gradingmanager Grading manager instance.
     * @return array Marking guide data.
     */
    private function parse_guide_method($gradingmanager): array {
        $controller = $gradingmanager->get_controller($gradingmanager->get_active_method());
        $definition = $controller->get_definition();

        $guide = [
            'type' => 'guide',
            'name' => $definition->name,
            'description' => $definition->description,
            'criteria' => [],
        ];

        if (!empty($definition->guide_criteria)) {
            foreach ($definition->guide_criteria as $criterionid => $criterion) {
                $guide['criteria'][] = [
                    'id' => $criterionid,
                    'shortname' => $criterion['shortname'],
                    'description' => $criterion['description'],
                    'max_score' => (float)$criterion['maxscore'],
                ];
            }
        }

        return $guide;
    }

    /**
     * Format rubric data for inclusion in LLM prompt.
     *
     * @param array $rubricdata Rubric data from parse_rubric().
     * @param string $format Output format: 'json' or 'markdown'.
     * @return string Formatted rubric string.
     */
    public function format_for_prompt(array $rubricdata, string $format = 'json'): string {
        if ($format === 'markdown') {
            return $this->format_as_markdown($rubricdata);
        } else {
            return json_encode($rubricdata, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Format rubric as markdown for better LLM readability.
     *
     * @param array $rubricdata Rubric data.
     * @return string Markdown formatted rubric.
     */
    private function format_as_markdown(array $rubricdata): string {
        $output = "## {$rubricdata['name']}\n\n";

        if (!empty($rubricdata['description'])) {
            $output .= "{$rubricdata['description']}\n\n";
        }

        if ($rubricdata['type'] === 'rubric') {
            foreach ($rubricdata['criteria'] as $criterion) {
                $output .= "### {$criterion['description']}\n\n";
                $output .= "| Score | Description |\n";
                $output .= "|-------|-------------|\n";

                foreach ($criterion['levels'] as $level) {
                    $score = $level['score'];
                    $desc = str_replace("\n", " ", $level['definition']);
                    $output .= "| {$score} | {$desc} |\n";
                }

                $output .= "\n";
            }
        } else if ($rubricdata['type'] === 'guide') {
            $output .= "| Criterion | Max Score | Description |\n";
            $output .= "|-----------|-----------|-------------|\n";

            foreach ($rubricdata['criteria'] as $criterion) {
                $name = $criterion['shortname'];
                $max = $criterion['max_score'];
                $desc = str_replace("\n", " ", $criterion['description']);
                $output .= "| {$name} | {$max} | {$desc} |\n";
            }

            $output .= "\n";
        }

        return $output;
    }
}
