# Documentación de la Tabla de Cotizaciones

**URL:** `http://document-tracking.test/admin/quotes`  
**Fecha de implementación:** 27 de agosto de 2025  
**Recurso:** `QuoteResource.php`  
**Formato aplicado:** Basado en `tabla-clientes-formato.md`

## 📋 Información General

La tabla de cotizaciones utiliza un diseño responsivo con layout tipo **Split** que organiza la información en columnas apiladas (Stack), siguiendo el mismo patrón visual y funcional documentado para la tabla de clientes. Incluye funcionalidad de descarga en PDF.

## 🗂️ Estructura de Columnas

### Columna Principal (Izquierda)
**Stack 1 - Información Básica:**

1. **🔢 Número Cotización**
   - Campo: `quote_number`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada
     - Ordenamiento: ✅ Habilitado
     - Estilo: Negrita, Color primario
     - Función: Copiable
     - Tooltip: "Número único de la cotización"

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

3. **📋 Solicitud**
   - Campo: `request.request_number`
   - Tipo: TextColumn
   - Características:
     - Búsqueda: ✅ Habilitada (a través de relación)
     - Ordenamiento: ✅ Habilitado
     - Color: Azul (info)
     - Icono: `heroicon-o-clipboard-document-list`

### Columna Central
**Stack 2 - Detalles de Cotización:**

4. **🔄 Versión**
   - Campo: `version`
   - Tipo: Badge
   - Características:
     - Color: Azul primario (primary)

5. **💰 Total**
   - Campo: `total`
   - Tipo: TextColumn
   - Características:
     - Formato monetario: Soles (PEN)
     - Ordenamiento: ✅ Habilitado
     - Color: Verde (success)
     - Estilo: Negrita

6. **💱 Moneda**
   - Campo: `currency`
   - Tipo: Badge
   - Valores y colores:
     - `PEN` → Soles (PEN) (Verde - success)
     - `USD` → Dólares (USD) (Azul - info)
     - Otros → Gris (gray)

### Columna Derecha
**Stack 3 - Estado y Fechas:**

7. **📊 Estado del Proceso**
   - Campo: `state.name`
   - Tipo: Badge
   - Características:
     - Color: Azul primario (primary)
     - Icono: `heroicon-o-clipboard-document-check`

8. **📅 Fecha de Generación**
   - Campo: `generation_date`
   - Tipo: TextColumn
   - Características:
     - Formato: `d/m/Y` (27/08/2025)
     - Ordenamiento: ✅ Habilitado
     - Color: Gris
     - Tamaño: Pequeño
     - Icono: `heroicon-o-calendar-days`

9. **📅 Fecha de Vencimiento**
   - Campo: `expiration_date`
   - Tipo: TextColumn
   - Características:
     - Formato: `d/m/Y` (27/08/2025)
     - Ordenamiento: ✅ Habilitado
     - Color: Gris (normal) / Rojo (vencido)
     - Tamaño: Pequeño
     - Icono: `heroicon-o-clock`

## 🎛️ Funcionalidades de la Tabla

### Filtros Disponibles
1. **💱 Moneda** (SelectFilter)
   - Múltiple selección
   - Precarga de opciones
   - Soles (PEN) y Dólares (USD)

2. **💳 Método de Pago** (SelectFilter)
   - Múltiple selección
   - Precarga de opciones
   - Factoring, Pago Directo, Contado, Otros

3. **📊 Estado** (SelectFilter)
   - Múltiple selección
   - Precarga de opciones
   - Solo estados activos de tipo 'quote'

4. **🆕 Recientes** (Filter personalizado)
   - Últimos 7 días
   - Tipo toggle

5. **🔴 Vencidas** (Filter personalizado)
   - Cotizaciones con fecha de vencimiento pasada
   - Tipo toggle

### Acciones de Fila
**Grupo de Acciones (ActionGroup):**
- **👁️ Ver** - Color: info, Icono: `heroicon-o-eye`
- **✏️ Editar** - Color: warning, Icono: `heroicon-o-pencil-square`
- **📄 Descargar PDF** - Color: success, Icono: `heroicon-o-arrow-down-tray`
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

- **Título:** "📋 No hay cotizaciones registradas"
- **Descripción:** "Comience creando su primera cotización en el sistema."
- **Icono:** `heroicon-o-calculator`

## 🔧 Características Técnicas

- **Modelo:** `App\Models\Quote`
- **Recurso:** `App\Filament\Resources\QuoteResource`
- **Navegación:** Grupo "📋 Gestión Comercial"
- **Icono de navegación:** `heroicon-o-calculator`
- **URL de edición:** `/admin/quotes/{id}/edit`

## ⚙️ Imports y Configuración de Clases

