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
 * Based on core Moodle qtype_essay originating at the UK Open University
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use qtype_aitext\response_formatter;

require_once($CFG->dirroot . '/question/type/aitext/format_base_renderer.php');
require_once($CFG->dirroot . '/question/type/aitext/format_editor_renderer.php');
require_once($CFG->dirroot . '/question/type/aitext/format_plain_renderer.php');
require_once($CFG->dirroot . '/question/type/aitext/format_monospaced_renderer.php');

/**
 * Generates the output for aitext questions.
 *
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_renderer extends qtype_renderer {
    /**
     * Generate the display of the formulation part of the question shown at runtime
     * in a quiz
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string
     */
    public function formulation_and_controls(
        question_attempt $qa,
        question_display_options $options
    ) {
        global $CFG, $USER;

        /** @var qtype_aitext_question $question */
        $question = $qa->get_question();

        /** @var qtype_aitext_format_renderer_base $responseoutput */
        $responseoutput = $question->get_format_renderer($this->page);
        $responseoutput->set_displayoptions($options);

        // Answer field.
        $step = $qa->get_last_step_with_qt_var('answer');

        if (!$step->has_qt_var('answer') && empty($options->readonly)) {
            // Question has never been answered, fill it with response template.
            $step = new question_attempt_step(['answer' => $question->responsetemplate]);
        }

        if (empty($options->readonly)) {
            $answer = $responseoutput->response_area_input(
                $qa,
                $step,
                $question->responsefieldlines,
                $options->context
            );
        } else {
            $answer = $responseoutput->response_area_read_only(
                $qa,
                $step,
                $question->responsefieldlines,
                $options->context
            );
            $answer .= html_writer::nonempty_tag('p', $question->get_word_count_message_for_review($step->get_qt_data()));

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $answer .= plagiarism_get_links([
                    'context' => $options->context->id,
                    'component' => $qa->get_question()->qtype->plugin_name(),
                    'area' => $qa->get_usage_id(),
                    'itemid' => $qa->get_slot(),
                    'userid' => $step->get_user_id(),
                    'content' => $qa->get_response_summary()]);
            }
        }

        $result = '';
        $uniqid = uniqid();
        if (get_config('qtype_aitext', 'backend') === 'local_ai_manager') {
            $result .= html_writer::tag(
                'div',
                '',
                ['data-content' => 'local_ai_manager_infobox', 'data-boxid' => $uniqid]
            );
            $this->page->requires->js_call_amd(
                'local_ai_manager/infobox',
                'renderInfoBox',
                ['qtype_aitext', $USER->id, '[data-content="local_ai_manager_infobox"][data-boxid="' . $uniqid . '"]',
                ['feedback']]
            );
        }
        $result .= html_writer::tag(
            'div',
            $question->format_questiontext($qa),
            ['class' => 'qtext']
        );

        $result .= html_writer::start_tag('div', ['class' => 'ablock']);
        $result .= html_writer::tag('div', $answer, ['class' => 'answer']);

        // If there is a response and min/max word limit is set in the form then check the response word count.
        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag(
                'div',
                $question->get_validation_error($step->get_qt_data()),
                ['class' => 'validationerror']
            );
        }

        // Add the spellcheck feedback, only in readonly mode and depending on the display options just like manualcomment .
        if ($question->spellcheck && $options->readonly && $options->manualcomment != question_display_options::HIDDEN) {
            $result .= $this->add_spellchecked_response_container($qa, $options->context, $uniqid);
        }

        if (get_config('qtype_aitext', 'backend') === 'local_ai_manager') {
            $result .= html_writer::tag(
                'div',
                '',
                ['data-content' => 'local_ai_manager_warningbox', 'data-boxid' => $uniqid]
            );
            $this->page->requires->js_call_amd(
                'local_ai_manager/warningbox',
                'renderWarningBox',
                ['[data-content="local_ai_manager_warningbox"][data-boxid="' . $uniqid . '"]']
            );
        }

        $result .= html_writer::end_tag('div');

        return $result;
    }

    /**
     * Extract plain text from the response for use in the spellcheck diff.
     *
     * Uses the same format-aware conversion that built the text sent to the AI
     * (see \qtype_aitext\response_formatter::to_spellcheck_text()): prose is reduced to plain text
     * (character-changing formatting neutralised) while any code is kept verbatim and fenced. This
     * keeps both sides of the spellcheck diff consistent, since both are reduced identically.
     *
     * @param question_attempt $qa
     * @return string
     */
    protected function get_plain_text_response(question_attempt $qa) {
        $answerstep = $qa->get_last_step_with_qt_var('answer');
        $answer = $answerstep->get_qt_var('answer');
        $answerformat = $answerstep->get_qt_var('answerformat') ?? FORMAT_HTML;
        return $answer ?
            response_formatter::to_spellcheck_text($answer, $answerformat) : null;
    }

    /**
     * Reduce the spellcheck text to plain text suitable for char-by-char diffs in JS.
     *
     * Both values are treated as plain text (FORMAT_PLAIN), i.e. returned verbatim (only trimmed):
     *  - The teacher-edited value (spellcheckedit) is produced by an editor that is forced to
     *    FORMAT_PLAIN (with PARAM_TEXT); note that its format is not persisted, only the text is.
     *  - The AI-generated value (_spellcheckresponse) is the raw LLM output. The model is given the
     *    student answer that has *already* been normalised by response_formatter::to_spellcheck_text()
     *    (prose reduced to plain text, code kept verbatim and fenced) and is asked to reproduce it
     *    1:1 with spelling fixed, so its output is that same plain text.
     *
     * @param question_attempt $qa the question attempt.
     * @return string plain text spellcheck content.
     */
    protected function get_spellchecked_response(question_attempt $qa): string {
        $teacheredit = $qa->get_last_behaviour_var('spellcheckedit');
        if ($teacheredit !== null) {
            return response_formatter::to_spellcheck_text($teacheredit, FORMAT_PLAIN);
        }
        $ai = $qa->get_last_behaviour_var('_spellcheckresponse') ?? '';
        return response_formatter::to_spellcheck_text($ai, FORMAT_PLAIN);
    }

    /**
     * Return the ai evaluation into the feedback area, instead
     * of the normal fixed/hint feedback when in preview mode.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string HTML fragment.
     */
    public function feedback(question_attempt $qa, question_display_options $options) {
        // Get data written in the question.php grade_response method.
        $comment = $qa->get_last_behaviour_var('_comment');

        if ($this->page->pagetype === 'question-bank-previewquestion-preview') {
            // Ensure $comment is an array and has content.
            if (!empty($comment)) {
                $this->page->requires->js_call_amd('qtype_aitext/showprompt', 'init', []);

                $prompt = $qa->get_last_behaviour_var('_aiprompt');

                // Clean the prompt so no script/JS can be injected, while keeping safe HTML.
                $prompt = format_text($prompt, FORMAT_HTML, [
                    'context' => $options->context ?? $this->page->context,
                    'noclean' => false,
                ]);

                $showprompt  = '<br /><button id="showprompt" class="rounded">';
                $showprompt .= get_string('showprompt', 'qtype_aitext') . '</button>';
                $showprompt .= '<div id="fullprompt" class="hidden">' . $prompt . '</div>';

                // Store the modified feedback in a variable.
                $feedback = $comment . $showprompt;
                return $feedback;
            }

            // Return the comment if it exists, otherwise empty string.
            return $comment ?? '';
        }

        return '';
    }

    /**
     * Show grader information above the manual comment field when grading.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string
     */
    public function manual_comment(question_attempt $qa, question_display_options $options) {
        if ($options->manualcomment != question_display_options::EDITABLE) {
            return '';
        }

        $output = '';

        $question = $qa->get_question();
        $output .= html_writer::nonempty_tag(
            'div',
            $question->format_text(
                $question->graderinfo,
                $question->graderinfoformat,
                $qa,
                'qtype_aitext',
                'graderinfo',
                $question->id
            ),
            ['class' => 'graderinfo']
        );

        // Show AI-generated feedback as a reference for the grader.
        $aicomment = $qa->get_last_behaviour_var('_comment');
        if (!empty($aicomment)) {
            $heading = get_string('aifeedbackforgrader', 'qtype_aitext');
            $helpicon = $this->output->help_icon(
                'aifeedbackforgrader',
                'qtype_aitext'
            );

            $output .= html_writer::start_tag(
                'div',
                ['class' => 'alert alert-info mt-2 mb-2']
            );
            $output .= html_writer::tag(
                'div',
                html_writer::tag('strong', $heading) . ' ' . $helpicon,
                ['class' => 'mb-1']
            );
            $output .= format_text(
                $aicomment,
                FORMAT_HTML,
                ['context' => $options->context]
            );
            $output .= html_writer::end_tag('div');
        }

        return $output;
    }

    /**
     * Add a container showing the diffs between the user response and the spellchecked version.
     * @param question_attempt $qa
     * @param context $context
     * @param string $uniqid
     * @return string
     * @throws coding_exception
     */
    protected function add_spellchecked_response_container(
        question_attempt $qa,
        context $context,
        string $uniqid
    ) {
        global $USER;
        $htmlfragment = "";
        $spellcheckareaid = 'aitext_spellcheck_area_' . $uniqid;
        $spellcheckeditbuttonid = 'aitext_spellcheckedit_' . $uniqid;
        $collapseid = 'aitext_spellcheck_collapse_' . $uniqid;
        $spellcheckedresponse = $this->get_spellchecked_response($qa);
        $response = $this->get_plain_text_response($qa);
        // Lib to display the spellcheck diff.
        $this->page->requires->js_call_amd('qtype_aitext/diff');
        $this->page->requires->js_call_amd(
            'qtype_aitext/spellcheck',
            'init',
            ['#' . $spellcheckareaid, '#' . $spellcheckeditbuttonid]
        );

        // Toggle link for the collapsible area.
        $collapsedicon = html_writer::tag(
            'span',
            $this->output->pix_icon('t/collapsedchevron', get_string('expand')),
            ['class' => 'collapsed-icon icon-no-margin'],
        );
        $expandedicon = html_writer::tag(
            'span',
            $this->output->pix_icon('t/expandedchevron', get_string('collapse')),
            ['class' => 'expanded-icon icon-no-margin'],
        );

        $togglelink = html_writer::link(
            '#' . $collapseid,
            $expandedicon . $collapsedicon . get_string('spellchecktoggle', 'qtype_aitext'),
            [
                'data-bs-toggle' => 'collapse',
                'aria-expanded' => 'true',
                'aria-controls' => $collapseid,
                'role' => 'button',
                'class' => 'btn-outline-primary icons-collapse-expand my-3',
            ]
        );

        $htmlfragment .= html_writer::tag(
            'div',
            $togglelink,
            ['class' => 'd-flex align-items-center'],
        );

        // Collapsible content (default open).
        $collapsiblecontent = '';

        $divoptions = [];
        $divoptions['id'] = $spellcheckareaid;
        $divoptions['data-content'] = 'qtype_aitext_spellcheck';
        $divoptions['data-spellcheck'] = $spellcheckedresponse;
        $divoptions['data-questionattemptid'] = $qa->get_database_id() ?? '';
        $divoptions['data-answer'] = $response;
        $collapsiblecontent .= html_writer::tag(
            'div',
            $response,
            $divoptions,
        );

        if (
            has_capability('mod/quiz:grade', $context) ||
            has_capability('mod/quiz:regrade', $context) ||
            ($context->contextlevel === CONTEXT_USER && intval($USER->id) === intval($context->instanceid))
        ) {
            $btnoptions = ['id' => $spellcheckeditbuttonid, 'class' => 'btn btn-link'];
            $collapsiblecontent .= html_writer::tag(
                'button',
                $this->output->pix_icon(
                    'i/edit',
                    get_string('spellcheckedit', 'qtype_aitext'),
                    'moodle'
                ) . " " . get_string('spellcheckedit', 'qtype_aitext'),
                $btnoptions
            );
        }

        // Wrap content in the collapse div.
        $htmlfragment .= html_writer::tag(
            'div',
            $collapsiblecontent,
            [
                'id' => $collapseid,
                'class' => 'collapse show',
            ]
        );

        return $htmlfragment;
    }
}
