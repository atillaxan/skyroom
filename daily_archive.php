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
if(has_capability('moodle/course:update', $context)){
    $today = skyroom_fetchLogs($skyroom->id, $cm, time());
    $logs = [];
    $role  = enrol_get_course_users_roles($course->id);
    $teachers = skyroom_getTeachers($role);
    $logs[] =  [
        'content'=>array(get_string('row','mod_skyroom'),get_string('name','mod_skyroom'),get_string('date','mod_skyroom'), get_string('status','mod_skyroom'), get_string('phone1','mod_skyroom'), get_string('phone2','mod_skyroom'), get_string('username','mod_skyroom') ),
    ];
    
    $res = skyroom_logTable_excel($today,$context, $logs, $teachers);
    if(count($res)>0){
        $time = new \calendartype_jalali\structure();
        $time = $time->timestamp_to_date_array(time());
        $name = $time['mday'].'-'. $time['mon'].'-'.$time['year'];
        $des = $CFG->dirroot . '/mod/skyroom/daily_archive';
        skyroom_createExcelFile($res, $name,$des,'daily');

    }
    $names = [];
    $names = scandir($CFG->dirroot . '/mod/skyroom/daily_archive');
    echo html_writer::start_tag('ul');
    foreach($names as $name ){        
        if($name!='.' && $name!='..'){
            echo html_writer::start_tag('li');
                echo html_writer::link($CFG->wwwroot . '/mod/skyroom/daily_archive/'.$name, $name);
            echo html_writer::end_tag('li');
        }
    }
    echo html_writer::end_tag('ul');
    
}   
echo $OUTPUT->footer();