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

require_once($CFG->dirroot.'/blocks/intelligent_learning/model/service/abstract.php');
/**
 * User service model
 *
 * @author Mark Nielsen
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */
class blocks_intelligent_learning_model_service_user extends blocks_intelligent_learning_model_service_abstract {

    /**
     * Synced user fields
     *
     * @var array
     */
    protected $userfields = array(
        'username',
        'password',
        'idnumber',
        'firstname',
        'lastname',
        'email',
        'auth',
        'icq',
        'skype',
        'yahoo',
        'aim',
        'man',
        'phone1',
        'phone2',
        'institution',
        'address',
        'city',
        'country',
        'lang',
        'timezone',
    );

    /**
     * Get a user's recent activity for a course
     *
     * @param string $username The user's username
     * @param string $course The course's idnumber - if not passed, all user courses are returned
     * @param int $fromdate Unix timestamp of the start date
     * @param bool $collapse Send only most recent update to an activity, count the rest
     * @param int $percourse Limit number of activities per course
     * @return string
     */
    public function get_user_course_recent_activity($username, $course = NULL, $fromdate = NULL, $collapse = false, $percourse = 10) {
        global $USER;

        $user      = $this->helper->connector->get_user($username);
        $courses   = $this->helper->connector->get_courses($user, $course);
        $percourse = clean_param($percourse, PARAM_INT);

        // Switch user global out for cap checks and the like
        $olduser = $USER;
        $USER    = $user;

        $recentactivity = $this->helper->connector->get_recent_activity($fromdate, $courses, $collapse, $percourse);

        // Restore
        $USER = $olduser;

        return $this->response->user_get_user_course_recent_activity($user, $courses, $recentactivity);
    }

