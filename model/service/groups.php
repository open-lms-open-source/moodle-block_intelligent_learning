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
 * Groups service model
 *
 * @author Mark Nielsen
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */
class blocks_intelligent_learning_model_service_groups extends blocks_intelligent_learning_model_service_abstract {

    protected $mappings = array(
        'course',
        'name',
        'newname',
        'description',
        'enrolmentkey',
        'hidepicture',
    );

    protected $mapoptions = array(
        'course' => array('required' => 1, 'type' => PARAM_TEXT),
        'name' => array('required' => 1, 'type' => PARAM_TEXT),
        'newname' => array('type' => PARAM_TEXT),
        'description' => array('type' => PARAM_CLEAN),
        'enrolmentkey' => array('type' => PARAM_TEXT),
        'hidepicture' => array('type' => PARAM_BOOL),
    );

    protected $actions = array(
        'create',
        'add',
        'update',
        'delete',
        'drop',
    );

    /**
     * Get groups in a course
     *
     * @param string $value The course value to lookup
     * @param string $field The course field to match the value against, can be id, shortname or idnumber
     * @return string
     */
    public function get_groups($value, $field = 'idnumber') {
        global $DB;

        $field  = clean_param($field, PARAM_ALPHA);

        try {
            $courseid = $DB->get_field('course', 'id', array($field => $value), MUST_EXIST);
        } catch (Exception $e) {
            throw new Exception("Failed to lookup course where $field = $value.  Perhaps the the field/value pair is not unique or the course doesn't exist");
        }
        return $this->response->groups_get_groups(
            groups_get_all_groups($courseid)
        );
    }

    /**
     * Groups Provisioning
     *
     * @param string $xml XML data
     * @return string
     */
    public function handle($xml) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/group/lib.php');

        list($action, $data) = $this->helper->xmlreader->validate_xml($xml, $this);

        // TODO: Have data come back as an object?
        $data = (object) $data;

        if (!$course = $DB->get_record('course', array('idnumber' => $data->course))) {
            throw new Exception('Passed course doesn\'t exist: '.$data->course);
        }
        $data->courseid = $course->id;
        unset($data->course);

        if (property_exists($data, 'hidepicture') and is_null($data->hidepicture)) {
            $data->hidepicture = 0;
        }
        $group = $DB->get_record('groups', array('name' => $data->name, 'courseid' => $data->courseid));

        switch ($action) {
            case 'create':
            case 'add':
            case 'update':
                // Apply rename.
                if (!empty($data->newname)) {
                    $data->name = $data->newname;
                }
                unset($data->newname);

                if ($group) {
                    $data->id = $group->id;
                    if (!groups_update_group($data)) {
                        throw new Exception("Failed to update group name = $data->name and course ID = $data->courseid");
                    }
                } else {
                    if (!$groupid = groups_create_group($data) or !$group = $DB->get_record('groups', array('id' => $groupid))) {
                        throw new Exception("Failed to create new group name = $data->name and course ID = $data->courseid");
                    }
                }
                break;
            case 'delete':
            case 'drop':
                if ($group) {
                    if (!groups_delete_group($group->id)) {
                        throw new Exception("Failed to delete group name = $data->name and course ID = $data->courseid");
                    }
                }
                break;
        }
        if (!$group) {
            $group = new stdClass;
            $group->id   = '';
            $group->name = $data->name;
        }
        return $this->response->groups_handle($course, $group);
    }
}
