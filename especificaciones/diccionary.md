# Sistema de Trackeo de Documentos - Diccionario de Datos

## Resumen de Tablas

| Nombre Tabla | Propósito | Relaciones Clave |
|-------------|-----------|-------------------|
| system_configurations | Configuraciones y parámetros del sistema | - |
| process_states | Estados configurables para cada entidad | - |
| clients | Tabla maestra de clientes/clínicas | → client_contacts |
| client_contacts | Personas de contacto de cada cliente | clients ← |
| requests | Solicitudes iniciales de servicio (punto de entrada) | clients, client_contacts, process_states |
| rfqs | Documentos RFQ formales de clientes | requests ← |
| quotes | Cotizaciones generadas (guardado automático) | requests, rfqs, client_contacts |
| quote_details | Detalle de items de cotizaciones | quotes ← |
| quote_transport | Información de transporte de cotizaciones | quotes ← |
| purchase_orders | Órdenes de compra de clientes | quotes ← |
| executed_services | Servicios que han sido ejecutados | purchase_orders ← |
| hes | Hojas de Entrada de Servicio (crítico para facturación) | executed_services ← |
| invoices | Facturas generadas (requiere HES) | hes ← |
| payments | Registros de pagos y conciliación | invoices ← |
| attachments | Documentos adjuntos (polimórfico) | Todas las tablas |
| audit_log | Registro de auditoría del sistema | Todas las tablas |

---

## Diccionario Detallado de Campos

### system_configurations (Configuraciones del Sistema)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| key | VARCHAR(100) | SÍ | Clave de configuración (única) |
| value | TEXT | NO | Valor de la configuración |
| type | ENUM | SÍ | Tipo de dato (string, integer, decimal, boolean, json) |
| description | TEXT | NO | Descripción de la configuración |
| is_editable | BOOLEAN | SÍ | Si la configuración puede ser editada |
| group_name | VARCHAR(50) | SÍ | Grupo de configuración |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |

### process_states (Estados de Proceso)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| entity | VARCHAR(50) | SÍ | Nombre de entidad (request, quote, invoice, etc.) |
| code | VARCHAR(50) | SÍ | Código de estado (único por entidad) |
| name | VARCHAR(100) | SÍ | Nombre para mostrar |
| description | TEXT | NO | Descripción del estado |
| color | VARCHAR(7) | NO | Color hexadecimal para la interfaz |
| display_order | INT | SÍ | Orden para mostrar |
| is_initial_state | BOOLEAN | SÍ | Si es el estado inicial |
| is_final_state | BOOLEAN | SÍ | Si es el estado final |
| is_active | BOOLEAN | SÍ | Si el estado está activo |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |

