<?php
// local/automatic_badges/db/upgrade.php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade hook for local_automatic_badges.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_automatic_badges_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025101401) {
        $table = new xmldb_table('local_automatic_badges_rules');
        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'criterion_type');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // Default existing rules to enabled.
            $DB->execute("UPDATE {local_automatic_badges_rules} SET enabled = 1");
        }

        upgrade_plugin_savepoint(true, 2025101401, 'local', 'automatic_badges');
    }

    // Upgrade para agregar campos de reglas globales
    if ($oldversion < 2025122801) {
        $table = new xmldb_table('local_automatic_badges_rules');
        
        // Agregar campo is_global_rule
        $field = new xmldb_field('is_global_rule', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Agregar campo activity_type
        $field = new xmldb_field('activity_type', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'is_global_rule');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025122801, 'local', 'automatic_badges');
    }

    // Upgrade para agregar campo de operador de comparación de calificaciones
    if ($oldversion < 2026010801) {
        $table = new xmldb_table('local_automatic_badges_rules');
        
        // Agregar campo grade_operator
        $field = new xmldb_field('grade_operator', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, '>=', 'grade_min');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026010801, 'local', 'automatic_badges');
    }

    // Upgrade para agregar campos de submission y dry_run
    if ($oldversion < 2026010802) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // require_submitted
        $field = new xmldb_field('require_submitted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'notify_message');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // require_graded
        $field = new xmldb_field('require_graded', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'require_submitted');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // dry_run
        $field = new xmldb_field('dry_run', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'require_graded');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026010802, 'local', 'automatic_badges');
    }

    // Upgrade para agregar campo forum_count_type
    if ($oldversion < 2026011301) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // forum_count_type (all, replies, topics)
        $field = new xmldb_field('forum_count_type', XMLDB_TYPE_CHAR, '20', null, null, null, 'all', 'forum_post_count');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026011301, 'local', 'automatic_badges');
    }

    // Upgrade para agregar tablas faltantes: coursecfg y criteria
    if ($oldversion < 2026011702) {
        // Tabla coursecfg
        $table = new xmldb_table('local_automatic_badges_coursecfg');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseid_idx', XMLDB_INDEX_UNIQUE, ['courseid']);
            $dbman->create_table($table);
        }

        // Tabla criteria (legacy)
        $table = new xmldb_table('local_automatic_badges_criteria');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('badgeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('grademin', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseid_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $table->add_index('badgeid_idx', XMLDB_INDEX_NOTUNIQUE, ['badgeid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026011702, 'local', 'automatic_badges');
    }

    // Upgrade para Fase 2: campos mejorados de criterios
    if ($oldversion < 2026011800) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // grade_max - Para rangos de calificación (RF01)
        $field = new xmldb_field('grade_max', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null, 'grade_min');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // submission_type - Tipo de entrega: any, ontime, early (RF04)
        $field = new xmldb_field('submission_type', XMLDB_TYPE_CHAR, '20', null, null, null, 'any', 'require_graded');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // early_hours - Horas antes del deadline para entrega "rápida" (RF04)
        $field = new xmldb_field('early_hours', XMLDB_TYPE_INTEGER, '10', null, null, null, '24', 'submission_type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026011800, 'local', 'automatic_badges');
    }

    // Upgrade para Fase 2: campos de workshop y section
    if ($oldversion < 2026011801) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // workshop_submission - Requiere envío en el taller
        $field = new xmldb_field('workshop_submission', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'early_hours');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // workshop_assessments - Número de evaluaciones de pares requeridas
        $field = new xmldb_field('workshop_assessments', XMLDB_TYPE_INTEGER, '10', null, null, null, '2', 'workshop_submission');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // section_id - ID de la sección del curso para criterios acumulativos
        $field = new xmldb_field('section_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'workshop_assessments');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // section_min_grade - Calificación promedio mínima en la sección
        $field = new xmldb_field('section_min_grade', XMLDB_TYPE_NUMBER, '10,2', null, null, null, '60', 'section_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026011801, 'local', 'automatic_badges');
    }

    return true;
}
