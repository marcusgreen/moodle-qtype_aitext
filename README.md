###  Moodle AI Text Quiz Question type by Marcus Green

This Moodle question type accepts free text which is then evaluated by a remote Large Language Model AI system such as ChatGPT. Each question can have its own feedback and grading prompts. For custom development and consultancy contact Moodle Partner Catalyst EU (https://www.catalyst-eu.net/).

It requires either a paid for ChatGPT api account which will give access to ChatGPT4 or 
other Large Language Model such as Ollama or https://groq.com.

It depends on this plugin for the api calls to work.
https://github.com/marcusgreen/moodle-tool_aiconnect

Additional documentation can be found here https://github.com/marcusgreen/moodle-qtype_aitext/wiki

## Warning
LLarge Language modesls (LLMs) such as ChatGPT are based on probablity and should never be relied on for high stakes tasks. LLM systems may give wrong and misleading answers and may give different answers based on the same data. This plugin is only as good as the LLM system it is linked to and the prompts that are provided as parts of the question.  This plugin was developed to help teachers and students to promote learning and it is widely used.

## Additional Warning

Do not use for important assessments,

## Prompting
It requires the creation of a prompt to evaluate the text according to its purpose and an optional marking scheme. For example for a question 

"Write an English sentence in the past tense"

The prompt could be

"Explain if there is anything wrong with the Grammar in this text."

An example mark scheme could be

Give 10 marks if there are no errors and all spelling is correct and it is in the past tense. Give 0 marks if the grammar is incorrect. Deduct one mark,  every word where the spelling is incorrect"

There is a prompttester field in the quesition editing form which uses ajax to test out prompts without needing to go through the quesiton preview screen.

## Limitations

HTML tags are stripped out from the text submitted to the AI System so evaluation cannot consider HTML formatting.

## Credits

I'd like to thank Justin Hunt of https://poodll.com for encurangement and contributions. Poodll is a a suite of excellent, mature language learning tools that integrate with moodle.
I would also like to thank the people at https://mebis.bycs.de for ideas, code and encouragement.

## Roadmap

Cron based evaluation. Allow for slow LLM systems by marking on a cron timer

## Promotion
If you are a Moodle developer and you use vscode/vscodium you should consider this plugin https://marketplace.visualstudio.com/items?itemName=LMSCloud.mdlcode.
It it very reasonably priced and will quickly save you time and frustration. It is the best Moodle development tool I have come accross in 20 years.

