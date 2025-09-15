<?php

namespace App\Filament\Resources;

use App\Models\Quote;
use App\Models\Request;
use App\Models\ProcessState;
use App\Filament\Resources\QuoteResource\Pages;
use BackedEnum;
use UnitEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction; 
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split as TableSplit;
use Filament\Tables\Columns\Layout\Stack as TableStack;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    /**
     * Método centralizado para calcular y actualizar montos desde el repeater
     */
    private static function calculateAndUpdateTotals(Set $set, Get $get, $component = null): void
    {
        Log::info('🔄 CÁLCULO CENTRALIZADO - Inicio', [
            'timestamp' => now(),
            'component_type' => $component ? get_class($component) : 'direct_call'
        ]);

        // Método 1: Intentar obtener datos del repeater padre
        $details = [];

        try {
            if ($component instanceof \Filament\Forms\Components\Repeater) {
                // Si el componente es el repeater directamente
                $rawDetails = $component->getState() ?? [];
            } else {
                // Si es un campo dentro del repeater, obtenemos el estado del repeater padre
                $repeaterComponent = $component->getContainer()->getParentComponent();
                if ($repeaterComponent instanceof \Filament\Forms\Components\Repeater) {
                    $rawDetails = $repeaterComponent->getState() ?? [];
                } else {
                    // Último recurso: buscar en el formulario completo
                    $allData = $get('../../') ?? [];
                    $rawDetails = $allData['details'] ?? [];
                }
            }
            Log::info('✅ Método 1 - REPEATER COMPONENT exitoso', [
                'raw_details_type' => gettype($rawDetails),
                'raw_details' => $rawDetails,
                'method' => 'repeater_component_state'
            ]);

            // Normalizar datos - asegurar que sea un array de servicios
            if (is_array($rawDetails) && !empty($rawDetails)) {
                // Verificar si es un array de servicios del repeater (cada elemento tiene quantity/unit_price)
                $isRepeaterServices = false;
                foreach ($rawDetails as $key => $service) {
                    if (is_array($service) && (isset($service['quantity']) || isset($service['unit_price']))) {
                        $isRepeaterServices = true;
                        break;
                    }
                }

                if ($isRepeaterServices) {
                    // Es un array de servicios del repeater, usar directamente
                    $details = array_values($rawDetails);
                } elseif (isset($rawDetails['quantity']) || isset($rawDetails['unit_price'])) {
                    // Es un solo servicio
                    $details = [$rawDetails];
                } else {
                    // Puede ser datos del formulario completo, buscar el campo details
                    if (isset($rawDetails['details'])) {
                        $repeaterData = $rawDetails['details'];
                        if (is_array($repeaterData)) {
                            $details = array_values($repeaterData);
                        }
                    } else {
                        $details = [];
                    }
                }
            } elseif (is_object($rawDetails)) {
                // Si es un objeto, convertirlo a array
                $rawDetailsArray = (array) $rawDetails;
                if (isset($rawDetailsArray['quantity']) || isset($rawDetailsArray['unit_price'])) {
                    $details = [$rawDetailsArray];
                } else {
                    $details = array_values($rawDetailsArray);
                }
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ Método 1 - REPEATER COMPONENT falló', [
                'error' => $e->getMessage()
            ]);
        }

        // Método 2: Si el método 1 falla, usar path directo
        if (empty($details)) {
            try {
                $rawDetails = $get('details') ?? [];
                Log::info('✅ Método 2 - PATH DIRECTO exitoso', [
                    'raw_details_type' => gettype($rawDetails),
                    'raw_details' => $rawDetails,
                    'method' => 'get_details_direct'
                ]);

                // Normalizar datos
                if (is_array($rawDetails) && !empty($rawDetails)) {
                    if (isset($rawDetails['quantity']) || isset($rawDetails['unit_price'])) {
                        $details = [$rawDetails];
                    } else {
                        $details = array_values($rawDetails);
                    }
                } elseif (is_object($rawDetails)) {
                    $rawDetailsArray = (array) $rawDetails;
                    if (isset($rawDetailsArray['quantity']) || isset($rawDetailsArray['unit_price'])) {
                        $details = [$rawDetailsArray];
                    } else {
                        $details = array_values($rawDetailsArray);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Método 2 - PATH DIRECTO falló', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Método 3: Si ambos fallan, usar el estado del formulario completo
        if (empty($details)) {
            try {
                $allData = $get('../../') ?? [];
                $rawDetails = $allData['details'] ?? [];
                Log::info('✅ Método 3 - FORMULARIO COMPLETO exitoso', [
                    'raw_details_type' => gettype($rawDetails),
                    'raw_details' => $rawDetails,
                    'method' => 'full_form_data'
                ]);

                // Normalizar datos
                if (is_array($rawDetails) && !empty($rawDetails)) {
                    if (isset($rawDetails['quantity']) || isset($rawDetails['unit_price'])) {
                        $details = [$rawDetails];
                    } else {
                        $details = array_values($rawDetails);
                    }
                } elseif (is_object($rawDetails)) {
                    $rawDetailsArray = (array) $rawDetails;
                    if (isset($rawDetailsArray['quantity']) || isset($rawDetailsArray['unit_price'])) {
                        $details = [$rawDetailsArray];
                    } else {
                        $details = array_values($rawDetailsArray);
                    }
                }
            } catch (\Exception $e) {
                Log::error('❌ Método 3 - FORMULARIO COMPLETO falló', [
                    'error' => $e->getMessage()
                ]);
                $details = [];
            }
        }

        Log::info('📋 SERVICIOS FINALES OBTENIDOS', [
            'total_servicios' => count($details),
            'servicios_data' => $details
        ]);

        $total = 0;
        foreach ($details as $index => $service) {
            // Asegurar que tenemos un array, no un objeto
            if (is_object($service)) {
                $service = (array) $service;
            }

            // Como ya no usamos cantidad, el precio unitario es el precio total del servicio
            $price = (float)($service['unit_price'] ?? 0);
            $total += $price; // Suma directa del precio total (ya incluye IGV)

            Log::info("📊 SERVICIO #{$index} CALCULADO", [
                'unit_price' => $price,
                'running_total' => $total
            ]);
        }

        Log::info('💰 TOTAL CALCULADO (incluye IGV)', [
            'total_before_format' => $total,
            'total_formatted' => number_format($total, 2, '.', '')
        ]);

        // Actualizar campos usando navegación hacia el formulario padre
        // Subtotal será igual al total ya que incluye IGV
        $set('../../subtotal', number_format($total, 2, '.', ''));

        // No calcular IGV adicional - el precio ya lo incluye
        $taxRate = (float)($get('../../tax_percentage') ?? 18);
        $tax = 0; // No hay IGV adicional que calcular
        $finalTotal = $total; // El total es el precio final

        Log::info('🧾 SIN CÁLCULO DE IMPUESTOS ADICIONALES', [
            'tax_rate' => $taxRate,
            'tax_amount' => $tax,
            'final_total' => $finalTotal
        ]);

        $set('../../tax_amount', number_format($tax, 2, '.', ''));
        $set('../../total', number_format($finalTotal, 2, '.', ''));

        Log::info('✅ CÁLCULO COMPLETADO - Precio total incluye IGV', [
            'subtotal_set' => number_format($total, 2, '.', ''),
            'tax_amount_set' => number_format($tax, 2, '.', ''),
            'total_set' => number_format($finalTotal, 2, '.', '')
        ]);
    }

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Cotizaciones';

    protected static ?string $modelLabel = 'Cotización';

    protected static ?string $pluralModelLabel = 'Cotizaciones';

    protected static UnitEnum|string|null $navigationGroup = '📋 Gestión Comercial';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('quote_tabs')
                    ->tabs([
                        Tab::make('📋 General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('quote_number')
                                                    ->label('🔢 Número de Cotización')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->placeholder('Se generará automáticamente')
                                                    ->helperText('Este número se asigna automáticamente al guardar')
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('version')
                                                    ->label('🔄 Versión')
                                                    ->options([
                                                        1 => '1 - Original',
                                                        2 => '2 - Primera revisión',
                                                        3 => '3 - Segunda revisión',
                                                        4 => '4 - Tercera revisión',
                                                        5 => '5 - Cuarta revisión',
                                                    ])
                                                    ->default(1)
                                                    ->required()
                                                    ->helperText('Versión (primera siempre es 1)')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('request_id')
                                                    ->label('📝 Solicitud')
                                                    ->relationship('request', 'request_number')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live() // Make it reactive
                                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                                        // Get the selected request ID
                                                        $requestId = $get('request_id');

                                                        if ($requestId) {
                                                            // Load the request model
                                                            $request = \App\Models\Request::find($requestId);

                                                            if ($request) {
                                                                // Set the destination_contact_id to the request's contact
                                                                if ($request->contact_id) {
                                                                    $set('destination_contact_id', $request->contact_id);
                                                                }

                                                                // Pre-cargar descripción del servicio en pestaña Servicio
                                                                if ($request->service_description) {
                                                                    $set('service_description', $request->service_description);
                                                                }

                                                                // Pre-cargar fecha de inicio (service_start_date) desde required_service_date
                                                                if ($request->required_service_date) {
                                                                    $formattedDate = Carbon::parse($request->required_service_date)->format('Y-m-d H:i:s');
                                                                    $set('service_start_date', $formattedDate);
                                                                }

                                                                // Calcular fecha de vencimiento (expiration_date) como request_date + 15 días
                                                                if ($request->request_date) {
                                                                    $expirationDate = Carbon::parse($request->request_date)->addDays(15);
                                                                    $set('expiration_date', $expirationDate->format('Y-m-d H:i:s'));
                                                                }

                                                                // Pre-cargar descripción del servicio en el primer elemento del repeater details
                                                                if ($request->service_description) {
                                                                    Log::info('🔄 PRECARGANDO DESCRIPCIÓN DEL SERVICIO', [
                                                                        'service_description' => $request->service_description
                                                                    ]);

                                                                    $currentDetails = $get('details') ?? [];

                                                                    // Siempre crear un nuevo servicio con la descripción de la solicitud
                                                                    // Esto asegura que se reemplace cualquier contenido existente
                                                                    Log::info('📝 Creando/reemplazando servicio con descripción precargada');
                                                                    $set('details', [[
                                                                        'item_description' => $request->service_description,
                                                                        'quantity' => 1,
                                                                        'unit_price' => 0,
                                                                        'unit_of_measure' => 'SERVICIO',
                                                                        'item_order' => 1,
                                                                        'item_notes' => $currentDetails[0]['item_notes'] ?? '' // Preservar notas si existen
                                                                    ]]);

                                                                    Log::info('✅ DESCRIPCIÓN PRECARGADA COMPLETADA', [
                                                                        'final_details' => $get('details')
                                                                    ]);
                                                                }
                                                            }
                                                        } else {
                                                            // Limpiar campos cuando no hay solicitud seleccionada
                                                            $set('destination_contact_id', null);
                                                            $set('service_description', null);
                                                            $set('service_start_date', null);
                                                            $set('expiration_date', null);
                                                            $set('details', []);
                                                        }
                                                    })
                                                    ->helperText('Solicitud asociada a esta cotización')
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('rfq_id')
                                                    ->label('|RFQ')
                                                    ->relationship('rfq', 'rfq_number')
                                                    ->searchable()
                                                    ->preload()
                                                    ->helperText('RFQ relacionado (opcional)')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('parent_quote_id')
                                                    ->label('🔁 Cotización Padre')
                                                    ->relationship('parentQuote', 'quote_number')
                                                    ->searchable()
                                                    ->preload()
                                                    ->helperText('Cotización anterior en caso de revisiones')
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('destination_contact_id')
                                                    ->label('👤 Contacto Destino')
                                                    ->options(function (Get $get) {
                                                        $requestId = $get('request_id');
                                                        
                                                        if (!$requestId) {
                                                            // If no request is selected, return an empty array or perhaps all active contacts
                                                            // Returning empty is safer to avoid overwhelming the user
                                                            return [];
                                                        }
                                                        
                                                        $request = \App\Models\Request::find($requestId);
                                                        
                                                        if (!$request || !$request->client_id) {
                                                            // If the request doesn't have a client, return empty
                                                            return [];
                                                        }
                                                        
                                                        // Return contacts for the request's client
                                                        return \App\Models\ClientContact::where('client_id', $request->client_id)
                                                            ->where('is_active', true)
                                                            ->pluck('full_name', 'id');
                                                    })
                                                    ->live() // Make it reactive to changes in request_id
                                                    ->searchable()
                                                    ->preload()
                                                    ->hidden() // <<-- Hide the field from the UI
                                                    ->helperText('Persona de contacto para esta cotización')
                                                    ->columnSpan(1),
                                            ]),
                                            
                                        // Informative section for the selected request's contact details
                                        Section::make('Información del Contacto de la Solicitud')
                                            ->description('Detalles del contacto asociado a la solicitud seleccionada')
                                            ->icon('heroicon-o-user')
                                            ->schema([
                                                Forms\Components\Placeholder::make('contact_details')
                                                    ->label('Contacto de la Solicitud')
                                                    ->content(function (Get $get) {
                                                        $requestId = $get('request_id');
                                                        
                                                        if (!$requestId) {
                                                            return 'Por favor, seleccione una solicitud para ver los detalles del contacto.';
                                                        }
                                                        
                                                        $request = \App\Models\Request::with('contact')->find($requestId);
                                                        
                                                        if (!$request || !$request->contact) {
                                                            return 'La solicitud seleccionada no tiene un contacto asociado.';
                                                        }
                                                        
                                                        $contact = $request->contact;
                                                        
                                                        return view('filament.forms.contact-details', [
                                                            'name' => $contact->full_name,
                                                            'position' => $contact->position ?? 'N/A',
                                                            'phone' => $contact->phone ?? 'N/A',
                                                            'email' => $contact->email ?? 'N/A',
                                                        ]);
                                                    })
                                            ])
                                            ->collapsible()
                                            ->collapsed() // Start collapsed
                                            ->visible(fn (Get $get) => $get('request_id')) // Only visible if a request is selected
                                    ])
                                    ->columnSpan('full'),
                            ]),

                        Tab::make('📝 Servicio')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Servicio')
                                    ->description('Describe el servicio')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\Textarea::make('service_description')
                                            ->label('📋 Descripción')
                                            ->required()
                                            ->rows(4)
                                            ->placeholder('Describe el servicio...')
                                            ->helperText('Descripción clara del servicio')
                                            ->afterStateHydrated(function (Textarea $component, $state, $record, Get $get) {
                                                $requestId = $get('request_id');
                                                if ($requestId && !$state) {
                                                    $request = \App\Models\Request::find($requestId);
                                                    if ($request && $request->service_description) {
                                                        $component->state($request->service_description);
                                                    }
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Section::make('Direcciones')
                                            ->description('Direcciones de recojo/entrega')
                                            ->icon('heroicon-o-map-pin')
                                            ->schema([
                                                Forms\Components\Textarea::make('pickup_address')
                                                    ->label('📍 Dirección Recojo')
                                                    ->required()
                                                    ->rows(3)
                                                    ->placeholder('Dirección completa de recojo...')
                                                    ->helperText('Dirección donde se realizará el servicio')
                                                    ->columnSpanFull(),

                                                Forms\Components\Textarea::make('delivery_address')
                                                    ->label('📦 Dirección Entrega')
                                                    ->required()
                                                    ->rows(3)
                                                    ->placeholder('Dirección completa de entrega...')
                                                    ->helperText('Dirección donde se entregará el servicio')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2),
                                    ]),
                            ]),

                        Tab::make('📅 Fechas')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Section::make('Fechas de Servicio')
                                    ->description('Planificación temporal')
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('service_start_date')
                                                    ->label('🏁 Inicio')
                                                    ->required()
                                                    ->helperText('Fecha y hora de inicio')
                                                    ->live()
                                                    ->afterStateHydrated(function (DateTimePicker $component, $state, $record, Get $get) {
                                                        $requestId = $get('request_id');
                                                        if ($requestId && !$state) {
                                                            $request = \App\Models\Request::find($requestId);
                                                            if ($request && $request->required_service_date) {
                                                                $component->state(Carbon::parse($request->required_service_date)->format('Y-m-d H:i:s'));
                                                            }
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                Forms\Components\DateTimePicker::make('service_end_date')
                                                    ->label('🏁 Fin')
                                                    ->helperText('Fecha y hora de finalización')
                                                    ->columnSpan(1),

                                                Forms\Components\DateTimePicker::make('generation_date')
                                                    ->label('🖨️ Generación')
                                                    ->default(now())
                                                    ->required()
                                                    ->helperText('Fecha de creación de la cotización')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('sent_date')
                                                    ->label('📤 Fecha de Envío')
                                                    ->helperText('Cuándo se envió la cotización al cliente')
                                                    ->columnSpan(1),

                                                Forms\Components\DateTimePicker::make('response_date')
                                                    ->label('📥 Respuesta')
                                                    ->helperText('Respuesta del cliente')
                                                    ->columnSpan(1),

                                                Forms\Components\DateTimePicker::make('expiration_date')
                                                    ->label('⏰ Vencimiento')
                                                    ->helperText('Fecha límite de aceptación')
                                                    ->live()
                                                    ->afterStateHydrated(function (DateTimePicker $component, $state, $record, Get $get) {
                                                        $requestId = $get('request_id');
                                                        if ($requestId && !$state) {
                                                            $request = \App\Models\Request::find($requestId);
                                                            if ($request && $request->request_date) {
                                                                $expirationDate = Carbon::parse($request->request_date)->addDays(15);
                                                                $component->state($expirationDate->format('Y-m-d H:i:s'));
                                                            }
                                                        }
                                                    })
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('🧮 Servicios')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Section::make('Servicios')
                                    ->description('Agrega los servicios')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->schema([
                                        Forms\Components\Repeater::make('details')
                                            ->label('🧮 Servicios')
                                            ->relationship('details') // Especificar explícitamente el nombre de la relación
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state, $component) {
                                                Log::info('🔄 REPEATER UPDATED - Cambio detectado en servicios', [
                                                    'total_servicios' => count($state ?? []),
                                                    'timestamp' => now()
                                                ]);

                                                // Llamar al método centralizado de cálculo
                                                self::calculateAndUpdateTotals($set, $get, $component);
                                            })
                                            ->schema([
                                                // Información del Servicio
                                                Section::make('Información del Servicio')
                                                    ->description('Detalles básicos del servicio')
                                                    ->icon('heroicon-o-document-text')
                                                    ->schema([
                                                        Forms\Components\Textarea::make('item_description')
                                                            ->label('📝 Servicio')
                                                            ->required()
                                                            ->rows(3)
                                                            ->placeholder('Describe el servicio que se va a realizar...')
                                                            ->columnSpanFull()
                                                            ->live(onBlur: true),

                                                        // Campo oculto para cantidad (siempre será 1)
                                                        Forms\Components\TextInput::make('quantity')
                                                            ->label('Cantidad')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->dehydrated(false) // No se guarda en BD
                                                            ->hidden(), // No se muestra en UI

                                                        // Campo oculto para orden
                                                        Forms\Components\TextInput::make('item_order')
                                                            ->label('Orden')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->dehydrated(false) // No se guarda en BD
                                                            ->hidden(), // No se muestra en UI

                                                        // Campo oculto para unidad (siempre será SERVICIO)
                                                        Forms\Components\Hidden::make('unit_of_measure')
                                                            ->default('SERVICIO'),
                                                    ]),

                                                // Información del Precio y Pago
                                                Section::make('Información del Precio y Pago')
                                                    ->description('Precio total y condiciones de pago')
                                                    ->icon('heroicon-o-currency-dollar')
                                                    ->schema([
                                                        Grid::make(2)
                                                            ->schema([
                                                                Forms\Components\TextInput::make('unit_price')
                                                                    ->label('💰 Precio Total')
                                                                    ->numeric()
                                                                    ->prefix('S/')
                                                                    ->step(0.01)
                                                                    ->required()
                                                                    ->placeholder('Ingrese el precio total del servicio')
                                                                    ->helperText('Monto total del servicio (incluye IGV)')
                                                                    ->columnSpan(1)
                                                                    ->live(onBlur: true)
                                                                    ->afterStateUpdated(function (Set $set, Get $get, $state, $component) {
                                                                        Log::info('💰 UNIT_PRICE UPDATED - Inicio del cálculo', [
                                                                            'unit_price_updated' => $state,
                                                                            'timestamp' => now()
                                                                        ]);

                                                                        // Llamar al método centralizado de cálculo
                                                                        self::calculateAndUpdateTotals($set, $get, $component);
                                                                    }),

                                                                Forms\Components\Select::make('currency')
                                                                    ->label('💱 Moneda')
                                                                    ->options([
                                                                        'PEN' => 'Soles (PEN)',
                                                                        'USD' => 'Dólares (USD)',
                                                                    ])
                                                                    ->default('PEN')
                                                                    ->required()
                                                                    ->helperText('Moneda utilizada en la cotización')
                                                                    ->columnSpan(1),
                                                            ]),

                                                        Forms\Components\Select::make('proposed_payment_method')
                                                            ->label('💳 Método de Pago')
                                                            ->options([
                                                                'factoring' => 'Factoring',
                                                                'direct_payment' => 'Pago Directo',
                                                                'cash' => 'Contado',
                                                                'others' => 'Otros',
                                                            ])
                                                            ->default('factoring')
                                                            ->required()
                                                            ->helperText('Forma de pago propuesta')
                                                            ->columnSpanFull(),
                                                    ]),

                                                // Notas adicionales (opcional)
                                                Section::make('Información Adicional')
                                                    ->description('Notas adicionales del servicio')
                                                    ->icon('heroicon-o-chat-bubble-left-right')
                                                    ->collapsible()
                                                    ->collapsed()
                                                    ->schema([
                                                        Forms\Components\Textarea::make('item_notes')
                                                            ->label('📋 Notas del Servicio')
                                                            ->rows(2)
                                                            ->placeholder('Notas adicionales para este servicio...')
                                                            ->columnSpanFull(),
                                                    ]),
                                            ])
                                            ->orderColumn('item_order')
                                            ->reorderable()
                                            ->collapsible()
                                            ->cloneable()
                                            ->addActionLabel('➕ Agregar')
                                            ->defaultItems(0)
                                            ->minItems(1)
                                            ->maxItems(20)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('💰 Resumen Financiero')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Resumen de la Cotización')
                                    ->description('Vista general de montos calculados')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('subtotal')
                                                    ->label('🧮 Subtotal Calculado')
                                                    ->numeric()
                                                    ->prefix('S/')
                                                    ->required()
                                                    ->helperText('Subtotal de todos los servicios')
                                                    ->columnSpan(1)
                                                    ->default(0)
                                                    ->readOnly()
                                                    ->live(),

                                                Forms\Components\TextInput::make('tax_percentage')
                                                    ->label('📈 IGV Aplicado (%)')
                                                    ->numeric()
                                                    ->default(18.00)
                                                    ->suffix('%')
                                                    ->required()
                                                    ->helperText('Porcentaje de IGV incluido en precios')
                                                    ->columnSpan(1)
                                                    ->readOnly()
                                                    ->dehydrated(true), // Mantener el valor pero no permitir edición

                                                Forms\Components\TextInput::make('tax_amount')
                                                    ->label('🧾 IGV Incluido')
                                                    ->numeric()
                                                    ->prefix('S/')
                                                    ->required()
                                                    ->helperText('IGV ya incluido en los precios')
                                                    ->columnSpan(1)
                                                    ->default(0)
                                                    ->readOnly(),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('total')
                                                    ->label('💰 Total General')
                                                    ->numeric()
                                                    ->prefix('S/')
                                                    ->required()
                                                    ->helperText('Monto total de la cotización')
                                                    ->columnSpan(1)
                                                    ->default(0)
                                                    ->readOnly(),

                                                Section::make('Información Adicional')
                                                    ->description('Detalles de pago y moneda')
                                                    ->icon('heroicon-o-information-circle')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('payment_info')
                                                            ->label('Información de Pago')
                                                            ->content(function (Get $get) {
                                                                $currency = $get('currency') ?: 'PEN';
                                                                $paymentMethod = $get('proposed_payment_method') ?: 'factoring';

                                                                $currencyText = $currency === 'PEN' ? 'Soles (PEN)' : 'Dólares (USD)';
                                                                $paymentText = match($paymentMethod) {
                                                                    'factoring' => 'Factoring',
                                                                    'direct_payment' => 'Pago Directo',
                                                                    'cash' => 'Contado',
                                                                    'others' => 'Otros',
                                                                    default => 'No especificado'
                                                                };

                                                                return "Moneda: {$currencyText}\nMétodo de Pago: {$paymentText}";
                                                            })
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columnSpan(1)
                                                    ->collapsed(false),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('📎 Docs')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Section::make('Archivos Adjuntos')
                                    ->description('Sube documentos relacionados')
                                    ->icon('heroicon-o-document-arrow-up')
                                    ->schema([
                                        Forms\Components\FileUpload::make('attachment_files')
                                            ->label('📄 Documentos')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                            ->disk('public')
                                            ->directory('quote-attachments')
                                            ->visibility('public')
                                            ->panelLayout('grid')
                                            ->maxFiles(10)
                                            ->maxSize(15360) // 15MB
                                            ->helperText('PDF, PNG, JPG, DOC, DOCX - máx. 10 archivos de 15MB')
                                            ->hint('Documentos seguros y disponibles')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->columnSpanFull()
                                            ->storeFiles(false), // No almacenar automáticamente
                                    ]),
                            ]),

                        Tab::make('🚚 Transporte')
                            ->icon('heroicon-o-truck')
                            ->schema([
                                Section::make('Transporte')
                                    ->description('Vehículo y conductor')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Fieldset::make('Conductor y Vehículo')
                                            ->relationship('transport')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('driver_name')
                                                            ->label('👤 Nombre del Conductor')
                                                            ->maxLength(255)
                                                            ->placeholder('Nombre completo del conductor')
                                                            ->columnSpan(1),

                                                        Forms\Components\TextInput::make('driver_license')
                                                            ->label('🪪 Licencia de Conducir')
                                                            ->maxLength(50)
                                                            ->placeholder('Número de licencia')
                                                            ->columnSpan(1),
                                                    ]),

                                                Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('vehicle_plate')
                                                            ->label('🚗 Placa del Vehículo')
                                                            ->maxLength(20)
                                                            ->placeholder('ABC-123')
                                                            ->columnSpan(1),

                                                        Forms\Components\TextInput::make('vehicle_model')
                                                            ->label('🏗️ Modelo del Vehículo')
                                                            ->maxLength(100)
                                                            ->placeholder('Marca y modelo')
                                                            ->columnSpan(1),

                                                        Forms\Components\TextInput::make('vehicle_capacity')
                                                            ->label('📦 Capacidad')
                                                            ->maxLength(50)
                                                            ->placeholder('Ej: 5 toneladas, 10 m³')
                                                            ->columnSpan(1),
                                                    ]),

                                                Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\Toggle::make('includes_insurance')
                                                            ->label('🛡️ Incluye Seguro')
                                                            ->default(false)
                                                            ->helperText('Marcar si el servicio incluye cobertura de seguro')
                                                            ->columnSpan(1),

                                                        Forms\Components\Textarea::make('transport_notes')
                                                            ->label('📝 Notas de Transporte')
                                                            ->rows(3)
                                                            ->placeholder('Información adicional...')
                                                            ->columnSpan(1),
                                                    ]),
                                            ])
                                            ->columns(2),
                                    ]),
                            ]),

                        Tab::make('📊 Estado y Notas')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Estado')
                                    ->description('Estado y comentarios')
                                    ->icon('heroicon-o-clipboard-document-check')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('state_id')
                                                    ->label('📊 Estado')
                                                    ->relationship('state', 'name', function ($query) {
                                                        $query->where('entity', 'quote')
                                                            ->where('is_active', true);
                                                    })
                                                    ->default(function () {
                                                        return ProcessState::where('entity', 'quote')
                                                            ->where('is_initial_state', true)
                                                            ->first()?->id;
                                                    })
                                                    ->required()
                                                    ->helperText('Estado actual del proceso de la cotización')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('template_used')
                                                    ->label('📑 Template Utilizado')
                                                    ->default('standard')
                                                    ->required()
                                                    ->helperText('Plantilla usada para generar la cotización')
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('📝 Notas Adicionales')
                                            ->rows(4)
                                            ->placeholder('Observaciones adicionales...')
                                            ->helperText('Información adicional útil')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
            ->contentGrid([
                'md' => 1,
                'lg' => 1,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('💱 Moneda')
                    ->options([
                        'PEN' => 'Soles (PEN)',
                        'USD' => 'Dólares (USD)',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('proposed_payment_method')
                    ->label('💳 Método de Pago')
                    ->options([
                        'factoring' => 'Factoring',
                        'direct_payment' => 'Pago Directo',
                        'cash' => 'Contado',
                        'others' => 'Otros',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('📊 Estado')
                    ->relationship('state', 'name', function ($query) {
                        $query->where('entity', 'quote')
                            ->where('is_active', true);
                    })
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (7 días)')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('expired')
                    ->label('🔴 Vencidas')
                    ->query(fn ($query) => $query->where('expiration_date', '<', now()))
                    ->toggle(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                ActionGroup::make([
                    Action::make('downloadPdf')
                        ->label('Descargar PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Quote $record) {
                            return redirect()->route('quotes.download.pdf', ['quote' => $record]);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Descargar PDF')
                        ->modalDescription('¿Está seguro de que desea generar y descargar el PDF de esta cotización?')
                        ->modalSubmitActionLabel('Descargar'),
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
                ->button(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->poll('30s')
            ->deferLoading()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('📋 No hay cotizaciones registradas')
            ->emptyStateDescription('Comience creando su primera cotización en el sistema.')
            ->emptyStateIcon('heroicon-o-calculator');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view' => Pages\ViewQuote::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }
}