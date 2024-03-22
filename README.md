###  Moodle AI Text Quiz Question type by Marcus Green

This is a fork of the core essay question type which can use the ChatGPT Large language model to give automatic feedback and marking for submitted responses.


It requires either a paid for ChatGPT api account which will give access to ChatGPT4 or Ollama hosted Large Language Model. Accounts are available from https://platform.openai.com, Ollama is available from https://github.com/ollama/ollama. It depends on the Moodle aiconnect tool which is available from

https://github.com/marcusgreen/moodle-tool_aiconnect

## Prompting
It is currently limited to the immediate feedback quesiton behaviour. It requires the creation of a prompt to evaluate the text according to its purpose. For example to confirm the grammar in English the following can be used.

"Explain if there is anything wrong with the Grammar in this sentence.  Give 10 marks if there are no errors and all spelling is correct and it is in the past tense. Give 0 marks if the grammar is incorrect. Deduct one mark,  every word where the spelling is incorrect. Reply in json format with a response and marks fields."

It may help to test prompts directly on the Chat GPT site to confirm they  work as expected.

## Limitations

Although it is based on a clone of the core esssay question type with lots of inspiration from Gordon Batesons essay_autograde plugin, it is being built and tested mainly on text responses and so things like the inclusion of
images in the question display may not work. It will only work with the immediate feedback question behaviour.

## Roadmap
~~Support other LLM systems. Supporting a self hosted LLM will ensure data sovereignty~~ done

Moderation: Do not show ai feedback until previewed and approved by a teache

Cron based evaluation. Allow for slow LLM systems by marking on a cron timer

Allow it to work with other question behaviour types such as Interactive with multiple tries.
