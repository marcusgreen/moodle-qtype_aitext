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
 * Expert mode template insertion for AI Text question type.
 *
 * @module     qtype_aitext/expertmode
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string} from 'core/str';
import Notification from 'core/notification';

/**
 * Initialize the expert mode template button.
 *
 * @param {string} template The expert mode template to insert.
 */
export const init = (template) => {
    const button = document.getElementById('id_expertmodetemplatebtn');
    const aipromptTextarea = document.getElementById('id_aiprompt');

    if (!button || !aipromptTextarea) {
        return;
    }

    button.addEventListener('click', async(e) => {
        e.preventDefault();

        // Check if the textarea already has content.
        const currentValue = aipromptTextarea.value.trim();

        if (currentValue) {
            // Ask for confirmation before replacing existing content.
            const confirmMessage = await get_string('expertmodeconfirm', 'qtype_aitext');

            Notification.confirm(
                await get_string('useexpertmodetemplate', 'qtype_aitext'),
                confirmMessage,
                await get_string('yes', 'core'),
                await get_string('no', 'core'),
                () => {
                    insertTemplate(aipromptTextarea, template);
                }
            );
        } else {
            insertTemplate(aipromptTextarea, template);
        }
    });
};

/**
 * Insert the template into the textarea.
 *
 * @param {HTMLTextAreaElement} textarea The textarea element.
 * @param {string} template The template to insert.
 */
const insertTemplate = (textarea, template) => {
    textarea.value = template;
    // Trigger input event so any listeners are notified.
    textarea.dispatchEvent(new Event('input', {bubbles: true}));
    textarea.dispatchEvent(new Event('change', {bubbles: true}));
    // Focus the textarea.
    textarea.focus();
};
