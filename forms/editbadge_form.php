<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class local_automatic_badges_editbadge_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // === Identificadores y seguridad ===
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // === Datos principales de la insignia ===
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required');

        $mform->addElement('editor', 'description_editor', get_string('description'));
        $mform->setType('description_editor', PARAM_RAW);

        // === Informacion del emisor ===
        $mform->addElement('text', 'issuername', get_string('issuername', 'core_badges'));
        $mform->setType('issuername', PARAM_TEXT);

        $mform->addElement('text', 'issuercontact', get_string('contact', 'core_badges'));
        $mform->setType('issuercontact', PARAM_TEXT);

        // === Periodo de validez ===
        $mform->addElement('date_selector', 'expirydate', get_string('expirydate', 'core_badges'), ['optional' => true]);

        // === Comunicaciones automaticas ===
        $mform->addElement('textarea', 'message', get_string('message', 'core_badges'), 'rows="4"');
        $mform->setType('message', PARAM_RAW_TRIMMED);

        // === Estado de publicacion ===
        $mform->addElement('advcheckbox', 'statusenable', get_string('enable', 'core'), null, null, [0,1]);

        // === Acciones del formulario ===
        $this->add_action_buttons(true, get_string('savechanges'));
    }
}


