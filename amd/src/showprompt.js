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
 * @module     qtype_aitext/showprompt
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export const init = () => {
    var button = document.getElementById('showprompt');
    button.addEventListener('click', (event) => {
        toggleFullPrompt(event);
    });

    /**
     * Togle the visibility of the prompt that is sent to
     * the AI System
     * @param {*} event
     */
    function toggleFullPrompt(event) {
        event.preventDefault();
        var text = document.getElementById("fullprompt");
        if (text.className === "hidden") {
            text.className = "visible";
        } else {
            text.className = "hidden";
        }
    }
};
