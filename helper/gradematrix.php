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
 * Gradesmatrix helper
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */

class blocks_intelligent_learning_helper_gradematrix extends mr_helper_abstract {

    protected $gradematrix = null;

    protected $controller = '';

    protected $tablerows = 0;

    protected $tablecols = 0;

    protected $errors = array();

    protected $gradelock = false;

    protected $retentionlink = false;

    protected $neverattended = false;

    protected $defaultincomplete = false;

    protected $expirelabel = '';

    protected $ilpurl = '';

    protected $retentionpid = '';

    protected $helper;

    public function __construct() {
        $this->helper = new mr_helper('blocks/intelligent_learning');
    }

    public function direct($gradematrix, $controller) {
        global $CFG, $SESSION, $COURSE, $OUTPUT;

        $this->controller = $controller;
        $this->gradematrix = $gradematrix;

        if (!empty($SESSION->block_intelligent_learning)) {
            $this->errors = $SESSION->block_intelligent_learning;
            $SESSION->block_intelligent_learning = array();
        }

        $config = get_config('blocks/intelligent_learning');
        $this->showlastattendance = $config->showlastattendance;
        $this->mtcolumns = $config->midtermgradecolumns;
        $this->gradelock = $config->gradelock;
        $this->showdefaultincomplete = $config->showdefaultincomplete;
        $this->neverattended = $config->showneverattended;
        $this->expirelabel = $config->expirelabel;

        $this->retentionlink = $config->retentionalertlink;
        $this->retentionpid = $config->retentionalertpid;
        $this->ilpurl = $config->ilpurl;

        // Need all three for retention link.
        if (empty($this->retentionlink) or empty($this->ilpurl) or empty($this->retentionpid)) {
            $this->retentionlink = false;
        }

        $gradereportlink = html_writer::link("$CFG->wwwroot/grade/report/grader/index.php?id=$COURSE->id", get_string('gotogrades', 'block_intelligent_learning'));
        $graderreportlinkbox = $OUTPUT->box($gradereportlink, 'block-ilp-link-to-grades centerpara');

        // Disable these for this controller.
        if ($this->controller == 'retentionalert') {
            $this->showlastattendance = false;
            $graderreportlinkbox = '';
        }

        $this->require_js();

        return $this->gradeform().$graderreportlinkbox;
    }

