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
            [
             'response'    => new external_value(PARAM_RAW, 'The students response to question'),
             'defaultmark' => new external_value(PARAM_INT, 'The total possible score'),
             'prompt'      => new external_value(PARAM_RAW, 'The AI Prompt'),
             'marksscheme' => new external_value(PARAM_RAW, 'The marks scheme'),
             'questiontext' => new external_value(PARAM_RAW, 'The question text'),
             'contextid'   => new external_value(PARAM_INT, 'The context id'),
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
    /**
     * Grade response using AI, optionally including question text.
     *
     * @param string $response     The student's response.
     * @param int    $defaultmark  The total possible score.
     * @param string $prompt       The AI prompt template.
     * @param string $marksscheme  The marks scheme instructions.
     * @param string $questiontext The question text content.
     * @param int    $contextid    The context id.
     * @return stdClass the AI feedback and marks
     */
    public static function fetch_ai_grade(
        string $response,
        int $defaultmark,
        string $prompt,
        string $marksscheme,
        string $questiontext,
        int $contextid
    ): stdClass {
        [
            'response'    => $response,
            'defaultmark' => $defaultmark,
            'prompt'      => $prompt,
            'marksscheme' => $marksscheme,
            'questiontext' => $questiontext,
            'contextid'   => $contextid,
        ] = self::validate_parameters(
            self::fetch_ai_grade_parameters(),
            [
                'response'    => $response,
                'defaultmark' => $defaultmark,
                'prompt'      => $prompt,
                'marksscheme' => $marksscheme,
                'questiontext' => $questiontext,
                'contextid'   => $contextid,
            ]
        );
        $context = $contextid === 0 ? context_system::instance() : context::instance_by_id($contextid);
        self::validate_context($context);

        // TODO Eventually move this to a own capability which by default is assigned to a teacher in a course.
        require_capability('mod/quiz:grade', $context);

        // Converting the HTML special chars seems to be the only way to accept things like "this<that" and keeping the text
        // "alive" without removing parts of it by passing it so sanitization functions.
        $response = clean_param(htmlspecialchars($response), PARAM_CLEANHTML);
        $prompt = clean_param(htmlspecialchars($prompt), PARAM_CLEANHTML);
        $marksscheme = clean_param(htmlspecialchars($marksscheme), PARAM_CLEANHTML);

        // Build an aitext question instance so we can call the same code that the question type uses when it grades.
        $type = 'aitext';
        \question_bank::load_question_definition_classes($type);
        $aiquestion = new qtype_aitext_question();
        $aiquestion->contextid = $contextid;
        $aiquestion->qtype = \question_bank::get_qtype('aitext');
        // Provide the current question text for placeholder substitution.
        $aiquestion->questiontext = $questiontext;
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

    /**
     * Parameters for generate_test_responses.
     *
     * @return external_function_parameters
     */
    public static function generate_test_responses_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
             'questiontext' => new external_value(PARAM_RAW, 'The question text'),
             'prompt'       => new external_value(PARAM_RAW, 'The AI prompt'),
             'marksscheme'  => new external_value(PARAM_RAW, 'The marks scheme'),
             'defaultmark'  => new external_value(PARAM_INT, 'The total possible score'),
             'contextid'    => new external_value(PARAM_INT, 'The context id'),
             'numresponses' => new external_value(PARAM_INT, 'How many responses to generate', VALUE_DEFAULT, 4),
            ]
        );
    }

    /**
     * Ask the AI to generate a spread of varied-quality student responses and
     * return them so the form can populate the sample response fields.
     *
     * This is a developer aid: the responses let the marking guide be tested at
     * every quality level without hand-writing sample answers.
     *
     * @param string $questiontext The question text content.
     * @param string $prompt       The AI grading prompt.
     * @param string $marksscheme  The marking criteria.
     * @param int    $defaultmark  The total possible score.
     * @param int    $contextid    The context id.
     * @return stdClass status flag, human readable message and the sample responses
     */
    public static function generate_test_responses(
        string $questiontext,
        string $prompt,
        string $marksscheme,
        int $defaultmark,
        int $contextid,
        int $numresponses = 4
    ): stdClass {
        [
            'questiontext' => $questiontext,
            'prompt'       => $prompt,
            'marksscheme'  => $marksscheme,
            'defaultmark'  => $defaultmark,
            'contextid'    => $contextid,
            'numresponses' => $numresponses,
        ] = self::validate_parameters(
            self::generate_test_responses_parameters(),
            [
                'questiontext' => $questiontext,
                'prompt'       => $prompt,
                'marksscheme'  => $marksscheme,
                'defaultmark'  => $defaultmark,
                'contextid'    => $contextid,
                'numresponses' => $numresponses,
            ]
        );

        // Keep the count sane: at least one response, capped so a stray value cannot
        // ask the AI for hundreds of answers.
        $numresponses = min(max($numresponses, 1), 20);

        $context = $contextid === 0 ? context_system::instance() : context::instance_by_id($contextid);
        self::validate_context($context);
        require_capability('mod/quiz:grade', $context);

        // Match the sanitisation used by fetch_ai_grade so "this<that" style text survives.
        $questiontext = clean_param(htmlspecialchars($questiontext), PARAM_CLEANHTML);
        $prompt = clean_param(htmlspecialchars($prompt), PARAM_CLEANHTML);
        $marksscheme = clean_param(htmlspecialchars($marksscheme), PARAM_CLEANHTML);

        if (empty($prompt) || $defaultmark <= 0) {
            return (object) [
                'status'    => false,
                'message'   => get_string('err_parammissing', 'qtype_aitext'),
                'responses' => [],
            ];
        }

        // Build an aitext question instance so we reuse the same LLM plumbing the question type uses.
        \question_bank::load_question_definition_classes('aitext');
        $aiquestion = new qtype_aitext_question();
        $aiquestion->contextid = $contextid;
        $aiquestion->qtype = \question_bank::get_qtype('aitext');
        $aiquestion->questiontext = $questiontext;

        // Row 0 is deliberate gibberish: a random 5-10 character alphanumeric string
        // so the marking guide can be checked against nonsense input. The AI only
        // needs to write the remaining, quality-spanning answers. Each sample is
        // prefixed with a {{...}} comment describing its purpose.
        $gibberish = random_string(random_int(5, 10));
        $responses = ['{{Random nonsense string to check the marking guide handles garbage input}}' . "\n" . $gibberish];
        $airequested = $numresponses - 1;

        if ($airequested > 0) {
            $genprompt = self::build_test_responses_prompt($questiontext, $prompt, $marksscheme, $defaultmark, $airequested);
            $raw = $aiquestion->perform_request($genprompt);
            $airesponses = self::parse_test_responses($raw);
            if (empty($airesponses)) {
                return (object) [
                    'status'    => false,
                    'message'   => get_string('gentestresponsesfailed', 'qtype_aitext'),
                    'responses' => [],
                ];
            }
            $responses = array_merge($responses, $airesponses);
        }

        return (object) [
            'status'    => true,
            'message'   => get_string('gentestresponseswritten', 'qtype_aitext', count($responses)),
            'responses' => $responses,
        ];
    }

    /**
     * Split the AI output into individual sample responses.
     *
     * The prompt asks the AI to separate each response with a line containing
     * only five dashes, off-topic first. Blank entries are discarded.
     *
     * @param string $raw the raw text returned by the AI
     * @return string[] trimmed, non-empty sample responses in order
     */
    protected static function parse_test_responses(string $raw): array {
        $parts = preg_split('/^\s*-{5,}\s*$/m', (string) $raw);
        $responses = [];
        foreach ($parts as $part) {
            // Keep the samples plain text: drop any HTML the AI may have added.
            $part = trim(strip_tags($part));
            if ($part !== '') {
                $responses[] = $part;
            }
        }
        return $responses;
    }

    /**
     * Construct the prompt that instructs the AI to produce a spread of
     * varied-quality student responses in markdown.
     *
     * @param string $questiontext
     * @param string $prompt
     * @param string $marksscheme
     * @param int    $defaultmark
     * @param int    $numresponses how many responses to request
     * @return string
     */
    protected static function build_test_responses_prompt(
        string $questiontext,
        string $prompt,
        string $marksscheme,
        int $defaultmark,
        int $numresponses = 4
    ): string {
        $marksscheme = trim($marksscheme) === '' ? get_string('nomarkscheme', 'qtype_aitext') : $marksscheme;
        return <<<PROMPT
You are helping a teacher test an automated marking guide by writing sample student answers.

QUESTION TEXT:
{$questiontext}

GRADING PROMPT / INSTRUCTIONS:
{$prompt}

MARKING SCHEME:
{$marksscheme}

MAXIMUM MARK: {$defaultmark}

Write {$numresponses} sample student responses that span the full range of quality,
so the marking guide can be tested at every level. Separate each sample with a line
containing only five dashes (-----).

The responses must range from an excellent answer worth {$defaultmark}/{$defaultmark}
down to a weak answer worth few marks, spread evenly across that range.

Begin each sample with a comment of the form {{...}} on its own line that briefly
explains the purpose of that sample (its intended quality and expected marks),
followed by the response body written as plausible prose a real student would submit.

For example (for two responses):

{{Excellent answer, expected full marks}}
<excellent response>
-----
{{Weak answer, expected few marks}}
<poor response>

Apart from the {{...}} comment line, do not include any headings, labels, grades or
explanations inside the response bodies. Write everything in plain text only: do not
use any HTML tags or markdown formatting. Output only the samples separated by the
----- delimiter.
PROMPT;
    }

    /**
     * Return structure for generate_test_responses.
     *
     * @return external_single_structure
     */
    public static function generate_test_responses_returns(): external_single_structure {
        return new external_single_structure([
            'status'    => new external_value(PARAM_BOOL, 'True when responses were generated'),
            'message'   => new external_value(PARAM_RAW, 'Human readable status message'),
            'responses' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'A generated sample response'),
                'Generated sample responses, off-topic first',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }
}
