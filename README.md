# Moodle AI Text Question Type

[![Moodle Plugin CI](https://github.com/marcusgreen/moodle-qtype_aitext/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/marcusgreen/moodle-qtype_aitext/actions/workflows/moodle-ci.yml)
[![GitHub Release](https://img.shields.io/github/release/marcusgreen/moodle-qtype_aitext.svg)](https://github.com/marcusgreen/moodle-qtype_aitext/releases)
[![Moodle Support](https://img.shields.io/badge/Moodle-%3E%3D%204.5-blue)](https://marketplace.moodle.com/plugins/qtype_aitext)

*by Marcus Green*

🇬🇧 **English** · 🇩🇪 **[Deutsch](./README.de.md)**

A Moodle question type that accepts free-text answers and grades them with a remote Large Language Model (LLM) such as ChatGPT or a self-hosted Ollama model. Each question defines its own grading prompt and optional marking scheme, so the AI evaluates responses against criteria you set.


For custom development and consultancy, contact Moodle Partner [Catalyst EU](https://www.catalyst-eu.net/).

Install from the Moodle plugins marketplace: <https://marketplace.moodle.com/plugins/qtype_aitext>

Changelog: <https://github.com/marcusgreen/moodle-qtype_aitext/blob/main/changelog.md>

Additional documentation: <https://github.com/marcusgreen/moodle-qtype_aitext/wiki>

## Requirements

- Moodle 4.5 or later.
- Access to the API of an external LLM.

## How it works

You supply two things per question:

1. A **prompt** telling the AI how to evaluate the response.
2. An optional **marking scheme** describing how to award marks.

For the question *"Write an English sentence in the past tense"*, the prompt could be:

> Explain if there is anything wrong with the grammar in this text.

And a marking scheme could be:

> Give 10 marks if there are no errors, all spelling is correct and it is in the past tense. Give 0 marks if the grammar is incorrect. Deduct one mark for every word that is misspelled.

A **prompt tester** field in the question editing form uses AJAX to try prompts out directly, without stepping through the question preview screen.

## Grading

Grading runs through two dedicated companion question behaviours rather than overriding Moodle's core behaviours:

- [`qbehaviour_immediate_for_aitext`](https://github.com/marcusgreen/moodle-qbehaviour_immediate_for_aitext)
- [`qbehaviour_deferred_for_aitext`](https://github.com/marcusgreen/moodle-qbehaviour_deferred_for_aitext)

These are separate plugins and must be installed alongside this question type, under `question/behaviour/immediate_for_aitext` and `question/behaviour/deferred_for_aitext`. Without them, AIText questions cannot be graded.

This keeps AIText grading predictable and isolated from other question types. Existing quiz attempts that used the legacy `interactivecountback` behaviour are migrated automatically on upgrade; attempts belonging to other question types are left untouched.

If the configured AI tools are unavailable, grading degrades gracefully instead of erroring, so an attempt is not lost.

### Manual grading

AI-generated feedback can be shown to a human grader and used as a reference when marking a response manually.

## Credits
Thanks to the ByCS team for ongoing extensive feedback, support encouragement, testing and contributed code since around July 2024

## License

Licensed under the [GNU GPL v3 or later](https://www.gnu.org/licenses/gpl-3.0.html).