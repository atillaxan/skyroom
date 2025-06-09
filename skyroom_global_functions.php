<?php

if (!function_exists('sendLog')) {
    function sendLog($log)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://iranischool.com/.log/save.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "log=" . print_r($log, true));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}


function skyroom_get_complete_url()
{
    return require('return_url.php');
}

//$data_string is json:
//{"action": "getRooms"}
//$result is json:
//{"ok":true,"result":[{"id":10349,"name":"test","title":"\u0627\u062a\u0627\u0642 \u0622\u0632\u0645\u0627\u06cc\u0634\u06cc","status":1}]}
function skyroom_request($data_string, &$http_code)
{
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, skyroom_get_complete_url());
    curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($c, CURLOPT_TIMEOUT, 15); //timeout in seconds
    curl_setopt($c, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ));
    $result = curl_exec($c);
    $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    return $result;
}

//$data_string is json or array
function skyroom_request_and_check($data_string)
{
    if (is_array($data_string)) {
        $data_string = json_encode($data_string, JSON_UNESCAPED_UNICODE);
    }
    $result = skyroom_request($data_string, $http_code);
    if ($http_code == 200) {
        $result = json_decode($result);
        return $result;
    } else {
        if (function_exists('print_error')) {

            print_error('servererror', 'mod_skyroom');
        }
        return false;
    }
}

