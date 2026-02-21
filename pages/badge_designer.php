<?php
// local/automatic_badges/pages/badge_designer.php

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

echo $OUTPUT->header();
echo $OUTPUT->heading('Diseñador de Insignias');

// 1. CARGA DE LIBRERÍAS EXTERNAS (Directa, más confiable)
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
echo '<script>var _backup_define = window.define; window.define = undefined;</script>';
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>';
echo '<script>window.define = _backup_define;</script>';

// 2. HTML DEL EDITOR
echo '
<div class="badge-designer-container d-flex flex-wrap border rounded p-3 bg-white shadow-sm">
    <!-- Panel de Controles -->
    <div class="col-md-4 border-right p-3 bg-light">
        <h5 class="mb-3 text-primary"><i class="fa fa-paint-brush"></i> Personalización</h5>
        
        <div class="form-group">
            <label class="font-weight-bold">Nombre</label>
            <input type="text" id="badge_text" class="form-control" value="Campeón">
        </div>

        <div class="form-group">
            <label class="font-weight-bold">Forma</label>
            <div class="btn-group w-100 mb-2">
                <button type="button" class="btn btn-outline-secondary shape-btn" data-shape="circle"><i class="fa fa-circle"></i></button>
                <button type="button" class="btn btn-outline-secondary shape-btn" data-shape="square"><i class="fa fa-square"></i></button>
                <button type="button" class="btn btn-outline-secondary shape-btn" data-shape="hexagon"><i class="fa fa-certificate"></i></button>
                <button type="button" class="btn btn-outline-secondary shape-btn" data-shape="shield"><i class="fa fa-shield-alt"></i></button>
            </div>
            <input type="color" id="badge_color_bg" class="form-control mb-2" value="#0f6cbf" title="Fondo">
            <input type="color" id="badge_color_border" class="form-control" value="#FFD700" title="Borde">
        </div>

        <div class="form-group">
            <label class="font-weight-bold">Icono</label>
            <select id="badge_icon" class="custom-select" style="font-family: \'Font Awesome 6 Free\', \'FontAwesome\', sans-serif; font-weight: 900;">
                <option value="&#xf091;">Trofeo</option>
                <option value="&#xf005;">Estrella</option>
                <option value="&#xf0a3;">Certificado</option>
                <option value="&#xf19d;">Birrete</option>
                <option value="&#xf0e7;">Rayo</option>
                <option value="&#xf00c;">Check</option>
                <option value="&#xf0eb;">Bombilla</option>
            </select>
            <input type="color" id="badge_color_icon" class="form-control mt-2" value="#FFFFFF" title="Color Icono/Texto">
        </div>
        
        <hr>
        <button id="btn_save_badge" class="btn btn-primary btn-lg btn-block shadow">
            <i class="fa fa-save"></i> Guardar Insignia
        </button>
        <a href="course_settings.php?id='.$courseid.'&tab=badges" class="btn btn-outline-secondary btn-block">Cancelar</a>
    </div>

    <!-- Lienzo -->
    <div class="col-md-8 d-flex flex-column align-items-center justify-content-center p-5 bg-light">
        <div class="canvas-wrapper bg-white shadow-sm border">
            <canvas id="c" width="400" height="400"></canvas>
        </div>
        <div class="mt-2 text-muted small"><i class="fa fa-info-circle"></i> Puedes mover y redimensionar los elementos.</div>
    </div>
</div>
';

$js_code = <<<EOF
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

    function createShape(type) {
        const color = $("#badge_color_bg").val();
        const stroke = $("#badge_color_border").val();

        if (bgShape) canvas.remove(bgShape);

        let shape;
        const commonProps = {
            fill: color,
            stroke: stroke,
            strokeWidth: 8,
            originX: "center",
            originY: "center",
            left: centerX,
            top: centerY,
            selectable: false
        };

        if (type === "circle") {
            shape = new fabric.Circle(Object.assign({ radius: baseSize }, commonProps));
        } else if (type === "square") {
            shape = new fabric.Rect(Object.assign({ width: baseSize*2, height: baseSize*2, rx: 30, ry: 30 }, commonProps));
        } else if (type === "hexagon") {
            // Un hexágono (rotado para que quede plano arriba/abajo)
            let points = [];
            for (let i = 0; i < 6; i++) {
                points.push({
                    x: baseSize * Math.cos(i * Math.PI / 3),
                    y: baseSize * Math.sin(i * Math.PI / 3)
                });
            }
            shape = new fabric.Polygon(points, Object.assign({}, commonProps));
        } else if (type === "shield") {
            // Un polígono con forma de escudo
            let points = [
                {x: -baseSize * 0.9, y: -baseSize * 0.9}, // Arriba izquierda
                {x: baseSize * 0.9, y: -baseSize * 0.9},  // Arriba derecha
                {x: baseSize * 0.9, y: baseSize * 0.2},   // Medio derecha
                {x: 0, y: baseSize * 1.1},                // Punta centro-abajo
                {x: -baseSize * 0.9, y: baseSize * 0.2}   // Medio izquierda
            ];
            shape = new fabric.Polygon(points, Object.assign({}, commonProps));
        } else {
            shape = new fabric.Circle(Object.assign({ radius: baseSize }, commonProps));
        }
        
        shape.set("shadow", new fabric.Shadow({ color: "rgba(0,0,0,0.3)", blur: 15, offsetX: 5, offsetY: 5 }));

        bgShape = shape;
        canvas.add(shape);
        shape.sendToBack();
    }

    function createIcon() {
        if (iconObj) canvas.remove(iconObj);
        
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
            selectable: true
        });
        canvas.add(iconObj);
    }

    function createText() {
        if (textObj) canvas.remove(textObj);
        
        textObj = new fabric.Text($("#badge_text").val(), {
            fontFamily: "Arial",
            fontSize: 28,
            fontWeight: "bold",
            fill: $("#badge_color_icon").val(),
            left: centerX,
            top: centerY + 100,
            originX: "center",
            originY: "center",
            textAlign: "center"
        });
        canvas.add(textObj);
    }

    $(".shape-btn").click(function() {
        createShape($(this).data("shape"));
        canvas.renderAll();
    });

    $("#badge_text").on("input", function() {
        if (textObj) textObj.set("text", $(this).val());
        else createText();
        canvas.renderAll();
    });

    $("#badge_icon").change(function() {
        createIcon();
        canvas.renderAll();
    });

    $("#badge_color_bg, #badge_color_border").on("input", function() {
        if (bgShape) {
            bgShape.set("fill", $("#badge_color_bg").val());
            bgShape.set("stroke", $("#badge_color_border").val());
            canvas.renderAll();
        }
    });

    $("#badge_color_icon").on("input", function() {
        if (iconObj) iconObj.set("fill", $(this).val());
        if (textObj) textObj.set("fill", $(this).val());
        canvas.renderAll();
    });

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
                        window.location.href = M.cfg.wwwroot + "/local/automatic_badges/course_settings.php?id=" + {$courseid} + "&tab=badges";
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

    setTimeout(function() {
        try {
            createShape("circle");
            createIcon();
            createText();
            canvas.renderAll();
        } catch(e) {
            console.error("Initialization error:", e);
        }
    }, 500);
});
EOF;

$PAGE->requires->js_amd_inline("require(['jquery'], function($) {\n" . $js_code . "\n});");

echo $OUTPUT->footer();

