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
 * Model Response
 *
 * Web service responses for IPL
 *
 * @author Mark Nielsen
 * @package block_intelligent_learning
 */
class blocks_intelligent_learning_model_response extends mr_server_response_abstract {
    /**
     * Customized response structure for Datatel
     */
    public function new_dom() {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $root = $dom->createElement('response');
        $root->setAttribute('method', $this->servicemethod);
        $root->setAttribute('generator', 'zend');
        $root->setAttribute('version', '1.0');

        $status  = $dom->createElement('status');
        $message = $dom->createElement('message');
        $data    = $dom->createElement('data');

        $dom->appendChild($root);
        $root->appendChild($status);
        $root->appendChild($message);
        $root->appendChild($data);

        return $dom;
    }

    /**
     * Customized server faults for Datatel
     */
    public function fault($message) {
        $dom = $this->new_dom();

        // Update the status tag.
        $status = $dom->getElementsByTagName('status')->item(0);
        $status->appendChild($dom->createTextNode(0));

        // Update the message tag.
        $msg = $dom->getElementsByTagName('message')->item(0);
        $msg->appendChild($dom->createTextNode($message));

        return $dom;
    }

    /**
     * Send server headers
     *
     * This method is overridden to ensure that a status code of
     * 200 is sent with every response (including faults)
     *
     * @param Zend_Server_* object
     * @return void
     */
    public function send_headers($server) {
        // We still need to send the content-type header if not sent.
        $contentheader = 'Content-Type: text/xml';

        if (!headers_sent()) {
            $headers = headers_list();

            if (!in_array($contentheader, $headers)) {
                header($contentheader);
            }
        }
    }

    /**
     * Map any Zend responses to ours (EG: Zend server faults)
     */
    public function post_handle($response) {
        // Try to capture Zend faults and map them to our custom faults.
        $xml = simplexml_load_string($response);
        if ($xml->getName() != 'response') {
            $message = @$xml->xpath('//message');
            $status  = @$xml->xpath('//status');

            if ($message and $status) {
                $status = strtolower($status[0]);

                if ((ctype_alpha($status) and $status != 'success') or !((bool) $status)) {
                    $dom = $this->fault((string) $message[0]);
                    $response = $dom->saveXML();
                }
            }
        }
        return $response;
    }

    /**
     * Conform the standard response to Datatel's
     */
    public function standard($response = null, $status = true) {
        $dom = $this->new_dom();

        // Update the status tag.
        $statustag = $dom->getElementsByTagName('status')->item(0);

        if ($status) {
            $statustag->appendChild($dom->createTextNode(1));
        } else {
            $statustag->appendChild($dom->createTextNode(0));
        }
        if (!empty($response)) {
            $data = $dom->getElementsByTagName('data')->item(0);

            if (is_array($response)) {
                $this->array_to_dom($response, $dom, $data);
            } else if (is_string($response)) {
                $data->appendChild($dom->createTextNode($response));
            }
        }

        return $dom;
    }

    /**
     * Generate the response array for recent activity
     *
     * @param object $user The user the recent activity is talored to
     * @param array $courses Array of $user's courses
     * @param array $recentactivity An array of recent activity, keyed on course ID then cmid
     * @return array
     */
    public function get_user_course_recent_activity_response($user, $courses, $recentactivity) {
        global $CFG;

        $response = array();
        foreach ($courses as $course) {
            $courseresponse = array('course' => array(
                'id'         => $course->id,
                'fullname'   => format_string($course->fullname),
                'shortname'  => format_string($course->shortname),
                'idnumber'   => format_string($course->idnumber),
                'url'        => "$CFG->wwwroot/course/view.php?id=$course->id",
                'visible'    => $course->visible,
                'activities' => array(),
            ));

            if (array_key_exists($course->id, $recentactivity)) {
                $activities = $recentactivity[$course->id];

                foreach ($activities as $activity) {
                    $courseresponse['course']['activities'][] = array('activity' => array(
                        'id'              => $activity->cmid,
                        'type'            => ($activity->type == 'assign') ? 'assignment' : $activity->type,
                        'module'          => $activity->type,
                        'name'            => format_string($activity->name),
                        'timestamp'       => $activity->timestamp,
                        'description'     => $activity->description_text,
                        'url'             => "$CFG->wwwroot/mod/$activity->type/view.php?id=$activity->cmid",
                        'numberofupdates' => isset($activity->numberofupdates) ? $activity->numberofupdates : null,
                        'accessible'      => $activity->accessible,
                    ));
                }
            }
            $response[] = $courseresponse;
        }
        return $response;
    }

