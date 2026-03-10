<?php
// local/automatic_badges/forms/form_add_global_rule.php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating global rules that apply to multiple activities at once.
 * Reuses components from form_add_rule.php but simplified for global use.
 */
class local_automatic_badges_add_global_rule_form extends moodleform {

    public function definition() {
        global $CFG, $PAGE;

        require_once($CFG->dirroot . '/lib/badgeslib.php');

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        // --- Hidden fields ---
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // =====================================================================
        // SECTION 1: Tipo de actividad global
        // =====================================================================
        $mform->addElement('header', 'globaltypehdr',
            get_string('globalrule_section_type', 'local_automatic_badges'));
        $mform->setExpanded('globaltypehdr', true);

        // Tipo de módulo
        $modtypes = \local_automatic_badges\helper::get_global_mod_types();
        $mform->addElement('select', 'global_mod_type',
            get_string('globalmodtype', 'local_automatic_badges'), $modtypes);
        $mform->setType('global_mod_type', PARAM_ALPHANUMEXT);
        $mform->addRule('global_mod_type', null, 'required', null, 'server');

        // Tipo de criterio
        $criterionoptions = [
            'grade'      => get_string('criterion_grade', 'local_automatic_badges'),
            'forum'      => get_string('criterion_forum', 'local_automatic_badges'),
            'submission' => get_string('criterion_submission', 'local_automatic_badges'),
        ];
        $criteriondefault = $this->_customdata['criterion_type'] ?? 'grade';
        $criterion = optional_param('criterion_type', $criteriondefault, PARAM_ALPHA);
        if (!array_key_exists($criterion, $criterionoptions)) {
            $criterion = 'grade';
        }

        $mform->addElement('select', 'criterion_type',
            get_string('criteriontype', 'local_automatic_badges'), $criterionoptions);
        $mform->addHelpButton('criterion_type', 'criteriontype', 'local_automatic_badges');
        $mform->setDefault('criterion_type', $criterion);
        $mform->addRule('criterion_type', null, 'required', null, 'server');

        // =====================================================================
        // SECTION 2: Selección de actividades
        // =====================================================================
        $mform->addElement('header', 'globalactivitieshdr',
            get_string('selectactivities', 'local_automatic_badges'));
        $mform->setExpanded('globalactivitieshdr', true);

        // No hidden field needed — JS will append individual hidden inputs to the form on submit

        $mform->addElement('html', '
        <div id="local_automatic_badges_global_activities" class="local-automatic-badges-activity-selection">
            <div class="local-automatic-badges-activity-selection__header">
                <span>' . get_string('selectactivities', 'local_automatic_badges') . '</span>
                <button type="button" id="local_badges_select_all" class="btn btn-sm btn-link">' . get_string('selectall') . '</button>
            </div>
            <div id="local_automatic_badges_global_activities_list" class="local-automatic-badges-activity-selection__list">
                <div class="local-automatic-badges-activity-selection__empty">' . get_string('selecttypefirst', 'local_automatic_badges') . '</div>
            </div>
        </div>
        ');

        // =====================================================================
        // SECTION 3: Criterio de evaluación
        // =====================================================================
        $mform->addElement('header', 'criteriahdr',
            get_string('criteriontype', 'local_automatic_badges'));
        $mform->setExpanded('criteriahdr', true);

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
        $mform->hideIf('grade_operator', 'criterion_type', 'neq', 'grade');

        $mform->addElement('text', 'grade_min',
            get_string('grademin', 'local_automatic_badges'));
        $mform->addHelpButton('grade_min', 'grademin', 'local_automatic_badges');
        $mform->setType('grade_min', PARAM_FLOAT);
        $mform->setDefault('grade_min', 60);
        $mform->hideIf('grade_min', 'criterion_type', 'neq', 'grade');

        $mform->addElement('text', 'grade_max',
            get_string('grademax', 'local_automatic_badges'));
        $mform->addHelpButton('grade_max', 'grademax', 'local_automatic_badges');
        $mform->setType('grade_max', PARAM_FLOAT);
        $mform->setDefault('grade_max', 100);
        $mform->hideIf('grade_max', 'criterion_type', 'neq', 'grade');
        $mform->hideIf('grade_max', 'grade_operator', 'neq', 'range');

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
        $mform->addRule('forum_post_count', null, 'numeric', null, 'server');
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

        // =====================================================================
        // SECTION 4: Insignia plantilla
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
            $mform->addElement('html', '
            <div class="alert alert-info mb-3">
                <i class="fa fa-info-circle mr-1"></i>
                ' . get_string('globalrule_badge_hint', 'local_automatic_badges') . '
            </div>');
            $mform->addElement('select', 'badgeid',
                get_string('selectbadge', 'local_automatic_badges'), $badgeoptions);
            $mform->addHelpButton('badgeid', 'selectbadge', 'local_automatic_badges');
            $mform->addRule('badgeid', null, 'required', null, 'server');
        }

        // =====================================================================
        // SECTION 5: Opciones adicionales
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
        // Botones de acción
        // =====================================================================
        // Disable client-side change checker (all rules are server-side).
        $mform->disable_form_change_checker();

        $this->add_action_buttons(true, get_string('globalrule_submit', 'local_automatic_badges'));

        // =====================================================================
        // JavaScript: carga dinámica de actividades
        // =====================================================================
        $PAGE->requires->js_init_code(<<<JS
require(['jquery'], function($) {
    $(function() {
        var courseid = {$courseid};
        var listContainer = $('#local_automatic_badges_global_activities_list');
        var selectAllBtn = $('#local_badges_select_all');
        var submitBtn = $('#id_submitbutton');

        function getCheckedIds() {
            var ids = [];
            listContainer.find('input[type="checkbox"]:checked').each(function() {
                ids.push(parseInt($(this).val(), 10));
            });
            return ids;
        }

        function updateSubmitCount() {
            var count = getCheckedIds().length;
            var text = 'Generar ' + count + ' insignia(s)';
            submitBtn.val(text);
            if (count > 0) {
                submitBtn.removeClass('btn-secondary').addClass('btn-primary').prop('disabled', false);
            } else {
                submitBtn.addClass('btn-secondary').prop('disabled', true);
            }
        }

        function updateGlobalList() {
            var criterion = $('#id_criterion_type').val();
            var modType = $('#id_global_mod_type').val();

            if (!modType) {
                listContainer.html('<div class="local-automatic-badges-activity-selection__empty">Primero selecciona un tipo de actividad.</div>');
                updateSubmitCount();
                return;
            }

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
                        updateSubmitCount();
                        return;
                    }

                    $.each(data, function(id, name) {
                        var item = $('<div class="local-automatic-badges-activity-selection__item"></div>');
                        var checkbox = $('<input type="checkbox" value="' + id + '" id="global_act_' + id + '" checked>');
                        var label = $('<label for="global_act_' + id + '">' + name + '</label>');
                        item.append(checkbox).append(label);
                        listContainer.append(item);
                        checkbox.on('change', updateSubmitCount);
                    });

                    updateSubmitCount();
                },
                error: function() {
                    listContainer.html('<div class="alert alert-danger">Error al cargar actividades.</div>');
                }
            });
        }

        $('#id_global_mod_type').on('change', updateGlobalList);
        $('#id_criterion_type').on('change', updateGlobalList);

        selectAllBtn.on('click', function(e) {
            e.preventDefault();
            var checks = listContainer.find('input[type="checkbox"]');
            var anyUnchecked = checks.filter(':not(:checked)').length > 0;
            checks.prop('checked', anyUnchecked);
            updateSubmitCount();
            $(this).text(anyUnchecked ? 'Deseleccionar todas' : 'Seleccionar todas');
        });

        // Inject hidden inputs into the form just before submit
        // Using native capture-phase listener (fires before all jQuery handlers)
        var formEl = document.querySelector('form#mform1, form[id^="mform"]');
        if (!formEl) {
            formEl = submitBtn.closest('form')[0];
        }
        if (formEl) {
            formEl.addEventListener('submit', function() {
                // Remove any previously injected hidden inputs
                $(formEl).find('input[name="selected_act[]"]').remove();
                // Inject one hidden input per checked activity
                var ids = getCheckedIds();
                $.each(ids, function(i, id) {
                    $(formEl).append('<input type="hidden" name="selected_act[]" value="' + id + '">');
                });
            }, true); // capture = true
        }

        // Init on load
        updateGlobalList();
    });
});
JS
        );
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['global_mod_type'])) {
            $errors['global_mod_type'] = get_string('required');
        }

        if (($data['criterion_type'] ?? '') === 'forum') {
            $requiredposts = isset($data['forum_post_count']) ? (int)$data['forum_post_count'] : 0;
            if ($requiredposts <= 0) {
                $errors['forum_post_count'] = get_string('forumpostcounterror', 'local_automatic_badges');
            }
        }

        if (($data['criterion_type'] ?? '') === 'grade') {
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
