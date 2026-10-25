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
 * PHP calls this from within
 * classes/output/mobile.php
 */
/* eslint-env es6 */
var that = this;
var result = {
    componentInit: function() {

        // Check that "this.question" was provided.
        if (!this.question) {
            return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
        }

        // Create a temporary div to ease extraction of parts of the provided html.
        var div = document.createElement('div');
        div.innerHTML = this.question.html;

        // Replace Moodle's correct/incorrect classes, feedback and icons with mobile versions.
        that.CoreQuestionHelperProvider.replaceCorrectnessClasses(div);
        that.CoreQuestionHelperProvider.replaceFeedbackClasses(div);
        that.CoreQuestionHelperProvider.treatCorrectnessIcons(div);

        // Get useful parts of the data provided in the question's html.
        var text = div.querySelector('.qtext');
        if (text) {
            this.question.text = text.innerHTML;
        }

        var answer = div.querySelector('.answer');
        var response = null;
        if (answer) {
            // Editable textarea, or the read-only textarea/div shown in review.
            response = answer.querySelector('textarea') || answer.querySelector('.qtype_aitext_response');
        }

        if (!text || !response) {
            // Oops, the expected elements, text and response, were not found !!
            return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
        }

        // Keep the response together with its label, the hidden inputs that must be
        // submitted with it (e.g. answerformat), any format menu and the word count message.
        var html = '';
        var label = answer.querySelector('label.visually-hidden');
        if (label) {
            html += label.outerHTML;
        }
        html += response.outerHTML;
        // The app has no rich text editor, so editor formats get a plain textarea too. Submit
        // the answer as plain text, otherwise its line breaks are lost when shown as HTML.
        var editable = response.tagName === 'TEXTAREA' && !response.readOnly;
        answer.querySelectorAll('input[type="hidden"]').forEach(function(input) {
            if (editable && /answerformat$/.test(input.name)) {
                input.setAttribute('value', '2'); // FORMAT_PLAIN.
            }
            html += input.outerHTML;
        });
        answer.querySelectorAll('select').forEach(function(select) {
            answer.querySelectorAll('label').forEach(function(selectlabel) {
                if (selectlabel.htmlFor === select.id) {
                    html += selectlabel.outerHTML;
                }
            });
            html += select.outerHTML;
        });
        answer.querySelectorAll(':scope > p').forEach(function(p) {
            html += p.outerHTML;
        });
        this.question.answer = html;

        return true;
    }
};
/* eslint-disable-next-line */
result;