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
 * @module     qtype_aitext/getSpellcheck
 * @copyright  2024, ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Diff from "qtype_aitext/diff";
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';

/**
 * Init
 */
export const init = () => {
    renderDiff();

    if (!document.getElementById('aitext_spellcheckedit')) {
        return;
    }
    document.getElementById('aitext_spellcheckedit').addEventListener("click", showModalForm);
};

/**
 * Render the spellcheckdiff
 */
export const renderDiff = () => {
    var studentanswer = document.getElementById('aitext_readonly_area').innerHTML,
        spellcheck = document.getElementById('aitext_readonly_area').dataset.spellcheck,
        span = null;

    var diff = Diff.diffChars(studentanswer, spellcheck);
    var fragment = document.createElement('div');

    let fullspellcheck = '';

    diff.forEach(function (part) {
        var cls = part.added ? 'qtype_aitext_spellcheck_new' :
            part.removed ? 'qtype_aitext_spellcheck_wrong' : '';
        if (part.added || part.removed) {
            span = document.createElement('span');
            span.classList = cls;
            span.appendChild(document.createTextNode(part.value));
            fullspellcheck += span.outerHTML;
        } else {
            fullspellcheck += part.value;
        }
    });

    fragment.innerHTML = fullspellcheck;
    document.getElementById('aitext_readonly_area').replaceChildren(fragment);
};

/**
 * Show the dynamic spellcheck form.
 */
export const showModalForm = () => {
    const attemptstepid = document.getElementById('aitext_readonly_area').dataset.spellcheckattemptstepid;
    const answerstepid = document.getElementById('aitext_readonly_area').dataset.spellcheckattemptstepanswerid;
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const modalForm = new ModalForm({
        formClass: "qtype_aitext\\form\\edit_spellchek",
        args: {
            attemptstepid: attemptstepid,
            answerstepid: answerstepid,
            cmid: urlParams.get('cmid'),
        },
        modalConfig: { title: getString('spellcheckedit', 'qtype_aitext') },
    });
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, reloadpage);
    modalForm.show();
};

/**
 * Reload the page. This is not nice, but easy :-)
 */
const reloadpage = () => {
    location.reload();
}
