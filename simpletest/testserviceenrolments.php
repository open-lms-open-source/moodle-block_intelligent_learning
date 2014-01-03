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
 * @see blocks_intelligent_learning_model_service_course
 */
require_once($CFG->dirroot.'/blocks/intelligent_learning/model/service/enrolments.php');

/**
 * Test blocks_intelligent_learning_model_service_enrolments
 *
 * @package block_intelligent_learning
 * @author Sam Chaffee
 */
class blocks_intelligent_learning_model_service_enrolments_test extends UnitTestCase {
    public static $includecoverage = array(
        'blocks/intelligent_learning/model/service/enrolments.php',
        'blocks/intelligent_learning/helper/xmlreader.php'
    );

    protected $_server;

    public function setUp() {
        if (!defined('BLOCKS_ILP_TEST')) {
            throw new coding_exception('You must define BLOCKS_ILP_TEST in your config.php to test the enrolments service');
        }

        $validator = new Zend_Validate();
        $validator->addValidator(new mr_server_validate_test());
        $this->_server = new mr_server_rest('blocks_intelligent_learning_model_service_enrolments', 'blocks_intelligent_learning_model_response', $validator);
    }

    public function test_handle_add() {
        global $DB;

        $courseid = $DB->get_field('course', 'id', array('idnumber' => 'ilp123'));
        $userid   = $DB->get_field('user', 'id', array('username' => 'ilp_student4'));
        
        // Get an enrol_plugin
        $enrol = enrol_get_plugin('manual');

        // Get the manual enrol instance
        $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));

        // Ensure that this user isn't enrolled
        $enrol->unenrol_user($instance, $userid);
        
        $timestart = time();
        $timeend   = strtotime('next month');


        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="add">
        <mapping name="course">ilp123</mapping>
        <mapping name="user">ilp_student4</mapping>
        <mapping name="role">student</mapping>
        <mapping name="timestart">$timestart</mapping>
        <mapping name="timeend">$timeend</mapping>
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
        $this->assertTrue($DB->count_records('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid)), 1);

        $enrol->unenrol_user($instance, $userid);
    }

    public function test_handle_drop() {
        global $DB;

        $courseid = $DB->get_field('course', 'id', array('idnumber' => 'ilp123'));
        $userid   = $DB->get_field('user', 'id', array('username' => 'ilp_student4'));
        $roleid   = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // Get an enrol_plugin
        $enrol = enrol_get_plugin('manual');

        // Get the manual enrol instance
        $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));

        // Ensure that this user is enrolled
        $enrol->enrol_user($instance, $userid, $roleid);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="drop">
        <mapping name="course">ilp123</mapping>
        <mapping name="user">ilp_student4</mapping>
        <mapping name="role">student</mapping>
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
        $this->assertEqual($DB->count_records('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid)), 0);
    }
}
