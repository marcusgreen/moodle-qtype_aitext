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
 * Strings for component 'qtype_aitext', language 'en'
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['acceptedfiletypes'] = 'Accepted file types';
$string['addsample'] = 'Add a sample response';
$string['aiprompt'] = 'AI prompt';
$string['aiprompt_help'] = 'A prompt for the AI Grader. This is the guideline that AI uses to give feedback on the student response.

Expert mode: If you include the placeholder {{response}} in your prompt, it will be used as the complete prompt template, bypassing the admin template. Available placeholders: {{response}}, {{questiontext}}, {{defaultmark}}, {{markscheme}}, {{jsonprompt}}, {{language}}, {{role}}.';
$string['aipromptmissing'] = 'The AI prompt is missing. Please enter a prompt on the basis of which the feedback is generated.';
$string['answerfiles'] = 'Answer files';
$string['answertext'] = 'Answer text';
$string['attachmentsoptional'] = 'Attachments are optional';
$string['automatic_spellcheck'] = 'Automatic spellcheck';
$string['backends'] = 'AI back end systems';
$string['backends_text'] = 'Core AI system was introduced with Moodle 4.5, Local AI System is from https://github.com/mebis-lp/moodle-local_ai_manager and Tool AI System is from https://github.com/marcusgreen/moodle-tool_aiconnect';
$string['cachedef_stringdata'] = 'Cachedef stringdata';
$string['coreaisubsystem'] = 'Core AI subsystem ';
$string['defaultmarksscheme'] = 'Marks scheme';
$string['defaultmarksscheme_setting'] = 'This will be the default marks scheme for new questions. Questions authors should alter this to suit the question.';
$string['defaultprompt'] = 'AI prompt';
$string['defaultprompt_setting'] = 'This will be the default AI prompt for new questions. It tells the AI grader how to analyse the student response. It is the guideline that AI uses to give feedback on the student response. Question authors should alter this to suit the question.';
$string['defaultprompttemplate'] = '=== ROLE ===
{{role}}

=== QUESTION ===
{{questiontext}}

=== GRADING INSTRUCTIONS ===
{{aiprompt}}

=== SCORING ===
Maximum score: {{defaultmark}}
{{markscheme}}

=== STUDENT RESPONSE TO GRADE ===
{{response}}

=== OUTPUT FORMAT ===
{{jsonprompt}}

