# AI Text Question Type for Moodle

`qtype_aitext` is a Moodle question type for free-text responses assessed with a configured Large Language Model (LLM). Teachers define the grading instructions for each question, and the plugin sends the student response to a supported AI backend to obtain feedback and, optionally, a numerical mark.

The plugin is designed for Moodle installations that need flexible AI-assisted assessment rather than a fixed answer key. It supports question-specific prompts, marking schemes, structured feedback, multiple AI backends, prompt testing during authoring, word limits, spellcheck assistance, and optional asynchronous grading through Moodle Cron.

> AI-generated feedback and marks are assessment assistance, not a substitute for institutional academic policy or appropriate human review.

## Features

| Feature | Description |
|---|---|
| Free-text assessment | Students answer questions using plain text, an HTML editor, a monospaced editor, or an editor with file-picker support where configured. |
| Question-specific grading | Each question can define its own AI prompt and optional marking scheme. |
| Structured output | The plugin asks the LLM to return feedback and marks in a predictable JSON structure, then parses and validates the response. |
| Multiple AI backends | Supports Moodle's Core AI subsystem, `local_ai_manager`, and `tool_aiconnect`, subject to their respective installation and configuration requirements. |
| Prompt tester | Teachers can test sample responses from the question-editing form without opening the question preview. |
| Expert mode | Advanced authors can use the complete prompt template directly with placeholders such as `{{response}}`, `{{questiontext}}`, `{{markscheme}}`, `{{language}}`, and `{{role}}`. |
| Word limits | Questions can enforce minimum and maximum response lengths. |
| AI spellcheck | Optional spellcheck assistance can be enabled for a question. |
| Asynchronous grading | Slow LLM calls can be processed by a Moodle scheduled task instead of blocking the student's submission request. |
| Moodle integration | AI results are stored in the Moodle question-attempt history and, for finished quiz attempts, the quiz total and Gradebook can be refreshed. |

## Requirements

| Requirement | Version or condition |
|---|---|
| Moodle | Moodle 4.5 or later. The current plugin metadata declares Moodle 4.5 as the minimum supported version. |
| PHP | Use the PHP version required by the installed Moodle release. |
| AI service | A configured supported backend and an LLM service accessible through that backend. |
| Moodle Cron | Required when asynchronous Cron-based Evaluation is enabled. |
| Node.js and npm | Required only when updating the bundled `jsdiff` JavaScript dependency. |

The plugin does not provide an LLM service or API credentials. The Moodle administrator must install and configure one of the supported AI backends separately.

## Installation

Install the plugin using one of the standard Moodle plugin installation methods.

For a manual installation, copy or extract the repository into:

```text
<moodle-root>/question/type/aitext
```

The directory must contain files such as `question.php`, `questiontype.php`, `settings.php`, and `version.php` directly; do not create an additional nested `moodle-qtype_aitext` directory inside `question/type/aitext`.

After copying the files, complete the Moodle upgrade from the web administration interface or run the Moodle CLI upgrade command:

```bash
php admin/cli/upgrade.php
```

The upgrade creates or updates the plugin database structures, including the asynchronous grading queue when that feature is available in the installed version.

## Configure the AI backend

Before creating AI Text questions, configure an AI backend that is supported by the plugin and enabled on the Moodle site. The available backend choices are exposed in the plugin settings under:

```text
Site administration > Plugins > Question types > AI Text
```

The exact provider settings depend on the selected backend. Administrators should verify the provider's endpoint, authentication, model availability, usage limits, data-retention policy, and failure behavior before enabling AI-assisted grading for students.

## Create an AI Text question

Create an **AI Text** question in the Moodle question bank. Configure the response format and optional minimum or maximum word limits, then provide the following grading information:

| Field | Purpose |
|---|---|
| AI prompt | Describes what the LLM should evaluate in the student's response. |
| Mark scheme | Explains how the LLM should calculate the numerical mark. The maximum available mark is the question's **Default mark**. |
| Response template | Optional text displayed in the response field when a new attempt starts. |
| Sample responses | Optional examples used by the prompt tester while authoring the question. |
| Spellcheck | Enables a separate AI-assisted spellcheck request when supported by the selected backend. |

Use the prompt tester to evaluate representative responses before making the question available to students. Test correct, partially correct, incorrect, empty, unusually long, multilingual, and adversarial responses.

## Prompt design

A prompt should describe the assessment objective clearly and should separate the question, grading instructions, marking criteria, and student response. For example:

**Question:**

```text
Write an English sentence in the past tense.
```

**AI prompt:**

```text
Check whether the student's sentence is grammatical and written in the past tense. Explain any errors clearly and suggest a corrected sentence when appropriate.
```

**Mark scheme:**

```text
Award 10 marks when the sentence is grammatical, correctly spelled, and uses the past tense. Award 0 marks when the sentence is not grammatical or does not use the past tense. Deduct marks proportionally for minor errors.
```

The plugin builds the final prompt from the configured role, question text, AI prompt, mark scheme, student response, language preference, output instructions, and maximum score. Expert mode can bypass the central template when the question prompt contains the required `{{response}}` placeholder.

## Asynchronous Cron-based Evaluation

LLM calls can take longer than a normal web request, especially when a provider is under load or a local model is running on limited hardware. The plugin therefore provides an opt-in asynchronous mode.

When asynchronous grading is enabled, Moodle records the submission and places a job in the `qtype_aitext_queue` table. The student's request returns without waiting for the LLM. Moodle Cron later processes the queue, writes the AI result to the question attempt, and updates the quiz total and Gradebook for finished quiz attempts when applicable.