### clients (Clientes)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| client_code | VARCHAR(20) | SÍ | Código único del cliente |
| business_name | VARCHAR(255) | SÍ | Nombre comercial |
| legal_name | VARCHAR(255) | SÍ | Razón social |
| client_type | ENUM | SÍ | Tipo (clinic, hospital, private_company, public_institution, others) |
| tax_id | VARCHAR(20) | SÍ | Número de RUC |
| fiscal_address | TEXT | NO | Dirección fiscal registrada |
| main_phone | VARCHAR(50) | NO | Teléfono principal |
| main_email | VARCHAR(255) | NO | Email principal |
| business_sector | VARCHAR(100) | NO | Sector empresarial |
| notes | TEXT | NO | Notas adicionales |
| is_active | BOOLEAN | SÍ | Si el cliente está activo |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### client_contacts (Contactos de Clientes)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| client_id | BIGINT UNSIGNED | SÍ | Referencia a tabla clients |
| full_name | VARCHAR(255) | SÍ | Nombre completo de la persona |
| position | VARCHAR(150) | NO | Cargo |
| department | VARCHAR(150) | NO | Departamento |
| phone | VARCHAR(50) | NO | Número de teléfono |
| email | VARCHAR(255) | NO | Dirección de email |
| is_primary_contact | BOOLEAN | SÍ | Si es el contacto principal |
| is_active | BOOLEAN | SÍ | Si el contacto está activo |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### requests (Solicitudes)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| request_number | VARCHAR(20) | SÍ | Número único de solicitud auto-generado |
| client_id | BIGINT UNSIGNED | SÍ | Referencia a tabla clients |
| contact_id | BIGINT UNSIGNED | NO | Referencia a tabla client_contacts |
| requesting_department | VARCHAR(150) | NO | Departamento que hace la solicitud |
| service_description | TEXT | SÍ | Descripción del servicio solicitado |
| request_date | DATETIME | SÍ | Fecha de la solicitud |
| required_service_date | DATETIME | NO | Fecha requerida para el servicio |
| urgency | ENUM | SÍ | Nivel de prioridad (normal, urgent) |
| origin | ENUM | SÍ | Origen de la solicitud (whatsapp, email, phone, in_person, system) |
| state_id | BIGINT UNSIGNED | SÍ | Estado actual del proceso |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| notes | TEXT | NO | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### rfqs (RFQs - Solicitudes de Cotización)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| rfq_number | VARCHAR(50) | SÍ | Número de RFQ del cliente |
| request_id | BIGINT UNSIGNED | SÍ | Referencia a tabla requests |
| received_date | DATETIME | SÍ | Fecha de recepción del RFQ |
| quote_deadline | DATETIME | SÍ | Fecha límite para cotizar |
| detailed_description | TEXT | NO | Descripción detallada del servicio |
| state_id | BIGINT UNSIGNED | SÍ | Estado actual del proceso |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| notes | TEXT | NO | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### quotes (Cotizaciones)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| quote_number | VARCHAR(20) | SÍ | Número de cotización auto-generado |
| request_id | BIGINT UNSIGNED | SÍ | Referencia a tabla requests |
| rfq_id | BIGINT UNSIGNED | NO | Referencia a tabla rfqs |
| destination_contact_id | BIGINT UNSIGNED | NO | Contacto al que se envía la cotización |
| service_description | TEXT | SÍ | Descripción del servicio |
| pickup_address | TEXT | SÍ | Dirección de recojo |
| delivery_address | TEXT | SÍ | Dirección de entrega |
| service_start_date | DATETIME | SÍ | Fecha planificada de inicio |
| service_end_date | DATETIME | NO | Fecha planificada de fin |
| subtotal | DECIMAL(12,2) | SÍ | Monto subtotal |
| tax_percentage | DECIMAL(5,2) | SÍ | Porcentaje de impuestos (defecto 18%) |
| tax_amount | DECIMAL(12,2) | SÍ | Monto de impuestos calculado |
| total | DECIMAL(12,2) | SÍ | Monto total |
| currency | ENUM | SÍ | Moneda (PEN, USD) |
| proposed_payment_method | ENUM | SÍ | Método de pago propuesto |
| version | INT | SÍ | Número de versión de cotización |
| parent_quote_id | BIGINT UNSIGNED | NO | Referencia a cotización padre para versiones |
| generation_date | DATETIME | SÍ | Fecha de generación de la cotización |
| sent_date | DATETIME | NO | Fecha de envío |
| response_date | DATETIME | NO | Fecha de respuesta recibida |
| expiration_date | DATETIME | NO | Fecha de vencimiento |
| state_id | BIGINT UNSIGNED | SÍ | Estado actual del proceso |
| template_used | VARCHAR(100) | SÍ | Template usado para generación |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| notes | TEXT | NO | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### quote_details (Detalles de Cotización)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| quote_id | BIGINT UNSIGNED | SÍ | Referencia a tabla quotes |
| item_order | INT | SÍ | Orden del ítem en la cotización |
| quantity | INT | SÍ | Cantidad del ítem |
| unit_of_measure | VARCHAR(20) | SÍ | Unidad de medida (defecto: UNIT) |
| item_description | VARCHAR(255) | SÍ | Descripción del ítem |
| unit_price | DECIMAL(10,2) | NO | Precio por unidad |
| item_subtotal | DECIMAL(10,2) | NO | Subtotal para este ítem |
| item_notes | TEXT | NO | Notas para este ítem |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |

### quote_transport (Transporte de Cotización)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| quote_id | BIGINT UNSIGNED | SÍ | Referencia a tabla quotes |
| driver_name | VARCHAR(255) | NO | Nombre del conductor |
| driver_license | VARCHAR(50) | NO | Número de licencia del conductor |
| vehicle_plate | VARCHAR(20) | NO | Placa del vehículo |
| vehicle_model | VARCHAR(100) | NO | Modelo del vehículo |
| vehicle_capacity | VARCHAR(50) | NO | Capacidad del vehículo |
| includes_insurance | BOOLEAN | SÍ | Si incluye seguro |
| transport_notes | TEXT | NO | Notas adicionales de transporte |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |

