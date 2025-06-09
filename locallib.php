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
 * Internal library of functions for module skyroom
 *
 * All the skyroom specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if(!function_exists('saveText')) {
	function saveText($object, $filename='G:/some.txt') {
		$file = fopen($filename, 'a');
		$timestamp = time();
		$date = date('Y/M/d H:i:s');
		fwrite($file, "----------$timestamp----------$date" . PHP_EOL);
		fwrite($file, print_r($object, true));
		fwrite($file, PHP_EOL);
		fclose($file);
	}
}

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

defined('MOODLE_INTERNAL') || die();
define('SKYROOM_DEFAULT_UPDATE_INTERVAL', 30);

$skyroom_base_url = 'https://www.skyroom.online/skyroom/api';
//$skyroom_base_url = 'https://api2.skyroom.online/skyroom/api';

require_once($CFG->dirroot.'/calendar/lib.php');

require_once('skyroom_global_functions.php');
require_once($CFG->dirroot.'/local/excel/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

//some emails are very long and skyroom gives error.
function skyroom_createPasswordFromEmail($email) {
    return substr(md5($email), 0, 10);
}

function skyroom_createPasswordFromUsername($username) {
    return substr($username, strlen($username) - 4, 4);
}

function skyroom_getUpdateInterval() {
    $config = get_config('skyroom');
    if(isset($config->updateinterval)) {
        return $config->updateinterval;
    }
    return SKYROOM_DEFAULT_UPDATE_INTERVAL;
}

function skyroom_getLoginUrl($skyroom, $skyroom_user, $access) {
    global $DB, $USER;
    $skyroom_login = $DB->get_record('skyroom_login', ['user_id' => $USER->id, 'skyroom_id' => $skyroom->id], '*');
    if($skyroom_login) {
        if((time() - $skyroom_login->timecreated) > skyroom_getUpdateInterval() * 24 * 3600){
            $result = skyroom_createLoginUrl($skyroom->room_id, $skyroom_user->skyroom_user_id, $USER->firstname . ' ' . $USER->lastname , $access, skyroom_getUpdateInterval() * 24 * 3600 + 3600);
            if($result->ok) {
                $skyroom_login->login_url = $result->result;
                $skyroom_login->timecreated = time();
                $DB->update_record('skyroom_login', $skyroom_login);
                return $result->result;
            } else {
                print_error(print_r($result, true));
            }
        } else {
            return $skyroom_login->login_url;
        }
    } else {
        $result = skyroom_createLoginUrl($skyroom->room_id, $skyroom_user->skyroom_user_id, $USER->firstname . ' ' . $USER->lastname , $access, skyroom_getUpdateInterval() * 24 * 3600 + 3600);
        if($result->ok) {
            $skyroom_login = new stdClass();
            $skyroom_login->user_id = $USER->id;
            $skyroom_login->skyroom_id = $skyroom->id;
            $skyroom_login->login_url = $result->result;
            $skyroom_login->timecreated = time();
            $DB->insert_record('skyroom_login', $skyroom_login);
            return $result->result;
        } else {
            print_error(print_r($result, true));
        }
    }
}

//force
//$skyroom_user from mdl_skyroom_users table
//$user from mdl_user table (for firstname and lastname)
function skyroom_getLoginUrl2($skyroom, $skyroom_user, $access, $user=null) {
    global $DB, $USER;
    if(!$user) {
        $user = $USER;
    }
    $skyroom_login = $DB->get_record('skyroom_login', ['user_id' => $skyroom_user->user_id, 'skyroom_id' => $skyroom->id], '*');
    if($skyroom_login) {
        $result = skyroom_createLoginUrl($skyroom->room_id, $skyroom_user->skyroom_user_id, $user->firstname . ' ' . $user->lastname , $access, skyroom_getUpdateInterval() * 24 * 3600 + 3600);
        if($result->ok) {
            $skyroom_login->login_url = $result->result;
            $skyroom_login->timecreated = time();
            $DB->update_record('skyroom_login', $skyroom_login);
            return $result->result;
        } else {
            print_error(print_r($result, true));
        }
    } else {
        $result = skyroom_createLoginUrl($skyroom->room_id, $skyroom_user->skyroom_user_id, $user->firstname . ' ' . $user->lastname , $access, skyroom_getUpdateInterval() * 24 * 3600 + 3600);
        if($result->ok) {
            $skyroom_login = new stdClass();
            $skyroom_login->user_id = $skyroom_user->user_id;
            $skyroom_login->skyroom_id = $skyroom->id;
            $skyroom_login->login_url = $result->result;
            $skyroom_login->timecreated = time();
            $DB->insert_record('skyroom_login', $skyroom_login);
            return $result->result;
        } else {
            print_error(print_r($result, true));
        }
    }
}

function skyroom_createUser2($u=null) {
    global $USER, $DB;
    if(!$u) {
        $u = $USER;
    }
    $skyroom_user = $DB->get_record('skyroom_users', ['user_id' => $u->id], '*');
    if(!$skyroom_user) {
        $user = skyroom_createUser($u->username, skyroom_createPasswordFromEmail($u->email), $u->firstname . ' ' . $u->lastname, $u->email);
        if($user->ok) {
            $skyroom_user = new stdClass();
            $skyroom_user->user_id = $u->id;
            $skyroom_user->skyroom_user_id = $user->result;
            $skyroom_user->username = $u->username;
            $skyroom_user->timecreated = time();
            $skyroom_user->timemodified = time();
            $DB->insert_record('skyroom_users', $skyroom_user);
        } else {
            print_error(print_r($user, true));
        }
    } else {
        if((time() - $skyroom_user->timemodified) > skyroom_getUpdateInterval() * 24 * 3600){
            $result = skyroom_updateUser($skyroom_user->skyroom_user_id, $u->username, skyroom_createPasswordFromEmail($u->email), $u->firstname . ' ' . $u->lastname);
            if($result->ok) {
                $skyroom_user->timemodified = time();
                $skyroom_user->username = $u->username;
                $DB->update_record('skyroom_users', $skyroom_user);
            } else {
                $DB->delete_records('skyroom_users', ['user_id' => $u->id]);
                //احتمالا کاربر در اسکای روم حذف شده است ولی در مودل وجود دارد
                $user = skyroom_createUser($u->username, skyroom_createPasswordFromEmail($u->email), $u->firstname . ' ' . $u->lastname, $u->email);
                if($user->ok) {
                    $skyroom_user = new stdClass();
                    $skyroom_user->user_id = $u->id;
                    $skyroom_user->skyroom_user_id = $user->result;
                    $skyroom_user->username = $u->username;
                    $skyroom_user->timecreated = time();
                    $skyroom_user->timemodified = time();
                    $DB->insert_record('skyroom_users', $skyroom_user);
                } else {
                    print_error(print_r($user, true));
                }
            }
        }
    }
    return $skyroom_user;
}

//without cache
function skyroom_createUser3($u=null) {
    global $USER, $DB;
    if(!$u) {
        $u = $USER;
    }
    $skyroom_user = $DB->get_record('skyroom_users', ['user_id' => $u->id], '*');
    if(!$skyroom_user) {
        $check_user = skyroom_getUserByUsername($u->username);
        //نام کاربری تکراری نباشد
        if(!$check_user->ok) {
            $user = skyroom_createUser($u->username, skyroom_createPasswordFromEmail($u->email), $u->firstname . ' ' . $u->lastname, $u->email);
            if(!$user->ok) {
                print_error(print_r($user, true));
            }
        } else {
            $user = new stdClass();
            $user->result = $check_user->result->id;
        }
        $skyroom_user = new stdClass();
        $skyroom_user->user_id = $u->id;
        $skyroom_user->skyroom_user_id = $user->result;
        $skyroom_user->username = $u->username;
        $skyroom_user->timecreated = time();
        $skyroom_user->timemodified = time();
        $DB->insert_record('skyroom_users', $skyroom_user);      
    } else {
        $result = skyroom_updateUser($skyroom_user->skyroom_user_id, $u->username, skyroom_createPasswordFromEmail($u->email), $u->firstname . ' ' . $u->lastname);
        if($result->ok) {
            $skyroom_user->timemodified = time();
            $skyroom_user->username = $u->username;
            $DB->update_record('skyroom_users', $skyroom_user);
        } else {
            $DB->delete_records('skyroom_users', ['user_id' => $u->id]);
            //احتمالا کاربر در اسکای روم حذف شده است ولی در مودل وجود دارد
            $check_user = skyroom_getUserByUsername($u->username);
            //نام کاربری تکراری نباشد
            if(!$check_user->ok) {
                $user = skyroom_createUser($u->username, skyroom_createPasswordFromEmail($u->email), $u->firstname . ' ' . $u->lastname, $u->email);
                if(!$user->ok) {
                    print_error(print_r($user, true));
                }
            } else {
                $user = new stdClass();
                $user->result = $check_user->result->id;
            }
            $skyroom_user = new stdClass();
            $skyroom_user->user_id = $u->id;
            $skyroom_user->skyroom_user_id = $user->result;
            $skyroom_user->username = $u->username;
            $skyroom_user->timecreated = time();
            $skyroom_user->timemodified = time();
            $DB->insert_record('skyroom_users', $skyroom_user);   
        }
    }
    return $skyroom_user;
}

function skyroom_createRoom2($skyroom) {
    $room = skyroom_createRoom($skyroom->classname, $skyroom->name, $skyroom->guestlogin == 1, $skyroom->service);
    if($room->ok) {
        $skyroom->room_id = $room->result;
        $room_url = skyroom_getRoomUrl($skyroom->room_id);
        if($room_url->ok) {
            $skyroom->room_url = $room_url->result;
        }
    } else {
        print_error(print_r($room, true));
    }
    return $skyroom;
}

//events

function skyroom_get_event_calendar($skyroom, $cm) {
    global $DB;
    $event = new stdClass();
    $event->eventtype = SKYROOM_EVENT_TYPE_CLASS_START; // Constant defined somewhere in your code - this can be any string value you want. It is a way to identify the event.
    $event->type = CALENDAR_EVENT_TYPE_STANDARD; // This is used for events we only want to display on the calendar, and are not needed on the block_myoverview.
    $event->name = $skyroom->name;
    $event->description = $skyroom->intro;
    $event->courseid = $cm->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'skyroom';
    $event->instance = $cm->instance;
    $event->timestart = $skyroom->classtimestarted;
    $event->visible = instance_is_visible('skyroom', $cm);
    $event->timeduration = $skyroom->classtimeended - $skyroom->classtimestarted;
    $event->id = $DB->get_field('event', 'id',
        array('modulename' => 'skyroom', 'instance' => $cm->instance, 'eventtype' => SKYROOM_EVENT_TYPE_CLASS_START));
    return $event;
}


function skyroom_event_created(\core\event\course_module_created $object) {
    global $DB;
    $data = $object->get_data();
    $modulename = $data['other']['modulename'];
    //this event is called for entire modules.
    if($modulename == 'skyroom') {
        //course module (no course id and no skyroom instance id)
        $cm         = get_coursemodule_from_id('skyroom', $data['objectid'], 0, false, MUST_EXIST);
        $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $skyroom    = $DB->get_record('skyroom', array('id' => $cm->instance), '*', MUST_EXIST);

        //create calendar event
        $event = skyroom_get_event_calendar($skyroom, $cm);
        calendar_event::create($event);
    }
}

function skyroom_event_updated(\core\event\course_module_updated $object) {
    global $DB, $force;
    $data = $object->get_data();
    $modulename = $data['other']['modulename'];
    //this event is called for entire modules.
    if($modulename == 'skyroom') {
        //course module (no course id and no skyroom instance id)
        $cm = get_coursemodule_from_id('skyroom', $data['objectid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $skyroom = $DB->get_record('skyroom', array('id' => $cm->instance), '*', MUST_EXIST);

        //update calendar event
        $event = skyroom_get_event_calendar($skyroom, $cm);
        $calendar_event = calendar_event::load($event);
        $calendar_event->update($event);
    }
}

function skyroom_event_updated_before(\mod_skyroom\event\course_module_updated_before $object){
    global $DB;
    $data = $object->get_data();
    $modulename = $data['other']['modulename'];
    //this event is called for entire modules.
    if($modulename == 'skyroom') {
        //course module (no course id and no skyroom instance id)
        $cm = get_coursemodule_from_id('skyroom', $data['objectid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $skyroom = $DB->get_record('skyroom', array('id' => $cm->instance), '*', MUST_EXIST);
    }
}

function skyroom_event_deleted(\mod_skyroom\event\course_module_deleted_before $object) {
    global $DB;
    $data = $object->get_data();
    $modulename = $data['other']['modulename'];
    //this event is called for entire modules.
    if($modulename == 'skyroom') {
        //course module (no course id and no skyroom instance id)
        $cm = get_coursemodule_from_id('skyroom', $data['objectid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $skyroom = $DB->get_record('skyroom', array('id' => $cm->instance), '*', MUST_EXIST);
        $result = skyroom_deleteRoom($skyroom->room_id);
    }
}

//teacher without editing
function skyroom_isTeacher($course, $user=null) {
    global $DB, $USER;
    if(!$user) {
        $user = $USER;
    }
    $role = $DB->get_record('role', array('shortname' => 'teacher'));
    $context_course = context_course::instance($course->id);
    $teachers = get_role_users($role->id, $context_course);
    foreach($teachers as $teacher) {
        if($teacher->id == $user->id) {
            return true;
        }
    }
    return false;
}

//---------HOSSEIN MOTAMEN---------

function skyroom_timestampToDate($time){

    $date = date('Y-m-d',$time);
    $start_date = $date . ' 00:00:01';
    list($date, $time) = explode(' ', $start_date);
    list($year, $month, $day) = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time); 
    $start_date_timestamp = mktime($hour, $minute, $second, $month, $day, $year);

    $end_date = $date . ' 23:59:59';
    list($date, $time) = explode(' ', $end_date);
    list($year, $month, $day) = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time); 
    $end_date_timestamp = mktime($hour, $minute, $second, $month, $day, $year);

   $result = new stdclass();
   $result->start_date = $start_date_timestamp;
   $result->end_date = $end_date_timestamp;
   return $result;

}

function skyroom_fetchLogs($id, $cm, $time=null){
    global $DB;
    if($time){
        $time = skyroom_timestampToDate($time);
        $sql = " SELECT LEFT(UUID(), 14) as rand, u.id as user_id,u.firstname,u.lastname,u.username,c.id as course_id ,c.shortname,s.id as skyroom_id,s.name,sl.timecreated  FROM {skyroom_logs} sl JOIN {skyroom} s ON sl.skyroom_id=s.id JOIN {course} c ON s.course=c.id JOIN {course_modules} cm ON cm.instance=s.id JOIN {user} u ON sl.user_id=u.id WHERE cm.module='$cm->module' AND cm.deletioninprogress=0 AND sl.skyroom_id='$id' AND sl.timecreated>'$time->start_date' AND sl.timecreated<'$time->end_date'   ";
        $result = $DB->get_records_sql($sql);
        return $result;
    }else{
        $sql = "SELECT LEFT(UUID(), 14) as rand, u.id as user_id,u.firstname,u.lastname,u.username,c.id as course_id ,c.shortname,s.id as skyroom_id,s.name,sl.timecreated  FROM {skyroom_logs} sl JOIN {skyroom} s ON sl.skyroom_id=s.id JOIN {course} c ON s.course=c.id JOIN {course_modules} cm ON cm.instance=s.id JOIN {user} u ON sl.user_id=u.id WHERE cm.module='$cm->module' AND cm.deletioninprogress=0 ";
        $result = $DB->get_records_sql($sql);
        return $result;
    }
}

function skyroom_isInArray($array, $id){
    foreach($array as $value){
        if($value->userid==$id){
            return true;
        }
    }
    return false;
}
function skyroom_getRoleByIdOrName($role_id=null, $role_name=null){
    global $DB;
    if($role_id){
        $result = $DB->get_field('role', 'shortname', array ('id'=>$role_id), $strictness=MUST_EXIST);
        return $result;
    }elseif($role_name){
        $result = $DB->get_field('role', 'id', array ('shortname'=>$role_name), $strictness=MUST_EXIST);
        return $result->id;
    }else{
        print_error("Failed,ID OR NAME!! NOT BOTH OR NEITHER");
    }
}

function skyroom_getTeachers($array){

    $teachers = [];
    foreach($array as $arr){
        foreach($arr as $value){
            $role = skyroom_getRoleByIdOrName($value->roleid);
            if($role == 'teacher' || $role == 'editingteacher'){
                $value->role = $role;
                $teachers[] = $value;
            }
        }
    }
    return $teachers;
}

function skyroom_createExcelFile($array, $n=null, $dest=null, $where = null){
    if($where == 'view' ){
        $header[] =  [
            'content'=>array(get_string('row','mod_skyroom'),get_string('name','mod_skyroom'),get_string('date','mod_skyroom'), get_string('status','mod_skyroom'), get_string('phone1','mod_skyroom'), get_string('phone2','mod_skyroom'), get_string('username','mod_skyroom') ),
        ];
        $Spreadsheet = new Spreadsheet();
        $i=2;
        $Spreadsheet->getActiveSheet()->fromArray($header[0], NULL, 'A1' );
        foreach($array as $value){
            foreach($value as $val ){
            
                $Spreadsheet->getActiveSheet()->fromArray($val, NULL, 'A' . ($i++));
        }
        }
    }elseif($where == 'daily' ){
        $i = 1 ;
        $Spreadsheet = new Spreadsheet();
        foreach($array as $value){
     
            $Spreadsheet->getActiveSheet()->fromArray($value, NULL, 'A' . ($i++));
   
        }
    }
    if($n!=null){
        $name = $n;

    }else{
        $name = skyroom_rand_char(5);
    }
    if($dest!=null){
        $destination = $dest;
    }else{
        $destination = 'logs';
    }
    
    $objWriter = IOFactory::createWriter($Spreadsheet, 'Xlsx');
    if(!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }
    $objWriter->save("$destination/$name.xlsx");
    return "$name.xlsx";
 }

 function skyroom_rand_char($length)
 {
     $random = '';
     for ($i = 0; $i < $length; $i++) {
         $select = rand(1, 2);
         if ($select == 1) {
             $random .= chr(mt_rand(48, 57));
         } elseif ($select == 2) {
             $random .= chr(mt_rand(97, 122));
         }
     }
     return $random;
 }

 function skyroom_logTable($date, $context, $teachers ){
    global $CFG ; 
    $users = get_enrolled_users($context);
    $logs = [];
    if($date){ 
        $i = 1 ;
        echo html_writer::start_tag('table',array('class'=>'table'));
            echo html_writer::start_tag('thead');
                echo html_writer::start_tag('tr' );
                    echo html_writer::start_tag('th' );
                    echo get_string('row','mod_skyroom');
                    echo html_writer::end_tag('th');
                    echo html_writer::start_tag('th' );
                    echo get_string('name','mod_skyroom');
                    echo html_writer::end_tag('th');
                    echo html_writer::start_tag('th' );
                    echo get_string('date','mod_skyroom');
                    echo html_writer::end_tag('th');
                echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead' );

            echo html_writer::start_tag('tbody' );
            foreach($users as $user){
                $role = '';
                if( skyroom_isInArray($teachers, $user->id )){
                    $role = '('.get_string('defaultcourseteacher').')';
                }
                $ok = false;
                foreach($date as  $present ){
            
                    $entrytime = new \calendartype_jalali\structure();
                    $entrytime = $entrytime->timestamp_to_date_array($present->timecreated);
                    if($user->id == $present->user_id && $user->suspended ==0  ){
                    
                            echo html_writer::start_tag('tr', array('class'=>'table-success') );
                                echo html_writer::start_tag('td' );
                                    echo $i; 
                                echo html_writer::end_tag('td');
            
                                echo html_writer::start_tag('td');
                                    $link = $CFG->wwwroot . "/user/profile.php?id=$user->id";
                                    echo html_writer::link($link, $user->firstname.' '. $user->lastname.$role) ;
                                echo html_writer::end_tag('td');

                                echo html_writer::start_tag('td' );
                                    echo $entrytime['weekday'] . ' ' .  $entrytime['mday'] . ' '. $entrytime['month'].' '. get_string('hour','mod_skyroom').' '.$entrytime['hours'] .':'.$entrytime['minutes'];
                                echo html_writer::end_tag('td');
                            echo html_writer::end_tag('tr');
                            $ok = true;
                            $logs []=[
                                'content'=>array($i,$user->firstname.' '. $user->lastname, $entrytime['weekday'] . ' ' .  $entrytime['mday'] . ' '. $entrytime['month'].' '. get_string('hour','mod_skyroom').' '.$entrytime['hours'] .':'.$entrytime['minutes'] ,get_string('present','mod_skyroom'), $user->phone1, $user->phone2, $user->username )
                            ];
                            $i++;

                            break;
                        
                    }
                }
                if(!$ok && $user->suspended == 0) {
                    echo html_writer::start_tag('tr', array('class'=>'table-danger') );
                        echo html_writer::start_tag('td' );
                        echo $i; 
                        echo html_writer::end_tag('td');

                        echo html_writer::start_tag('td');
                        $link = $CFG->wwwroot . "/user/profile.php?id=$user->id";
                        echo html_writer::link($link, $user->firstname.' '. $user->lastname.$role) ;
                        echo html_writer::end_tag('td');


                        echo html_writer::start_tag('td' );
                        echo $entrytime['weekday'] . ' ' .  $entrytime['mday'] . ' '. $entrytime['month']   ;
                        echo html_writer::end_tag('td');
                    echo html_writer::end_tag('tr');

                    $logs []=[
                        'content'=>array($i,$user->firstname.' '. $user->lastname, $entrytime['weekday'] . ' ' .  $entrytime['mday'] . ' '. $entrytime['month'], get_string('absent','mod_skyroom'), $user->phone1, $user->phone2, $user->username)
                        
                    ];
                    $i++;
                } 

            }      

            echo html_writer::end_tag('tbody' );
        echo html_writer::end_tag('table' );
        $logs []=[
            'content'=>array('-', '-', '-', '-', '-', '-', '-')                    
        ];
    }
    return $logs;     
}

function skyroom_logTable_excel($date, $context, $logs, $teachers ){
    global $CFG ; 
    $users = get_enrolled_users($context);
    if($date){ 
        $i = 1 ;
            foreach($users as $user){
                $role = '';
                if( skyroom_isInArray($teachers, $user->id )){
                    $role = '('.get_string('defaultcourseteacher').')';
                }
                $ok = false;
                foreach($date as  $present ){
            
                    $entrytime = new \calendartype_jalali\structure();
                    $entrytime = $entrytime->timestamp_to_date_array($present->timecreated);
                    if($user->id == $present->user_id && $user->suspended ==0  ){           
                        $ok = true;
                        $logs []=[
                            'content'=>array($i,$user->firstname.' '. $user->lastname.' '.$role, $entrytime['weekday'] . ' ' .  $entrytime['mday'] . ' '. $entrytime['month'].' '. get_string('hour','mod_skyroom').' '.$entrytime['hours'] .':'.$entrytime['minutes'] ,get_string('present','mod_skyroom'), $user->phone1, $user->phone2, $user->username )
                        ];
                        $i++;

                        break;
                        
                    }
                }
                if(!$ok && $user->suspended == 0) {

                    $logs []=[
                        'content'=>array($i,$user->firstname.' '. $user->lastname.' '.$role, $entrytime['weekday'] . ' ' .  $entrytime['mday'] . ' '. $entrytime['month'], get_string('absent','mod_skyroom'), $user->phone1, $user->phone2, $user->username)
                        
                    ];
                    $i++;
                } 
            }      
        $logs []=[
            'content'=>array('-', '-', '-', '-', '-', '-', '-')
            
        ];
    }               
   return $logs;            
}