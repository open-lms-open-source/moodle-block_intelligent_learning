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
 * Datatel Grades block midterm grades controller
 *
 * @author Sam Chaffee
 * @version $Id$
 * @package block_intelligent_learning
 **/

require_once($CFG->dirroot . '/blocks/intelligent_learning/model/gradematrix.php');
class block_intelligent_learning_controller_midtermgrades extends mr_controller_block {

    const SANEDATELOWER = 0;
    const SANEDATEUPPER = 2145945600;

    public function require_capability() {
        require_capability('block/intelligent_learning:edit', $this->get_context());
    }

    /**
     * Access rules
     */
    protected function init() {
        if (!$this->helper->gradeperiod()) {
            throw new moodle_exception('notavailable', 'block_intelligent_learning');
        }
        if (empty($this->config->gradebookapp) or $this->config->gradebookapp != 'moodle') {
            throw new moodle_exception('gradebookapperror', 'block_intelligent_learning');
        }
    }

    public static function add_tabs($controller, &$tabs) {
        if ($controller->helper->gradeperiod() and has_capability('block/intelligent_learning:edit', $controller->get_context())
            and !empty($controller->get_config()->gradebookapp) and $controller->get_config()->gradebookapp == 'moodle') {
            $tabs->add('midtermgrades', array('controller' => 'midtermgrades', 'action' => 'edit'), null, 1);
        }
    }

