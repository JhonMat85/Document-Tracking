# Documentación de la Tabla de RFQs (Formato Estandarizado)

**URL:** `http://document-tracking.test/admin/rfqs`  
**Fecha de implementación:** 27 de agosto de 2025  
**Recurso:** `RfqResource.php`  
**Formato aplicado:** Basado en `tabla-solicitudes-formato-actualizada.md`

## 📋 Información General

La tabla de RFQs ha sido actualizada para utilizar el diseño responsivo con layout tipo **Split** que organiza la información en columnas apiladas (Stack), siguiendo el mismo patrón visual y funcional documentado para la tabla de solicitudes y clientes. El formulario incluye funcionalidad completa de subida de archivos.

## 📝 Formulario de Creación/Edición

### Estructura con Tabs
El formulario utiliza una estructura de pestañas (Tabs) para organizar la información:

**📋 Tab 1 - Información del RFQ:**
- 🔢 Número de RFQ (requerido)
- 📊 Estado (con valor por defecto del estado inicial)
- 📝 Solicitud Asociada (opcional, relación con Request)
- 📅 Fecha de Recepción (requerida, por defecto now())
- ⏰ Fecha Límite para Cotizar (requerida, debe ser posterior a fecha de recepción)

**📝 Tab 2 - Descripción del Servicio:**
- 📝 Descripción del Servicio (requerida, 6 filas)
- 📝 Notas Adicionales (opcional, 4 filas)

**📎 Tab 3 - Documentos:**
- 📄 Documentos del RFQ (múltiples archivos)
  - Formatos: PDF, PNG, JPG, DOC, DOCX
  - Máximo: 10 archivos de 15MB cada uno
  - Directorio: `rfq-attachments`
  - Layout: Grid
  - Almacenamiento: Público

## 🗂️ Estructura de Columnas

### Columna Principal (Izquierda)
**Stack 1 - Información Básica:**

1. **🔢 Número RFQ**
   - Campo: `rfq_number`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Ordenamiento: ✅ Habilitado
     - Estilo: Negrita, Color primario
     - Función: Copiable
     - Tooltip: "Número único del RFQ"

2. **🏥 Cliente**
   - Campo: `request.client.business_name`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada (a través de relación)
     - Ordenamiento: ✅ Habilitado
     - Estilo: Tamaño grande, Peso medio
     - Límite: 25 caracteres
     - Tooltip: Muestra texto completo si excede el límite
     - Placeholder: "Sin cliente"

3. **🏢 Área**
   - Campo: `request.requesting_department`
   - Tipo: Badge
   - Características:
     - Color: Gris
     - Placeholder: "Sin área"
     - Límite: 20 caracteres

### Columna Central
**Stack 2 - Clasificación y Estado:**

4. **⏰ Urgencia**
   - Campo: `quote_deadline`
   - Tipo: Badge
   - Valores y colores:
     - Vencido → 🔴 Vencido (Rojo - danger)
     - Próximo a vencer (≤3 días) → 🟡 Próximo a vencer (Amarillo - warning)
     - En tiempo → 🟢 En tiempo (Verde - success)
     - Sin fecha → ⚪ Sin fecha (Gris - gray)

5. **📊 Cotizaciones**
   - Campo: `quotes_count`
   - Tipo: Badge
   - Valores y colores:
     - 0 → 📋 Sin cotizaciones (Amarillo - warning)
     - 1 → 📄 1 cotización (Azul - info)
     - >1 → 📄 X cotizaciones (Verde - success)

6. **📝 Descripción**
   - Campo: `detailed_description`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Icono: `heroicon-o-document-text`
     - Color: Azul (info)
     - Placeholder: "Sin descripción"
     - Límite: 30 caracteres
     - Tooltip: Muestra texto completo si excede el límite

### Columna Derecha
**Stack 3 - Estado y Fechas:**

7. **📊 Estado del Proceso**
   - Campo: `state.name`
   - Tipo: Badge
   - Características:
     - Color: Azul primario (primary)
     - Icono: `heroicon-o-clipboard-document-check`
     - Relación con ProcessState

