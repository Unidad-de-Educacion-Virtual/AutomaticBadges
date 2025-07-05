<?php
// /local/automaticbadges/lib.php

defined('MOODLE_INTERNAL') || die();

/**
 * Este hook se dispara cuando Moodle construye la navegación de un curso.
 * El primer parámetro es un navigation_node (no global_navigation).
 *
 * @param navigation_node      $parentnode  Nodo raíz de “Course Navigation” para este curso.
 * @param stdClass             $course      Objeto del curso (id, fullname, etc.).
 * @param context_course       $context     Contexto del curso.
 */
function local_automatic_badges_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    // 1) Verificamos capacidad (por ejemplo, que el usuario pueda editar el curso).
    if (!has_capability('moodle/course:update', $context)) {
        return;
    }

    // 2) Construimos la URL a la página de “Configuración de Insignias” en este curso.
    $urlconfig = new moodle_url('/local/automaticbadges/course_settings.php', ['id' => $course->id]);

    // 3) Creamos un nodo padre “Insignias Automáticas” (primero definimos el icono y el texto).
    $icon = new pix_icon('i/certificate', ''); // ícono genérico de certificado
    $title = get_string('coursenode_title', 'local_automatic_badges');

    $node = navigation_node::create(
        $title,
        $urlconfig,
        navigation_node::TYPE_CUSTOM,
        null,
        'automaticbadges',    // identificador único dentro de este nivel
        $icon
    );

    // 4) Lo añadimos a $parentnode (que es el “Course Navigation” de este curso)
    $parentnode->add_node($node);

    // 5) Si queremos subenlaces (bajo el mismo nodo padre), los agregamos al $node:
    // a) “Historial de Insignias”
    $urlhistory = new moodle_url('/local/automaticbadges/course_history.php', ['id' => $course->id]);
    $subnode2 = navigation_node::create(
        get_string('coursenode_subhistory', 'local_automatic_badges'),
        $urlhistory,
        navigation_node::TYPE_CUSTOM,
        null,
        'automaticbadges_history',
        new pix_icon('i/report', '')
    );
    $node->add_node($subnode2);

    $urlcriteria = new moodle_url('/local/automaticbadges/course_criteria.php', ['id' => $course->id]);
$subnode3 = navigation_node::create(
    get_string('coursenode_subcriteria', 'local_automatic_badges'),
    $urlcriteria,
    navigation_node::TYPE_CUSTOM,
    null,
    'automaticbadges_criteria',
    new pix_icon('i/settings', '')
);
$node->add_node($subnode3);


}