    /**
     * Generate the response array for activities due
     *
     * @param object $user The user's that the due activities are talored to
     * @param array $courses The user's courses
     * @param array $dueactivities Array of due activities, keyed by course ID
     * @return array
     */
    public function get_user_course_activities_due_response($user, $courses, $dueactivities) {
        global $CFG;

        $response = array();
        foreach ($courses as $course) {
            $courseresponse = array(
                'course' => array(
                    'id'         => $course->id,
                    'fullname'   => format_string($course->fullname),
                    'shortname'  => format_string($course->shortname),
                    'idnumber'   => format_string($course->idnumber),
                    'url'        => "$CFG->wwwroot/course/view.php?id=$course->id",
                    'activities' => array()
                ),
            );

            if (array_key_exists($course->id, $dueactivities)) {
                foreach ($dueactivities[$course->id] as $activity) {
                    $courseresponse['course']['activities'][] = array('activity' => array(
                        'id'              => $activity->cmid,
                        'type'            => $activity->type,
                        'module'          => $activity->module,
                        'name'            => format_string($activity->name),
                        'descriptionhtml' => $activity->descriptionhtml,
                        'descriptiontext' => $activity->descriptiontext,
                        'duedate'         => $activity->duedate,
                        'url'             => "$CFG->wwwroot/mod/$activity->module/view.php?id=$activity->cmid",
                        'accessible'      => $activity->accessible,
                    ));
                }
            }
            $response[] = $courseresponse;
        }
        return $response;
    }

    /**
     * Generate the response array for course events
     *
     * @param object $user The user's that the due activities are talored to
     * @param array $courses The user's courses
     * @param array $events Array of course events, keyed by course ID
     * @return array
     */
    public function get_user_course_events_response($user, $courses, $events) {
        global $CFG;

        require_once($CFG->dirroot.'/calendar/lib.php');

        $response = array();
        foreach ($courses as $course) {
            $courseresponse = array(
                'course' => array(
                    'id'        => $course->id,
                    'fullname'  => format_string($course->fullname),
                    'shortname' => format_string($course->shortname),
                    'idnumber'  => format_string($course->idnumber),
                    'url'       => "$CFG->wwwroot/course/view.php?id=$course->id",
                    'events'    => array(),
                ),
            );
            if (array_key_exists($course->id, $events)) {
                foreach ($events[$course->id] as $event) {
                    if (!empty($event->modulename) or $event->courseid != $course->id) {
                        continue;
                    }
                    $startdate = usergetdate($event->timestart);
                    $calurl    = calendar_get_link_href(new moodle_url(CALENDAR_URL.'view.php', array('view' => 'day', 'course' => $course->id)), $startdate['mday'], $startdate['mon'], $startdate['year']);

                    $courseresponse['course']['events'][] = array('event' => array(
                        'id'          => $event->id,
                        'name'        => format_string($event->name),
                        'timestart'   => $event->timestart,
                        'timeend'     => ($event->timestart + $event->timeduration),
                        'description' => trim(html_to_text(format_text($event->description, $event->format, null, $course->id), 0)),
                        'url'         => $calurl->out(false)."#event_$event->id",
                    ));
                }
            }
            $response[] = $courseresponse;
        }
        return $response;
    }