    /**
     * Get a user's due activities for a course
     *
     * @param string $username The user's username
     * @param int $todate Unix timestamp of the end date
     * @param string $course The course's idnumber - if not passed, all user courses are returned
     * @param bool $deschtml Include HTML description
     * @return string
     */
    public function get_user_course_activities_due($username, $todate, $course = NULL, $deschtml = true) {
        global $CFG, $DB;

        $todate    = clean_param($todate, PARAM_INT);
        $maxevents = 100;

        $user    = $this->helper->connector->get_user($username);
        $courses = $this->helper->connector->get_courses($user, $course);

        if (!empty($CFG->gradebookroles)) {
            $gradebookroles = explode(',', $CFG->gradebookroles);
        } else {
            $gradebookroles = array();
        }

        $dueactivities = array();
        foreach ($courses as $course) {
            $activities = array();

            // See if they have a gradebook role in the course
            if (!$roles = get_user_roles(get_context_instance(CONTEXT_COURSE, $course->id), $user->id)) {
                // Shouldn't happen, they should already be confirmed to have a role in this course
                unset($courses[$course->id]);
                continue;
            }
            $found = false;
            foreach ($roles as $role) {
                if (in_array($role->roleid, $gradebookroles)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                unset($courses[$course->id]);
                continue;
            }

            $params = array(
                $course->id, CONTEXT_MODULE, $user->id, $todate,
                $course->id, CONTEXT_MODULE, $user->id, $todate,
                $course->id, CONTEXT_MODULE, $user->id, $todate,
                $course->id, CONTEXT_MODULE, $user->id, $todate,
            );

            $sql = "SELECT cm.id AS cmid, m.name AS type, m.name AS module, q.name, q.intro AS descriptionhtml, q.timeclose AS duedate, cm.visible, c.id AS contextid
                      FROM {quiz} q
                INNER JOIN {course_modules} cm ON cm.instance = q.id AND cm.course = ?
                INNER JOIN {modules} m ON cm.module = m.id AND m.name = 'quiz'
                INNER JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = ?
           LEFT OUTER JOIN {quiz_grades} g ON q.id = g.quiz AND g.userid = ?
                     WHERE q.timeclose < ?
                       AND g.id IS NULL
                     UNION ALL
                    SELECT cm.id AS cmid, m.name AS type, m.name AS module, l.name, NULL AS descriptionhtml, l.deadline AS duedate, cm.visible, c.id AS contextid
                      FROM {lesson} l
                INNER JOIN {course_modules} cm ON cm.instance = l.id AND cm.course = ?
                INNER JOIN {modules} m ON cm.module = m.id AND m.name = 'lesson'
                INNER JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = ?
           LEFT OUTER JOIN {lesson_grades} g ON l.id = g.lessonid AND g.userid = ?
                     WHERE l.deadline < ?
                       AND g.id IS NULL
                     UNION ALL
                    SELECT cm.id AS cmid, m.name AS type, m.name AS module, a.name, a.intro AS descriptionhtml, a.timedue AS duedate, cm.visible, c.id AS contextid
                      FROM {assignment} a
                INNER JOIN {course_modules} cm ON cm.instance = a.id AND cm.course = ?
                INNER JOIN {modules} m ON cm.module = m.id AND m.name = 'assignment'
                INNER JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = ?
           LEFT OUTER JOIN {assignment_submissions} s ON a.id = s.assignment AND s.userid = ?
                     WHERE a.timedue < ?
                       AND s.id IS NULL
                     UNION ALL
                    SELECT cm.id AS cmid, 'assignment' AS type, m.name AS module, a.name, a.intro AS descriptionhtml, a.duedate, cm.visible, c.id AS contextid
                      FROM {assign} a
                INNER JOIN {course_modules} cm ON cm.instance = a.id AND cm.course = ?
                INNER JOIN {modules} m ON cm.module = m.id AND m.name = 'assign'
                INNER JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = ?
           LEFT OUTER JOIN {assign_submission} s ON a.id = s.assignment AND s.userid = ?
                     WHERE a.duedate < ?
                       AND s.id IS NULL
                  ORDER BY duedate";

            $activities = $DB->get_records_sql($sql, $params);

            // Only grab when we have activities to process
            if (!empty($activities)) {
                $modinfo = get_fast_modinfo($course, $user->id);
            }

            // Unset hidden activities
            foreach ($activities as $key => $activity) {
                // Check to see if the activity is visible to the user
                if (empty($modinfo->cms[$activity->cmid]) or (!$modinfo->cms[$activity->cmid]->uservisible &&
                    (empty($modinfo->cms[$activity->cmid]->showavailability) ||
                      empty($modinfo->cms[$activity->cmid]->availableinfo)))) {
                    unset($activities[$key]);
                    continue;
                }

                if (!empty($activity->descriptionhtml)) {
                    if (mb_detect_encoding($activity->descriptionhtml) != 'UTF-8') {
                        $activity->descriptionhtml = mb_convert_encoding($activity->descriptionhtml, 'UTF-8');
                    }
                    $html = file_rewrite_pluginfile_urls($activity->descriptionhtml, 'pluginfile.php', $activity->contextid, 'mod_'.$activity->module, 'intro', null);
                    $html = format_text($html, FORMAT_HTML);
                    $text = trim(html_to_text($html, 0));
                } else {
                    $html = $text = '';
                }

                $activities[$key]->descriptionhtml = !empty($deschtml) ? $html : NULL;
                $activities[$key]->descriptiontext = $text;
                $activities[$key]->accessible      = $modinfo->cms[$activity->cmid]->uservisible;
            }

            // Add to our main array
            $dueactivities[$course->id] = $activities;
        }
        return $this->response->user_get_user_course_activities_due($user, $courses, $dueactivities);
    }

    /**
     * Get a user's course calendar events
     *
     * @param string $username The user's username
     * @param int $fromdate Unix timestamp of the start date
     * @param int $todate Unix timestamp of the end date
     * @param string $course The course's idnumber - if not passed, all user courses are returned
     * @return string
     */
    public function get_user_course_events($username, $fromdate, $todate, $course = NULL) {
        global $CFG, $COURSE;

        require_once($CFG->dirroot.'/calendar/lib.php');

        // Test times
        // 1259740800
        // 1259913600

        $fromdate  = clean_param($fromdate, PARAM_INT);
        $todate    = clean_param($todate, PARAM_INT);
        $maxevents = 100;

        $user    = $this->helper->connector->get_user($username);
        $courses = $this->helper->connector->get_courses($user, $course);

        if ($fromdate > $todate) {
            throw new Exception("From date ($fromdate) is greater than to date ($todate)");
        }

        $events       = array();
        $daysinfuture = ($todate - $fromdate) / DAYSECS;

        foreach ($courses as $course) {
            $COURSE = $course;

            $groups = groups_get_user_groups($course->id, $user->id);
            if (!empty($groups[0])) {
                $groups = $groups[0];
            } else {
                $groups = false;
            }
            $events[$course->id] = calendar_get_upcoming(array($course->id), $groups, $user->id, $daysinfuture, 100, $fromdate);
        }
        return $this->response->user_get_user_course_events($user, $courses, $events);
    }

    /**
     * Get grades for Datatel
     *
     * @param int $batchsize The number of grade records to return (No more than 50)
     * @param int $lastid The last grade ID processed
     * @return string
     */
    public function get_user_course_grades($batchsize = 50, $lastid = NULL, $starttime = 0, $endtime = NULL) {
        global $DB;

        $batchsize = clean_param($batchsize, PARAM_INT);
        $starttime = clean_param($starttime, PARAM_INT);

        $sqlparams = array();

        if ($batchsize > 50) {
            $batchsize = 50;
        }
        if (!is_null($lastid)) {
            $lastid    = clean_param($lastid, PARAM_INT);
            $lastidsql = " AND l.id > :lastid";
            $sqlparams['lastid'] = $lastid;
        } else {
            $lastidsql = '';
        }
        if (is_null($endtime)) {
            $endtime = time() - 1;
        } else {
            $endtime = clean_param($endtime, PARAM_INT);
        }
        if ($starttime > $endtime) {
            throw new Exception("The start time ($starttime) cannot be more than the end time ($endtime)");
        }

        $sqlparams = array_merge($sqlparams, array(
            'siteid' => SITEID,
            'starttime' => $starttime,
            'endtime' => $endtime,
        ));
        $sql = "SELECT l.*, l.course AS courseid, u.username, u.idnumber AS useridnumber, c.idnumber AS courseidnumber,
                      u1.username AS mt1username, u2.username AS mt2username, u3.username AS mt3username,
                      u4.username AS mt4username, u5.username AS mt5username, u6.username AS mt6username,
                      u7.username AS finalgradeusername, u8.username AS expiredateusername,
                      u9.username AS lastaccessusername, u9.username AS neverattendedusername
                 FROM {block_intelligent_learning} l
           INNER JOIN {user} u ON l.userid = u.id
           INNER JOIN {course} c ON l.course = c.id
      LEFT OUTER JOIN {user} u1 ON l.mt1userid = u1.id
      LEFT OUTER JOIN {user} u2 ON l.mt2userid = u2.id
      LEFT OUTER JOIN {user} u3 ON l.mt3userid = u3.id
      LEFT OUTER JOIN {user} u4 ON l.mt4userid = u4.id
      LEFT OUTER JOIN {user} u5 ON l.mt5userid = u5.id
      LEFT OUTER JOIN {user} u6 ON l.mt6userid = u6.id
      LEFT OUTER JOIN {user} u7 ON l.finalgradeuserid = u7.id
      LEFT OUTER JOIN {user} u8 ON l.expiredateuserid = u8.id
      LEFT OUTER JOIN {user} u9 ON l.lastaccessuserid = u9.id
      LEFT OUTER JOIN {user} u10 ON l.neverattendeduserid = u10.id
                WHERE l.course != :siteid
                  AND l.timemodified > :starttime
                  AND l.timemodified < :endtime
                      $lastidsql
             ORDER BY l.id ASC";

        try {
            $grades = $DB->get_records_sql($sql, $sqlparams, 0, $batchsize);
        } catch (dml_exception $e) {
            throw new Exception("Couldn't read grades from database");
        }

        if (!$grades) {
            $grades = array();
            $lastid = '';
        } else {
            $last   = end($grades);
            $lastid = $last->id;
        }
        return $this->response->user_get_user_course_grades($grades, $batchsize, $lastid, $starttime, $endtime);
    }

    /**
     * User Provisioning
     *
     * @param string $xml XML Data
     * @return string
     */
    public function handle($xml) {
        global $CFG, $DB;

        list($action, $data) = $this->helper->xmlreader->validate_xml($xml, $this);

        if (empty($data['username'])) {
            throw new Exception('No username passed, required');
        }
        $username = clean_param($data['username'], PARAM_TEXT);
        $username = moodle_strtolower($username);

        if (mb_strlen($username) > 100) {
            throw new Exception("The username is over 100 characters long and cannot be used by Moodle: $username");
        }
        // Update data array
        $data['username'] = $username;

        // Try to get user that we are operating on
        $user = $DB->get_record('user', array('mnethostid' => $CFG->mnet_localhost_id, 'username' => $username));

        switch($action) {
            case 'create':
            case 'add':
            case 'update':
                if ($user) {
                    $this->update($user, $data);
                } else {
                    $user = $this->add($data);
                }
                break;
            case 'delete':
            case 'drop':
                if ($user and !@delete_user($user)) {
                    throw new Exception("Failed to deleted user with username = $user->username and id = $user->id");
                }
                break;
            default:
                throw new Exception("Invalid action found: $action.  Valid actions: create, update and delete");
        }
        return $this->response->user_handle($user);
    }

    /**
     * Add a user
     *
     * @param array $data User data
     * @return int
     */
    private function add($data) {
        global $CFG, $DB;

        $user = array();
        foreach ($this->userfields as $field) {
            if (isset($data[$field])) {
                $user[$field] = $data[$field];
            }
        }

        $user = (object) truncate_userinfo($user);
        $user->modified   = time();
        $user->confirmed  = 1;
        $user->deleted    = 0;
        $user->mnethostid = $CFG->mnet_localhost_id;
        if (empty($user->auth)) {
            $user->auth = 'manual';
        }
        if (empty($user->lang)) {
            $user->lang = $CFG->lang;
        }
        if (isset($user->password)) {
            $user->password = hash_internal_user_password($user->password);
        } else {
            $user->password = '';
        }

        try {
            $userid = $DB->insert_record('user', $user);
        } catch(dml_exception $e) {
            throw new Exception("Failed to insert user with username = $user->username");
        }

        try {
            $user = $DB->get_record('user', array('id' => $userid));
        } catch (dml_exception $e) {
            throw new Exception("Failed to get user object from database id = $userid");
        }

        return $user;
    }

    /**
     * Update a user
     *
     * @param object $user Current user
     * @param array $data New user data
     * @return void
     */
    private function update($user, $data = NULL) {
        global $DB;

        // Truncate first!
        $data = truncate_userinfo($data);

        $update = false;
        $record = new stdClass;
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->userfields)) {
                continue;
            }
            // Special field processing
            switch ($key) {
                case 'password':
                    $value = hash_internal_user_password($value);
                    break;
            }
            if ($key != 'id' and isset($user->$key) and $user->$key != $value) {
                $record->$key = $value;
                $update = true;
            }
        }
        if ($update) {
            // Make sure this is set properly
            $record->id = $user->id;

            try {
                $DB->update_record('user', $record);
            } catch (dml_exception $e) {
                throw new Exception("Failed to update user with username = $user->username and id = $user->id");
            }
        }
    }
}