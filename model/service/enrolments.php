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

require_once($CFG->dirroot.'/blocks/intelligent_learning/model/service/abstract.php');
/**
 * Enrolments Service Model
 *
 * @author Mark Nielsen
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */
class blocks_intelligent_learning_model_service_enrolments extends blocks_intelligent_learning_model_service_abstract {

    /**
     * Enrollment Provisioning
     *
     * @param string $xml XML data
     * @return string
     */
    public function handle($xml) {
        global $DB;

        list($action, $data) = $this->helper->xmlreader->validate_xml($xml, $this);

        // Required fields.
        if (empty($data['course'])) {
            throw new Exception('No course passed, required');
        }
        if (empty($data['user']) && empty($data['idnumber'])) {
            throw new Exception('Either user or idnumber is required');
        }
        if (empty($data['role'])) {
            throw new Exception('No role passed, required');
        }

        // Clean.
        $role   = clean_param($data['role'], PARAM_TEXT);
        $user   = clean_param($data['user'], PARAM_TEXT);
        if (isset($data['idnumber'])) {
            $idnumber   = clean_param($data['idnumber'], PARAM_TEXT);
        }
        $course = clean_param($data['course'], PARAM_TEXT);

        // Grab data from database.
        if (!$roleid = $DB->get_field('role', 'id', array('shortname' => $role))) {
            throw new Exception('Passed role doesn\'t exist: '.$role);
        }
        if (!empty($data['user'])) {
            if (!$userid = $DB->get_field('user', 'id', array('username' => $user))) {
                throw new Exception('Passed user doesn\'t exist: '.$user);
            }
        } else {
            if (!$userid = $DB->get_field('user', 'id', array('idnumber' => $idnumber))) {
                throw new Exception('Passed user doesn\'t exist: '.$user);
            }
        }
        if (!$courseobj = $DB->get_record('course', array('idnumber' => $course))) {
            throw new Exception('Passed course doesn\'t exist: '.$course);
        }

        // Get an enrol_plugin.
        if (!$enrol = enrol_get_plugin('manual')) {
            throw new Exception('Enrollment plugin does not exist');
        }

        // Ensure that a enrol instance exists.
        $enrol->add_instance($courseobj);

        // Get the manual enrol instance.
        //$instance = $DB->get_record('enrol', array('courseid' => $courseobj->id, 'enrol' => 'manual'), '*', MUST_EXIST);
    	if ($instances = $DB->get_records('enrol', array('courseid'=>$courseobj->id, 'enrol'=>'manual'))) {
        	$instance = array_shift($instances);
        } else {
            throw new Exception('Manual enrollment instance does not exist');
        }

        switch ($action) {
            case 'add':
                if (!empty($data['timestart'])) {
                    $timestart = clean_param($data['timestart'], PARAM_INT);
                } else {
                    $timestart = 0;
                }
                if (!empty($data['timeend'])) {
                    $timeend = clean_param($data['timeend'], PARAM_INT);
                } else {
                    $timeend = 0;
                }
                if ($timestart != 0 and $timeend != 0 and $timestart > $timeend) {
                    throw new Exception("Invalid enrollment start time greater than end time: role = $role user = $user course = $course timestart = $timestart timeend = $timeend");
                }
                try {
                    $enrol->enrol_user($instance, $userid, $roleid, $timestart, $timeend);
                } catch (Exception $e) {
                    throw new Exception('Failed to assigned role');
                }
                break;
            case 'drop':
                try {
                    $enrol->unenrol_user($instance, $userid);
                } catch (Exception $e) {
                    throw new Exception('Failed to removed role');
                }
                break;
            default:
                throw new Exception("Invalid action found: $action. Valid actions: add and drop");
                break;
        }
        if (!empty($user)) {
            return $this->response->enrolments_handle((object) array('role' => $role, 'user' => $user, 'course' => $course));
        } else {
            return $this->response->enrolments_handle((object) array('role' => $role, 'user' => $idnumber, 'course' => $course));
        }
    }
}