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
 * @param {HTMLElement} root library region
 * @param {string} path example path
 */
const updateForm = (root, path) => {
    const form = root.querySelector('.qtype_aitext_examples-import form');
    form.querySelector('input[name="example"]').value = path;
    form.querySelectorAll('input[type="submit"], button[type="submit"]').forEach((button) => {
        button.disabled = false;
        button.removeAttribute('disabled');
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
        window.history.replaceState(null, '', link.href);
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Set up the example library page.
 */
export const init = () => {
    const root = document.querySelector('[data-region="examplelibrary"]');
    if (!root) {
        return;
    }
    root.addEventListener('click', (e) => {
        const link = e.target.closest('[data-action="preview"]');
        if (!link) {
            return;
        }
        e.preventDefault();
        showPreview(root, link);
    });
};
