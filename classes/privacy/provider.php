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
 * @copyright Copyright (c) 2019 Ellucian
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package block_intelligent_learning
 * @author Ellucian
 */
 
namespace block_intelligent_learning\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\writer;

// This plugin does store personal user data. 
class provider implements 
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {
 
	public static function get_metadata(collection $collection) : collection {
 
		$collection->add_database_table(
			'block_intelligent_learning',
			[
				'userid' => 'privacy:metadata:block_intelligent_learning:userid',
				'course' => 'privacy:metadata:block_intelligent_learning:course',
				'mt1' => 'privacy:metadata:block_intelligent_learning:mt1',
				'mt1userid' => 'privacy:metadata:block_intelligent_learning:mt1userid',
				'mt2' => 'privacy:metadata:block_intelligent_learning:mt2',
				'mt2userid' => 'privacy:metadata:block_intelligent_learning:mt2userid',
				'mt3' => 'privacy:metadata:block_intelligent_learning:mt3',
				'mt3userid' => 'privacy:metadata:block_intelligent_learning:mt3userid',
				'mt4' => 'privacy:metadata:block_intelligent_learning:mt4',
				'mt4userid' => 'privacy:metadata:block_intelligent_learning:mt4userid',
				'mt5' => 'privacy:metadata:block_intelligent_learning:mt5',
				'mt5userid' => 'privacy:metadata:block_intelligent_learning:mt5userid',
				'mt6' => 'privacy:metadata:block_intelligent_learning:mt6',
				'mt6userid' => 'privacy:metadata:block_intelligent_learning:mt6userid',
				'finalgrade' => 'privacy:metadata:block_intelligent_learning:finalgrade',
				'finalgradeuserid' => 'privacy:metadata:block_intelligent_learning:finalgradeuserid',
				'expiredate' => 'privacy:metadata:block_intelligent_learning:expiredate',
				'expiredateuserid' => 'privacy:metadata:block_intelligent_learning:expiredateuserid',
				'lastaccess' => 'privacy:metadata:block_intelligent_learning:lastaccess',
				'lastaccessuserid' => 'privacy:metadata:block_intelligent_learning:lastaccessuserid',
				'neverattended' => 'privacy:metadata:block_intelligent_learning:neverattended',
				'neverattendeduserid' => 'privacy:metadata:block_intelligent_learning:neverattendeduserid',
	 
			],
			'privacy:metadata:block_intelligent_learning'
		);
		
		$collection->add_external_location_link('ilp_user_activities_service', [
			'id' => 'privacy:metadata:ilp_user_activities_service:id',
			'sourcedid' => 'privacy:metadata:ilp_user_activities_service:sourcedid',
			'firstname' => 'privacy:metadata:ilp_user_activities_service:firstname',
			'lastname' => 'privacy:metadata:ilp_user_activities_service:lastname',
			'email' => 'privacy:metadata:ilp_user_activities_service:email',
		], 'privacy:metadata:ilp_user_activities_service');
	 
		return $collection;
    }
    
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
 
        $sql = "SELECT ctx.id
				FROM {context} ctx
				INNER JOIN {course} c ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
				INNER JOIN {block_intelligent_learning} ilp ON c.id = ilp.course
                WHERE (
                	ilp.userid        = :userid
                )
        ";
 
        $params = [
            'contextlevel'	=> CONTEXT_COURSE,
            'userid'		=> $userid,
        ];
 
        $contextlist->add_from_sql($sql, $params);
 
        return $contextlist;
    }
    
    /**
	 * Get the list of users who have data within a context.
	 *
	 * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
	 */
	public static function get_users_in_context(userlist $userlist) {
	 
	    $context = $userlist->get_context();
	 
	    if (!$context instanceof \context_course) {
	        return;
	    }
	 
	    $params = [
	        'instanceid'    => $context->instanceid,
	    ];
	 
	    // Students with submitted grades for a course
	    $sql = "SELECT ilp.userid
				FROM {course} c
				JOIN {block_intelligent_learning} ilp ON c.id = ilp.course
				WHERE c.id = :instanceid";
	    $userlist->add_from_sql('userid', $sql, $params);
	}
	

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        // Get the course.
        list($select, $params) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $params['contextcourse'] = CONTEXT_COURSE;

        $sql = "SELECT c.*
                FROM {course} c
                JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextcourse
                WHERE ctx.id $select";

        $courses = $DB->get_recordset_sql($sql, $params);

        foreach ($courses as $course) {
            // Get user's grades information for the particular course.
            $coursegrade = \block_intelligent_learning\privacy\provider::get_grades_info_for_user($contextlist->get_user()->id, $course->id);
            if ($coursegrade) { // If the course has been graded for the user, include it in the export.
                writer::with_context(\context_course::instance($course->id))->export_data(
                        [get_string('privacy:intelligentlearninggradespath', 'block_intelligent_learning')], (object) $coursegrade);
            }
        }
        $courses->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // Check what context we've been delivered.
        if (!$context instanceof \context_course) {
            return;
        }
        // Delete course submitted grades data
        global $DB;

        $params = [
            'courseid' => $context->instanceid
        ];

        $select = "course = :courseid";
        $DB->delete_records_select('block_intelligent_learning', $select, $params);
    }
    

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist as $context) {
            // Check what context we've been delivered.
            if ($context instanceof \context_course) {
                // Delete course submitted grades data.                   
		        $params = [
		            'courseid' => $context->instanceid,
		            'userid' => $contextlist->get_user()->id
		        ];
		
		        $select = "course = :courseid AND userid = :userid";
		        $DB->delete_records_select('block_intelligent_learning', $select, $params);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();  
        $userids = $userlist->get_userids();
        if (empty($userids) || (!$context instanceof \context_course)) {
            return;
        }
        
        global $DB;

        // Delete course submitted grades data for selected users
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $params = [
            'courseid' => $context->instanceid
        ];

        $params += $userparams;
        $select = "course = :courseid AND userid $usersql";
        
        $DB->delete_records_select('block_intelligent_learning', $select, $params);
    }
    
    
    /**
     * Get grades data for the specified user in the specified course.
     *
     * @param int $userid The id of the user in scope.
     * @param int $itemid The course's ID.
     * @return array|null
     */
    public static function get_grades_info_for_user(int $userid, int $courseid) {
        global $DB;

        $params = [
            'course' => $courseid,
            'userid' => $userid,
        ];

        if (!$grade = $DB->get_record('block_intelligent_learning', $params)) {
            return;
        }

        return [
            'midterm1' => $grade->mt1,
            'midterm2' => $grade->mt2,
            'midterm3' => $grade->mt3,
            'midterm4' => $grade->mt4,
            'midterm5' => $grade->mt5,
            'midterm6' => $grade->mt6,
            'finalgrade' => $grade->finalgrade,
            'incompletefinalgrade' => $grade->incompletefinalgrade,
            'expiredate' => transform::datetime($grade->expiredate),
            'lastaccess' => transform::datetime($grade->lastaccess)
        ];
    }
}