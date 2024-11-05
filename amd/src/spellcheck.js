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
 * Generates the spellcheck diff view.
 *
 * @module     qtype_aitext/spellcheck
 * @copyright  2024, ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Diff from 'qtype_aitext/diff';
import ModalForm from 'core_form/modalform';
import {getString as getString} from 'core/str';

/**
 * Init the module.
 *
 * @param {int} cmid the course module id of the quiz.
 * @param {string} readonlyareaselector the selector for the readonly area to apply the spellchecking
 * @param {string} spellcheckeditbuttonselector the selector for the spell check edit button
 */
export const init = (cmid, readonlyareaselector, spellcheckeditbuttonselector) => {
    renderDiff(readonlyareaselector);

    if (!document.querySelector(spellcheckeditbuttonselector)) {
        return;
    }
    document.querySelector(spellcheckeditbuttonselector).addEventListener('click',
        async(event) => {
            event.preventDefault();
            await showModalForm(cmid, readonlyareaselector);
        });
};

/**
 * Render the spell check highlighting.
 *
 * @param {string} readonlyareaselector the selector for the readonly area to apply the spell check diff to
 */
export const renderDiff = (readonlyareaselector) => {
    const studentanswer = document.querySelector(readonlyareaselector).innerHTML;
    const spellcheck = document.querySelector(readonlyareaselector).dataset.spellcheck;
    let span = null;

    const diff = Diff.diffChars(studentanswer, spellcheck);
    const fragment = document.createElement('div');

    let fullspellcheck = '';

    diff.forEach(part => {
        // We need to replace the whitespaces, because otherwise they will be removed by
        // calling parseFromString of the DOMParser.
        part.value = part.value.replace(/ /g, '&nbsp;');
        const parser = new DOMParser();
        part.value = parser.parseFromString(part.value, 'text/html');
        const cls = part.added ? 'qtype_aitext_spellcheck_new' :
            part.removed ? 'qtype_aitext_spellcheck_wrong' : '';
        if (part.added || part.removed) {
            span = document.createElement('span');
            span.classList = cls;
            span.appendChild(part.value.documentElement);
            fullspellcheck += span.outerHTML;
        } else {
            fullspellcheck += part.value.documentElement.textContent;
        }
    });

    fragment.innerHTML = fullspellcheck;
    document.querySelector(readonlyareaselector).replaceChildren(fragment);
};

/**
 * Show the dynamic spellcheck form.
 *
 * @param {int} cmid the course module id of the quiz
 * @param {string} readonlyareaselector the selector for the readonly area
 */
export const showModalForm = async(cmid, readonlyareaselector) => {
    const attemptstepid = document.querySelector(readonlyareaselector).dataset.spellcheckattemptstepid;
    const answerstepid = document.querySelector(readonlyareaselector).dataset.spellcheckattemptstepanswerid;
    const title = await getString('spellcheckedit', 'qtype_aitext');
    const modalForm = new ModalForm({
        formClass: "qtype_aitext\\form\\edit_spellchek",
        args: {
            attemptstepid,
            answerstepid,
            cmid
        },
        modalConfig: {title},
    });
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, reloadpage);
    await modalForm.show();
};

/**
 * Reload the page.
 *
 * This is not nice, but easy :-) .
 */
const reloadpage = () => {
    location.reload();
};
