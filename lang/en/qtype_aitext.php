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
 * Strings for component 'qtype_aitext', language 'en'.
 *
 * @package    qtype_aitext
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'AI text';
$string['pluginname_help'] = 'A question type using AI to provide feedback on responses';
$string['pluginname_link'] = 'question/type/aitext';
$string['pluginnameadding'] = 'Adding an AI text question';
$string['pluginnameediting'] = 'Editing an AI text question';
$string['pluginnamesummary'] = 'A question type using AI to provide feedback on responses';
$string['privacy:metadata'] = 'The AI text question type plugin does not store any personal data.';
$string['privacy:preference:defaultmark'] = 'The default mark setting for the question.';
$string['privacy:preference:penalty'] = 'The penalty for each incorrect try when using the adaptive behaviour.';
$string['privacy:preference:penaltyrole'] = 'The penalty role setting.';
$string['privacy:preference:hint'] = 'The hint settings.';
$string['privacy:preference:hintrole'] = 'The hint role setting.';
$string['privacy:preference:allowsubmissionsfromdatesummary'] = 'The date from which responses are allowed to be submitted.';
$string['privacy:preference:duedatesummary'] = 'The date when the response is due.';
$string['privacy:preference:graceperiod'] = 'The period during which responses can still be submitted after the due date.';
$string['privacy:preference:graceperiodclosed'] = 'The date after which responses can no longer be submitted.';
$string['privacy:preference:mark'] = 'The mark awarded for this question attempt.';
$string['privacy:preference:markrole'] = 'The mark role setting.';
$string['privacy:preference:markingworkflow'] = 'Whether marking workflow is enabled for this question.';
$string['privacy:preference:markingstate'] = 'The current state of marking for this question.';
$string['privacy:preference:maxmark'] = 'The maximum mark possible for this question attempt.';
$string['privacy:preference:modified'] = 'The date this attempt was last modified.';
$string['privacy:preference:original'] = 'The original question this attempt is based on.';
$string['privacy:preference:question'] = 'The ID of the question being attempted.';
$string['privacy:preference:slot'] = 'The slot number of this question attempt.';
$string['privacy:preference:sequencecheck'] = 'A check value to ensure data integrity.';
$string['privacy:preference:started'] = 'The date this attempt was started.';
$string['privacy:preference:state'] = 'The current state of this question attempt.';
$string['privacy:preference:timemodified'] = 'The date this attempt was last modified.';
$string['privacy:preference:timecreated'] = 'The date this attempt was created.';
$string['privacy:preference:userid'] = 'The ID of the user who created this attempt.';
$string['privacy:preference:variant'] = 'The variant of this question attempt.';

$string['backends'] = 'AI Backend';
$string['backends_text'] = 'Choose which AI system to use';
$string['coreaisubsystem'] = 'Core AI subsystem';
$string['defaultprompt'] = 'Default prompt';
$string['defaultprompt_setting'] = 'Default prompt for all AI text questions';
$string['thedefaultprompt'] = 'Analyse the part delimited by double brackets without mentioning the brackets as follows';
$string['defaultmarksscheme'] = 'Default marks scheme';
$string['defaultmarksscheme_setting'] = 'Default marks scheme for all AI text questions';
$string['thedefaultmarksscheme'] = 'Return only a JSON object which enumerates a set of 2 elements. The JSON object should be in this format: {"feedback":"string","marks":"number" where marks is a single number summing all marks. Also show the marks as part of the feedback.';
$string['disclaimer'] = 'Disclaimer';
$string['disclaimer_setting'] = 'Disclaimer text to be displayed with AI responses';
$string['prompt'] = 'Prompt';
$string['prompt_setting'] = 'Prompt format';
$string['jsonprompt'] = 'JSON prompt';
$string['jsonprompt_setting'] = 'JSON prompt format';
$string['responseformat'] = 'Response format';
$string['responseformat_setting'] = 'Format of the response field';
$string['localaimanager'] = 'Local AI manager';
$string['toolaimanager'] = 'Tool AI manager';
$string['markprompt_required'] = 'Marks prompt required';
$string['markprompt_required_setting'] = 'Whether the marks prompt is required';
$string['translatepostfix'] = 'Translate postfix';
$string['translatepostfix_text'] = 'Whether to translate the postfix';
$string['expertmode'] = 'Expert mode';
$string['expertmode_setting'] = 'Enable expert mode';
$string['download_diagnostics'] = 'Download Diagnostics Report';
$string['diagnostics'] = 'Diagnostics';