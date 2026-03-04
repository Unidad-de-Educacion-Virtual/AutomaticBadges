# Tarea: Integrar librerías externas localmente en Moodle

Actualmente, el diseñador de insignias (`badge_designer.php`) depende de CDNs externos para cargar **Fabric.js** y **Font Awesome**. Para asegurar que el plugin funcione en entornos cerrados (intranets) y cumpla con los estándares de revisión de la comunidad Moodle, es necesario alojar estas librerías de forma local.

## Pasos a seguir:

### 1. Descargar las librerías
*   **Fabric.js**: Descargar la versión 5.3.1 (o similar) desde el repositorio oficial o CDNJS y guardarla localmente.
*   **Font Awesome**: Descargar los archivos CSS y las fuentes (archivos `.woff2`, `.ttf`, etc.) de la versión 6 gratuita.

### 2. Ubicar los archivos en el plugin
*   Crear una estructura de carpetas adecuada en el plugin si no existe, por ejemplo:
    *   `local/automatic_badges/js/fabric.min.js`
    *   `local/automatic_badges/css/fontawesome.min.css`
    *   `local/automatic_badges/webfonts/` (para los archivos de fuentes)

### 3. Modificar `badge_designer.php`
*   Eliminar las líneas actuales que cargan los recursos desde `cdnjs.cloudflare.com`.
*   Usar la API de la página de Moodle (`$PAGE`) para requerir los archivos locales:
    ```php
    $PAGE->requires->css(new moodle_url('/local/automatic_badges/css/fontawesome.min.css'));
    ```
    *(Nota: Revisa si Moodle soporta una forma mejor de integrar FontAwesome localmente o si tu tema ya provee una versión adecuada)*

### 4. Resolver el problema de AMD con Fabric.js
*   Moodle utiliza RequireJS (AMD) de forma nativa. Fabric.js, al detectar `define`, intenta registrarse como un módulo en lugar de exponer la variable global `fabric`.
*   **Opción A (Recomendada - Carga de Módulo AMD):** Envolver la inicialización del diseñador en un módulo AMD de Moodle que requiera Fabric.js como dependencia. Para esto, es ideal colocar el código JS del diseñador en un archivo separado dentro de la carpeta `amd/src/` del plugin y compilarlo con Grunt.
*   **Opción B (Solución rápida - Script tag manual con URL local):** Cambiar el `$PAGE->requires->js` hacia la URL local, y mantener el "hack" temporal de ocultar `window.define` antes de que se parsee la librería.
    ```php
    echo '<script>var _backup_define = window.define; window.define = undefined;</script>';
    echo '<script src="' . new moodle_url('/local/automatic_badges/js/fabric.min.js') . '"></script>';
    echo '<script>window.define = _backup_define;</script>';
    ```

### 5. Pruebas
*   Desconectar el equipo de internet (o bloquear salidas a un CDN) y verificar que el previsualizador del lienzo gráfico siga cargando correctamente.
*   Verificar que los iconos en el lienzo se sigan dibujando al seleccionarlos.
