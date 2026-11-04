@qtype @qtype_aitext @qtype_aitext_examples
Feature: Add the questions in a site category to the AI Text example library
  As an administrator
  In order to share our own example questions with every teacher
  I need to choose a question category whose questions appear in the example library

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
    And the following "activities" exist:
      | activity | name          | course               | idnumber |
      | qbank    | Site examples | Acceptance test site | sitebank |
    And the following "question categories" exist:
      | contextlevel    | reference | questioncategory | name         |
      | Activity module | sitebank  | top              | Example bank |
      | Activity module | sitebank  | Example bank     | Biology      |
    And the following "questions" exist:
      | questioncategory | qtype  | name          | template | questiontext                   |
      | Biology          | aitext | Blood vessels | plain    | Compare arteries and veins.    |
    And the following "core_question > Tags" exist:
      | question      | tag        |
      | Blood vessels | level:gcse |

  @javascript
  Scenario: Teachers can preview, filter and import questions from the example category
    Given I log in as "admin"
    And I navigate to "Plugins > Question types > AI Text" in site administration
    And I set the field "Example category" to "Site examples: Example bank"
    And I press "Save changes"
    And I log out
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "item_qtype_aitext" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I follow "Browse example questions"
    And I click on "Biology" "text"
    Then I should see "Site" in the "[data-path^='bank/']" "css_element"
    # Choosing ESL leaves only English examples, so Biology is hidden.
    And I press "Esl"
    And "details[data-subject='biology']" "css_element" should not be visible
    And "details[data-subject='english']" "css_element" should be visible
    And I press "Clear filters"
    And I press "Gcse"
    And "details[data-subject='english']" "css_element" should not be visible
    And I should see "Blood vessels"
    And I click on "Blood vessels" "link"
    And I should see "Compare arteries and veins." in the "[data-region='preview']" "css_element"
    And I press "Import this question"
    And I should see "Imported 1 question(s):"
    And "Blood vessels" "link" should exist in the ".alert-success" "css_element"

  @javascript
  Scenario: Built-in example subjects can be hidden
    Given the following config values are set as admin:
      | examplehiddensubjects | medicine,english | qtype_aitext |
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "item_qtype_aitext" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I follow "Browse example questions"
    Then I should see "Biology"
    And I should not see "Medicine"
    And I should not see "English"