    private function gradeform() {

        $form = $this->start_form();

        if (!$this->gradematrix->has_usergrades()) {
            return $form;
        }

        if (!in_array($this->controller, array('lastattendance', 'retentionalert'))) {
            $form .= $this->current_grade_header();
        }

        switch ($this->controller) {
            case 'midtermgrades':
                $form .= $this->midterm_grade_headers();
                break;
            case 'finalgrades':
                $form .= $this->final_grade_header();
                if (!empty($this->showdefaultincomplete)) {
                    $form .= $this->default_incomplete_grade_header();
                }
                $form .= $this->expire_date_header();

                break;
        }

        if (!empty($this->showlastattendance)) {
            $this->tablecols++;
            $form .= html_writer::tag('th', get_string('lastattendancetableheader', 'block_intelligent_learning'), array('class' => $this->classes->thclass));
            if (!empty($this->neverattended)) {
                $form .= $this->never_attended_header();
            }
        }

        if (!empty($this->retentionlink)) {
            $this->tablecols++;
            $form .= html_writer::tag('th', '', array('class' => $this->classes->thclass));
        }
        $form .= '</tr>';

        // Now go though each student.
        $trodd = 'block-ilp-tr odd';
        $treven = 'block-ilp-tr even';
        $odd = true;

        $usergrades = $this->gradematrix->get_usergrades();

        $rows = count($usergrades);
        $count = 1;
        foreach ($usergrades as $usergrade) {
            $moreclass = '';
            if ($count == $rows) {
                $moreclass = ' last';
            }
            $count++;
            $trclass = $odd ? array('class' => $trodd.$moreclass) : array('class' => $treven.$moreclass);

            $form .= html_writer::start_tag('tr', $trclass);
            $form .= html_writer::start_tag('td', array('class' => 'block-ilp-td first'));
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'uid[]', 'value' => $usergrade->uid));
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'uidnumber[]', 'value' => $usergrade->idnumber));
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'ufullname[]', 'value' => $usergrade->firstname . ' ' . $usergrade->lastname));

            $form .= $usergrade->firstname . ' ' .  $usergrade->lastname;
            $form .= html_writer::end_tag('td');

            if (!in_array($this->controller, array('lastattendance', 'retentionalert'))) {
                $form .= $this->current_grade($usergrade, $trclass);
            }

            switch ($this->controller) {
                case 'midtermgrades':
                    $form .= $this->midterm_grades($usergrade);
                    break;
                case 'finalgrades':
                    $form .= $this->final_grade($usergrade);
                    if (!empty($this->showdefaultincomplete)) {
                          $form .= $this->default_incomplete($usergrade);
                    }
                    $form .= $this->expire_date($usergrade);
                    break;
            }

            if (!empty($this->showlastattendance)) {
                $value = '';
                $attributes = array('class' => '');
                if (!empty($usergrade->lastaccess)) {
                    $value = $this->helper->date->format($usergrade->lastaccess);
                    $attributes['class'] = $this->classes->submittedclass;
                }

                $id = "lastaccess_$usergrade->uid";

                if (array_key_exists($id, $this->errors)) {
                    $attributes['class'] = $this->add_error_class($attributes['class']);
                    $value = $this->errors[$id];
                }

                $attributes = array_merge($attributes, array(
                    'id' => $id,
                    'type' => 'text',
                    'size' => '10',
                    'name' => $id,
                    'value' => $value,
                ));

                $form .= html_writer::start_tag('td', array('class' => 'block-ilp-td'));
                $form .= html_writer::empty_tag('input', $attributes);

                $form .= html_writer::end_tag('td');

                if (!empty($this->neverattended)) {
                    $form .= $this->never_attended($usergrade);
                }
            }

            if (!empty($this->retentionlink)) {
                $ralstring = get_string('retentionalert', 'block_intelligent_learning');
                $efa       = base64_encode("A.ID=$usergrade->idnumber");
                $url       = "$this->ilpurl/pages/WebAdvisor.aspx?title=Retention+Cases+for+Student&type=p&pid=$this->retentionpid&EFA=$efa";
                $form .= html_writer::start_tag('td', array('class' => 'block-ilp-td'));
                $form .= html_writer::link($url, $ralstring, array('target' => '_blank'));
                $form .= html_writer::end_tag('td');
            }

            $form .= html_writer::end_tag('tr');

            $odd = !$odd;
        }

        switch ($this->controller) {
            case 'lastattendance':
                $submitstr = get_string('submitlda', 'block_intelligent_learning');
                break;
            default:
                $submitstr = get_string('submitgrades', 'block_intelligent_learning');
                break;
        }

        $form .= html_writer::end_tag('table');

        if ($this->controller != 'retentionalert') {
            $form .= html_writer::start_tag('div', array('class' => 'block-ilp-submitbutton'));
            $form .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => $submitstr));
            $form .= html_writer::end_tag('div');
        }

        $form .= html_writer::end_tag('form');

        return $form;
    }

    private function start_form() {
        global $CFG, $COURSE, $OUTPUT;

        $helpbutton = $OUTPUT->help_icon($this->controller, 'block_intelligent_learning');
        $form = html_writer::tag('div', get_string($this->controller, 'block_intelligent_learning') . $helpbutton, array('class' => 'block-ilp-title'));

        if (get_string_manager()->string_exists("helptext$this->controller", 'block_intelligent_learning')) {
            $help  = get_string("helptext$this->controller", 'block_intelligent_learning');
            if (!empty($help)) {
                $form .= $OUTPUT->box($help, 'generalbox boxaligncenter boxwidthnarrow block-ilp-helptext');
            }
        }
        $form .= html_writer::start_tag('div', array('class' => 'block-ilp-groupselector'));
        $form .= groups_print_course_menu($COURSE, "$CFG->wwwroot/blocks/intelligent_learning/view.php?controller=$this->controller&action=edit&courseid=$COURSE->id", true);
        $form .= html_writer::end_tag('div');

        $form .= html_writer::start_tag('div', array('class' => 'block-ilp-groupselector'));
        $form .= $this->metaenroll_print_course_menu($COURSE, "$CFG->wwwroot/blocks/intelligent_learning/view.php?controller=$this->controller&action=edit&courseid=$COURSE->id", true);
        $form .= html_writer::end_tag('div');

        if (!$this->gradematrix->has_usergrades()) {
            if (groups_get_course_group($COURSE)) {
                $form .= $OUTPUT->notification(get_string('nogradebookusersandgroups', 'block_intelligent_learning'));
            } else {
                $form .= $OUTPUT->notification(get_string('nogradebookusers', 'block_intelligent_learning'));
            }
            return $form;
        }
        $form_attributes = array(
            'id' => 'gradematrix',
            'action' => 'view.php',
            'method' => 'post',
        );
        $form .= html_writer::start_tag('form', $form_attributes);
        $form .= html_writer::empty_tag('input', array('name' => 'sesskey', 'type' => 'hidden', 'value' => sesskey()));
        $form .= html_writer::empty_tag('input', array('name' => 'controller', 'type' => 'hidden', 'value' => $this->controller));
        $form .= html_writer::empty_tag('input', array('name' => 'action', 'type' => 'hidden', 'value' => 'process'));
        $form .= html_writer::empty_tag('input', array('name' => 'courseid', 'type' => 'hidden', 'value' => $this->gradematrix->get_courseid()));

        $this->classes = new stdClass;
        $this->classes->thclass = 'block-ilp-th';
        $this->classes->submittedclass = 'block-ilp-submitted';
        $this->classes->errorclass = ' block-ilp-error';

        $form .= $this->populategrade_form();

        $form .= $this->cleargrades_form();

        $form .= html_writer::start_tag('table', array('id' => 'gmtable', 'class' => 'block-ilp-gmtable'));

        $form .= html_writer::start_tag('tr', array('class' => 'top'));
        $form .= html_writer::start_tag('th', array('class' => $this->classes->thclass . ' first'));

        foreach (array('firstname', 'lastname') as $name) {
            $url = "$CFG->wwwroot/blocks/intelligent_learning/view.php?controller=$this->controller&action=edit&courseid=$COURSE->id&sort=$name&order=";
            if ($this->gradematrix->get_sort() == $name) {
                // Add direction arrow.
                if ($this->gradematrix->get_order() == 'ASC') {
                    $url .= 'DESC';
                    $pic  = 'up';
                    $alt  = s(get_string('sortasc', 'grades'));
                } else {
                    $url .= 'ASC';
                    $pic  = 'down';
                    $alt  = s(get_string('sortdesc', 'grades'));
                }
                $image = $OUTPUT->pix_icon("t/$pic", $alt);
                // Add sort in opposite direction.
            } else {
                // Add sort ASC.
                $url  .= 'ASC';
                $image = '';
            }
            // Add separator.
            if ($name == 'lastname') {
                $form .= ' / ';
            }
            $form .= html_writer::link($url, get_string($name)) . $image;
        }
        $form .= html_writer::end_tag('th');

        $this->tablecols = 2;

        return $form;
    }

    private function midterm_grade_headers() {
        $form = '';

        if ($this->mtcolumns > 0) {
            for ($i = 1; $i <= $this->mtcolumns; $i++) {
                $this->tablecols++;
                $form .= html_writer::tag('th', get_string('midterm', 'block_intelligent_learning', $i), array('class' => $this->classes->thclass));
            }
        }

        return $form;
    }

    private function midterm_grades($usergrade) {
        $form = '';

        if ($this->mtcolumns > 0) {
            for ($i = 1; $i <= $this->mtcolumns; $i++) {

                $value = '';
                $class = '';
                if (!empty($usergrade->{"mt$i"})) {
                    $value = $usergrade->{"mt$i"};
                    $class = $this->classes->submittedclass;
                }

                $id = "mt{$i}_$usergrade->uid";
                if (array_key_exists($id, $this->errors)) {
                    $class = $this->add_error_class($class);
                    $value = $this->errors[$id];
                }
                $form .= html_writer::start_tag('td', array('class' => 'block-ilp-td'));
                $attributes = array('type' => 'text', 'name' => $id, 'id' => $id, 'value' => $value, 'size' => '5', 'class' => $class);
                $form .= html_writer::empty_tag('input', $attributes);

                $form .= html_writer::end_tag('td');
            }
        }

        return $form;
    }

    private function final_grade($usergrade) {

        $value = '';

        $attributes = array('class' => '');
        if (!empty($usergrade->finalgrade)) {
            $value = $usergrade->finalgrade;
            //$attributes['class'] = $this->classes->submittedclass;

            if ($this->gradelock) {
                $attributes['disabled'] = 'disabled';
            }

        }

        $id = "finalgrade_$usergrade->uid";

        if (array_key_exists($id, $this->errors)) {

            $attributes['class'] = $this->add_error_class($attributes['class']);
            $value = $this->errors[$id];
        }

        $form = html_writer::start_tag('td', array('class' => 'block-ilp-td'));
        $attributes = array_merge($attributes, array(
            'id' => $id,
            'type' => 'text',
            'name' => $id,
            'value' => $value,
            'size' => '5'
        ));
        $form .= html_writer::empty_tag('input', $attributes);

        $form .= html_writer::end_tag('td');

        return $form;
    }

    private function default_incomplete($usergrade) {
        $value = '';
        $attributes = array('class' => '');

        if (!empty($usergrade->incompletefinalgrade)) {
            $value = $usergrade->incompletefinalgrade;
            $attributes['class'] = $this->classes->submittedclass;
        }

        if (($this->gradelock) && (!empty($usergrade->finalgrade))) {
            $attributes['disabled'] = 'disabled';
        }

        $id = "incompletefinalgrade_$usergrade->uid";

        if (array_key_exists($id, $this->errors)) {
            $attributes['class'] = $this->add_error_class($attributes['class']);
            $value = $this->errors[$id];
        }

        $attributes = array_merge($attributes, array(
            'id' => $id,
            'type' => 'text',
            'size' => '5',
            'name' => $id,
            'value' => $value,
        ));

        $form = html_writer::start_tag('td', array('class' => 'block-ilp-td'));
        $form .= html_writer::empty_tag('input', $attributes);

        $form .= html_writer::end_tag('td');

        return $form;
    }

    private function final_grade_header() {
        $form = html_writer::tag('th', get_string('finalgrade', 'block_intelligent_learning'), array('class' => $this->classes->thclass));
        $this->tablecols++;

        return $form;
    }

    private function default_incomplete_grade_header() {
        $form = html_writer::tag('th', get_string('incompletefinalgrade', 'block_intelligent_learning'), array('class' => $this->classes->thclass));
        $this->tablecols++;

        return $form;
    }

    private function expire_date_header() {
        $form = html_writer::tag('th', get_string($this->expirelabel, 'block_intelligent_learning'), array('class' => $this->classes->thclass));
        $this->tablecols++;

        return $form;
    }

    private function expire_date($usergrade) {

        $value = '';
        $attributes = array('class' => $this->classes->submittedclass);
        if (!empty($usergrade->expiredate)) {
            $value = $this->helper->date->format($usergrade->expiredate);
        }

        if (($this->gradelock) && (!empty($usergrade->finalgrade))) {
            $attributes['disabled'] = 'disabled';
        }

        $id = "expiredate_$usergrade->uid";

        if (array_key_exists($id, $this->errors)) {
            $attributes['class'] = $this->add_error_class($attributes['class']);
            $value = $this->errors[$id];
        }

        $attributes = array_merge($attributes, array(
            'id' => $id,
            'name' => $id,
            'value' => $value,
            'size' => '10',
        ));

        $form = html_writer::start_tag('td', array('class' => 'block-ilp-td'));
        $form .= html_writer::empty_tag('input', $attributes);

        $form .= html_writer::end_tag('td');

        return $form;
    }

    private function never_attended($usergrade) {

        $checked = false;
        $id = "neverattended_$usergrade->uid";
        $class = '';

        if (array_key_exists($id, $this->errors)) {
            $class = $this->add_error_class($class);
            if ($this->errors[$id]) {
                $checked = true;
            }
        } else if (!empty($usergrade->neverattended)) {
            $checked = true;
        }
        $form = html_writer::start_tag('td', array('class' => 'block-ilp-td'));

        $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $id, 'value' => '0'));
        $form .= html_writer::start_tag('span', array('class' => $class));
        $form .= html_writer::checkbox($id, '1', $checked, '', array('id' => $id));
        $form .= html_writer::end_tag('span');

        $form .= html_writer::end_tag('td');

        return $form;

    }

    private function never_attended_header() {
        $header = html_writer::tag('th', get_string('neverattended', 'block_intelligent_learning'), array('class' => 'block-ilp-th'));
        $this->tablecols++;

        return $header;
    }

    private function current_grade($usergrade) {
        $currentgrade = $usergrade->currentgrade->realletter;
        return html_writer::tag('td', $currentgrade, array('class' => 'block-ilp-td'));
    }

    private function current_grade_header() {
        $header = html_writer::tag('th', get_string('currentgrade', 'block_intelligent_learning'), array('class' => 'block-ilp-th'));
        $this->tablecols++;

        return $header;
    }

    private function add_error_class($currentclass) {
        $errorclass = $this->classes->errorclass;
        if (!empty($currentclass)) {
            $errorclass = $currentclass . ' ' . $errorclass;
        }

        return $errorclass;
    }

    private function populategrade_form() {
        global $COURSE, $OUTPUT;

        $form = '';

        if (!in_array($this->controller, array('midtermgrades', 'finalgrades'))) {
            return $form;
        }

        if ($this->controller == 'midtermgrades' and $this->mtcolumns < 1) {
            return $form;
        }

        $options = array();
        $populatelabel = "";
        switch ($this->controller) {
            case 'midtermgrades':
                for ($i = 1; $i <= $this->mtcolumns; $i++) {
                    $options["mt$i"] = get_string('midterm', 'block_intelligent_learning', $i);
                }
                $choosestring = get_string('populatemidterm', 'block_intelligent_learning');
                $populatelabel = get_string('populatemidtermlabel', 'block_intelligent_learning');
                break;

            case 'finalgrades':
                $options["finalgrade"] = get_string('finalgrade', 'block_intelligent_learning');
                $choosestring = get_string('populatefinalgrade', 'block_intelligent_learning');
                $populatelabel = get_string('populatefinalgradelabel', 'block_intelligent_learning');
                break;
        }

        $menu = html_writer::label($populatelabel . ' ', 'block-ilp-populategrade', false, array());
        $menu .= html_writer::select($options, 'populategrade', '', $choosestring, array('id' => 'block-ilp-populategrade'));

        $form .= $OUTPUT->box("$menu", 'block-ilp-gradepopulate groupselector');

        return $form;
    }

    private function cleargrades_form() {
        global $COURSE, $OUTPUT;

        $form = '';

        if (!in_array($this->controller, array('midtermgrades', 'finalgrades'))) {
            return $form;
        }

        if ($this->controller == 'midtermgrades' and $this->mtcolumns < 1) {
            return $form;
        }

        $options = array();
        $clearlabel = get_string('cleargradeslabel', 'block_intelligent_learning');
        $cleardescexplanation = '';
        $clearfields = '';
        switch ($this->controller) {
            case 'midtermgrades':
                if ($this->mtcolumns > 1) {
                    // Clear grades is not supported for multiple midterm grades.
                    return $form;
                }
                $clearfields .= 'mt1';
                $cleardescexplanation = get_string('cleargradesexplanationmidterm','block_intelligent_learning');
                break;

            case 'finalgrades':
                if ($this->gradelock) {
                    return $form;
                }

                $clearfields .= 'finalgrade';
                if (!empty($this->showdefaultincomplete)) {
                    $clearfields .= '-incompletefinalgrade';
                }
                $clearfields .= '-expiredate';
                $cleardescexplanation = get_string('cleargradesexplanationfinal','block_intelligent_learning');
                break;
        }

        $form .= html_writer::tag('p', '');
        $form .= html_writer::start_tag('div');
        $form .= html_writer::start_tag('div', array('class' => 'groupselector'));
        $form .= html_writer::tag('label', get_string('cleargradesdescription', 'block_intelligent_learning'));
        $form .= html_writer::empty_tag('input', array('type' => 'button', 'value' => $clearlabel, 'id' => 'block-ilp-cleargrades', 'data-clearfields' => $clearfields));
        $form .= html_writer::end_tag('div');
        $form .= html_writer::end_tag('div');
        $form .= html_writer::tag('div', $cleardescexplanation);
        $form .= html_writer::tag('p', '');
        return $form;
    }

    /**
     * Print group menu selector for course level.
     *
     * @category group
     * @param stdClass $course course object
     * @param mixed $urlroot return address. Accepts either a string or a moodle_url
     * @param bool $return return as string instead of printing
     * @return mixed void or string depending on $return param
     */
    private function metaenroll_print_course_menu($course, $urlroot, $return=false) {
        global $USER, $OUTPUT, $DB;

        $context = context_course::instance($course->id);
        $aag = has_capability('moodle/course:enrolconfig', $context);

        $output = "";

        $children = $DB->get_records('enrol', array('enrol' => 'meta', 'courseid' => $course->id));

        if (!empty($children) and (count($children) > 0)) {
            $options = array();
            $options[0] = get_string('allparticipants');

            foreach ($children as $child) {
                $childcourse = $DB->get_record('course', array('id' => $child->customint1), '*', MUST_EXIST);
                $options[$child->id] = $childcourse->fullname;
            }
            $metaid = optional_param('meta', 0, PARAM_INT);
            $select = new single_select(new moodle_url($urlroot), 'meta', $options, $metaid, null, 'selectgroup');
            $select->label = get_string('metalink_label', 'block_intelligent_learning');
            $output = $OUTPUT->render($select);
        }

         $output = '<div class="groupselector">'.$output.'</div>';

         return $output;
    }

    private function require_js() {
        global $PAGE, $COURSE;

        $jsarray      = array();
        $usergrades   = $this->gradematrix->get_usergrades();
        $gradeletters = grade_get_letters(context_course::instance($COURSE->id));
        foreach ($usergrades as $usergrade) {
            if (in_array($usergrade->currentgrade->letter, $gradeletters)) {
                $jsarray[$usergrade->uid] = $usergrade->currentgrade->letter;
            }
        }
        $arguments = array('grades' => $jsarray);

        $module = array(
            'name' => 'block_intelligent_learning',
            'fullpath' => '/blocks/intelligent_learning/javascript.js',
            'requires' => array(
                'node',
                'event',
            ),
            'strings' => array(
                array('confirmunsaveddata', 'block_intelligent_learning'),
            ),
        );

        $PAGE->requires->js_init_call(
            'M.block_intelligent_learning.init_gradematrix',
            $arguments,
            true,
            $module
        );

    }
}