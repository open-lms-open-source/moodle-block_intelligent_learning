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
 * @see blocks_intelligent_learning_model_service_groups
 */
require_once($CFG->dirroot.'/blocks/intelligent_learning/model/service/groups.php');

/**
 * Test blocks_intelligent_learning_model_service_groups
 *
 * @package block_intelligent_learning
 * @author Sam Chaffee
 */
class blocks_intelligent_learning_model_service_groups_test extends UnitTestCase {
    public static $includecoverage = array(
        'blocks/intelligent_learning/model/service/groups.php',
        'blocks/intelligent_learning/helper/xmlreader.php'
    );

    protected $_server;

    public function setUp() {
        if (!defined('BLOCKS_ILP_TEST')) {
            throw new coding_exception('You must define BLOCKS_ILP_TEST in your config.php to test the groups service');
        }
        
        $validator = new Zend_Validate();
        $validator->addValidator(new mr_server_validate_test());
        $this->_server = new mr_server_rest('blocks_intelligent_learning_model_service_groups', 'blocks_intelligent_learning_model_response', $validator);
    }

    public function tearDown() {
        global $DB;
        
        $groups = $DB->get_records_select('groups', 'name LIKE \'Simpletest%\'');
        foreach ($groups as $group) {
            groups_delete_group($group);
        }
    }

    public function test_get_groups() {
        $response = $this->_server->handle(array(
            'method' => 'get_groups',
            'field' => 'idnumber',
            'value' => 'ilp123',
        ), true);

        $this->_server->document($response)
                      ->simpletest_report($response);

        $this->assertTrue($this->_server->is_successful());
    }

    public function test_handle_create() {
        global $DB;
        
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="add">
        <mapping name="course">ilp123</mapping>
        <mapping name="name">Simpletest</mapping>
        <mapping name="description">Simpletest description</mapping>
        <mapping name="enrolmentkey">Key</mapping>
        <mapping name="hidepicture">0</mapping>
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
        $this->assertEqual($DB->count_records('groups', array('name' => 'Simpletest')), 1);
    }

    public function test_handle_update() {
        global $DB;

        $courseid = $DB->get_field('course', 'id', array('idnumber' => 'ilp123'));

        $data = (object) array(
            'name' => 'Simpletest2',
            'courseid' => $courseid,
            'hidepicture' => 0,
            'description' => 'Simpletest',
        );
        
        groups_create_group($data);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="update">
        <mapping name="course">ilp123</mapping>
        <mapping name="name">Simpletest2</mapping>
        <mapping name="description">Simpletest description</mapping>
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
        $this->assertEqual($DB->get_field('groups', 'description', array('courseid' => $courseid, 'name' => 'Simpletest2')), 'Simpletest description');
    }

    public function test_handle_delete() {
        global $DB;

        $courseid = $DB->get_field('course', 'id', array('idnumber' => 'ilp123'));

        $data = (object) array(
            'name' => 'Simpletest3',
            'courseid' => $courseid,
            'hidepicture' => 0,
            'description' => 'Simpletest',
        );

        groups_create_group($data);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="drop">
        <mapping name="course">ilp123</mapping>
        <mapping name="name">Simpletest3</mapping>
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
        $this->assertEqual($DB->count_records('groups', array('courseid' => $courseid, 'name' => 'Simpletest3')), 0);
    }
}