### Enable asynchronous grading

1. Open **Site administration > Plugins > Question types > AI Text**.
2. Enable **Enable Cron-based AI grading**.
3. Configure the AI backend and confirm that it can process requests from the Moodle server.
4. Ensure the Moodle Cron service is running.
5. Open **Site administration > Server > Scheduled tasks** and locate **Process asynchronous AI grading queue**.

The task is registered to run every minute, but actual execution depends on the normal Moodle Cron schedule. The following settings control throughput and retry behavior:

| Setting | Default | Description |
|---|---:|---|
| Enable Cron-based AI grading | Disabled | Switches grading from the synchronous request path to the queue. |
| Cron AI grading batch size | `5` | Maximum number of jobs processed by one task run. Runtime values are bounded to `1`–`100`. |
| Maximum AI grading retries | `5` | Maximum number of attempts before a job becomes permanently failed. Runtime values are bounded to `1`–`20`. |
| AI grading retry delay | `300` seconds | Initial delay before retrying a failed job. Runtime values are bounded to `60`–`3600` seconds; the delay increases with each attempt up to one hour. |

A queued question remains in the `needsgrading` state until the worker creates a grading step. If the provider returns feedback without a valid numerical mark, the feedback is retained but the question remains available for manual grading. The question renderer displays a pending message while the result is being processed. If a newer response is submitted before an older job runs, response-version protection prevents the old result from overwriting the newer response.

The queue also recovers jobs that remain in the `processing` state after a worker timeout. Administrators can inspect the queue table and Moodle task logs when diagnosing provider failures. The queue stores the response and prompt needed to complete the request, so its retention and access must be considered part of the site's data-protection policy.

## Data protection and security

Student responses may contain personal, sensitive, or confidential information. Before enabling an external AI service, the Moodle administrator should review the provider's data processing terms, retention policy, hosting location, access controls, and institutional approval requirements.

The plugin applies Moodle capability checks to the prompt-testing web service and uses Moodle's standard output and text-formatting functions when displaying AI feedback. Nevertheless, LLM-specific risks remain. Student text can contain prompt-injection instructions, unexpected markup, code, or sensitive data. Teachers should write prompts that treat the student response as untrusted content and should not rely on the LLM as the sole authority for high-stakes decisions.

The plugin strips HTML tags from the text sent to the AI service. Consequently, the model cannot evaluate visual formatting or HTML structure in the submitted response. Attachments and provider-specific handling should be reviewed separately before they are used in assessment decisions.

The asynchronous queue implements Moodle privacy metadata and supports export and deletion of queued records. Sites should also define an appropriate cleanup and retention policy for completed queue records and question-attempt history.

## Limitations

AI grading quality depends on the configured model, prompt, marking scheme, provider configuration, and language of the response. The plugin cannot guarantee that an LLM will always return valid JSON, consistent marks, or pedagogically appropriate feedback. Responses that cannot be parsed or graded automatically remain available for manual review.

Synchronous mode still waits for the AI backend during the submission request. Use asynchronous mode when provider latency is unpredictable or when a local model may require substantial processing time. Asynchronous mode requires a functioning Moodle Cron service and does not produce a result until the scheduled task has processed the job.

The plugin is not a plagiarism detector, a general-purpose moderation system, or a replacement for a teacher's assessment policy. Institutions should validate prompts and marks against representative submissions before production use.

## JavaScript dependency

The plugin uses [`jsdiff`](https://www.npmjs.com/package/diff) to display differences between the student's input and an AI-corrected version. The generated JavaScript is already bundled in `amd/src` and `amd/build`, so normal plugin installation does not require npm.

To update the dependency during development:

```bash
npm install
npm run deployJsDiff
```

## Development and testing

Follow the standard Moodle plugin development conventions. The main implementation areas are:

| Path | Responsibility |
|---|---|
| `question.php` | Question definition, response validation, prompt construction, synchronous grading, and queue submission. |
| `classes/local/queue.php` | Queue persistence, job state transitions, retry, and stale-job recovery. |
| `classes/task/process_ai_queue.php` | Moodle scheduled task that processes asynchronous grading jobs. |
| `classes/privacy/provider.php` | Privacy metadata, export, and deletion support for queue data. |
| `db/install.xml` and `db/upgrade.php` | Database installation and upgrade definitions. |
| `tests/` | PHPUnit and Behat tests. |

For JavaScript changes, update the source modules under `amd/src` and rebuild the deployed AMD files according to Moodle's JavaScript build conventions. For PHP changes, run the available Moodle linting and PHPUnit suites in a configured Moodle development site. At minimum, verify PHP syntax, XMLDB validity, database upgrades, synchronous grading, asynchronous queue processing, retry behavior, stale-job recovery, newer-response protection, and Gradebook updates.

## Documentation and source code

* [Source repository](https://github.com/agutuyen/moodle-qtype_aitext)
* [Upstream repository](https://github.com/marcusgreen/moodle-qtype_aitext)
* [Project wiki](https://github.com/marcusgreen/moodle-qtype_aitext/wiki)
* [Moodle Question API documentation](https://moodledev.io/docs/apis/subsystems/question)
* [Moodle Scheduled Tasks documentation](https://moodledev.io/docs/apis/subsystems/task)

## License

This plugin is distributed under the [GNU General Public License v3 or later](https://www.gnu.org/licenses/gpl-3.0.html).
