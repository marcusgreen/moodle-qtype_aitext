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

import {
    get_strings
} from 'core/str';
import Ajax from 'core/ajax';
import Notify from 'core/notification';
import Log from 'core/log';
import {
    exception as displayException
} from 'core/notification';


export const init = (contextid) => {

    const Selectors = {
        fields: {
            sampleresponse: '#id_sampleresponse',
            sampleresponsebtn: '#id_sampleresponsebtn',
            sampleresponseeval: '#id_sampleresponseeval',
        },
    };
    // Normalize evaluation container IDs by deriving the index from the nearest sampleresponses textarea.
    // Note: The hardcoded HTML div has id="id_sampleresponseeval" (no suffix), repeated for each sample.
    const allResponses = Array.from(document.querySelectorAll("[id^='id_sampleresponses_']"));
    Log.debug('Found ' + allResponses.length + ' sampleresponses textareas');

    allResponses.forEach((responseTextarea) => {
        // Extract the index from the textarea ID (e.g., "id_sampleresponses_0" -> "0").
        const match = responseTextarea.id.match(/_(\d+)$/);
        if (!match) {
            Log.debug('No match for textarea: ' + responseTextarea.id);
            return;
        }
        const idx = match[1];
        Log.debug('Processing textarea index: ' + idx);

        // Find all eval divs with the base id.
        const allEvals = Array.from(document.querySelectorAll('[id="id_sampleresponseeval"]'));
        Log.debug('Found ' + allEvals.length + ' eval divs with base id');

        // Find the nearest following eval div (should be in the same repeated group).
        let evalEl = null;
        for (const candidate of allEvals) {
            if (responseTextarea.compareDocumentPosition(candidate) & Node.DOCUMENT_POSITION_FOLLOWING) {
                evalEl = candidate;
                Log.debug('Found matching eval div for index ' + idx);
                break;
            }
        }

        if (evalEl) {
            const newId = 'id_responseeval_' + idx;
            Log.debug('Renaming eval div to: ' + newId);
            evalEl.id = newId;
        } else {
            Log.debug('No eval div found for index ' + idx);
        }
    });
    let elementcount = document.querySelectorAll("[id^='id_sampleresponsebtn']");
    let SelectorsWithCount = {};

    for (let i = 0; i < elementcount.length; i++) {
        SelectorsWithCount.fields = {};
        for (let key in Selectors.fields) {
            SelectorsWithCount.fields[key] = Selectors.fields[key] + "_" + i;
            SelectorsWithCount.fields.aiprompt = '#id_aiprompt';
            SelectorsWithCount.fields.markscheme = '#id_markscheme';
            SelectorsWithCount.fields.defaultmark = '#id_defaultmark';
        }
        clickSetup(contextid, SelectorsWithCount);
    }

};

/**
 * Configure event handlers
 *
 * @param {number} contextid
 * @param {object} Selectors
 */
function clickSetup(contextid, Selectors) {
    // Set up strings
    var strings = {};
    get_strings([{
            "key": "responsetester",
            "component": 'qtype_aitext'
        },
            {
            "key": "sampleresponseempty",
            "component": 'qtype_aitext'
        },
        {
            "key": "loading",
            "component": 'moodle'
        },

    ]).done(function(s) {
        var i = 0;
        strings.responsetester = s[i++];
        strings.sampleresponseempty = s[i++];
        strings.loading = s[i++];
    });
    document.querySelector(Selectors.fields.sampleresponsebtn).addEventListener('click', e => {
        let index = e.target.id.lastIndexOf("_");
        let id = e.target.id.slice(index + 1);

        const sampleresponse = document.getElementById('id_sampleresponses' + '_' + id);
        const sampleresponseeval = document.getElementById('id_responseeval_' + id);

        const aiprompt = document.getElementById('id_aiprompt');
        const marksscheme = document.getElementById('id_markscheme');
        const defaultmark = document.getElementById('id_defaultmark');
        // Include current question text for AI grading if referenced in prompt.
        const questiontextElem = document.getElementById('id_questiontext');
        const questiontext = questiontextElem ? questiontextElem.value : '';

        const spinnerOuter = document.querySelector('#fitem_id_spinner_' + id);
        const spinner = spinnerOuter.querySelector('#id_spinner');

        if (sampleresponse.value === "" || aiprompt.value === "") {
            Notify.alert(strings.responsetester, strings.sampleresponseempty);
            return;
        }
        // Put  spinner in place.
        spinner.innerHTML = '<span class="loading-icon icon-no-margin">';
        spinner.innerHTML += ' <i class="fa fa-spinner fa-spin fa-3x fa-fw"" title="' + strings.loading +
            '" role="img" aria-label="' + strings.loading + '"></i>';
        spinner.innerHTML += '</span>';

        spinner.classList.remove('hide');
        Ajax.call([{
            methodname: 'qtype_aitext_fetch_ai_grade',
            args: {
                response: sampleresponse.value,
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
                spinner.classList.add('hide');
            }
            return true;
        }).fail(error => {
            displayException(error);
            sampleresponseeval.innerHTML = '';
        });

    }); // End of click.

}
