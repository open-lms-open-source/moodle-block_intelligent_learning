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
 * Datatel Grades block last date of attendance controller
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 **/
require_once("$CFG->dirroot/blocks/intelligent_learning/controller/midtermgrades.php");
class block_intelligent_learning_controller_lastattendance extends block_intelligent_learning_controller_midtermgrades {

    public static function add_tabs($controller, &$tabs) {
        if (has_capability('block/intelligent_learning:edit', $controller->get_context()) and
            !empty($controller->get_config()->gradebookapp) and $controller->get_config()->gradebookapp == 'moodle' and
            get_config('blocks/intelligent_learning', 'showlastattendance')) {

            $tabs->add('lastattendance', array('controller' => 'lastattendance', 'action' => 'edit'), null, 3);
        }
    }

    /**
     * Access rules
     */
    protected function init() {
        if (empty($this->config->gradebookapp) or $this->config->gradebookapp != 'moodle') {
            throw new moodle_exception('gradebookapperror', 'block_intelligent_learning');
        }
    }

    protected function notify_changes_saved() {
        $this->notify->good('ldasubmitted');
    }

    /**
     * Create a new blank usergrade object for LDA
     */
    protected function new_usergrade($userid, $courseid) {
        static $showlastattendance = null;

        if (is_null($showlastattendance)) {
            $showlastattendance = get_config('blocks/intelligent_learning', 'showlastattendance');
        }
        $usergrade = new stdClass;
        $usergrade->userid = $userid;
        $usergrade->course = $courseid;

        if ($showlastattendance) {
            $usergrade->lastaccess    = null;
            $usergrade->neverattended = null;
        }
        return $usergrade;
    }
}