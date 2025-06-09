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
 * delete a particular instance of skyroom
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // Course_module ID, or
$confirm = optional_param('confirm', '', PARAM_RAW); //yes,no

if ($id) {
    $cm         = get_coursemodule_from_id('skyroom', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $skyroom  = $DB->get_record('skyroom', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}


require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);


$return = course_get_url($cm->course);
if(empty($confirm)) {

    $fullmodulename = get_string('modulename', $cm->modname);

    $optionsyes = array('id'=>$cm->id, 'confirm'=>'yes', 'sesskey'=>sesskey());

    $strdeletecheck = get_string('deletecheck', '', $fullmodulename);
    $strparams = (object)array('type' => $fullmodulename, 'name' => $cm->name);
    $strdeletechecktypename = get_string('deletechecktypename', '', $strparams);

    $PAGE->set_pagetype('mod-' . $cm->modname . '-delete');
    $PAGE->set_title($strdeletecheck);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strdeletecheck);

    echo $OUTPUT->header();
    echo $OUTPUT->box_start('noticebox');
    $formcontinue = new single_button(new moodle_url("$PAGE->url", $optionsyes), get_string('yes'));
    $formcancel = new single_button($return, get_string('no'), 'get');
    echo $OUTPUT->confirm($strdeletechecktypename, $formcontinue, $formcancel);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();

    exit;
} else {
    if($confirm == 'no') {
        redirect($return);
    } else {
        $event = \mod_skyroom\event\course_module_deleted_before::create(array(
            'courseid' => $cm->course,
            'context' => context_course::instance($cm->course),
            'objectid' => $cm->id,
            'other'    => array(
                'modulename' => 'skyroom',
                'instanceid' => $id,
            )
        ));
        $event->trigger();
        redirect($return);
    }
}

