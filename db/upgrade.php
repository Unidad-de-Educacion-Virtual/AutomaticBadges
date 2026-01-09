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

    return true;
}
