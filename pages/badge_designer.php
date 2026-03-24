<?php
// This file is part of local_automatic_badges - https://moodle.org/.
//
// local_automatic_badges is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// local_automatic_badges is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with local_automatic_badges.  If not, see <https://www.gnu.org/licenses/>.

/**
 * This file is part of local_automatic_badges
 *
 * local_automatic_badges is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * local_automatic_badges is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with local_automatic_badges.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Local/automatic_badges/pages/badge_designer.php.

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/badgeslib.php');

$courseid = required_param('id', PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);
require_capability('moodle/badges:createbadge', $context);

$PAGE->set_url(new moodle_url('/local/automatic_badges/pages/badge_designer.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Diseñador de Insignias');
$PAGE->set_heading(format_string($COURSE->fullname));
$PAGE->set_pagelayout('course');

// 1. CARGA DE LIBRERÍAS EXTERNAS (Localmente).
$PAGE->requires->css(new moodle_url('/local/automatic_badges/css/fontawesome.min.css'));

echo $OUTPUT->header();
echo $OUTPUT->heading('Diseñador de Insignias');

echo '<script>var _backup_define = window.define; window.define = undefined;</script>';
echo '<script src="' . new moodle_url('/local/automatic_badges/js/fabric.min.js') . '"></script>';
echo '<script src="' . new moodle_url('/local/automatic_badges/js/Sortable.min.js') . '"></script>';
echo '<script>window.define = _backup_define;</script>';

// 2. HTML DEL EDITOR.
// Phpcs:disable moodle.Files.LineLength.
echo '<style>
.badge-designer-container { min-height: 600px; }
.badge-section { border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; margin-bottom: 12px; background: #fff; }
.badge-section-header {
    font-weight: 600; font-size: 0.9rem; color: #495057; cursor: pointer;
    display: flex; align-items: center; justify-content: space-between;
    user-select: none; margin-bottom: 8px;
}
.badge-section-header .fa-chevron-down { transition: transform 0.2s; font-size: 0.7rem; }
.badge-section.collapsed .badge-section-body { display: none; }
.badge-section.collapsed .fa-chevron-down { transform: rotate(-90deg); }
.shape-btn, .icon-btn, .deco-btn { min-width: 42px; min-height: 42px; padding: 6px; }
.icon-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px; }
.icon-grid .icon-btn { width: 100%; }
.deco-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
.canvas-wrapper {
    border-radius: 12px; overflow: hidden;
    background: repeating-conic-gradient(#f0f0f0 0% 25%, #fff 0% 50%) 50% / 20px 20px;
}

.layer-item { border-radius: 6px !important; transition: background 0.15s; font-size: 0.85rem; }
.layer-item:hover { background: #e9ecef !important; }
.layer-item .layer-actions { opacity: 0; transition: opacity 0.15s; }
.layer-item:hover .layer-actions { opacity: 1; }

.control-slider { width: 100%; }
.color-pair { display: flex; gap: 8px; }
.color-pair > div { flex: 1; }
.font-select { font-size: 0.85rem; }
</style>

<div class="badge-designer-container d-flex flex-wrap border rounded p-3 bg-white shadow-sm">
    <!-- Panel de Controles -->
    <div class="col-md-4 p-3" style="max-height: 85vh; overflow-y: auto;">
        <h5 class="mb-3 text-primary"><i class="fa fa-paint-brush"></i> Personalización</h5>

        <!-- Nombre -->
        <div class="badge-section">
            <div class="badge-section-header">
                <span><i class="fa fa-font mr-1"></i> Nombre de la Insignia</span>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="badge-section-body">
                <input type="text" id="badge_text" class="form-control" value="Campeón" placeholder="Nombre de la insignia">
            </div>
        </div>

        <!-- Forma Principal -->
        <div class="badge-section">
            <div class="badge-section-header">
                <span><i class="fa fa-shapes mr-1"></i> Forma Principal</span>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="badge-section-body">
                <div class="d-flex flex-wrap mb-2" style="gap:4px;">
                    <button type="button" class="btn btn-primary shape-btn" data-shape="circle" title="Círculo">
                        <i class="fa fa-circle fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary shape-btn" data-shape="square" title="Cuadrado">
                        <i class="fa fa-square fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary shape-btn" data-shape="hexagon" title="Hexágono">
                        <i class="fa fa-cube fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary shape-btn" data-shape="shield" title="Escudo">
                        <i class="fa fa-shield-alt fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary shape-btn" data-shape="star" title="Estrella">
                        <i class="fa fa-star fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary shape-btn" data-shape="diamond" title="Diamante">
                        <i class="fa fa-gem fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary shape-btn" data-shape="oval" title="Óvalo">
                        <i class="fa fa-egg fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary shape-btn" data-shape="pentagon" title="Pentágono">
                        <span style="font-size:1.3em;line-height:1">⬠</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary shape-btn" data-shape="heart" title="Corazón">
                        <i class="fa fa-heart fa-lg"></i>
                    </button>
                </div>

                <div class="color-pair mt-2">
                    <div>
                        <label class="small text-muted mb-1">Color Fondo</label>
                        <input type="color" id="badge_color_bg" class="form-control p-1" style="height: 38px;" value="#0f6cbf">
                    </div>
                    <div>
                        <label class="small text-muted mb-1">Color Borde</label>
                        <input type="color" id="badge_color_border" class="form-control p-1" style="height: 38px;" value="#FFD700">
                    </div>
                </div>

                <div class="mt-2">
                    <label class="small text-muted mb-1">Grosor del Borde: <span id="border_width_val">8</span>px</label>
                    <input type="range" id="badge_border_width" class="control-slider" min="0" max="20" value="8">
                </div>

                <div class="mt-2">
                    <label class="small text-muted mb-1">Opacidad: <span id="opacity_val">100</span>%</label>
                    <input type="range" id="badge_opacity" class="control-slider" min="10" max="100" value="100">
                </div>
            </div>
        </div>

        <!-- Ícono Central -->
        <div class="badge-section">
            <div class="badge-section-header">
                <span><i class="fa fa-icons mr-1"></i> Ícono Central</span>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="badge-section-body">
                <div class="icon-grid mb-2">
                    <button type="button" class="btn btn-primary icon-btn" data-icon="&#xf091;" title="Trofeo">
                        <i class="fa fa-trophy fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf005;" title="Estrella">
                        <i class="fa fa-star fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf0a3;" title="Certificado">
                        <i class="fa fa-certificate fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf19d;" title="Birrete">
                        <i class="fa fa-graduation-cap fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf0e7;" title="Rayo">
                        <i class="fa fa-bolt fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf00c;" title="Check">
                        <i class="fa fa-check fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf0eb;" title="Bombilla">
                        <i class="fa fa-lightbulb fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf521;" title="Corona">
                        <i class="fa fa-crown fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf06d;" title="Fuego">
                        <i class="fa fa-fire fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf5a2;" title="Medalla">
                        <i class="fa fa-medal fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf02d;" title="Libro">
                        <i class="fa fa-book fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf001;" title="Música">
                        <i class="fa fa-music fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf013;" title="Engranaje">
                        <i class="fa fa-cog fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf164;" title="Pulgar Arriba">
                        <i class="fa fa-thumbs-up fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf135;" title="Cohete">
                        <i class="fa fa-rocket fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf3a5;" title="Diamante">
                        <i class="fa fa-gem fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf024;" title="Bandera">
                        <i class="fa fa-flag fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf084;" title="Llave">
                        <i class="fa fa-key fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="&#xf0f3;" title="Campana">
                        <i class="fa fa-bell fa-lg"></i>
                    </button>
                </div>
                <input type="hidden" id="badge_icon" value="&#xf091;">

                <div class="color-pair mt-2">
                    <div>
                        <label class="small text-muted mb-1">Color Ícono</label>
                        <input type="color" id="badge_color_icon" class="form-control p-1" style="height: 38px;" value="#FFFFFF">
                    </div>
                    <div>
                        <label class="small text-muted mb-1">Color Texto</label>
                        <input type="color" id="badge_color_text" class="form-control p-1" style="height: 38px;" value="#FFFFFF">
                    </div>
                </div>
            </div>
        </div>

        <!-- Imagen Personalizada -->
        <div class="badge-section">
            <div class="badge-section-header">
                <span><i class="fa fa-image mr-1"></i> Imagen Personalizada</span>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="badge-section-body">
                <p class="small text-muted mb-2">Sube tu propia imagen (PNG, JPG, SVG) para añadirla al lienzo.</p>
                <label class="btn btn-outline-secondary btn-block" style="cursor:pointer;">
                    <i class="fa fa-upload mr-1"></i> Seleccionar imagen...
                    <input type="file" id="badge_img_upload" accept="image/*" style="display:none;">
                </label>
                <div id="badge_img_feedback" class="small text-muted mt-1" style="display:none;"></div>
            </div>
        </div>

        <!-- Fuente del Texto -->
        <div class="badge-section">
            <div class="badge-section-header">
                <span><i class="fa fa-text-height mr-1"></i> Fuente del Texto</span>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="badge-section-body">
                <select id="badge_font" class="form-control font-select">
                    <option value="Arial" style="font-family:Arial">Arial</option>
                    <option value="Georgia" style="font-family:Georgia">Georgia</option>
                    <option value="Times New Roman" style="font-family:Times New Roman">Times New Roman</option>
                    <option value="Courier New" style="font-family:Courier New">Courier New</option>
                    <option value="Verdana" style="font-family:Verdana">Verdana</option>
                    <option value="Impact" style="font-family:Impact">Impact</option>
                    <option value="Comic Sans MS" style="font-family:Comic Sans MS">Comic Sans MS</option>
                    <option value="Trebuchet MS" style="font-family:Trebuchet MS">Trebuchet MS</option>
                </select>
            </div>
        </div>

        <!-- Decoraciones -->
        <div class="badge-section">
            <div class="badge-section-header">
                <span><i class="fa fa-magic mr-1"></i> Decoraciones</span>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="badge-section-body">
                <div class="deco-grid mb-2">
                    <button type="button" class="btn btn-outline-primary deco-btn" data-deco="ribbon" title="Listón Inferior">
                        <i class="fa fa-bookmark fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary deco-btn" data-deco="sunburst" title="Resplandor">
                        <i class="fa fa-sun fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary deco-btn" data-deco="wings" title="Alas">
                        <i class="fa fa-dove fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary deco-btn" data-deco="crown" title="Corona Superior">
                        <i class="fa fa-crown fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary deco-btn" data-deco="laurels" title="Laureles">
                        <i class="fa fa-leaf fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary deco-btn" data-deco="stars_around" title="Estrellas">
                        <i class="fa fa-star-of-life fa-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary deco-btn" data-deco="dots" title="Puntos Decorativos">
                        <i class="fa fa-circle-notch fa-lg"></i>
                    </button>
                </div>
                <label class="small text-muted mb-1">Color de Decoraciones</label>
                <input type="color" id="badge_color_deco" class="form-control p-1" style="height: 38px;" value="#D4AF37">
            </div>
        </div>

        <hr>
        <button id="btn_save_badge" class="btn btn-primary btn-lg btn-block shadow">
            <i class="fa fa-save"></i> Guardar Insignia
        </button>
        <a href="course_settings.php?id=' . $courseid . '&tab=badges" class="btn btn-outline-secondary btn-block">Cancelar</a>
    </div>

    <!-- Lienzo -->
    <div class="col-md-5 d-flex flex-column align-items-center justify-content-center p-4">
        <div class="canvas-wrapper shadow border" style="position: relative; border-radius: 12px;">
            <canvas id="c" width="400" height="400"></canvas>
            <div class="position-absolute" style="top:10px; right:10px; display:flex; gap:4px;">
                <button type="button" class="btn btn-sm btn-light border shadow-sm" id="btn_center_all" title="Centrar todo">
                    <i class="fa fa-crosshairs"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light border shadow-sm"
                        id="btn_delete_selected" title="Eliminar seleccionado">
                    <i class="fa fa-trash text-danger"></i>
                </button>
            </div>
        </div>
        <div class="mt-3 text-muted text-center small">
            <i class="fa fa-info-circle text-primary"></i> Clic en elementos para moverlos. Usa las esquinas para redimensionar.
        </div>
    </div>

    <!-- Capas -->
    <div class="col-md-3 p-3" style="max-height: 85vh; overflow-y: auto;">
        <h5 class="mb-3 text-primary"><i class="fa fa-layer-group"></i> Capas</h5>
        <ul id="layers_list" class="list-group layer-list" style="cursor: grab;">
            <!-- Elementos generados dinámicamente -->
        </ul>
        <div class="mt-3 small text-muted"><i class="fa fa-arrows-alt-v"></i> Arrastra para ordenar las capas.</div>
    </div>
</div>';
// Phpcs:enable moodle.Files.LineLength.

// Phpcs:disable moodle.Files.LineLength.
$jscode = <<<EOF
$(function() {
    if (typeof fabric === "undefined") {
        console.error("Fabric.js no cargado");
        alert("Error: Fabric.js no se cargó correctamente.");
        return;
    }

    const canvas = new fabric.Canvas("c", {
        backgroundColor: "transparent",
        preserveObjectStacking: true
    });

    const centerX = 200;
    const centerY = 200;
    const baseSize = 140;
    let bgShape, iconObj, textObj;
    let activeDecos = {};

    // LAYERS PANEL.
    function updateLayersPanel() {
        const list = $("#layers_list");
        list.empty();

        let objects = canvas.getObjects();
        for (let i = objects.length - 1; i >= 0; i--) {
            let obj = objects[i];
            if (obj.name) {
                let isVisible = obj.visible !== false;
                let eyeIcon = isVisible ? 'fa-eye' : 'fa-eye-slash';
                let eyeColor = isVisible ? 'text-primary' : 'text-muted';
                let li = `<li class="list-group-item layer-item d-flex justify-content-between align-items-center mb-1 bg-white"
                            style="padding: 6px 10px;" data-index="\${i}">
                    <span><i class="fa fa-grip-lines text-muted mr-2" style="cursor:grab"></i> \${obj.name}</span>
                    <span class="layer-actions">
                        <button type="button" class="btn btn-sm btn-link p-0 mx-1 layer-vis-btn \${eyeColor}"
                                data-index="\${i}" title="Visibilidad"><i class="fa \${eyeIcon}"></i></button>
                        <button type="button" class="btn btn-sm btn-link p-0 mx-1 text-danger layer-del-btn"
                                data-index="\${i}" title="Eliminar"><i class="fa fa-trash-alt"></i></button>
                    </span>
                </li>`;
                list.append(li);
            }
        }

        // Visibility toggle.
        $(".layer-vis-btn").off("click").on("click", function(e) {
            e.stopPropagation();
            let idx = parseInt($(this).data("index"));
            let obj = canvas.getObjects()[idx];
            if (obj) {
                obj.visible = !obj.visible;
                canvas.renderAll();
                updateLayersPanel();
            }
        });

        // Delete layer.
        $(".layer-del-btn").off("click").on("click", function(e) {
            e.stopPropagation();
            let idx = parseInt($(this).data("index"));
            let obj = canvas.getObjects()[idx];
            if (obj) {
                removeTrackedObject(obj);
                canvas.remove(obj);
                canvas.renderAll();
                updateLayersPanel();
            }
        });
    }

    function removeTrackedObject(obj) {
        if (obj === bgShape) bgShape = null;
        if (obj === iconObj) iconObj = null;
        if (obj === textObj) textObj = null;
        // Check decorations.
        for (let key in activeDecos) {
            if (activeDecos[key] === obj) {
                // Reset decoration button.
                $(".deco-btn[data-deco='" + key + "']").removeClass("btn-primary").addClass("btn-outline-primary");
                delete activeDecos[key];
            }
        }
    }

    // Sortable for layers.
    if (document.getElementById('layers_list')) {
        new Sortable(document.getElementById('layers_list'), {
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function () {
                let items = $('#layers_list li');
                let orderedIndices = [];
                items.each(function() { orderedIndices.push(parseInt($(this).attr('data-index'))); });

                let originalObjects = [...canvas.getObjects()];
                for (let i = orderedIndices.length - 1; i >= 0; i--) {
                    let oldIndex = orderedIndices[i];
                    let obj = originalObjects[oldIndex];
                    if (obj) { obj.bringToFront(); }
                }
                canvas.renderAll();
                updateLayersPanel();
            }
        });
    }

    // SHAPES.
    function createShape(type) {
        const color = $("#badge_color_bg").val();
        const stroke = $("#badge_color_border").val();
        const strokeW = parseInt($("#badge_border_width").val()) || 8;
        const opacityVal = parseInt($("#badge_opacity").val()) / 100;

        let oldIndex = -1;
        if (bgShape) {
            oldIndex = canvas.getObjects().indexOf(bgShape);
            canvas.remove(bgShape);
        }

        let shape;
        const commonProps = {
            fill: color,
            stroke: stroke,
            strokeWidth: strokeW,
            originX: "center",
            originY: "center",
            left: centerX,
            top: centerY,
            selectable: true,
            opacity: opacityVal
        };

        if (type === "circle") {
            shape = new fabric.Circle(Object.assign({ radius: baseSize }, commonProps));
        } else if (type === "square") {
            shape = new fabric.Rect(Object.assign({ width: baseSize*2, height: baseSize*2, rx: 30, ry: 30 }, commonProps));
        } else if (type === "hexagon") {
            let points = [];
            for (let i = 0; i < 6; i++) {
                points.push({
                    x: baseSize * Math.cos(i * Math.PI / 3),
                    y: baseSize * Math.sin(i * Math.PI / 3)
                });
            }
            shape = new fabric.Polygon(points, Object.assign({}, commonProps));
        } else if (type === "shield") {
            let points = [
                {x: -baseSize * 0.9, y: -baseSize * 0.9},
                {x: baseSize * 0.9, y: -baseSize * 0.9},
                {x: baseSize * 0.9, y: baseSize * 0.2},
                {x: 0, y: baseSize * 1.1},
                {x: -baseSize * 0.9, y: baseSize * 0.2}
            ];
            shape = new fabric.Polygon(points, Object.assign({}, commonProps));
        } else if (type === "star") {
            let points = [];
            let outerR = baseSize;
            let innerR = baseSize * 0.45;
            for (let i = 0; i < 10; i++) {
                let r = i % 2 === 0 ? outerR : innerR;
                let angle = (i * 36 - 90) * Math.PI / 180;
                points.push({ x: r * Math.cos(angle), y: r * Math.sin(angle) });
            }
            shape = new fabric.Polygon(points, Object.assign({}, commonProps));
        } else if (type === "diamond") {
            let points = [
                {x: 0, y: -baseSize * 1.1},
                {x: baseSize * 0.75, y: 0},
                {x: 0, y: baseSize * 1.1},
                {x: -baseSize * 0.75, y: 0}
            ];
            shape = new fabric.Polygon(points, Object.assign({}, commonProps));
        } else if (type === "oval") {
            shape = new fabric.Ellipse(Object.assign({
                rx: baseSize,
                ry: baseSize * 0.72
            }, commonProps));
        } else if (type === "pentagon") {
            let points = [];
            for (let i = 0; i < 5; i++) {
                let angle = (i * 72 - 90) * Math.PI / 180;
                points.push({ x: baseSize * Math.cos(angle), y: baseSize * Math.sin(angle) });
            }
            shape = new fabric.Polygon(points, Object.assign({}, commonProps));
        } else if (type === "heart") {
            let heartPath = 'M 0 -60 C -30 -110, -110 -110, -110 -50 C -110 10, -50 60, 0 110 ' +
                            'C 50 60, 110 10, 110 -50 C 110 -110, 30 -110, 0 -60 Z';
            shape = new fabric.Path(heartPath, Object.assign({
                scaleX: baseSize / 110,
                scaleY: baseSize / 110
            }, commonProps));
        } else {
            shape = new fabric.Circle(Object.assign({ radius: baseSize }, commonProps));
        }

        shape.set("shadow", new fabric.Shadow({ color: "rgba(0,0,0,0.3)", blur: 15, offsetX: 5, offsetY: 5 }));
        shape.set({ name: 'Forma Principal' });

        bgShape = shape;
        canvas.add(shape);

        if (oldIndex !== -1) {
            shape.moveTo(oldIndex);
        } else {
            shape.sendToBack();
        }
        updateLayersPanel();
    }

    // Keep track of current shape type.
    let currentShapeType = 'circle';

    // DECORATIONS.
    function toggleDecoration(type) {
        if (activeDecos[type]) {
            canvas.remove(activeDecos[type]);
            delete activeDecos[type];
        } else {
            const decoColor = $("#badge_color_deco").val();
            const strokeColor = $("#badge_color_border").val();
            let newDecoObj = null;

            if (type === "ribbon") {
                let ribbonPath = 'M -70 50 L -120 180 L -55 145 L 0 165 L 55 145 L 120 180 L 70 50 Z';
                newDecoObj = new fabric.Path(ribbonPath, {
                    fill: decoColor, stroke: strokeColor, strokeWidth: 6,
                    left: centerX, top: centerY + 90, originX: "center", originY: "center", selectable: true,
                    shadow: new fabric.Shadow({ color: "rgba(0,0,0,0.3)", blur: 15, offsetX: 5, offsetY: 5 })
                });
            } else if (type === "sunburst") {
                let rays = [];
                for (let i = 0; i < 24; i++) {
                    let angle = (i * 15) * Math.PI / 180;
                    let length = (i % 2 === 0) ? 180 : 130;
                    rays.push({ x: Math.cos(angle) * length, y: Math.sin(angle) * length });
                }
                newDecoObj = new fabric.Polygon(rays, {
                    fill: decoColor, stroke: strokeColor, strokeWidth: 6,
                    left: centerX, top: centerY, originX: "center", originY: "center", selectable: true,
                    shadow: new fabric.Shadow({ color: "rgba(0,0,0,0.3)", blur: 15, offsetX: 5, offsetY: 5 })
                });
            } else if (type === "wings") {
                let leftWing = new fabric.Polygon([
                    {x: 0, y: 0}, {x: -80, y: -60}, {x: -60, y: -20}, {x: -100, y: 10},
                    {x: -50, y: 30}, {x: -80, y: 60}, {x: 0, y: 40}
                ], {
                    fill: decoColor, stroke: strokeColor, strokeWidth: 5,
                    originX: 'right', originY: 'center', left: -80, top: 0
                });
                let rightWing = new fabric.Polygon([
                    {x: 0, y: 0}, {x: 80, y: -60}, {x: 60, y: -20}, {x: 100, y: 10},
                    {x: 50, y: 30}, {x: 80, y: 60}, {x: 0, y: 40}
                ], {
                    fill: decoColor, stroke: strokeColor, strokeWidth: 5,
                    originX: 'left', originY: 'center', left: 80, top: 0
                });

                newDecoObj = new fabric.Group([leftWing, rightWing], {
                    left: centerX, top: centerY + 10, originX: 'center', originY: 'center', selectable: true,
                    shadow: new fabric.Shadow({ color: "rgba(0,0,0,0.3)", blur: 15, offsetX: 5, offsetY: 5 })
                });
            } else if (type === "crown") {
                let crownPath = 'M -60 0 L -70 -50 L -35 -25 L 0 -60 L 35 -25 L 70 -50 L 60 0 Z';
                newDecoObj = new fabric.Path(crownPath, {
                    fill: decoColor, stroke: strokeColor, strokeWidth: 4,
                    left: centerX, top: centerY - 130, originX: "center", originY: "center", selectable: true,
                    shadow: new fabric.Shadow({ color: "rgba(0,0,0,0.2)", blur: 10, offsetX: 3, offsetY: 3 })
                });
            } else if (type === "laurels") {
                // Laurel izquierdo.
                let leftLaurel = [];
                for (let i = 0; i < 6; i++) {
                    let angle = (i * 25 + 200) * Math.PI / 180;
                    let r = 130 + i * 3;
                    leftLaurel.push(new fabric.Ellipse({
                        rx: 12, ry: 22, fill: decoColor, stroke: strokeColor, strokeWidth: 2,
                        left: Math.cos(angle) * r, top: Math.sin(angle) * r,
                        angle: (i * 25 + 200 + 90), originX: 'center', originY: 'center'
                    }));
                }
                // Laurel derecho (mirror).
                let rightLaurel = [];
                for (let i = 0; i < 6; i++) {
                    let angle = (-i * 25 - 20) * Math.PI / 180;
                    let r = 130 + i * 3;
                    rightLaurel.push(new fabric.Ellipse({
                        rx: 12, ry: 22, fill: decoColor, stroke: strokeColor, strokeWidth: 2,
                        left: Math.cos(angle) * r, top: Math.sin(angle) * r,
                        angle: (-i * 25 - 20 - 90), originX: 'center', originY: 'center'
                    }));
                }
                newDecoObj = new fabric.Group([...leftLaurel, ...rightLaurel], {
                    left: centerX, top: centerY + 20, originX: 'center', originY: 'center', selectable: true,
                    shadow: new fabric.Shadow({ color: "rgba(0,0,0,0.2)", blur: 8, offsetX: 2, offsetY: 2 })
                });
            } else if (type === "stars_around") {
                let starGroup = [];
                for (let i = 0; i < 8; i++) {
                    let angle = (i * 45) * Math.PI / 180;
                    let r = 160;
                    // Mini estrella de 5 puntas.
                    let pts = [];
                    for (let j = 0; j < 10; j++) {
                        let sr = j % 2 === 0 ? 14 : 6;
                        let a = (j * 36 - 90) * Math.PI / 180;
                        pts.push({ x: sr * Math.cos(a), y: sr * Math.sin(a) });
                    }
                    starGroup.push(new fabric.Polygon(pts, {
                        fill: decoColor, stroke: strokeColor, strokeWidth: 1,
                        left: Math.cos(angle) * r, top: Math.sin(angle) * r,
                        originX: 'center', originY: 'center'
                    }));
                }
                newDecoObj = new fabric.Group(starGroup, {
                    left: centerX, top: centerY, originX: 'center', originY: 'center', selectable: true,
                    shadow: new fabric.Shadow({ color: "rgba(0,0,0,0.2)", blur: 8, offsetX: 2, offsetY: 2 })
                });
            } else if (type === "dots") {
                let dotGroup = [];
                for (let i = 0; i < 16; i++) {
                    let angle = (i * 22.5) * Math.PI / 180;
                    let r = 155;
                    let dotSize = (i % 2 === 0) ? 5 : 3;
                    dotGroup.push(new fabric.Circle({
                        radius: dotSize, fill: decoColor,
                        left: Math.cos(angle) * r, top: Math.sin(angle) * r,
                        originX: 'center', originY: 'center'
                    }));
                }
                newDecoObj = new fabric.Group(dotGroup, {
                    left: centerX, top: centerY, originX: 'center', originY: 'center', selectable: true,
                    shadow: new fabric.Shadow({ color: "rgba(0,0,0,0.15)", blur: 5, offsetX: 1, offsetY: 1 })
                });
            }

            if (newDecoObj) {
                let decoNames = {
                    'ribbon': 'Listón', 'sunburst': 'Resplandor', 'wings': 'Alas',
                    'crown': 'Corona', 'laurels': 'Laureles', 'stars_around': 'Estrellas', 'dots': 'Puntos'
                };
                newDecoObj.set({ name: decoNames[type] });
                activeDecos[type] = newDecoObj;
                canvas.add(newDecoObj);
                newDecoObj.sendToBack();
            }
        }
        updateLayersPanel();
    }

    function reColorDecorations() {
        const decoColor = $("#badge_color_deco").val();
        const strokeColor = $("#badge_color_border").val();
        Object.values(activeDecos).forEach(decoObj => {
            if (decoObj.type === 'group') {
                decoObj._objects.forEach(obj => {
                    obj.set({fill: decoColor, stroke: strokeColor});
                });
            } else {
                decoObj.set({fill: decoColor, stroke: strokeColor});
            }
        });
    }

    // ICON & TEXT.
    function createIcon() {
        let oldIndex = -1;
        if (iconObj) {
            oldIndex = canvas.getObjects().indexOf(iconObj);
            canvas.remove(iconObj);
        }

        let unicode = $("#badge_icon").val();

        iconObj = new fabric.Text(unicode, {
            fontFamily: '"Font Awesome 6 Free", FontAwesome, sans-serif',
            fontWeight: 900,
            fontSize: 100,
            fill: $("#badge_color_icon").val(),
            left: centerX,
            top: centerY - 20,
            originX: "center",
            originY: "center",
            textBaseline: "bottom",
            selectable: true,
            name: 'Ícono Central'
        });
        canvas.add(iconObj);
        if (oldIndex !== -1) iconObj.moveTo(oldIndex);
        updateLayersPanel();
    }

    function createText() {
        let oldIndex = -1;
        if (textObj) {
            oldIndex = canvas.getObjects().indexOf(textObj);
            canvas.remove(textObj);
        }

        let fontSize = parseInt($("#badge_font_size").val()) || 26;
        let fontFamily = $("#badge_font").val() || "Arial";

        textObj = new fabric.Text($("#badge_text").val(), {
            fontFamily: fontFamily,
            fontSize: fontSize,
            fontWeight: "bold",
            fill: $("#badge_color_text").val(),
            left: centerX,
            top: centerY + 115,
            originX: "center",
            originY: "center",
            textAlign: "center",
            textBaseline: "bottom",
            selectable: true,
            name: 'Texto Principal'
        });
        canvas.add(textObj);
        if (oldIndex !== -1) textObj.moveTo(oldIndex);
        updateLayersPanel();
    }

    // EVENT HANDLERS.

    // Center all.
    $("#btn_center_all").click(function() {
        if (bgShape) { bgShape.set({left: centerX, top: centerY, scaleX: 1, scaleY: 1, angle: 0}); }
        if (iconObj) { iconObj.set({left: centerX, top: centerY - 20, scaleX: 1, scaleY: 1, angle: 0}); }
        if (textObj) { textObj.set({left: centerX, top: centerY + 115, scaleX: 1, scaleY: 1, angle: 0}); }

        if (activeDecos["ribbon"]) activeDecos["ribbon"].set({left: centerX, top: centerY + 90, scaleX: 1, scaleY: 1, angle: 0});
        if (activeDecos["sunburst"]) activeDecos["sunburst"].set({left: centerX, top: centerY, scaleX: 1, scaleY: 1, angle: 0});
        if (activeDecos["wings"]) activeDecos["wings"].set({left: centerX, top: centerY + 10, scaleX: 1, scaleY: 1, angle: 0});
        if (activeDecos["crown"]) activeDecos["crown"].set({left: centerX, top: centerY - 130, scaleX: 1, scaleY: 1, angle: 0});
        if (activeDecos["laurels"]) activeDecos["laurels"].set({left: centerX, top: centerY + 20, scaleX: 1, scaleY: 1, angle: 0});
        if (activeDecos["stars_around"]) {
            activeDecos["stars_around"].set({left: centerX, top: centerY, scaleX: 1, scaleY: 1, angle: 0});
        }
        if (activeDecos["dots"]) activeDecos["dots"].set({left: centerX, top: centerY, scaleX: 1, scaleY: 1, angle: 0});

        canvas.renderAll();
    });

    // Delete selected.
    $("#btn_delete_selected").click(function() {
        let active = canvas.getActiveObject();
        if (active) {
            removeTrackedObject(active);
            canvas.remove(active);
            canvas.discardActiveObject();
            canvas.renderAll();
            updateLayersPanel();
        }
    });

    // Also allow keyboard delete.
    $(document).keydown(function(e) {
        if ((e.key === "Delete" || e.key === "Backspace") && !$(e.target).is("input, textarea, select")) {
            let active = canvas.getActiveObject();
            if (active && active !== bgShape) {
                removeTrackedObject(active);
                canvas.remove(active);
                canvas.discardActiveObject();
                canvas.renderAll();
                updateLayersPanel();
            }
        }
    });

    // Shape buttons.
    $(".shape-btn").click(function() {
        $(".shape-btn").removeClass("btn-primary").addClass("btn-outline-primary");
        $(this).removeClass("btn-outline-primary").addClass("btn-primary");
        currentShapeType = $(this).data("shape");
        createShape(currentShapeType);
        canvas.renderAll();
    });

    // Text input.
    $("#badge_text").on("input", function() {
        if (textObj) textObj.set("text", $(this).val());
        else createText();
        canvas.renderAll();
    });

    // Icon buttons.
    $(".icon-btn").click(function() {
        $(".icon-btn").removeClass("btn-primary").addClass("btn-outline-secondary");
        $(this).removeClass("btn-outline-secondary").addClass("btn-primary");
        $("#badge_icon").val($(this).data("icon"));
        createIcon();
        canvas.renderAll();
    });

    // Color changes for bg/border.
    $("#badge_color_bg, #badge_color_border").on("input", function() {
        if (bgShape) {
            bgShape.set("fill", $("#badge_color_bg").val());
            bgShape.set("stroke", $("#badge_color_border").val());
        }
        reColorDecorations();
        canvas.renderAll();
    });

    // Icon color.
    $("#badge_color_icon").on("input", function() {
        if (iconObj) iconObj.set("fill", $(this).val());
        canvas.renderAll();
    });

    // Text color.
    $("#badge_color_text").on("input", function() {
        if (textObj) textObj.set("fill", $(this).val());
        canvas.renderAll();
    });

    // Border width slider.
    $("#badge_border_width").on("input", function() {
        let w = parseInt($(this).val());
        $("#border_width_val").text(w);
        if (bgShape) bgShape.set("strokeWidth", w);
        canvas.renderAll();
    });

    // Opacity slider.
    $("#badge_opacity").on("input", function() {
        let op = parseInt($(this).val());
        $("#opacity_val").text(op);
        if (bgShape) bgShape.set("opacity", op / 100);
        canvas.renderAll();
    });

    // Font selector.
    $("#badge_font").change(function() {
        if (textObj) {
            textObj.set("fontFamily", $(this).val());
            canvas.renderAll();
        }
    });

    // Collapsible sections.
    $(".badge-section-header").click(function() {
        $(this).closest(".badge-section").toggleClass("collapsed");
    });

    // Decoration buttons.
    $(".deco-btn").click(function() {
        $(this).toggleClass("btn-outline-primary btn-primary");
        toggleDecoration($(this).data("deco"));
        canvas.renderAll();
    });

    // Decoration color.
    $("#badge_color_deco").on("input", function() {
        reColorDecorations();
        canvas.renderAll();
    });

    // Image upload.
    let imageCount = 0;
    $('#badge_img_upload').on('change', function() {
        const file = this.files[0];
        if (!file) return;
        if (!file.type.match(/^image\//)) {
            alert('Solo se permiten archivos de imagen (PNG, JPG, GIF, SVG, etc.).');
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            fabric.Image.fromURL(e.target.result, function(img) {
                const maxDim = 160;
                const scale = Math.min(maxDim / img.width, maxDim / img.height, 1);
                img.scale(scale);
                imageCount++;
                img.set({
                    left: centerX,
                    top: centerY,
                    originX: 'center',
                    originY: 'center',
                    selectable: true,
                    name: 'Imagen ' + imageCount
                });
                canvas.add(img);
                canvas.setActiveObject(img);
                canvas.renderAll();
                updateLayersPanel();
                $('#badge_img_feedback').text('Imagen "' + file.name + '" añadida al lienzo.').show();
                setTimeout(function() { $('#badge_img_feedback').hide(); }, 3000);
            });
        };
        reader.readAsDataURL(file);
        this.value = '';
    });

    // SAVE.
    $("#btn_save_badge").click(function() {
        const btn = $(this);
        const name = $("#badge_text").val();
        if (!name) { alert("Nombre requerido"); return; }

        btn.prop("disabled", true).text("Guardando...");

        try {
            canvas.discardActiveObject().renderAll();
            const dataURL = canvas.toDataURL({ format: "png", multiplier: 2 });

            $.ajax({
                url: M.cfg.wwwroot + "/local/automatic_badges/ajax/save_badge_design.php",
                type: "POST",
                data: {
                    courseid: {$courseid},
                    sesskey: M.cfg.sesskey,
                    name: name,
                    imagedata: dataURL
                },
                dataType: "json",
                success: function(r) {
                    if (r.success) {
                        window.location.href = M.cfg.wwwroot + "/badges/edit.php?id=" + r.badgeid + "&action=badge";
                    } else {
                        alert(r.message);
                        btn.prop("disabled", false).html('<i class="fa fa-save"></i> Guardar Insignia');
                    }
                },
                error: function() {
                    alert("Error de conexión al guardar.");
                    btn.prop("disabled", false).html('<i class="fa fa-save"></i> Guardar Insignia');
                }
            });
        } catch(e) {
            console.error(e);
            alert("Error procesando imagen");
            btn.prop("disabled", false).html('<i class="fa fa-save"></i> Guardar Insignia');
        }
    });

    // INIT.
    setTimeout(function() {
        try {
            createShape("circle");
            createIcon();
            createText();
            // Activar solo el listón por defecto.
            $(".deco-btn[data-deco='ribbon']").toggleClass("btn-outline-primary btn-primary");
            toggleDecoration("ribbon");
            canvas.renderAll();
        } catch(e) {
            console.error("Initialization error:", e);
        }
    }, 500);
});
EOF;
// Phpcs:enable moodle.Files.LineLength.

$PAGE->requires->js_amd_inline("require(['jquery'], function($) {\n" . $jscode . "\n});");

echo $OUTPUT->footer();