### Imports Críticos (Filament 4)
```php
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\TableSplit;
use Filament\Tables\Columns\Layout\TableStack;
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
        Action::make('download_pdf')
            ->label('📄 Descargar PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->url(fn ($record) => route('quotes.download.pdf', $record))
            ->openUrlInNewTab(),
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

### Configuración de Columnas
```php
->columns([
    TableSplit::make([
        TableStack::make([
            Tables\Columns\TextColumn::make('quote_number')
                ->label('🔢 Número Cotización')
                ->searchable()
                ->sortable()
                ->weight('bold')
                ->color('primary')
                ->copyable()
                ->tooltip('Número único de la cotización'),

            Tables\Columns\TextColumn::make('request.client.business_name')
                ->label('🏥 Cliente')
                ->searchable()
                ->sortable()
                ->size('lg')
                ->weight('medium')
                ->limit(25)
                ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                    $state = $column->getState();
                    return strlen($state) > 25 ? $state : null;
                })
                ->placeholder('Sin cliente'),

            Tables\Columns\TextColumn::make('request.request_number')
                ->label('📋 Solicitud')
                ->searchable()
                ->sortable()
                ->color('info')
                ->icon('heroicon-o-clipboard-document-list'),
        ])->space(1),

        TableStack::make([
            Tables\Columns\TextColumn::make('version')
                ->label('🔄 Versión')
                ->badge()
                ->color('primary'),

            Tables\Columns\TextColumn::make('total')
                ->label('💰 Total')
                ->money('PEN')
                ->sortable()
                ->color('success')
                ->weight('bold'),

            Tables\Columns\TextColumn::make('currency')
                ->label('💱 Moneda')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'PEN' => 'success',
                    'USD' => 'info',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'PEN' => 'Soles (PEN)',
                    'USD' => 'Dólares (USD)',
                    default => $state,
                }),
        ])->space(1),

        TableStack::make([
            Tables\Columns\TextColumn::make('state.name')
                ->label('📊 Estado')
                ->badge()
                ->color('primary')
                ->icon('heroicon-o-clipboard-document-check'),

            Tables\Columns\TextColumn::make('generation_date')
                ->label('📅 Generación')
                ->dateTime('d/m/Y')
                ->sortable()
                ->color('gray')
                ->size('sm')
                ->icon('heroicon-o-calendar-days'),

            Tables\Columns\TextColumn::make('expiration_date')
                ->label('📅 Vencimiento')
                ->dateTime('d/m/Y')
                ->sortable()
                ->color(fn ($state) => $state && $state < now() ? 'danger' : 'gray')
                ->size('sm')
                ->icon('heroicon-o-clock'),
        ])->space(1),
    ])->from('md'),
])
```

## 📄 Funcionalidad de Descarga PDF

### Ruta de Descarga
- **URL:** `/quotes/{quote}/download-pdf`
- **Nombre de ruta:** `quotes.download.pdf`
- **Método:** GET
- **Controlador:** `App\Http\Controllers\QuoteController@downloadPdf`

### Lógica de Descarga
1. **Verificación de PDF adjunto:** Busca en la tabla `attachments` un archivo PDF relacionado con la cotización
2. **Descarga directa:** Si existe un PDF adjunto, lo descarga directamente
3. **Generación básica:** Si no existe, genera un PDF básico con la información de la cotización

### Controlador
```php
class QuoteController extends Controller
{
    public function downloadPdf(Quote $quote)
    {
        // Verificar si existe un archivo PDF adjunto
        $pdfAttachment = $quote->attachments()
            ->where('category', 'official')
            ->where('mime_type', 'application/pdf')
            ->first();

        if ($pdfAttachment) {
            // Si existe un PDF adjunto, devolverlo
            $filePath = storage_path('app/public/' . $pdfAttachment->file_path);
            
            if (file_exists($filePath)) {
                return response()->download($filePath, $pdfAttachment->original_name);
            }
        }

        // Si no existe un PDF adjunto, generar uno básico
        $fileName = 'cotizacion-' . $quote->quote_number . '.pdf';
        $content = "Cotización #" . $quote->quote_number . "\n\n";
        $content .= "Cliente: " . ($quote->request->client->business_name ?? 'N/A') . "\n";
        $content .= "Fecha de generación: " . $quote->generation_date->format('d/m/Y') . "\n";
        $content .= "Total: " . $quote->total . " " . $quote->currency . "\n";
        $content .= "Estado: " . ($quote->state->name ?? 'N/A') . "\n";
        
        $tempFile = tempnam(sys_get_temp_dir(), 'quote_');
        file_put_contents($tempFile, $content);
        
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
```

## 🎨 Consistencia Visual

### Emojis Descriptivos
- 🔢 Número Cotización
- 🏥 Cliente
- 📋 Solicitud
- 🔄 Versión
- 💰 Total
- 💱 Moneda
- 📊 Estado
- 📅 Fechas

### Colores Coherentes
- **Primary:** Número Cotización, Versión, Estado
- **Success:** Total, Soles
- **Info:** Cliente, Dólares
- **Warning:** Factoring
- **Danger:** Vencidas
- **Gray:** Fechas, Otros

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

La tabla mantiene total compatibilidad con el sistema existente mientras proporciona una experiencia visual mejorada y funcionalidades específicas para la gestión eficiente de cotizaciones.