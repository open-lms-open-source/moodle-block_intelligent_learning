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
 * @see blocks_intelligent_learning_model_service_user
 */
require_once($CFG->dirroot.'/blocks/intelligent_learning/model/service/user.php');

/**
 * Test blocks_intelligent_learning_model_service_user
 *
 * @package block_intelligent_learning
 * @author Sam Chaffee
 */
class blocks_intelligent_learning_model_service_user_test extends UnitTestCase {
    public static $includecoverage = array(
        'blocks/intelligent_learning/model/service/user.php',
        'blocks/intelligent_learning/helper/xmlreader.php');

    protected $_server;

    public function setUp() {
        if (!defined('BLOCKS_ILP_TEST')) {
            throw new coding_exception('You must define BLOCKS_ILP_TEST in your config.php to test the user service');
        }

        $validator = new Zend_Validate();
        $validator->addValidator(new mr_server_validate_test());
        $this->_server = new mr_server_rest('blocks_intelligent_learning_model_service_user', 'blocks_intelligent_learning_model_response', $validator);
    }

    public function tearDown() {
        global $DB;

        // Clear any users
        $users = $DB->get_records_select('user', 'username LIKE \'simpletest%\' OR idnumber LIKE \'simpletest%\' OR email LIKE \'simpletest%\'');
        foreach ($users as $user) {
            delete_user($user);
            $DB->delete_records('user', array('id' => $user->id));
        }
    }

    public function test_get_user_course_activities_due_idnumber() {
        global $DB;
        
        $response = $this->_server->handle(array(
            'method' => 'get_user_course_activities_due',
            'username' => 'ilp_student',
            'todate' => strtotime('now + 3 months'),
            'course' => 'ilp123',
        ), true);

        $this->_server->document($response)
                      ->simpletest_report($response);

        $this->assertTrue($this->_server->is_successful());
    }

    public function test_get_user_course_grades() {
        $response = $this->_server->handle(array(
            'method' => 'get_user_course_grades',
        ), true);

        $this->_server->document($response)
                      ->simpletest_report($response);

        $this->assertTrue($this->_server->is_successful());
    }

    public function test_get_user_course_events() {
        $response = $this->_server->handle(array(
            'method' => 'get_user_course_events',
            'username' => 'ilp_student',
            'fromdate' => time(),
            'todate' => strtotime('now + 3 months'),
        ), true);

        $this->_server->document($response)
                      ->simpletest_report($response);

        $this->assertTrue($this->_server->is_successful());
    }

    public function test_get_user_course_recent_activity() {
        $response = $this->_server->handle(array(
            'method' => 'get_user_course_recent_activity',
            'username' => 'ilp_student',
            'course' => 'ilp123',
            'fromdate' => strtotime('last wednesday'),
//            'collapse' => true,
        ), true);

        $this->_server->document($response)
                      ->simpletest_report($response);

        $this->assertTrue($this->_server->is_successful());
    }

    public function test_handle_create() {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="add">
        <mapping name="username">simpletest</mapping>
        <mapping name="password">password</mapping>
        <mapping name="idnumber">simpletest</mapping>
        <mapping name="firstname">simpletest</mapping>
        <mapping name="lastname">simpletest</mapping>
        <mapping name="email">simpletest@m2.local</mapping>
        <mapping name="town">simpletest</mapping>
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
    }

    public function test_handle_update() {
        global $DB, $CFG;

        $userid = $DB->insert_record('user', array(
            'username' => 'simpletest2',
            'password' => '',
            'idnumber' => 'simpeltest2',
            'mnethostid' => $CFG->mnet_localhost_id
        ));
        
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="update">
        <mapping name="username">simpletest2</mapping>
        <mapping name="password">password</mapping>
        <mapping name="idnumber">simpletest2</mapping>
        <mapping name="firstname">simpletest2</mapping>
        <mapping name="lastname">simpletest2</mapping>
        <mapping name="email">simpletest2@m2.local</mapping>
        <mapping name="town">simpletest2</mapping>
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
        $this->assertEqual($DB->get_field('user', 'idnumber', array('username' => 'simpletest2')), 'simpletest2');
    }

    public function test_handle_delete() {
        global $DB, $CFG;

        $userid = $DB->insert_record('user', array(
            'username' => 'simpletest3',
            'password' => '',
            'idnumber' => 'simpeltest3',
            'mnethostid' => $CFG->mnet_localhost_id
        ));

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="drop">
        <mapping name="username">simpletest3</mapping>
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
        $this->assertEqual($DB->count_records('user', array('username' => 'simpletest3')), 0);

    }
}