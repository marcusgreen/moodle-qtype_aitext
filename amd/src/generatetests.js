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
 * Ask the AI to generate a spread of varied-quality test responses and drop
 * them into the repeated sample response fields, off-topic first, cloning
 * extra rows as needed just as a human would by hand.
 *
 * @module     qtype_aitext/generatetests
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings} from 'core/str';
import Ajax from 'core/ajax';
import Notify from 'core/notification';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';
import {setupSampleButton} from 'qtype_aitext/responserun';

/**
 * Collect the top-level DOM nodes that make up one repeated sample-response row.
 * A row runs from its spinner fitem up to and including its trailing <hr>.
 *
 * @param {number|string} idx the row index
 * @return {Element[]} the row's top-level nodes
 */
const getRowNodes = (idx) => {
    const nodes = [];
    let node = document.getElementById('fitem_id_spinner_' + idx);
    while (node) {
        nodes.push(node);
        if (node.tagName === 'HR') {
            break;
        }
        node = node.nextElementSibling;
    }
    return nodes;
};

/**
 * Re-index every id, name and label target inside a cloned row so it belongs
 * to the destination row rather than the template row (index 0).
 *
 * @param {Element} node a cloned node (the row template is always row 0)
 * @param {number} newIdx the destination row index
 */
const reindexNode = (node, newIdx) => {
    const elements = [node, ...node.querySelectorAll('*')];
    elements.forEach((el) => {
        if (el.id) {
            el.id = el.id.replace(/_0$/, '_' + newIdx);
        }
        if (el.name) {
            el.name = el.name.replace(/\[0\]$/, '[' + newIdx + ']');
        }
        const forAttr = el.getAttribute && el.getAttribute('for');
        if (forAttr) {
            el.setAttribute('for', forAttr.replace(/_0$/, '_' + newIdx));
        }
        // Row 0's button already carries the "handler bound" flag; clear it on
        // the clone so setupSampleButton will bind a handler to the new row.
        if (el.dataset && el.dataset.aitextBound) {
            delete el.dataset.aitextBound;
        }
    });
};

/**
 * Clone the row-0 template into a fresh row at the given index, inserting it
 * after the supplied node.
 *
 * @param {number} idx the destination row index
 * @param {Element} insertAfter the node the new row is inserted after
 * @return {Element} the last inserted node (for chaining further inserts)
 */
const cloneRow = (idx, insertAfter) => {
    const template = getRowNodes(0);
    let anchor = insertAfter;
    template.forEach((tn) => {
        const clone = tn.cloneNode(true);
        reindexNode(clone, idx);
        anchor.after(clone);
        anchor = clone;
    });
    return anchor;
};

/**
 * Put text into a sample-response row and clear any stale evaluation output.
 *
 * @param {number} idx the row index
 * @param {string} text the response body
 */
const fillRow = (idx, text) => {
    const textarea = document.getElementById('id_sampleresponses_' + idx)
        || document.querySelector('textarea[name="sampleresponses[' + idx + ']"]');
    if (textarea) {
        textarea.value = text;
    } else {
        Log.debug('generatetests: no sample textarea found for row ' + idx);
    }
    const evalDiv = document.getElementById('id_responseeval_' + idx);
    if (evalDiv) {
        evalDiv.innerHTML = '';
    }
    const spinnerOuter = document.querySelector('#fitem_id_spinner_' + idx);
    const spinner = spinnerOuter ? spinnerOuter.querySelector('#id_spinner') : null;
    if (spinner) {
        spinner.innerHTML = '';
    }
};

/**
 * Highest existing sample-response row index in the form.
 *
 * @return {number} the highest index, or -1 when there are no rows
 */
const highestRowIndex = () => {
    const textareas = document.querySelectorAll('textarea[name^="sampleresponses["]');
    const indexes = Array.from(textareas)
        .map((el) => el.name.match(/\[(\d+)\]/))
        .filter((match) => match)
        .map((match) => parseInt(match[1], 10));
    return indexes.length ? Math.max(...indexes) : -1;
};

