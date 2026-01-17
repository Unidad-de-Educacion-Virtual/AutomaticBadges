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

    return true;
}
