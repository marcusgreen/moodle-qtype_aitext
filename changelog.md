### Release 0.01 of the Moodle AIText question type May 2024

Version 2024050300

Many thanks to Justin Hunt of Poodll fame https://poodll.com/moodle for contributing code
to allw the testing of prompts from within the question editing form.

Many thanks to Alexander Mikasch of Moodle.NRW (https://moodlenrw.de/) for reporting a prompt injection issue

Refined the default prompt settings to ensure Ollama/mistral returns a number when grading

The model field in the plugins settings can now accept a comma separated list. If there
is more than one model the question editor form will show a dropdown with available models. The selected model is written to the database and will be used as part of the connection to the AI system.

Add model name to disclaimer if configured in settings as [[model]]

Many thanks to Peter Mayer for fixes to coding standards and for code to make the prompt field required. Also for being generally very encouraging.

