@qtype @qtype_aitext @qtype_aitext_add
Feature: Test creating an AIText question
              As a teacher
              In order to test my students
              I need to be able to create an aitext question

        Background:

            Given the following "users" exist:
                  | username |
                  | teacher  |
              And the following "courses" exist:
                  | fullname | shortname | category |
                  | Course 1 | C1        | 0        |
              And the following "course enrolments" exist:
                  | user    | course | role           |
                  | teacher | C1     | editingteacher |
              And the following config values are set as admin:
                  | model | gpt-4,gpt-4o | tool_aiconnect |
        @javascript
        Scenario: Create an AI text question with Response format set to HTML editor
             When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
                And I press "Create a new question ..."
                And I set the field "AI Text" to "1"
                And I press "Add"
                And I set the following fields to these values:
                  | Question name    | aitext-001                      |
                  | Question text    | Write an aitext with 500 words. |
                  | General feedback | This is general feedback        |
                  | Response format  | HTML editor                     |
                  | AI Prompt        | Evaluate this                   |
                  | Mark scheme      | Give one mark if correct        |

             Then I should see "aitext-001"
