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
 * Display a button in testing to reveal the prompt that was sent
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


export const init = (contextid) => {

    const Selectors = {
        fields: {
            sampleanswer: '#id_sampleanswer',
            sampleanswerbtn: '#id_sampleanswerbtn',
            sampleanswereval: '#id_sampleanswereval',
        },
    };
    let elementcount = document.querySelectorAll("[id^='id_sampleanswerbtn']");
    let SelectorsWithCount = {};

    for (let i = 0; i < elementcount.length; i++) {
        SelectorsWithCount.fields = {};
        for (let key in Selectors.fields) {
            SelectorsWithCount.fields[key] = Selectors.fields[key] + "_" + i;
            SelectorsWithCount.fields.aiprompt = '#id_aiprompt';
            SelectorsWithCount.fields.markscheme = '#id_markscheme';
            SelectorsWithCount.fields.defaultmark = '#id_defaultmark';
            SelectorsWithCount.fields.spinner = '#id_spinner';
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
    get_strings([
        {"key": "prompttester", "component": 'qtype_aitext'},
        {"key": "sampleanswerempty", "component": 'qtype_aitext'},

    ]).done(function (s) {
        var i = 0;
        strings.prompttester = s[i++];
        strings.sampleanswerempty = s[i++];
    });
    document.querySelector(Selectors.fields.sampleanswerbtn).addEventListener('click', e => {
        const form = e.target.closest('form');
        let index = e.target.id.lastIndexOf("_");
        let id = e.target.id.slice(index + 1);

        const sampleanswer = document.getElementById('id_sampleanswers' + '_'+ id);
        const sampleanswereval = document.getElementById('id_sampleanswereval' + "_" + id);

        const aiprompt = document.getElementById('id_aiprompt');
        const marksscheme = document.getElementById('id_markscheme');
        const defaultmark = document.getElementById('id_defaultmark');
        const spinner = form.querySelector(Selectors.fields.spinner);

        if (sampleanswer.value === "" || aiprompt.value === "") {
            Notify.alert(strings.prompttester, strings.sampleanswerempty);
            return;
        }
     // Put  spinner in place.
     spinner.innerHTML = '<span class="loading-icon icon-no-margin">';
     spinner.innerHTML += ' <i class="fa fa-spinner fa-spin fa-3x fa-fw"" title="Loading" role="img" aria-label="Loading"></i>';
     spinner.innerHTML += '</span>';

     spinner.classList.remove('hide');
     Ajax.call([{
        methodname: 'qtype_aitext_fetch_ai_grade',
        args: {
            response: sampleanswer.value,
            defaultmark: defaultmark.value,
            prompt: aiprompt.value,
            marksscheme: marksscheme.value,
            contextid: contextid
        },
        async: false
    }])[0].then(function(airesponse) {
        Log.debug(airesponse);
        if (airesponse.feedback) {
            sampleanswereval.textContent = airesponse.feedback + ' (GRADE: ' + airesponse.marks + '/' + defaultmark.value + ')';
            spinner.classList.add('hide');
        }
        return true;
    }).fail(error => {
        displayException(error);
        sampleanswereval.innerHTML = '';
    });

}); // End of click.

}
