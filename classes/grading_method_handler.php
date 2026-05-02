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
 * Grading method handler for LID.
 *
 * Extracts grading criteria from all Moodle grading methods:
 * - Simple direct grading (points or custom scale)
 * - Rubric (advanced grading)
 * - Marking guide (advanced grading)
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_lid;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/grading/lib.php');

/**
 * Grading method handler class.
 */
class grading_method_handler {

    /** @var object Assignment instance */
    protected $assign;

    /** @var int Assignment ID */
    protected $assignmentid;

    /** @var object Context */
    protected $context;

    /**
     * Constructor.
     *
     * @param object $assign Assignment instance (assign class)
     * @param int $assignmentid Assignment ID
     */
    public function __construct($assign, $assignmentid) {
        $this->assign = $assign;
        $this->assignmentid = $assignmentid;
        $this->context = $assign->get_context();
    }

    /**
     * Get grading criteria in standardized format for LLM prompt.
     *
     * Returns criteria regardless of grading method used.
     *
     * @return array Grading criteria array
     */
    public function get_grading_criteria() {
        $method = $this->detect_grading_method();

        switch ($method) {
            case 'rubric':
                return $this->extract_rubric_criteria();
            case 'guide':
                return $this->extract_marking_guide_criteria();
            case 'scale':
                return $this->extract_scale_criteria();
            case 'point':
            default:
                return $this->extract_point_grading_criteria();
        }
    }

    /**
     * Detect which grading method is in use.
     *
     * @return string Method type: 'rubric', 'guide', 'scale', 'point'
     */
    public function detect_grading_method() {
        global $DB;

        // Check for advanced grading (rubric or marking guide).
        $gradingmanager = get_grading_manager($this->context, 'mod_assign', 'submissions');
        $gradingmethod = $gradingmanager->get_active_method();

        if ($gradingmethod === 'rubric') {
            return 'rubric';
        }

        if ($gradingmethod === 'guide') {
            return 'guide';
        }

        // Check if using a custom scale.
        $assignment = $DB->get_record('assign', ['id' => $this->assignmentid]);
        if ($assignment && $assignment->grade < 0) {
            // Negative grade value = scale ID.
            return 'scale';
        }

        // Default: simple point grading.
        return 'point';
    }

    /**
     * Extract rubric criteria.
     *
     * @return array Rubric criteria in standardized format
     */
    protected function extract_rubric_criteria() {
        global $DB;

        $gradingmanager = get_grading_manager($this->context, 'mod_assign', 'submissions');
        $controller = $gradingmanager->get_controller('rubric');

        if (!$controller || !$controller->is_form_available()) {
            return $this->extract_point_grading_criteria();
        }

        $definition = $controller->get_definition();
        $criteria = [];

        if (isset($definition->rubric_criteria)) {
            foreach ($definition->rubric_criteria as $criterionid => $criterion) {
                $levels = [];

                if (isset($criterion['levels'])) {
                    foreach ($criterion['levels'] as $levelid => $level) {
                        $levels[] = [
                            'score' => $level['score'] ?? 0,
                            'definition' => strip_tags($level['definition'] ?? ''),
                        ];
                    }

                    // Sort levels by score descending.
                    usort($levels, function($a, $b) {
                        return $b['score'] - $a['score'];
                    });
                }

                $criteria[] = [
                    'criterion_name' => strip_tags($criterion['description'] ?? 'Criterion ' . $criterionid),
                    'type' => 'rubric',
                    'max_score' => max(array_column($levels, 'score')),
                    'levels' => $levels,
                ];
            }
        }

        return [
            'method' => 'rubric',
            'criteria' => $criteria,
            'total_points' => array_sum(array_column($criteria, 'max_score')),
        ];
    }

