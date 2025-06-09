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
 * Skyroom module admin settings and defaults
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi  <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->dirroot/mod/skyroom/locallib.php");

    $settings->add(new admin_setting_heading('skyroommodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configtextarea('skyroom/apikey',
        get_string('skyroomapikey', 'skyroom'), get_string('skyroomapikey_help', 'skyroom'), '', PARAM_RAW));

    $settings->add(new admin_setting_configtext('skyroom/updateinterval',
        get_string('skyroomupdateinterval', 'skyroom'), get_string('skyroomupdateinterval_help', 'skyroom'), SKYROOM_DEFAULT_UPDATE_INTERVAL, PARAM_INT));
}
