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

$context = context_module::instance($cm->id);
$context_system = context_system::instance();
//require_capability('moodle/site:config', $context_system);
require_capability('moodle/course:manageactivities', $context);


// Print the page header.
$page_url = new moodle_url('/mod/skyroom/update.php', array('id' => $cm->id));
$PAGE->set_url('/mod/skyroom/update.php', array('id' => $cm->id));
$PAGE->set_title(format_string($skyroom->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($skyroom->intro) {
    echo $OUTPUT->box(format_module_intro('skyroom', $skyroom, $cm->id), 'generalbox mod_introbox', 'skyroomintro');
}


$context_course = context_course::instance($course->id);
$users = get_enrolled_users($context_course);

$ok_user = [];
$nok_user = [];
$nok_msg = [];
foreach($users as $user) {
    try {
        skyroom_createUser3($user);
        $ok_user[] = $user;
    } catch (Exception $e) {
        $nok_user[] = $user;
        $nok_msg[$user->id] = $e->getMessage();
    }
}

$out = '';
if(count($nok_user) > 0) {
    $out .= html_writer::start_tag('div', array('class'=>'table-responsive px-md-5'));
    $out .= html_writer::start_tag('table', array('class'=>'table table-bordered table-hover table-striped'));
    $out .= html_writer::start_tag('thead');
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('th', array('colspan'=>'2', 'class'=>'text-center alert alert-danger'));
    $out .= html_writer::start_tag('strong');
    $out .= get_string('error');
    $out .= html_writer::end_tag('strong');
    $out .= html_writer::end_tag('th');
    $out .= html_writer::end_tag('tr');
    $out .= html_writer::start_tag('tr');
    $out .= html_writer::start_tag('th');
    $out .= get_string('firstname') . ' / ' . get_string('lastname');
    $out .= html_writer::end_tag('th');
    $out .= html_writer::start_tag('th');
    $out .= get_string('error');
    $out .= html_writer::end_tag('th');
    $out .= html_writer::end_tag('tr');
    $out .= html_writer::end_tag('thead');
    //
    $out .= html_writer::start_tag('tbody');
    
    foreach($nok_user as $u) {
        $out .= html_writer::start_tag('tr');
        $out .= html_writer::start_tag('td');
        $out .= html_writer::link("$CFG->wwwroot/user/view.php?id=$u->id&course=$course->id", $u->firstname . ' ' . $u->lastname . ' (' . $u->username . ')');
        $out .= html_writer::end_tag('td');
        $out .= html_writer::start_tag('td');
        $out .= $nok_msg[$u->id];
        $out .= html_writer::end_tag('td');
        $out .= html_writer::end_tag('tr');
    }
    
    $out .= html_writer::end_tag('tbody');
    $out .= html_writer::end_tag('div');
    
    $out .= html_writer::end_tag('table');
} else {
    $out .= html_writer::start_tag('p', ['class' => 'alert alert-success']);
    $out .= get_string('success');
    $out .= html_writer::end_tag('p');
}

echo $out;

echo $OUTPUT->footer();