export const init = (contextid) => {
    const btn = document.getElementById('id_gentestresponsesbtn');
    if (!btn) {
        return;
    }
    const status = document.getElementById('id_gentestresponsesstatus');

    const strings = {};
    get_strings([
        {key: 'responsetester', component: 'qtype_aitext'},
        {key: 'gentestresponsesempty', component: 'qtype_aitext'},
        {key: 'loading', component: 'moodle'},
    ]).done(function(s) {
        strings.responsetester = s[0];
        strings.gentestresponsesempty = s[1];
        strings.loading = s[2];
    });

    btn.addEventListener('click', () => {
        const questiontextElem = document.getElementById('id_questiontext');
        const questiontext = questiontextElem ? questiontextElem.value : '';
        const aiprompt = document.getElementById('id_aiprompt');
        const marksscheme = document.getElementById('id_markscheme');
        const defaultmark = document.getElementById('id_defaultmark');
        const numbox = document.getElementById('id_numtestresponses');
        let numresponses = numbox ? parseInt(numbox.value, 10) : 4;
        if (!numresponses || numresponses < 1) {
            numresponses = 4;
        }

        if (!aiprompt || aiprompt.value === '') {
            Notify.alert(strings.responsetester, strings.gentestresponsesempty);
            return;
        }

        if (status) {
            status.innerHTML = '<span class="loading-icon icon-no-margin">' +
                '<i class="fa fa-spinner fa-spin fa-2x fa-fw" title="' + strings.loading +
                '" role="img" aria-label="' + strings.loading + '"></i></span>';
        }

        Ajax.call([{
            methodname: 'qtype_aitext_generate_test_responses',
            args: {
                questiontext: questiontext,
                prompt: aiprompt.value,
                marksscheme: marksscheme ? marksscheme.value : '',
                defaultmark: defaultmark ? defaultmark.value : 0,
                contextid: contextid,
                numresponses: numresponses,
            },
        }])[0].then(function(response) {
            Log.debug(response);
            const responses = response.responses || [];
            if (response.status && responses.length) {
                populateRows(contextid, responses);
            }
            if (status) {
                const cls = response.status ? 'text-success' : 'text-danger';
                status.innerHTML = '<span class="' + cls + '">' + response.message + '</span>';
            }
            return true;
        }).fail(error => {
            if (status) {
                status.innerHTML = '';
            }
            displayException(error);
        });
    });
};

/**
 * Fill the sample-response rows with the generated responses (off-topic first),
 * cloning extra rows and wiring up their Evaluate buttons as needed.
 *
 * @param {number} contextid
 * @param {string[]} responses the generated response bodies
 */
const populateRows = (contextid, responses) => {
    let maxIdx = highestRowIndex();
    Log.debug('generatetests: ' + responses.length + ' responses, highest existing row ' + maxIdx);
    if (maxIdx < 0) {
        return;
    }

    // Grow the form to hold one row per response.
    let insertAfter = getRowNodes(maxIdx).slice(-1)[0];
    for (let idx = maxIdx + 1; idx < responses.length; idx++) {
        insertAfter = cloneRow(idx, insertAfter);
        setupSampleButton(contextid, idx);
    }

    // Keep the repeat counter in step so the extra rows survive form submission.
    // The hidden field is read server-side to decide how many rows to expand; if
    // it is not bumped the cloned rows are ignored and only row 0 is saved.
    const counter = document.getElementById('id_option_repeats')
        || document.querySelector('input[name="option_repeats"]');
    if (counter) {
        const total = Math.max(maxIdx + 1, responses.length);
        if (parseInt(counter.value, 10) < total) {
            counter.value = total;
        }
    }

    responses.forEach((text, idx) => fillRow(idx, text));
};
