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
            'section'    => get_string('criterion_section', 'local_automatic_badges'),
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

        // --- Global Settings (Solo para nuevas reglas) ---
        if ($ruleid == 0) {
            $mform->addElement('header', 'globalsettings', get_string('globalsettings', 'local_automatic_badges'));
            
            $mform->addElement('checkbox', 'is_global_rule', get_string('isglobalrule', 'local_automatic_badges'));
            $mform->addHelpButton('is_global_rule', 'isglobalrule', 'local_automatic_badges');
            
            // Mod type
            $modtypes = \local_automatic_badges\helper::get_global_mod_types();
            $mform->addElement('select', 'global_mod_type', get_string('globalmodtype', 'local_automatic_badges'), $modtypes);
            $mform->hideIf('global_mod_type', 'is_global_rule', 'notchecked');
            
            // Selector de actividades para regla global
            $mform->addElement('html', '<div id="local_automatic_badges_global_activities" class="local-automatic-badges-activity-selection" style="display:none;">');
            $mform->addElement('html', '<div class="local-automatic-badges-activity-selection__header">');
            $mform->addElement('html', '<span>' . get_string('selectactivities', 'local_automatic_badges') . '</span>');
            $mform->addElement('html', '<button type="button" id="local_badges_select_all" class="btn btn-sm btn-link">' . get_string('selectall') . '</button>');
            $mform->addElement('html', '</div>');
            $mform->addElement('html', '<div id="local_automatic_badges_global_activities_list" class="local-automatic-badges-activity-selection__list">');
            $mform->addElement('html', '<div class="local-automatic-badges-activity-selection__empty">' . get_string('selecttypefirst', 'local_automatic_badges') . '</div>');
            $mform->addElement('html', '</div>');
            $mform->addElement('html', '</div>');
            
            $mform->addElement('html', '<hr>');
        }

        // --- Seleccion de la actividad objetivo ---
        // Selector anidado segun el criterio.
        $mform->addElement('html', '<div id="local_automatic_badges_activity_container">');
        $criteriaactivities = [
            'grade' => \local_automatic_badges\helper::get_eligible_activities($courseid, 'grade'),
            'forum' => \local_automatic_badges\helper::get_eligible_activities($courseid, 'forum'),
            'submission' => \local_automatic_badges\helper::get_eligible_activities($courseid, 'submission'),
            'section' => \local_automatic_badges\helper::get_course_sections($courseid),
        ];
        $this->eligibleactivities = $criteriaactivities[$criterion] ?? [];
        $mform->addElement('select', 'activityid',
            get_string('activitylinked', 'local_automatic_badges'), $this->eligibleactivities);
        $mform->addHelpButton('activityid', 'activitylinked', 'local_automatic_badges');
        
        // Hide activity selector if global
        if ($ruleid == 0) {
            $mform->hideIf('activityid', 'is_global_rule', 'checked');
            $mform->hideIf('local_automatic_badges_activity_container', 'is_global_rule', 'checked');
            $mform->hideIf('local_automatic_badges_activity_warning', 'is_global_rule', 'checked');
        }
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
            'range' => get_string('operator_range', 'local_automatic_badges'),
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

        // Calificación máxima - solo visible cuando operador es "range"
        $mform->addElement('text', 'grade_max',
            get_string('grademax', 'local_automatic_badges'));
        $mform->addHelpButton('grade_max', 'grademax', 'local_automatic_badges');
        $mform->setType('grade_max', PARAM_FLOAT);
        $mform->setDefault('grade_max', 100);

        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('grade_max', 'criterion_type', 'neq', 'grade');
            $mform->hideIf('grade_max', 'grade_operator', 'neq', 'range');
        } else {
            $mform->disabledIf('grade_max', 'criterion_type', 'neq', 'grade');
            $mform->disabledIf('grade_max', 'grade_operator', 'neq', 'range');
        }

        // Tipo de publicaciones a contar
        $forumcounttypes = [
            'all'     => get_string('forumcounttype_all', 'local_automatic_badges'),
            'replies' => get_string('forumcounttype_replies', 'local_automatic_badges'),
            'topics'  => get_string('forumcounttype_topics', 'local_automatic_badges'),
        ];
        $mform->addElement('select', 'forum_count_type',
            get_string('forumcounttype', 'local_automatic_badges'), $forumcounttypes);
        $mform->addHelpButton('forum_count_type', 'forumcounttype', 'local_automatic_badges');
        $mform->setType('forum_count_type', PARAM_ALPHA);
        $mform->setDefault('forum_count_type', 'all');
        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('forum_count_type', 'criterion_type', 'neq', 'forum');
        } else {
            $mform->disabledIf('forum_count_type', 'criterion_type', 'neq', 'forum');
        }

        // Campo único de conteo - la etiqueta se actualiza via JavaScript
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

        // --- Campos específicos para section (acumulativo) ---
        $mform->addElement('text', 'section_min_grade',
            get_string('section_min_grade', 'local_automatic_badges'));
        $mform->addHelpButton('section_min_grade', 'section_min_grade', 'local_automatic_badges');
        $mform->setType('section_min_grade', PARAM_FLOAT);
        $mform->setDefault('section_min_grade', 60);
        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('section_min_grade', 'criterion_type', 'neq', 'section');
        } else {
            $mform->disabledIf('section_min_grade', 'criterion_type', 'neq', 'section');
        }

        // --- Seleccion de la insignia ---
        require_once($CFG->dirroot . '/badges/lib.php');
        $badges = badges_get_badges(BADGE_TYPE_COURSE, $courseid);
        $badgeoptions = [];
        foreach ($badges as $badge) {
            $badgeoptions[$badge->id] = $badge->name;
        }

        if (empty($badgeoptions)) {
            // Mostrar alerta prominente cuando no hay insignias
            $createbadgeurl = new moodle_url('/badges/newbadge.php', ['type' => BADGE_TYPE_COURSE, 'id' => $courseid]);
            $alerthtml = '
            <div class="alert alert-warning d-flex align-items-start" role="alert" style="margin: 1rem 0; padding: 1rem 1.25rem; border-left: 4px solid #ffc107;">
                <i class="fa fa-exclamation-triangle fa-2x mr-3" style="color: #856404;"></i>
                <div>
                    <h5 class="alert-heading mb-2" style="font-weight: 600; color: #856404;">' . 
                        get_string('nobadgesavailable', 'local_automatic_badges') . '
                    </h5>
                    <p class="mb-2" style="color: #856404;">
                        ' . get_string('nobadges_createfirst', 'local_automatic_badges') . '
                    </p>
                    <a href="' . $createbadgeurl->out() . '" class="btn btn-warning btn-sm">
                        <i class="fa fa-plus mr-1"></i> ' . get_string('newbadge', 'badges') . '
                    </a>
                </div>
            </div>';
            $mform->addElement('html', $alerthtml);
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

        // Tipo de entrega (puntualidad)
        $submissiontypes = [
            'any'    => get_string('submissiontype_any', 'local_automatic_badges'),
            'ontime' => get_string('submissiontype_ontime', 'local_automatic_badges'),
            'early'  => get_string('submissiontype_early', 'local_automatic_badges'),
        ];
        $mform->addElement('select', 'submission_type',
            get_string('submissiontype', 'local_automatic_badges'), $submissiontypes);
        $mform->addHelpButton('submission_type', 'submissiontype', 'local_automatic_badges');
        $mform->setType('submission_type', PARAM_ALPHA);
        $mform->setDefault('submission_type', 'any');

        // Horas antes del deadline para entrega anticipada
        $mform->addElement('text', 'early_hours',
            get_string('earlyhours', 'local_automatic_badges'));
        $mform->addHelpButton('early_hours', 'earlyhours', 'local_automatic_badges');
        $mform->setType('early_hours', PARAM_INT);
        $mform->setDefault('early_hours', 24);

        // Solo aplican para submission
        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('require_submitted', 'criterion_type', 'neq', 'submission');
            $mform->hideIf('require_graded', 'criterion_type', 'neq', 'submission');
            $mform->hideIf('submission_type', 'criterion_type', 'neq', 'submission');
            $mform->hideIf('early_hours', 'criterion_type', 'neq', 'submission');
            $mform->hideIf('early_hours', 'submission_type', 'neq', 'early');
        } else {
            $mform->disabledIf('require_submitted', 'criterion_type', 'neq', 'submission');
            $mform->disabledIf('require_graded', 'criterion_type', 'neq', 'submission');
            $mform->disabledIf('submission_type', 'criterion_type', 'neq', 'submission');
            $mform->disabledIf('early_hours', 'criterion_type', 'neq', 'submission');
            $mform->disabledIf('early_hours', 'submission_type', 'neq', 'early');
        }

        // --- Modo prueba (dry-run) ---
        $mform->addElement('advcheckbox', 'dry_run',
            get_string('dryrun', 'local_automatic_badges'));
        $mform->setType('dry_run', PARAM_INT);
        $mform->setDefault('dry_run', 0);

        // --- Resumen/preview de la regla ---
        $mform->addElement('header', 'rulepreviewhdr',
            get_string('rulepreview', 'local_automatic_badges'));

        $mform->addElement('html', '
        <div id="local_automatic_badges_rule_preview" class="alert alert-info" style="border-left: 4px solid #0f6cbf;">
            <div id="local_automatic_badges_rule_preview_text" style="background: rgba(255,255,255,0.6); padding: 12px; border-radius: 4px; margin-top: 10px;"></div>
        </div>
        ');

        // Botón extra para probar (no guarda)
        $mform->addElement('submit', 'testrule',
            get_string('testrule', 'local_automatic_badges'));

        // --- Acciones del formulario ---
        $this->add_action_buttons(true,
            get_string('saverule', 'local_automatic_badges'));

        $activityjson = json_encode($criteriaactivities, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $noactivities = json_encode(get_string('noeligibleactivities', 'local_automatic_badges'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $PAGE->requires->js_init_code(<<<JS
require(['jquery'], function($) {
    $(function() {
        var activityMap = {$activityjson};
        var noActivitiesText = {$noactivities};
        var container = $('#local_automatic_badges_activity_container');
        var select = $('#id_activityid');
        var warning = $('#local_automatic_badges_activity_warning');

        // Actualiza las opciones de actividad según el criterio
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

        // Etiquetas dinámicas para el campo de conteo según el tipo
        var forumCountLabels = {
            'all': 'Publicaciones necesarias (temas o respuestas)',
            'replies': 'Respuestas necesarias',
            'topics': 'Temas necesarios'
        };

        // Actualiza la etiqueta del campo de conteo según el tipo seleccionado
        function updateForumCountLabel() {
            var countType = $('#id_forum_count_type').val() || 'all';
            var label = forumCountLabels[countType] || forumCountLabels['all'];
            
            // Actualizar la etiqueta del campo forum_post_count
            var labelEl = $('label[for="id_forum_post_count"]');
            if (labelEl.length) {
                labelEl.text(label);
            }
            // También intentar actualizar en themes Bootstrap
            $('#id_forum_post_count').closest('.form-group, .fitem').find('label').first().text(label);
        }
        
        // Construye el texto de previsualización
        function buildPreviewText() {
            var criterion = $('#id_criterion_type').val();
            var criterionLabel = $('#id_criterion_type option:selected').text();
            var enabled = $('#id_enabled').is(':checked');
            
            var activityVal = $('#id_activityid').val();
            var activityName = $('#id_activityid option:selected').text();
            
            var badgeName = $('#id_badgeid option:selected').text();
            
            var gradeMin = $('#id_grade_min').val();
            var gradeOperatorRaw = $('#id_grade_operator').val();
            
            var countType = $('#id_forum_count_type').val();
            var forumPosts = $('#id_forum_post_count').val() || '5';
            var enableBonus = $('#id_enable_bonus').is(':checked');
            var bonusPoints = $('#id_bonus_points').val();
            var dryRun = $('#id_dry_run').is(':checked');
            
            var reqSubmitted = $('#id_require_submitted').is(':checked');
            var reqGraded = $('#id_require_graded').is(':checked');
            var submissionType = $('#id_submission_type').val() || 'any';
            var earlyHours = $('#id_early_hours').val() || '24';
            var gradeMax = $('#id_grade_max').val();
            var notifyMessage = $('#id_notify_message').val();

            var parts = [];

            // 1. Estado
            if (dryRun) {
                parts.push('<span class="badge badge-warning"><i class="fa fa-flask"></i> MODO PRUEBA</span>');
            } else if (!enabled) {
                parts.push('<span class="badge badge-secondary"><i class="fa fa-pause"></i> Deshabilitada</span>');
            } else {
                parts.push('<span class="badge badge-success"><i class="fa fa-check-circle"></i> Activa</span>');
            }

            parts.push('<div class="mt-2">');
            
            // 2. Criterio y Actividad
            parts.push('<i class="fa fa-filter text-muted"></i> <strong>Criterio:</strong> ' + criterionLabel);
            
            if (activityVal && activityName) {
                parts.push('<br><i class="fa fa-link text-muted"></i> <strong>Actividad:</strong> ' + activityName);
            } else {
                parts.push('<br><i class="fa fa-link text-muted"></i> <strong>Actividad:</strong> <em class="text-danger">Sin seleccionar</em>');
            }
            parts.push('</div>');

            // 3. Condición específica
            var conditionHtml = '';
            if (criterion === 'grade') {
                var op = gradeOperatorRaw || '>=';
                var min = gradeMin || '0';
                
                if (op === 'range' && gradeMax) {
                    conditionHtml = 'Calificación entre <strong class="text-primary">' + min + '%</strong> y <strong class="text-primary">' + gradeMax + '%</strong>';
                } else if (op === 'range') {
                    conditionHtml = 'Calificación entre <strong class="text-primary">' + min + '%</strong> y <strong class="text-primary">100%</strong>';
                } else {
                    conditionHtml = 'Calificación ' + op + ' <strong class="text-primary">' + min + '%</strong>';
                }
            } else if (criterion === 'forum') {
                var posts = forumPosts || '5';
                var typeLabel = '';
                if (countType === 'replies') {
                    typeLabel = 'respuesta(s)';
                } else if (countType === 'topics') {
                    typeLabel = 'tema(s) nuevo(s)';
                } else {
                    typeLabel = 'publicación(es)';
                }
                conditionHtml = 'Mínimo <strong class="text-primary">' + posts + '</strong> ' + typeLabel + ' en el foro';
            } else if (criterion === 'submission') {
                var conds = [];
                if (reqSubmitted) conds.push('entrega realizada');
                if (reqGraded) conds.push('calificación publicada');
                
                // Añadir tipo de entrega
                if (submissionType === 'ontime') {
                    conds.push('<strong class="text-success">a tiempo</strong>');
                } else if (submissionType === 'early') {
                    conds.push('<strong class="text-success">' + earlyHours + 'h antes del plazo</strong>');
                }
                
                if (conds.length > 0) {
                    conditionHtml = conds.join(' y ');
                } else {
                    conditionHtml = '<em class="text-warning">Sin requisitos extra</em>';
                }
            }
            
            if (conditionHtml) {
                parts.push('<div class="mt-1"><i class="fa fa-tasks text-muted"></i> <strong>Condición:</strong> ' + conditionHtml + '</div>');
            }

            // 4. Insignia y Bonificación
            var rewardHtml = '<div class="mt-2 py-2 px-2 bg-white rounded border">';
            if (badgeName) {
                rewardHtml += '<i class="fa fa-trophy text-warning"></i> <strong>Insignia:</strong> ' + badgeName;
            } else {
                rewardHtml += '<i class="fa fa-trophy text-muted"></i> <strong>Insignia:</strong> <em class="text-danger">Sin seleccionar</em>';
            }

            if (enableBonus && bonusPoints && parseFloat(bonusPoints) > 0) {
                rewardHtml += '<br><i class="fa fa-gift text-success"></i> <strong>Bonificación:</strong> +' + bonusPoints + ' punto(s)';
            }
            
            rewardHtml += '</div>';
            parts.push(rewardHtml);

            // 5. Notificación
            if (notifyMessage && notifyMessage.trim() !== '') {
                var preview = notifyMessage.substring(0, 80);
                if (notifyMessage.length > 80) preview += '...';
                // Basic HTML escape for preview
                var safePreview = $('<div>').text(preview).html();
                parts.push('<div class="mt-2 text-muted small"><i class="fa fa-envelope"></i> <em>' + safePreview + '</em></div>');
            }

            // 6. Advertencia
            if (dryRun) {
                parts.push('<div class="alert alert-warning mt-2 mb-0 py-1"><small><i class="fa fa-exclamation-triangle"></i> Esta regla no otorgará insignias realmente.</small></div>');
            }

            $('#local_automatic_badges_rule_preview_text').html(parts.join(''));
        }

        // Event Listeners
        var inputs = [
            '#id_criterion_type', '#id_enabled', '#id_activityid', '#id_badgeid',
            '#id_grade_operator', '#id_enable_bonus', '#id_dry_run', 
            '#id_require_submitted', '#id_require_graded', '#id_forum_count_type',
            '#id_submission_type'
        ].join(', ');
        
        var textInputs = '#id_grade_min, #id_grade_max, #id_forum_post_count, #id_bonus_points, #id_notify_message, #id_early_hours';

        $(document).on('change', inputs, function() {
            updateForumCountLabel();
            buildPreviewText();
        });
        $(document).on('keyup', textInputs, buildPreviewText);

        function updateActivities() {
            var criterion = $('#id_criterion_type').val();
            if (!criterion) {
                return;
            }
            setOptions(criterion);
            updateForumCountLabel();
            buildPreviewText();
        }

        $(document).on('change', '#id_criterion_type', updateActivities);
        updateActivities();
        updateForumCountLabel();
        buildPreviewText();
    });
});
JS
);

        // JS Complementario para la selección global
        $PAGE->requires->js_init_code(<<<JS
require(['jquery'], function($) {
    $(function() {
        var courseid = {$courseid};
        var globalContainer = $('#local_automatic_badges_global_activities');
        var listContainer = $('#local_automatic_badges_global_activities_list');
        var selectAllBtn = $('#local_badges_select_all');
        var isGlobalCheck = $('#id_is_global_rule');
        var modTypeSelect = $('#id_global_mod_type');
        var submitBtn = $('#id_submitbutton');
        var originalBtnText = submitBtn.val();

        function updateGlobalList() {
            var criterion = $('#id_criterion_type').val();
            var modType = modTypeSelect.val();
            
            if (!isGlobalCheck.is(':checked')) {
                globalContainer.hide();
                submitBtn.val(originalBtnText);
                return;
            }

            globalContainer.show();
            listContainer.html('<div class="p-3 text-center"><i class="fa fa-circle-o-notch fa-spin"></i> Cargando actividades...</div>');

            $.ajax({
                url: M.cfg.wwwroot + '/local/automatic_badges/ajax/load_activities.php',
                data: {
                    courseid: courseid,
                    criterion_type: criterion,
                    modname: modType,
                    format: 'json'
                },
                dataType: 'json',
                success: function(data) {
                    listContainer.empty();
                    if (!data || Object.keys(data).length === 0) {
                        listContainer.append('<div class="local-automatic-badges-activity-selection__empty">No se encontraron actividades elegibles de este tipo.</div>');
                        updateSubmitCount(0);
                        return;
                    }

                    $.each(data, function(id, name) {
                        var item = $('<div class="local-automatic-badges-activity-selection__item"></div>');
                        var checkbox = $('<input type="checkbox" name="selected_activities[]" value="' + id + '" id="global_act_' + id + '" checked>');
                        var label = $('<label for="global_act_' + id + '">' + name + '</label>');
                        
                        item.append(checkbox).append(label);
                        listContainer.append(item);
                        
                        checkbox.on('change', function() {
                            updateSubmitCount();
                        });
                    });
                    
                    updateSubmitCount();
                }
            });
        }

        function updateSubmitCount() {
            if (!isGlobalCheck.is(':checked')) {
                submitBtn.val(originalBtnText);
                return;
            }

            var count = listContainer.find('input[type="checkbox"]:checked').length;
            var text = 'Generar ' + count + ' insignias';
            submitBtn.val(text);
            
            if (count > 0) {
                submitBtn.removeClass('btn-secondary').addClass('btn-primary').prop('disabled', false);
            } else {
                submitBtn.addClass('btn-secondary').prop('disabled', true);
            }
        }

        isGlobalCheck.on('change', updateGlobalList);
        modTypeSelect.on('change', updateGlobalList);
        $('#id_criterion_type').on('change', updateGlobalList);

        selectAllBtn.on('click', function(e) {
            e.preventDefault();
            var checks = listContainer.find('input[type="checkbox"]');
            var anyUnchecked = checks.filter(':not(:checked)').length > 0;
            checks.prop('checked', anyUnchecked);
            updateSubmitCount();
            $(this).text(anyUnchecked ? 'Deseleccionar todas' : 'Seleccionar todas');
        });

        // Init
        if (isGlobalCheck.is(':checked')) {
            updateGlobalList();
        }
    });
});
JS
);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $courseid = isset($data['courseid']) ? (int)$data['courseid'] : 0;
        $criterion = $data['criterion_type'] ?? 'grade';
        $this->eligibleactivities = \local_automatic_badges\helper::get_eligible_activities($courseid, $criterion);
        $activityid = isset($data['activityid']) ? (int)$data['activityid'] : 0;
        
        // Si es global, saltamos validación de activityid
        if (!empty($data['is_global_rule'])) {
            unset($errors['activityid']); 
            if (empty($data['global_mod_type'])) {
                $errors['global_mod_type'] = get_string('required');
            }
        } else {
            // Validación estándar
            if (empty($this->eligibleactivities)) {
                $errors['activityid'] = get_string('noeligibleactivities', 'local_automatic_badges');
            } else if (!array_key_exists($activityid, $this->eligibleactivities)) {
                $errors['activityid'] = get_string('activitynoteligible', 'local_automatic_badges');
            }
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
