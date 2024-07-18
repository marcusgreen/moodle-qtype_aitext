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
 * Aitext services definition
 *
 * @package    qtype_aitext
 * @copyright  Justin Hunt - poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * External class.
 *
 * @package qtype_aitext
 * @author  Justin Hunt - poodll.com
 */
defined('MOODLE_INTERNAL') || die();

$functions = [
        'qtype_aitext_fetch_ai_grade' => [
                'classname'   => 'qtype_aitext_external',
                'methodname'  => 'fetch_ai_grade',
                'description' => 'checks a response with the AI grader' ,
                'capabilities' => 'mod/quiz:grade',
                'type'        => 'read',
                'ajax'        => true,
        ],
];
