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
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/format/lib.php');
require_once("$CFG->dirroot/enrol/meta/locallib.php");

/**
 * Course Service Model
 *
 * @author Mark Nielsen
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */
class blocks_intelligent_learning_model_service_course extends blocks_intelligent_learning_model_service_abstract {
    /**
     * Synced course fields
     *
     * @var array
     */
    private $coursefields = array(
        'shortname',
        'category',
        'fullname',
        'idnumber',
        'summary',
        'format',
        'showgrades',
        'startdate',
        'visible',
        'groupmode',
        'groupmodeforce',
        'enddate',
        'automaticenddate',
    );

    /**
     * Course Provisioning
     *
     * @param string $xml XML with course data
     * @throws Exception
     * @return string
     */
    public function handle($xml) {
        global $DB;

        list($action, $data) = $this->helper->xmlreader->validate_xml($xml, $this);

        if (empty($data['idnumber'])) {
            throw new Exception('No idnumber passed, required');
        }

        // Try to get the course that we are operating on.
        $course = false;

        if ($courseid = $DB->get_field('course', 'id', array('idnumber' => $data['idnumber']))) {
            $course = course_get_format($courseid)->get_course();
        }

        switch($action) {
            case 'create':
            case 'add':
            case 'update':
            case 'change':
                if ($course) {
                    $this->update($course, $data);
                } else {
                    if (empty($data['shortname'])) {
                        throw new Exception('No shortname passed, required when creating a course');
                    }
                    if (empty($data['fullname'])) {
                        throw new Exception('No fullname passed, required when creating a course');
                    }
                    $course = $this->add($data);
                }
                break;
            case 'remove':
            case 'delete':
            case 'drop':
                if ($course and !@delete_course($course, false)) {
                    throw new Exception("Failed to delete course (idnumber = $course->idnumber)");
                }
                break;
            default:
                throw new Exception("Invalid action found: $action.  Valid actions: create, update, change and remove");
        }
        return $this->response->course_handle($course);
    }

    /**
     * Add a course
     *
     * @param array $data Course data
     * @throws Exception
     * @return object
     */
    protected function add($data) {
        global $DB;

        $course = array();
        foreach ($this->coursefields as $field) {
            if (isset($data[$field])) {
                $course[$field] = $data[$field];
            }
        }
        $course   = (object) $course;
        $defaults = array(
            'startdate'      => time() + 3600 * 24,
            'summary'        => get_string('defaultcoursesummary'),
            'format'         => 'weeks',
            'guest'          => 0,
            'idnumber'       => '',
            'newsitems'      => 5,
            'showgrades'     => 1,
            'groupmode'      => 0,
            'groupmodeforce' => 0,
            'visible'        => 1,
            'automaticenddate'	=> 1,
        );

        $courseconfigs = get_config('moodlecourse');
        if (!empty($courseconfigs)) {
            foreach ($courseconfigs as $name => $value) {
                $defaults[$name] = $value;
            }
        }

        // Apply defaults to the course object.
        foreach ($defaults as $key => $value) {
            if (!isset($course->$key) or (!is_numeric($course->$key) and empty($course->$key))) {
                $course->$key = $value;
            }
        }
        
        // If data contains a valid end date then disable the Automatic End Date setting
        if (isset($course->enddate) and is_numeric($course->enddate) and $course->enddate > 0) {
        	$course->automaticenddate = 0;
        }

        // Last adjustments.
        fix_course_sortorder();  // KEEP (Packs sort order).
        unset($course->id);
        $course->category    = $this->process_category($course);
        $course->timecreated = time();
        $course->shortname   = substr($course->shortname, 0, 100);
        $course->sortorder   = $DB->get_field('course', 'COALESCE(MAX(sortorder)+1, 100) AS max', array('category' => $course->category));

        if (isset($course->idnumber)) {
            $course->idnumber = substr($course->idnumber, 0, 100);
        }

        try {
            $courseid = $DB->insert_record('course', $course);
        } catch (dml_exception $e) {
            throw new Exception("Could not create new course idnumber = $course->idnumber");
        }

        // Check if this is a metacourse.
        if (isset($data["children"])) {
            $children = $data["children"];
            $metacourse = $this->process_metacourse($course, $children);
            
            //if parent course is assigned a valid end date, turn off auto end date setting
            if (!is_null($metacourse) and property_exists($metacourse, 'automaticenddate')) {
            	$course->automaticenddate = $metacourse->automaticenddate;
            }
        }

        // Save course format options.
        course_get_format($courseid)->update_course_format_options($course);

        // Create the context so Moodle queries work OK.
        context_course::instance($courseid);

        // Make sure sort order is correct and category paths are created.
        fix_course_sortorder();

        try {
            $course = course_get_format($courseid)->get_course();

            // Create a default section.
            course_create_sections_if_missing($course, 0);

            blocks_add_default_course_blocks($course);
        } catch (dml_exception $e) {
            throw new Exception("Failed to get course object from database id = $courseid");
        }

        return $course;
    }

