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


/**
 * Represents an aitext question.
 *
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_question extends question_graded_automatically {
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
     * Model the AI backend actually used for the most recent request,
     * as reported by the backend/provider. Null until perform_request() runs.
     * @var string|null
     */
    public $modelused = null;


    /**
     * Options including from sampleanswers table
     * @var array
     */
    public $options;


    /**
     * used in the question editing interface
     *
     * @var array
     */
    public $sampleresponses;

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

    /** @var int */
    public $defaultmark;

    /** @var array The string array of file types accepted upon file submission. */
    public $filetypeslist;

    /** @var bool $spellcheck if spellcheck is enabled */
    public bool $spellcheck;


    /** @var array  */
    public $sampleanswers;

    /** @var int|null Cached context id of the current attempt usage. */
    protected $attemptcontextid = null;

    /** @var string|null Cached AI comment from the last grade_response() call. */
    public $lastaicomment = null;

    /** @var string|null Cached AI prompt from the last grade_response() call. */
    public $lastaiprompt = null;

    /** @var string|null Cached spellcheck response from the last grade_response() call. */
    public $lastspellcheckresponse = null;

    /** @var string|null Cached hint text from the last generate_hint() call. */
    public $lastaihint = null;

    /** @var string|null Cached hint prompt from the last generate_hint() call. */
    public $lastaihintprompt = null;

    /**
     * Choose the question behaviour to use for this question attempt.
     *
     * Routes the requested preferred behaviour to an adapted variant that
     * persists AI grading results (comment, prompt, spellcheck) as cached
     * behaviour variables on the grading step.
     *
     * Interactive-style behaviours (interactive, interactivecountback) are routed
     * to qbehaviour_interactive_for_aitext, which grades on every submit and lets
     * the student retry, giving an AI-generated hint between tries.
     *
     * Immediate-style behaviours (immediatefeedback, immediatecbm, adaptive,
     * adaptivenopenalty) are routed to qbehaviour_immediate_for_aitext, which
     * grades on every submit.
     *
     * Manualgraded is kept as-is via the core archetypal behaviour: no AI
     * grading occurs in that mode, so no adapter is needed. Note that
     * manualgraded is disabled by default in the quiz preferred-behaviour
     * dropdown but may be re-enabled by an admin.
     *
     * All remaining behaviours, including deferredfeedback and deferredcbm,
     * are routed to qbehaviour_deferred_for_aitext, which grades only on finish.
     *
     * @param question_attempt $qa the question attempt being constructed.
     * @param string $preferredbehaviour the preferred behaviour name from the quiz settings.
     * @return question_behaviour the behaviour instance to use.
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        // Interactive: multiple tries, each retry preceded by an AI-generated hint.
        if (in_array($preferredbehaviour, ['interactive', 'interactivecountback'], true)) {
            return question_engine::make_behaviour('interactive_for_aitext', $qa, $preferredbehaviour);
        }

        if (
            in_array(
                $preferredbehaviour,
                ['immediatefeedback', 'immediatecbm', 'adaptive', 'adaptivenopenalty'],
                true
            )
        ) {
            return question_engine::make_behaviour('immediate_for_aitext', $qa, $preferredbehaviour);
        }

        // Manual grading: no AI grading occurs, use core behaviour as-is.
        if ($preferredbehaviour === 'manualgraded') {
            return question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);
        }

        // Everything else (deferredfeedback, deferredcbm, anything unknown) → deferred adapter.
        return question_engine::make_behaviour('deferred_for_aitext', $qa, $preferredbehaviour);
    }

    /**
     * Re-initialise the state during a quiz (or question use)
     *
     * @param question_attempt_step $step
     * @return void
     */
    public function apply_attempt_state(question_attempt_step $step) {
        $this->lastaicomment = null;
        $this->lastaiprompt = null;
        $this->lastspellcheckresponse = null;
        $this->lastaihint = null;
        $this->lastaihintprompt = null;
        $this->attemptcontextid = null;
        // Resolve the usage context from the step.
        $this->resolve_attempt_context($step);
    }

    /**
     * Resolve the attempt (usage) context from a step and cache it.
     *
     * Walks step → question_attempt → question_usage → context via DB.
     * Ignores user contexts because AI permission checks cannot be
     * evaluated for them (typically means question preview).
     *
     * @param question_attempt_step $step A step belonging to this attempt.
     */
    private function resolve_attempt_context(question_attempt_step $step): void {
        global $DB;

        $stepid = 0;
        if (method_exists($step, 'get_id')) {
            $stepid = (int) $step->get_id();
        }

        if ($stepid <= 0) {
            return;
        }

        $sql = "SELECT qu.contextid
              FROM {question_attempt_steps} qas
              JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
              JOIN {question_usages} qu ON qu.id = qa.questionusageid
              JOIN {context} c ON c.id = qu.contextid
             WHERE qas.id = :stepid AND c.contextlevel <> :contextlevel";
        $contextid = $DB->get_field_sql($sql, [
            'stepid' => $stepid,
            'contextlevel' => CONTEXT_USER,
        ]);
        if (!empty($contextid)) {
            $this->attemptcontextid = (int) $contextid;
        }
    }

    /**
     * Call the llm using either the 4.5 core api or the backend provided by
     * local_ai_manager (mebis) or tool_aimanager
     *
     * @param string $prompt
     * @param string $purpose
     */
    public function perform_request(string $prompt, string $purpose = 'feedback'): string {
        $this->modelused = null;
        if (defined('BEHAT_SITE_RUNNING') || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            $this->modelused = 'test';
            return "AI Feedback";
        }
        $contextid = $this->get_contextid_for_ai_request();
        $backend = get_config('qtype_aitext', 'backend');
        if ($backend == 'local_ai_manager') {
            $manager = new local_ai_manager\manager($purpose);
            $llmresponse = (object) $manager->perform_request($prompt, 'qtype_aitext', $contextid);
            if ($llmresponse->get_code() !== 200) {
                throw new moodle_exception(
                    'err_retrievingfeedback',
                    'qtype_aitext',
                    '',
                    $llmresponse->get_errormessage(),
                    $llmresponse->get_debuginfo()
                );
            }
            $this->modelused = $llmresponse->get_modelinfo();
            return $llmresponse->get_content();
        } else if ($backend == 'core_ai_subsystem') {
            global $USER;
            $action = new \core_ai\aiactions\generate_text(
                contextid: $contextid,
                userid: $USER->id,
                prompttext: $prompt
            );
            $manager = \core\di::get(\core_ai\manager::class);
            $llmresponse = $manager->process_action($action);
            $responsedata = $llmresponse->get_response_data();
            // Check the response data is actually a string.
            if (
                is_null($responsedata) || is_null($responsedata['generatedcontent'])
                ||
                !is_array($responsedata) || !array_key_exists('generatedcontent', $responsedata)
            ) {
                if (is_null($responsedata) || is_null($responsedata['generatedcontent'])) {
                    throw new moodle_exception('err_retrievingfeedback_checkconfig', 'qtype_aitext');
                } else {
                    throw new moodle_exception('err_retrievingfeedback', 'qtype_aitext');
                }
            }
            $this->modelused = $llmresponse->get_model_used();
            return $responsedata['generatedcontent'];
        } else if ($backend == 'tool_aimanager') {
            if (class_exists('\tool_aiconnect\ai\ai')) {
                $ai = new tool_aiconnect\ai\ai();
                $llmresponse = $ai->prompt_completion($prompt);
                $this->modelused = $llmresponse['response']['model'] ?? null;
                return $llmresponse['response']['choices'][0]['message']['content'];
            } else {
                throw new moodle_exception('err_retrievingfeedback_checkconfig', 'qtype_aitext', '');
            }
        }
        throw new moodle_exception('err_invalidbackend', 'qtype_aitext');
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
    private function get_spellchecking(array $response): string {
        $fullaiprompt = $this->build_full_ai_spellchecking_prompt($response['answer']);
        $response = $this->perform_request($fullaiprompt, 'feedback');
        return $response;
    }

    /**
     * Grade response by making a call to external
     * large language model such as ChatGPT
     *
     * @param array $response
     * @return void
     */
    public function grade_response(array $response): array {
        // Clear from any previous call.
        $this->lastaicomment = null;
        $this->lastaiprompt = null;
        $this->lastspellcheckresponse = null;

        if (!$this->is_complete_response($response)) {
            return [0, question_state::$needsgrading];
        }

        // If spellcheck is enabled, perform spellchecking and cache the response. Spellcheck failure is non-fatal.
        if ($this->spellcheck) {
            try {
                $this->lastspellcheckresponse = $this->get_spellchecking($response);
            } catch (\moodle_exception $e) {
                // Spellcheck failure is non-fatal — keep going without it.
                $this->lastspellcheckresponse = null;
            }
        }

        $fullaiprompt = $this->build_full_ai_prompt(
            $response['answer'],
            $this->aiprompt,
            $this->defaultmark,
            $this->markscheme
        );

        $this->lastaiprompt = $fullaiprompt;
        try {
            $feedback = $this->perform_request($fullaiprompt, 'feedback');
        } catch (\moodle_exception $e) {
            // AI unavailable (quota, capability, tenant, etc.) — defer to manual grading.
            debugging('AI grading unavailable, deferring to manual grading: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $this->lastaicomment = get_string('err_nofeedback', 'qtype_aitext');
            return [0.0, question_state::$needsgrading];
        }
        $contentobject = $this->process_feedback($feedback);
        $this->lastaicomment = $contentobject->feedback;

        // If there are no marks, write the feedback and set to needs grading .
        if (is_null($contentobject->marks)) {
            return [0.0, question_state::$needsgrading];
        }
        $fraction = 0.0;
        if (is_numeric($contentobject->marks) && $this->defaultmark > 0) {
            $fraction = (float) $contentobject->marks / $this->defaultmark;
        }
        return [$fraction, question_state::graded_state_for_fraction($fraction)];
    }

    /**
     * Build the complete AI prompt for grading a student response.
     *
     * Uses the structured template system. Also used by the prompt tester in the editing form.
     *
     * @param string $response The student's response text.
     * @param string $aiprompt The grading instructions from the question.
     * @param float $defaultmark The maximum achievable score.
     * @param string $markscheme The marking criteria.
     * @return string The complete prompt ready to send to the AI.
     */
    public function build_full_ai_prompt($response, $aiprompt, $defaultmark, $markscheme): string {
        return $this->build_template_prompt($response, $aiprompt, $defaultmark, $markscheme);
    }

    /**
     * Build prompt using the structured template system.
     *
     * Supports two modes:
     * - Standard mode: Uses the admin-configured template with {{placeholders}}.
     * - Expert mode: If aiprompt contains {{response}}, it becomes the complete template.
     *
     * @param string $response The student's response text.
     * @param string $aiprompt The grading instructions from the question.
     * @param float $defaultmark The maximum achievable score.
     * @param string $markscheme The marking criteria.
     * @return string The complete prompt with all placeholders replaced.
     */
    private function build_template_prompt(string $response, string $aiprompt, float $defaultmark, string $markscheme): string {
        $template = get_config('qtype_aitext', 'prompttemplate');
        if (empty($template)) {
            $template = get_string('defaultprompttemplate', 'qtype_aitext');
        }

        $roleprompt = get_config('qtype_aitext', 'roleprompt');
        if (empty($roleprompt)) {
            $roleprompt = get_string('defaultroleprompt', 'qtype_aitext');
        }

        $jsonprompt = get_config('qtype_aitext', 'jsonprompt') ?? '';

        $language = $this->determine_output_language($aiprompt);

        $markschemetext = trim($markscheme);
        if (empty($markschemetext)) {
            $markschemetext = get_string('nomarkscheme', 'qtype_aitext');
        }

        $cleanedaiprompt = $this->clean_legacy_tags($aiprompt);

        // Expert mode: {{response}} in aiprompt makes it the complete template.
        $isexpertmode = strpos($cleanedaiprompt, '{{response}}') !== false;

        if ($isexpertmode) {
            // In expert mode, the aiprompt itself serves as the complete template.
            $expertreplacement = [
                '{{questiontext}}' => strip_tags($this->questiontext ?? ''),
                '{{markscheme}}' => $markschemetext,
                '{{response}}' => strip_tags($response),
                '{{language}}' => $language,
                '{{role}}' => trim($roleprompt),
            ];
            $prompt = str_replace(array_keys($expertreplacement), array_values($expertreplacement), $cleanedaiprompt);
        } else {
            $replacements = [
                '{{role}}' => trim($roleprompt),
                '{{questiontext}}' => strip_tags($this->questiontext ?? ''),
                '{{aiprompt}}' => trim($cleanedaiprompt),
                '{{markscheme}}' => $markschemetext,
                '{{response}}' => strip_tags($response),
                '{{language}}' => $language,
            ];
            $prompt = str_replace(array_keys($replacements), array_values($replacements), $template);
        }

        // Always append the output format section at the end of the prompt.
        // This ensures the LLM returns structured JSON and knows the max score, regardless of mode.
        $jsonprompttext = trim($jsonprompt);
        if (!empty($jsonprompttext)) {
            $prompt .= "\n\n=== OUTPUT FORMAT ===\n" . $jsonprompttext;
        }
        $prompt .= "\nMaximum score: " . $defaultmark;

        return $prompt;
    }

    /**
     * Determine the output language for the AI response.
     *
     * Priority: [[language=XX]] > [[language=""]] (disabled) > translatepostfix setting.
     *
     * @param string $aiprompt The AI prompt that may contain language override tags.
     * @return string A language code (e.g., 'en', 'de') or descriptive text.
     */
    private function determine_output_language(string $aiprompt): string {
        if (strpos($aiprompt, '[[language=""]]') !== false) {
            return 'the same language as the question';
        }

        if (preg_match('/\[\[language=([a-zA-Z]{2})\]\]/', $aiprompt, $matches)) {
            return $matches[1];
        }

        if (get_config('qtype_aitext', 'translatepostfix')) {
            return current_language();
        }

        return 'the same language as the question';
    }

    /**
     * Remove legacy placeholder tags from the AI prompt.
     *
     * Removes: [[questiontext]], [[language=XX]], [[language=""]], [[userlang]].
     * These are processed separately and should not appear in the final prompt.
     *
     * @param string $aiprompt The original AI prompt with potential legacy tags.
     * @return string The cleaned prompt.
     */
    private function clean_legacy_tags(string $aiprompt): string {
        $cleaned = str_replace('[[questiontext]]', '', $aiprompt);
        $cleaned = preg_replace('/\[\[language="?[a-zA-Z]*"?\]\]/', '', $cleaned);
        $cleaned = str_replace('[[userlang]]', '', $cleaned);

        return trim($cleaned);
    }


    /**
     * Build the full ai spellchecking prompt.
     *
     * @param string $response
     * @return string
     * @throws coding_exception
     */
    public function build_full_ai_spellchecking_prompt(string $response): string {
        return get_string('spellcheck_prompt', 'qtype_aitext') . ($response);
    }

    /**
     * Ask the LLM for a hint to help the student improve their next attempt.
     *
     * Called by qbehaviour_interactive_for_aitext after grading a submission that
     * leaves the student with a try remaining. Unlike grade_response() the reply is
     * plain prose, not JSON: a hint carries no marks, so there is nothing to parse
     * and plain text is more robust.
     *
     * Failure is never fatal. If the request fails the student simply gets the
     * "Try again" button with no hint, rather than being blocked from retrying.
     *
     * @param array $response The response the student just submitted.
     * @param string|null $hintinstruction The teacher's instruction for this try, if any.
     * @param string $previousfeedback The feedback just generated for this response.
     * @param int $attemptnumber Which submission this is, counting from 1.
     * @param string[] $previousresponses Earlier submitted responses, oldest first.
     * @return string HTML hint text, or an empty string if none could be generated.
     */
    public function generate_hint(
        array $response,
        ?string $hintinstruction,
        string $previousfeedback,
        int $attemptnumber,
        array $previousresponses = []
    ): string {
        $this->lastaihint = null;
        $this->lastaihintprompt = null;

        if (empty($response['answer'])) {
            return '';
        }

        $prompt = $this->build_full_ai_hint_prompt(
            $response['answer'],
            $hintinstruction,
            $previousfeedback,
            $attemptnumber,
            $previousresponses
        );
        $this->lastaihintprompt = $prompt;

        try {
            $hint = $this->perform_request($prompt, 'feedback');
        } catch (\moodle_exception $e) {
            // A missing hint must not stop the student retrying.
            debugging('AI hint unavailable: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return '';
        }

        if (trim($hint) === '') {
            return '';
        }

        $hint = $this->format_ai_markdown(trim($hint));
        $disclaimer = $this->build_disclaimer();
        if ($disclaimer !== '') {
            $hint .= ' ' . $disclaimer;
        }

        $this->lastaihint = $hint;
        return $hint;
    }

    /**
     * Build the complete prompt asking the LLM for a hint.
     *
     * Uses the hintprompttemplate admin setting, falling back to the
     * defaulthintprompttemplate language string. Placeholders follow the same
     * convention as the grading template.
     *
     * @param string $response The response the student just submitted.
     * @param string|null $hintinstruction The teacher's instruction for this try, if any.
     * @param string $previousfeedback The feedback just generated for this response.
     * @param int $attemptnumber Which submission this is, counting from 1.
     * @param string[] $previousresponses Earlier submitted responses, oldest first.
     * @return string The complete prompt ready to send to the AI.
     */
    public function build_full_ai_hint_prompt(
        string $response,
        ?string $hintinstruction,
        string $previousfeedback,
        int $attemptnumber,
        array $previousresponses = []
    ): string {
        $template = get_config('qtype_aitext', 'hintprompttemplate');
        if (empty($template)) {
            $template = get_string('defaulthintprompttemplate', 'qtype_aitext');
        }

        $roleprompt = get_config('qtype_aitext', 'roleprompt');
        if (empty($roleprompt)) {
            $roleprompt = get_string('defaultroleprompt', 'qtype_aitext');
        }

        $markschemetext = trim((string) $this->markscheme);
        if ($markschemetext === '') {
            $markschemetext = get_string('nomarkscheme', 'qtype_aitext');
        }

        if ($hintinstruction === null || trim($hintinstruction) === '') {
            $hintinstruction = get_string('nohintinstruction', 'qtype_aitext');
        }

        // The $previousresponses array holds only responses from earlier, already
        // committed submissions; the response being graded now is passed separately
        // as {{response}} and is not in the array.
        if (empty($previousresponses)) {
            $earlier = get_string('nopreviousresponses', 'qtype_aitext');
        } else {
            $earlier = '';
            foreach ($previousresponses as $i => $earlierresponse) {
                $earlier .= ($i + 1) . '. ' . strip_tags($earlierresponse) . "\n";
            }
            $earlier = trim($earlier);
        }

        $replacements = [
            '{{role}}' => trim($roleprompt),
            '{{questiontext}}' => strip_tags($this->questiontext ?? ''),
            '{{aiprompt}}' => trim($this->clean_legacy_tags((string) $this->aiprompt)),
            '{{markscheme}}' => $markschemetext,
            '{{response}}' => strip_tags($response),
            '{{feedback}}' => strip_tags($previousfeedback),
            '{{hintinstruction}}' => trim($hintinstruction),
            '{{attemptnumber}}' => (string) $attemptnumber,
            '{{previousresponses}}' => $earlier,
            '{{language}}' => $this->determine_output_language((string) $this->aiprompt),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
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
        // LLMs sometimes do not return the plain JSON, but it is wrapped inside HTML tags, or some
        // blabla like "Here is the JSON you asked for: ...". So we need to extract the JSON part.
        if (empty($feedback)) {
            $contentobject = new \stdClass();
            $contentobject->feedback = get_string('err_nofeedback', 'qtype_aitext');
            $contentobject->marks = null;
            return $contentobject;
        }
        $contentobject = $this->extract_single_json_object($feedback);
        if (!is_null($contentobject)) {
            $contentobject->feedback = trim($contentobject->feedback);
            $contentobject->feedback = preg_replace(['/\[\[/', '/\]\]/'], '"', $contentobject->feedback);
        } else {
            $contentobject = new \stdClass();
            $contentobject->feedback = $feedback;
            $contentobject->marks = null;
        }
        $contentobject->feedback = $this->format_ai_markdown($contentobject->feedback);
        $contentobject->feedback .= ' ' . $this->build_disclaimer();

        return $contentobject;
    }

    /**
     * Convert markdown returned by the LLM into HTML, preserving backslashes.
     *
     * format_text() interprets a backslash as an escaping character. To preserve one
     * we need to double them first. This is especially important so that the mathjax
     * filter still has a chance to have its delimiters \( ... \). The number of
     * backslashes is limited so they are not doubled if they are already doubled.
     *
     * @param string $text The raw text from the LLM.
     * @return string HTML.
     */
    private function format_ai_markdown(string $text): string {
        $text = str_replace('\\\\', '\\', $text);
        $text = str_replace('\\', '\\\\', $text);
        return format_text($text, FORMAT_MARKDOWN, ['para' => false]);
    }

    /**
     * The configured disclaimer, translated, with the model placeholder resolved.
     *
     * Must be called directly after the perform_request() whose model should be
     * named, because it reads $this->modelused.
     *
     * @return string The disclaimer text, empty if none is configured.
     */
    private function build_disclaimer(): string {
        $disclaimer = (string) get_config('qtype_aitext', 'disclaimer');
        if (trim($disclaimer) === '') {
            return '';
        }
        // Capture the model before translating: llm_translate() may itself issue a
        // perform_request('translate') which would overwrite $this->modelused with the
        // translation model. Read it here so {{model}} reflects the model that
        // generated the content. Use ?: rather than ?? so an empty string from a
        // backend also falls back to the configured model rather than rendering a
        // blank placeholder.
        $model = $this->modelused ?: ($this->model ?: '');
        $disclaimer = $this->llm_translate($disclaimer);
        // Replace {{model}} with the model the backend actually used (falls back to the configured model).
        // The legacy [[model]] form is still honoured for disclaimers configured before the rename.
        return str_replace(['{{model}}', '[[model]]'], $model, $disclaimer);
    }

    /**
     * Extracts the JSON properly from a string, also respecting { symbols inside the JSON.}
     *
     * @param string $text the JSON string possibly with extra text around it.
     * @return stdClass|null the JSON object or null if none found.
     */
    private function extract_single_json_object(string $text): ?stdClass {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }
        $depth = 0;
        $json = '';
        for ($i = $start, $len = strlen($text); $i < $len; $i++) {
            if ($text[$i] === '{') {
                $depth++;
            }
            if ($depth > 0) {
                $json .= $text[$i];
            }
            if ($text[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }
        if ($json) {
            // Sometimes, the external LLM will return bad JSON (with not properly escaped backslashes, for example in a LaTeX
            // formula). The returned JSON then contains something like "... \( K_\alpha \) ...", which is invalid JSON.
            // However, we cannot just blindly escape all backslashes, because that would also mess up valid JSON sequences like
            // "\n" or "\t" and we cannot know if these are intended well escaped backslashes or the LLM just messed up and forgot
            // to escape.
            // So we are trying to parse LaTeX-like sequences explicitely and only inside them add an extra backslash to each
            // backslash.
            // Try to decode the JSON as-is first. If the LLM returned valid JSON, use it directly.
            // This avoids corrupting already properly escaped backslashes (e.g. \\( \\frac{x}{3} \\)).
            $decoded = json_decode($json);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            $json = preg_replace_callback(
                '/\\\\{1,2}\(.*?\\\\{1,2}\)|\\\\{1,2}\[.*?\\\\{1,2}\]|\$\$.*?\$\$|\$[^$]+\$/s',
                function ($matches) {
                    if (empty($matches[0])) {
                        return $matches[0] ?? '';
                    }
                    return str_replace('\\', '\\\\', $matches[0]);
                },
                $json
            );
            $decoded = json_decode($json);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return null;
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
        if (get_config('qtype_aitext', 'translatepostfix') == 0) {
            return $text;
        }

        $cache = cache::make('qtype_aitext', 'stringdata');
        if (($translation = $cache->get(current_language() . '_' . $text)) === false) {
            $prompt = 'translate "' . $text . '" into ' . current_language() .
                    'Only return the exact text, do not wrap it in other text.';
            $translation = $this->perform_request($prompt, 'translate');
            $translation = trim($translation, '"');
            $cache->set(current_language() . '_' . $text, $translation);
        }
        return $translation;
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
            $output .= question_utils::to_plain_text(
                $response['answer'],
                $response['answerformat'],
                ['para' => false]
            );
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
        } else if (
            array_key_exists('attachments', $response)
                && $response['attachments'] instanceof question_response_files
        ) {
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
                    $prevresponse,
                    $newresponse,
                    'attachments'
                ));
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
            return parent::check_file_access(
                $qa,
                $options,
                $component,
                $filearea,
                $args,
                $forcedownload
            );
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
            return get_string(
                'maxwordlimitboundary',
                'qtype_aitext',
                ['limit' => $this->maxwordlimit, 'count' => $count]
            );
        } else if ($count < $this->minwordlimit) {
            return get_string(
                'minwordlimitboundary',
                'qtype_aitext',
                ['limit' => $this->minwordlimit, 'count' => $count]
            );
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
            return get_string(
                'wordcounttoomuch',
                'qtype_aitext',
                ['limit' => $this->maxwordlimit, 'count' => $count]
            );
        } else if ($count < $this->minwordlimit) {
            return get_string(
                'wordcounttoofew',
                'qtype_aitext',
                ['limit' => $this->minwordlimit, 'count' => $count]
            );
        } else {
            return get_string('wordcount', 'qtype_aitext', $count);
        }
    }

    /**
     * Resolve the context id which should be used for AI requests.
     *
     * This context is important because it will be used to perform permission checks. So we need the context in which
     * the user *actually* requests AI functionalities.
     *
     * Prefers the current question usage context (attempt/quiz context). If not available or the determined one is a user
     * context, the question bank context of the question will be used.
     *
     * @return int the id of the context to use for AI requests
     */
    public function get_contextid_for_ai_request(): int {
        global $DB;

        if (!is_null($this->attemptcontextid)) {
            return $this->attemptcontextid;
        }

        // The question bank context ($this->contextid) is always available as a fallback.
        // It is set by the question engine when loading the question definition.
        if (!empty($this->contextid)) {
            $this->attemptcontextid = $this->contextid;
            return $this->attemptcontextid;
        }

        // We usually should not get here, but if we do, we are falling back to the system context.
        $this->attemptcontextid = context_system::instance()->id;
        return $this->attemptcontextid;
    }
}
