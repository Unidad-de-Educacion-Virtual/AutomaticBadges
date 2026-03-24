<?php
// This file is part of local_automatic_badges - https://moodle.org/.
//
// local_automatic_badges is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// local_automatic_badges is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with local_automatic_badges.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Form to edit badge details in local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Edit badge form.
 *
 * @package    local_automatic_badges
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_automatic_badges_editbadge_form extends moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        // Identifiers.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Badge main data.
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required');

        $mform->addElement('editor', 'description_editor', get_string('description'));
        $mform->setType('description_editor', PARAM_RAW);

        // Issuer information.
        $mform->addElement('text', 'issuername', get_string('issuername', 'core_badges'));
        $mform->setType('issuername', PARAM_TEXT);

        $mform->addElement('text', 'issuercontact', get_string('contact', 'core_badges'));
        $mform->setType('issuercontact', PARAM_TEXT);

        // Validity period.
        $mform->addElement('date_selector', 'expirydate', get_string('expirydate', 'core_badges'), ['optional' => true]);

        // Automatic communications.
        $mform->addElement('textarea', 'message', get_string('message', 'core_badges'), ['rows' => 4]);
        $mform->setType('message', PARAM_RAW_TRIMMED);

        // Publication status.
        $mform->addElement('advcheckbox', 'statusenable', get_string('enable', 'core'), null, null, [0, 1]);

        // Form actions.
        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
