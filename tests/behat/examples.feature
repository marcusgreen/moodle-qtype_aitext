@qtype @qtype_aitext @qtype_aitext_examples
Feature: Browse, preview and import AI Text example questions
  As a teacher
  In order to get started quickly with AI Text questions
  I need to browse the example questions by subject, preview them and import them into my question bank

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
    And I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "item_qtype_aitext" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I follow "Browse example questions"

  @javascript @accessibility
  Scenario: Preview an example and import it
    Then I should see "Example questions"
    And I should see "Choose a question from the list to preview it."
    And the "Import this question" "button" should be disabled
    When I click on "Medicine" "text"
    And I click on "ECG interpretation: rhythm strip" "link"
    Then I should see "Sample responses" in the "[data-region='preview']" "css_element"
    And I should see "Examine the rhythm strip above" in the "[data-region='preview']" "css_element"
    And the "Import this question" "button" should be enabled
    And the page should meet accessibility standards
    And I press "Import this question"
    And I should see "Imported 1 question(s):"
    And "ECG interpretation: rhythm strip" "link" should exist in the ".alert-success" "css_element"
    # The previewed example stays selected after importing.
    And I should see "Sample responses" in the "[data-region='preview']" "css_element"
    And I click on "Back" "link" in the "region-main" "region"
    And I should see "ECG interpretation: rhythm strip"

  @javascript
  Scenario: Import every example in a subject
    When I click on "English" "text"
    And I click on "The future tense" "link"
    And I press "Import all in this subject"
    Then I should see "Imported 3 question(s):"
    And "The future tense" "link" should exist in the ".alert-success" "css_element"
    And "Polite request" "link" should exist in the ".alert-success" "css_element"
    And "Subject verb agreement" "link" should exist in the ".alert-success" "css_element"
