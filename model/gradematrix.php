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

/**
 * Description of gradesmatrix
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */

require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/querylib.php');
class block_intelligent_learning_model_gradematrix {

    /**
     *
     * @var block_intelligent_learning_model_gradematrix instance
     */
    private static $instance;

    /**
     *
     * @var int 
     */
    private $courseid;

    /**
     *
     * @var array 
     */
    private $usergrades = array();

    /**
     * User grades SQL sort field
     *
     * @var string
     */
    private $sort = 'lastname';

    /**
     * User grades SQL sort direction
     *
     * @var string
     */
    private $order = 'ASC';

    /**
     * 
     *
     * @param int $courseid The id of the course the matrix is used in
     * 
     */
    private function __construct($courseid, $load_coursegrade = true) {
        global $SESSION;

        $this->courseid = $courseid;

        // Sorting of user grades
        if (empty($SESSION->block_intelligent_learning_sorting)) {
            $SESSION->block_intelligent_learning_sorting = (object) array(
                'sort' => $this->sort,
                'order' => $this->order,
            );
        }
        // Update from user requests
        $SESSION->block_intelligent_learning_sorting->sort  = optional_param('sort', $SESSION->block_intelligent_learning_sorting->sort, PARAM_SAFEDIR);
        $SESSION->block_intelligent_learning_sorting->order = optional_param('order', $SESSION->block_intelligent_learning_sorting->order, PARAM_SAFEDIR);

        // Store locally
        $this->sort  = $SESSION->block_intelligent_learning_sorting->sort;
        $this->order = $SESSION->block_intelligent_learning_sorting->order;

        // Be sure it is firstname/lastname
        if ($this->sort != 'firstname' and $this->sort != 'lastname') {
            $this->sort = 'lastname';
        }

        // Be sure it is ASC/DESC
        if ($this->order != 'ASC' and $this->order != 'DESC') {
            $this->order = 'ASC';
        }

        $this->load_usergrades();
        if ($load_coursegrade) {
            $this->load_current_grades();
        }
    }

    /**
     *
     * @param int $courseid
     * @return block_intelligent_learning_gradesmatrix instance
     */
    public static function singleton($courseid, $load_coursegrades = true) {
        if (empty(self::$instance)) {
            self::$instance = new block_intelligent_learning_model_gradematrix($courseid, $load_coursegrades);
        }

        return self::$instance;
    }

    /**
     * Return the usergrades array - contains the midterm grades, final grades,
     * expiredate, lastaccess, and neverattended values
     *
     * @return array 
     */
    public function get_usergrades() {
        return $this->usergrades;
    }

    /**
     * Determine if there are grades for this course
     *
     * @return boolean - true if there are grades for this course
     */
    public function has_usergrades() {
        return !empty($this->usergrades);
    }

    /**
     * Get the course id
     *
     * @return int - the course id
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Get the number of decimal places in the course grade item
     *
     * @return int - the number of decimal places in the grade item
     */
    public function get_decimals() {
        $grade_item = grade_item::fetch_course_item($this->courseid);

        return $grade_item->get_decimals();
    }

    /**
     * Get user grades SQL sort field
     *
     * @return string
     */
    public function get_sort() {
        return $this->sort;
    }

    /**
     * Get user grades SQL order field
     *
     * @return void
     */
    public function get_order() {
        return $this->order;
    }

    /**
     * Construct sort order SQL
     *
     * @param string $alias User table alias
     * @return string
     */
    public function get_sort_order($alias = '') {
        if (!empty($alias)) {
            $alias .= '.';
        }

        if ($this->sort == 'firstname') {
            return "{$alias}firstname $this->order, {$alias}lastname $this->order";
        } else if ($this->sort == 'lastname') {
            return "{$alias}lastname $this->order, {$alias}firstname $this->order";
        }
        return "{$alias}$this->sort $this->order";
    }