    /**
     * Sets up the gradematrix form
     *
     * @global object $COURSE
     * @return string
     */
    public function edit_action() {
        global $COURSE;

        $gradematrix = block_intelligent_learning_model_gradematrix::singleton($COURSE->id);
        $gmhelper    = new mr_helper('blocks/intelligent_learning');

        return $gmhelper->gradematrix($gradematrix, $this->name);
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

            $idindex = 0;
            foreach ($data->uid as $userid) {
                $usergrades[$userid] = $this->new_usergrade($userid, $courseid);
                $usergrades[$userid]->uidnumber = $data->uidnumber[$idindex];
                $usergrades[$userid]->ufullname = $data->ufullname[$idindex];
                $idindex++;
            }

            foreach ($data as $key => $datum) {
                if (!is_array($datum) and trim($datum) != '') {
                    $keypieces = explode('_', $key);

                    // Mmake sure that this was a key we're looking for.
                    // Mt#_# or fg_# or la_#.
                    if (count($keypieces) != 2) {
                        continue;
                    }

                    $userid = $keypieces[1];

                    $property = $keypieces[0];

                    if (strpos($keypieces[0], 'mt') === 0) {

                        $datum = clean_param($datum, PARAM_TEXT);
                        $datum = strtoupper($datum);
                        if (!$this->check_grade($datum)) {
                            // Add a notification that the grade wasn't valid.
                            $errormsg = $usergrades[$userid]->ufullname . ": " . $datum;
                            $this->notify->bad('notvalidgrade', $errormsg);

                            // Add the key (which is the input id) to an array for later.
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->$property);
                        } else {
                            $usergrades[$userid]->$property = $datum;
                        }

                    } else if (strpos($keypieces[0], 'lastaccess') === 0) {

                        $datum    = clean_param($datum, PARAM_TEXT);
                        $neverkey = "neverattended_$userid";

                        // Check if never attend has been selected as well (Error message is set in never attend processing).
                        if (!empty($data->$neverkey) and !empty($datum)) {
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->$property);

                            // Check the date.
                        } else {
                            try {
                                $ts = $this->helper->date($datum);
                                $usergrades[$userid]->$property = $ts;

                            } catch (moodle_exception $e) {
                                // Add the bad value using $key (which is the input id) to an array for later.
                                $errorelements[$key] = $datum;
                                unset($usergrades[$userid]->$property);
                                $errormsg = $usergrades[$userid]->ufullname . ": " . $e->getMessage();
                                $this->notify->add_string($errormsg);
                            }
                        }

                    } else if (strpos($keypieces[0], 'neverattended') === 0) {

                        $lastaccesskey = "lastaccess_$userid";

                        if (!empty($data->$lastaccesskey) and !empty($datum)) {
                            $errormsg = $usergrades[$userid]->ufullname . ": " . get_string('neverattenderror', 'block_intelligent_learning');
                            $this->notify->add_string($errormsg);
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->neverattended);
                        } else {
                            $usergrades[$userid]->neverattended = 0;
                            if (!empty($datum)) {
                                // Set the neverattended flag.
                                $usergrades[$userid]->neverattended = 1;

                                // Clear the last date of attendance field.
                                $usergrades[$userid]->lastaccess = null;
                            }
                        }
                    } else {
                        // Shouldn't be here, let's skip this.
                        continue;
                    }
                }
            }

            try {
                $sissystemerror = false;
                $siserrorsflag = false;
                // Update the SIS; then only save grades that were successfully transmited to the SIS.
                $ilpapi = get_config('blocks/intelligent_learning', 'ilpapi_url');
                if (!empty($ilpapi)) {
                    $sisgrades = block_intelligent_learning_model_gradematrix::get_grades_to_send_to_sis($courseid, $usergrades);

                    if (count($sisgrades) > 0) {
                        $sisresults = null;
                        try {
                            $sisresults = ilpsislib::update_sis_grades($sisgrades);
                        } catch (Exception $e) {
                            $sissystemerror = true;
                            // General service error; log and display generic message to user and don't save any grades.
                            $this->notify->bad('ilpapi_service_error');
                            debugging('Error communicating with ILP API: ' . $e->getMessage(), DEBUG_NORMAL);
                        }

                        if (isset($sisresults) and count($sisresults->errors) > 0) {
                            foreach ($sisresults->errors as $er) {
                                $siserrorsflag = true;
                                if (!empty($er->uidnumber)) {

                                    $erroruser = $this->helper->connector->get_user_by_id($er->uidnumber);
                                    $studentname = $erroruser->firstname . ' ' . $erroruser->lastname . ': ';
                                    $usermessage = get_string('ilpapi_error_student', 'block_intelligent_learning', $studentname . $er->message);
                                    $this->notify->add_string($usermessage);

                                    // Remove the failed grades from the matrix to be saved.
                                    $property = $er->property;
                                    if (!empty($property)) {
                                        $errorgrade = $usergrades[$erroruser->id]->$property;
                                        if (ilpsislib::is_date($errorgrade)) {
                                            $errorgrade = $this->helper->date->format($errorgrade);
                                        }

                                        $errorelements[$property . '_' . $erroruser->id] = $errorgrade;
                                        unset($usergrades[$erroruser->id]->$property);
                                    } else {
                                        // We don't have enough information to know what specific field failed; unset all.
                                        $fields = array('mt1', 'mt2', 'mt3', 'mt4', 'mt5', 'mt2', 'expiredate', 'lastaccess', 'neverattended');
                                        foreach ($fields as $prop) {
                                            unset($usergrades[$erroruser->id]->$prop);
                                        }
                                    }
                                } else {
                                    // There's not enough data to show anything to the end user; display a generic message.
                                    $this->notify->bad('ilpapi_generic_error');
                                    break;
                                }
                            }
                        }
                    } else {
                        debugging("No changes to send to SIS for course " . $courseid, DEBUG_NORMAL);
                    }
                }

                if (!empty($errorelements)) {
                    $this->notify->bad('ilpapi_error');
                }

                if (!$sissystemerror) {
                    if (block_intelligent_learning_model_gradematrix::save_grades($usergrades)) {
                        if (!$siserrorsflag) {
                            // If there is an error flag, errors have already been reported; this is a partial save,
                            // don't report success until all grades are updated correctly.
                            $this->notify->good('gradessubmitted');
                        }
                    }
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
     *
     * @param float $grade
     * @return bool
     */
    protected function check_grade($grade) {

        if (isset($this->config->gradevalidatelocalgradescheme)) {
            if ($this->config->gradevalidatelocalgradescheme == "0") {
                return true;
            }
        }

        $gradeletters = grade_get_letters($this->get_context());

        if (in_array($grade, $gradeletters)) {
            return true;
        }
        if (!empty($this->config->extraletters)) {
            $extraletters = explode(',', $this->config->extraletters);
            if (in_array($grade, $extraletters)) {
                return true;
            }
        }
        return false;
    }

    protected function add_errors_to_session($errors) {
        global $SESSION;

        $SESSION->block_intelligent_learning = array();
        $SESSION->block_intelligent_learning = $errors;
    }

    /**
     * Set the message to say when process_action was successful
     *
     * @return void
     */
    protected function notify_changes_saved() {
        $this->notify->good('gradessubmitted');
    }

    /**
     * Create a new blank usergrade object for Midterm grades
     *
     * @param int $userid User id
     * @param int $courseid Course id
     * @return object
     */
    protected function new_usergrade($userid, $courseid) {
        static $mtnum = null;
        static $showlastattendance = null;

        if (is_null($mtnum)) {
            $mtnum = get_config('blocks/intelligent_learning', 'midtermgradecolumns');
        }
        if (is_null($showlastattendance)) {
            $showlastattendance = get_config('blocks/intelligent_learning', 'showlastattendance');
        }
        $usergrade = new stdClass;
        $usergrade->userid = $userid;
        $usergrade->uidnumber = null;
        $usergrade->course = $courseid;

        if ($mtnum) {
            for ($i = 1; $i <= $mtnum; $i++) {
                $field = "mt$i";
                $usergrade->$field = null;
            }
        }
        if ($showlastattendance) {
            $usergrade->lastaccess    = null;
            $usergrade->neverattended = null;
        }
        return $usergrade;
    }
}