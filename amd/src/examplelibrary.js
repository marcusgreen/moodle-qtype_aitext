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
 * Example library: load the preview of the chosen example in place instead of reloading the page.
 *
 * @module     qtype_aitext/examplelibrary
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Fragment from 'core/fragment';
import Templates from 'core/templates';
import Notification from 'core/notification';

/**
 * Mark the chosen example as selected in the list.
 *
 * @param {HTMLElement} root library region
 * @param {HTMLElement} link chosen example link
 */
const markSelected = (root, link) => {
    root.querySelectorAll('[data-action="preview"]').forEach((item) => {
        item.classList.remove('active');
        item.removeAttribute('aria-current');
    });
    link.classList.add('active');
    link.setAttribute('aria-current', 'true');
};

/**
 * Point the import form at the chosen example and enable its buttons.
 *
 * The prefill button is only enabled when the preview says the example can prefill a new question.
 *
 * @param {HTMLElement} root library region
 * @param {string} path example path
 */
const updateForm = (root, path) => {
    const form = root.querySelector('.qtype_aitext_examples-import form');
    const preview = root.querySelector('[data-region="preview"] [data-canprefill]');
    const canPrefill = preview && preview.dataset.canprefill === '1';
    form.querySelector('input[name="example"]').value = path;
    form.querySelectorAll('[type="submit"]').forEach((button) => {
        button.disabled = button.name === 'prefill' && !canPrefill;
    });
};

/**
 * Load and show the preview of an example.
 *
 * @param {HTMLElement} root library region
 * @param {HTMLElement} link example link that was clicked
 */
const showPreview = async(root, link) => {
    const path = link.dataset.path;
    const region = root.querySelector('[data-region="preview"]');
    try {
        // The fragment promise resolves with two arguments, so await alone would lose the JS.
        const [html, js] = await new Promise((resolve, reject) => {
            Fragment.loadFragment('qtype_aitext', 'example_preview', root.dataset.contextid, {path})
                .then((html, js) => resolve([html, js]))
                .fail(reject);
        });
        Templates.replaceNodeContents(region, html, js);
        markSelected(root, link);
        updateForm(root, path);
        // Keep the tag filter in the URL along with the chosen example.
        const url = new URL(link.href);
        chosenKeys(root).forEach((key) => url.searchParams.append('filter', key));
        window.history.replaceState(null, '', url);
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Set whether a filter button is chosen.
 *
 * @param {HTMLElement} button filter button
 * @param {boolean} pressed
 */
const setPressed = (button, pressed) => {
    button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
    button.classList.toggle('active', pressed);
};

/**
 * The facet:value keys chosen in the tag filter.
 *
 * @param {HTMLElement} root library region
 * @returns {string[]}
 */
const chosenKeys = (root) => [...root.querySelectorAll('[data-action="filter"][aria-pressed="true"]')]
    .map((button) => button.dataset.key);

/**
 * Show only the examples matching the tag filter, and update the subject counts.
 *
 * Within a facet any chosen value matches; an example must match every facet that has a chosen value.
 * The chosen filter is kept in the page URL so it survives a reload.
 *
 * @param {HTMLElement} root library region
 */
const applyFilter = (root) => {
    const keys = chosenKeys(root);
    const byFacet = {};
    keys.forEach((key) => {
        const facet = key.split(':')[0];
        (byFacet[facet] = byFacet[facet] || []).push(key);
    });
    const facets = Object.values(byFacet);

    let total = 0;
    root.querySelectorAll('[data-subject]').forEach((subject) => {
        let count = 0;
        subject.querySelectorAll('[data-facets]').forEach((item) => {
            const itemKeys = JSON.parse(item.dataset.facets);
            const match = facets.every((facetKeys) => facetKeys.some((key) => itemKeys.includes(key)));
            item.hidden = !match;
            count += match ? 1 : 0;
        });
        subject.hidden = count === 0;
        subject.querySelector('[data-region="count"]').textContent = count;
        total += count;
    });
    root.querySelector('[data-region="nomatch"]').hidden = total > 0;
    root.querySelector('[data-action="clearfilters"]').hidden = keys.length === 0;

    const url = new URL(window.location.href);
    url.searchParams.delete('filter');
    keys.forEach((key) => url.searchParams.append('filter', key));
    window.history.replaceState(null, '', url);
};

/**
 * Show the tag filter, with any filter from the page URL chosen.
 *
 * @param {HTMLElement} root library region
 */
const initFilter = (root) => {
    const filters = root.querySelector('[data-region="filters"]');
    if (!filters) {
        return;
    }
    const fromUrl = new URL(window.location.href).searchParams.getAll('filter');
    filters.querySelectorAll('[data-action="filter"]').forEach((button) => {
        setPressed(button, fromUrl.includes(button.dataset.key));
    });
    filters.hidden = false;
    applyFilter(root);
};

/**
 * Set up the example library page.
 */
export const init = () => {
    const root = document.querySelector('[data-region="examplelibrary"]');
    if (!root) {
        return;
    }
    initFilter(root);
    root.addEventListener('click', (e) => {
        const filter = e.target.closest('[data-action="filter"]');
        if (filter) {
            setPressed(filter, filter.getAttribute('aria-pressed') !== 'true');
            applyFilter(root);
            return;
        }
        if (e.target.closest('[data-action="clearfilters"]')) {
            root.querySelectorAll('[data-action="filter"]').forEach((button) => setPressed(button, false));
            applyFilter(root);
            return;
        }
        const link = e.target.closest('[data-action="preview"]');
        if (!link) {
            return;
        }
        e.preventDefault();
        showPreview(root, link);
    });
};
