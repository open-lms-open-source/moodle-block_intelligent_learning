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
 * Datatel Grades block final grades controller
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 **/

require_once("$CFG->dirroot/blocks/intelligent_learning/controller/midtermgrades.php");
require_once("$CFG->dirroot/blocks/intelligent_learning/helper/connector.php");
require_once("$CFG->dirroot/blocks/intelligent_learning/helper/ilpsislib.php");
require_once("$CFG->dirroot/blocks/intelligent_learning/helper/ilpapiclient.php");
class block_intelligent_learning_controller_finalgrades extends block_intelligent_learning_controller_midtermgrades {

    public static function add_tabs($controller, &$tabs) {
        if ($controller->helper->gradeperiod() and has_capability('block/intelligent_learning:edit', $controller->get_context())
            and !empty($controller->get_config()->gradebookapp) and $controller->get_config()->gradebookapp == 'moodle') {
            $tabs->add('finalgrades', array('controller' => 'finalgrades', 'action' => 'edit'), null, 3);
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

                    // Make sure that this was a key we're looking for
                    // mt#_# or fg_# or la_#.
                    if (count($keypieces) != 2) {
                        continue;
                    }

                    $userid = $keypieces[1];
                    if (strpos($keypieces[0], 'finalgrade') === 0) {

                        $datum = clean_param($datum, PARAM_TEXT);
                        $datum = strtoupper($datum);

                        if (!$this->check_grade($datum)) {
                            // Add a notification that the grade wasn't valid.
                            $errormsg = $usergrades[$userid]->ufullname . ": " . $datum;
                            $this->notify->bad('notvalidgrade', $errormsg);

                            // Add the key (which is the input id) to an array for later.
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->finalgrade);
                        } else {
                            $usergrades[$userid]->finalgrade = $datum;
                        }

                    } else if (strpos($keypieces[0], 'lastaccess') === 0) {
                        $datum    = clean_param($datum, PARAM_TEXT);
                        $neverkey = "neverattended_$userid";

                        // Check if never attend has been selected as well (Error message is set in never attend processing).
                        if (!empty($data->$neverkey) and !empty($datum)) {
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->lastaccess);

                        } else {
                            try {
                                $ts = $this->helper->date($datum);
                                $usergrades[$userid]->lastaccess = $ts;

                            } catch (moodle_exception $e) {
                                // Add the bad value using $key (which is the input id) to an array for later.
                                $errorelements[$key] = $datum;
                                unset($usergrades[$userid]->lastaccess);
                                $errormsg = $usergrades[$userid]->ufullname . ":" . $e->getMessage();
                                $this->notify->add_string($errormsg);
                            }
                        }

                    } else if (strpos($keypieces[0], 'expiredate') === 0) {

                        $datum = clean_param($datum, PARAM_TEXT);
                        try {
                            $ts = $this->helper->date($datum);
                            $usergrades[$userid]->expiredate = $ts;

                        } catch (moodle_exception $e) {
                            // Add the bad value using $key (which is the input id) to an array for later.
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->expiredate);
                            $errormsg = $usergrades[$userid]->ufullname . ": " . $e->getMessage();
                            $this->notify->add_string($errormsg);
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

                    } else if (strpos($keypieces[0], 'incompletefinalgrade') === 0) {

                        $datum = clean_param($datum, PARAM_TEXT);
                        $datum = strtoupper($datum);

                        if (!$this->check_grade($datum)) {
                            // Add a notification that the grade wasn't valid.
                            $errormsg = $usergrades[$userid]->ufullname . ": " . $datum;
                            $this->notify->bad('notvalidgrade', $errormsg);

                            // Add the key (which is the input id) to an array for later.
                            $errorelements[$key] = $datum;
                            unset($usergrades[$userid]->incompletefinalgrade);
                        } else {
                            $usergrades[$userid]->incompletefinalgrade = $datum;
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
                                        if ($property == 'finalgrade') {
                                            // Unset exp date and incomplete final grade as well.
                                            if (property_exists($usergrades[$erroruser->id], 'incompletefinalgrade')) {
                                                $errorelements['incompletefinalgrade' . '_' . $erroruser->id] = $usergrades[$erroruser->id]->incompletefinalgrade;
                                                unset($usergrades[$erroruser->id]->incompletefinalgrade);
                                            }
                                            if (property_exists($usergrades[$erroruser->id], 'expiredate')) {
                                                if (!empty($usergrades[$erroruser->id]->expiredate)) {
                                                    $errorelements['expiredate' . '_' . $erroruser->id] = $this->helper->date->format($usergrades[$erroruser->id]->expiredate);
                                                    unset($usergrades[$erroruser->id]->expiredate);
                                                } else {
                                                    // Check if it's empty but it's been deleted and unset it as well.
                                                    if ($sisgrades[$erroruser->id]->clearexpireflag) {
                                                        $errorelements['expiredate' . '_' . $erroruser->id] = $usergrades[$erroruser->id]->expiredate;
                                                        unset($usergrades[$erroruser->id]->expiredate);
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        // We don't have enough information to know what specific field failed; unset all.
                                        $fields = array('finalgrade', 'expiredate', 'lastaccess', 'neverattended', 'incompletefinalgrade');
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
                        } else {
                            debugging("No changes to send to SIS for course " . $courseid, DEBUG_NORMAL);
                        }
                    }

                    if (!empty($errorelements)) {
                        $this->notify->bad('ilpapi_error');
                    }
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
                debugging("Unable to save grades. " . $e, DEBUG_NORMAL);
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
        static $showlastattendance = null;
        static $showdefaultincomplete = null;

        if (is_null($showlastattendance)) {
            $showlastattendance = get_config('blocks/intelligent_learning', 'showlastattendance');
        }
        if (is_null($showdefaultincomplete)) {
            $showdefaultincomplete = get_config('blocks/intelligent_learning', 'showdefaultincomplete');
        }

        $usergrade = new stdClass;
        $usergrade->userid = $userid;
        $usergrade->course = $courseid;
        $usergrade->ufullname = null;
        $usergrade->uidnumber = null;
        $usergrade->finalgrade = null;
        $usergrade->expiredate = null;

        if ($showlastattendance) {
            $usergrade->lastaccess    = null;
            $usergrade->neverattended = null;
        }
        if ($showdefaultincomplete) {
            $usergrade->incompletefinalgrade = null;
        }
        return $usergrade;
    }
}