8. **📅 Fecha de Recepción**
   - Campo: `received_date`
   - Tipo: TextColumn
   - Características:
     - Formato: `d/m/Y` (27/08/2025)
     - Ordenamiento: ✅ Habilitado
     - Color: Gris
     - Tamaño: Pequeño
     - Icono: `heroicon-o-calendar-days`

9. **📅 Fecha de Creación**
   - Campo: `created_at`
   - Tipo: TextColumn
   - Características:
     - Formato: `d/m/Y` (27/08/2025)
     - Ordenamiento: ✅ Habilitado
     - Color: Gris
     - Tamaño: Pequeño
     - Oculta por defecto (toggleable)

## 🎛️ Funcionalidades de la Tabla

### Filtros Disponibles
1. **⏰ Próximos a Vencer** (Filter personalizado)
   - Filtros RFQs con deadline en los próximos 3 días
   - Tipo toggle

2. **🔴 Vencidos** (Filter personalizado)
   - Filtros RFQs con deadline pasado
   - Tipo toggle

3. **📋 Sin Cotizaciones** (Filter personalizado)
   - Filtros RFQs que no tienen cotizaciones
   - Tipo toggle

4. **📊 Estado** (SelectFilter)
   - Múltiple selección
   - Precarga de opciones
   - Solo estados activos de tipo 'rfq'

5. **🆕 Recientes** (Filter personalizado)
   - Últimos 7 días
   - Tipo toggle

6. **📅 Rango de Fechas** (Filter con formulario)
   - Filtro por rango de fechas de recepción
   - Campos: "Desde" y "Hasta"

### Acciones de Fila
**Grupo de Acciones (ActionGroup):**
- **👁️ Ver** - Color: info, Icono: `heroicon-o-eye`
- **✏️ Editar** - Color: warning, Icono: `heroicon-o-pencil-square`
- **🗑️ Eliminar** - Color: danger, Icono: `heroicon-o-trash`

**Configuración del botón:**
- Label: "Acciones"
- Icono: `heroicon-o-ellipsis-vertical`
- Tamaño: `sm`
- Color: `gray`
- Método: `->button()` (clave para visibilidad)

### Acciones Masivas (Bulk Actions)
1. **🗑️ Eliminar seleccionados** - Acción de eliminación estándar

### Configuraciones de Tabla
- **Ordenamiento por defecto:** `created_at DESC`
- **Paginación:** 10, 25, 50, 100 registros por página
- **Búsqueda global:** ✅ Habilitada
- **Persistencia:** Ordenamiento, búsqueda y filtros en sesión
- **Actualización automática:** Cada 30 segundos
- **Carga diferida:** ✅ Habilitada
- **Filas rayadas:** ✅ Habilitada
- **Layout de filtros:** Colapsible arriba del contenido

## 📱 Diseño Responsivo

- **Layout:** ContentGrid con configuración:
  - `md`: 1 columna
  - `lg`: 1 columna
- **Split responsivo:** A partir de `md` (medium breakpoint)
- **Espaciado entre stacks:** 1 unidad

## 🚫 Estado Vacío

- **Título:** "📋 No hay RFQs registrados"
- **Descripción:** "Comience creando su primer RFQ en el sistema."
- **Icono:** `heroicon-o-document-text`

## 🔧 Características Técnicas

- **Modelo:** `App\Models\Rfq`
- **Recurso:** `App\Filament\Resources\RfqResource`
- **Navegación:** Grupo "📋 Gestión Comercial"
- **Icono de navegación:** `heroicon-o-document-text`
- **URL de edición:** `/admin/rfqs/{id}/edit`

## ⚙️ Imports y Configuración de Clases

### Imports Críticos (Filament 4)
```php
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
```

### Declaración de Método
```php
public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
```

