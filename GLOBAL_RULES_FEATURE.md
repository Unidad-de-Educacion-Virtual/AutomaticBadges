# Funcionalidad de Reglas Globales - Insignias Automáticas

## Descripción General

Se ha agregado la capacidad de crear **reglas globales** que se apliquen a todas las actividades de un tipo específico (ej: tareas, cuestionarios, foros, etc.) en lugar de solo a una actividad individual.

## Cambios Realizados

### 1. Base de Datos (`db/install.xml` y `db/upgrade.php`)

Se agregaron dos nuevos campos a la tabla `local_automatic_badges_rules`:

- **`is_global_rule`** (INT, DEFAULT 0): Indica si la regla es global (1) o específica (0)
- **`activity_type`** (CHAR 50): Almacena el tipo de actividad (assign, quiz, forum, workshop, choice, etc.)

### 2. Formulario (`forms/form_add_rule.php`)

Se agregaron dos nuevos elementos al formulario:

- **Checkbox `is_global_rule`**: Permite al usuario elegir entre crear una regla global o específica
- **Selector `activity_type`**: Muestra opciones de tipos de actividades cuando se habilita la regla global
  - Se deshabilita automáticamente cuando la regla no es global

### 3. Procesamiento (`add_rule.php`)

Se actualizo el procesamiento del formulario para:

- Capturar el campo `is_global_rule`
- Guardar el tipo de actividad si es una regla global
- No guardar `activityid` si es una regla global
- Usar `activity_type` en lugar de `activityid` para las reglas globales

### 4. Motor de Reglas (`classes/rule_engine.php`)

Se agregaron nuevos métodos para evaluar reglas globales:

#### `check_global_rule($rule, $userid)`
Obtiene todas las actividades del tipo especificado en el curso y valida la regla contra ellas.

#### `check_global_grade_rule($rule, $userid, $cmids)`
Evalúa si el usuario tiene al menos una calificación que cumple el criterio en alguna de las actividades del tipo.

#### `check_global_forum_rule($rule, $userid, $cmids)`
Cuenta el total de posts/respuestas en todos los foros del tipo y verifica si cumple el mínimo requerido.

### 5. Strings de Idioma

Se agregaron nuevas claves de idioma en `lang/en/local_automatic_badges.php` y `lang/es/local_automatic_badges.php`:

- `isglobalrule`: Etiqueta del checkbox
- `isglobalrule_help`: Ayuda sobre reglas globales
- `activitytype`: Etiqueta del selector de tipo
- `activitytype_help`: Ayuda sobre el selector de tipo

### 6. Version del Plugin

Se actualizó la versión a `2025122801` (v0.2.0) para reflejar los cambios.

## Flujo de Uso

### Crear una Regla Global

1. Ir a "Gestión de insignias automáticas" → "Agregar nueva regla"
2. Configurar el tipo de criterio (calificación, foro, etc.)
3. **Habilitar** el checkbox "Aplicar a todas las actividades del tipo (Regla global)"
4. Seleccionar el tipo de actividad (Tareas, Cuestionarios, Foros, etc.)
5. Configurar los parámetros específicos:
   - Para **calificación**: Establecer la calificación mínima
   - Para **forum**: Establecer el número mínimo de respuestas
6. Seleccionar la insignia a otorgar
7. Guardar la regla

### Comportamiento

#### Regla Global de Calificación
- Se evalúa si el usuario tiene **al menos una** actividad del tipo con calificación ≥ al mínimo
- Ejemplo: Si configuras "Tareas con calificación ≥ 70%", se revisan todas las tareas del curso

#### Regla Global de Foro
- Se cuenta el **total de respuestas** en todos los foros del tipo
- Ejemplo: Si requieres 5 respuestas en "Foros", se cuentan todas las respuestas en todos los foros

## Ejemplos de Casos de Uso

### Ejemplo 1: Insignia por completar tareas
```
- Criterio: Calificación mínima
- Regla Global: Sí
- Tipo de actividad: Tareas (assign)
- Calificación mínima: 70%
- Insignia: "Tareas Completadas"
```
**Resultado**: El usuario obtiene la insignia cuando completa al menos una tarea con 70% o más.

### Ejemplo 2: Insignia por participación en foros
```
- Criterio: Participación en foros
- Regla Global: Sí
- Tipo de actividad: Foros
- Respuestas mínimas: 10
- Insignia: "Participante Activo"
```
**Resultado**: El usuario obtiene la insignia cuando suma 10 respuestas en todos los foros del curso.

### Ejemplo 3: Insignia por completar cuestionarios
```
- Criterio: Calificación mínima
- Regla Global: Sí
- Tipo de actividad: Cuestionarios (quiz)
- Calificación mínima: 80%
- Insignia: "Experto en Cuestionarios"
```
**Resultado**: El usuario obtiene la insignia cuando aprueba al menos un cuestionario con 80% o más.

## Compatibilidad

- Totalmente compatible con las reglas específicas existentes
- Las reglas antiguas (no globales) continúan funcionando normalmente
- Se puede mezclar reglas globales y específicas en el mismo curso

## Migración

Si actualizas desde una versión anterior, el script de upgrade (`db/upgrade.php`) automáticamente:
1. Agrega los nuevos campos a la tabla
2. Establece todas las reglas existentes como no-globales (is_global_rule = 0)
3. Mantiene toda la funcionalidad existente intacta

## Notas Técnicas

- El motor de reglas utiliza `get_fast_modinfo()` para obtener las actividades de forma eficiente
- Solo se consideran actividades visibles para el usuario
- Las reglas globales son más eficientes que crear múltiples reglas específicas
- Los campos `activityid` e `activity_type` son mutuamente excluyentes según el tipo de regla
