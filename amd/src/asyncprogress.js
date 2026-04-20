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
 * Reload the page when an async AI grading progress bar completes.
 *
 * @module     qtype_aitext/asyncprogress
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Attach a listener to each async-progress container. When the inner
 * stored-progress-bar fires its "update" event at 100%, reload the page
 * so the rendered AI feedback replaces the progress bar.
 */
export const init = () => {
    document.querySelectorAll('.qtype_aitext-async-progress').forEach(container => {
        const bar = container.querySelector('.stored-progress-bar');
        if (!bar) {
            return;
        }
        bar.addEventListener('update', e => {
            if (e.detail.percent === 100 && !e.detail.error) {
                // Brief pause so the user sees the completed bar before reload.
                setTimeout(() => location.reload(), 1500);
            }
        });
    });
};
