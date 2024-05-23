@qtype @qtype_aitext @qtype_aitext_preview
Feature: Preview aitext questions
    As a teacher
    In order to check my aitext questions will work for students
    I need to preview them

  Background:
    Given the following "users" exist:
        | username | firstname | lastname | email               |
        | teacher  | user      | user | teacher@example.org |
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

  @javascript
  Scenario: Preview an aitext question that uses the HTML editor.
  # Testing with the HTML editor is a legacy of the essay fork
  # as aitext strips html before sending it may be redundant
    When I am on the "aitext-001" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the field "How questions behave" to "Immediate feedback"
    # And I press "Start again with these options"
    And I press "saverestart"
    And I should see "Please write a story about a frog."

  @javascript @_switch_window
  Scenario: Preview an aitext question that uses a plain text area.
    When I am on the "aitext-002" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the field "How questions behave" to "Immediate feedback"
    # And I press "Start again with these options"
    And I press "saverestart"
    And I should see "Please write a story about a frog."