    /**
     * Extract marking guide criteria.
     *
     * @return array Marking guide criteria in standardized format
     */
    protected function extract_marking_guide_criteria() {
        global $DB;

        $gradingmanager = get_grading_manager($this->context, 'mod_assign', 'submissions');
        $controller = $gradingmanager->get_controller('guide');

        if (!$controller || !$controller->is_form_available()) {
            return $this->extract_point_grading_criteria();
        }

        $definition = $controller->get_definition();
        $criteria = [];

        if (isset($definition->guide_criteria)) {
            foreach ($definition->guide_criteria as $criterionid => $criterion) {
                $criteria[] = [
                    'criterion_name' => strip_tags($criterion['shortname'] ?? 'Criterion ' . $criterionid),
                    'description' => strip_tags($criterion['description'] ?? ''),
                    'type' => 'marking_guide',
                    'max_score' => $criterion['maxscore'] ?? 0,
                ];
            }
        }

        return [
            'method' => 'marking_guide',
            'criteria' => $criteria,
            'total_points' => array_sum(array_column($criteria, 'max_score')),
        ];
    }

    /**
     * Extract custom scale criteria.
     *
     * @return array Scale criteria in standardized format
     */
    protected function extract_scale_criteria() {
        global $DB;

        $assignment = $DB->get_record('assign', ['id' => $this->assignmentid]);
        if (!$assignment || $assignment->grade >= 0) {
            return $this->extract_point_grading_criteria();
        }

        // Grade value is negative scale ID.
        $scaleid = abs($assignment->grade);
        $scale = $DB->get_record('scale', ['id' => $scaleid]);

        if (!$scale) {
            return $this->extract_point_grading_criteria();
        }

        // Parse scale items (comma-separated).
        $scaleitems = explode(',', $scale->scale);
        $scaleitems = array_map('trim', $scaleitems);

        $levels = [];
        foreach ($scaleitems as $index => $item) {
            $levels[] = [
                'level' => $index + 1,
                'label' => $item,
            ];
        }

        return [
            'method' => 'scale',
            'scale_name' => $scale->name,
            'levels' => $levels,
            'criteria' => [[
                'criterion_name' => 'Overall Performance',
                'type' => 'scale',
                'scale_name' => $scale->name,
                'levels' => $levels,
            ]],
        ];
    }

    /**
     * Extract simple point grading criteria.
     *
     * @return array Point grading criteria in standardized format
     */
    protected function extract_point_grading_criteria() {
        global $DB;

        $assignment = $DB->get_record('assign', ['id' => $this->assignmentid]);
        $maxgrade = $assignment ? $assignment->grade : 100;

        return [
            'method' => 'point',
            'max_points' => $maxgrade,
            'criteria' => [[
                'criterion_name' => 'Overall Grade',
                'type' => 'point',
                'max_score' => $maxgrade,
                'description' => 'Simple numeric grade out of ' . $maxgrade . ' points',
            ]],
        ];
    }

    /**
     * Format grading criteria for LLM prompt.
     *
     * Converts internal criteria structure to JSON for prompt inclusion.
     *
     * @return string JSON-encoded criteria
     */
    public function format_for_prompt() {
        $criteria = $this->get_grading_criteria();
        return json_encode($criteria, JSON_PRETTY_PRINT);
    }

    /**
     * Get a human-readable description of the grading method.
     *
     * @return string Description
     */
    public function get_method_description() {
        $method = $this->detect_grading_method();

        switch ($method) {
            case 'rubric':
                return get_string('gradingmethod_rubric', 'assignsubmission_lid');
            case 'guide':
                return get_string('gradingmethod_guide', 'assignsubmission_lid');
            case 'scale':
                return get_string('gradingmethod_scale', 'assignsubmission_lid');
            case 'point':
            default:
                return get_string('gradingmethod_point', 'assignsubmission_lid');
        }
    }

    /**
     * Check if advanced grading is in use.
     *
     * @return bool True if rubric or marking guide is active
     */
    public function is_advanced_grading() {
        $method = $this->detect_grading_method();
        return in_array($method, ['rubric', 'guide']);
    }
}