    /**
     * START - below here are all web service response callbacks
     */

    /**
     * Service: course
     * Method:  handle
     *
     * @param object $course Course object
     * @return DOMDocument
     */
    public function course_handle($course) {
        return $this->standard(array('course' => array(
            'id' => $course->id,
            'idnumber' => format_string($course->idnumber),
        )));
    }

    /**
     * Service: enrol
     * Method:  handle
     *
     * @param object $enrol Enrollment information
     * @return DOMDocument
     */
    public function enrolments_handle($enrol) {
        return $this->standard(array('enrollment' => array(
            'course' => format_string($enrol->course),
            'user' => format_string($enrol->user),
            'role' => format_string($enrol->role)
        )));
    }

    /**
     * Service: role_assign
     * Method:  handle
     *
     * @param object $ra Role assignment information
     * @return DOMDocument
     */
    public function role_assign_handle($ra) {
        return $this->standard(array('roleassign' => array(
            'context' => format_string($ra->context),
            'moodlekey' => format_string($ra->moodlekey),
            'user' => format_string($ra->user),
            'role' => format_string($ra->role),
        )));
    }

    /**
     * Service: user
     * Method:  handle
     *
     * @param object $user User object
     * @return DOMDocument
     */
    public function user_handle($user) {
        return $this->standard(array('user' => array(
            'id' => $user->id,
            'idnumber' => format_string($user->idnumber),
            'username' => format_string($user->username),
        )));
    }

    /**
     * Service: groups
     * Method:  handle
     *
     * @param object $course The course that owns the group
     * @param object $group The group object (only name and id are guaranteed to be set)
     * @return string
     */
    public function groups_handle($course, $group) {
        return $this->standard(array('group' => array(
            'id' => $group->id,
            'name' => format_string($group->name),
            'course' => format_string($course->idnumber),
        )));
    }

    /**
     * Service: groupmembers
     * Method:  handle
     *
     * @param object $course The course that owns the group
     * @param object $user The user being assinged or removed from the group
     * @param object $group The group object
     * @return string
     */
    public function groups_members_handle($course, $user, $group) {
        return $this->standard(array('groupmember' => array(
            'course' => format_string($course->idnumber),
            'user' => format_string($user->username),
            'group' => format_string($group->name),
        )));
    }

    /**
     * Service: user
     * Method:  get_user_course_recent_activity
     *
     * @param object $user The user the recent activity is talored to
     * @param array $courses Array of $user's courses
     * @param array $recentactivity An array of recent activity, keyed on course ID then cmid
     * @return DOMDocument
     */
    public function user_get_user_course_recent_activity($user, $courses, $recentactivity) {
        return $this->standard($this->get_user_course_recent_activity_response($user, $courses, $recentactivity));
    }

    /**
     * Service: user
     * Method:  get_user_course_activities_due
     *
     * @param object $user The user's that the due activities are talored to
     * @param array $courses The user's courses
     * @param array $dueactivities Array of due activities, keyed by course ID
     * @return DOMDocument
     */
    public function user_get_user_course_activities_due($user, $courses, $dueactivities) {
        return $this->standard($this->get_user_course_activities_due_response($user, $courses, $dueactivities));
    }

    /**
     * Service: user
     * Method:  get_user_course_events
     *
     * @param object $user The user's that the due activities are talored to
     * @param array $courses The user's courses
     * @param array $events Array of course events, keyed by course ID
     * @return DOMDocument
     */
    public function user_get_user_course_events($user, $courses, $events) {
        return $this->standard($this->get_user_course_events_response($user, $courses, $events));
    }