### purchase_orders (Órdenes de Compra)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| po_number | VARCHAR(50) | SÍ | Número de orden de compra del cliente |
| solped_number | VARCHAR(50) | NO | Número de SOLPED del cliente |
| quote_id | BIGINT UNSIGNED | SÍ | Referencia a tabla quotes |
| issue_date | DATETIME | SÍ | Fecha de emisión de la OC |
| validity_date | DATETIME | NO | Fecha de vigencia de la OC |
| scheduled_execution_date | DATETIME | NO | Fecha programada del servicio |
| assigned_technician | VARCHAR(255) | NO | Técnico asignado |
| reception_area | VARCHAR(255) | NO | Área de recepción en el cliente |
| authorized_amount | DECIMAL(12,2) | SÍ | Monto autorizado |
| currency | ENUM | SÍ | Moneda (PEN, USD) |
| special_conditions | TEXT | NO | Condiciones especiales |
| is_urgent | BOOLEAN | SÍ | Si el servicio es urgente |
| state_id | BIGINT UNSIGNED | SÍ | Estado actual del proceso |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| notes | TEXT | NO | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### executed_services (Servicios Ejecutados)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| purchase_order_id | BIGINT UNSIGNED | SÍ | Referencia a tabla purchase_orders |
| start_date | DATETIME | SÍ | Fecha/hora de inicio del servicio |
| end_date | DATETIME | NO | Fecha/hora de fin del servicio |
| main_technician | VARCHAR(255) | SÍ | Nombre del técnico principal |
| work_team | JSON | NO | Miembros adicionales del equipo (arreglo JSON) |
| work_description | TEXT | NO | Descripción del trabajo realizado |
| incidents | TEXT | NO | Incidencias durante el servicio |
| execution_hours | DECIMAL(5,2) | NO | Horas totales de ejecución |
| state_id | BIGINT UNSIGNED | SÍ | Estado actual del proceso |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| notes | TEXT | NO | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### hes (Hojas de Entrada de Servicio)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| hes_number | VARCHAR(50) | SÍ | Número de HES del cliente |
| executed_service_id | BIGINT UNSIGNED | SÍ | Referencia a tabla executed_services |
| reference_po_number | VARCHAR(50) | SÍ | Número de OC de referencia (debe coincidir) |
| reference_solped_number | VARCHAR(50) | NO | Número de SOLPED de referencia |
| approval_date | DATETIME | SÍ | Fecha de aprobación del HES |
| approver_name | VARCHAR(255) | SÍ | Nombre del aprobador |
| approver_position | VARCHAR(150) | NO | Cargo del aprobador |
| authorized_amount | DECIMAL(12,2) | SÍ | Monto autorizado para facturar |
| currency | ENUM | SÍ | Moneda (PEN, USD) |
| payment_method | ENUM | SÍ | Método de pago (factoring, direct_payment, cash, others) |
| payment_days | INT | NO | Término de pago en días |
| discount_percentage | DECIMAL(5,2) | NO | Porcentaje de descuento para factoring |
| imputation_type | VARCHAR(100) | NO | Tipo de imputación |
| state_id | BIGINT UNSIGNED | SÍ | Estado actual del proceso |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| notes | TEXT | NO | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### invoices (Facturas)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| invoice_number | VARCHAR(50) | SÍ | Número de factura |
| hes_id | BIGINT UNSIGNED | SÍ | Referencia a tabla hes (REQUERIDO PARA FACTURAR) |
| issue_date | DATETIME | SÍ | Fecha de emisión de factura |
| sent_to_client_date | DATETIME | NO | Fecha de envío al cliente |
| due_date | DATETIME | NO | Fecha de vencimiento |
| subtotal | DECIMAL(12,2) | SÍ | Monto subtotal |
| tax_amount | DECIMAL(12,2) | SÍ | Monto de impuestos |
| total | DECIMAL(12,2) | SÍ | Monto total de la factura |
| currency | ENUM | SÍ | Moneda (PEN, USD) |
| accounting_area | VARCHAR(100) | NO | Área de contabilidad |
| state_id | BIGINT UNSIGNED | SÍ | Estado actual del proceso |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| notes | TEXT | NO | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### payments (Pagos)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| invoice_id | BIGINT UNSIGNED | SÍ | Referencia a tabla invoices |
| payment_date | DATETIME | SÍ | Fecha de recepción del pago |
| invoiced_amount | DECIMAL(12,2) | SÍ | Monto original de la factura |
| discount_amount | DECIMAL(12,2) | SÍ | Monto de descuento aplicado |
| discount_percentage | DECIMAL(5,2) | SÍ | Porcentaje de descuento aplicado |
| net_received_amount | DECIMAL(12,2) | SÍ | Monto neto recibido |
| currency | ENUM | SÍ | Moneda (PEN, USD) |
| payment_method | VARCHAR(100) | NO | Método de pago |
| bank_reference | VARCHAR(255) | NO | Número de referencia bancaria |
| origin_bank | VARCHAR(100) | NO | Banco originador |
| delay_days | INT | NO | Días de atraso en el pago |
| state_id | BIGINT UNSIGNED | SÍ | Estado actual del proceso |
| created_by | BIGINT UNSIGNED | SÍ | Usuario que creó el registro |
| updated_by | BIGINT UNSIGNED | NO | Usuario que actualizó por última vez |
| notes | TEXT | NO | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### attachments (Adjuntos)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| attachable_type | VARCHAR(255) | SÍ | Nombre de clase del modelo (polimórfico) |
| attachable_id | BIGINT UNSIGNED | SÍ | ID del registro relacionado |
| file_name | VARCHAR(255) | SÍ | Nombre de archivo generado |
| original_name | VARCHAR(255) | SÍ | Nombre original del archivo subido |
| file_path | VARCHAR(500) | SÍ | Ruta de almacenamiento del archivo |
| mime_type | VARCHAR(100) | SÍ | Tipo MIME del archivo |
| size_bytes | BIGINT | SÍ | Tamaño del archivo en bytes |
| file_hash | VARCHAR(64) | NO | Hash del archivo para detectar duplicados |
| is_public | BOOLEAN | SÍ | Si el archivo es públicamente accesible |
| category | ENUM | SÍ | Categoría del archivo (evidence, official, backup, generated) |
| uploaded_by | BIGINT UNSIGNED | SÍ | Usuario que subió el archivo |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de última actualización |
| deleted_at | TIMESTAMP | NO | Fecha de eliminación suave |

