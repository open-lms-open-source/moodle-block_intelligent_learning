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
 * ILP block grade report controller
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 **/

require_once("$CFG->dirroot/blocks/intelligent_learning/controller/midtermgrades.php");

class block_intelligent_learning_controller_retentionalert extends block_intelligent_learning_controller_midtermgrades {

    public static function add_tabs($controller, &$tabs) {
        // Grade report tab.
        if (!empty($controller->get_config()->retentionalertlink)) {
            $tabs->add('retentionalert', array('controller' => 'retentionalert', 'action' => 'edit'), null, 4);
        }
    }

    protected function init() {
        if (empty($this->config->retentionalertlink)) {
            throw new moodle_exception('retentionalertlinkerror', 'block_intelligent_learning');
        }
    }
}