### Configuración de Acciones
```php
->actions([
    ActionGroup::make([
        ViewAction::make()
            ->icon('heroicon-o-eye')
            ->color('info'),
        EditAction::make()
            ->icon('heroicon-o-pencil-square')
            ->color('warning'),
        DeleteAction::make()
            ->icon('heroicon-o-trash')
            ->color('danger'),
    ])
    ->label('Acciones')
    ->icon('heroicon-o-ellipsis-vertical')
    ->size('sm')
    ->color('gray')
    ->button(), // ⚠️ CRÍTICO: Sin esto no se ve el botón
])
```

## 🆕 Funcionalidades Específicas de RFQs

### Lógica de Urgencia por Deadline
- **Vencido:** `quote_deadline < now()`
- **Próximo a vencer:** `quote_deadline <= now()->addDays(3)`
- **En tiempo:** `quote_deadline > now()->addDays(3)`

### Contador de Cotizaciones
- Relación `hasMany` con modelo `Quote`
- Badge dinámico según cantidad
- Filtro específico para RFQs sin cotizaciones

### Filtros Especializados
- **Próximos a vencer:** Identifica RFQs críticos
- **Vencidos:** Para seguimiento de deadlines perdidos
- **Sin cotizaciones:** Para RFQs pendientes de proceso

### ✅ Sistema de Persistencia de Archivos
- **Implementación completa:** Archivos se guardan automáticamente en tabla `attachments`
- **CreateRfq:** Método `afterCreate()` maneja persistencia manual de archivos
- **EditRfq:** Métodos `mutateFormDataBeforeFill()` y `afterSave()` para cargar y guardar archivos
- **Validación:** Solo procesa objetos UploadedFile válidos
- **Logging:** Sistema completo de logs para debugging
- **RelationManager:** Vista de documentos adjuntos con opciones de descarga y eliminación
- **Categoría:** Archivos se marcan como 'official' (documentos oficiales de RFQ)
- **Compatibilidad ENUM:** Usa valores permitidos en tabla attachments: evidence, official, backup, generated

## 📊 Relaciones y Dependencias

- **Relación con Request:** `request.client.business_name`
- **Relación con Request:** `request.requesting_department`
- **Relación con ProcessState:** `state.name`
- **Relación con Quote:** `quotes` (hasMany)
- **Relación con Attachment:** `attachments` (morphMany)

### Sistema de Archivos Adjuntos
- **Tipo de relación:** Polimórfica (morphMany)
- **Modelo:** `App\Models\Attachment`
- **Directorio de almacenamiento:** `public/rfq-attachments/`
- **Formatos soportados:** PDF, PNG, JPG, DOC, DOCX
- **Límites:** 10 archivos máximo, 15MB por archivo
- **Visibilidad:** Pública

## 🎨 Consistencia Visual

### Emojis Descriptivos
- 🔢 Número RFQ
- 🏥 Cliente
- 🏢 Área
- ⏰ Urgencia
- 📊 Cotizaciones
- 📝 Descripción
- 📊 Estado
- 📅 Fechas

### Colores Coherentes
- **Primary:** Número RFQ, Estado
- **Success:** En tiempo, Con cotizaciones múltiples
- **Warning:** Próximo a vencer, Sin cotizaciones
- **Danger:** Vencido
- **Info:** Cliente, Descripción, Ver
- **Gray:** Área, Fechas, Botón acciones

## 🐛 Soluciones a Problemas Comunes

### Error "Class not found"
- **Problema:** Imports incorrectos de Filament 4
- **Solución:** Usar `Filament\Actions\*` no `Filament\Tables\Actions\*`

### Botón de acciones no visible
- **Problema:** Falta método `->button()` en ActionGroup
- **Solución:** Agregar `->button()` al final del ActionGroup

### Error de compatibilidad de tipos
- **Problema:** Declaración incorrecta del método table()
- **Solución:** Usar namespace completo `\Filament\Tables\Table`

### Relaciones no encontradas
- **Problema:** Acceso a relaciones anidadas
- **Solución:** Verificar que las relaciones estén definidas en los modelos

La tabla mantiene total compatibilidad con el sistema existente mientras proporciona una experiencia visual mejorada y funcionalidades específicas para la gestión eficiente de RFQs.