    /**
     *
     * @param array $usergrades
     * @return boolean - were any records saved
     */
    public static function save_grades($usergrades) {
        global $COURSE, $USER, $DB;

        //flag to return specifying whether any records were actually saved
        $saved = false;

        // Get current grades to detect changes
        $currentgrades = self::singleton($COURSE->id, false)->get_usergrades();
        $mr_db_records = array();

        //create new mr_db_records for each current user grade
        foreach ($currentgrades as $userid => $currentgrade) {
            $mr_db_records[$userid] = new mr_db_record('block_intelligent_learning', $currentgrade);
        }
        
        $currenttime   = time();
        $gradelock     = get_config('blocks/intelligent_learning', 'gradelock');
        $couldnotsave  = array();

        foreach ($usergrades as $usergrade) {
            if ($gradelock) {
                if (!empty($mr_db_records[$usergrade->userid]) and !empty($mr_db_records[$usergrade->userid]->finalgrade)) {
                    unset($usergrade->finalgrade);
                }
                if (empty($usergrade)) {
                    continue;
                }
            }

            $fields = array('mt1', 'mt2', 'mt3', 'mt4', 'mt5', 'mt6', 'finalgrade', 'expiredate', 'lastaccess', 'neverattended');

            // Manage last edit user ID and time
            foreach ($fields as $field) {
                if (property_exists($usergrade, $field)) {
                    if (empty($currentgrades[$usergrade->userid]) or $currentgrades[$usergrade->userid]->$field != $usergrade->$field) {
                        // Flag as changed
                        $fielduserid                   = "{$field}userid";
                        $fieldtimemodified             = "{$field}timemodified";
                        $usergrade->$fielduserid       = $USER->id;
                        $usergrade->$fieldtimemodified = $currenttime;
                        $usergrade->timemodified       = $currenttime;
                    }
                }
            }
            $mr_db_records[$usergrade->userid]->set($usergrade);

            $saved = $saved || $mr_db_records[$usergrade->userid]->is_changed();
        }

        $mr_queue = new mr_db_queue();
        $mr_queue->add($mr_db_records);
        $mr_queue->flush();

        return $saved;
    }

    /**
     *
     * @global object $CFG, $DB
     * @return void
     */
    private function load_usergrades() {
        global $CFG, $DB;

        //query parameters
        $params = array();

        if (!$course = $DB->get_record('course', array('id' => $this->courseid))) {
            throw new coding_exception("Invalid course ID set: $this->courseid");
        }
        if ($groupid = groups_get_course_group($course, true)) {
            $groupjoin  = " LEFT JOIN {groups_members} gm ON u.id = gm.userid";
            $groupwhere = " AND gm.groupid = :groupid ";

            $params['groupid'] = $groupid;
        } else {
            $groupjoin = $groupwhere = '';
        }
        $sortorder = $this->get_sort_order('u');

        $sql = "SELECT DISTINCT u.id as uid, u.firstname, u.lastname, u.idnumber, il.id, il.course, il.userid, il.mt1, il.mt2, il.mt3, il.mt4, il.mt5, il.mt6, il.finalgrade, il.expiredate, il.lastaccess, il.neverattended
                  FROM {user} u
                  JOIN {role_assignments} ra ON u.id = ra.userid
             LEFT JOIN {block_intelligent_learning} il ON u.id = il.userid AND il.course = :courseid
                       $groupjoin
                 WHERE ra.roleid in ($CFG->gradebookroles)$groupwhere
                   AND ra.contextid ".get_related_contexts_string(get_context_instance(CONTEXT_COURSE, $this->courseid)).
            " ORDER BY $sortorder";

        $params['courseid'] = $this->courseid;

        if ($users = $DB->get_records_sql($sql, $params)) {
            $this->usergrades = $users;
        }
    }

    private function load_current_grades() {

        if (!empty($this->usergrades)) {
            $uids = array_keys($this->usergrades);
            $grades = grade_get_course_grades($this->courseid, $uids);
            $gradeitem = grade_item::fetch_course_item($this->courseid);

            foreach ($this->usergrades as $uid => $usergrade) {

                $this->usergrades[$uid]->currentgrade = new stdClass;
                $currentgrade_realletter = '';
                $currentgrade_letter = '';
                if ($grades->grades[$uid]) {
                    $currentgrade_realletter = grade_format_gradevalue($grades->grades[$uid]->grade, $gradeitem, true, GRADE_DISPLAY_TYPE_REAL_LETTER);
                    $currentgrade_letter = grade_format_gradevalue($grades->grades[$uid]->grade, $gradeitem, true, GRADE_DISPLAY_TYPE_LETTER);

                }
                $this->usergrades[$uid]->currentgrade->realletter = $currentgrade_realletter;
                $this->usergrades[$uid]->currentgrade->letter = $currentgrade_letter;
            }
        }
    }
}