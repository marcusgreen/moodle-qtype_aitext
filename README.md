###  Moodle AI Text Quiz Question type by Marcus Green

This is a fork of the core essay question type which can use the ChatGPT Large language model to give automatic feedback and marking for submitted responses.

It is currently limited to the immediate feedback quesiton behaviour. It requires the creation of a prompt to evaluate the text according to its purpose. For example to confirm the grammar in English the following can be used.

"Explain if there is anything wrong with the Grammar in this sentence.  Give 10 marks if there are no errors and all spelling is correct and it is in the past tense. Give 0 marks if the grammar is incorrect. Deduct one mark,  every word where the spelling is incorrect. Reply in json format with a response and marks fields."