function skyroom_createRoom($name, $title, $guest_login, $service_id = 0, $op_login_first = true, $max_users = 0, $session_duration = 0)
{
    $data_string = array(
        "action" => "createRoom",
        "params" => [
            "name" => $name,
            "title" => $title,
            "guest_login" => $guest_login,
            "op_login_first" => $op_login_first,
            "max_users" => $max_users == 0 ? '' : $max_users,
            "session_duration" => $session_duration == 0 ? '' : $session_duration,
            "service_id" => $service_id,
        ],
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_updateRoom($room_id, $name = '', $title = '', $guest_login = '', $service_id = 0, $op_login_first = '', $max_users = '', $session_duration = '')
{
    $params["room_id"] = $room_id;
    if ($name !== '') {
        $params["name"]  = $name;
    }
    if ($title !== '') {
        $params["title"]  = $title;
    }
    if ($guest_login !== '') {
        $params["guest_login"]  = $guest_login;
    }
    if ($op_login_first !== '') {
        $params["op_login_first"]  = $op_login_first;
    }
    if ($max_users !== '') {
        $params["max_users"]  = $max_users;
    }
    if ($session_duration !== '') {
        $params["session_duration"]  = $session_duration;
    }
    if ($service_id != 0) {
        $params["service_id"]  = $service_id;
    }
    $data_string = array(
        "action" => "updateRoom",
        "params" => $params,
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_getRooms()
{
    $data_string = array(
        "action" => "getRooms",
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_getRoom($room_id)
{
    $data_string = array(
        "action" => "getRoom",
        "params" => [
            "room_id" => $room_id,
        ],
    );
    return skyroom_request_and_check($data_string);
}

//we can add user to multiple room but we only add to one.
/*
    $access => 1 => کاربر عادی
    $access => 2 => ارائه کننده
    $access => 3 => اپراتور
    $access => 4 => مدیر
*/
function skyroom_addRoomUser($room_id, $skyroom_user_id, $access)
{
    $data_string = array(
        "action" => "addRoomUsers",
        "params" => [
            "room_id" => $room_id,
            "users" => [
                [
                    "user_id" => $skyroom_user_id,
                    "access" => $access,
                ],
            ]
        ],
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_UpdateRoomUser($room_id, $skyroom_user_id, $access)
{
    $data_string = array(
        "action" => "updateRoomUser",
        "params" => [
            "room_id" => $room_id,
            "user_id" => $skyroom_user_id,
            "access" => $access,
        ],
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_addOrUpdateRoomUser($room_id, $skyroom_user_id, $access)
{
    $result = skyroom_addRoomUser($room_id, $skyroom_user_id, $access);
    if (!$result->ok) {
        $result = skyroom_UpdateRoomUser($room_id, $skyroom_user_id, $access);
    }
    return $result;
}

//it already exists;
function skyroom_checkRoomExist($name_of_room)
{
    if ($rooms = skyroom_getRooms()) {
        $rooms = $rooms->result;
        foreach ($rooms as $room) {
            if ($room->name == $name_of_room) {
                return true;
            }
        }
    }
    return false;
}

function skyroom_getUser($skyroom_user_id)
{
    $data_string = array(
        "action" => "getUser",
        "params" => [
            "user_id"  => $skyroom_user_id,
        ],
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_getUserByUsername($username)
{
    $data_string = array(
        "action" => "getUser",
        "params" => [
            "username"  => $username,
        ],
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_getUsers()
{
    $data_string = array(
        "action" => "getUsers",
    );
    return skyroom_request_and_check($data_string);
}

//return skyroom user id if exists.
function skyroom_checkUserExist($username, $skyroom_users = null)
{
    if ($skyroom_users) {
        $result = $skyroom_users;
    } else {
        $result = skyroom_getUsers();
    }
    $users = $result->result;
    foreach ($users as $user) {
        if ($user->username == $username) {
            return $user->id;
        }
    }
    return false;
}

function skyroom_createUser($username, $password, $nickname, $email, $status = 1, $is_public = false)
{
    $data_string = array(
        "action" => "createUser",
        "params" => [
            "username"  => $username,
            "password"  => $password,
            "nickname"  => $nickname,
            "status"    => $status,
            "email"     => $email,
            "is_public" => $is_public,
        ],
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_updateUser($skyroom_user_id, $username = '', $password = '', $nickname = '', $email = '', $status = '', $is_public = '')
{
    $params["user_id"] = $skyroom_user_id;
    if ($username !== '') {
        $params["username"]  = $username;
    }
    if ($password !== '') {
        $params["password"]  = $password;
    }
    if ($nickname !== '') {
        $params["nickname"]  = $nickname;
    }
    if ($email !== '') {
        $params["email"]  = $email;
    }
    if ($status !== '') {
        $params["status"]  = $status;
    }
    if ($is_public !== '') {
        $params["is_public"]  = $is_public;
    }
    $data_string = array(
        "action" => "updateUser",
        "params" => $params,
    );
    return skyroom_request_and_check($data_string);
}


//url without login (600s = 10min)
/*function skyroom_getLoginUrl($room_id, $skyroom_user_id, $language = 'fa', $ttl = 600)
{
    $data_string = array(
        "action" => "getLoginUrl",
        "params" => [
            "room_id"  => $room_id,
            "user_id"  => $skyroom_user_id,
            "language"  => $language,
            "ttl"    => $ttl,
        ],
    );
    return skyroom_request_and_check($data_string);
}*/

//with username and password (default password is email)
function skyroom_getRoomUrl($room_id, $language = 'fa')
{
    $data_string = array(
        "action" => "getRoomUrl",
        "params" => [
            "room_id"  => $room_id,
            "language"  => $language,
        ],
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_deleteRoom($room_id)
{
    $data_string = array(
        "action" => "deleteRoom",
        "params" => [
            "room_id"  => $room_id,
        ],
    );
    return skyroom_request_and_check($data_string);
}

//$users ==> array ==> [ 6344, 6345 ]
function skyroom_removeRoomUsers($room_id, $users)
{
    $data_string = array(
        "action" => "removeRoomUsers",
        "params" => [
            "room_id"  => $room_id,
            "users"  => $users,
        ],
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_createHash($length = 10)
{
    $hash = md5(time());
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $index = rand(0, strlen($hash) - 1);
        $out .= $hash[$index];
        $hash = substr_replace($hash, "", $index, 1);
    }
    return $out;
}


function skyroom_getServices()
{
    $data_string = array(
        "action" => "getServices",
    );
    return skyroom_request_and_check($data_string);
}

function skyroom_createLoginUrl($room_id, $skyroom_user_id, $nickname, $access, $ttl, $concurrent=1, $language='fa'){
    $data_string = array(
        "action" => "createLoginUrl",
        "params" => [
            "room_id" => $room_id,
            "user_id" => $skyroom_user_id,
            "nickname" => $nickname,
            "access" => $access,
            "concurrent" => $concurrent,
            "language" => $language,
            "ttl" => $ttl
        ],
    );
    return skyroom_request_and_check($data_string);
}
