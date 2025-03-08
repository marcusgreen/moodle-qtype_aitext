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

const Selectors = {
    fields: {
        sampleanswer: '#id_sampleanswer',
        sampleanswerbtn: '#id_sampleanswerbtn',
        sampleanswereval: '#id_sampleanswereval',
        spinner: '#id_spinner',
        aiprompt: '#id_aiprompt',
        markscheme: '#id_markscheme',
        defaultmark: '#id_defaultmark',
    },
};

export const init = (contextid) => {

    // Set up strings
    var strings={};
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
        const sampleanswer = form.querySelector(Selectors.fields.sampleanswer);
        const sampleanswereval = form.querySelector(Selectors.fields.sampleanswereval);

        const aiprompt = form.querySelector(Selectors.fields.aiprompt);
        const marksscheme = form.querySelector(Selectors.fields.markscheme);
        const defaultmark = form.querySelector(Selectors.fields.defaultmark);
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

};
