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
 * Abstract service model
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */

class blocks_intelligent_learning_model_service_abstract extends mr_server_service_abstract {

    /**
     * Mapped fields
     *
     * @var array
     */
    protected $mappings = array();

    /**
     * Field mapping options
     *
     * @var array
     */
    protected $mapoptions = array();

    /**
     * Available provisioning actions
     *
     * @var array
     */
    protected $actions = array();

    /**
     * Constructor initialization
     */
    protected function init() {
        $this->helper = new mr_helper('blocks/intelligent_learning');
    }

    /**
     * Mappings getter
     *
     * @return array - mappings
     */
    public function get_mappings() {
        return $this->mappings;
    }

    /**
     * Mapoptions getter
     *
     * @return array - mapoptions
     */
    public function get_mapoptions() {
        return $this->mapoptions;
    }

    /**
     * Actions getter
     * 
     * @return array - actions
     */
    public function get_actions() {
        return $this->actions;
    }
}