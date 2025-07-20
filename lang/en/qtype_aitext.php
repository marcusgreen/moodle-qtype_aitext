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
$string['aiprompt'] = 'AI Prompt';
$string['aiprompt_help'] = 'A prompt for the Ai Grader. This is the guideline that AI uses to give feedback on the student response.';
$string['aipromptmissing'] = 'The ai prompt is missing. Please enter a prompt on the basis of which the feedback is generated.';
$string['answerfiles'] = 'Answer files';
$string['answertext'] = 'Answer text';
$string['answeraudio'] = 'Answer audio';
$string['attachmentsoptional'] = 'Attachments are optional';
$string['batchmode'] = 'Batch mode';
$string['batchmode_setting'] = 'Requests to the external LLM will be queued';
$string['automatic_spellcheck'] = 'Automatic spellcheck';
$string['backends'] = 'AI back end systems';
$string['backends_text'] = 'Core AI System was introduced with Moodle 4.5, Local AI System is from https://github.com/mebis-lp/moodle-local_ai_manager and Tool AI System is from https://github.com/marcusgreen/moodle-tool_aiconnect';
$string['cachedef_stringdata'] = 'Cachedef stringdata';
$string['coreaisubsystem'] = 'Core AI  Subsystem ';
$string['defaultmarksscheme'] = 'Marks scheme';
$string['defaultmarksscheme_setting'] = 'This will be the default marks scheme for new questions. Questions authors should alter this to suit the question.';
$string['defaultprompt'] = 'AI Prompt';
$string['defaultprompt_setting'] = 'This will be the default AI prompt for new questions. It tells the AI grader how to analyse the student response. It is the guideline that AI uses to give feedback on the student response. Question authors should alter this to suit the question.';
$string['deletesample'] = 'Delete sample';
$string['disclaimer'] = 'Disclaimer';
$string['disclaimer_setting'] = 'Text appended to each response indicating feedback is from a Large Language Model and not a human';
$string['err_invalidbackend'] = 'Err invalidbackend;';
$string['err_maxminmismatch'] = 'Maximum word limit must be greater than minimum word limit';
$string['err_maxwordlimit'] = 'Maximum word limit is enabled but is not set';
$string['err_maxwordlimitnegative'] = 'Maximum word limit cannot be a negative number';
$string['err_minwordlimit'] = 'Minimum word limit is enabled but is not set';
$string['err_minwordlimitnegative'] = 'Minimum word limit cannot be a negative number';
$string['err_parammissing'] = 'Invalid parameters. Make sure you have a sample answer and prompt';
$string['err_retrievingfeedback'] = 'Error retrieving feedback vom KI-Tool: {$a}';
$string['err_retrievingtranslation'] = 'Error retrieving translation: {$a}';
$string['formataudio'] = 'Audio format';
$string['feedbacklanguage'] = 'Feedback language';
$string['feedbacklanguage_setting'] = 'Feedback 1 language settings';
$string['formateditor'] = 'HTML editor';
$string['formateditorfilepicker'] = 'HTML editor with file picker';
$string['formatmonospaced'] = 'Plain text, monospaced font';
$string['formatnoinline'] = 'No online text';
$string['formatplain'] = 'Plain text';
$string['get_llmmfeedback'] = 'Get LLM feedback';
$string['graderinfo'] = 'Information for graders';
$string['graderinfoheader'] = 'Grader information';
$string['jsonprompt'] = 'JSON prompt';
$string['jsonprompt_setting'] = 'Instructions sent to convert the returned value into json';
$string['localaimanager'] = 'Local AI Manager';
$string['markprompt_required'] = 'Mark prompt required';
$string['markprompt_required_setting'] = 'If set, when authoring a question a prompt asking for marking is a required field and will show an error if it is left empty';
$string['markscheme'] = 'Mark scheme';
$string['markscheme_help'] = 'This will tell the AI grader how to give a numerical grade to the student response. The total possible score is this question\'s \'Default mark\'';
$string['markschememissing'] = 'The marke scheme is missing. Please enter a prompt, how to mark the users input';
$string['maxwordlimit'] = 'Maximum word limit';
$string['maxwordlimit_help'] = 'If the response requires that students enter text, this is the maximum number of words that each student will be allowed to submit.';
$string['maxwordlimitboundary'] = 'The word limit for this question is {$a->limit} words and you are attempting to submit {$a->count} words. Please shorten your response and try again.';
$string['minwordlimit'] = 'Minimum word limit';
$string['minwordlimit_help'] = 'If the response requires that students enter text, this is the minimum number of words that each student will be allowed to submit.';
$string['minwordlimitboundary'] = 'This question requires a response of at least {$a->limit} words and you are attempting to submit {$a->count} words. Please expand your response and try again.';
$string['model'] = 'Model';
$string['nlines'] = '{$a} lines';
$string['pluginname'] = 'AI Text';
$string['pluginname_help'] = 'In response to a question, the respondent enters text. A response template may be provided. Responses are given a preliminary grade by an AI system (e.g. ChatGPT) then can be graded manually.';
$string['pluginname_link'] = 'question/type/AI Text';
$string['pluginnameadding'] = 'Adding an AI Text question';
$string['pluginnameediting'] = 'Editing an AI Text question';
$string['pluginnamesummary'] = 'Allows a response of a file upload and/or online text. The student response is processed by the configured AI/Large language model which returns feedback and optionally a grade..';
$string['privacy:preference:attachments'] = 'Number of allowed attachments.';
$string['privacy:preference:attachmentsrequired'] = 'Number of required attachments.';
$string['privacy:preference:defaultmark'] = 'The default mark set for a given question.';
$string['privacy:preference:maxbytes'] = 'Maximum file size.';
$string['privacy:preference:responseformat'] = 'What is the response format (HTML editor, plain text, etc.)?';
$string['prompt'] = 'Prompt';
$string['prompt_setting'] = 'Wrapper text for the prompt set to the AI System, [responsetext] is whatever the student typed as an answer. The ai prompt value from the question will be appended to this';
$string['response'] = 'Response';
$string['responsefieldlines'] = 'Input box size';
$string['responseformat'] = 'Response format';
$string['responseformat_setting'] = 'The editor the student uses when responding';
$string['responselanguage'] = 'Response language';
$string['responselanguage_setting'] = 'The response language we are expecting';
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
$string['sampleresponse'] = 'Sample Response';
$string['sampleresponse_help'] = 'The sample response can be used to test how the AI grader will respond to a given response.';
$string['sampleresponseempty'] = 'Make sure that you have an AI prompt and sample respons before testing.';
$string['sampleresponseeval'] = 'Sample Response Evaluation';
$string['sampleresponseevaluate'] = 'Evaluate Sample Response';
$string['showprompt'] = 'Show prompt';
$string['thedefaultmarksscheme'] = 'Deduct a point from the maximum score for each grammar or spelling mistake.';
$string['thedefaultprompt'] = 'Explain if there is anything wrong with the grammar and spelling in the text.';
$string['usecoreai'] = 'Use core ai';
$string['usecoreai_setting'] = 'If you are using Moodle 4.5 or above you can use the core ai subsystem. Otherwise you will need to have tool_aiconnect installed.';
$string['spellcheck_editor_desc'] = 'This is the text in which the spelling mistakes have been corrected by AI. You can edit this suggested correction.';
$string['spellcheck_prompt'] = 'Reproduce the text 1:1. Give no feedback!. But correct all spelling mistakes in the following text: ';
$string['spellcheck_student_anser_desc'] = 'This is the original student\'s answer';
$string['spellcheckedit'] = 'Edit spellcheck';
$string['spellcheckeditor'] = 'Edit the ai based spellcheck';
$string['testresponses'] = 'Test responses';
$string['thedefaultmarksscheme'] = 'Deduct a point from the total score for each grammar or spelling mistake.';
$string['thedefaultprompt'] = 'Explain if there is anything wrong with the grammar and spelling in the text.';
$string['toolaimanager'] = 'Tool AI Manager';
$string['translatepostfix'] = 'Translate postfix';
$string['translatepostfix_text'] = 'The end of the prompt has &quot;translate the feedback to the language .current_language()&quot; appended';
$string['use_local_ai_manager'] = 'Use AI backend provided by local_ai_manager plugin';
$string['use_local_ai_manager_setting'] = 'Use the local_ai_manager plugin to process AI related queries (must be installed)';
$string['wordcount'] = 'Word count: {$a}';
$string['wordcounttoofew'] = 'Word count: {$a->count}, less than the required {$a->limit} words.';
$string['wordcounttoomuch'] = 'Word count: {$a->count}, more than the limit of {$a->limit} words.';


