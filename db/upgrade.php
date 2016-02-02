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
 * ILP Integration upgrade
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 *
 */

// This file keeps track of upgrades to
// the ILP Integration block
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class.

function xmldb_block_intelligent_learning_upgrade($oldversion=0) {

    global $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2010011500) {

        // Define index userid (not unique) to be added to block_intelligent_learning.
        $table = new xmldb_table('block_intelligent_learning');
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch add index userid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Intelligent_learning savepoint reached.
        upgrade_block_savepoint($result, 2010011500, 'intelligent_learning');
    }

    if ($result && $oldversion < 2010020300) {

        // Define field expiredate to be added to block_intelligent_learning.
        $table = new xmldb_table('block_intelligent_learning');
        $field = new xmldb_field('expiredate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'finalgrade');

        // Conditionally launch add field expiredate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field neverattended to be added to block_intelligent_learning.
        $field = new xmldb_field('neverattended', XMLDB_TYPE_INTEGER, '5', XMLDB_UNSIGNED, null, null, null, 'lastaccess');

        // Conditionally launch add field neverattended.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changing type of field mt1 on table block_intelligent_learning to char.
        $field = new xmldb_field('mt1', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'userid');

        // Launch change of type for field mt1.
        $dbman->change_field_type($table, $field);

        // Changing type of field mt2 on table block_intelligent_learning to char.
        $field = new xmldb_field('mt2', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'mt1');

        // Launch change of type for field mt2.
        $dbman->change_field_type($table, $field);

        // Changing type of field mt3 on table block_intelligent_learning to char.
        $field = new xmldb_field('mt3', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'mt2');

        // Launch change of type for field mt3.
        $dbman->change_field_type($table, $field);

        // Changing type of field mt4 on table block_intelligent_learning to char.
        $field = new xmldb_field('mt4', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'mt3');

        // Launch change of type for field mt4.
        $dbman->change_field_type($table, $field);

        // Changing type of field mt5 on table block_intelligent_learning to char.
        $field = new xmldb_field('mt5', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'mt4');

        // Launch change of type for field mt5.
        $dbman->change_field_type($table, $field);

        // Changing type of field mt6 on table block_intelligent_learning to char.
        $field = new xmldb_field('mt6', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'mt5');

        // Launch change of type for field mt6.
        $dbman->change_field_type($table, $field);

        // Changing type of field finalgrade on table block_intelligent_learning to char.
        $field = new xmldb_field('finalgrade', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'mt6');

        // Launch change of type for field finalgrade.
        $dbman->change_field_type($table, $field);

        // Intelligent_learning savepoint reached.
        upgrade_block_savepoint($result, 2010020300, 'intelligent_learning');
    }

    if ($result && $oldversion < 2010021500) {
        $result = ($result and unset_config('gradeperiodend', 'blocks/intelligent_learning'));
        $result = ($result and unset_config('gradeperiodstart', 'blocks/intelligent_learning'));
    }

    if ($result && $oldversion < 2010031100) {
        // Our table.
        $table = new xmldb_table('block_intelligent_learning');

        for ($i = 1; $i <= 6; $i++) {
            // Define field mtXuserid to be added to block_intelligent_learning.
            $field = new xmldb_field("mt{$i}userid", XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, "mt{$i}");

            // Conditionally launch add field mtXuserid.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            // Define field mtXtimemodified to be added to block_intelligent_learning.
            $field = new xmldb_field("mt{$i}timemodified", XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, "mt{$i}userid");

            // Conditionally launch add field mtXtimemodified.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            // Define index mtXuserid (not unique) to be added to block_intelligent_learning.
            $index = new xmldb_index("mt{$i}userid", XMLDB_INDEX_NOTUNIQUE, array("mt{$i}userid"));

            // Conditionally launch add index mtXuserid.
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            // Intelligent_learning savepoint reached.
            upgrade_block_savepoint($result, 2010031100, 'intelligent_learning');
        }
    }

    if ($result && $oldversion < 2010031101) {

        // Define field finalgradeuserid to be added to block_intelligent_learning.
        $table = new xmldb_table('block_intelligent_learning');
        $field = new xmldb_field('finalgradeuserid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'finalgrade');

        // Conditionally launch add field finalgradeuserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field finalgradetimemodified to be added to block_intelligent_learning.
        $field = new xmldb_field('finalgradetimemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'finalgradeuserid');

        // Conditionally launch add field finalgradetimemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index finalgradeuserid (not unique) to be added to block_intelligent_learning.
        $index = new xmldb_index('finalgradeuserid', XMLDB_INDEX_NOTUNIQUE, array('finalgradeuserid'));

        // Conditionally launch add index finalgradeuserid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Intelligent_learning savepoint reached.
        upgrade_block_savepoint($result, 2010031101, 'intelligent_learning');
    }

    if ($oldversion < 2011020900) {

        // Define field expiredateuserid to be added to block_intelligent_learning.
        $table = new xmldb_table('block_intelligent_learning');
        $field = new xmldb_field('expiredateuserid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'expiredate');

        // Conditionally launch add field expiredateuserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field expiredatetimemodified to be added to block_intelligent_learning.
        $field = new xmldb_field('expiredatetimemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'expiredateuserid');

        // Conditionally launch add field expiredatetimemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field lastaccessuserid to be added to block_intelligent_learning.
        $field = new xmldb_field('lastaccessuserid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'lastaccess');

            // Conditionally launch add field lastaccessuserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field lastaccesstimemodified to be added to block_intelligent_learning.
        $field = new xmldb_field('lastaccesstimemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'lastaccessuserid');

        // Conditionally launch add field lastaccesstimemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field neverattendeduserid to be added to block_intelligent_learning.
        $field = new xmldb_field('neverattendeduserid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'neverattended');

        // Conditionally launch add field neverattendeduserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field neverattendedtimemodified to be added to block_intelligent_learning.
        $field = new xmldb_field('neverattendedtimemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'neverattendeduserid');

        // Conditionally launch add field neverattendedtimemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index expiredateuserid (not unique) to be added to block_intelligent_learning.
        $index = new xmldb_index('expiredateuserid', XMLDB_INDEX_NOTUNIQUE, array('expiredateuserid'));

         // Conditionally launch add index expiredateuserid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index lastaccessuserid (not unique) to be added to block_intelligent_learning.
        $index = new xmldb_index('lastaccessuserid', XMLDB_INDEX_NOTUNIQUE, array('lastaccessuserid'));

        // Conditionally launch add index lastaccessuserid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index neverattendeduserid (not unique) to be added to block_intelligent_learning.
        $index = new xmldb_index('neverattendeduserid', XMLDB_INDEX_NOTUNIQUE, array('neverattendeduserid'));

        // Conditionally launch add index neverattendeduserid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Intelligent_learning savepoint reached.
        upgrade_block_savepoint($result, 2011020900, 'intelligent_learning');
    }

    if ($oldversion < 2011050901) {
        $settings = array(
            'token' => 'webservices_token',
            'ipaddresses' => 'webservices_ipaddresses',
        );
        foreach ($settings as $old => $new) {
            $setting = get_config('blocks/intelligent_learning', $new);
            if (empty($setting)) {
                $oldsetting = get_config('blocks/mr_web', $old);
                if (!empty($oldsetting)) {
                    set_config($new, $oldsetting, 'blocks/intelligent_learning');
                }
            }
        }

        // Intelligent_learning savepoint reached.
        upgrade_block_savepoint($result, 2011050901, 'intelligent_learning');
    }

    if ($oldversion < 2014110612) {
         // Define field incompletefinalgrade to be added to block_intelligent_learning.
        $table = new xmldb_table('block_intelligent_learning');
        $field = new xmldb_field('incompletefinalgrade', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'timemodified');

        // Conditionally launch add field incompletefinalgrade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint($result, 2014110612, 'intelligent_learning');
    }

    /* Final return of upgrade result (true/false) to Moodle. Must be
    / always the last line in the script
    */
    return $result;
}