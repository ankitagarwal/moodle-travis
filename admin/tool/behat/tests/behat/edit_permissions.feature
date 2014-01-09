@tool @tool_behat
Feature: Edit capabilities
  In order to extend and restrict moodle features
  As an admin or a teacher
  I need to allow/deny the existing capabilities at different levels

  @javascript @what2
  Scenario: Default system capabilities modification
    Given I run a skip test
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability | permission |
      | block/mnet_hosts:myaddinstance | Allow |
      | moodle/community:add | Inherit |
      | moodle/grade:managesharedforms | Prevent |
      | moodle/course:request | Prohibit |
    When I follow "Edit Teacher role"
  @javascript
  Scenario: Course capabilities overrides
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I expand "Users" node
    And I follow "Permissions"
    And I override the system permissions of "Student" role with:
      | mod/forum:deleteanypost | Prohibit |
      | mod/forum:editanypost | Prevent |
      | mod/forum:addquestion | Allow |
    When I select "Student (3)" from "Advanced role override"
    Then the "mod/forum:deleteanypost" field should match "-1000" value
    And the "mod/forum:editanypost" field should match "-1" value
    And the "mod/forum:addquestion" field should match "1" value

  @javascript
  Scenario: Module capabilities overrides
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | I'm the name |
      | Description | I'm the introduction |
    And I follow "I'm the name"
    And I follow "Permissions"
    And I override the system permissions of "Student" role with:
      | mod/forum:deleteanypost | Prohibit |
      | mod/forum:editanypost | Prevent |
      | mod/forum:addquestion | Allow |
    When I select "Student (3)" from "Advanced role override"
    Then the "mod/forum:deleteanypost" field should match "-1000" value
    And the "mod/forum:editanypost" field should match "-1" value
    And the "mod/forum:addquestion" field should match "1" value
