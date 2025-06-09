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
 * The main skyroom configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once(dirname(__FILE__).'/locallib.php');

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


/**
 * Module instance settings form
 *
 * @package    mod_skyroom
 * @copyright  2019 Morteza Ahmadi <m.ahmadi.ma@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_skyroom_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('skyroomname', 'skyroom'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'skyroomname', 'skyroom');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        //class name (url)
        $options = array('size' => '64');
        //edit mode
        if($edit_mode = $this->get_instance()) {
            $options['disabled'] = 'disabled';
        }

        $mform->addElement('date_time_selector', 'classtimestarted', get_string('skyroomclasstimestarted', 'skyroom'), array('optional' => true));
        $mform->addHelpButton('classtimestarted', 'skyroomclasstimestarted', 'skyroom');

        $mform->addElement('date_time_selector', 'classtimeended', get_string('skyroomclasstimeended', 'skyroom'), array('optional' => true));
        $mform->addHelpButton('classtimeended', 'skyroomclasstimeended', 'skyroom');

        // Second settings
        $mform->addElement('header', 'secondsettingheader', get_string('skyroomsecondsettingheader', 'skyroom'));
        

        //guest login check box
        $mform->addElement('advcheckbox', 'guestlogin', get_string('skyroomguestlogin', 'skyroom'), '', null, array(0, 1));
        $mform->setDefault('guestlogin', 0);
        $mform->addHelpButton('guestlogin', 'skyroomguestlogin', 'skyroom');

        $mform->addElement('text', 'classname', get_string('skyroomclassname', 'skyroom'), $options);
        $mform->setDefault('classname', skyroom_createHash());
        $mform->addRule('classname', null, $edit_mode ? '' : 'required', null, 'client');
        $mform->addHelpButton('classname', 'skyroomclassname', 'skyroom');

        
        $_services = skyroom_getServices();
        $services = [];
        if($_services->ok) {
            foreach($_services->result as $_service) {
                if($_service->status) {
                    $services[$_service->id] = $_service->title;
                }
            }
        }
        $select = $mform->addElement('select', 'service', get_string('skyroomservice', 'skyroom'), $services, $attributes);

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
    }

    public function data_postprocessing($data) {
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        //create mode
        if(!$this->get_instance()) {
            //it already exists;
            if (skyroom_checkRoomExist($data['classname'])) {
                $errors['classname'] = get_string('alreadyexistserror', 'skyroom');
            }
        }
        return $errors;
    }
}
