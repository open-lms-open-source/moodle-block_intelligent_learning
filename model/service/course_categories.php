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
 * @copyright Copyright (c) 2018 Ellucian
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package block_intelligent_learning
 * @author Ellucian
 */

require_once($CFG->dirroot.'/blocks/intelligent_learning/model/service/abstract.php');
require_once($CFG->dirroot.'/course/lib.php');

/**
 * Course Categories Service Model
 *
 * @author Ellucian
 * @package block_intelligent_learning
 */
class blocks_intelligent_learning_model_service_course_categories extends blocks_intelligent_learning_model_service_abstract {
    /**
     * Synced course category fields
     *
     * @var array
     */
    private $coursecategoryfields = array(
        'name',
    );

    /**
     * Course Category Provisioning
     *
     * @param string $xml XML with course category data
     * @throws Exception
     * @return string
     */
    public function handle($xml) {
        global $DB;

        list($action, $data) = $this->helper->xmlreader->validate_xml($xml, $this);

        if (empty($data['name'])) {
            throw new Exception('No category name passed, required');
        }
        $coursecategory = false;

        switch($action) {
            case 'create':
            case 'add':
				$coursecategory = $this->add($data);
                break;
            default:
                throw new Exception("Invalid action found: $action.  Valid actions: add and create");
        }
        return $this->response->coursecategories_handle($coursecategory);
    }

    /**
     * Add a course category
     *
     * @param array $data Course Category data
     * @throws Exception
     * @return object
     */
    protected function add($data) {
        global $DB;

		$categoryname = $data['name'];
		$parentid = 0;
		
		//if category already exists return it, otherwise create it
        if ($coursecategories = $DB->get_records('course_categories', array('name' => $categoryname, 'parent' => $parentid))) {
        	$category = array_shift($coursecategories);
        } else {
            $category = new stdClass();
            $category->name      = $categoryname;
            $category->parent    = $parentid;
            $category->sortorder = 999;
            $category->depth     = 1;

            try {
                $category->id = $DB->insert_record('course_categories', $category);
            } catch (dml_exception $e) {
                throw new Exception("Could not create the new category: $category->name");
            }

            $context = context_coursecat::instance($category->id);
            $context->mark_dirty();

        	// Make sure sort order is correct and category paths are created.
        	fix_course_sortorder();

            if ($coursecategories = $DB->get_records('course_categories', array('id' => $category->id))) {
            	$category = array_shift($coursecategories);
            }
        }        

        return $category;
    }
}
