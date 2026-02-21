alert("Script parsing OK");
require(["jquery"], function($) {
    function dlog(m) { 
        if(typeof m === "object") m = JSON.stringify(m);
        $("#debug_log").append("<br>" + m); 
        console.log(m); 
    }
    
    dlog("Init script started");

    if (typeof fabric === "undefined") {
        alert("Error: La librería gráfica no pudo cargarse. Por favor verifica tu conexión a internet (CDN).");
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
            shape = new fabric.Circle({ radius: baseSize, ...commonProps });
        } else if (type === "square") {
            shape = new fabric.Rect({ width: baseSize*2, height: baseSize*2, rx: 30, ry: 30, ...commonProps });
        } else {
            shape = new fabric.Circle({ radius: baseSize, ...commonProps });
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
        dlog("Save clicked");
        const btn = $(this);
        const name = $("#badge_text").val();
        
        if (!name) { alert("Nombre requerido"); return; }
        
        dlog("Name: " + name);
        btn.prop("disabled", true).text("Guardando...");
        
        try {
            canvas.discardActiveObject().renderAll();
            
            const dataURL = canvas.toDataURL({ format: "png", multiplier: 2 });
            dlog("DataURL created...");

            $.ajax({
            url: M.cfg.wwwroot + "/local/automatic_badges/ajax/save_badge_design.php",
            type: "POST",
            data: {
                courseid: '.$courseid.',
                sesskey: M.cfg.sesskey,
                name: name,
                imagedata: dataURL
            },
            dataType: "json",
            success: function(r) {
                if (r.success) window.location.href = M.cfg.wwwroot + "/local/automatic_badges/course_settings.php?id='.$courseid.'&tab=badges";
                else { alert(r.message); btn.prop("disabled", false).text("Guardar Insignia"); }
            },
            error: function(err) {
                dlog("Error ajax: " + JSON.stringify(err));
                alert("Error de conexión al guardar.");
                btn.prop("disabled", false).text("Guardar Insignia");
            }
        });
        } catch(e) {
            dlog("Error in save: " + e.message);
        }
    });

    setTimeout(function() {
        try {
            dlog("Timeout starting");
            createShape("circle");
            dlog("Shape created");
            createIcon();
            dlog("Icon created");
            createText();
            dlog("Text created");
            canvas.renderAll();
            dlog("Rendered");
        } catch(e) {
            dlog("Error en inicializacion: " + e.message);
        }
    }, 500);

});
