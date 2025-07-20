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
 * Mobile app areas for AI Text
 *
 * Documentation: {@link https://moodledev.io/general/app/development/plugins-development-guide}
 *
 * @package    qtype_aitext
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    "qtype_aitext" => [
        "handlers" => [ // Different places where the add-on will display content.
            'aitext' => [ // Handler unique name (can be anything).
                'displaydata' => [
                    'title' => 'AIText question',
                    'icon' => '/question/type/aitext/pix/icon.gif',
                    'class' => '',
                ],
                'delegate' => 'CoreQuestionDelegate', // Delegate (where to display the link to the add-on).
                'method' => 'mobile_get_aitext',
                'offlinefunctions' => [
                    'mobile_get_aitext' => [], // Function in classes/output/mobile.php.
                ], // Function needs caching for offline.
                'styles' => [
                    'url' => $CFG->wwwroot.'/question/type/aitext/mobile/qtype_aitext_app.css',
                    'version' => '0.1',
                ],
            ],
        ],
        'lang' => [
                    ['pluginname', 'qtype_aitext'], // Matching value in  lang/en/qtype_gapfill.
        ],
    ],
];
