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
 * Webservice Connector helper
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */

class block_intelligent_learning_helper_connector extends mr_helper_abstract {

    public function direct() {
    }

    /**
     *
     * @param string - $username The user's username (dirty)
     * @return object - user record
     */
    public function get_user($username) {
        global $DB;

        $username = clean_param($username, PARAM_TEXT);

        if (!$records = $DB->get_records('user', array('username' => $username))) {
            throw new Exception("Failed to lookup username = $username in table user");
        }
        if (count($records) > 1) {
            throw new Exception("Found duplicate records where username = $username in table user");
        }
        return current($records);
    }

    /**
     *
     * @param string - The user's idnumber (dirty)
     * @return object - user record
     */
    public function get_user_by_id($idnumber) {
        global $DB;

        $userid = clean_param($idnumber, PARAM_TEXT);

        if (!$records = $DB->get_records('user', array('idnumber' => $idnumber))) {
            throw new Exception("Failed to lookup user idnumber = $idnumber in table user");
        }
        if (count($records) > 1) {
            throw new Exception("Found duplicate records where idnumber = $idnumber in table user");
        }
        return current($records);
    }

    /**
     * Get a user's courses
     *
     * @param object $user User record object
     * @param mixed $course The course to fetch (idnumber or empty for all courses)
     * @return array
     */
    public function get_courses($user, $idnumber) {
        global $DB;

        $idnumber = clean_param($idnumber, PARAM_TEXT);

        if (empty($idnumber)) {
            if (!$courses = enrol_get_users_courses($user->id, true, '*')) {
                $courses = array();
            }
        } else if ($courses = $DB->get_records('course', array('idnumber' => $idnumber))) {
            foreach ($courses as $course) {
                if ($course->id == SITEID) {
                    throw new Exception("Cannot access site course (idnumber = $course->idnumber)");
                }
                $context = context_course::instance($course->id);
                if (!$course->visible and !has_capability('moodle/course:viewhiddencourses', $context, $user->id)) {
                    throw new Exception("User (username = $user->username) cannot view hidden course (idnumber = $course->idnumber)");
                }
                if (!is_enrolled($context, $user)) {
                    throw new Exception("User (username = $user->username) cannot view course (idnumber = $course->idnumber)");
                }
            }
        } else {
            throw new Exception("Invalid course idnumber passed: $idnumber");
        }

        // Exclude courses that are meta-enrollment to other courses.
        $courses = $this->exclude_meta_enrollments($courses);

        // Exclude courses that have a start date that is older than the max number of days specified in config.
        $courses = $this->exclude_old_courses($courses);

        return $courses;
    }

