###  Moodle AI Text Quiz Question type by Marcus Green

This Moodle question type accepts free text which is then evaluated by a remote Large Language Model AI system such as ChatGPT. Each question can have its own feedback and grading prompts. For custom development and consultancy contact Moodle Partner Catalyst EU (https://www.catalyst-eu.net/).

It requires either a paid for ChatGPT api account which will give access to ChatGPT4 or 
other Large Language Model such as Ollama or https://groq.com.

It depends on this plugin for the api calls to work.
https://github.com/marcusgreen/moodle-tool_aiconnect

Additional documentation can be found here https://github.com/marcusgreen/moodle-qtype_aitext/wiki

## Prompting
It is currently limited to the immediate feedback quesiton behaviour. It requires the creation of a prompt to evaluate the text according to its purpose. For example to confirm the grammar in English the following can be used.

"Explain if there is anything wrong with the Grammar in this sentence.  Give 10 marks if there are no errors and all spelling is correct and it is in the past tense. Give 0 marks if the grammar is incorrect. Deduct one mark,  every word where the spelling is incorrect. Reply in json format with a response and marks fields."

It may help to test prompts directly on the Chat GPT site to confirm they  work as expected.


## Limitations

Although it is based on a clone of the core esssay question type with lots of inspiration from Gordon Batesons essay_autograde plugin, it is being built and tested mainly on text responses and so things like the inclusion of images in the question display may not work. It will only work with the immediate feedback question behaviour.

If you are a Moodle developer and you use vscode/vscodium you should consider this plugin https://marketplace.visualstudio.com/items?itemName=LMSCloud.mdlcode.

## Roadmap

Cron based evaluation. Allow for slow LLM systems by marking on a cron timer
