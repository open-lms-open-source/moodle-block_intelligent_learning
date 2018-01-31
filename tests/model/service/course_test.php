<?php

global $CFG;

require($CFG->dirroot.'/local/mr/bootstrap.php');
require_once($CFG->dirroot.'/blocks/intelligent_learning/model/service/course.php');
require_once($CFG->dirroot.'/blocks/intelligent_learning/model/response.php');

class blocks_intelligent_learning_model_service_course_test extends advanced_testcase {
    protected function setUp() {
        $this->resetAfterTest();
    }

    public function test_add() {
        global $DB;

        $data = array(
            'shortname'			=> 'testphpunit',
            'category'			=> 'testphpunit|testphpunit2',
            'fullname'			=> 'testphpunitfullname',
            'idnumber'			=> 'testphpunitidnumber',
            'summary'			=> 'testphpunitsummary',
            'format'			=> 'weeks',
            'showgrades'		=> '1',
            'startdate'			=> time() - (5 * DAYSECS),
            'visible'			=> '1',
            'groupmode'			=> '0',
            'groupmodeforce'	=> '0',
        );

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockForAbstractClass('mr_server_response_abstract', array(), '', false);

        $service = new blocks_intelligent_learning_model_service_course($server, $response);

        $reflection = new ReflectionMethod('blocks_intelligent_learning_model_service_course', 'add');
        $reflection->setAccessible(true);

        $course = $reflection->invoke($service, $data);

        foreach ($data as $name => $value) {
            $this->assertTrue(property_exists($course, $name));

            if ($name == 'category') {
                $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'testphpunit2'), MUST_EXIST);
                $this->assertEquals($categoryid, $course->category);
            } else {
                $this->assertEquals($value, $course->$name);
            }
        }
    }

    public function test_update() {

        $course = $this->getDataGenerator()->create_course();

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockForAbstractClass('mr_server_response_abstract', array(), '', false);

        $service = new blocks_intelligent_learning_model_service_course($server, $response);

        $reflection = new ReflectionMethod('blocks_intelligent_learning_model_service_course', 'update');
        $reflection->setAccessible(true);

        $data = array(
            'summary' => 'blocks_intelligent_phpunittest',
            'showgrades' => '0'
        );

        $reflection->invoke($service, $course, $data);

        $updatedcourse = course_get_format($course->id)->get_course();

        foreach ($data as $name => $value) {
            $this->assertTrue(property_exists($updatedcourse, $name));
            $this->assertEquals($value, $updatedcourse->$name);
        }
    }

    public function test_add_enddate() {
        global $DB;
        $enddate = time();

        $data = array(
            'shortname'			=> 'testphpunit',
            'category'			=> 'testphpunit|testphpunit2',
            'fullname'			=> 'testphpunitfullname',
            'idnumber'			=> 'testphpunitidnumber',
            'summary'			=> 'testphpunitsummary',
            'format'			=> 'weeks',
            'showgrades'		=> '1',
            'startdate'			=> time() - (5 * DAYSECS),
            'visible'			=> '1',
            'groupmode'			=> '0',
            'groupmodeforce'	=> '0',
            'enddate'			=> $enddate,
        );

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockForAbstractClass('mr_server_response_abstract', array(), '', false);

        $service = new blocks_intelligent_learning_model_service_course($server, $response);

        $reflection = new ReflectionMethod('blocks_intelligent_learning_model_service_course', 'add');
        $reflection->setAccessible(true);

        $course = $reflection->invoke($service, $data);
        
        if (property_exists($course, 'enddate')) {
        	$this->assertEquals($enddate, $course->enddate);
        }
        
        if (property_exists($course, 'automaticenddate')) {
        	$this->assertEquals('0', $course->automaticenddate);
        }
    }

    public function test_add_invalidenddate() {
        global $DB;
        $enddate = strtotime('0001-01-01');

        $data = array(
            'shortname'			=> 'testphpunit',
            'category'			=> 'testphpunit|testphpunit2',
            'fullname'			=> 'testphpunitfullname',
            'idnumber'			=> 'testphpunitidnumber',
            'summary'			=> 'testphpunitsummary',
            'format'			=> 'weeks',
            'showgrades'		=> '1',
            'startdate'			=> time() - (5 * DAYSECS),
            'visible'			=> '1',
            'groupmode'			=> '0',
            'groupmodeforce'	=> '0',
            'enddate'			=> $enddate,
        );

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockForAbstractClass('mr_server_response_abstract', array(), '', false);

        $service = new blocks_intelligent_learning_model_service_course($server, $response);

        $reflection = new ReflectionMethod('blocks_intelligent_learning_model_service_course', 'add');
        $reflection->setAccessible(true);

        $course = $reflection->invoke($service, $data);
        
        if (property_exists($course, 'automaticenddate')) {
        	$this->assertEquals('1', $course->automaticenddate);
        }
    }

    public function test_update_showgrades_omitted() {
        $course = $this->getDataGenerator()->create_course(array('showgrades' => '0'));

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockForAbstractClass('mr_server_response_abstract', array(), '', false);

        $service = new blocks_intelligent_learning_model_service_course($server, $response);

        $reflection = new ReflectionMethod('blocks_intelligent_learning_model_service_course', 'update');
        $reflection->setAccessible(true);

        $data = array(
            'summary' => 'blocks_intelligent_phpunittest'
        );

        $reflection->invoke($service, $course, $data);

        $updatedcourse = course_get_format($course->id)->get_course();
        $this->assertEquals('0', $updatedcourse->showgrades);
    }

    public function test_update_showgrades_only() {
        $course = $this->getDataGenerator()->create_course(array('showgrades' => '1'));

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockForAbstractClass('mr_server_response_abstract', array(), '', false);

        $service = new blocks_intelligent_learning_model_service_course($server, $response);

        $reflection = new ReflectionMethod('blocks_intelligent_learning_model_service_course', 'update');
        $reflection->setAccessible(true);

        $data = array(
            'showgrades' => '0',
        );

        $reflection->invoke($service, $course, $data);

        $updatedcourse = course_get_format($course->id)->get_course();
        $this->assertEquals('0', $updatedcourse->showgrades);
    }

    public function test_add_metacourse() {
        global $DB;
        $enddate = time();

        $course1data = array(
            'shortname'			=> 'testphpunit1',
            'fullname'			=> 'testphpunit1fullname',
            'idnumber'			=> 'testphpunit1idnumber',
            'summary'			=> 'testphpunit1summary',
            'format'			=> 'weeks',
            'showgrades'		=> '1',
            'startdate'			=> time() - (5 * DAYSECS),
            'visible'			=> '1',
            'groupmode'			=> '0',
            'groupmodeforce'	=> '0',
            'enddate'			=> time() - (2 * DAYSECS),
        );

        $course2data = array(
            'shortname'			=> 'testphpunit2',
            'fullname'			=> 'testphpunit2fullname',
            'idnumber'			=> 'testphpunit2idnumber',
            'summary'			=> 'testphpunit2summary',
            'format'			=> 'weeks',
            'showgrades'		=> '1',
            'startdate'			=> time() - (7 * DAYSECS),
            'visible'			=> '1',
            'groupmode'			=> '0',
            'groupmodeforce'	=> '0',
            'enddate'			=> $enddate,
        );

        $metacoursedata = array(
            'shortname'			=> 'testphpunitparent',
            'fullname'			=> 'testphpunitparent',
            'idnumber'			=> 'testphpunitparent',
            'enrollable'		=> '1',
            'visible'			=> '1',
            'children'			=> 'testphpunit1idnumber,testphpunit2idnumber',
        );

        $course1 = $this->getDataGenerator()->create_course($course1data);
        $course2 = $this->getDataGenerator()->create_course($course2data);
        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockForAbstractClass('mr_server_response_abstract', array(), '', false);

        $service = new blocks_intelligent_learning_model_service_course($server, $response);

        $reflection = new ReflectionMethod('blocks_intelligent_learning_model_service_course', 'add');
        $reflection->setAccessible(true);

        $metacourse = $reflection->invoke($service, $metacoursedata);
        
        //Assertions
        $this->assertEquals($metacoursedata['idnumber'], $metacourse->idnumber);
        $this->assertEquals('testphpunit1, testphpunit2', $metacourse->shortname);
        $this->assertEquals('testphpunit1fullname, testphpunit2fullname', $metacourse->fullname);
        $this->assertEquals($course1->category, $metacourse->category);
        $this->assertEquals($course2data['startdate'], $metacourse->startdate);
        
        if (property_exists($metacourse, 'enddate')) {
        	$this->assertEquals($enddate, $metacourse->enddate);
        }
    }

    public function test_handle_create() {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="create">
        <mapping name="shortname">testphpunitidnumber</mapping>
        <mapping name="idnumber">testphpunitidnumber</mapping>
        <mapping name="fullname">testphpunitidnumber</mapping>
        <mapping name="category">testphpunitidnumber</mapping>
    </datum>
</data>
XML;

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockBuilder('blocks_intelligent_learning_model_response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->once())
            ->method('course_handle')
            ->withAnyParameters();

        $service = $this->getMock('blocks_intelligent_learning_model_service_course', array('add'), array($server, $response));

        $service->expects($this->once())
            ->method('add')
            ->withAnyParameters()
            ->will($this->returnValue(new stdClass()));

        $service->handle($xml);
    }

    public function test_handle_update() {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="update">
        <mapping name="idnumber">testphpunitidnumber</mapping>
        <mapping name="fullname">simpletestTwo</mapping>
        <mapping name="category">simpletest</mapping>
    </datum>
</data>
XML;

        $this->getDataGenerator()->create_course(array('idnumber' => 'testphpunitidnumber'));

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockBuilder('blocks_intelligent_learning_model_response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->once())
            ->method('course_handle')
            ->withAnyParameters();

        $service = $this->getMock('blocks_intelligent_learning_model_service_course', array('update'), array($server, $response));

        $service->expects($this->once())
            ->method('update')
            ->withAnyParameters();

        $service->handle($xml);
    }

    public function test_handle_delete() {
        global $DB;

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data>
    <datum action="delete">
        <mapping name="idnumber">testphpunitidnumber</mapping>
        <mapping name="fullname">simpletestTwo</mapping>
        <mapping name="category">simpletest</mapping>
    </datum>
</data>
XML;

        $course = $this->getDataGenerator()->create_course(array('idnumber' => 'testphpunitidnumber'));

        $server   = $this->getMockForAbstractClass('mr_server_abstract', array(), '', false);
        $response = $this->getMockBuilder('blocks_intelligent_learning_model_response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->once())
            ->method('course_handle')
            ->withAnyParameters();

        $service = new blocks_intelligent_learning_model_service_course($server, $response);
        $service->handle($xml);

        $this->assertFalse($DB->record_exists('course', array('id' => $course->id)));
    }
}