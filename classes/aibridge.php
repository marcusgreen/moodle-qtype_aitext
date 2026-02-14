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

namespace qtype_aitext;

/**
 * AI Bridge class for handling LLM requests through different backends.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aibridge {
    /** @var int The context ID for AI requests */
    private $contextid;

    /**
     * Constructor for the AI bridge class.
     *
     * @param int $contextid The context ID for AI requests
     */
    public function __construct(int $contextid) {
        $this->contextid = $contextid;
    }

    /**
     * Call the llm using either the 4.5 core api or the backend provided by
     * local_ai_manager (mebis) or tool_aimanager
     *
     * @param string $prompt
     * @param string $purpose
     * @return string The AI response content
     * @throws \moodle_exception If there's an error retrieving feedback or invalid backend
     */
    public function perform_request(string $prompt, string $purpose = 'feedback'): string {
        if (defined('BEHAT_SITE_RUNNING') || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            return "AI Feedback";
        }
        $backend = get_config('qtype_aitext', 'backend');
        if ($backend == 'local_ai_manager') {
            $manager = new \local_ai_manager\manager($purpose);
            $llmresponse = (object) $manager->perform_request($prompt, 'qtype_aitext', $this->contextid);
            if ($llmresponse->get_code() !== 200) {
                throw new \moodle_exception(
                    'err_retrievingfeedback',
                    'qtype_aitext',
                    '',
                    $llmresponse->get_errormessage(),
                    $llmresponse->get_debuginfo()
                );
            }
            return $llmresponse->get_content();
        } else if ($backend == 'core_ai_subsystem') {
            global $USER;
            $action = new \core_ai\aiactions\generate_text(
                contextid: $this->contextid,
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
                    throw new \moodle_exception('err_retrievingfeedback_checkconfig', 'qtype_aitext');
                } else {
                    throw new \moodle_exception('err_retrievingfeedback', 'qtype_aitext');
                }
            }
            return $responsedata['generatedcontent'];
        } else if ($backend == 'tool_aimanager') {
            if (class_exists('\tool_aiconnect\ai\ai')) {
                $ai = new \tool_aiconnect\ai\ai();
                $llmresponse = $ai->prompt_completion($prompt);
                return $llmresponse['response']['choices'][0]['message']['content'];
            } else {
                throw new \moodle_exception('err_retrievingfeedback_checkconfig', 'qtype_aitext', '');
            }
        }
        throw new \moodle_exception('err_invalidbackend', 'qtype_aitext');
    }
}