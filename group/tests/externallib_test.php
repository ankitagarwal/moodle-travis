<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Group external PHPunit tests
 *
 * @package    core_group
 * @category   external
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.4
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/group/externallib.php');

class core_group_external_testcase extends externallib_advanced_testcase {

    /**
     * Test create_groups
     */
    public function test_create_groups() {
        global $DB;

        $this->resetAfterTest(true);

        $course  = self::getDataGenerator()->create_course();

        $group1 = array();
        $group1['courseid'] = $course->id;
        $group1['name'] = 'Group Test 1';
        $group1['description'] = 'Group Test 1 description';
        $group1['descriptionformat'] = FORMAT_MOODLE;
        $group1['enrolmentkey'] = 'Test group enrol secret phrase';
        $group2 = array();
        $group2['courseid'] = $course->id;
        $group2['name'] = 'Group Test 2';
        $group2['description'] = 'Group Test 2 description';
        $group3 = array();
        $group3['courseid'] = $course->id;
        $group3['name'] = 'Group Test 3';
        $group3['description'] = 'Group Test 3 description';

        // Set the required capabilities by the external function
        $context = context_course::instance($course->id);
        $roleid = $this->assignUserCapability('moodle/course:managegroups', $context->id);
        $this->assignUserCapability('moodle/course:view', $context->id, $roleid);

        // Call the external function.
        $groups = core_group_external::create_groups(array($group1, $group2));

        // We need to execute the return values cleaning process to simulate the web service server.
        $groups = external_api::clean_returnvalue(core_group_external::create_groups_returns(), $groups);

        // Checks against DB values
        $this->assertEquals(2, count($groups));
        foreach ($groups as $group) {
            $dbgroup = $DB->get_record('groups', array('id' => $group['id']), '*', MUST_EXIST);
            switch ($dbgroup->name) {
                case $group1['name']:
                    $groupdescription = $group1['description'];
                    $groupcourseid = $group1['courseid'];
                    $this->assertEquals($dbgroup->descriptionformat, $group1['descriptionformat']);
                    $this->assertEquals($dbgroup->enrolmentkey, $group1['enrolmentkey']);
                    break;
                case $group2['name']:
                    $groupdescription = $group2['description'];
                    $groupcourseid = $group2['courseid'];
                    break;
                default:
                    throw new moodle_exception('unknowgroupname');
                    break;
            }
            $this->assertEquals($dbgroup->description, $groupdescription);
            $this->assertEquals($dbgroup->courseid, $groupcourseid);
        }

        // Call without required capability
        $this->unassignUserCapability('moodle/course:managegroups', $context->id, $roleid);
        $this->setExpectedException('required_capability_exception');
        $froups = core_group_external::create_groups(array($group3));
    }

    /**
     * Test get_groups
     */
    public function test_get_groups() {
        global $DB;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();
        $group1data = array();
        $group1data['courseid'] = $course->id;
        $group1data['name'] = 'Group Test 1';
        $group1data['description'] = 'Group Test 1 description';
        $group1data['descriptionformat'] = FORMAT_MOODLE;
        $group1data['enrolmentkey'] = 'Test group enrol secret phrase';
        $group2data = array();
        $group2data['courseid'] = $course->id;
        $group2data['name'] = 'Group Test 2';
        $group2data['description'] = 'Group Test 2 description';
        $group1 = self::getDataGenerator()->create_group($group1data);
        $group2 = self::getDataGenerator()->create_group($group2data);

        // Set the required capabilities by the external function
        $context = context_course::instance($course->id);
        $roleid = $this->assignUserCapability('moodle/course:managegroups', $context->id);
        $this->assignUserCapability('moodle/course:view', $context->id, $roleid);

        // Call the external function.
        $groups = core_group_external::get_groups(array($group1->id, $group2->id));

        // We need to execute the return values cleaning process to simulate the web service server.
        $groups = external_api::clean_returnvalue(core_group_external::get_groups_returns(), $groups);

        // Checks against DB values
        $this->assertEquals(2, count($groups));
        foreach ($groups as $group) {
            $dbgroup = $DB->get_record('groups', array('id' => $group['id']), '*', MUST_EXIST);
            switch ($dbgroup->name) {
                case $group1->name:
                    $groupdescription = $group1->description;
                    $groupcourseid = $group1->courseid;
                    $this->assertEquals($dbgroup->descriptionformat, $group1->descriptionformat);
                    $this->assertEquals($dbgroup->enrolmentkey, $group1->enrolmentkey);
                    break;
                case $group2->name:
                    $groupdescription = $group2->description;
                    $groupcourseid = $group2->courseid;
                    break;
                default:
                    throw new moodle_exception('unknowgroupname');
                    break;
            }
            $this->assertEquals($dbgroup->description, $groupdescription);
            $this->assertEquals($dbgroup->courseid, $groupcourseid);
        }

        // Call without required capability
        $this->unassignUserCapability('moodle/course:managegroups', $context->id, $roleid);
        $this->setExpectedException('required_capability_exception');
        $groups = core_group_external::get_groups(array($group1->id, $group2->id));
    }

