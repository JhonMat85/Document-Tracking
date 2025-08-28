# Documentación de la Tabla de Solicitudes (Actualizada)

**URL:** `http://document-tracking.test/admin/requests`  
**Fecha de implementación:** 27 de agosto de 2025  
**Recurso:** `RequestResource.php`  
**Formato aplicado:** Basado en `tabla-clientes-formato.md`

## 📋 Información General

La tabla de solicitudes ha sido actualizada para utilizar el diseño responsivo con layout tipo **Split** que organiza la información en columnas apiladas (Stack), siguiendo el mismo patrón visual y funcional documentado para la tabla de clientes.

## 🗂️ Estructura de Columnas

### Columna Principal (Izquierda)
**Stack 1 - Información Básica:**

1. **🔢 Número de Solicitud**
   - Campo: `request_number`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Ordenamiento: ✅ Habilitado
     - Estilo: Negrita, Color primario
     - Función: Copiable
     - Tooltip: "Número único de la solicitud"

2. **🏥 Cliente**
   - Campo: `client.business_name`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada (a través de relación)
     - Ordenamiento: ✅ Habilitado
     - Estilo: Tamaño grande, Peso medio
     - Límite: 25 caracteres
     - Tooltip: Muestra texto completo si excede el límite

3. **🏢 Área Requirente**
   - Campo: `requesting_department`
   - Tipo: Badge
   - Características:
     - Color: Gris
     - Placeholder: "Sin área"
     - Límite: 20 caracteres

### Columna Central
**Stack 2 - Clasificación y Origen:**

4. **🚨 Urgencia**
   - Campo: `urgency`
   - Tipo: Badge
   - Valores y colores:
     - `urgent` → 🔴 Urgente (Rojo - danger)
     - `normal` → 🟢 Normal (Verde - success)

5. **📞 Origen de Solicitud**
   - Campo: `origin`
   - Tipo: Badge
   - Valores y colores:
     - `whatsapp` → 💬 WhatsApp (Verde - success)
     - `email` → 📧 Email (Azul - info)
     - `phone` → 📞 Teléfono (Amarillo - warning)
     - `in_person` → 👥 Presencial (Azul primario - primary)
     - `system` → 💻 Sistema (Gris - gray)

6. **👤 Contacto**
   - Campo: `contact.full_name`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Icono: `heroicon-o-user`
     - Color: Azul (info)
     - Placeholder: "Sin contacto"
     - Límite: 20 caracteres

### Columna Derecha
**Stack 3 - Estado y Fechas:**

7. **📊 Estado del Proceso**
   - Campo: `state.name`
   - Tipo: Badge
   - Características:
     - Color: Azul primario (primary)
     - Icono: `heroicon-o-clipboard-document-check`
     - Relación con ProcessState

8. **📅 Fecha de Solicitud**
   - Campo: `request_date`
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
1. **🚨 Urgencia** (SelectFilter)
   - Múltiple selección
   - Precarga de opciones
   - Opciones: 🔴 Urgente, 🟢 Normal

2. **📞 Origen de Solicitud** (SelectFilter)
   - Múltiple selección
   - Precarga de opciones
   - Todas las opciones con emojis

3. **📊 Estado** (SelectFilter)
   - Múltiple selección
   - Precarga de opciones
   - Solo estados activos de tipo 'request'

4. **🏥 Cliente** (SelectFilter)
   - Búsqueda habilitada
   - Precarga de opciones
   - Filtro por relación

5. **🆕 Recientes** (Filter personalizado)
   - Últimos 7 días
   - Tipo toggle

6. **⚡ Solo Urgentes** (Filter personalizado)
   - Filtra solo solicitudes urgentes
   - Tipo toggle

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

- **Título:** "📋 No hay solicitudes registradas"
- **Descripción:** "Comience creando su primera solicitud en el sistema."
- **Icono:** `heroicon-o-clipboard-document-list`

## 🔧 Características Técnicas

- **Modelo:** `App\Models\Request`
- **Recurso:** `App\Filament\Resources\RequestResource`
- **Navegación:** Grupo "📋 Gestión Comercial"
- **Icono de navegación:** `heroicon-o-clipboard-document-list`
- **Acción por defecto:** Editar (click en fila)
- **URL de edición:** `/admin/requests/{id}/edit`

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

## 🆕 Mejoras Implementadas

### Nuevas Funcionalidades
- **Filtros adicionales** para cliente y filtros de conveniencia
- **Tooltips informativos** en campos clave
- **Placeholders** para campos vacíos
- **Botón de acciones visible** igual que en tabla de clientes

### Consistencia Visual
- **Emojis descriptivos** en todas las etiquetas
- **Colores coherentes** con el sistema de clientes
- **Iconos Heroicon** estándar en todas las acciones
- **Badges coloridos** para categorización visual

### Experiencia de Usuario
- **Campos copiables** (número de solicitud)
- **Tooltips** para información adicional
- **Filtros inteligentes** (recientes, urgentes)
- **Persistencia de estado** de la tabla
- **Actualización automática** para datos en tiempo real
- **Botón de acciones consistente** con otros recursos

## 📊 Relaciones y Dependencias

- **Relación con Client:** `client.business_name`
- **Relación con ClientContact:** `contact.full_name`
- **Relación con ProcessState:** `state.name`
- **RelationManager:** `AttachmentsRelationManager`

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

### Imports duplicados
- **Problema:** Mismo import declarado múltiples veces
- **Solución:** Eliminar duplicados, mantener solo una declaración

La tabla mantiene total compatibilidad con el sistema existente mientras proporciona una experiencia visual mejorada y funcionalidades adicionales para la gestión eficiente de solicitudes.