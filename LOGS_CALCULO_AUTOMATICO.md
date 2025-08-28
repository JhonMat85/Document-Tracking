# 📊 Logs de Cálculo Automático - Cotizaciones

## 🎯 Propósito
Este documento explica cómo monitorear y debuggear el funcionamiento del cálculo automático en la creación de cotizaciones mediante logs detallados.

## 📍 Ubicación de Logs
- **Archivo**: `storage/logs/laravel.log`
- **Logs en tiempo real**: `php artisan log:clear && tail -f storage/logs/laravel.log`

## 🔍 Tipos de Logs Implementados

### 1. 🔢 QUANTITY UPDATED
**Cuándo se ejecuta**: Al modificar la cantidad de un item
**Información registrada**:
- Valor actualizado de cantidad
- Timestamp del evento

### 2. 💰 UNIT_PRICE UPDATED  
**Cuándo se ejecuta**: Al modificar el precio unitario de un item
**Información registrada**:
- Valor actualizado del precio
- Timestamp del evento

### 3. 📋 DETAILS OBTENIDOS
**Cuándo se ejecuta**: Al obtener todos los items del repeater
**Información registrada**:
- Número total de items
- Array completo con todos los datos de items
- Método utilizado para obtener los datos

### 4. 📊 ITEM CALCULADO
**Cuándo se ejecuta**: Por cada item al calcular subtotales
**Información registrada**:
- Índice del item (#0, #1, #2...)
- Cantidad del item
- Precio unitario del item
- Subtotal del item (cantidad × precio)
- Total acumulativo

### 5. 💰 SUBTOTAL CALCULADO
**Cuándo se ejecuta**: Después de sumar todos los items
**Información registrada**:
- Total antes del formateo
- Total formateado (2 decimales)

### 6. 🧾 IMPUESTOS CALCULADOS
**Cuándo se ejecuta**: Al calcular impuestos y total final
**Información registrada**:
- Porcentaje de impuesto aplicado
- Monto de impuesto calculado
- Total final (subtotal + impuestos)

### 7. ✅ CÁLCULO COMPLETADO
**Cuándo se ejecuta**: Al finalizar la actualización de campos
**Información registrada**:
- Valor asignado al campo subtotal
- Valor asignado al campo tax_amount
- Valor asignado al campo total

### 8. 📈 TAX_PERCENTAGE UPDATED
**Cuándo se ejecuta**: Al cambiar el porcentaje de impuesto manualmente
**Información registrada**:
- Nuevo porcentaje de impuesto
- Timestamp del evento

## 🔄 MÉTODOS DE OBTENCIÓN DE DATOS (NUEVO)

### Método 1: ✅ REPEATER COMPONENT
- **Descripción**: Obtiene datos directamente del componente repeater padre
- **Prioridad**: Más confiable en Filament 4

### Método 2: ✅ PATH DIRECTO
- **Descripción**: Usa `$get('details')` para obtener datos
- **Prioridad**: Alternativa si el método 1 falla

### Método 3: ✅ FORMULARIO COMPLETO
- **Descripción**: Navega hacia el formulario principal para obtener datos
- **Prioridad**: Último recurso

## 🛠️ Comandos Útiles para Monitoreo

### Ver logs en tiempo real:
```bash
# Limpiar logs y monitorear en tiempo real
php artisan log:clear && tail -f storage/logs/laravel.log

# Solo logs de cálculo automático
tail -f storage/logs/laravel.log | grep "QUANTITY\|UNIT_PRICE\|TAX_PERCENTAGE\|CÁLCULO\|MÉTODO"
```

### Filtrar logs específicos:
```bash
# Solo logs de cantidad
grep "QUANTITY UPDATED" storage/logs/laravel.log

# Solo logs de precios
grep "UNIT_PRICE UPDATED" storage/logs/laravel.log

# Solo logs de completación
grep "CÁLCULO COMPLETADO" storage/logs/laravel.log

# Logs de métodos de obtención de datos
grep "MÉTODO\|METHOD" storage/logs/laravel.log
```

### Ver logs de hoy:
```bash
grep "$(date '+%Y-%m-%d')" storage/logs/laravel.log | grep "QUANTITY\|UNIT_PRICE\|TAX_PERCENTAGE"
```

## 🔄 Flujo Esperado de Logs

### Al modificar CANTIDAD:
1. `🔢 QUANTITY UPDATED`
2. `📋 DETAILS OBTENIDOS`  
3. `📊 ITEM #0 CALCULADO`
4. `📊 ITEM #1 CALCULADO` (si hay más items)
5. `💰 SUBTOTAL CALCULADO`
6. `🧾 IMPUESTOS CALCULADOS`
7. `✅ CÁLCULO COMPLETADO`

### Al modificar PRECIO:
1. `💰 UNIT_PRICE UPDATED`
2. `📋 DETAILS OBTENIDOS (PRICE)`
3. `📊 ITEM #0 CALCULADO (PRICE)`
4. `💰 SUBTOTAL CALCULADO (PRICE)`
5. `🧾 IMPUESTOS CALCULADOS (PRICE)`
6. `✅ CÁLCULO COMPLETADO (PRICE)`

### Al modificar % IMPUESTO:
1. `📈 TAX_PERCENTAGE UPDATED`
2. `📊 VALORES OBTENIDOS PARA RECALCULO`
3. `🧾 RECALCULO DE IMPUESTOS COMPLETADO`
4. `✅ RECALCULO COMPLETADO`

## 🚨 Problemas Potenciales a Identificar

### ❌ Si NO aparecen logs:
- El evento `live(onBlur: true)` no se está disparando
- Problema en la configuración de Filament

### ❌ Si aparecen logs pero los campos no se actualizan:
- Los paths absolutos (`$get('details')`, `$set('subtotal')`) no funcionan
- Problema en la navegación entre tabs

### ❌ Si los cálculos están incorrectos:
- Revisar los logs de `📊 ITEM CALCULADO` para verificar valores
- Verificar que `quantity` y `unit_price` no sean null o 0

### ❌ Si falta información en los logs:
- Verificar que el array `$details` contiene los datos esperados
- Verificar que `$get('tax_percentage')` retorna un valor válido

### ❌ Si los métodos de obtención de datos fallan:
- `⚠️ Método 1 - REPEATER COMPONENT falló`
- `⚠️ Método 2 - PATH DIRECTO falló`
- `❌ Método 3 - FORMULARIO COMPLETO falló`

## 🎯 Resultado Esperado

Con los logs funcionando correctamente, deberías ver una secuencia completa de mensajes cada vez que:
- Modifiques la cantidad de un item
- Modifiques el precio de un item  
- Cambies el porcentaje de impuesto

Los valores mostrados en los logs deben coincidir con los que aparecen en la interfaz de usuario.

## 📝 Notas Importantes

- Los logs incluyen emojis para facilitar la identificación visual
- Cada tipo de operación tiene identificadores únicos (QUANTITY, PRICE, TAX_PERCENTAGE)
- Los logs contienen tanto valores sin formatear como formateados
- El timestamp permite rastrear la secuencia temporal de eventos
- **NUEVO**: Sistema de fallback con 3 métodos de obtención de datos

---

**🔧 Para deshabilitar logs en producción**: Comentar o eliminar las líneas `Log::info()` del archivo `QuoteResource.php`