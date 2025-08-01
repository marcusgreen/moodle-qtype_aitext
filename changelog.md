### Release 0.04 of the Moodle AIText question type July 2025

Merge of callbacks code for upcoming mebis local_ai_connect features.
Fix upgrade.db which was missing the spellcheck fields and breaking the plugin

### Release 0.03 of the Moodle AIText question type May 2025

Fixed compatibility with Moodle 5.0, thanks to Philipp Memmel of Mebis-lp for the hint about Dependency Injection.

In question editing form changed sample answer allowing multiple sample responses. This saves multiple responses
to be tested against the prompt. Make the spinner prettier for the web service call in the editing form.

Fix backup/restore and xml import/export


### Release 0.02 of the Moodle AIText question type Dec 2024

Merged in code from https://github.com/mebis-lp/moodle-qtype_aitext
Who have have extensive experience with live use of the question type.

It is now possible to select from three different "back end" systems for connecting to the LLM. These are

* Tool AI Connect
* Moodle 4.5 AI Subsystem
* Local AI Manager

Tool AI Connect was the only way previously. The Moodle 4.5 AI subsystem only supports OpenAI ChatGPT and Microsoft Azure (which supports the Open AI Models).

Local AI Manager support Openai (ChatGPT), Ollama, and has other interesting features including this from the readme

https://github.com/mebis-lp/moodle-local_ai_manager/blob/main/README.md

"The AI manager provides the key feature of being a multi-tenant AI backend which is the main reason you should be using this plugin for providing AI functionality in your moodle instance instead of the moodle core AI subsystem."

Removed the ability to pick a different model for each question.

New configuration options in settings.php

Marks prompt in the editing interface is optional

Translation of the response is now optional

Big thanks to Philipp Memmel for help with the switchable backend settings to work and for testing and many other things.

Thanks to Farah Ahmad of Aga Khan University for reporting an issue with plagiarism plugins.

Thanks to Matt Metzgar for reporting an issue with the tranlation postfix.

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

