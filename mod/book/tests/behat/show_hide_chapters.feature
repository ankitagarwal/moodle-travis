@mod @mod_book
Feature: In a book, show and hide chapters and sub chapters
  In order to show/hide chapters and sub chapters
  As a teacher
  I need to show or hide them.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Book" to section "1" and I fill the form with:
      | Name | Test book |
      | Description | A book about dreams! |
    And I follow "Test book"
    And I set the following fields to these values:
      | Chapter title | First chapter |
      | Content | First chapter |
    And I press "Save changes"
    And I click on "a[href*='pagenum=1']" "css_element"
    And I set the following fields to these values:
      | Chapter title | Second chapter |
      | Content | Second chapter |
    And I press "Save changes"
    And I click on "a[href*='pagenum=2']" "css_element"
    And I set the following fields to these values:
      | Chapter title | Sub chapter |
      | subchapter | 1 |
      | Content | Sub chapter |
    And I press "Save changes"
    And I click on "a[href*='pagenum=3']" "css_element"
    And I set the following fields to these values:
      | Chapter title | Third chapter |
      | subchapter | 0 |
      | Content | Third chapter |
    And I press "Save changes"
    And I click on "a[href*='pagenum=4']" "css_element"
    And I set the following fields to these values:
      | Chapter title | Fourth chapter |
      | Content | Fourth chapter |
    And I press "Save changes"

  @javascript
  Scenario: Show/hide chapters and subchapters

    When I click on "a[title='Hide chapter - 2 Second chapter']" "css_element"
    And I click on "a[title='Hide chapter - 2 Third chapter']" "css_element"
    And I click on "Turn editing off" "link"
    And  I am on homepage
    And I follow "Course 1"
    And I follow "Test book"
    Then I should not see "Second chapter" in the "Table of contents" "block"
    And I should not see "Third chapter" in the "Table of contents" "block"
    And I click on "Next" "link"
    And I should see "Fourth chapter" in the ".book_content" "css_element"
    And I click on "Exit book" "link"
    And I follow "Test book"
    And I should see "First chapter" in the ".book_content" "css_element"
    And I click on "Turn editing on" "link"
    And I click on "Next" "link"
    And I should see "Second chapter" in the ".book_content" "css_element"
    And I should not see "Exit book"
    And I click on "Next" "link"
    And I should see "Sub chapter" in the ".book_content" "css_element"
    And I click on "Next" "link"
    And I should see "Third chapter" in the ".book_content" "css_element"
    And I click on "Next" "link"
    And I should see "Fourth chapter" in the ".book_content" "css_element"
    And I click on "Exit book" "link"