    /**
     * Get recent activity for courses
     *
     * @param int $timestart Look for activity after this time
     * @param array $courses An array of course objects
     * @param bool $collapse Send only most recent update to an activity, count the rest
     * @param int $percourse Limit the number of activities per course
     * @return array
     */
    public function get_recent_activity($timestart, $courses, $collapse = false, $percourse = null) {
        global $CFG, $USER, $DB;

        $recentactivity = array();

        // Param checks.
        if (is_null($timestart) or empty($courses)) {
            return $recentactivity;
        }
        if (!is_array($courses)) {
            $courses = array($courses->id => $courses);
        }
        $timestart = clean_param($timestart, PARAM_INT);

        if ($allmods = $DB->get_records('modules')) {
            foreach ($allmods as $mod) {
                if ($mod->visible) {
                    $modnames[$mod->name] = get_string('modulename', $mod->name);
                }
            }
        } else {
            throw new Exception('No modules are installed!');
        }

        // Gather recent activity.
        foreach ($courses as $course) {
            $modinfo       = get_fast_modinfo($course, $USER->id);
            $viewfullnames = has_capability('moodle/site:viewfullnames', context_course::instance($course->id));
            $activities    = array();
            $index         = 0;

            $params = array($timestart, $course->id);
            $sql = "SELECT l.*, u.firstname, u.lastname, u.picture
                      FROM {log} l
           LEFT OUTER JOIN {user} u ON l.userid = u.id
                     WHERE time > ?
                       AND course = ?
                       AND module = 'course'
                       AND (action = 'add mod' OR action = 'update mod' OR action = 'delete mod')
                  ORDER BY id ASC";

            $logs = $DB->get_records_sql($sql, $params);

            if ($logs) {
                $changelist = array();
                $actions    = array('add mod', 'update mod', 'delete mod');
                $newgones   = array(); // Added and later deleted items.
                foreach ($logs as $key => $log) {
                    $info = explode(' ', $log->info);
                    $itemtosave = null;

                    // Labels are ignored in recent activity.
                    if ($info[0] == 'label') {
                        continue;
                    }
                    // Check for incorrect entry.
                    if (count($info) != 2) {
                        continue;
                    }

                    $modname    = $info[0];
                    $instanceid = $info[1];

                    // INT-1735: Look for 1.9 -> 2.0 upgrade resources.
                    if ($modname === 'resource' && !isset($modinfo->instances[$modname][$instanceid])) {
                        $old = $DB->get_record('resource_old', array('oldid' => $instanceid));

                        // Did we find a resource that was upgraded?
                        if (!empty($old)) {
                            // Yes, found an upgraded resource.
                            $modname = $old->newmodule;
                            $instanceid = $old->newid;
                        }
                    }

                    $userinfo = new stdClass;
                    $userinfo->userid   = $log->userid;
                    $userinfo->fullname = '';
                    $userinfo->picture  = $log->picture;

                    if (!empty($log->firstname) and !empty($log->lastname)) {
                        $a = new stdClass;
                        $a->fullname = fullname($log, $viewfullnames);
                        $a->modname  = get_string('modulename', $modname);
                        $userinfo->fullname = $a->fullname;
                    } else {
                        $a = false;
                    }

                    if ($log->action == 'delete mod') {
                        // Unfortunately we do not know if the mod was visible.
                        if (!array_key_exists($log->info, $newgones)) {
                            if ($a) {
                                $strdeleted = get_string('deletedactivity', 'block_intelligent_learning', $a);
                            } else {
                                $strdeleted = get_string('deletedactivity', 'moodle', get_string('modulename', $modname));
                            }
                            $itemtosave = (object) array(
                                'cmid' => $log->cmid,
                                'type' => $modname,
                                'name' => '',
                                'timestamp' => $log->time,
                                'description_html' => $strdeleted,
                                'description_text' => $strdeleted,
                                'accessible' => '',
                                'user' => $userinfo,
                            );
                            if ($collapse) {
                                $changelist[] = $itemtosave;
                            } else {
                                $changelist[$log->info] = $itemtosave;
                            }
                        }
                    } else {
                        if (!isset($modinfo->instances[$modname][$instanceid])) {
                            if ($log->action == 'add mod') {
                                // Do not display added and later deleted activities.
                                $newgones[$log->info] = true;
                            }
                            continue;
                        }
                        $cm = $modinfo->instances[$modname][$instanceid];
                        if (!$cm->uservisible && (empty($cm->showavailability) ||
                      empty($cm->availableinfo))) {
                            continue;
                        }

                        if ($log->action == 'add mod') {
                            if ($a) {
                                $stradded = get_string('addedactivity', 'block_intelligent_learning', $a);
                            } else {
                                $stradded = get_string('added', 'moodle', get_string('modulename', $modname));
                            }
                            $itemtosave = (object) array(
                                'cmid' => $cm->id,
                                'type' => $modname,
                                'name' => $cm->name,
                                'timestamp' => $log->time,
                                'description_html' => "$stradded:<br /><a href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id={$cm->id}\">".format_string($cm->name, true).'</a>',
                                'description_text' => "$stradded: ".format_string($cm->name, true),
                                'accessible' => $cm->uservisible,
                                'user' => $userinfo,
                            );
                            if ($collapse) {
                                $changelist[] = $itemtosave;
                            } else {
                                $changelist[$log->info] = $itemtosave;
                            }
                        } else if ($log->action == 'update mod' and (($collapse) or (!$collapse and empty($changelist[$log->info])))) {
                            if ($a) {
                                $strupdated = get_string('updatedactivity', 'block_intelligent_learning', $a);
                            } else {
                                $strupdated = get_string('updated', 'moodle', get_string('modulename', $modname));
                            }
                            $itemtosave = (object) array(
                                'cmid' => $cm->id,
                                'type' => $modname,
                                'name' => $cm->name,
                                'timestamp' => $log->time,
                                'description_html' => "$strupdated:<br /><a href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id={$cm->id}\">".format_string($cm->name, true).'</a>',
                                'description_text' => "$strupdated: ".format_string($cm->name, true),
                                'accessible' => $cm->uservisible,
                                'user' => $userinfo,
                            );
                            if ($collapse) {
                                $changelist[] = $itemtosave;
                            } else {
                                $changelist[$log->info] = $itemtosave;
                            }
                        }
                    }
                }
                // Add to main recentactivity array.
                $recentactivity[$course->id] = array_values($changelist);
            }

            $accessible = array();
            foreach ($modinfo->cms as $cm) {
                if (!$cm->uservisible && (empty($cm->showavailability) ||
                      empty($cm->availableinfo))) {
                    continue;
                }
                $lib = "$CFG->dirroot/mod/$cm->modname/lib.php";
                if (file_exists($lib)) {
                    require_once($lib);

                    $get_recent_mod_activity = "{$cm->modname}_get_recent_mod_activity";
                    if (function_exists($get_recent_mod_activity)) {
                        $get_recent_mod_activity($activities, $index, $timestart, $course->id, $cm->id, 0, 0);
                        $accessible[$cm->id] = $cm->uservisible;
                    }
                }
            }

            foreach ($activities as $activity) {
                $print_recent_mod_activity = "{$activity->type}_print_recent_mod_activity";

                if (function_exists($print_recent_mod_activity)) {
                    ob_start();
                    $print_recent_mod_activity($activity, $course->id, true, $modnames, true);
                    $description = ob_get_contents();
                    ob_end_clean();

                    $activity->description_html = $description;
                    $activity->description_text = trim(strip_tags(str_replace(array('</td>', '</div>'), array(' </td>', ' </div>'), $description)));
                    $activity->accessible       = isset($accessible[$activity->cmid]) ? $accessible[$activity->cmid] : '';
                    if (empty($activity->timestamp)) {
                        $activity->timestamp = 0;
                    }
                    $recentactivity[$course->id][] = $activity;
                }
            }
        }

        // Order the recent activity.
        foreach ($recentactivity as $courseid => $activities) {
            // Reorder.
            uasort($activities, create_function('$a, $b', 'return ($a->timestamp == $b->timestamp) ? 0 : (($a->timestamp > $b->timestamp) ? -1 : 1);'));

            $recentactivity[$courseid] = array_values($activities);
        }

        // Return only the most recent update for each activity?
        if ($collapse) {

            foreach ($recentactivity as $courseid => $activities) {
                $collapsedactivity = array();
                $counts = array();
                foreach ($activities as $activity) {
                    // If this cmid is not already in the collapsed acivitiy, add it.
                    // We only want the most recent per cmid.
                    if (!array_key_exists($activity->cmid, $collapsedactivity)) {
                        // Add it.
                        $collapsedactivity[$activity->cmid] = $activity;
                    }

                    // Count it.
                    if (!array_key_exists($activity->cmid, $counts)) {
                        $counts[$activity->cmid] = 1;
                    } else {
                        $counts[$activity->cmid] += 1;
                    }
                }

                // Loop through the counts and add them to the activity.
                foreach ($counts as $cmid => $count) {
                    if (array_key_exists($cmid, $collapsedactivity)) {
                        $collapsedactivity[$cmid]->numberofupdates = $count;
                    }
                }

                $recentactivity[$courseid] = array_values($collapsedactivity);
            }
        }

        // Limit to $percourse.
        if (!is_null($percourse)) {
            if ($percourse < 0) {
                $percourse = 10;
            }
            foreach ($recentactivity as $courseid => $activities) {
                if (count($activities) > $percourse) {
                    $recentactivity[$courseid] = array_slice($activities, 0, $percourse);
                }
            }
        }

        return $recentactivity;
    }

