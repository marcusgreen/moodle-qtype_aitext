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
 * aitext question definition class.
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');
use local_ai_manager\manager;
/**
 * Represents an aitext question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_question extends question_graded_automatically_with_countback {

    /**
     * Plain text or html
     * @var string
     */
    public $responseformat;

    /**
     * Count of lines of text
     *
     * @var int
     */
    public $responsefieldlines;

    /** @var int indicates whether the minimum number of words required */
    public $minwordlimit;

    /** @var int indicates whether the maximum number of words required */
    public $maxwordlimit;

    /**
     * LLM Model, will vary between AI systems, e.g. gpt4 or llama3
     * @var stream_set_blocking
     */
    public $model;


    /**
     * used in the question editing interface
     *
     * @var string
     */
    public $sampleanswer;

    /**
     * Information on how to manually grade
     *
     * @var string
     */
    public $graderinfo;

    /**
     * Plain text or html
     * @var int
     */
    public $graderinfoformat;

    /**
     * Text to appear in area where student types response.
     * @var string
     */
    public $responsetemplate;
    /**
     * plain text or html
     *
     * @var int
     */
    public $responsetemplateformat;
    /**
     * String to pass to the LLM telling it how to give
     * feedback
     * @var mixed
     */
    public $aiprompt;

    /**
     * String to pass to the LLM telling it how to mark
     * a submission
     * @var string
     */
    public $markscheme;

    /**
     * Question attempt step
     *
     * @var mixed
     */
    public $step;

    /** @var int */
    public $defaultmark;

    /** @var array The string array of file types accepted upon file submission. */
    public $filetypeslist;

    /** @var bool $spellcheck if spellcheck is enabled */
    public bool $spellcheck;

    /**
     * Required by the interface question_automatically_gradable_with_countback.
     *
     * @param array $responses
     * @param array $totaltries
     * @return number
     */
    public function compute_final_grade($responses, $totaltries) {

        return true;
    }
    /**
     * Re-initialise the state during a quiz (or question use)
     *
     * @param question_attempt_step $step
     * @return void
     */
    public function apply_attempt_state(question_attempt_step $step) {
        $this->step = $step;
    }

    /**
     * Get the spellchecking response.
     *
     * @param array $response
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_spellchecking(array $response):string {
        $fullaiprompt = $this->build_full_ai_spellchecking_prompt($response['answer']);
        $ai = new local_ai_manager\manager('feedback');
        $llmresponse = $ai->perform_request($fullaiprompt, ['component' => 'qtype_aitext', 'contextid' => $this->contextid]);
        if ($llmresponse->get_code() !== 200) {
            throw new moodle_exception(
                'err_airesponsefailed',
                'qtype_aitext',
                '',
                $llmresponse->get_errormessage(),
                $llmresponse->get_debuginfo()
            );
        }
        return $llmresponse->get_content();
    }

    /**
     * Grade response by making a call to external
     * large language model such as ChatGPT
     *
     * @param array $response
     * @return void
     */
    public function grade_response(array $response): array {

        if($this->spellcheck) {
            $spellcheckresponse = $this->get_spellchecking($response);
            $this->insert_attempt_step_data('-spellcheckresponse', $spellcheckresponse);
        }

        if (!$this->is_complete_response($response)) {
            $grade = [0 => 0, question_state::$needsgrading];
            return $grade;
        }
        $ai = new local_ai_manager\manager('feedback');
        if (is_array($response)) {
            $fullaiprompt = $this->build_full_ai_prompt($response['answer'], $this->aiprompt,
                 $this->defaultmark, $this->markscheme);
            $llmresponse = $ai->perform_request($fullaiprompt, ['component' => 'qtype_aitext', 'contextid' => $this->contextid]);
            if ($llmresponse->get_code() !== 200) {
                throw new moodle_exception('err_retrievingfeedback', 'qtype_aitext', '', $llmresponse->get_errormessage(),
                        $llmresponse->get_debuginfo());
            }
            $feedback = $llmresponse->get_content();
        }

        $contentobject = $this->process_feedback($feedback);

        // If there are no marks, write the feedback and set to needs grading .
        if (is_null($contentobject->marks)) {
            $grade = [0 => 0, question_state::$needsgrading];
        } else {
            $fraction = $contentobject->marks / $this->defaultmark;
            $grade = [$fraction, question_state::graded_state_for_fraction($fraction)];
        }
         // The -aicontent data is used in question preview. Only needs to happen in preview.
        $this->insert_attempt_step_data('-aiprompt', $fullaiprompt);
        $this->insert_attempt_step_data('-aicontent', $contentobject->feedback);

        $this->insert_attempt_step_data('-comment', $contentobject->feedback);
        $this->insert_attempt_step_data('-commentformat', FORMAT_HTML);

        return $grade;
    }

    /**
     * Used by prompttester in the editing form
     *
     * @param string $response
     * @param string $aiprompt
     * @param number $defaultmark
     * @param string $markscheme
     * @return string;
     */
    public function build_full_ai_prompt($response, $aiprompt, $defaultmark, $markscheme): string {
        $responsetext = strip_tags($response);
            $responsetext = '[['.$responsetext.']]';
            $prompt = get_config('qtype_aitext', 'prompt');
            $prompt = preg_replace("/\[responsetext\]/", $responsetext, $prompt);
            $prompt .= ' '.trim($aiprompt);

        if ($markscheme > '') {
            // Tell the LLM how to mark the submission.
            $prompt .= " The total score is: $defaultmark .";
            $prompt .= ' '.$markscheme;
        } else {
            // Todo should this be a plugin setting value?.
            $prompt .= ' Set marks to null in the json object.'.PHP_EOL;
        }
        $prompt .= ' '.trim(get_config('qtype_aitext', 'jsonprompt'));
        $prompt .= ' translate the feedback to the language '.current_language();
        return $prompt;

    }

    /**
     * Build the full ai spellchecking prompt.
     *
     * @param string $response
     * @return string
     * @throws coding_exception
     */
    public function build_full_ai_spellchecking_prompt(string $response): string {
        // $response = strip_tags($response);
        return get_string('spellcheck_prompt', 'qtype_aitext') . ($response);
    }

    /**
     *
     * Convert string json returned from LLM call to an object,
     * if it is not valid json apend as string to new object
     *
     * @param string $feedback
     * @return \stdClass
     */
    public function process_feedback(string $feedback) {
        preg_match_all('#\{(?:[^{}]|(?R))*\}#s', $feedback, $feedbackjsonstrings);
        if (empty($feedbackjsonstrings)) {
            throw new moodle_exception('Could not parse feedback of AI tool');
        }
        if (preg_match('/\{[^{}]*\}/', $feedback, $matches)) {
            // Array $matches[1] contains the captured text inside the braces.
            $feedback = $matches[0];
        }
        $contentobject = json_decode($feedback);
        if (json_last_error() === JSON_ERROR_NONE) {
            $contentobject->feedback = trim($contentobject->feedback);
            $contentobject->feedback = preg_replace(['/\[\[/', '/\]\]/'], '"', $contentobject->feedback);
            $disclaimer = get_config('qtype_aitext', 'disclaimer');
            // TODO Model currently is only used for connecting and at this point I believe. We need to remove all the model
            //  selection logic or make local_ai_manager support the selection of models.
            $disclaimer = str_replace("[[model]]",
                    \local_ai_manager\ai_manager_utils::get_connector_instance_by_purpose('feedback')->get_model(), $disclaimer);
            $contentobject->feedback .= ' '.$this->llm_translate($disclaimer);
        } else {
            $contentobject = (object) [
                                        "feedback" => $feedback,
                                        "marks" => null,
                                        ];
        }
        return $contentobject;
    }

    /**
     * Translate into the current language and
     * store in a cache
     *
     * @param string $text
     * @return string
     */
    protected function llm_translate(string $text): string {
        if (current_language() == 'en') {
            return $text;
        }
        $ai = new local_ai_manager\manager('translate');
        $cache = cache::make('qtype_aitext', 'stringdata');
        if (($translation = $cache->get(current_language().'_'.$text)) === false) {
            $prompt = 'translate "'.$text .'" into '.current_language();
            $llmresponse = $ai->perform_request($prompt);
            if ($llmresponse->get_code() !== 200) {
                throw new moodle_exception('Could not retrieve the translation from the AI tool');
            }
            $translation = $llmresponse->get_content();
            $translation = trim($translation, '"');
            $cache->set(current_language().'_'.$text, $translation);
        }
        return $translation;
    }

    /**
     * Fake manual grading
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    protected function insert_attempt_step_data(string $name, string $value ): void {
        global $DB;
        $data = [
            'attemptstepid' => $this->step->get_id(),
            'name' => $name,
            'value' => $value,
        ];
        $DB->insert_record('question_attempt_step_data', $data);
    }

    /**
     * Possibly redundant, a legacy from filesubmission
     *
     * @param moodle_page $page     the page we are outputting to.
     * @return renderer_base the response-format-specific renderer.
     */
    public function get_format_renderer(moodle_page $page) {
        return  $page->get_renderer('qtype_aitext', 'format_' . $this->responseformat);
    }
    /**
     * Get expected data types
     * @return array
     */
    public function get_expected_data() {
        $expecteddata = ['answer' => PARAM_RAW];
        $expecteddata['answerformat'] = PARAM_ALPHANUMEXT;
        return $expecteddata;
    }


    /**
     * Value returned will be written to responsesummary field of the
     * question_attempts table
     *
     * @param array $response
     * @return string
     */
    public function summarise_response(array $response) {
        $output = null;
        if (isset($response['answer'])) {
            $output .= question_utils::to_plain_text($response['answer'],
                $response['answerformat'], ['para' => false]);
        }

        return $output;
    }

    /**
     * Construct a response that could have lead to the given response summary.
     * For testing only
     * @param string $summary
     * @return array
     */
    public function un_summarise_response(string $summary) {
        if (empty($summary)) {
            return [];
        }

        if (str_contains($this->responseformat, 'editor')) {
            return ['answer' => text_to_html($summary), 'answerformat' => FORMAT_HTML];
        } else {
            return ['answer' => $summary, 'answerformat' => FORMAT_PLAIN];
        }
    }

    /**
     * There is no one correct response for this quesiton type
     * so return null.
     * @return array|null
     */
    public function get_correct_response() {
        return null;
    }

    /**
     * Is there some text and does it match the required word count?
     * @param array $response
     * @return bool
     * @throws coding_exception
     */
    public function is_complete_response(array $response) {
        // Determine if the given response has online text and attachments.
        $hasinlinetext = array_key_exists('answer', $response) && ($response['answer'] !== '');

        // If there is a response and min/max word limit is set in the form then validate the number of words in response.
        if ($hasinlinetext) {
            if ($this->check_input_word_count($response['answer'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return null if is_complete_response() returns true
     * otherwise, return the minmax-limit error message
     *
     * @param array $response
     * @return string
     */
    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return $this->check_input_word_count($response['answer']);
    }

    /**
     * Has the quesiton been answered, the attachment stuff needs removing
     *
     * @param array $response
     * @return bool
     */
    public function is_gradable_response(array $response) {
        // Determine if the given response has online text and attachments.
        if (array_key_exists('answer', $response) && ($response['answer'] !== '')) {
            return true;
        } else if (array_key_exists('attachments', $response)
                && $response['attachments'] instanceof question_response_files) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * If you are moving from viewing one question to another this will
     * discard the processing if the answer has not changed. If you don't
     * use this method it will constantantly generate new question steps and
     * the question will be repeatedly set to incomplete. This is a comparison of
     * the equality of two arrays.
     *
     * @param array $prevresponse
     * @param array $newresponse
     * @return bool
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        if (array_key_exists('answer', $prevresponse) && $prevresponse['answer'] !== $this->responsetemplate) {
            $value1 = (string) $prevresponse['answer'];
        } else {
            $value1 = '';
        }
        if (array_key_exists('answer', $newresponse) && $newresponse['answer'] !== $this->responsetemplate) {
            $value2 = (string) $newresponse['answer'];
        } else {
            $value2 = '';
        }
        return $value1 === $value2 && ($this->attachments == 0 ||
                question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'attachments'));
    }

    /**
     *  Checks whether the users is allow to be served a particular file.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @param string $component
     * @param string $filearea
     * @param array $args
     * @param bool $forcedownload
     * @return bool
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'response_attachments') {
            // Response attachments visible if the question has them.
            return $this->attachments != 0;

        } else if ($component == 'question' && $filearea == 'response_answer') {
            // Response attachments visible if the question has them.
            return $this->responseformat === 'editorfilepicker';

        } else if ($component == 'qtype_aitext' && $filearea == 'graderinfo') {
            return $options->manualcomment && $args[0] == $this->id;

        } else {
            return parent::check_file_access($qa, $options, $component,
                    $filearea, $args, $forcedownload);
        }
    }

    /**
     * Return the question settings that define this question as structured data.
     *
     * @param question_attempt $qa the current attempt for which we are exporting the settings.
     * @param question_display_options $options the question display options which say which aspects of the question
     * should be visible.
     * @return mixed structure representing the question settings. In web services, this will be JSON-encoded.
     */
    public function get_question_definition_for_external_rendering(question_attempt $qa, question_display_options $options) {
        // This is a partial implementation, returning only the most relevant question settings for now,
        // ideally, we should return as much as settings as possible (depending on the state and display options).

        $settings = [
            'responseformat' => $this->responseformat,
            'responsefieldlines' => $this->responsefieldlines,
            'responsetemplate' => $this->responsetemplate,
            'responsetemplateformat' => $this->responsetemplateformat,
            'minwordlimit' => $this->minwordlimit,
            'maxwordlimit' => $this->maxwordlimit,
        ];

        return $settings;
    }

    /**
     * Check the input word count and return a message to user
     * when the number of words are outside the boundary settings.
     *
     * @param string $responsestring
     * @return string|null
     .*/
    private function check_input_word_count($responsestring) {

        if (!$this->minwordlimit && !$this->maxwordlimit) {
            // This question does not care about the word count.
            return null;
        }

        // Count the number of words in the response string.
        $count = count_words($responsestring);
        if ($this->maxwordlimit && $count > $this->maxwordlimit) {
            return get_string('maxwordlimitboundary', 'qtype_aitext',
                    ['limit' => $this->maxwordlimit, 'count' => $count]);
        } else if ($count < $this->minwordlimit) {
            return get_string('minwordlimitboundary', 'qtype_aitext',
                    ['limit' => $this->minwordlimit, 'count' => $count]);
        } else {
            return null;
        }
    }

    /**
     * If this question uses word counts, then return a display of the current
     * count, and whether it is within limit, for when the question is being reviewed.
     *
     * @param array $response responses, as returned by
     *      {@see question_attempt_step::get_qt_data()}.
     * @return string If relevant to this question, a display of the word count.
     */
    public function get_word_count_message_for_review(array $response): string {
        if (!$this->minwordlimit && !$this->maxwordlimit) {
            // This question does not care about the word count.
            return '';
        }

        if (!array_key_exists('answer', $response) || ($response['answer'] === '')) {
            // No response.
            return '';
        }

        $count = count_words($response['answer']);
        if ($this->maxwordlimit && $count > $this->maxwordlimit) {
            return get_string('wordcounttoomuch', 'qtype_aitext',
                    ['limit' => $this->maxwordlimit, 'count' => $count]);
        } else if ($count < $this->minwordlimit) {
            return get_string('wordcounttoofew', 'qtype_aitext',
                    ['limit' => $this->minwordlimit, 'count' => $count]);
        } else {
            return get_string('wordcount', 'qtype_aitext', $count);
        }
    }
}