### audit_log (Registro de Auditoría)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | BIGINT UNSIGNED | SÍ | Clave primaria |
| table_name | VARCHAR(100) | SÍ | Nombre de la tabla afectada |
| record_id | BIGINT UNSIGNED | SÍ | ID del registro afectado |
| action | ENUM | SÍ | Acción realizada (INSERT, UPDATE, DELETE) |
| old_values | JSON | NO | Valores anteriores (para UPDATE/DELETE) |
| new_values | JSON | NO | Valores nuevos (para INSERT/UPDATE) |
| user_id | BIGINT UNSIGNED | NO | Usuario que realizó la acción |
| ip_address | VARCHAR(45) | NO | Dirección IP del usuario |
| user_agent | TEXT | NO | Cadena de user agent |
| created_at | TIMESTAMP | SÍ | Fecha/hora de la acción |

---

## Flujo del Proceso de Negocio

```
1. SOLICITUD (punto de entrada)
   ↓
2. RFQ (opcional - documento formal)
   ↓
3. COTIZACIÓN (auto-generada, con versiones)
   ↓
4. ORDEN DE COMPRA (del cliente)
   ↓
5. SERVICIO EJECUTADO (servicio realizado)
   ↓
6. HES (CRÍTICO - requerido para facturar)
   ↓
7. FACTURA (generada por contabilidad)
   ↓
8. PAGO (conciliación)
```

## Reglas de Negocio Clave

- **HES es OBLIGATORIO** para crear facturas (`invoices.hes_id` NOT NULL)
- **Versionado de cotizaciones** mediante `parent_quote_id` y `version`
- **Métodos de pago flexibles** en HES (factoring, pago directo, contado, otros)
- **Adjuntos polimórficos** pueden vincularse a cualquier entidad
- **Auditoría completa** de todos los cambios
- **Eliminaciones suaves** preservan datos históricos
- **Estados de proceso** configurables por tipo de entidad

## Tipos de Documentos por Etapa

- **Solicitud**: WhatsApp, emails, documentos físicos
- **RFQ**: Documento RFQ oficial, emails, anexos técnicos  
- **Cotización**: Auto-guardada, respuestas del cliente
- **OC**: Orden de compra oficial, notificaciones
- **Servicio**: Guías técnicas, fotos, reportes, evidencias
- **HES**: Documento HES oficial (crítico), emails
- **Factura**: Factura desde contabilidad, comprobantes
- **Pago**: Comprobantes bancarios, estados de cuenta