    /*
     * Excludes courses that are meta enrollments to other courses
     * @param $courses - array of courses
     */
    private function exclude_meta_enrollments($courses) {
        global $DB;

        $primarycourses = array();
        foreach ($courses as $course) {
            $children = $DB->get_records('enrol', array('enrol' => 'meta', 'customint1' => $course->id));
            if (count($children) > 0) {
                continue;
            }
            array_push($primarycourses, $course);
        }
        return $primarycourses;
    }

    /*
     * Excludes courses that have a start day older than the max specified
     * @param $courses - array of courses
     * @param $maxdays - maximum number of days from the start date of a class that it should stay in
     * the list.
     */
    private function exclude_old_courses($courses) {

        $currentcourses = array();
        $maxdays = get_config('blocks/intelligent_learning', 'maxnumberofdays');

        if (isset($maxdays) && is_numeric($maxdays) && ($maxdays > 0)) {
            try {
                foreach ($courses as $course) {
                    $daysold = (time() - $course->startdate) / 86400;
                    if ($daysold < $maxdays) {
                        array_push($currentcourses, $course);
                    } else {
                        debugging("Excluding course with id $course->id from courses list because its start date " . date("Y-M-d", $course->startdate) ." is older than $maxdays days old", DEBUG_NORMAL);
                    }
                }
            } catch (moodle_exception $e) {
                // Don't stop the service because of this; log and continue.
                debugging("Error limiting classes to max number of days. " . $e->getMessage(), DEBUG_ERROR);
                $currentcourses = $courses;
            }
        } else {
            $currentcourses = $courses;
        }

        return $currentcourses;
    }

}