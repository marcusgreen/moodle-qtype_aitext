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

  Scenario: Create an AI text question with Response format set to 'HTML editor'
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I add a "AI Text" question filling the form with:
        | Question name    | aitext-001                      |
        | Question text    | Write an aitext with 500 words. |
        | General feedback | This is general feedback        |
        | Response format  | HTML editor                     |
        | AI Prompt        | Evaluate this                   |
        | Mark scheme      | Give one mark if correct        |

    Then I should see "aitext-001"

  Scenario: Create an AI Text question with Response format set to 'HTML editor with the file picker'
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I add a "AI Text" question filling the form with:
        | Question name    | aitext-002                      |
        | Question text    | Write an aitext with 500 words. |
        | General feedback | This is general feedback        |
        | AI Prompt        | Evaluate this                   |
        | Mark scheme      | Give one mark if correct        |

    Then I should see "aitext-002"

  @javascript
  Scenario: Create an AI Text question for testing some default options
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I add a "AI Text" question filling the form with:
        | Question name         | aitext-003                      |
        | Question text         | Write an aitext with 500 words. |
        | General feedback      | This is general feedback        |
        | id_responserequired   | 0                               |
        | id_responsefieldlines | 15                              |
    Then I should see "aitext-003"
    # Checking that the next new question form displays user preferences settings.
    And I press "Create a new question ..."
    And I set the field "item_qtype_aitext" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And the following fields match these values:
        | id_responserequired   | 0  |
        | id_responsefieldlines | 15 |
