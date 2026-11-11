@block @block_sharing_cart @javascript

Feature: Restore a selected activity from an unchecked custom section
  As an editing teacher
  I want to restore an activity from a custom section without restoring that section
  So that the activity is placed in the target section

  Background:
    Given the following "courses" exist:
      | fullname      | shortname | format  | numsections | initsections |
      | Source course | SRC       | pxgrid  | 0           | 1            |
      | Target course | TARGET    | topics  | 2           | 1            |
    And the following "format_pxgrid > sections" exist:
      | id | course | parent_section_id | name         |
      | 1  | SRC    | 0                 | Root section |
      | 2  | SRC    | 1                 | Custom child |
    And the following "activities" exist:
      | activity | name                  | course | idnumber       | section |
      | page     | Root activity         | SRC    | rootactivity   | 1       |
      | page     | Custom child activity | SRC    | childactivity  | 2       |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | admin | SRC    | editingteacher |
      | admin | TARGET | editingteacher |

  Scenario: Reselecting a child activity restores it flat in the target section
    Given I log in as "admin"
    And I am on the "SRC" "Course" page
    And I switch editing mode on
    And I queue section number "1" in the Sharing Cart
    And I run all adhoc tasks
    And I am on the "TARGET" "Course" page
    And I switch editing mode on
    And I click on "//a[@data-key='addblock']" "xpath_element"
    And I wait until "//a[@data-blockname='sharing_cart']" "xpath_element" exists
    And I click on "//a[@data-blockname='sharing_cart']" "xpath_element"
    And I wait until "(//div[@class='sharing_cart_item' and @data-type='section']//i[@data-action='copy_to_course'])[1]" "xpath_element" exists
    And I click on "(//div[@class='sharing_cart_item' and @data-type='section']//i[@data-action='copy_to_course'])[1]" "xpath_element"
    And I wait until "//li[@id='section-2']//*[@class='clipboard_target']" "xpath_element" exists
    And I click on "//li[@id='section-2']//*[@class='clipboard_target']" "xpath_element"
    And I wait until "//div[@class='modal-footer']//button[@data-action='save']" "xpath_element" exists
    And I set the field with xpath "//input[@data-container='true' and @data-title='Custom child']" to "0"
    And the field with xpath "//input[@data-container='true' and @data-title='Custom child']/ancestor::div[contains(concat(' ', normalize-space(@class), ' '), ' form-check ')][1]//div[contains(concat(' ', normalize-space(@class), ' '), ' item-children ')]//input[@data-type='coursemodule']" matches value "0"
    And I set the field with xpath "//input[@data-container='true' and @data-title='Custom child']/ancestor::div[contains(concat(' ', normalize-space(@class), ' '), ' form-check ')][1]//div[contains(concat(' ', normalize-space(@class), ' '), ' item-children ')]//input[@data-type='coursemodule']" to "1"
    Then the field with xpath "//input[@data-container='true' and @data-title='Custom child']" matches value "0"
    And the field with xpath "//input[@data-container='true' and @data-title='Custom child']/ancestor::div[contains(concat(' ', normalize-space(@class), ' '), ' form-check ')][1]//div[contains(concat(' ', normalize-space(@class), ' '), ' item-children ')]//input[@data-type='coursemodule']" matches value "1"
    When I click on "//div[@class='modal-footer']//button[@data-action='save']" "xpath_element"
    And I wait until "//div[contains(concat(' ', normalize-space(@class), ' '), ' queue-item ')]" "xpath_element" exists
    And I run all adhoc tasks
    And I reload the page
    Then "//li[@id='section-2']//div[@data-activityname='Root activity']" "xpath_element" should be visible
    And "//li[@id='section-2']//div[@data-activityname='Custom child activity']" "xpath_element" should be visible
    And "//li[@data-for='section'][@data-sectionname='Custom child']" "xpath_element" should not exist
