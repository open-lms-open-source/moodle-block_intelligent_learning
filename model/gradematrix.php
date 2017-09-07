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

/**
 * Description of gradesmatrix
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */

require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/querylib.php');
require_once("$CFG->dirroot/blocks/intelligent_learning/helper/ilpsislib.php");
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

        // Sorting of user grades.
        if (empty($SESSION->block_intelligent_learning_sorting)) {
            $SESSION->block_intelligent_learning_sorting = (object) array(
                'sort' => $this->sort,
                'order' => $this->order,
            );
        }
        // Update from user requests.
        $SESSION->block_intelligent_learning_sorting->sort  = optional_param('sort', $SESSION->block_intelligent_learning_sorting->sort, PARAM_SAFEDIR);
        $SESSION->block_intelligent_learning_sorting->order = optional_param('order', $SESSION->block_intelligent_learning_sorting->order, PARAM_SAFEDIR);

        // Store locally.
        $this->sort  = $SESSION->block_intelligent_learning_sorting->sort;
        $this->order = $SESSION->block_intelligent_learning_sorting->order;

        // Be sure it is firstname/lastname.
        if ($this->sort != 'firstname' and $this->sort != 'lastname') {
            $this->sort = 'lastname';
        }

        // Be sure it is ASC/DESC.
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

        // Flag to return specifying whether any records were actually saved.
        $saved = false;

        // Get current grades to detect changes.
        $currentgrades = self::singleton($COURSE->id, false)->get_usergrades();
        $mr_db_records = array();

        // Create new mr_db_records for each current user grade.
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

            $fields = array('mt1', 'mt2', 'mt3', 'mt4', 'mt5', 'mt6', 'finalgrade', 'expiredate', 'lastaccess', 'neverattended', 'incompletefinalgrade');

            // Manage last edit user ID and time.
            foreach ($fields as $field) {
                if (property_exists($usergrade, $field)) {
                    if (empty($currentgrades[$usergrade->userid]) or $currentgrades[$usergrade->userid]->$field != $usergrade->$field) {
                        // Flag as changed.
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

        // Query parameters.
        $params = array();

        if (!$course = $DB->get_record('course', array('id' => $this->courseid))) {
            throw new coding_exception("Invalid course ID set: $this->courseid");
        }

        $groupjoin = $groupwhere = $metawhere = '';

        if ($groupid = groups_get_course_group($course, true)) {
            $groupjoin  = " LEFT JOIN {groups_members} gm ON u.id = gm.userid";
            $groupwhere = " AND gm.groupid = :groupid ";

            $params['groupid'] = $groupid;
        } else {
            $groupjoin = $groupwhere = '';
        }

        $metaid = optional_param('meta', 0, PARAM_INT);

        if ($metaid != 0) {
            $metawhere = " AND ra.itemid = :metaid AND ra.component = 'enrol_meta'";
            $params['metaid'] = $metaid;
        }

        $sortorder = $this->get_sort_order('u');

        $context = context_course::instance($this->courseid);
        if ($parents = $context->get_parent_context_ids()) {
            $contextstr = ' IN ('.$context->id.','.implode(',', $parents).')';
        } else {
            $contextstr = ' ='.$context->id;
        }

        $sql = "SELECT DISTINCT u.id as uid, u.firstname, u.lastname, u.idnumber, il.id, il.course, il.userid, il.mt1, il.mt2, il.mt3, il.mt4, il.mt5, il.mt6, il.finalgrade, il.expiredate, il.lastaccess, il.neverattended, il.incompletefinalgrade
                  FROM {user} u
                  JOIN {role_assignments} ra ON u.id = ra.userid
             LEFT JOIN {block_intelligent_learning} il ON u.id = il.userid AND il.course = :courseid
                       $groupjoin
                 WHERE ra.roleid in ($CFG->gradebookroles)$groupwhere $metawhere
                   AND ra.contextid $contextstr " .
            " ORDER BY $sortorder";

        $params['courseid'] = $this->courseid;

        if ($users = $DB->get_records_sql($sql, $params)) {
            $this->usergrades = $users;
        }
    }

    private function load_current_grades() {

        if (!empty($this->usergrades)) {
            $uids = array_keys($this->usergrades);
            $gradeitem = grade_item::fetch_course_item($this->courseid);

            if ($gradeitem->needsupdate) {
                grade_regrade_final_grades($this->courseid);
            }
            $grades = grade_grade::fetch_users_grades($gradeitem, $uids);

            foreach ($this->usergrades as $uid => $usergrade) {

                $this->usergrades[$uid]->currentgrade = new stdClass;
                $currentgrade_realletter = '';
                $currentgrade_letter = '';
                if (array_key_exists($uid, $grades)) {
                    /** @var grade_grade $grade */
                    $grade = $grades[$uid];

                    $gradeitem           = clone($gradeitem);
                    $gradeitem->grademin = $grade->get_grade_min();
                    $gradeitem->grademax = $grade->get_grade_max();

                    $currentgrade_realletter = grade_format_gradevalue($grade->finalgrade, $gradeitem, true, GRADE_DISPLAY_TYPE_REAL_LETTER);
                    $currentgrade_letter = grade_format_gradevalue($grade->finalgrade, $gradeitem, true, GRADE_DISPLAY_TYPE_LETTER);

                }
                $this->usergrades[$uid]->currentgrade->realletter = $currentgrade_realletter;
                $this->usergrades[$uid]->currentgrade->letter = $currentgrade_letter;
            }
        }
    }

    /**
     * 
     * Parse submitted grades and only send to the SIS anything that's changed
     * @param string $courseid - id of the course
     * @param array $usergrades - array of grades in the form
     */
    public static function get_grades_to_send_to_sis($courseid, $usergrades) {

        GLOBAL $COURSE;

        // Get current grades to detect changes.
        $currentgrades = self::singleton($courseid, false)->get_usergrades();

        $ilp_sis_records = array();
        $ilplib = new ilpsislib();
        $gradelock     = get_config('blocks/intelligent_learning', 'gradelock');
        $neverattended = get_config('blocks/intelligent_learning', 'showlastattendance');

        foreach ($usergrades as $usergrade) {
            if ($gradelock) {
                if (!empty($currentgrades[$usergrade->userid]) and !empty($currentgrades[$usergrade->userid]->finalgrade)) {
                    unset($usergrade->finalgrade);
                    // Also unset the two other properties that would be locked along with final grade.
                    if (property_exists($usergrade, 'expiredate')) {
                        unset($usergrade->expiredate);
                    }
                    if (property_exists($usergrade, 'incompletefinalgrade')) {
                        unset($usergrade->incompletefinalgrade);
                    }
                }
                if (empty($usergrade)) {
                    continue;
                }
            }

            $fields = array('mt1', 'mt2', 'mt3', 'mt4', 'mt5', 'mt6', 'finalgrade', 'expiredate', 'lastaccess', 'neverattended', 'incompletefinalgrade');

            $sisgrade = $ilplib->sisgrade($COURSE, $usergrade);
            $sisgrade->requiressisupdate    = $fieldupdated = false;

            foreach ($fields as $field) {
                $fieldupdated = false;
                if (property_exists($usergrade, $field)) {
                    if ((empty($currentgrades[$usergrade->userid]->$field)) and (!empty($usergrade->$field))) {

                        // Add this record to the list of data to be sent to the SIS; this is a new value.
                        $sisgrade->$field              = $usergrade->$field;
                        $sisgrade->requiressisupdate   = $fieldupdated = true;

                    } else if ($currentgrades[$usergrade->userid]->$field != $usergrade->$field) {
                        if (($field == 'neverattended') and (empty($neverattended))) {
                            continue;
                        }
                        /*
                         * Add this record to the list of data to be sent to the SIS,
                         * but check first if the grade went from having a value to being blank as this
                         * has special handling in the grades API.
                         */
                        if (empty($usergrade->$field)) {
                            switch ($field) {
                                case 'mt1':
                                case 'mt2':
                                case 'mt3':
                                case 'mt4':
                                case 'mt5':
                                case 'mt6':
                                case 'finalgrade':
                                case 'incompletefinalgrade':
                                    $sisgrade->$field = "";
                                    break;
                                case 'lastaccess':
                                    $sisgrade->lastaccess = null;
                                    $sisgrade->clearlastattendflag = true;
                                    break;
                                case 'expiredate':
                                    $sisgrade->expiredate = null;
                                    $sisgrade->clearexpireflag = true;
                                    break;
                                default:
                                    $sisgrade->$field = $usergrade->$field;
                                    break;
                            }
                        } else {
                            $sisgrade->$field = $usergrade->$field;
                        }

                        $sisgrade->requiressisupdate = $fieldupdated = true;
                    }

                    if ($fieldupdated) {
                        // Check for additional data required for certain fields; incomplete, expire and final
                        // grades always go together.
                        if (($field == 'incompletefinalgrade') || ($field == 'expiredate') || ($field == 'finalgrade')) {
                            if (!isset($sisgrade->finalgrade) || is_null($sisgrade->finalgrade)) {
                                $sisgrade->finalgrade = $usergrade->finalgrade;
                                if (is_null($sisgrade->finalgrade)) {
                                    $sisgrade->finalgrade = "";
                                }
                            }
                            if (!empty($usergrade->incompletefinalgrade)) {
                                $sisgrade->incompletefinalgrade = $usergrade->incompletefinalgrade;
                            }
                            if (!empty($usergrade->expiredate)) {
                                $sisgrade->expiredate = $usergrade->expiredate;
                            }
                        }
                    }
                }
            }

            if ($sisgrade->requiressisupdate) {
                // Make sure we're sending to SIS the course id number associated with the user's enrollment,
                // which does not necessarily match with the current course for meta-link enrollments.
                $sisgrade->cidnumber = ilpsislib::get_enrol_course_idnumber($COURSE->id, $sisgrade);
                $ilp_sis_records[$sisgrade->userid] = $sisgrade;
            }
        }

        // debugging("Grades to upgrade after matrix out existing grades is: " . print_r($ilp_sis_records, true), DEBUG_NORMAL);

        return $ilp_sis_records;
    }
}