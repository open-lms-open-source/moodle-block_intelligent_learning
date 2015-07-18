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
 */

/**
 * ILP helper functions
 *
 **/

class ilpsislib {
    public function __construct() {
    }

    /**
     *
     * Updates grades in the SIS
     * @param array $sisgrades - array of user grades to be updated in the sis
     */
    public static function update_sis_grades($sisgrades) {

        global $COURSE, $USER, $DB;

        debugging("Will attempt to update grades in the SIS for course id " . $COURSE->idnumber . ' and user ' . $USER->id, DEBUG_NORMAL);

        $gradesendpoint = "api/coursesection/grades";
        $contents = ilpapiclient::build_grades_request_payload($USER->idnumber, $sisgrades);
        $apiclient = new ilpapiclient();
        $apiclient->init();
        $updatedgrades = $apiclient->send_request($gradesendpoint, $contents);

        return $updatedgrades;

    }

    /**
     *
     * A grade record to be sent to the SIS
     * @param $course - the current course    
     * @param $usergrade - current grades for one user
     * @param $uidnumber - idnumber of the user     
     */
    public function sisgrade($course, $usergrade) {

        global $DB;
        $sisgrade = new stdClass;

        $sisgrade->userid = $usergrade->userid;
        $sisgrade->cidnumber = $course->idnumber;
        $sisgrade->uidnumber = $usergrade->uidnumber;
        $sisgrade->mt1 = null;
        $sisgrade->mt2 = null;
        $sisgrade->mt3 = null;
        $sisgrade->mt4 = null;
        $sisgrade->mt5 = null;
        $sisgrade->mt6 = null;
        $sisgrade->finalgrade = null;
        $sisgrade->expiredate = null;
        $sisgrade->lastaccess = null;
        $sisgrade->neverattended = null;
        $sisgrade->incompletefinalgrade = null;
        $sisgrade->requiressisupdate = false;
        $sisgrade->clearexpireflag = false;
        $sisgrade->clearlastattendflag = false;

        return $sisgrade;

    }

    /**
     *
     * Retrieves the id number for the course associated with the user enrollment
     * @param $courseid - id of the current course
     * @param $sisgrade - grade record to be sent to the sis           
     */
    public static function get_enrol_course_idnumber($courseid, $sisgrade) {

        global $DB;

        $children = array();

        // Find the enrollment for this user to determine of the idnumber to be send to the SIS
        // should be the idnumber of the current course or of the meta enroll course.
        $children = $DB->get_records('enrol', array('enrol' => 'meta', 'courseid' => $courseid));
        if (count($children) > 0) {
            // This course has metalink enrollments; find out if this user is associated with a child course.
            foreach ($children as $child) {
                $childenrollment = array();
                $childenrollment = $DB->get_records('user_enrolments', array('userid' => $sisgrade->userid, 'enrolid' => $child->id));
                if (!empty($childenrollment)) {
                    $cidnumber = $DB->get_field('course', 'idnumber', array('id' => $child->customint1));
                    if (!empty($cidnumber)) {
                        return $cidnumber;
                    }
                }
            }
        }
        return $sisgrade->cidnumber;
    }

    /*
     * Returns true if the input is a valid unix date
     */
    public static function is_date($timestamp) {
        return (is_int($timestamp))
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

}
