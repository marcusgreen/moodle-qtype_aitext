@qtype @qtype_aitext @qtype_aitext_edit
Feature: Test editing an AI Text question
    As a teacher
    In order to be able to update my aitext question
    I need to edit them

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
    And the following "question categories" exist:
        | contextlevel | reference | name           |
        | Course       | C1        | Test questions |
    And the following "questions" exist:
        | questioncategory | qtype  | name       | template |
        | Test questions   | aitext | aitext-001 | editor   |
        | Test questions   | aitext | aitext-002 | plain    |

  Scenario: Edit an aitext question
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I choose "Edit question" action for "aitext-001" in the question bank
    And I set the following fields to these values:
        | Question name |  |
    And I press "id_submitbutton"
    And I should see "You must supply a value here."
    And I set the following fields to these values:
        | Question name   | Edited aitext-001 name |
        | Response format | Plain text             |
    And I press "id_submitbutton"
    Then I should see "Edited aitext-001 name"