$string['currentlanguage'] = 'Current language';
$string['en-us'] = 'English (US)';
$string['es-us'] = 'Spanish (US)';
$string['en-au'] = 'English (Aus.)';
$string['en-nz'] = 'English (NZ)';
$string['en-za'] = 'English (S.Africa)';
$string['en-gb'] = 'English (GB)';
$string['fr-ca'] = 'French (Can.)';
$string['fr-fr'] = 'French (FR)';
$string['it-it'] = 'Italian (IT)';
$string['pt-br'] = 'Portuguese (BR)';
$string['en-in'] = 'English (IN)';
$string['es-es'] = 'Spanish (ES)';
$string['fr-fr'] = 'French (FR)';
$string['fil-ph'] = 'Filipino';
$string['de-de'] = 'German (DE)';
$string['de-ch'] = 'German (CH)';
$string['de-at'] = 'German (AT)';
$string['da-dk'] = 'Danish (DK)';
$string['hi-in'] = 'Hindi';
$string['ko-kr'] = 'Korean';
$string['ar-ae'] = 'Arabic (Gulf)';
$string['ar-sa'] = 'Arabic (Modern Standard)';
$string['zh-cn'] = 'Chinese (Mandarin-Mainland)';
$string['nl-nl'] = 'Dutch (NL)';
$string['nl-be'] = 'Dutch (BE)';
$string['en-ie'] = 'English (Ireland)';
$string['en-wl'] = 'English (Wales)';
$string['en-ab'] = 'English (Scotland)';
$string['fa-ir'] = 'Farsi';
$string['he-il'] = 'Hebrew';
$string['id-id'] = 'Indonesian';
$string['ja-jp'] = 'Japanese';
$string['ms-my'] = 'Malay';
$string['pt-pt'] = 'Portuguese (PT)';
$string['ru-ru'] = 'Russian';
$string['ta-in'] = 'Tamil';
$string['te-in'] = 'Telugu';
$string['tr-tr'] = 'Turkish';
$string['uk-ua'] = 'Ukranian';
$string['eu-es'] = 'Basque';
$string['fi-fi'] = 'Finnish';
$string['hu-hu'] = 'Hungarian';
$string['sv-se'] = 'Swedish';
$string['no-no'] = 'Norwegian';
$string['nb-no'] = 'Norwegian (Bokmål)';
$string['nn-no'] = 'Norwegian (Nynorsk)';
$string['pl-pl'] = 'Polish';
$string['ro-ro'] = 'Romanian';
$string['ro-ro'] = 'Romanian';
$string['mi-nz'] = 'Maori';
$string['bg-bg'] = 'Bulgarian';
$string['cs-cz'] = 'Czech';
$string['el-gr'] = 'Greek';
$string['hr-hr'] = 'Croatian';
$string['hu-hu'] = 'Hungarian';
$string['lt-lt'] = 'Lithuanian';
$string['lv-lv'] = 'Latvian';
$string['sk-sk'] = 'Slovak';
$string['sl-si'] = 'Slovenian';
$string['is-is'] = 'Icelandic';
$string['mk-mk'] = 'Macedonian';
$string['no-no'] = 'Norwegian';
$string['sr-rs'] = 'Serbian';
$string['vi-vn'] = 'Vietnamese';
$string['relevance'] = 'Relevance';
$string['relevanceanswer'] = 'Relevance Comparison Answer';
$string['relevance_setting'] = 'How to determine the relevance of the response to the question asked.';
$string['relevance_comparison'] = 'Relevance Comparison Answer';
$string['relevance_none'] = 'Relevance not considered';
$string['relevancetoqtext'] = 'Relevance to question';
$string['relevancetocomparison'] = 'Relevance to comparison answer';
$string['maxtime'] = 'Time limit';
$string['maxtime_setting'] = 'This is the default time limit for audio recording responses.';
$string['notimelimit'] = 'No time limit';
$string['xsecs'] = '{$a} seconds';
$string['onemin'] = '1 minute';
$string['xmins'] = '{$a} minutes';
$string['oneminxsecs'] = '1 minutes {$a} seconds';
$string['xminsecs'] = '{$a->minutes} minutes {$a->seconds} seconds';
$string['questionanswered'] = 'Question Answered';
$string['retry'] = 'Retry';
$string['currentwordcount'] = 'Total Words';
$string['submissionrelevance'] = 'Relevance: {$a}%';
$string['relevanceheader'] = 'Relevance';
$string['correctedtext'] = 'Corrections:';