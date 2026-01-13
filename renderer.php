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

require_once($CFG->dirroot . '/question/type/aitext/format_editor_renderer.php');
require_once($CFG->dirroot . '/question/type/aitext/format_plain_renderer.php');

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
                'answer',
                $qa,
                $step,
                $question->responsefieldlines,
                $options->context
            );
        } else {
            $answer = $responseoutput->response_area_read_only(
                'answer',
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

        $files = '';
        if (isset($question->attachments) && $question->attachments) {
            if (empty($options->readonly)) {
                $files = $this->files_input($qa, $question->attachments, $options);
            } else {
                $files = $this->files_read_only($qa, $options);
            }
        }

        $result = '';
        if (get_config('qtype_aitext', 'backend') === 'local_ai_manager') {
            $uniqid = uniqid();
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

        // Initialize submission feedback handling for student responses.
        $this->page->requires->js_call_amd('qtype_aitext/submission', 'init', []);
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
        $result .= html_writer::tag('div', $files, ['class' => 'attachments']);
        $result .= html_writer::end_tag('div');
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

        return $result;
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
        // This probably should be retrieved by an API call.
        $comment = $qa->get_current_manual_comment();

        if ($this->page->pagetype === 'question-bank-previewquestion-preview') {
            // Ensure $comment is an array and has content.
            if (is_array($comment) && !empty($comment[0])) {
                $this->page->requires->js_call_amd('qtype_aitext/showprompt', 'init', []);

                $prompt = $qa->get_last_qt_var('-aiprompt');

                $showprompt  = '<br /><button id="showprompt" class="rounded">';
                $showprompt .= get_string('showprompt', 'qtype_aitext') . '</button>';
                $showprompt .= '<div id="fullprompt" class="hidden">' . $prompt . '</div>';

                // Store the modified feedback in a variable.
                $feedback = $comment[0] . $showprompt;
                return $feedback;
            }

            // Return the comment if it exists, otherwise empty string.
            return (is_array($comment) && isset($comment[0])) ? $comment[0] : '';
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

        $question = $qa->get_question();
        return html_writer::nonempty_tag(
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
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        global $CFG;
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $filelist = [];

        $step = $qa->get_last_step_with_qt_var('attachments');

        foreach ($files as $file) {
            $out = html_writer::link(
                $qa->get_response_file_url($file),
                $this->output->pix_icon(
                    file_file_icon($file),
                    get_mimetype_description($file),
                    'moodle',
                    ['class' => 'icon']
                ) . ' ' . s($file->get_filename())
            );
            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $out .= plagiarism_get_links((object)[
                    'context' => $options->context->id,
                    'component' => $qa->get_question()->qtype->plugin_name(),
                    'area' => $qa->get_usage_id(),
                    'itemid' => $qa->get_slot(),
                    'userid' => $step->get_user_id(),
                    'file' => $file]);
            }
            $filelist[] = html_writer::tag('li', $out, ['class' => 'mb-2']);
        }

        $labelbyid = $qa->get_qt_field_name('attachments') . '_label';

        $fileslabel = $options->add_question_identifier_to_label(get_string('answerfiles', 'qtype_aitext'));
        $output = html_writer::tag('h4', $fileslabel, ['id' => $labelbyid, 'class' => 'sr-only']);
        $output .= html_writer::tag('ul', implode($filelist), [
            'aria-labelledby' => $labelbyid,
            'class' => 'list-unstyled m-0',
        ]);
        return $output;
    }

    /**
     * Displays the input control for when the student should upload a single file.
     * @param question_attempt $qa the question attempt to display.
     * @param int $numallowed the maximum number of attachments allowed. -1 = unlimited.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_input(
        question_attempt $qa,
        $numallowed,
        question_display_options $options
    ) {
        global $CFG, $COURSE;
        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        $pickeroptions = new stdClass();
        $pickeroptions->mainfile = null;
        $pickeroptions->maxfiles = $numallowed;
        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid(
            'attachments',
            $options->context->id
        );
        $pickeroptions->context = $options->context;
        $pickeroptions->return_types = FILE_INTERNAL | FILE_CONTROLLED_LINK;

        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid(
            'attachments',
            $options->context->id
        );
        $pickeroptions->accepted_types = $qa->get_question()->filetypeslist;

        $fm = new form_filemanager($pickeroptions);
        $fm->options->maxbytes = get_user_max_upload_file_size(
            $this->page->context,
            $CFG->maxbytes,
            $COURSE->maxbytes,
            $qa->get_question()->maxbytes
        );
        $filesrenderer = $this->page->get_renderer('core', 'files');

        $text = '';
        if (!empty($qa->get_question()->filetypeslist)) {
            $text = html_writer::tag('p', get_string('acceptedfiletypes', 'qtype_aitext'));
            $filetypesutil = new \core_form\filetypes_util();
            $filetypes = $qa->get_question()->filetypeslist;
            $filetypedescriptions = $filetypesutil->describe_file_types($filetypes);
            $text .= $this->render_from_template('core_form/filetypes-descriptions', $filetypedescriptions);
        }

        $output = html_writer::start_tag('fieldset');
        $fileslabel = $options->add_question_identifier_to_label(get_string('answerfiles', 'qtype_aitext'));
        $output .= html_writer::tag('legend', $fileslabel, ['class' => 'sr-only']);
        $output .= $filesrenderer->render($fm);
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $qa->get_qt_field_name('attachments'),
            'value' => $pickeroptions->itemid,
        ]);
        $output .= $text;
        $output .= html_writer::end_tag('fieldset');

        return $output;
    }
}


/**
 * Abstract out the differences between different type of response format.
 *
 *
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_aitext_format_renderer_base extends plugin_renderer_base {
    /** @var question_display_options Question display options instance for any necessary information for rendering the question. */
    protected $displayoptions;

    /**
     * Question number setter.
     *
     * @param question_display_options $displayoptions
     */
    public function set_displayoptions(question_display_options $displayoptions): void {
        $this->displayoptions = $displayoptions;
    }

    /**
     * Render the students response when the question is in read-only mode.
     *
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response.
     */
    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        global $USER;

        $question = $qa->get_question();
        $uniqid = uniqid();
        $readonlyareaid = 'aitext_readonly_area' . $uniqid;
        $spellcheckeditbuttonid = 'aitext_spellcheckedit' . $uniqid;

        if ($question->spellcheck) {
            $this->page->requires->js_call_amd('qtype_aitext/diff');
            $this->page->requires->js_call_amd(
                'qtype_aitext/spellcheck',
                'init',
                ['#' . $readonlyareaid, '#' . $spellcheckeditbuttonid]
            );
            $stepspellcheck = $qa->get_last_step_with_qt_var('-spellcheckresponse');
            $stepanswer = $qa->get_last_step_with_qt_var('answer');
        }
        // Lib to display the spellcheck diff.
        $labelbyid = $qa->get_qt_field_name($name) . '_label';
        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_aitext'));
        $output = html_writer::tag('h4', $responselabel, ['id' => $labelbyid, 'class' => 'sr-only']);

        $divoptions = [
                'id' => $readonlyareaid,
                'role' => 'textbox',
                'aria-readonly' => 'true',
                'aria-labelledby' => $labelbyid,
                'class' => $this->class_name() . ' qtype_aitext_response readonly',
                'style' => 'min-height: ' . ($lines * 1.25) . 'em;',
        ];

        if ($qa->get_question()->spellcheck) {
            $divoptions['data-spellcheck'] = $this->prepare_response('-spellcheckresponse', $qa, $stepspellcheck, $context);
            $divoptions['data-spellcheckattemptstepid'] = $stepspellcheck->get_id();
            $divoptions['data-spellcheckattemptstepanswerid'] = $stepanswer->get_id();
            $divoptions['data-answer'] = $this->prepare_response($name, $qa, $step, $context);
        }

        $output .= html_writer::tag('div', $this->prepare_response($name, $qa, $step, $context), $divoptions);

        if (
                $qa->get_question()->spellcheck &&
                (
                        has_capability('mod/quiz:grade', $context) ||
                        has_capability('mod/quiz:regrade', $context) ||
                        ($context->contextlevel === CONTEXT_USER && intval($USER->id) === intval($context->instanceid))
                )
        ) {
            $btnoptions = ['id' => $spellcheckeditbuttonid, 'class' => 'btn btn-link'];
            $output .= html_writer::tag(
                'button',
                $this->output->pix_icon(
                    'i/edit',
                    get_string('spellcheckedit', 'qtype_aitext'),
                    'moodle'
                ) . " " . get_string('spellcheckedit', 'qtype_aitext'),
                $btnoptions
            );
        }
        // Height $lines * 1.25 because that is a typical line-height on web pages.
        // That seems to give results that look OK.

        return $output;
    }

    /**
     * Render the students respone when the question is in read-only mode.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response for editing.
     */
    abstract public function response_area_input(
        $name,
        question_attempt $qa,
        question_attempt_step $step,
        $lines,
        $context
    );

    /**
     * Specific class name to add to the input element.
     *
     * @return string
     */
    abstract protected function class_name();
}




/**
 * An aitext format renderer for aitexts for plain input
 *
 * With an input box with a monospaced font. You might use this, for example, for a
 * question where the students should type computer code.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_format_monospaced_renderer extends qtype_aitext_format_plain_renderer {
    /**
     * Specific class name to add to the input element.
     *
     * @return string
     */
    protected function class_name() {
        return 'qtype_aitext_monospaced';
    }
}
