<?php
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

require_login($course, true, $cm);

require_once('locallib.php');

$skyroom_user = skyroom_createUser2();
if(!$skyroom_user) {
    print_error(print_r($skyroom_user, true));
    exit;
}

$context = context_module::instance($cm->id);

if(has_capability('moodle/course:update', $context)) {
    //admin and editing teacher
    $login_url = skyroom_getLoginUrl($skyroom, $skyroom_user, 3);
} else if(skyroom_isTeacher($course)) {
    //teacher
    $login_url = skyroom_getLoginUrl($skyroom, $skyroom_user, 2);
} else {
    //student
    $login_url = skyroom_getLoginUrl($skyroom, $skyroom_user, 1);
}

$obj = new stdclass();
$obj->user_id = $USER->id;
$obj->skyroom_id = $skyroom->id;
$obj->login_url = $login_url;
$obj->timecreated = time();
$id = $DB->insert_record('skyroom_logs', $obj);

redirect($login_url);

