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
 * Date helper
 *
 * @author Mark Nielsen
 * @package block_intelligent_learning
 */

class blocks_intelligent_learning_helper_date extends mr_helper_abstract {
    /**
     * Sane lower date value, dates must be more than this value
     * @todo Need?
     */
    const SANEDATELOWER = 0;

    /**
     * Sane upper date value, dates must be less than this value
     * @todo Need?
     */
    const SANEDATEUPPER = 2145945600;

    /**
     * The date format
     *
     * @var string
     */
    protected $format = 'm/d/Y';

    /**
     * All valid date formats
     *
     * @var string
     */
    protected $formats = array(
        'm/d/Y' => 'MM/DD/YYYY',
        'd/m/Y' => 'DD/MM/YYYY',
        'Y/m/d' => 'YYYY/MM/DD'
    );

    /**
     * Read dateformat config
     */
    public function __construct() {
        // Grab global format setting, store locally.
        $format = get_config('blocks/intelligent_learning', 'dateformat');
        if (!empty($format)) {
            $this->format = $format;
        }

        // Added robustness....
        if (!array_key_exists($this->format, $this->formats)) {
            $this->format = 'm/d/Y';
        }
    }

    /**
     * Convert an input string to a UNIX timestamp
     *
     * @param string $string The string to convert
     * @return int
     * @throws moodle_exception
     * @todo Enforce sane lower/upper?
     */
    public function direct($string) {
        $a = new stdClass;
        $a->date   = $string;
        $a->format = $this->formats[$this->format];

        $parts = explode('/', $string);
        if (count($parts) != 3) {
            throw new moodle_exception('missingmonthdayoryear', 'block_intelligent_learning', '', $a);
        }

        $keys = explode('/', $this->format);
        $dmy  = new stdClass;
        foreach ($keys as $index => $key) {
            $dmy->$key = $parts[$index];
        }

        // More validation...
        if (!is_numeric($dmy->m) or $dmy->m > 12 or $dmy->m < 0) {
            throw new moodle_exception('invalidmonth', 'block_intelligent_learning', '', $a);
        }
        if (!is_numeric($dmy->Y) or strlen($dmy->Y) != 4) {
            throw new moodle_exception('invalidyear', 'block_intelligent_learning', '', $a);
        }
        $maxdays = cal_days_in_month(CAL_GREGORIAN, $dmy->m, $dmy->Y);
        if (!is_numeric($dmy->d) or $dmy->d < 0 or $dmy->d > $maxdays) {
            throw new moodle_exception('invalidday', 'block_intelligent_learning', '', $a);
        }

        $timestamp = @strtotime("$dmy->m/$dmy->d/$dmy->Y");

        if ($timestamp == false) {
            throw new moodle_exception('failedtoconvert', 'block_intelligent_learning', '', $a);
        }

        // We really need this?
        if ($timestamp < self::SANEDATELOWER or $timestamp > self::SANEDATEUPPER) {
            throw new moodle_exception('outsideoflimits', 'block_intelligent_learning', '', $a);
        }
        return $timestamp;
    }

    /**
     * Format a UNIX timestamp to a display string
     *
     * @param int $timestamp The timestamp
     * @return string
     */
    public function format($timestamp) {
        return date($this->format, $timestamp);
    }

    /**
     * Get available time formats
     *
     * @return array
     */
    public function get_formats() {
        return $this->formats;
    }
}