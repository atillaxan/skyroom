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
 * Defines the before update event.
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_skyroom\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_skyroom instance before updating. we need database data.
 *
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_updated_before extends \core\event\base {

    /**
     * Initialize the event
     */
    protected function init() {
        $this->data['objecttable'] = 'skyroom';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

}
