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
 * @copyright  2025 Larry Velarde
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str'], function($, Str) {
    return {
        init: function() {
            $(function() {
                const checkButton = $('button.submit[name$="-submit"]');

                if (!checkButton.length) {
                    return;
                }

                checkButton.on('click', function(e) {
                    const responseText = $("textarea[name^='q'][name$='answer']").val().trim();

                    if (!responseText) {
                        e.preventDefault();
                        e.stopPropagation();

                        // Use Moodle string API for translatable alert
                        Str.get_string('emptyresponsealert', 'qtype_aitext').then(function(message) {
                            alert(message);
                        });

                        return;
                    }

                    $(".aitext-spinner").removeClass("d-none");
                });
            });
        }
    };
});