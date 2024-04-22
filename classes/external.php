<?php
/**
 * External.
 *
 * @package qtype_aitext
 * @author  Justin Hunt - poodll.com
 */


global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

use tool_aiconnect\ai;



/**
 * External class.
 *
 * @package qtype_aitext
 * @author  Justin Hunt - poodll.com
 */
class qtype_aitext_external extends external_api
{

    public static function fetch_ai_grade_parameters()
    {
        return new external_function_parameters(
            array('response' => new external_value(PARAM_TEXT, 'The students response to question'),
                'defaultmark' => new external_value(PARAM_INT, 'The total possible score'),
                'prompt' => new external_value(PARAM_TEXT, 'The AI Prompt'),
                'marksscheme' => new external_value(PARAM_TEXT, 'The marks scheme')
            )
        );

    }

    public static function fetch_ai_grade($response,$defaultmark,$prompt,$marksscheme)
    {
        //get our AI helper
        $ai = new ai\ai();

        //build an aitext question instance so we can call the same code that the question type uses when it grades
        $type = 'aitext';
        \question_bank::load_question_definition_classes($type);
        $aiquestion = new qtype_aitext_question();
        $aiquestion->qtype = \question_bank::get_qtype('aitext');

        //make sure we have the right data for AI to work with
        if (!empty($response) && !empty($prompt) && $defaultmark > 0) {
            $full_ai_prompt = $aiquestion->build_full_ai_prompt($response, $prompt, $defaultmark, $marksscheme);
            $llmresponse = $ai->prompt_completion($full_ai_prompt);
            $feedback = $llmresponse['response']['choices'][0]['message']['content'];
            $contentobject = $aiquestion->process_feedback($feedback);
        }else{
            $contentobject = ["feedback" => "Invalid parameters. Check that you have a sample answer and prompt","marks" => 0];
        }

        //return whatever we have got
        return $contentobject;

    }

    public static function fetch_ai_grade_returns()
    {
        return new external_single_structure([
            'feedback' => new external_value(PARAM_TEXT, 'text feedback for display to student', VALUE_DEFAULT),
            'marks' => new external_value(PARAM_INT, 'AI grader awarded marks for student response', VALUE_DEFAULT),
        ]);

    }

}