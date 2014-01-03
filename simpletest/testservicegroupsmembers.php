<?php
/**
 * ILP Integration
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package block_intelligent_learning
 * @author Sam Chaffee
 */

mr_bootstrap::zend();

/**
 * @see Zend_Validate
 */
require_once('Zend/Validate.php');

/**
 * @see blocks_intelligent_learning_model_response
 */
require_once($CFG->dirroot.'/blocks/intelligent_learning/model/response.php');

/**
 * @see blocks_intelligent_learning_model_service_groups_members
 */
require_once($CFG->dirroot.'/blocks/intelligent_learning/model/service/groups_members.php');

/**
 * Test blocks_intelligent_learning_model_service_groups_members
 *
 * @package block_intelligent_learning
 * @author Sam Chaffee
 */
class blocks_intelligent_learning_model_service_groupsmembers_test extends UnitTestCase {
    public static $includecoverage = array(
        'blocks/intelligent_learning/model/service/groups_members.php',
        'blocks/intelligent_learning/helper/xmlreader.php'
    );

    protected $_server;

    public function setUp() {
        if (!defined('BLOCKS_ILP_TEST')) {
            throw new coding_exception('You must define BLOCKS_ILP_TEST in your config.php to test the groups_members service');
        }

        $validator = new Zend_Validate();
        $validator->addValidator(new mr_server_validate_test());
        $this->_server = new mr_server_rest('blocks_intelligent_learning_model_service_groups_members', 'blocks_intelligent_learning_model_response', $validator);
    }

    public function test_handle_create() {
        global $DB;

        $courseid = $DB->get_field('course', 'id', array('idnumber' => 'ilp123'));
        $groupid  = $DB->get_field('groups', 'id', array('name' => 'Group B', 'courseid' => $courseid));
        $userid   = $DB->get_field('user', 'id', array('username' => 'ilp_student3'));

        groups_remove_member($groupid, $userid);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="add">
        <mapping name="course">ilp123</mapping>
        <mapping name="user">ilp_student3</mapping>
        <mapping name="groupname">Group B</mapping>
    </datum>
</data>
XML;
        $response = $this->_server->handle(array(
            'method' => 'handle',
            'xml' => $xml,
        ), true);

        $this->_server->document($response)
                      ->simpletest_report($response);

        $this->assertTrue($this->_server->is_successful());
        $this->assertTrue(groups_is_member($groupid, $userid));

        groups_remove_member($groupid, $userid);
    }

    public function test_handle_delete() {
        global $DB;

        $courseid = $DB->get_field('course', 'id', array('idnumber' => 'ilp123'));
        $groupid  = $DB->get_field('groups', 'id', array('name' => 'Group B', 'courseid' => $courseid));
        $userid   = $DB->get_field('user', 'id', array('username' => 'ilp_student3'));

        groups_add_member($groupid, $userid);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="drop">
        <mapping name="course">ilp123</mapping>
        <mapping name="user">ilp_student3</mapping>
        <mapping name="groupname">Group B</mapping>
    </datum>
</data>
XML;
        $response = $this->_server->handle(array(
            'method' => 'handle',
            'xml' => $xml,
        ), true);

        $this->_server->document($response)
                      ->simpletest_report($response);

        $this->assertTrue($this->_server->is_successful());
        $this->assertFalse(groups_is_member($groupid, $userid));
    }
}
