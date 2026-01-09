<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class local_automatic_badges_add_rule_form extends moodleform {
    /** @var array<int,string> Eligible activities cache */
    protected $eligibleactivities = [];

    // === Definicion del formulario ===
    public function definition() {
        global $CFG, $PAGE;

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
        $criteriondefault = $this->_customdata['criterion_type'] ?? 'grade';
        $criterion = optional_param('criterion_type', $criteriondefault, PARAM_ALPHA);
        if (!array_key_exists($criterion, $options)) {
            $criterion = 'grade';
        }

        $mform->addElement('select', 'criterion_type',
            get_string('criteriontype', 'local_automatic_badges'), $options);
        $mform->addHelpButton('criterion_type', 'criteriontype', 'local_automatic_badges');
        $mform->setDefault('criterion_type', $criterion);
        $mform->addRule('criterion_type', null, 'required', null, 'client');

        // --- Seleccion de la actividad objetivo ---
        // Selector anidado segun el criterio.
        $mform->addElement('html', '<div id="local_automatic_badges_activity_container">');
        $criteriaactivities = [
            'grade' => $this->get_eligible_activities($courseid, 'grade'),
            'forum' => $this->get_eligible_activities($courseid, 'forum'),
            'submission' => $this->get_eligible_activities($courseid, 'submission'),
        ];
        $this->eligibleactivities = $criteriaactivities[$criterion] ?? [];
        $mform->addElement('select', 'activityid',
            get_string('activitylinked', 'local_automatic_badges'), $this->eligibleactivities);
        $mform->addHelpButton('activityid', 'activitylinked', 'local_automatic_badges');
        $mform->addRule('activityid', null, 'required', null, 'client');
        $mform->setType('activityid', PARAM_INT);
        $mform->addElement('html', '<div id="local_automatic_badges_activity_warning" class="alert alert-warning" style="display:none;">' .
            get_string('noeligibleactivities', 'local_automatic_badges') . '</div>');
        $mform->addElement('html', '</div>');

        // --- Validaciones especificas del criterio ---
        $operators = [
            '>=' => get_string('operator_gte', 'local_automatic_badges'),
            '>'  => get_string('operator_gt', 'local_automatic_badges'),
            '==' => get_string('operator_eq', 'local_automatic_badges'),
        ];
        $mform->addElement('select', 'grade_operator',
            get_string('gradeoperator', 'local_automatic_badges'), $operators);
        $mform->addHelpButton('grade_operator', 'gradeoperator', 'local_automatic_badges');
        $mform->setType('grade_operator', PARAM_TEXT);
        $mform->setDefault('grade_operator', '>=');
        
        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('grade_operator', 'criterion_type', 'neq', 'grade');
        } else {
            $mform->disabledIf('grade_operator', 'criterion_type', 'neq', 'grade');
        }
        
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

        // --- Resumen/preview de la regla ---
        $mform->addElement('header', 'rulepreviewhdr',
            get_string('rulepreview', 'local_automatic_badges'));

        $mform->addElement('html', '
        <div id="local_automatic_badges_rule_preview" class="alert alert-info" style="border-left: 4px solid #0f6cbf;">
            <div id="local_automatic_badges_rule_preview_text" style="background: rgba(255,255,255,0.6); padding: 12px; border-radius: 4px; margin-top: 10px;"></div>
        </div>
        ');


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
        $mform->hideIf('bonus_points', 'enable_bonus', 'notchecked');

        // --- Mensaje de notificacion ---
        $mform->addElement('textarea', 'notify_message',
            get_string('notifymessage', 'local_automatic_badges'),
            'wrap="virtual" rows="3" cols="50"');
        $mform->addHelpButton('notify_message', 'notifymessage', 'local_automatic_badges');
        $mform->setType('notify_message', PARAM_TEXT);

        // --- Condiciones extra para submission ---
        $mform->addElement('advcheckbox', 'require_submitted',
            get_string('requiresubmitted', 'local_automatic_badges'));
        $mform->setType('require_submitted', PARAM_INT);
        $mform->setDefault('require_submitted', 1);

        $mform->addElement('advcheckbox', 'require_graded',
            get_string('requiregraded', 'local_automatic_badges'));
        $mform->setType('require_graded', PARAM_INT);
        $mform->setDefault('require_graded', 0);

        // Solo aplican para submission
        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('require_submitted', 'criterion_type', 'neq', 'submission');
            $mform->hideIf('require_graded', 'criterion_type', 'neq', 'submission');
        } else {
            $mform->disabledIf('require_submitted', 'criterion_type', 'neq', 'submission');
            $mform->disabledIf('require_graded', 'criterion_type', 'neq', 'submission');
        }

        // --- Modo prueba (dry-run) ---
        $mform->addElement('advcheckbox', 'dry_run',
            get_string('dryrun', 'local_automatic_badges'));
        $mform->setType('dry_run', PARAM_INT);
        $mform->setDefault('dry_run', 0);

        // BotÃ³n extra para probar (no guarda)
        $mform->addElement('submit', 'testrule',
            get_string('testrule', 'local_automatic_badges'));

        // --- Acciones del formulario ---
        $this->add_action_buttons(true,
            get_string('saverule', 'local_automatic_badges'));

        $activityjson = json_encode($criteriaactivities, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $noactivities = json_encode(get_string('noeligibleactivities', 'local_automatic_badges'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $PAGE->requires->js_init_code("
require(['jquery'], function($) {
    $(function() {
        var activityMap = {$activityjson};
        var noActivitiesText = {$noactivities};
        var container = $('#local_automatic_badges_activity_container');
        var select = $('#id_activityid');
        var warning = $('#local_automatic_badges_activity_warning');

        function setOptions(criterion) {
            var activities = activityMap[criterion] || {};
            var current = select.val();
            select.empty();

            var hasOptions = false;
            $.each(activities, function(id, name) {
                hasOptions = true;
                select.append($('<option></option>').val(id).text(name));
            });

            if (current && Object.prototype.hasOwnProperty.call(activities, current)) {
                select.val(current);
            }

            if (!warning.length) {
                warning = $('<div></div>', {
                    id: 'local_automatic_badges_activity_warning',
                    'class': 'alert alert-warning'
                }).text(noActivitiesText);
                warning.hide();
                container.append(warning);
            }

            if (!hasOptions) {
                warning.show();
                select.prop('disabled', true);
            } else {
                warning.hide();
                select.prop('disabled', false);
            }
        }

        function buildPreviewText() {
            var criterion = $('#id_criterion_type').val();
            var criterionLabel = $('#id_criterion_type option:selected').text();
            var enabled = $('#id_enabled').is(':checked');
            var isGlobal = $('#id_is_global_rule').is(':checked');
            var activityType = $('#id_activity_type option:selected').text();
            var activityName = $('#id_activityid option:selected').text();
            var badgeName = $('#id_badgeid option:selected').text();
            var gradeMin = $('#id_grade_min').val();
            var gradeOperator = $('#id_grade_operator option:selected').text();
            var gradeOperatorRaw = $('#id_grade_operator').val();
            var forumPosts = $('#id_forum_post_count').val();
            var enableBonus = $('#id_enable_bonus').is(':checked');
            var bonusPoints = $('#id_bonus_points').val();
            var dryRun = $('#id_dry_run').is(':checked');
            var reqSubmitted = $('#id_require_submitted').is(':checked');
            var reqGraded = $('#id_require_graded').is(':checked');
            var notifyMessage = $('#id_notify_message').val();

            var parts = [];

            // Estado
            if (dryRun) {
                parts.push('<span class=\"badge badge-warning\"><i class=\"fa fa-flask\"></i> MODO PRUEBA</span>');
            } else if (!enabled) {
                parts.push('<span class=\"badge badge-secondary\"><i class=\"fa fa-pause\"></i> Deshabilitada</span>');
            } else {
                parts.push('<span class=\"badge badge-success\"><i class=\"fa fa-check-circle\"></i> Activa</span>');
            }

            // Criterio
            parts.push('<i class=\"fa fa-filter\"></i> <strong>Criterio:</strong> ' + criterionLabel);

            // Alcance
            if (isGlobal) {
                parts.push('<i class=\"fa fa-globe\"></i> <strong>Alcance:</strong> Todas las actividades de tipo ' + activityType);
            } else {
                if (activityName) {
                    parts.push('<i class=\"fa fa-link\"></i> <strong>Actividad:</strong> ' + activityName);
                } else {
                    parts.push('<i class=\"fa fa-link\"></i> <strong>Actividad:</strong> <em style=\"color:#dc3545\">Sin seleccionar</em>');
                }
            }

            // CondiciÃ³n
            if (criterion === 'grade') {
                parts.push('<i class=\"fa fa-chart-line\"></i> <strong>CondiciÃ³n:</strong> CalificaciÃ³n ' + (gradeOperator || '‰¥') + ' <strong style=\"color:#0f6cbf\">' + (gradeMin || '0') + '%</strong>');
            } else if (criterion === 'forum') {
                parts.push('<i class=\"fa fa-comments\"></i> <strong>CondiciÃ³n:</strong> MÃ­nimo <strong style=\"color:#0f6cbf\">' + (forumPosts || '0') + '</strong> respuesta(s) en el foro');
            } else if (criterion === 'submission') {
                var conds = [];
                if (reqSubmitted) conds.push('entrega realizada');
                if (reqGraded) conds.push('calificación publicada');
                if (conds.length > 0) {
                    parts.push('<i class=\"fa fa-tasks\"></i> <strong>CondiciÃ³n:</strong> ' + conds.join(' y '));
                }
            }

            // Insignia
            if (badgeName) {
                parts.push('<i class=\"fa fa-trophy\"></i> <strong>Insignia:</strong> ' + badgeName);
            } else {
                parts.push('<i class=\"fa fa-trophy\"></i> <strong>Insignia:</strong> <em style=\"color:#dc3545\">Sin seleccionar</em>');
            }

            // Puntos extra
            if (enableBonus && bonusPoints && parseFloat(bonusPoints) > 0) {
                parts.push('<i class=\"fa fa-gift\"></i> <strong>BonificaciÃ³n:</strong> +' + bonusPoints + ' punto(s)');
            } else {
                parts.push('\u003ci class=\"fa fa-gift\"\u003e\u003c/i\u003e \u003cstrong\u003ePuntos extra:\u003c/strong\u003e No');
            }

            // NotificaciÃ³n
            if (notifyMessage && notifyMessage.trim() !== '') {
                var preview = notifyMessage.substring(0, 80);
                if (notifyMessage.length > 80) preview += '...';
                parts.push('<i class=\"fa fa-envelope\"></i> <strong>NotificaciÃ³n:</strong> \"' + preview + '\"');
            }

            // Advertencia modo prueba
            if (dryRun) {
                parts.push('<div style=\"margin-top:10px;padding:8px;background:#fff3cd;border-left:3px solid #ffc107;border-radius:3px;font-size:12px;color:#856404\"><i class=\"fa fa-exclamation-triangle\"></i> Esta regla no otorgarÃ¡ insignias realmente</div>');
            }

            $('#local_automatic_badges_rule_preview_text').html(parts.join('<br>'));
        }

        // Update preview on changes
        $(document).on('change', '#id_criterion_type, #id_is_global_rule, #id_activity_type, #id_activityid, #id_badgeid, #id_enable_bonus, #id_dry_run, #id_require_submitted, #id_require_graded, #id_grade_operator, #id_enabled', buildPreviewText);
        $(document).on('keyup', '#id_grade_min, #id_forum_post_count, #id_bonus_points, #id_notify_message', buildPreviewText);

        function updateActivities() {
            var criterion = $('#id_criterion_type').val();
            if (!criterion) {
                return;
            }
            setOptions(criterion);
            buildPreviewText();
        }

        $(document).on('change', '#id_criterion_type', updateActivities);
        updateActivities();
        buildPreviewText();
    });
});
");
    }

    // === Helpers de actividades ===

    /**
     * Obtiene las actividades del curso elegibles para reglas de insignias.
     *
     * @param int $courseid
     * @param string|null $criterion
     * @return array<int,string>
     */
    protected function get_eligible_activities(int $courseid, ?string $criterion = null): array {
        $modinfo = get_fast_modinfo($courseid);
        $activities = [];
        $criterion = $criterion ?? '';
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible) {
                continue;
            }

            if (!$this->is_activity_eligible($cm, $criterion)) {
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
     * @param string $criterion
     * @return bool
     */
    protected function is_activity_eligible(\cm_info $cm, string $criterion = ''): bool {
        switch ($criterion) {
            case 'forum':
                return $cm->modname === 'forum';
            case 'submission':
                return in_array($cm->modname, ['assign', 'workshop'], true);
            case 'grade':
                return plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE);
        }

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
        $courseid = isset($data['courseid']) ? (int)$data['courseid'] : 0;
        $criterion = $data['criterion_type'] ?? 'grade';
        $this->eligibleactivities = $this->get_eligible_activities($courseid, $criterion);
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

