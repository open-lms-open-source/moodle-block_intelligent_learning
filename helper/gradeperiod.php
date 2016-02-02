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
 * Gradeperiod helper
 *
 * @author Mark Nielsen
 * @package block_intelligent_learning
 */

class blocks_intelligent_learning_helper_gradeperiod extends mr_helper_abstract {
    /**
     * Holds cutoff dates for categories
     *
     * @var array
     */
    protected $times = array();

    /**
     * Read category cutoff dates config
     */
    public function __construct() {
        $config = get_config('blocks/intelligent_learning', 'categorycutoff');
        if (!empty($config)) {
            parse_str($config, $this->times);
        }
    }

    /**
     * Given a category, find if we are before the category cutoff date
     *
     * @param int $categoryid Category ID (Defaults to $COURSE->category)
     * @return boolean
     */
    public function direct($categoryid = null) {
        global $COURSE, $DB;

        if (!empty($this->times)) {
            if (is_null($categoryid)) {
                $categoryid = $COURSE->category;
            }
            if ($path = $DB->get_field('course_categories', 'path', array('id' => $categoryid))) {
                $catids = explode('/', trim($path, '/'));
                $catids = array_reverse($catids);
                foreach ($catids as $catid) {
                    if (array_key_exists($catid, $this->times)) {
                        return (time() < $this->times[$catid]);
                    }
                }
            }
        }
        return true;
    }
}