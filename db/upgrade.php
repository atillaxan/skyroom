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
 * This file keeps track of upgrades to the skyroom module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute skyroom upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_skyroom_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if($oldversion < 2019022824) {
        $table = new xmldb_table('skyroom_service_data');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('skyroom_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('login_url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('login_url_timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('user_timeupdated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('room_timeadded', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'));
        $table->add_key('skyroom_id', XMLDB_KEY_FOREIGN, array('skyroom_id'), 'skyroom', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2019022824, 'skyroom');
    }
    if($oldversion < 2019022826) {
        $table = new xmldb_table('skyroom');
        $field = new xmldb_field('room_url', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2019022826, 'skyroom');
    }
    if($oldversion < 2019022829) {
        //skyroom
        $table = new xmldb_table('skyroom');
        $field = new xmldb_field('service', XMLDB_TYPE_INTEGER, '10', null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        //skyroom_logs
        $table = new xmldb_table('skyroom_logs');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('skyroom_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('login_url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'));
        $table->add_key('skyroom_id', XMLDB_KEY_FOREIGN, array('skyroom_id'), 'skyroom', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2019022829, 'skyroom');
    }
    if($oldversion < 2019022830) {
        $table = new xmldb_table('skyroom_service_data');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('skyroom_login');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('skyroom_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('login_url', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'));
        $table->add_key('skyroom_id', XMLDB_KEY_FOREIGN, array('skyroom_id'), 'skyroom', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        //skyroom_logs
        $table = new xmldb_table('skyroom_logs');
        $field = new xmldb_field('login_url', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019022830, 'skyroom');
    }
    return true;
}
