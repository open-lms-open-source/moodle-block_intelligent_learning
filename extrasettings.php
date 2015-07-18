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
 * ILP Integration block extra settings class definitions
 *
 * @author Sam Chaffee
 * @version $Id$
 * @package block_intelligent_learning
 **/

/**
 * Creates a setting where you can define
 * dates related to categories and then manage
 * those set dates (EG: update or delete)
 */
class admin_setting_intelligent_learning_catdate extends admin_setting {
    /**
     * Returns current value of this setting
     * @return mixed array or string depending on instance, null means not set yet
     */
    public function get_setting() {
        $config = $this->config_read($this->name);
        if (!empty($config)) {
            $return = array();
            parse_str($config, $return);
            return $return;
        }
        return $config;
    }

    /**
     * Store new setting
     * @param mixed string or array, must not be null
     * @return '' if ok, string error message otherwise
     */
    public function write_setting($data) {
        global $CFG, $DB;

        if (!$config = $this->get_setting()) {
            $config = array();
        }

        $categoryid = optional_param($this->get_full_name().'_categoryid', 0, PARAM_INT);
        $date       = optional_param($this->get_full_name().'_date', '', PARAM_TEXT);
        $delete     = optional_param($this->get_full_name().'_delete', 0, PARAM_INT);

        if (!empty($delete)) {
            foreach ($delete as $deletecatid => $value) {
                unset($config[$deletecatid]);
            }
        }
        if (!empty($categoryid) and !empty($date)) {
            try {
                $helper = new mr_helper('blocks/intelligent_learning');
                $config[$categoryid] = $helper->date($date);
            } catch (moodle_exception $e) {
                return $e->getMessage();
            }
        }
        if (!empty($config)) {
            // Validate that all category IDs still exist.
            foreach ($config as $catid => $date) {
                if (!$DB->record_exists('course_categories', array('id' => $catid))) {
                    unset($config[$catid]);
                }
            }
            $config = http_build_query($config, '', '&');
        } else {
            $config = '';
        }
        return ($this->config_write($this->name, $config) ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Return part of form with setting
     * @param mixed data array or string depending on setting
     * @return string
     */
    public function output_html($data, $query='') {
        global $CFG, $OUTPUT;

        require_once($CFG->libdir.'/coursecatlib.php');

        $helper = new mr_helper('blocks/intelligent_learning');

        $displaylist = coursecat::make_categories_list();
        $category = html_writer::select($displaylist, $this->get_full_name().'_categoryid');

        $checkboxes = '';
        if ($config = $this->get_setting($this->name)) {
            $options = array();
            foreach ($displaylist as $categoryid => $displayname) {
                if (array_key_exists($categoryid, $config)) {
                    $options[] = $displayname.': '.$helper->date->format($config[$categoryid]).' '.
                                 '<input type="checkbox" id="'.$this->get_id().'_'.$categoryid.'" name="'.$this->get_full_name().'_delete['.$categoryid.']" value="1" />'
                                 .'<label for="'.$this->get_id().'_'.$categoryid.'">'.get_string('delete').'</label>';
                }
            }
            if (!empty($options)) {
                $checkboxes = '<div class="form-multicheckbox"><ul><li>'.implode('</li><li>', $options).'</li></ul></div><br />';
            }
        }
        $helpbutton = $OUTPUT->help_icon('categorycutoff', 'block_intelligent_learning');
        return format_admin_setting($this, $this->visiblename,
                '<div class="form-text defaultsnext"><input type="hidden" id="'.$this->get_id().'" name="'.$this->get_full_name().'" value="1" />'.$checkboxes.$category.
                '<input type="text" size="15" id="'.$this->get_id().'" name="'.$this->get_full_name().'_date" />
                <input type="submit" value="'.s(get_string('addcutoff', 'block_intelligent_learning'))."\" /> $helpbutton</div>",
                $this->description, false, '', null, $query);
    }
}

/**
 * Creates the extraletters config option.
 *
 * Config value is like: I,NC,WOW
 * All caps, each less than or equal to three characters, separated by commas and no spaces.
 */
class admin_setting_intelligent_learning_extraletters extends admin_setting_configtext {
    /**
     * Constructor specific to extraletters config
     */
    public function __construct() {
        parent::__construct('extraletters', get_string('extraletters', 'block_intelligent_learning'), get_string('extralettersdesc', 'block_intelligent_learning'), '');
    }

    /**
     * Specific validation and data manipulation
     */
    public function validate($data) {
        if ($data != '') {
            $parts = explode(',', $data);

            $newdata = array();
            foreach ($parts as $part) {
                $part = trim($part);
                $part = strtoupper($part);
                if (strlen($part) > 3) {
                    return get_string('lettergradetoolong', 'block_intelligent_learning', $part);
                }
                $newdata[] = $part;
            }
            $data = implode(',', $newdata);
        }
        return true;
    }
}