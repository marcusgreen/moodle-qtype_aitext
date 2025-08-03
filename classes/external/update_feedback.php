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

namespace qtype_aitext\external;


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

use local_ai_manager\manager;

/**
 * External class.
 *
 * @package qtype_aitext
 * @author  Justin Hunt - poodll.com
 */
class qtype_aitext_external extends external_api {
    /**
     * Get the parameters and types
     *
     * @return void
     */
    public static function fetch_add_attempt_step_feedback_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
            'questionattemptid' => new \external_value(PARAM_INT, 'The ID of the question attempt'),
            'data' => new \external_value(PARAM_RAW, 'Optional JSON encoded data for the step.', VALUE_DEFAULT, null),
            ]
        );
    }
}
