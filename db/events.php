<?php

$observers = array(

    /*array(
        'eventname'   => '\mod_skyroom\event\course_module_viewed',
        'callback'    => 'testisdream',
    ),*/
    array(
        'eventname'   => '\core\event\course_module_created',
        'callback'    => 'skyroom_event_created',
        'includefile' => 'mod/skyroom/locallib.php',
    ),
    array(
        'eventname'   => '\core\event\course_module_updated',
        'callback'    => 'skyroom_event_updated',
        'includefile' => 'mod/skyroom/locallib.php',
    ),
    array(
        'eventname'   => '\mod_skyroom\event\course_module_deleted_before',
        'callback'    => 'skyroom_event_deleted',
        'includefile' => 'mod/skyroom/locallib.php',
    ),
    array(
        'eventname'   => '\mod_skyroom\event\course_module_updated_before',
        'callback'    => 'skyroom_event_updated_before',
        'includefile' => 'mod/skyroom/locallib.php',
    ),
);