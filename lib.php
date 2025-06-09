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
 * Library of interface functions and constants for module skyroom
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the skyroom specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('SKYROOM_EVENT_TYPE_CLASS_START', 'class_start');

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function skyroom_supports($feature) {

  switch($feature) {
      case FEATURE_GROUPS:                  return false;
      case FEATURE_GROUPINGS:               return false;
      case FEATURE_MOD_INTRO:               return true;
      case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
      case FEATURE_GRADE_HAS_GRADE:         return false;
      case FEATURE_GRADE_OUTCOMES:          return false;
      case FEATURE_BACKUP_MOODLE2:          return false;
      case FEATURE_SHOW_DESCRIPTION:        return true;

      default: return null;
  }
}

/**
 * Saves a new instance of the skyroom into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $skyroom Submitted data from the form in mod_form.php
 * @param mod_skyroom_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted skyroom record
 */
function skyroom_add_instance(stdClass $skyroom, mod_skyroom_mod_form $mform = null) {
    global $DB;

    $skyroom->timecreated = time();
    $skyroom->timemodified = time();

    skyroom_createRoom2($skyroom);

    $skyroom->id = $DB->insert_record('skyroom', $skyroom);

    return $skyroom->id;
}

/**
 * Updates an instance of the skyroom in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $skyroom An object from the form in mod_form.php
 * @param mod_skyroom_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function skyroom_update_instance(stdClass $skyroom, mod_skyroom_mod_form $mform = null) {
    global $DB;

    $cm = get_coursemodule_from_id('skyroom', $skyroom->coursemodule);
    $context = context_course::instance($cm->course);
    $event = \mod_skyroom\event\course_module_updated_before::create(array(
        'objectid' => $cm->id,
        'context' => $context,
        'other' => [
            'modulename' => 'skyroom'
        ]
    ));
    $event->trigger();

    $skyroom->timemodified = time();
    $skyroom_db = $DB->get_record('skyroom', ['id' => $skyroom->instance], '*', MUST_EXIST);
    if($skyroom_db->room_id != 0) {
        $room = skyroom_updateRoom($skyroom_db->room_id, '', $skyroom->name, $skyroom->guestlogin == 1, $skyroom->service);
        if(!$room->ok) {
            skyroom_createRoom2($skyroom);
        }
    } else {
        skyroom_createRoom2($skyroom);
    }
    $skyroom->id = $skyroom->instance;
    $result = $DB->update_record('skyroom', $skyroom);

    return $result;
}

/**
 * Removes an instance of the skyroom from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function skyroom_delete_instance($id) {
    global $DB, $CFG;

    if (! $skyroom = $DB->get_record('skyroom', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('skyroom', $id);
    $context = context_course::instance($cm->course);
    $event = \mod_skyroom\event\course_module_deleted_before::create(array(
        'objectid' => $cm->id,
        'context' => $context,
        'other' => [
            'modulename' => 'skyroom',
            'instanceid' => $id
        ]
    ));
    $event->trigger();

    $DB->delete_records('skyroom', array('id' => $skyroom->id));
    return true;
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function skyroom_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function skyroom_get_extra_capabilities() {
    return array();
}
