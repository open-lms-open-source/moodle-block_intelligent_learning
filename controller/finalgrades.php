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
 * Datatel Grades block final grades controller
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 **/

require_once("$CFG->dirroot/blocks/intelligent_learning/controller/midtermgrades.php");
class block_intelligent_learning_controller_finalgrades extends block_intelligent_learning_controller_midtermgrades {

    public static function add_tabs($controller, &$tabs) {
        if ($controller->helper->gradeperiod() and has_capability('block/intelligent_learning:edit', $controller->get_context())
            and !empty($controller->get_config()->gradebookapp) and $controller->get_config()->gradebookapp == 'moodle') {
            $tabs->add('finalgrades', array('controller' => 'finalgrades', 'action' => 'edit'), NULL, 3);
        }
    }

        /**
     * Processes gradematrix form
     *
     * @return void
     */
    public function process_action() {

        if ($data = data_submitted() and confirm_sesskey()) {
            $courseid = required_param('courseid', PARAM_INT);

            $usergrades = array();
            $errorelements = array();

            foreach ($data->uid as $userid) {
                $usergrades[$userid] = $this->new_usergrade($userid, $courseid);
            }

            foreach ($data as $key => $datum) {
                if (!is_array($datum) and trim($datum) != '') {
                    $keypieces = explode('_', $key);

                    //make sure that this was a key we're looking for
                    //mt#_# or fg_# or la_#
                    if (count($keypieces) != 2) {
                        continue;
                    }

                    $userid = $keypieces[1];

                    if (strpos($keypieces[0], 'finalgrade') === 0) {

                        $datum = clean_param($datum, PARAM_TEXT);
                        $datum = strtoupper($datum);
                        if (!$this->check_grade($datum)) {
                            //add a notification that the grade wasn't valid
                            $this->notify->bad('notvalidgrade', $datum);

                            //add the key (which is the input id) to an array for later
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->finalgrade);
                        } else {
                            $usergrades[$userid]->finalgrade = $datum;
                        }

                    } else if (strpos($keypieces[0], 'lastaccess')  === 0) {
                        $datum    = clean_param($datum, PARAM_TEXT);
                        $neverkey = "neverattended_$userid";

                        // Check if never attend has been selected as well (Error message is set in never attend processing)
                        if (!empty($data->$neverkey) and !empty($datum)) {
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->lastaccess);

                        } else {
                            try {
                                $ts = $this->helper->date($datum);
                                $usergrades[$userid]->lastaccess = $ts;

                            } catch (moodle_exception $e) {
                                //add the bad value using $key (which is the input id) to an array for later
                                $errorelements[$key] = $datum;
                                unset($usergrades[$userid]->lastaccess);

                                $this->notify->add_string($e->getMessage());
                            }
                        }

                    } else if (strpos($keypieces[0], 'expiredate') === 0) {

                        $datum = clean_param($datum, PARAM_TEXT);
                        try {
                            $ts = $this->helper->date($datum);
                            $usergrades[$userid]->expiredate = $ts;

                        } catch (moodle_exception $e) {
                            //add the bad value using $key (which is the input id) to an array for later
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->expiredate);

                            $this->notify->add_string($e->getMessage());
                        }

                    } else if (strpos($keypieces[0], 'neverattended') === 0) {

                        $lastaccesskey = "lastaccess_$userid";


                        if (!empty($data->$lastaccesskey) and !empty($datum)) {
                            $this->notify->bad('neverattenderror');
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->neverattended);
                        } else {
                            $usergrades[$userid]->neverattended = 0;
                            if (!empty($datum)) {
                                //set the neverattended flag
                                $usergrades[$userid]->neverattended = 1;

                                //clear the last date of attendance field
                                $usergrades[$userid]->lastaccess = NULL;
                            }
                        }

                    } else {
                        //shouldn't be here, let's skip this
                        continue;
                    }
                }
            }

            try {
                if (block_intelligent_learning_model_gradematrix::save_grades($usergrades)) {
                    $this->notify->good('gradessubmitted');
                }
            } catch (moodle_exception $e) {
                $this->notify->bad('couldnotsave');
            }
        }

        if (!empty($errorelements)) {
            $this->add_errors_to_session($errorelements);
        }

        redirect($this->url->out(false, array('controller' => $this->name, 'action' => 'edit', 'courseid' => $courseid)));
    }

    /**
     * Create a new blank usergrade object for final grades
     */
    protected function new_usergrade($userid, $courseid) {
        static $showlastattendance = NULL;

        if (is_null($showlastattendance)) {
            $showlastattendance = get_config('blocks/intelligent_learning', 'showlastattendance');
        }
        $usergrade = new stdClass;
        $usergrade->userid = $userid;
        $usergrade->course = $courseid;
        $usergrade->finalgrade = NULL;
        $usergrade->expiredate = NULL;

        if ($showlastattendance) {
            $usergrade->lastaccess    = NULL;
            $usergrade->neverattended = NULL;
        }
        return $usergrade;
    }
}