=== LANGUAGE ===
Respond in {{language}}.';
$string['defaultroleprompt'] = 'You are an experienced teacher who grades student responses fairly and constructively. Provide helpful feedback that helps the student learn.';
$string['deletesample'] = 'Delete sample';
$string['deprecated'] = '(Deprecated - use prompt template instead)';
$string['disclaimer'] = 'Disclaimer';
$string['disclaimer_setting'] = 'Text appended to each response indicating feedback is from a Large Language Model and not a human';
$string['enable_expertmode'] = 'Enable expert mode';
$string['enable_expertmode_setting'] = 'When enabled, shows the "Expert mode template" button in the question editing form, allowing teachers to use the expert mode prompt template.';
$string['err_invalidbackend'] = 'Err invalidbackend;';
$string['err_maxminmismatch'] = 'Maximum word limit must be greater than minimum word limit';
$string['err_maxwordlimit'] = 'Maximum word limit is enabled but is not set';
$string['err_maxwordlimitnegative'] = 'Maximum word limit cannot be a negative number';
$string['err_minwordlimit'] = 'Minimum word limit is enabled but is not set';
$string['err_minwordlimitnegative'] = 'Minimum word limit cannot be a negative number';
$string['err_parammissing'] = 'Invalid parameters. Make sure you have a sample answer and prompt';
$string['err_retrievingfeedback'] = 'Error retrieving feedback vom KI-Tool: {$a}';
$string['err_retrievingfeedback_checkconfig'] = 'Unable to retrieve feedback. AI System Configuration might be wrong, contact your Administrator.';
$string['err_retrievingtranslation'] = 'Error retrieving translation: {$a}';
$string['expertmodeconfirm'] = 'This will replace the current prompt with the expert mode template.<br><br><strong>What is expert mode?</strong><br>In expert mode, you have full control over the entire AI prompt. The admin template is ignored and your prompt is sent directly to the AI.<br><br><strong>Available placeholders:</strong><ul><li><code>{{response}}</code> - The student\'s answer (required to activate expert mode)</li><li><code>{{questiontext}}</code> - The question text</li><li><code>{{markscheme}}</code> - The grading criteria</li><li><code>{{defaultmark}}</code> - Maximum achievable points</li><li><code>{{language}}</code> - The user\'s language for the response</li><li><code>{{jsonprompt}}</code> - Instructions for JSON output format</li><li><code>{{role}}</code> - The role description</li></ul><strong>Note:</strong> The <code>{{role}}</code> placeholder inserts the admin-defined role prompt. You can either use this placeholder or write your own role description directly in your prompt.<br><br>Continue?';
$string['formateditor'] = 'HTML editor';
$string['formateditorfilepicker'] = 'HTML editor with file picker';
$string['formatmonospaced'] = 'Plain text, monospaced font';
$string['formatnoinline'] = 'No online text';
$string['formatplain'] = 'Plain text';
$string['get_llmmfeedback'] = 'Get LLM feedback';
$string['graderinfo'] = 'Information for graders';
$string['graderinfoheader'] = 'Grader information';
$string['jsonprompt'] = 'JSon prompt';
$string['jsonprompt_setting'] = 'Instructions sent to convert the returned value into json';
$string['localaimanager'] = 'Local AI manager';
$string['markprompt_required'] = 'Mark prompt required';
$string['markprompt_required_setting'] = 'If set, when authoring a question a prompt asking for marking is a required field and will show an error if it is left empty';
$string['markscheme'] = 'Mark scheme';
$string['markscheme_help'] = 'This will tell the AI grader how to give a numerical grade to the student response. The total possible score is this question\'s \'Default mark\'';
$string['markschememissing'] = 'The mark scheme is missing. Please enter a prompt, how to mark the users input';
$string['maxwordlimit'] = 'Maximum word limit';
$string['maxwordlimit_help'] = 'If the response requires that students enter text, this is the maximum number of words that each student will be allowed to submit.';
$string['maxwordlimitboundary'] = 'The word limit for this question is {$a->limit} words and you are attempting to submit {$a->count} words. Please shorten your response and try again.';
$string['minwordlimit'] = 'Minimum word limit';
$string['minwordlimit_help'] = 'If the response requires that students enter text, this is the minimum number of words that each student will be allowed to submit.';
$string['minwordlimitboundary'] = 'This question requires a response of at least {$a->limit} words and you are attempting to submit {$a->count} words. Please expand your response and try again.';
$string['model'] = 'Model';
$string['nlines'] = '{$a} lines';
$string['nomarkscheme'] = 'No mark scheme provided. Set marks to null.';
$string['pluginname'] = 'AI Text';
$string['pluginname_help'] = 'In response to a question, the respondent enters text. A response template may be provided. Responses are given a preliminary grade by an AI system (e.g. ChatGPT) then can be graded manually.';
$string['pluginname_link'] = 'question/type/AI Text';
$string['pluginname_userfaced'] = 'Question type "AI text" with AI supported feedback generation';
$string['pluginnameadding'] = 'Adding an AI Text question';
$string['pluginnameediting'] = 'Editing an AI Text question';
$string['pluginnamesummary'] = 'Allows a response of a file upload and/or online text. The student response is processed by the configured AI/Large language model which returns feedback and optionally a grade..';
$string['privacy::responsefieldlines'] = 'Number of lines indicating the size of the input box (textarea).';
$string['privacy:metadata'] = 'AI Text question type plugin allows question authors to set default options as user preferences.';
$string['privacy:preference:attachments'] = 'Number of allowed attachments.';
$string['privacy:preference:attachmentsrequired'] = 'Number of required attachments.';
$string['privacy:preference:defaultmark'] = 'The default mark set for a given question.';
$string['privacy:preference:disclaimer']  = 'Text to indicate the feedback and/or marking is from a LLM';
$string['privacy:preference:maxbytes'] = 'Maximum file size.';
$string['privacy:preference:responseformat'] = 'What is the response format (HTML editor, plain text, etc.)?';
$string['prompttemplate'] = 'Prompt template';
$string['prompttemplate_setting'] = 'The structured template for building the AI prompt. Use placeholders: {{role}}, {{questiontext}}, {{aiprompt}}, {{defaultmark}}, {{markscheme}}, {{response}}, {{jsonprompt}}, {{language}}. Leave a placeholder empty section to omit it.';
$string['purposeplacedescription_feedback'] = 'Generation of feedback suggestions when submitting a quiz attempt or when regrading';
$string['purposeplacedescription_translate'] = 'Translation of the disclaimer and AI generated feedback to the user\'s target language.';
$string['response'] = 'Response';
$string['responsefieldlines'] = 'Input box size';
$string['responseformat'] = 'Response format';
$string['responseformat_setting'] = 'The editor the student uses when responding';
$string['responseisrequired'] = 'Require the student to enter text';
$string['responsenotrequired'] = 'Text input is optional';
$string['responseoptions'] = 'Response options';
$string['responsetemplate'] = 'Response template';
$string['responsetemplate_help'] = 'Any text entered here will be displayed in the response input box when a new attempt at the question starts.';
$string['responsetemplateheader'] = 'Response template';
$string['responsetester'] = 'Response Tester';
$string['responsetesthelp'] = 'Response test help';
$string['responsetesthelp_help'] = 'When the form is saved only the test response is saved, not the value returned by the LLM';
$string['responsetests'] = 'Test output from multiple responses';
$string['roleprompt'] = 'Role prompt';
$string['roleprompt_setting'] = 'The system role description for the AI. This tells the AI what role it should take when grading.';
$string['sampleresponse'] = 'Sample response';
$string['sampleresponse_help'] = 'The sample response can be used to test how the AI grader will respond to a given response.';
$string['sampleresponseempty'] = 'Make sure that you have an AI prompt and sample respons before testing.';
$string['sampleresponseeval'] = 'Sample response evaluation';
$string['sampleresponseevaluate'] = 'Evaluate sample response';
$string['showprompt'] = 'Show prompt';
$string['spellcheck_editor_desc'] = 'This is the text in which the spelling mistakes have been corrected by AI. You can edit this suggested correction.';
$string['spellcheck_prompt'] = 'Reproduce the text 1:1. Give no feedback!. But correct all spelling mistakes in the following text: ';
$string['spellcheck_student_anser_desc'] = 'This is the original student\'s answer';
$string['spellcheckedit'] = 'Edit spellcheck';
$string['spellcheckeditor'] = 'Edit the ai based spellcheck';
$string['testresponses'] = 'Test responses';
$string['thedefaultmarksscheme'] = 'Deduct a point from the total score for each grammar or spelling mistake.';
$string['thedefaultprompt'] = 'Explain if there is anything wrong with the grammar and spelling in the text.';
$string['toolaimanager'] = 'Tool AI manager';
$string['translatepostfix'] = 'Translate postfix';
$string['translatepostfix_text'] = 'The end of the prompt has &quot;translate the feedback to the language .current_language()&quot; appended';
$string['use_local_ai_manager'] = 'Use AI backend provided by local_ai_manager plugin';
$string['use_local_ai_manager_setting'] = 'Use the local_ai_manager plugin to process AI related queries (must be installed)';
$string['useexpertmodetemplate'] = 'Use expert mode template';
$string['wordcount'] = 'Word count: {$a}';
$string['wordcounttoofew'] = 'Word count: {$a->count}, less than the required {$a->limit} words.';
$string['wordcounttoomuch'] = 'Word count: {$a->count}, more than the limit of {$a->limit} words.';
