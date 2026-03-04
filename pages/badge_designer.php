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

// 1. CARGA DE LIBRERÍAS EXTERNAS (Localmente)
$PAGE->requires->css(new moodle_url('/local/automatic_badges/css/fontawesome.min.css'));

echo $OUTPUT->header();
echo $OUTPUT->heading('Diseñador de Insignias');

echo '<script>var _backup_define = window.define; window.define = undefined;</script>';
echo '<script src="' . new moodle_url('/local/automatic_badges/js/fabric.min.js') . '"></script>';
echo '<script src="' . new moodle_url('/local/automatic_badges/js/Sortable.min.js') . '"></script>';
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
            <label class="font-weight-bold">Forma Principal</label>
            <div class="d-flex mb-3">
                <button type="button" class="btn btn-primary shape-btn flex-fill mx-1" data-shape="circle" title="Círculo"><i class="fa fa-circle fa-lg"></i></button>
                <button type="button" class="btn btn-outline-primary shape-btn flex-fill mx-1" data-shape="square" title="Cuadrado"><i class="fa fa-square fa-lg"></i></button>
                <button type="button" class="btn btn-outline-primary shape-btn flex-fill mx-1" data-shape="hexagon" title="Hexágono"><i class="fa fa-cube fa-lg"></i></button>
                <button type="button" class="btn btn-outline-primary shape-btn flex-fill mx-1" data-shape="shield" title="Escudo"><i class="fa fa-shield-alt fa-lg"></i></button>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <label class="small text-muted mb-1">Color Fondo</label>
                    <input type="color" id="badge_color_bg" class="form-control p-1" style="height: 40px;" value="#0f6cbf">
                </div>
                <div class="col-6">
                    <label class="small text-muted mb-1">Color Borde</label>
                    <input type="color" id="badge_color_border" class="form-control p-1" style="height: 40px;" value="#FFD700">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="font-weight-bold">Ícono Central</label>
            <div class="d-flex flex-wrap mb-2 justify-content-center bg-white border rounded p-2">
                <button type="button" class="btn btn-primary icon-btn m-1" data-icon="&#xf091;" title="Trofeo"><i class="fa fa-trophy fa-lg"></i></button>
                <button type="button" class="btn btn-outline-secondary icon-btn m-1" data-icon="&#xf005;" title="Estrella"><i class="fa fa-star fa-lg"></i></button>
                <button type="button" class="btn btn-outline-secondary icon-btn m-1" data-icon="&#xf0a3;" title="Certificado"><i class="fa fa-certificate fa-lg"></i></button>
                <button type="button" class="btn btn-outline-secondary icon-btn m-1" data-icon="&#xf19d;" title="Birrete"><i class="fa fa-graduation-cap fa-lg"></i></button>
                <button type="button" class="btn btn-outline-secondary icon-btn m-1" data-icon="&#xf0e7;" title="Rayo"><i class="fa fa-bolt fa-lg"></i></button>
                <button type="button" class="btn btn-outline-secondary icon-btn m-1" data-icon="&#xf00c;" title="Check"><i class="fa fa-check fa-lg"></i></button>
                <button type="button" class="btn btn-outline-secondary icon-btn m-1" data-icon="&#xf0eb;" title="Bombilla"><i class="fa fa-lightbulb fa-lg"></i></button>
            </div>
            <input type="hidden" id="badge_icon" value="&#xf091;">
            
            <div class="row mt-2">
                <div class="col-6">
                    <label class="small text-muted mb-1">Color Ícono</label>
                    <input type="color" id="badge_color_icon" class="form-control p-1" style="height: 40px;" value="#FFFFFF">
                </div>
                <div class="col-6">
                    <label class="small text-muted mb-1">Color Texto</label>
                    <input type="color" id="badge_color_text" class="form-control p-1" style="height: 40px;" value="#FFFFFF">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="font-weight-bold">Decoraciones Adicionales</label>
            <div class="d-flex mb-2">
                <button type="button" class="btn btn-outline-primary deco-btn flex-fill mx-1" data-deco="ribbon" title="Listón Inferior"><i class="fa fa-bookmark fa-lg"></i></button>
                <button type="button" class="btn btn-outline-primary deco-btn flex-fill mx-1" data-deco="sunburst" title="Resplandor"><i class="fa fa-sun fa-lg"></i></button>
                <button type="button" class="btn btn-outline-primary deco-btn flex-fill mx-1" data-deco="wings" title="Alas"><i class="fa fa-dove fa-lg"></i></button>
            </div>
            
            <label class="small text-muted mb-1 mt-2">Color de las Decoraciones</label>
            <input type="color" id="badge_color_deco" class="form-control p-1" style="height: 40px;" value="#D4AF37">
        </div>
        
        <hr>
        <button id="btn_save_badge" class="btn btn-primary btn-lg btn-block shadow">
            <i class="fa fa-save"></i> Guardar Insignia
        </button>
        <a href="course_settings.php?id='.$courseid.'&tab=badges" class="btn btn-outline-secondary btn-block">Cancelar</a>
    </div>

    <!-- Lienzo -->
    <div class="col-md-5 d-flex flex-column align-items-center justify-content-center p-5 bg-light">
        <div class="canvas-wrapper bg-white shadow-sm border" style="position: relative;">
            <canvas id="c" width="400" height="400"></canvas>
            <div class="position-absolute" style="top:10px; right:10px;">
                <button type="button" class="btn btn-sm btn-light border shadow-sm" id="btn_center_all" title="Centrar todo"><i class="fa fa-crosshairs"></i></button>
            </div>
        </div>
        <div class="mt-3 text-muted text-center small">
            <i class="fa fa-info-circle text-primary"></i> Puedes hacer clic en los elementos para moverlos o cambiar su tamaño libremente.
        </div>
    </div>

    <!-- Capas -->
    <div class="col-md-3 border-left p-3 bg-light">
        <h5 class="mb-3 text-primary"><i class="fa fa-layer-group"></i> Capas</h5>
        <ul id="layers_list" class="list-group layer-list" style="cursor: grab;">
            <!-- Elementos generados dinámicamente -->
        </ul>
        <div class="mt-3 small text-muted"><i class="fa fa-arrows-alt-v"></i> Arrastra los elementos en esta lista para ordenar las capas. El de arriba cubrirá a los de abajo.</div>
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
    let activeDecos = {}; // Map para guardar las decoraciones activas

    function updateLayersPanel() {
        const list = $("#layers_list");
        list.empty();
        
        let objects = canvas.getObjects();
        // Fabric renderiza de atras hacia adelante (0 es el fondo).
        // UI: queremos el elemento de más al frente arriba (N-1 -> 0)
        for (let i = objects.length - 1; i >= 0; i--) {
            let obj = objects[i];
            if (obj.name) {
                let li = `<li class="list-group-item d-flex justify-content-between align-items-center shadow-sm mb-1 bg-white user-select-none" data-index="\${i}" style="border-radius:5px;">
                    <span><i class="fa fa-grip-lines text-muted mr-2" style="cursor:grab"></i> \${obj.name}</span>
                </li>`;
                list.append(li);
            }
        }
    }

    if (document.getElementById('layers_list')) {
        new Sortable(document.getElementById('layers_list'), {
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function () {
                let items = $('#layers_list li');
                let orderedIndices = [];
                items.each(function() { orderedIndices.push(parseInt($(this).attr('data-index'))); });
                
                let originalObjects = [...canvas.getObjects()];
                
                // Tráelos al frente en orden inverso a la lista visual (desde el fondo de la lista hacia arriba)
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

    function createShape(type) {
        const color = $("#badge_color_bg").val();
        const stroke = $("#badge_color_border").val();

        let oldIndex = -1;
        if (bgShape) {
            oldIndex = canvas.getObjects().indexOf(bgShape);
            canvas.remove(bgShape);
        }

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
        shape.set({ name: 'Forma Principal' });

        bgShape = shape;
        canvas.add(shape);
        
        if (oldIndex !== -1) {
            shape.moveTo(oldIndex);
        } else {
            shape.sendToBack(); // La figura principal va al fondo por defecto la primera vez
        }
        updateLayersPanel();
    }

    function toggleDecoration(type) {
        if (activeDecos[type]) {
            // Si ya existe, la quitamos
            canvas.remove(activeDecos[type]);
            delete activeDecos[type];
        } else {
            // Si no existe, la creamos
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
                    {x: 0, y: 0}, {x: -80, y: -60}, {x: -60, y: -20}, {x: -100, y: 10}, {x: -50, y: 30}, {x: -80, y: 60}, {x: 0, y: 40}
                ], { fill: decoColor, stroke: strokeColor, strokeWidth: 5, originX: 'right', originY: 'center', left: -80, top: 0 });
                let rightWing = new fabric.Polygon([
                    {x: 0, y: 0}, {x: 80, y: -60}, {x: 60, y: -20}, {x: 100, y: 10}, {x: 50, y: 30}, {x: 80, y: 60}, {x: 0, y: 40}
                ], { fill: decoColor, stroke: strokeColor, strokeWidth: 5, originX: 'left', originY: 'center', left: 80, top: 0 });
                
                newDecoObj = new fabric.Group([leftWing, rightWing], {
                    left: centerX, top: centerY + 10, originX: 'center', originY: 'center', selectable: true,
                    shadow: new fabric.Shadow({ color: "rgba(0,0,0,0.3)", blur: 15, offsetX: 5, offsetY: 5 })
                });
            }

            if (newDecoObj) {
                let decoNames = { 'ribbon': 'Listón', 'sunburst': 'Resplandor', 'wings': 'Alas' };
                newDecoObj.set({ name: decoNames[type] });
                activeDecos[type] = newDecoObj;
                canvas.add(newDecoObj);
                
                // Las decoraciones van al fondo por defecto
                // Sin alterar la posición de bgShape para que no suba y tape el texto
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
        
        textObj = new fabric.Text($("#badge_text").val(), {
            fontFamily: "Arial",
            fontSize: 26,
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

    $("#btn_center_all").click(function() {
        if (bgShape) { bgShape.set({left: centerX, top: centerY, scaleX: 1, scaleY: 1, angle: 0}); }
        if (iconObj) { iconObj.set({left: centerX, top: centerY - 20, scaleX: 1, scaleY: 1, angle: 0}); }
        if (textObj) { textObj.set({left: centerX, top: centerY + 115, scaleX: 1, scaleY: 1, angle: 0}); }
        

        if (activeDecos["ribbon"]) activeDecos["ribbon"].set({left: centerX, top: centerY + 90, scaleX: 1, scaleY: 1, angle: 0});
        if (activeDecos["sunburst"]) activeDecos["sunburst"].set({left: centerX, top: centerY, scaleX: 1, scaleY: 1, angle: 0});
        if (activeDecos["wings"]) activeDecos["wings"].set({left: centerX, top: centerY + 10, scaleX: 1, scaleY: 1, angle: 0});
        
        canvas.renderAll();
    });

    $(".shape-btn").click(function() {
        $(".shape-btn").removeClass("btn-primary").addClass("btn-outline-primary");
        $(this).removeClass("btn-outline-primary").addClass("btn-primary");
        createShape($(this).data("shape"));
        canvas.renderAll();
    });

    $("#badge_text").on("input", function() {
        if (textObj) textObj.set("text", $(this).val());
        else createText();
        canvas.renderAll();
    });

    $(".icon-btn").click(function() {
        $(".icon-btn").removeClass("btn-primary").addClass("btn-outline-secondary");
        $(this).removeClass("btn-outline-secondary").addClass("btn-primary");
        $("#badge_icon").val($(this).data("icon"));
        createIcon();
        canvas.renderAll();
    });

    $("#badge_color_bg, #badge_color_border").on("input", function() {
        if (bgShape) {
            bgShape.set("fill", $("#badge_color_bg").val());
            bgShape.set("stroke", $("#badge_color_border").val());
        }
        reColorDecorations();
        canvas.renderAll();
    });

    $("#badge_color_icon").on("input", function() {
        if (iconObj) iconObj.set("fill", $(this).val());
        canvas.renderAll();
    });

    $("#badge_color_text").on("input", function() {
        if (textObj) textObj.set("fill", $(this).val());
        canvas.renderAll();
    });

    $(".deco-btn").click(function() {
        // Togglear estilo del botón
        $(this).toggleClass("btn-outline-primary btn-primary");
        // Añadir o remover la decoración
        toggleDecoration($(this).data("deco"));
        canvas.renderAll();
    });

    $("#badge_color_deco").on("input", function() {
        reColorDecorations();
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
            
            // Activar solo el listón por defecto
            $(".deco-btn[data-deco='ribbon']").toggleClass("btn-outline-primary btn-primary");
            toggleDecoration("ribbon");

            canvas.renderAll();
        } catch(e) {
            console.error("Initialization error:", e);
        }
    }, 500);
});
EOF;

$PAGE->requires->js_amd_inline("require(['jquery'], function($) {\n" . $js_code . "\n});");

echo $OUTPUT->footer();

