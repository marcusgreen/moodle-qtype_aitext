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
/* eslint-disable no-console */
/* eslint-env es6 */
var that = this;
var result = {
    componentInit: function() {

        // Check that "this.question" was provided.
        if (! this.question) {
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

        var textarea = div.querySelector('.answer textarea');
        if (textarea === null) {
            // review or check
            textarea = div.querySelector('.answer .qtype_aitext_response');
        }
        if (textarea) {
            textarea.style.borderRadius = '4px';
            textarea.style.padding = '6px 12px';
            if (textarea.matches('.readonly')) {
                textarea.style.border = '2px #b8dce2 solid'; // light blue
                textarea.style.backgroundColor = '#e7f3f5'; // lighter blue
            } else {
                textarea.style.backgroundColor = '#edf6f7'; // lightest blue
            }
            this.question.textarea = textarea.outerHTML;
        }

        var itemcount = div.querySelector('.itemcount');
        if (itemcount) {

            // Replace bootstrap styles with inline styles because
            // adding styles to 'mobile/styles_app.css' doesn't seem to be effective :-(
            that.replaceBootstrapClasses(itemcount);

            itemcount.querySelectorAll('p').forEach(function(p){
                that.replaceBootstrapClasses(p);
            });

            // Fix background and text color on "wordswarning" span.
            var warning = itemcount.querySelector(".warning");
            if (warning) {
                that.replaceBootstrapClasses(warning);
            }

            this.question.itemcount = itemcount.outerHTML;
        }

        /**
         * questionRendered
         */
        this.questionRendered = function(){

            var textarea = this.componentContainer.querySelector('textarea');
            var itemcount = this.componentContainer.querySelector('.itemcount');
            if (textarea && itemcount) {

                // Maybe "this.CoreLangProvider" has a method for fetching a string
                // but I can't find it, so we use our own method, thus:
                var minwordswarning = that.getPluginString("qtype_aitext", "minwordswarning");
                var maxwordswarning = that.getPluginString("qtype_aitext", "maxwordswarning");

                var countitems = itemcount.querySelector(".countitems");
                var value = countitems.querySelector(".value");
                var warning = countitems.querySelector(".warning");

                var itemtype = itemcount.dataset.itemtype;
                var minitems = parseInt(itemcount.dataset.minitems);
                var maxitems = parseInt(itemcount.dataset.maxitems);

                var itemsplit = '';
                switch (itemtype) {
                    case "chars": itemsplit = ""; break;
                    case "words": itemsplit = "[\\s—–]+"; break;
                    case "sentences": itemsplit = "[\\.?!]+"; break;
                    case "paragraphs": itemsplit = "[\\r\\n]+"; break;
                }

                if (itemsplit) {
                    itemsplit = new RegExp(itemsplit);
                    textarea.addEventListener("keyup", function() {
                        var text = textarea.value;
                        var warningtext = "";
                        var count = 0;
                        if (text) {
                            count = text.split(itemsplit).filter(function(item) {
                                return (item !== "");
                            }).length;
                            if (minitems && (count < minitems)) {
                                warningtext = minwordswarning;
                            }
                            if (maxitems && (count > maxitems)) {
                                warningtext = maxwordswarning;
                            }
                        }
                        value.innerText = count;
                        if (warning) {
                            warning.innerText = warningtext;
                            if (warningtext == "") {
                                warning.style.display = "none";
                            } else {
                                warning.style.display = "inline";
                            }
                        }
                    });
                }
            }
        };

        if (text && textarea) {
            return true;
        }

        // Oops, the expected elements, text and textarea, were not found !!
        return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
    }
};
/* eslint-disable-next-line */
result;