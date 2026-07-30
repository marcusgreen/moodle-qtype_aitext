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
 * Take the sample response and make an AJAX request to the LLM.
 *
 * @module     qtype_aitext/responserun
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings} from 'core/str';
import Ajax from 'core/ajax';
import Notify from 'core/notification';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';

/**
 * Give the raw evaluation div for a row a unique id derived from its index.
 *
 * The repeated HTML element hardcodes id="id_sampleresponseeval" (no suffix),
 * so it is duplicated for every sample. Rename by the nearest following div.
 *
 * @param {number|string} idx the row index
 */
export const renameEvalDiv = (idx) => {
    const textarea = document.getElementById('id_sampleresponses_' + idx);
    if (!textarea) {
        return;
    }
    const evals = Array.from(document.querySelectorAll('[id="id_sampleresponseeval"]'));
    for (const candidate of evals) {
        const position = textarea.compareDocumentPosition(candidate);
        // eslint-disable-next-line no-bitwise
        if (position & Node.DOCUMENT_POSITION_FOLLOWING) {
            candidate.id = 'id_responseeval_' + idx;
            return;
        }
    }
};

/**
 * Attach the "evaluate sample response" click handler to a single row.
 *
 * Safe to call more than once for a row; the handler is only bound once.
 *
 * @param {number} contextid
 * @param {number|string} idx the row index
 */
export const setupSampleButton = (contextid, idx) => {
    const btn = document.getElementById('id_sampleresponsebtn_' + idx);
    if (!btn || btn.dataset.aitextBound) {
        return;
    }
    btn.dataset.aitextBound = '1';

    const strings = {};
    get_strings([
        {key: 'responsetester', component: 'qtype_aitext'},
        {key: 'sampleresponseempty', component: 'qtype_aitext'},
        {key: 'loading', component: 'moodle'},
    ]).done(function(s) {
        strings.responsetester = s[0];
        strings.sampleresponseempty = s[1];
        strings.loading = s[2];
    });

    btn.addEventListener('click', () => {
        const sampleresponse = document.getElementById('id_sampleresponses_' + idx);
        const sampleresponseeval = document.getElementById('id_responseeval_' + idx);
        const aiprompt = document.getElementById('id_aiprompt');
        const marksscheme = document.getElementById('id_markscheme');
        const defaultmark = document.getElementById('id_defaultmark');
        // Include current question text for AI grading if referenced in prompt.
        const questiontextElem = document.getElementById('id_questiontext');
        const questiontext = questiontextElem ? questiontextElem.value : '';

        const spinnerOuter = document.querySelector('#fitem_id_spinner_' + idx);
        const spinner = spinnerOuter ? spinnerOuter.querySelector('#id_spinner') : null;

        if (sampleresponse.value === "" || aiprompt.value === "") {
            Notify.alert(strings.responsetester, strings.sampleresponseempty);
            return;
        }
        // Put spinner in place.
        if (spinner) {
            spinner.innerHTML = '<span class="loading-icon icon-no-margin">' +
                '<i class="fa fa-spinner fa-spin fa-3x fa-fw" title="' + strings.loading +
                '" role="img" aria-label="' + strings.loading + '"></i></span>';
            spinner.classList.remove('hide');
        }

        // Strip the {{purpose}} annotation so only the real answer is graded.
        const responsetext = sampleresponse.value.replace(/\{\{[^}]*\}\}/g, '').replace(/^\s+/, '');

        Ajax.call([{
            methodname: 'qtype_aitext_fetch_ai_grade',
            args: {
                response: responsetext,
                defaultmark: defaultmark.value,
                prompt: aiprompt.value,
                marksscheme: marksscheme.value,
                questiontext: questiontext,
                contextid: contextid
            },
            async: false
        }])[0].then(function(airesponse) {
            Log.debug(airesponse);
            if (airesponse.feedback) {
                // Render HTML so tags are not escaped.
                sampleresponseeval.innerHTML = airesponse.feedback +
                    ' (GRADE: ' + airesponse.marks + '/' + defaultmark.value + ')';
            }
            if (spinner) {
                spinner.classList.add('hide');
            }
            return true;
        }).fail(error => {
            displayException(error);
            if (spinner) {
                spinner.innerHTML = '';
            }
            sampleresponseeval.innerHTML = '';
        });
    });
};

export const init = (contextid) => {
    const textareas = Array.from(document.querySelectorAll("[id^='id_sampleresponses_']"));
    Log.debug('Found ' + textareas.length + ' sampleresponses textareas');

    textareas.forEach((textarea) => {
        const match = textarea.id.match(/_(\d+)$/);
        if (match) {
            renameEvalDiv(match[1]);
        }
    });

    const buttons = Array.from(document.querySelectorAll("[id^='id_sampleresponsebtn_']"));
    buttons.forEach((btn) => {
        const match = btn.id.match(/_(\d+)$/);
        if (match) {
            setupSampleButton(contextid, match[1]);
        }
    });
};
