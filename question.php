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
 * @package    qtype
 * @subpackage aitext
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');
use local_ai_connector\ai;

/**
 * Represents an aitext question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_question extends question_graded_automatically_with_countback {

    public $responseformat;

    /** @var int Indicates whether an inline response is required ('0') or optional ('1')  */
    public $responserequired;

    public $responsefieldlines;

    /** @var int indicates whether the minimum number of words required */
    public $minwordlimit;

    /** @var int indicates whether the maximum number of words required */
    public $maxwordlimit;

    public $attachments;

    /** @var int maximum file size in bytes */
    public $maxbytes;

    /** @var int The number of attachments required for a response to be complete. */
    public $attachmentsrequired;

    public $graderinfo;
    public $graderinfoformat;
    public $responsetemplate;
    public $responsetemplateformat;
    public $aiprompt;

    public $markscheme;

    public $step;

    /** @var int */
    public $defaultmark;

    /** @var array The string array of file types accepted upon file submission. */
    public $filetypeslist;
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
    public function apply_attempt_state(question_attempt_step $step) {
        $this->step = $step;

    }

    /**
     * Grade response by making a call to external
     * large language model such as ChatGPT
     *
     * @param array $response
     * @return void
     */
    public function grade_response(array $response) : array {
        global $USER;
        $ai = new ai\ai();
        $prompt = $this->aiprompt;
        if ($this->markscheme > '') {
            $prompt .= ' '.$this->markscheme;
            $prompt .= $this->get_json_prompt();
        }
        $prompt .= ' respond in the language '.current_language();
        if (is_array($response)) {
            $prompt .= '" ' . strip_tags($response['answer']) . '"';
            $llmresponse = $ai->prompt_completion($prompt);
            $content = $llmresponse['response']['choices'][0]['message']['content'];
        }
        $this->insert_attempt_step_data('-aicontent', $content);

        $contentobject = json_decode($content);
        if (!$contentobject || !is_string($contentobject->response)) {
            $content .= ' '.get_config('qtype_aitext', 'disclaimer');
            $this->insert_attempt_step_data('-comment', $content);
            $grade = [0 => 0, question_state::$needsgrading];
            return $grade;
        }
        // The response musthave the expected fields.
        if ($contentobject) {
             $response = $contentobject->response;
             $fraction = $contentobject->marks / $this->defaultmark;
             $grade = array($fraction, question_state::graded_state_for_fraction($fraction));
        } else {
            $response = $content;
            $grade = [0 => 0, question_state::$needsgrading];
        }

        $response .= ' '.$this->llm_translate(get_config('qtype_aitext', 'disclaimer'));
        $this->insert_attempt_step_data('-comment', $response);

        $this->insert_attempt_step_data('-commentformat', 1);

        return $grade;
    }

    protected function get_json_prompt() :string {
        return 'return only a JSON object which enumerates a set of 2  elements.
        The elements sould have properties of "response" and "marks".
        The resulting JSON object should be in this format: {"response":"string","marks":"number"}.\n\n';
    }
    /**
     * Translate into the current language and
     * store in a cache
     *
     * @param string $text
     * @return string
     */
    protected function llm_translate(string $text) :string {
        if (current_language() == 'en') {
            return $text;
        }
        $ai = new ai\ai();
        $cache = cache::make('qtype_aitext', 'stringdata');
        if (($translation = $cache->get(current_language().'_'.$text)) === false) {
            $prompt = 'translate "'.$text .'" into '.current_language();
            $llmresponse = $ai->prompt_completion($prompt);
            $translation = $llmresponse['response']['choices'][0]['message']['content'];
            $translation = trim($translation, '"');
            $cache->set(current_language().'_'.$text, $translation);
        }
        return $translation;
    }
    protected function insert_attempt_step_data(string $name, string $value ) {
        global $DB;
        $data = [
            'attemptstepid' => $this->step->get_id(),
            'name' => $name,
            'value' => $value
        ];
        $DB->insert_record('question_attempt_step_data', $data);
    }

    /**
     * @param moodle_page the page we are outputting to.
     * @return qtype_aitext_format_renderer_base the response-format-specific renderer.
     */
    public function get_format_renderer(moodle_page $page) {
        return  $page->get_renderer('qtype_aitext', 'format_' . $this->responseformat);
    }

    public function get_expected_data() {
        if ($this->responseformat == 'editorfilepicker') {
            $expecteddata = array('answer' => question_attempt::PARAM_RAW_FILES);
        } else {
            $expecteddata = array('answer' => PARAM_RAW);
        }
        $expecteddata['answerformat'] = PARAM_ALPHANUMEXT;
        if ($this->attachments != 0) {
            $expecteddata['attachments'] = question_attempt::PARAM_FILES;
        }
        return $expecteddata;
    }

    public function summarise_response(array $response) {
        $output = null;

        if (isset($response['answer'])) {
            $output .= question_utils::to_plain_text($response['answer'],
                $response['answerformat'], array('para' => false));
        }

        if (isset($response['attachments'])  && $response['attachments']) {
            $attachedfiles = [];
            foreach ($response['attachments']->get_files() as $file) {
                $attachedfiles[] = $file->get_filename() . ' (' . display_size($file->get_filesize()) . ')';
            }
            if ($attachedfiles) {
                $output .= get_string('attachedfiles', 'qtype_aitext', implode(', ', $attachedfiles));
            }
        }
        return $output;
    }

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

    public function get_correct_response() {
        return null;
    }

    public function is_complete_response(array $response) {
        // Determine if the given response has online text and attachments.
        $hasinlinetext = array_key_exists('answer', $response) && ($response['answer'] !== '');

        // If there is a response and min/max word limit is set in the form then validate the number of words in response.
        if ($hasinlinetext) {
            if ($this->check_input_word_count($response['answer'])) {
                return false;
            }
        }
        $hasattachments = array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files;

        // Determine the number of attachments present.
        if ($hasattachments) {
            // Check the filetypes.
            $filetypesutil = new \core_form\filetypes_util();
            $allowlist = $filetypesutil->normalize_file_types($this->filetypeslist);
            $wrongfiles = array();
            foreach ($response['attachments']->get_files() as $file) {
                if (!$filetypesutil->is_allowed_file_type($file->get_filename(), $allowlist)) {
                    $wrongfiles[] = $file->get_filename();
                }
            }
            if ($wrongfiles) { // At least one filetype is wrong.
                return false;
            }
            $attachcount = count($response['attachments']->get_files());
        } else {
            $attachcount = 0;
        }

        // Determine if we have /some/ content to be graded.
        $hascontent = $hasinlinetext || ($attachcount > 0);

        // Determine if we meet the optional requirements.
        $meetsinlinereq = $hasinlinetext || (!$this->responserequired) || ($this->responseformat == 'noinline');
        $meetsattachmentreq = ($attachcount >= $this->attachmentsrequired);

        // The response is complete iff all of our requirements are met.
        return $hascontent && $meetsinlinereq && $meetsattachmentreq;
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
            'responserequired' => $this->responserequired,
            'responsefieldlines' => $this->responsefieldlines,
            'attachments' => $this->attachments,
            'attachmentsrequired' => $this->attachmentsrequired,
            'maxbytes' => $this->maxbytes,
            'filetypeslist' => $this->filetypeslist,
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
        if (!$this->responserequired) {
            return null;
        }
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
