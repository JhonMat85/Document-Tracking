# Documentación de la Tabla de Clientes

**URL:** `http://document-tracking.test/admin/clients`  
**Fecha de documentación:** 27 de agosto de 2025  
**Recurso:** `ClientResource.php`

## 📋 Información General

La tabla de clientes utiliza un diseño responsivo con layout tipo **Split** que organiza la información en columnas apiladas (Stack) para mejorar la visualización en diferentes dispositivos.

## 🗂️ Estructura de Columnas

### Columna Principal (Izquierda)
**Stack 1 - Información Básica:**

1. **🔖 Código Cliente**
   - Campo: `client_code`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Ordenamiento: ✅ Habilitado
     - Estilo: Negrita, Color primario
     - Función: Copiable
     - Tooltip: "Código único del cliente"

2. **🏪 Nombre Comercial**
   - Campo: `business_name`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Ordenamiento: ✅ Habilitado
     - Estilo: Tamaño grande, Peso medio
     - Límite: 30 caracteres
     - Tooltip: Muestra texto completo si excede el límite

3. **🏷️ Tipo de Cliente**
   - Campo: `client_type`
   - Tipo: Badge
   - Valores y colores:
     - `clinic` → 🏥 Clínica (Verde - success)
     - `hospital` → 🏨 Hospital (Azul - info)
     - `private_company` → 🏢 Empresa Privada (Amarillo - warning)
     - `public_institution` → 🏛️ Institución Pública (Azul primario - primary)
     - `others` → 📋 Otros (Gris - gray)

### Columna Central
**Stack 2 - Información de Contacto:**

4. **📱 Teléfono Principal**
   - Campo: `main_phone`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Icono: `heroicon-o-phone`
     - Color: Verde (success)
     - Función: Copiable
     - Placeholder: "Sin teléfono"

5. **📧 Email Principal**
   - Campo: `main_email`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Icono: `heroicon-o-envelope`
     - Color: Azul (info)
     - Función: Copiable
     - Límite: 25 caracteres
     - Placeholder: "Sin email"

6. **🏭 Sector Empresarial**
   - Campo: `business_sector`
   - Tipo: Badge
   - Características:
     - Color: Gris
     - Placeholder: "Sin sector"

### Columna Derecha
**Stack 3 - Estado y Metadatos:**

7. **✅ Estado del Cliente**
   - Campo: `is_active`
   - Tipo: IconColumn (Boolean)
   - Características:
     - Icono activo: `heroicon-o-check-circle` (Verde)
     - Icono inactivo: `heroicon-o-x-circle` (Rojo)
     - Tooltip: "Cliente Activo" / "Cliente Inactivo"

8. **📅 Fecha de Creación**
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
1. **🏷️ Tipo de Cliente** (SelectFilter)
   - Múltiple selección
   - Precarga de opciones
   - Todas las categorías disponibles

2. **✅ Estado del Cliente** (TernaryFilter)
   - Todos los clientes
   - Solo activos
   - Solo inactivos

3. **🆕 Clientes Recientes** (Filter)
   - Últimos 30 días
   - Tipo toggle

### Acciones de Fila
**Grupo de Acciones (ActionGroup):**
- **👁️ Ver** - Color: info, Icono: `heroicon-o-eye`
- **✏️ Editar** - Color: warning, Icono: `heroicon-o-pencil-square`
- **🔄 Cambiar Estado** - Dinámico:
  - Activo → "Desactivar" (Rojo, `heroicon-o-x-circle`)
  - Inactivo → "Activar" (Verde, `heroicon-o-check-circle`)
  - Requiere confirmación

### Acciones Masivas (Bulk Actions)
1. **✅ Activar seleccionados** - Verde, requiere confirmación
2. **❌ Desactivar seleccionados** - Rojo, requiere confirmación  
3. **🗑️ Eliminar seleccionados** - Acción de eliminación estándar

### Configuraciones de Tabla
- **Ordenamiento por defecto:** `created_at DESC`
- **Paginación:** 10, 25, 50, 100 registros por página
- **Búsqueda global:** ✅ Habilitada
- **Persistencia:** Ordenamiento, búsqueda y filtros en sesión
- **Actualización automática:** Cada 30 segundos
- **Carga diferida:** ✅ Habilitada
- **Filas rayadas:** ✅ Habilitada

## 📱 Diseño Responsivo

- **Layout:** ContentGrid con configuración:
  - `md`: 1 columna
  - `lg`: 1 columna
- **Split responsivo:** A partir de `md` (medium breakpoint)
- **Espaciado entre stacks:** 1 unidad

## 🚫 Estado Vacío

- **Título:** "😅 No hay clientes registrados"
- **Descripción:** "Comience agregando su primer cliente al sistema."
- **Icono:** `heroicon-o-building-office`

## 📊 Datos de Ejemplo Visibles

La tabla actualmente muestra 9 clientes de ejemplo:

1. **HOS001** - Hospital Nacional Dos de Mayo (🏨 Hospital)
2. **EMP001** - Laboratorios ABC (🏢 Empresa Privada)
3. **INS001** - ESSALUD - Red Asistencial Lima (🏛️ Institución Pública)
4. **CLI002** - Centro Médico Miraflores (🏥 Clínica)
5. **OTH001** - Instituto de Investigación Médica (📋 Otros)
6. **HOS002** - Hospital Británico (🏨 Hospital)
7. **CLI003** - Clínica Cerrada (🏥 Clínica)
8. **CLI001** - Clínica San Juan (🏥 Clínica)
9. **SSS** - SS (🏨 Hospital) - Cliente de prueba incompleto

## 🔧 Características Técnicas

- **Modelo:** `App\Models\Client`
- **Recurso:** `App\Filament\Resources\ClientResource`
- **Navegación:** Grupo "📋 Gestión Comercial"
- **Icono de navegación:** `heroicon-o-building-office`
- **Acción por defecto:** Editar (click en fila)
- **URL de edición:** `/admin/clients/{id}/edit`