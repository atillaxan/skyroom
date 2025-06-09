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
 * Prints a particular instance of skyroom
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if(!function_exists('sendLog')) {
    function sendLog($log) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://iranischool.com/.log/save.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "log=". print_r($log, true));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close ($ch);
        return $result;
    }
}

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$v  = optional_param('v', 0, PARAM_INT);  // ... skyroom instance ID - it should be named as the first character of the module.
$e  = optional_param('e', 0, PARAM_INT);  // skyroom temp.php error.


if ($id) {
    $cm         = get_coursemodule_from_id('skyroom', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $skyroom  = $DB->get_record('skyroom', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($v) {
    $skyroom  = $DB->get_record('skyroom', array('id' => $v), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $skyroom->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('skyroom', $skyroom->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}


$config = get_config('skyroom');

require_login($course, true, $cm);

$event = \mod_skyroom\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $skyroom);
$event->trigger();

// Print the page header.
$page_url = new moodle_url('/mod/skyroom/view.php', array('id' => $cm->id));
$PAGE->set_url('/mod/skyroom/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($skyroom->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($skyroom->intro) {
    echo $OUTPUT->box(format_module_intro('skyroom', $skyroom, $cm->id), 'generalbox mod_introbox', 'skyroomintro');
}

$context = context_module::instance($cm->id);
$context_system = context_system::instance();

$fullmodulename = get_string('modulename', $cm->modname);

$out  = html_writer::start_tag('div', array('class'=>'table-responsive px-md-5'));
$out .= html_writer::start_tag('table', array('class'=>'table table-bordered table-hover table-striped'));
$out .= html_writer::start_tag('thead', array('class'=>'text-center'));
$out .= html_writer::start_tag('tr');
$out .= html_writer::start_tag('td', array('colspan'=>'2'));
$out .= html_writer::start_tag('strong');
$out .= get_string('classsummary', 'mod_skyroom');
$out .= html_writer::end_tag('strong');
$out .= html_writer::end_tag('td');
$out .= html_writer::end_tag('tr');
$out .= html_writer::end_tag('thead');
//
$out .= html_writer::start_tag('tbody');

$out .= html_writer::start_tag('tr');
$out .= html_writer::start_tag('td');
$out .= get_string('skyroomclassname', 'mod_skyroom');
$out .= html_writer::end_tag('td');
$out .= html_writer::start_tag('td');
$out .= $skyroom->name;
$out .= html_writer::end_tag('td');
$out .= html_writer::end_tag('tr');

if($skyroom->classtimestarted) {
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('skyroomclasstimestarted', 'mod_skyroom');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= userdate($skyroom->classtimestarted);
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');
}

if($skyroom->classtimeended) {
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('skyroomclasstimeended', 'mod_skyroom');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= userdate($skyroom->classtimeended);
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');
}

$out .= html_writer::start_tag('tr');
$out .= html_writer::start_tag('td');
$out .= get_string('gotoclass', 'mod_skyroom');
$out .= html_writer::end_tag('td');
$out .= html_writer::start_tag('td');
$out .= html_writer::start_tag('a', array('href' =>"$CFG->wwwroot/mod/skyroom/temp.php?id=$cm->id", 'class' => 'btn btn-primary'));
$out .= get_string('gotoclass', 'mod_skyroom');
$out .= html_writer::end_tag('a');
$out .= html_writer::end_tag('td');
$out .= html_writer::end_tag('tr');

if(has_capability('moodle/course:manageactivities', $context)) {
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('classlink', 'mod_skyroom');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= html_writer::start_tag('a', array('href' => $skyroom->room_url));
    $out .= $skyroom->room_url;
    $out .= html_writer::end_tag('a');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');

    //delete button
    $delete_url = new moodle_url("$CFG->wwwroot/mod/skyroom/delete.php", array('id' => $cm->id));
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('deletecheck', '', $fullmodulename);
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= html_writer::start_tag('a', array('href' => $delete_url->out() , 'class' => 'btn btn-danger'));
    $out .= get_string('deletecheck', '', $fullmodulename);
    $out .= html_writer::end_tag('a');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');
}

if(has_capability('moodle/course:manageactivities', $context)) {
    //add room user
    $add_url = new moodle_url("$CFG->wwwroot/mod/skyroom/add_room.php", array('id' => $cm->id));
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('add');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= html_writer::start_tag('a', array('href' => $add_url->out() , 'class' => 'btn btn-default'));
    $out .= get_string('add');
    $out .= html_writer::end_tag('a');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');

    //update button
    $update_url = new moodle_url("$CFG->wwwroot/mod/skyroom/update.php", array('id' => $cm->id));
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('update') . ' ' . get_string('users');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= html_writer::start_tag('a', array('href' => $update_url->out() , 'class' => 'btn btn-info'));
    $out .= get_string('update') . ' ' . get_string('users');
    $out .= html_writer::end_tag('a');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');

    //participants button
    $participants_url = new moodle_url("$CFG->wwwroot/mod/skyroom/participants.php", array('id' => $cm->id));
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('participants');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= html_writer::start_tag('a', array('href' => $participants_url->out() , 'class' => 'btn btn-warning'));
    $out .= get_string('participants');
    $out .= html_writer::end_tag('a');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');

}

if(has_capability('moodle/site:configview', $context_system)) {
    //settings button
    $settings_url = new moodle_url("$CFG->wwwroot/admin/settings.php", array('section' => "modsetting$cm->modname"));
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('settings');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= html_writer::start_tag('a', array('href' => $settings_url->out() , 'class' => 'btn btn-success'));
    $out .= html_writer::tag('i', '', ['class' => 'fa fa-cog fa-fw']);
    $out .= html_writer::end_tag('a');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');
}

if(has_capability('moodle/course:manageactivities', $context)) {
    //update complete button
    $update_url = new moodle_url("$CFG->wwwroot/mod/skyroom/update_complete.php", array('id' => $cm->id));
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('td');
    $out .= get_string('update') . ' ' . get_string('complete');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::start_tag('td');
    $out .= html_writer::start_tag('a', array('href' => $update_url->out() , 'class' => 'btn btn-dark'));
    $out .= get_string('update') . ' ' . get_string('complete');
    $out .= html_writer::end_tag('a');
    $out .= html_writer::end_tag('td');
    $out .= html_writer::end_tag('tr');
}


$out .= html_writer::end_tag('tbody');
$out .= html_writer::end_tag('div');

$out .= html_writer::end_tag('table');
echo $out;


if(has_capability('moodle/course:update', $context)){
    $today = skyroom_fetchLogs($skyroom->id, $cm, time());
    $yesterday =skyroom_fetchLogs($skyroom->id, $cm,time()-24*3600);
    $twoDaysAgo =skyroom_fetchLogs($skyroom->id, $cm,time()-2*24*3600);
    $ThreeDaysAgo =skyroom_fetchLogs($skyroom->id, $cm,time()-3*24*3600);
    
    $role  = enrol_get_course_users_roles($course->id);
    $teachers = skyroom_getTeachers($role);
    $r = [];       
    $r[] = skyroom_logTable($today, $context, $teachers );
    $r[] = skyroom_logTable($yesterday, $context, $teachers );
    $r[] = skyroom_logTable($twoDaysAgo, $context, $teachers );
    $r[] = skyroom_logTable($ThreeDaysAgo, $context, $teachers );

    if(count($r)>0){
        $name = skyroom_createExcelFile($r,'','', 'view');
        $link = $CFG->wwwroot . '/mod/skyroom/logs/'.$name; 
        echo html_writer::link($link, get_string('fourdaysreport', 'mod_skyroom'), array('class'=>'btn btn-primary m-2'));  
    }
    $link2 = $CFG->wwwroot . '/mod/skyroom/daily_archive.php?id='.$cm->id; 
    echo html_writer::link($link2, get_string('daily_archive', 'mod_skyroom'), array('class'=>'btn btn-info m-2'));

}


echo $OUTPUT->footer();