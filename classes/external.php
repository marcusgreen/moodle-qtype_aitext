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
 * External
 *
 * @package    qtype_aitext
 * @copyright  Justin Hunt - poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

use local_ai_manager\manager;

/**
 * External class.
 *
 * @package qtype_aitext
 * @author  Justin Hunt - poodll.com
 */
class qtype_aitext_external extends external_api {
    /**
     * Get the parameters and types
     *
     * @return void
     */
    public static function fetch_ai_grade_parameters(): external_function_parameters {
        return new external_function_parameters(
            ['response' => new external_value(PARAM_TEXT, 'The students response to question'),
                'defaultmark' => new external_value(PARAM_INT, 'The total possible score'),
                'prompt' => new external_value(PARAM_TEXT, 'The AI Prompt'),
                'marksscheme' => new external_value(PARAM_TEXT, 'The marks scheme'),
                'contextid' => new external_value(PARAM_INT, 'The context id'),
            ]
        );

    }

    /**
     * Similar to clicking the submit button.
     *
     * @param string $response
     * @param int $defaultmark
     * @param string $prompt
     * @param string $marksscheme
     * @param int $contextid the context id
     * @return stdClass the response
     */
    public static function fetch_ai_grade(string $response, int $defaultmark,
            string $prompt, string $marksscheme, int $contextid): stdClass {
        [
                'response' => $response,
                'defaultmark' => $defaultmark,
                'prompt' => $prompt,
                'marksscheme' => $marksscheme,
                'contextid' => $contextid,
        ] = self::validate_parameters(self::fetch_ai_grade_parameters(),
                [
                        'response' => $response,
                        'defaultmark' => $defaultmark,
                        'prompt' => $prompt,
                        'marksscheme' => $marksscheme,
                        'contextid' => $contextid,
                ]
        );
        $context = $contextid === 0 ? context_system::instance() : context::instance_by_id($contextid);
        self::validate_context($context);

        // TODO Eventually move this to a own capability which by default is assigned to a teacher in a course.
        require_capability('mod/quiz:grade', $context);

        // Build an aitext question instance so we can call the same code that the question type uses when it grades.
        $type = 'aitext';
        \question_bank::load_question_definition_classes($type);
        $aiquestion = new qtype_aitext_question();
        $aiquestion->contextid = $contextid;
        $aiquestion->qtype = \question_bank::get_qtype('aitext');
        // Make sure we have the right data for AI to work with.
        if (!empty($response) && !empty($prompt) && $defaultmark > 0) {
            $fullaiprompt = $aiquestion->build_full_ai_prompt($response, $prompt, $defaultmark, $marksscheme);
            $feedback = $aiquestion->perform_request($fullaiprompt);
            $contentobject = $aiquestion->process_feedback($feedback);
        } else {
            $contentobject = (object)["feedback" => get_string('err_parammissing', 'qtype_aitext'), "marks" => 0];
        }
        // Return whatever we have got.
        return $contentobject;
    }

    /**
     * Get the structure for retuning grade feedbak and marks
     *
     * @return void
     */
    public static function fetch_ai_grade_returns(): external_single_structure {
        return new external_single_structure([
            'feedback' => new external_value(PARAM_RAW, 'text feedback for display to student', VALUE_DEFAULT),
            'marks' => new external_value(PARAM_FLOAT, 'AI grader awarded marks for student response', VALUE_DEFAULT),
        ]);

    }

}
