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
 * Default settings for the aitext question type
 *
 * @package    qtype_aitext
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtextarea('qtype_aitext/defaultprompt',
        new lang_string('defaultprompt', 'qtype_aitext'),
        new lang_string('defaultprompt_setting', 'qtype_aitext'),
        new lang_string('thedefaultprompt', 'qtype_aitext')));

    $settings->add(new admin_setting_configtextarea('qtype_aitext/defaultmarksscheme',
        new lang_string('defaultmarksscheme', 'qtype_aitext'),
        new lang_string('defaultmarksscheme_setting', 'qtype_aitext'),
        new lang_string('thedefaultmarksscheme', 'qtype_aitext')));
    $settings->add(new admin_setting_configtext(
        'qtype_aitext/disclaimer',
        new lang_string('disclaimer', 'qtype_aitext'),
        new lang_string('disclaimer_setting', 'qtype_aitext'),
        '(Response provided by an AI System)'
        ));
    $settings->add(new admin_setting_configtextarea(
        'qtype_aitext/prompt',
        new lang_string('prompt', 'qtype_aitext'),
        new lang_string('prompt_setting', 'qtype_aitext'),
        'in [responsetext] analyse but do not mention the part between [[ and ]] as follows:',
        PARAM_RAW,
        20,
        3
    ));
    $settings->add(new admin_setting_configtextarea(
        'qtype_aitext/jsonprompt',
        new lang_string('jsonprompt', 'qtype_aitext'),
        new lang_string('jsonprompt_setting', 'qtype_aitext'),
        'Return only a JSON object which enumerates a set of 2 elements.The JSON object should be in
	this format: {feedback":"string","marks":"number"} where marks is a single number summing all marks.
   	Also show the marks as part of the feedback.',
        PARAM_RAW,
        20,
        6
    ));
    $settings->add(new admin_setting_configselect(
        'qtype_aitext/responseformat',
        new lang_string('responseformat', 'qtype_aitext'),
        new lang_string('responseformat_setting', 'qtype_aitext'),
        0, ['plain' => 'plain', 'editor' => 'editor', 'monospaced' => 'monospaced']
    ));
    $settings->add(new admin_setting_configcheckbox(
        'qtype_aitext/uselocalaimanager',
        new lang_string('use_local_ai_manager', 'qtype_aitext'),
        new lang_string('use_local_ai_manager_setting', 'qtype_aitext'),
        0
    ));

}

