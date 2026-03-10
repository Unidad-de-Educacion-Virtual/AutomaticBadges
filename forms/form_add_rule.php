<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class local_automatic_badges_add_rule_form extends moodleform {
    /** @var array<int,string> Eligible activities cache */
    protected $eligibleactivities = [];

    public function definition() {
        global $CFG, $PAGE;

        require_once($CFG->dirroot . '/lib/badgeslib.php');

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $ruleid   = $this->_customdata['ruleid'] ?? 0;

        // --- Hidden fields ---
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'ruleid', $ruleid);
        $mform->setType('ruleid', PARAM_INT);

        // =====================================================================
        // SECTION 1: Criterio de evaluación
        // =====================================================================
        $mform->addElement('header', 'criteriahdr',
            get_string('criteriontype', 'local_automatic_badges'));
        $mform->setExpanded('criteriahdr', true);

        $options = [
            'grade'       => get_string('criterion_grade', 'local_automatic_badges'),
            'forum_grade' => get_string('criterion_forum_grade', 'local_automatic_badges'),
            'forum'       => get_string('criterion_forum', 'local_automatic_badges'),
            'submission'  => get_string('criterion_submission', 'local_automatic_badges'),
            'section'     => get_string('criterion_section', 'local_automatic_badges'),
        ];
        $criteriondefault = $this->_customdata['criterion_type'] ?? 'grade';
        $criterion = optional_param('criterion_type', $criteriondefault, PARAM_ALPHANUMEXT);
        if (!array_key_exists($criterion, $options)) {
            $criterion = 'grade';
        }

        $mform->addElement('select', 'criterion_type',
            get_string('criteriontype', 'local_automatic_badges'), $options);
        $mform->addHelpButton('criterion_type', 'criteriontype', 'local_automatic_badges');
        $mform->setDefault('criterion_type', $criterion);
        $mform->addRule('criterion_type', null, 'required', null, 'client');

        // --- Calificación ---
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
        // Shown for both 'grade' and 'forum_grade' — hidden by JS below.

        $mform->addElement('text', 'grade_min',
            get_string('grademin', 'local_automatic_badges'));
        $mform->addHelpButton('grade_min', 'grademin', 'local_automatic_badges');
        $mform->setType('grade_min', PARAM_FLOAT);
        $mform->setDefault('grade_min', 60);
        // Shown for both 'grade' and 'forum_grade' — hidden by JS below.

        $mform->addElement('text', 'grade_max',
            get_string('grademax', 'local_automatic_badges'));
        $mform->addHelpButton('grade_max', 'grademax', 'local_automatic_badges');
        $mform->setType('grade_max', PARAM_FLOAT);
        $mform->setDefault('grade_max', 100);
        // grade_max is hidden unless operator = 'range'; JS handles both grade and forum_grade.

        // --- Foro ---
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
        $mform->hideIf('forum_count_type', 'criterion_type', 'neq', 'forum');

        $mform->addElement('text', 'forum_post_count',
            get_string('forumpostcount', 'local_automatic_badges'));
        $mform->addHelpButton('forum_post_count', 'forumpostcount', 'local_automatic_badges');
        $mform->setType('forum_post_count', PARAM_INT);
        $mform->setDefault('forum_post_count', 5);
        $mform->addRule('forum_post_count', null, 'numeric', null, 'client');
        $mform->hideIf('forum_post_count', 'criterion_type', 'neq', 'forum');

        // --- Entrega ---
        $mform->addElement('advcheckbox', 'require_submitted',
            get_string('requiresubmitted', 'local_automatic_badges'));
        $mform->setType('require_submitted', PARAM_INT);
        $mform->setDefault('require_submitted', 1);
        $mform->hideIf('require_submitted', 'criterion_type', 'neq', 'submission');

        $mform->addElement('advcheckbox', 'require_graded',
            get_string('requiregraded', 'local_automatic_badges'));
        $mform->setType('require_graded', PARAM_INT);
        $mform->setDefault('require_graded', 0);
        $mform->hideIf('require_graded', 'criterion_type', 'neq', 'submission');

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
        $mform->hideIf('submission_type', 'criterion_type', 'neq', 'submission');

        $mform->addElement('text', 'early_hours',
            get_string('earlyhours', 'local_automatic_badges'));
        $mform->addHelpButton('early_hours', 'earlyhours', 'local_automatic_badges');
        $mform->setType('early_hours', PARAM_INT);
        $mform->setDefault('early_hours', 24);
        $mform->hideIf('early_hours', 'criterion_type', 'neq', 'submission');
        $mform->hideIf('early_hours', 'submission_type', 'neq', 'early');

        // --- Sección (acumulativo) ---
        $mform->addElement('text', 'section_min_grade',
            get_string('section_min_grade', 'local_automatic_badges'));
        $mform->addHelpButton('section_min_grade', 'section_min_grade', 'local_automatic_badges');
        $mform->setType('section_min_grade', PARAM_FLOAT);
        $mform->setDefault('section_min_grade', 60);
        $mform->hideIf('section_min_grade', 'criterion_type', 'neq', 'section');

        // =====================================================================
        // SECTION 2: Actividad vinculada
        // =====================================================================
        $mform->addElement('header', 'activityhdr',
            get_string('activitylinked', 'local_automatic_badges'));
        $mform->setExpanded('activityhdr', true);

        $criteriaactivities = [
            'grade'       => \local_automatic_badges\helper::get_eligible_activities($courseid, 'grade'),
            'forum_grade' => \local_automatic_badges\helper::get_eligible_activities($courseid, 'forum_grade'),
            'forum'       => \local_automatic_badges\helper::get_eligible_activities($courseid, 'forum'),
            'submission'  => \local_automatic_badges\helper::get_eligible_activities($courseid, 'submission'),
            'section'     => \local_automatic_badges\helper::get_course_sections($courseid),
        ];
        $this->eligibleactivities = $criteriaactivities[$criterion] ?? [];

        // Hidden input that actually gets submitted
        $mform->addElement('hidden', 'activityid', 0, ['id' => 'id_custom_activityid_field']);
        $mform->setType('activityid', PARAM_INT);

        $noeligiblestr     = get_string('noeligibleactivities', 'local_automatic_badges');
        $searchplaceholder = get_string('search', 'core');
        $chooseplaceholder = get_string('activitylinked', 'local_automatic_badges');

        $widget_html = '
        <div id="local_automatic_badges_activity_container" style="position: relative; width: 100%;">
            <!-- Select2-style dropdown -->
            <div class="ab-select" id="ab_activity_select">
                <button type="button"
                        class="ab-select__trigger custom-select form-control"
                        style="text-align: left; padding-right: 2rem; min-width: 350px;"
                        id="ab_activity_trigger"
                        aria-haspopup="listbox"
                        aria-expanded="false">
                    <span id="ab_activity_label" class="ab-select__label ab-select__label--placeholder" style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' .
                        s($chooseplaceholder) . '...</span>
                </button>
                <div class="ab-select__dropdown" id="ab_activity_dropdown" role="listbox" aria-label="' . s($chooseplaceholder) . '">
                    <div class="ab-select__search-wrap">
                        <i class="fa fa-search ab-select__search-icon" aria-hidden="true"></i>
                        <input type="text"
                               id="ab_activity_search"
                               class="ab-select__search form-control form-control-sm border-0"
                               style="box-shadow: none; border-radius: 0;"
                               placeholder="' . s($searchplaceholder) . '..."
                               autocomplete="off"
                               aria-label="' . s($searchplaceholder) . '" />
                    </div>
                    <div class="ab-select__options" id="ab_activity_options"></div>
                </div>
            </div>
            <div id="local_automatic_badges_activity_warning" class="alert alert-warning mt-2" style="display:none;">' .
            $noeligiblestr . '</div>
        </div>
        ';

        $mform->addElement('static', 'activity_picker_dummy', get_string('activitylinked', 'local_automatic_badges'), $widget_html);


        // =====================================================================
        // SECTION 3: Insignia a otorgar
        // =====================================================================
        $mform->addElement('header', 'badgehdr',
            get_string('selectbadge', 'local_automatic_badges'));
        $mform->setExpanded('badgehdr', true);

        $badges = badges_get_badges(BADGE_TYPE_COURSE, $courseid);
        $badgeoptions = [];
        foreach ($badges as $badge) {
            $badgeoptions[$badge->id] = $badge->name;
        }

        if (empty($badgeoptions)) {
            $createbadgeurl = new moodle_url('/badges/edit.php', ['action' => 'new', 'courseid' => $courseid]);
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

        // =====================================================================
        // SECTION 4: Opciones avanzadas
        // =====================================================================
        $mform->addElement('header', 'optionshdr',
            get_string('advancedoptions', 'local_automatic_badges'));

        $mform->addElement('advcheckbox', 'enabled',
            get_string('ruleenabledlabel', 'local_automatic_badges'));
        $mform->addHelpButton('enabled', 'ruleenabledlabel', 'local_automatic_badges');
        $mform->setType('enabled', PARAM_INT);
        $mform->setDefault('enabled', 1);

        $mform->addElement('advcheckbox', 'enable_bonus',
            get_string('enablebonus', 'local_automatic_badges'));
        $mform->addHelpButton('enable_bonus', 'enablebonus', 'local_automatic_badges');
        $mform->addElement('text', 'bonus_points',
            get_string('bonusvalue', 'local_automatic_badges'));
        $mform->addHelpButton('bonus_points', 'bonusvalue', 'local_automatic_badges');
        $mform->setType('bonus_points', PARAM_FLOAT);
        $mform->setDefault('bonus_points', 0);
        $mform->hideIf('bonus_points', 'enable_bonus', 'notchecked');

        $mform->addElement('textarea', 'notify_message',
            get_string('notifymessage', 'local_automatic_badges'),
            'wrap="virtual" rows="3" cols="50"');
        $mform->addHelpButton('notify_message', 'notifymessage', 'local_automatic_badges');
        $mform->setType('notify_message', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'dry_run',
            get_string('dryrun', 'local_automatic_badges'));
        $mform->setType('dry_run', PARAM_INT);
        $mform->setDefault('dry_run', 0);

        // =====================================================================
        // SECTION 5: Vista previa de la regla
        // =====================================================================
        $mform->addElement('header', 'rulepreviewhdr',
            get_string('rulepreview', 'local_automatic_badges'));

        $mform->addElement('html', '
        <div id="local_automatic_badges_rule_preview" class="alert alert-info" style="border-left: 4px solid #0f6cbf;">
            <div id="local_automatic_badges_rule_preview_text" style="background: rgba(255,255,255,0.6); padding: 12px; border-radius: 4px; margin-top: 10px;"></div>
        </div>
        ');

        // =====================================================================
        // Botones de acción
        // =====================================================================
        $mform->addElement('submit', 'testrule',
            get_string('testrule', 'local_automatic_badges'));

        $this->add_action_buttons(true,
            get_string('saverule', 'local_automatic_badges'));

        // =====================================================================
        // JavaScript: actividades dinámicas + preview
        // =====================================================================
        $activityjson = json_encode($criteriaactivities, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $noactivities = json_encode(get_string('noeligibleactivities', 'local_automatic_badges'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $PAGE->requires->js_init_code(<<<JS
require(['jquery'], function($) {
    $(function() {
        var activityMap     = {$activityjson};
        var currentActivities = {};
        var selectedId   = 0;
        var selectedName = '';

        var hiddenInput  = $('#id_custom_activityid_field');
        if (!hiddenInput.length) {
            hiddenInput = $('input[name="activityid"]');
        }
        var trigger      = $('#ab_activity_trigger');
        var label        = $('#ab_activity_label');
        var dropdown     = $('#ab_activity_dropdown');
        var searchInput  = $('#ab_activity_search');
        var optionsEl    = $('#ab_activity_options');
        var warning      = $('#local_automatic_badges_activity_warning');
        var selectWrap   = $('#ab_activity_select');

        /* ---- Open / close the dropdown ---- */
        function openDropdown() {
            dropdown.addClass('ab-select__dropdown--open');
            trigger.attr('aria-expanded', 'true');
            selectWrap.addClass('ab-select--open');
            searchInput.val('').focus();
            renderOptions('');
        }

        function closeDropdown() {
            dropdown.removeClass('ab-select__dropdown--open');
            trigger.attr('aria-expanded', 'false');
            selectWrap.removeClass('ab-select--open');
        }

        trigger.on('click', function(e) {
            e.stopPropagation();
            if (dropdown.hasClass('ab-select__dropdown--open')) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });

        // Close on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#ab_activity_select').length) {
                closeDropdown();
            }
        });

        // Close on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') { closeDropdown(); }
        });

        /* ---- Render the options list, filtering by query ---- */
        function renderOptions(filter) {
            filter = (filter || '').toLowerCase().trim();
            optionsEl.empty();

            var count = 0;
            $.each(currentActivities, function(id, name) {
                if (filter && name.toLowerCase().indexOf(filter) === -1) { return; }
                count++;
                var isSelected = (String(id) === String(selectedId));
                var escapedName = $('<div>').text(name).html();
                var opt = $(
                    '<div class="ab-select__option' + (isSelected ? ' ab-select__option--selected' : '') + '"' +
                    ' role="option" aria-selected="' + (isSelected ? 'true' : 'false') + '" data-id="' + id + '">' +
                    (isSelected ? '<i class="fa fa-check ab-select__option-check" aria-hidden="true"></i>' :
                                  '<i class="fa fa-fw" aria-hidden="true"></i>') +
                    '<span>' + escapedName + '</span>' +
                    '</div>'
                );
                optionsEl.append(opt);
            });

            if (count === 0) {
                optionsEl.html('<div class="ab-select__no-results"><i class="fa fa-search"></i> Sin resultados</div>');
            }
        }

        /* ---- Update the trigger label ---- */
        function updateTriggerLabel() {
            if (selectedId && selectedName) {
                label.text(selectedName).removeClass('ab-select__label--placeholder');
            } else {
                label.text('{$chooseplaceholder}...').addClass('ab-select__label--placeholder');
            }
        }

        /* ---- Select an activity ---- */
        function selectActivity(id, name) {
            selectedId   = id;
            selectedName = name;
            hiddenInput.val(id).trigger('change');
            updateTriggerLabel();
            closeDropdown();
            buildPreviewText();
        }

        /* ---- Click on an option ---- */
        optionsEl.on('click', '.ab-select__option', function() {
            var id   = $(this).data('id');
            var name = $(this).find('span').text();
            selectActivity(id, name);
        });

        /* ---- Live search filter ---- */
        searchInput.on('input keyup', function() {
            renderOptions($(this).val());
        });

        /* ---- Populate for the given criterion ---- */
        function setOptions(criterion) {
            currentActivities = activityMap[criterion] || {};
            var keepId = String(selectedId);
            if (!currentActivities.hasOwnProperty(keepId)) {
                selectedId   = 0;
                selectedName = '';
                hiddenInput.val(0);
            } else {
                selectedName = currentActivities[keepId];
            }
            updateTriggerLabel();

            var hasOptions = Object.keys(currentActivities).length > 0;
            if (!hasOptions) {
                warning.show();
                selectWrap.hide();
            } else {
                warning.hide();
                selectWrap.show();
            }
        }

        // ---- Show/hide grade fields based on criterion (grade OR forum_grade) ----
        // Moodle's hideIf doesn't support OR, so we use JS.
        function updateGradeFieldsVisibility() {
            var criterion = $('#id_criterion_type').val();
            var isGradeCriterion = (criterion === 'grade' || criterion === 'forum_grade');
            var isRange = ($('#id_grade_operator').val() === 'range');
            var gradeFields = [
                $('#id_grade_operator').closest('.form-group, .fitem'),
                $('#id_grade_min').closest('.form-group, .fitem')
            ];
            var gradeMaxField = $('#id_grade_max').closest('.form-group, .fitem');
            $.each(gradeFields, function(i, el) {
                if (isGradeCriterion) { el.show(); } else { el.hide(); }
            });
            if (isGradeCriterion && isRange) { gradeMaxField.show(); } else { gradeMaxField.hide(); }
        }

        // ---- Forum count label ----
        var forumCountLabels = {
            'all':     'Publicaciones necesarias (temas o respuestas)',
            'replies': 'Respuestas necesarias',
            'topics':  'Temas necesarios'
        };

        function updateForumCountLabel() {
            var countType = $('#id_forum_count_type').val() || 'all';
            var label2 = forumCountLabels[countType] || forumCountLabels['all'];
            $('#id_forum_post_count').closest('.form-group, .fitem').find('label').first().text(label2);
        }

        // ---- Rule preview ----
        function buildPreviewText() {
            var criterion      = $('#id_criterion_type').val();
            var criterionLabel = $('#id_criterion_type option:selected').text();
            var enabled        = $('#id_enabled').is(':checked');
            var activityVal    = hiddenInput.val();
            var badgeName      = $('#id_badgeid option:selected').text();
            var gradeMin       = $('#id_grade_min').val();
            var gradeOperatorRaw = $('#id_grade_operator').val();
            var countType      = $('#id_forum_count_type').val();
            var forumPosts     = $('#id_forum_post_count').val() || '5';
            var enableBonus    = $('#id_enable_bonus').is(':checked');
            var bonusPoints    = $('#id_bonus_points').val();
            var dryRun         = $('#id_dry_run').is(':checked');
            var reqSubmitted   = $('#id_require_submitted').is(':checked');
            var reqGraded      = $('#id_require_graded').is(':checked');
            var submissionType = $('#id_submission_type').val() || 'any';
            var earlyHours     = $('#id_early_hours').val() || '24';
            var gradeMax       = $('#id_grade_max').val();
            var notifyMessage  = $('#id_notify_message').val();

            var parts = [];

            if (dryRun) {
                parts.push('<span class="badge badge-warning"><i class="fa fa-flask"></i> MODO PRUEBA</span>');
            } else if (!enabled) {
                parts.push('<span class="badge badge-secondary"><i class="fa fa-pause"></i> Deshabilitada</span>');
            } else {
                parts.push('<span class="badge badge-success"><i class="fa fa-check-circle"></i> Activa</span>');
            }

            parts.push('<div class="mt-2">');
            parts.push('<i class="fa fa-filter text-muted"></i> <strong>Criterio:</strong> ' + criterionLabel);

            if (activityVal && activityVal !== '0' && selectedName) {
                parts.push('<br><i class="fa fa-link text-muted"></i> <strong>Actividad:</strong> ' + $('<div>').text(selectedName).html());
            } else {
                parts.push('<br><i class="fa fa-link text-muted"></i> <strong>Actividad:</strong> <em class="text-danger">Sin seleccionar</em>');
            }
            parts.push('</div>');

            var conditionHtml = '';
            if (criterion === 'grade') {
                var op  = gradeOperatorRaw || '>=';
                var min = gradeMin || '0';
                if (op === 'range' && gradeMax) {
                    conditionHtml = 'Calificación entre <strong class="text-primary">' + min + '%</strong> y <strong class="text-primary">' + gradeMax + '%</strong>';
                } else if (op === 'range') {
                    conditionHtml = 'Calificación entre <strong class="text-primary">' + min + '%</strong> y <strong class="text-primary">Sin límite superior</strong>';
                } else {
                    conditionHtml = 'Calificación ' + op + ' <strong class="text-primary">' + min + '%</strong>';
                }
            } else if (criterion === 'forum_grade') {
                var op  = gradeOperatorRaw || '>=';
                var min = gradeMin || '0';
                if (op === 'range' && gradeMax) {
                    conditionHtml = 'Nota en foro entre <strong class="text-primary">' + min + '%</strong> y <strong class="text-primary">' + gradeMax + '%</strong>';
                } else if (op === 'range') {
                    conditionHtml = 'Nota en foro entre <strong class="text-primary">' + min + '%</strong> y <strong class="text-primary">Sin límite superior</strong>';
                } else {
                    conditionHtml = 'Nota en foro ' + op + ' <strong class="text-primary">' + min + '%</strong>';
                }
            } else if (criterion === 'forum') {
                var typeLabel = countType === 'replies' ? 'respuesta(s)' : (countType === 'topics' ? 'tema(s) nuevo(s)' : 'publicación(es)');
                conditionHtml = 'Mínimo <strong class="text-primary">' + (forumPosts || '5') + '</strong> ' + typeLabel + ' en el foro';
            } else if (criterion === 'submission') {
                var conds = [];
                if (reqSubmitted) conds.push('entrega realizada');
                if (reqGraded)    conds.push('calificación publicada');
                if (submissionType === 'ontime') {
                    conds.push('<strong class="text-success">a tiempo</strong>');
                } else if (submissionType === 'early') {
                    conds.push('<strong class="text-success">' + earlyHours + 'h antes del plazo</strong>');
                }
                conditionHtml = conds.length > 0 ? conds.join(' y ') : '<em class="text-warning">Sin requisitos extra</em>';
            }

            if (conditionHtml) {
                parts.push('<div class="mt-1"><i class="fa fa-tasks text-muted"></i> <strong>Condición:</strong> ' + conditionHtml + '</div>');
            }

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

            if (notifyMessage && notifyMessage.trim() !== '') {
                var preview = notifyMessage.substring(0, 80);
                if (notifyMessage.length > 80) preview += '...';
                var safePreview = $('<div>').text(preview).html();
                parts.push('<div class="mt-2 text-muted small"><i class="fa fa-envelope"></i> <em>' + safePreview + '</em></div>');
            }

            if (dryRun) {
                parts.push('<div class="alert alert-warning mt-2 mb-0 py-1"><small><i class="fa fa-exclamation-triangle"></i> Esta regla no otorgará insignias realmente.</small></div>');
            }

            $('#local_automatic_badges_rule_preview_text').html(parts.join(''));
        }

        // ---- Wire up remaining form change events ----
        var inputs = [
            '#id_criterion_type', '#id_enabled', '#id_badgeid',
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

        $(document).on('change', '#id_criterion_type', function() {
            setOptions($(this).val());
            updateGradeFieldsVisibility();
            updateForumCountLabel();
            buildPreviewText();
        });

        $(document).on('change', '#id_grade_operator', function() {
            updateGradeFieldsVisibility();
            buildPreviewText();
        });

        // ---- Pre-select existing value when editing ----
        var preselectedId   = parseInt(hiddenInput.val(), 10) || 0;
        var preselectedName = '';
        var actualCriterion = $('#id_criterion_type').val() || 'grade';
        
        if (preselectedId) {
            var criterionActs = activityMap[actualCriterion] || {};
            if (criterionActs.hasOwnProperty(preselectedId)) {
                preselectedName = criterionActs[preselectedId];
            }
        }
        selectedId   = preselectedId;
        selectedName = preselectedName;

        setOptions(actualCriterion);
        updateGradeFieldsVisibility();
        updateForumCountLabel();
        buildPreviewText();
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

        if (empty($activityid)) {
            $errors['activityid'] = get_string('required');
        } else if (empty($this->eligibleactivities)) {
            $errors['activityid'] = get_string('noeligibleactivities', 'local_automatic_badges');
        } else if (!array_key_exists($activityid, $this->eligibleactivities)) {
            $errors['activityid'] = get_string('activitynoteligible', 'local_automatic_badges');
        }

        if ($criterion === 'forum') {
            $requiredposts = isset($data['forum_post_count']) ? (int)$data['forum_post_count'] : 0;
            if ($requiredposts <= 0) {
                $errors['forum_post_count'] = get_string('forumpostcounterror', 'local_automatic_badges');
            }
        }

        if ($criterion === 'grade' || $criterion === 'forum_grade') {
            $grademin = isset($data['grade_min']) ? (float)$data['grade_min'] : 0.0;
            if ($grademin < 0 || $grademin > 100) {
                $errors['grade_min'] = get_string('grademin_invalid', 'local_automatic_badges');
            }

            if (isset($data['grade_operator']) && $data['grade_operator'] === 'range') {
                $grademax = isset($data['grade_max']) ? (float)$data['grade_max'] : 100.0;
                if ($grademax < 0 || $grademax > 100) {
                    $errors['grade_max'] = get_string('grademax_invalid', 'local_automatic_badges');
                } else if ($grademax < $grademin) {
                    $errors['grade_max'] = get_string('grademax_lower', 'local_automatic_badges');
                }
            }
        }

        return $errors;
    }
}