    /**
     * Test delete_groups
     */
    public function test_delete_groups() {
        global $DB;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();
        $group1data = array();
        $group1data['courseid'] = $course->id;
        $group1data['name'] = 'Group Test 1';
        $group1data['description'] = 'Group Test 1 description';
        $group1data['descriptionformat'] = FORMAT_MOODLE;
        $group1data['enrolmentkey'] = 'Test group enrol secret phrase';
        $group2data = array();
        $group2data['courseid'] = $course->id;
        $group2data['name'] = 'Group Test 2';
        $group2data['description'] = 'Group Test 2 description';
        $group3data['courseid'] = $course->id;
        $group3data['name'] = 'Group Test 3';
        $group3data['description'] = 'Group Test 3 description';
        $group1 = self::getDataGenerator()->create_group($group1data);
        $group2 = self::getDataGenerator()->create_group($group2data);
        $group3 = self::getDataGenerator()->create_group($group3data);

        // Set the required capabilities by the external function
        $context = context_course::instance($course->id);
        $roleid = $this->assignUserCapability('moodle/course:managegroups', $context->id);
        $this->assignUserCapability('moodle/course:view', $context->id, $roleid);

        // Checks against DB values
        $groupstotal = $DB->count_records('groups', array());
        $this->assertEquals(3, $groupstotal);

        // Call the external function.
        core_group_external::delete_groups(array($group1->id, $group2->id));

        // Checks against DB values
        $groupstotal = $DB->count_records('groups', array());
        $this->assertEquals(1, $groupstotal);

        // Call without required capability
        $this->unassignUserCapability('moodle/course:managegroups', $context->id, $roleid);
        $this->setExpectedException('required_capability_exception');
        $froups = core_group_external::delete_groups(array($group3->id));
    }

    /**
     * Test assign_module_grouping() and unassign_module_grouping()
     */
    public function test_module_grouping() {
        global $DB;
        $this->resetAfterTest();

        // create data to play with.
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_user();
        $role = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role->id);
        $record = new stdClass();
        $record->courseid = $course->id;
        $group1 = $this->getDataGenerator()->create_group($record);
        $group2 = $this->getDataGenerator()->create_group($record);
        $grouping1 = $this->getDataGenerator()->create_grouping($record);
        $grouping2 = $this->getDataGenerator()->create_grouping($record);
        $record->course = $course->id;
        $module1 = $this->getDataGenerator()->create_module('assign', $record);
        $module1 = get_coursemodule_from_instance('assign', $module1->id);
        $module2 = $this->getDataGenerator()->create_module('assignment', $record);
        $module2 = get_coursemodule_from_instance('assignment', $module2->id);
        $module3 = $this->getDataGenerator()->create_module('assign', $record);
        $module3 = get_coursemodule_from_instance('assign', $module3->id);

        // Let us test it out with proper caps.
        $this->setUser($user);
        $this->assignUserCapability('moodle/course:manageactivities', $context->id, $role->id);
        $this->assignUserCapability('moodle/course:managegroups', $context->id, $role->id);

        $params = array(
                array('groupingid' => $grouping1->id, 'cmid' => $module1->id),
                array('groupingid' => $grouping1->id, 'cmid' => $module2->id),
                array('groupingid' => $grouping2->id, 'cmid' => $module2->id),
                array('groupingid' => $grouping2->id, 'cmid' => $module3->id));
        core_group_external::assign_module_grouping($params);

        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping1->id, 'id' => $module1->id));
        $this->assertEquals(true, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping1->id, 'id' => $module2->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping2->id, 'id' => $module2->id));
        $this->assertEquals(true, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping2->id, 'id' => $module3->id));
        $this->assertEquals(true, $count);

        $params = array(
                array('groupingid' => $grouping1->id, 'cmid' => $module1->id),
                array('groupingid' => $grouping2->id, 'cmid' => $module2->id),
                array('groupingid' => $grouping2->id, 'cmid' => $module3->id));
        core_group_external::unassign_module_grouping($params);

        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping1->id, 'id' => $module1->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping1->id, 'id' => $module2->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping2->id, 'id' => $module2->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping2->id, 'id' => $module3->id));
        $this->assertEquals(false, $count);



        // No caps, no cookies.

        $this->unassignUserCapability('moodle/course:manageactivities', $context->id, $role->id);
        $this->unassignUserCapability('moodle/course:managegroups', $context->id, $role->id);

        $params = array(
                array('groupingid' => $grouping1->id, 'cmid' => $module1->id),
                array('groupingid' => $grouping1->id, 'cmid' => $module2->id),
                array('groupingid' => $grouping2->id, 'cmid' => $module2->id),
                array('groupingid' => $grouping2->id, 'cmid' => $module3->id));
        try {
            core_group_external::assign_module_grouping($params);
            $this->fail('Exception expected');
        } catch (Exception $e) {
            $this->assertInstanceOf('required_capability_exception', $e);
        }

        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping1->id, 'id' => $module1->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping1->id, 'id' => $module2->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping2->id, 'id' => $module2->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping2->id, 'id' => $module3->id));
        $this->assertEquals(false, $count);

        try {
            core_group_external::assign_module_grouping($params);
            $this->fail('Exception expected');
        } catch (Exception $e) {
            $this->assertInstanceOf('required_capability_exception', $e);
        }

        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping1->id, 'id' => $module1->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping1->id, 'id' => $module2->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping2->id, 'id' => $module2->id));
        $this->assertEquals(false, $count);
        $count = $DB->record_exists('course_modules', array('groupingid' => $grouping2->id, 'id' => $module3->id));
        $this->assertEquals(false, $count);

    }
}