    /**
     * Service: user
     * Method:  get_user_course_grades
     *
     * @param array $grades Grade information
     * @return DOMDocument
     */
    public function user_get_user_course_grades($grades, $batchsize, $lastid, $starttime, $endtime) {
        $response = array(
            'courses' => array(),
            'batchsize' => $batchsize,
            'lastid' => $lastid,
            'starttime' => $starttime,
            'endtime' => $endtime,
        );
        foreach ($grades as $grade) {
            if (!isset($response['courses'][$grade->courseid]['course'])) {
                $response['courses'][$grade->courseid]['course'] = array(
                    'id' => $grade->courseid,
                    'idnumber' => $grade->courseidnumber,
                    'users' => array(),
                );
            }
            $response['courses'][$grade->courseid]['course']['users'][$grade->userid]['user'] = array(
                'id' => $grade->userid,
                'username' => $grade->username,
                'idnumber' => $grade->useridnumber,
                'modifiedtime' => $grade->timemodified,
                'midtermgrade1' => $grade->mt1,
                'modifiedtime1' => $grade->mt1timemodified,
                'lastsubmittedby1' => $grade->mt1username,
                'midtermgrade2' => $grade->mt2,
                'modifiedtime2' => $grade->mt2timemodified,
                'lastsubmittedby2' => $grade->mt2username,
                'midtermgrade3' => $grade->mt3,
                'modifiedtime3' => $grade->mt3timemodified,
                'lastsubmittedby3' => $grade->mt3username,
                'midtermgrade4' => $grade->mt4,
                'modifiedtime4' => $grade->mt4timemodified,
                'lastsubmittedby4' => $grade->mt4username,
                'midtermgrade5' => $grade->mt5,
                'modifiedtime5' => $grade->mt5timemodified,
                'lastsubmittedby5' => $grade->mt5username,
                'midtermgrade6' => $grade->mt6,
                'modifiedtime6' => $grade->mt6timemodified,
                'lastsubmittedby6' => $grade->mt6username,
                'finalgrade' => $grade->finalgrade,
                'modifiedtimefinalgrade' => $grade->finalgradetimemodified,
                'lastsubmittedbyfinalgrade' => $grade->finalgradeusername,
                'lastattenddate' => $grade->lastaccess,
                'modifiedtimelastattenddate' => $grade->lastaccesstimemodified,
                'lastsubmittedbylastattenddate' => $grade->lastaccessusername,
                'expiredate' => $grade->expiredate,
                'modifiedtimeexpiredate' => $grade->expiredatetimemodified,
                'lastsubmittedbyexpiredate' => $grade->expiredateusername,
                'neverattended' => $grade->neverattended,
                'modifiedtimeneverattended' => $grade->neverattendedtimemodified,
                'lastsubmittedbyneverattended' => $grade->neverattendedusername,
                'incompletefinalgrade' => $grade->incompletefinalgrade,
                'modifiedtimeincompletefinalgrade' => $grade->incompletefinalgradetimemodified,
                'lastsubmittedbyincompletefinalgrade' => $grade->incompletefinalgradeusername,
            );
        }
        return $this->standard($response);
    }

    /**
     * Service: groups
     * Method:  get_groups
     *
     * @param array $groups Course groups
     * @return DOMDocument
     */
    public function groups_get_groups($groups) {
        $response = array(
            'groups' => array(),
        );

        foreach ($groups as $group) {
            $response['groups'][] = array(
                'group' => array(
                    'courseid' => $group->courseid,
                    'name' => $group->name,
                    'description' => $group->description,
                    'enrolmentkey' => $group->enrolmentkey,
                    'picture' => $group->picture,
                    'hidepicture' => $group->hidepicture,
                    'timecreated' => $group->timecreated,
                    'timemodified' => $group->timemodified,
                ),
            );
        }

        return $this->standard($response);
    }

    /**
     * Service: coursecategories
     * Method:  handle
     *
     * @param object $coursecategories Course Category object
     * @return DOMDocument
     */
    public function coursecategories_handle($coursecategories) {
        return $this->standard(array('coursecategory' => array(
            'id' => $coursecategories->id,
            'name' => $coursecategories->name,
            'parent' => $coursecategories->parent,
        )));
    }
}