    /**
     * Update a course
     *
     * @param object $course Current Moodle course
     * @param array $data New course data
     * @throws Exception
     * @return void
     */
    protected function update($course, $data) {
        global $DB;

        // Process category.
        if (isset($data['category'])) {
            $data['category'] = $this->process_category($data);
        }

        $update = false;
        $record = new stdClass;
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->coursefields)) {
                continue;
            }
            if ($key != 'id' and isset($course->$key) and $course->$key != $value) {
                switch ($key) {
                    case 'idnumber':
                    case 'shortname':
                        $record->$key = substr($value, 0, 100);
                        break;
                    default:
                        $record->$key = $value;
                        break;
                }
                $update = true;
            }
        }
        if ($update) {
            // Make sure this is set properly.
            $record->id = $course->id;
            $record->timemodified = time();

            try {
                $DB->update_record('course', $record);

                // Save course format options.
                course_get_format($course->id)->update_course_format_options($record, $course);
            } catch (dml_exception $e) {
                throw new Exception('Failed to update course with id = '.$record->id);
            }
        }
        // Check if this is a metacourse.
        if (isset($data["children"])) {
            $children = $data["children"];
            $this->process_metacourse($course, $children);
        }
    }

    /**
     * Process the category from the external database
     *
     * @param object|array $course External course
     * @param int $defaultcategory Default category if category lookup fails
     * @throws Exception
     * @return int
     */
    protected function process_category($course, $defaultcategory = null) {
        global $CFG, $DB;

        if (is_array($course)) {
            $course = (object) $course;
        }

        if (isset($course->category) and is_numeric($course->category)) {
            if ($DB->record_exists('course_categories', array('id' => $course->category))) {
                return $course->category;
            }
        } else if (isset($course->category)) {
            // Apply separator.
            $category   = trim($course->category, '|');
            $categories = explode('|', $category);

            $parentid = $depth = 0;
            foreach ($categories as $catname) {  // Meow!
                $depth++;

                //if ($category = $DB->get_record('course_categories', array('name' => $catname, 'parent' => $parentid))) {
                //    $parentid = $category->id;
                if ($coursecategories = $DB->get_records('course_categories', array('name' => $catname, 'parent' => $parentid))) {
                	$category = array_shift($coursecategories);
                    $parentid = $category->id;
                } else {
                    $category = new stdClass();
                    $category->name      = $catname;
                    $category->parent    = $parentid;
                    $category->sortorder = 999;
                    $category->depth     = $depth;

                    try {
                        $category->id = $DB->insert_record('course_categories', $category);
                    } catch (dml_exception $e) {
                        throw new Exception("Could not create the new category: $category->name");
                    }

                    $context = context_coursecat::instance($category->id);
                    $context->mark_dirty();

                    $parentid = $category->id;
                }
            }

            if (!empty($category) and strtolower($category->name) == strtolower(end($categories))) {
                // We found or created our category.
                return $category->id;
            }
        }

        if (!is_null($defaultcategory)) {
            return $defaultcategory;
        }
        return $CFG->defaultrequestcategory;
    }

    /**
     * Processes metacourse handling for the course and its children
     *
     * @param object|array $course External course
     * @param object|array $children List of child courses idnumbers
     * @throws Exception
     * @return int
     */
    protected function process_metacourse($course, $children) {
        global $CFG, $DB;
        $metacourse = null;
        try {
            if (isset($children)) {
                $parentfullname = "";
                $parentcategory = "";
                $parentshortname = "";
                $parentstartdate = time();
                $parentenddate = strtotime('1970-01-01');
                $parentautomaticenddate = 1;

                $childids = explode(',', $children);
                $enrol      = enrol_get_plugin('meta');

                // Make this a metacourse by adding enrollment entries for each of the child courses.
                $metacourse = $DB->get_record('course', array('idnumber' => $course->idnumber), '*', MUST_EXIST);

                $requestchildren = array();

                if (!empty($children)) {
                    // If children is set but empty, that means we are removing all children from the course; skip this.
                    foreach ($childids as $childidnumber) {
                        $child             = $DB->get_record('course', array('idnumber' => $childidnumber), '*', MUST_EXIST);
                        $existingchild     = $DB->get_record('enrol', array('enrol' => 'meta', 'courseid' => $metacourse->id, 'customint1' => $child->id));

                        $parentfullname .= ", " . $child->fullname;
                        $parentstartdate = min(array($parentstartdate, $child->startdate));
                        $parentcategory = $child->category;
                        $parentshortname .= ", " . $child->shortname;
                        //Add latest child course end date to parent, if end date exists 
                        if (property_exists($child, 'enddate')) {
                        	$parentenddate = max(array($parentenddate, $child->enddate));
                        	
                        	//if auto end date setting not turned off already and start/end dates don't match
                        	//then turn setting off
			            	if ($parentautomaticenddate == 1 and (($child->enddate - $child->startdate) > (24*60*60))) {
			            		$parentautomaticenddate = 0; 
			            	}
                        }

                        // Only add if not a duplicate.
                        if (!isset($existingchild->id)) {
                            $eid        = $enrol->add_instance($metacourse, array('customint1' => $child->id));
                            // Hide child - users will only interact with the parent.
                            $child->visible = false;
                            $DB->update_record('course', $child);
                        }
                        array_push($requestchildren, $child->id);
                    }
                }

                // If there are any children that are no longer in the list, remove the meta-link.
                $currentchildren = array();
                $currentchildren = $DB->get_records('enrol', array('enrol' => 'meta', 'courseid' => $metacourse->id), null, '*');
                if (count($requestchildren) != count($currentchildren)) {
                    foreach ($currentchildren as $checkchild) {
                        if (!in_array($checkchild->customint1, $requestchildren)) {
                            // This child is not in the current list; remove the meta link.
                            $eid = $enrol->delete_instance($checkchild);
                        }
                    }
                }

                enrol_meta_sync($metacourse->id);

                // Update the course title, category and start date with the values from the children.
                if (!empty($parentfullname) && ($parentfullname != "")) {
                    $metacourse->fullname = ltrim($parentfullname, ", ");
                    $metacourse->shortname = ltrim(substr($parentshortname, 0, 100), ", ");
                    $metacourse->startdate = $parentstartdate;
                    if ($metacourse->category == $CFG->defaultrequestcategory) {
                        $metacourse->category = $parentcategory;
                    }
        			if (property_exists($metacourse, 'enddate')) {
                    	$metacourse->enddate = $parentenddate;
                    	$metacourse->automaticenddate = $parentautomaticenddate;
                    }
                    $DB->update_record('course', $metacourse);
                }
            }
        } catch (Exception $e) {
            $errormessage = "Error adding child courses $children to metacourse $course->idnumber. " . $e->getMessage();
            debugging($errormessage);
            throw new Exception($errormessage);
        }
        
        return $metacourse;

    }
}
