@qtype @qtype_aitext @qtype_aitext_backup_restore
Feature: Test duplicating a quiz containing an aitext question
    As a teacher
    In order re-use my courses containing aitext questions
    I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
        | fullname | shortname | category |
        | Course 1 | C1        | 0        |
    And the following "question categories" exist:
        | contextlevel | reference | name           |
        | Course       | C1        | Test questions |
    And the following "questions" exist:
        | questioncategory | qtype  | name       | template |
        | Test questions   | aitext | aitext-001 | editor   |
        | Test questions   | aitext | aitext-002 | plain    |
    And the following "activities" exist:
        | activity | name      | course | idnumber |
        | quiz     | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
        | aitext-001 | 1 |
        | aitext-002 | 1 |

    # Without this it will show the pending progress bar and the back
    # to course button introduced in Moodle 4.3
    # https://docs.moodle.org/403/en/Course_backup#Asynchronous_course_backups
    And the following config values are set as admin:
      | enableasyncbackup | 0 |

  @javascript
  Scenario: Backup and restore a course containing 3 aitext questions
    When I am on the "Course 1" course page logged in as admin
    And I backup "Course 1" course using this options:
        | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
        | Schema | Course name       | Course 2 |
        | Schema | Course short name | C2       |
    And I am on the "Course 2" "core_question > course question bank" page
    Then I should see "aitext-001"
    And I should see "aitext-002"
    And I choose "Edit question" action for "aitext-001" in the question bank
    Then the following fields match these values:
        | Question name    | aitext-001                                              |
        | Question text    | Please write a story about a frog.                      |
        | General feedback | I hope your story had a beginning, a middle and an end. |
        | Response format  | HTML editor                                             |
    And I press "Cancel"
    And I choose "Edit question" action for "aitext-002" in the question bank
    Then the following fields match these values:
        | Question name    | aitext-002                                              |
        | Question text    | Please write a story about a frog.                      |
        | General feedback | I hope your story had a beginning, a middle and an end. |
        | Response format  | Plain text                                              |
