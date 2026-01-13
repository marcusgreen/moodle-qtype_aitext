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
 * Handle form submission for aitext questions with spinner.
 *
 * @module     qtype_aitext/submission
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {
    addIconToContainer
} from 'core/loadingicon';

/**
 * Initialize submission handling for aitext questions.
 */
export const init = () => {
    // Find the form and the submit button.
    const questionContainer = document.querySelector('.que.aitext');
    if (!questionContainer) {
        return;
    }
    const submitButton = questionContainer.querySelector('button[id^="q"][id$="-submit"]');
    const form = questionContainer.closest('form');

    if (!submitButton || !form) {
        return;
    }

    // When the submit button is clicked, show the spinner.
    submitButton.addEventListener('click', function(e) {
        // Show a spinner as an overlay on top of the submit button for better user feedback.
        const spinnerContainer = document.createElement('div');
        spinnerContainer.className = 'aitext-spinner-overlay';
        spinnerContainer.style.position = 'relative';
        spinnerContainer.style.display = 'inline-block';

        // Wrap the submit button with the overlay container
        submitButton.parentNode.insertBefore(spinnerContainer, submitButton);
        spinnerContainer.appendChild(submitButton);

        // Create inner container for the spinner
        const spinnerInner = document.createElement('div');
        spinnerInner.className = 'qtype_aitext_spinnerInner';

        spinnerContainer.appendChild(spinnerInner);

        // Show the spinner in the overlay container.
        addIconToContainer(spinnerInner).then(() => {
            submitButton.disabled = true;
            submitButton.style.opacity = '0.7';
            spinnerContainer.dataset.spinnerActive = 'true';
            return true;
        }).catch(() => {
            // Restore the original DOM structure if there's an error.
            spinnerContainer.parentNode.insertBefore(submitButton, spinnerContainer);
            spinnerContainer.remove();
        });

        // Prevent the default form submission to give the spinner time to appear.
        e.preventDefault();

        // Get the button's name and value, as they are not submitted with form.submit().
        const buttonName = submitButton.getAttribute('name');
        const buttonValue = submitButton.getAttribute('value');

        // Create a hidden input to carry the button's data.
        if (buttonName) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = buttonName;
            input.value = buttonValue || '';
            form.appendChild(input);
        }

        // The spinner will be hidden automatically when the page reloads after submission.
        // We use a small timeout to allow the browser to render the spinner before submitting the form.
        setTimeout(function() {
            form.submit();
        }, 100);
    });
};