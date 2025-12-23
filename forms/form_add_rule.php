<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class local_automatic_badges_add_rule_form extends moodleform {
    /** @var array<int,string> Eligible activities cache */
    protected $eligibleactivities = [];

    // === Definicion del formulario ===
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $ruleid = $this->_customdata['ruleid'] ?? 0;

        // --- Identificadores base ---
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'ruleid', $ruleid);
        $mform->setType('ruleid', PARAM_INT);

        // --- Estado de la regla ---
        $mform->addElement('advcheckbox', 'enabled',
            get_string('ruleenabledlabel', 'local_automatic_badges'));
        $mform->addHelpButton('enabled', 'ruleenabledlabel', 'local_automatic_badges');
        $mform->setType('enabled', PARAM_INT);
        $mform->setDefault('enabled', 1);

        // --- Seleccion del tipo de criterio ---
        $options = [
            'grade'      => get_string('criterion_grade', 'local_automatic_badges'),
            'forum'      => get_string('criterion_forum', 'local_automatic_badges'),
            'submission' => get_string('criterion_submission', 'local_automatic_badges'),
        ];

        $mform->addElement('select', 'criterion_type',
            get_string('criteriontype', 'local_automatic_badges'), $options);
        $mform->addHelpButton('criterion_type', 'criteriontype', 'local_automatic_badges');
        $mform->setDefault('criterion_type', 'grade');
        $mform->addRule('criterion_type', null, 'required', null, 'client');

        // --- Seleccion de la actividad objetivo ---
        $this->eligibleactivities = $this->get_eligible_activities($courseid);
        if (!empty($this->eligibleactivities)) {
            $mform->addElement('select', 'activityid',
                get_string('activitylinked', 'local_automatic_badges'), $this->eligibleactivities);
            $mform->addHelpButton('activityid', 'activitylinked', 'local_automatic_badges');
            $mform->addRule('activityid', null, 'required', null, 'client');
            $mform->setType('activityid', PARAM_INT);
        } else {
            $mform->addElement('static', 'noeligibleactivities', '',
                get_string('noeligibleactivities', 'local_automatic_badges'));
            $mform->addElement('hidden', 'activityid', 0);
            $mform->setType('activityid', PARAM_INT);
        }

        // --- Validaciones especificas del criterio ---
        $mform->addElement('text', 'grade_min',
            get_string('grademin', 'local_automatic_badges'));
        $mform->addHelpButton('grade_min', 'grademin', 'local_automatic_badges');
        $mform->setType('grade_min', PARAM_FLOAT);
        $mform->setDefault('grade_min', 60);

        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('grade_min', 'criterion_type', 'neq', 'grade');
        } else {
            $mform->disabledIf('grade_min', 'criterion_type', 'neq', 'grade');
        }

        $mform->addElement('text', 'forum_post_count',
            get_string('forumpostcount', 'local_automatic_badges'));
        $mform->addHelpButton('forum_post_count', 'forumpostcount', 'local_automatic_badges');
        $mform->setType('forum_post_count', PARAM_INT);
        $mform->setDefault('forum_post_count', 5);
        $mform->addRule('forum_post_count', null, 'numeric', null, 'client');
        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('forum_post_count', 'criterion_type', 'neq', 'forum');
        } else {
            $mform->disabledIf('forum_post_count', 'criterion_type', 'neq', 'forum');
        }

        // --- Seleccion de la insignia ---
        require_once($CFG->dirroot . '/badges/lib.php');
        $badges = badges_get_badges(BADGE_TYPE_COURSE, $courseid);
        $badgeoptions = [];
        foreach ($badges as $badge) {
            $badgeoptions[$badge->id] = $badge->name;
        }

        if (empty($badgeoptions)) {
            $mform->addElement('static', 'nobadges', '',
                get_string('nobadgesavailable', 'local_automatic_badges'));
        } else {
            $mform->addElement('select', 'badgeid',
                get_string('selectbadge', 'local_automatic_badges'), $badgeoptions);
            $mform->addHelpButton('badgeid', 'selectbadge', 'local_automatic_badges');
            $mform->addRule('badgeid', null, 'required', null, 'client');
        }

        // --- Opciones de bonificacion ---
        $mform->addElement('advcheckbox', 'enable_bonus',
            get_string('enablebonus', 'local_automatic_badges'));
        $mform->addHelpButton('enable_bonus', 'enablebonus', 'local_automatic_badges');
        $mform->addElement('text', 'bonus_points',
            get_string('bonusvalue', 'local_automatic_badges'));
        $mform->addHelpButton('bonus_points', 'bonusvalue', 'local_automatic_badges');
        $mform->setType('bonus_points', PARAM_FLOAT);
        $mform->setDefault('bonus_points', 0);
        $mform->disabledIf('bonus_points', 'enable_bonus', 'notchecked');

        // --- Mensaje de notificacion ---
        $mform->addElement('textarea', 'notify_message',
            get_string('notifymessage', 'local_automatic_badges'),
            'wrap="virtual" rows="3" cols="50"');
        $mform->addHelpButton('notify_message', 'notifymessage', 'local_automatic_badges');
        $mform->setType('notify_message', PARAM_TEXT);

        // --- Acciones del formulario ---
        $this->add_action_buttons(true,
            get_string('saverule', 'local_automatic_badges'));
    }

    // === Helpers de actividades ===

    /**
     * Obtiene las actividades del curso elegibles para reglas de insignias.
     *
     * @param int $courseid
     * @return array<int,string>
     */
    protected function get_eligible_activities(int $courseid): array {
        $modinfo = get_fast_modinfo($courseid);
        $activities = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible) {
                continue;
            }

            if (!$this->is_activity_eligible($cm)) {
                continue;
            }
            $activities[$cm->id] = $cm->get_formatted_name();
        }
        return $activities;
    }

    /**
     * Determina si una actividad es valida para otorgar insignias automaticas.
     *
     * @param \cm_info $cm
     * @return bool
     */
    protected function is_activity_eligible(\cm_info $cm): bool {
        $supportsgrades = plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE);
        $supportssubmission = plugin_supports('mod', $cm->modname, FEATURE_COMPLETION_HAS_RULES);
        return !empty($supportsgrades) || !empty($supportssubmission);
    }

    // === Validaciones personalizadas ===

    /**
     * Validacion personalizada del formulario.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $activityid = isset($data['activityid']) ? (int)$data['activityid'] : 0;
        if (empty($this->eligibleactivities)) {
            $errors['activityid'] = get_string('noeligibleactivities', 'local_automatic_badges');
        } else if (!array_key_exists($activityid, $this->eligibleactivities)) {
            $errors['activityid'] = get_string('activitynoteligible', 'local_automatic_badges');
        }

        if (($data['criterion_type'] ?? '') === 'forum') {
            $requiredposts = isset($data['forum_post_count']) ? (int)$data['forum_post_count'] : 0;
            if ($requiredposts <= 0) {
                $errors['forum_post_count'] = get_string('forumpostcounterror', 'local_automatic_badges');
            }
        }

        return $errors;
    }
}
