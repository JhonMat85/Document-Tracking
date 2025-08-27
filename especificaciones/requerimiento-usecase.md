Sistema de Trackeo de Documentos - Casos de Uso Completos (Corregidos)
Actores
Actor Principal: Proveedor de Servicios (Usuario del Sistema)

Actores Secundarios: Personal Administrativo (Jaime, Andrés), Área de Contabilidad/Finanzas

Casos de Uso (UC)
UC000: Registrar Solicitud Simple (PUNTO DE ENTRADA)
Objetivo: Capturar solicitudes iniciales recibidas por WhatsApp o email.

Precondiciones:

Usuario autenticado.

Cliente ha enviado solicitud informal.

Flujo Principal:

Usuario recibe solicitud simple por WhatsApp o email.

Usuario selecciona "Nueva Solicitud" en el sistema.

Usuario ingresa datos MANUALMENTE:

Cliente/Clínica solicitante

Área requirente

Contacto responsable (nombre, teléfono, email)

Descripción del servicio solicitado

Fecha requerida para servicio

Urgencia (Normal/Urgente)

Usuario SUBE/ADJUNTA evidencia de la solicitud:

Captura de WhatsApp

Email original

Foto de documento físico

Sistema asigna número de solicitud automático.

Sistema registra con estado "Solicitud Recibida".

Sistema programa recordatorio para elaborar cotización.

Flujos Alternativos:

4a. Solicitud telefónica: Usuario puede registrar sin adjuntos, solo descripción manual.

Postcondiciones:

Solicitud registrada y documentada.

Disponible para generar cotización directa O esperar RFQ formal.

UC001: Registrar RFQ Recibido
Objetivo: Registrar RFQ que envía la clínica al proveedor.

Precondiciones:

Solicitud simple registrada (opcional).

Clínica ha enviado RFQ por email/notificación.

Flujo Principal:

Usuario recibe notificación por email de RFQ de la clínica.

Usuario selecciona solicitud existente O crea nueva entrada.

Usuario selecciona "Registrar RFQ Recibido".

Usuario ingresa datos MANUALMENTE:

Número de RFQ (ej: RQF375)

Cliente/Clínica emisora

Fecha de recepción

Fecha límite para cotizar

Descripción del servicio

Usuario SUBE/ADJUNTA archivo del RFQ (PDF, imagen, email).

Sistema vincula con solicitud original (si existe).

Sistema actualiza estado a "RFQ Recibido - Pendiente Cotizar".

Sistema programa alerta para fecha límite.

Flujos Alternativos:

1a. RFQ directo: Si no hay solicitud previa, se crea registro nuevo.

Postcondiciones: RFQ registrado con documento adjunto.

UC002: Gestionar Cotizaciones (CORREGIDO)
Objetivo: Crear y enviar cotización en respuesta al RFQ o solicitud directa.

Precondiciones:

RFQ registrado O solicitud directa recibida.

Flujo Principal:

Usuario selecciona RFQ/solicitud pendiente de cotizar.

Usuario selecciona "Crear Cotización".

Sistema GENERA la cotización con template predefinido.

Sistema pre-llena datos desde RFQ/solicitud.

Usuario completa:

Costo del servicio

Tiempo de ejecución

Condiciones especiales

Sistema GUARDA cotización automáticamente en base de datos.

Sistema permite descargar/imprimir cotización en PDF.

Usuario envía cotización al cliente (fuera del sistema).

Usuario marca en sistema como "Cotización Enviada".

Sistema programa seguimiento automático.

Postcondiciones: Cotización generada, guardada automáticamente y marcada como enviada.

UC003: Registrar Respuesta de Cotización
Objetivo: Actualizar estado según respuesta del cliente.

Precondiciones:

Cotización enviada.

Cliente ha respondido.

Flujo Principal:

Usuario recibe respuesta del cliente.

Usuario busca cotización correspondiente.

Usuario actualiza estado:

Aprobada → Continúa flujo normal

Rechazada → Se cierra proceso

En negociación → Permite modificar cotización

Pendiente → Mantiene seguimiento

Usuario SUBE/ADJUNTA respuesta del cliente (email, WhatsApp).

Si es aprobada, sistema solicita confirmar datos para siguiente fase.

Flujos Alternativos:

3a. Modificación requerida: Usuario puede generar nueva versión de cotización (sistema la guarda automáticamente).

Postcondiciones: Estado actualizado con documentación de respuesta.

UC004: Gestionar Orden de Compra
Objetivo: Registrar OC recibida del cliente.

Precondiciones:

Cotización aprobada.

Flujo Principal:

Usuario recibe OC del cliente por email/correo físico.

Usuario selecciona "Registrar Orden de Compra".

Usuario ingresa datos MANUALMENTE:

Número de OC (ej: 4600021590-00010)

Número de SOLPED (ej: 2000013516-00020)

Fecha de emisión OC

Técnico asignado al servicio

Usuario SUBE/ADJUNTA archivo de la OC (PDF, foto, scan).

Sistema vincula OC con cotización y RFQ.

Sistema actualiza estado a "OC Recibida - Listo para Ejecutar".

Flujos Alternativos:

Servicio Urgente sin OC: Sistema permite marcar como "Ejecutado sin OC" y genera alerta para seguimiento posterior.

Postcondiciones: OC registrada con documento adjunto, servicio programado.

UC005: Ejecutar Servicio
Objetivo: Marcar servicio como ejecutado y registrar documentación.

Precondiciones:

OC registrada O servicio urgente autorizado.

Flujo Principal:

Usuario/técnico ejecuta físicamente el servicio.

Usuario accede al sistema y marca servicio como "Ejecutado".

Usuario registra datos de ejecución (fecha real, técnico, observaciones).

Usuario SUBE documentos del servicio (guías, fotos, reportes).

Sistema actualiza estado a "Ejecutado - Pendiente HES".

Sistema envía notificación automática.

Postcondiciones: Servicio ejecutado, documentado y esperando HES del cliente.

UC006: Registrar HES (Hoja de Entrada de Servicio) (AJUSTADO)
Objetivo: Registrar HES emitido por cliente como requisito indispensable para facturar.

Precondiciones:

Servicio ejecutado y documentado.

Cliente ha emitido HES oficial.

Flujo Principal:

Usuario recibe HES del cliente.

Usuario registra datos MANUALMENTE del HES:

Número de HES (ej: 1000070935)

Fecha de aprobación del HES

Número de OC vinculada

Aprobador (ej: MENDOZA ANTONIO FRANCISCO)

Monto autorizado para facturar

FORMA DE PAGO (campo flexible):

Factoring a X días (con % descuento)

Pago directo a X días

Contado

Otros términos específicos

Porcentaje de descuento (solo si aplica factoring)

Usuario SUBE/ADJUNTA documento HES oficial (PDF, foto).

Sistema valida vinculación con OC registrada.

Sistema actualiza estado a "HES RECIBIDO - AUTORIZADO PARA FACTURAR".

Postcondiciones:

HES registrado con documento oficial adjunto.

Facturación AUTORIZADA por cliente.

Condiciones de pago específicas establecidas.

UC007: Gestionar Facturación (CORREGIDO)
Objetivo: Registrar y trackear factura generada por contabilidad/finanzas.

Precondiciones:

✅ Servicio ejecutado.

✅ HES registrado (REQUISITO INDISPENSABLE).

Flujo Principal:

Usuario solicita factura a área de contabilidad (fuera del sistema).

Contabilidad genera factura oficial con datos y condiciones del HES.

Usuario recibe factura generada y la SUBE al sistema.

Usuario registra datos de la factura (número, fecha, monto, referencias).

Usuario envía factura a "facturación electrónica" de la clínica (fuera del sistema).

Usuario marca como "Factura Enviada".

Sistema actualiza estado a "Facturado - En Proceso".

Flujos Alternativos:

1a. Sin HES: Sistema bloquea la facturación y muestra alerta "REQUISITO HES INDISPENSABLE".

Postcondiciones: Factura oficial subida al sistema y en seguimiento.

UC008: Controlar Conformidad de Facturación (AJUSTADO)
Objetivo: Confirmar que facturación electrónica procesó correctamente.

Precondiciones:

Factura enviada a facturación electrónica.

Flujo Principal:

Sistema alerta después de 2 días si no hay confirmación.

Usuario verifica y actualiza estado:

Conforme → Pasa a "Pendiente de Pago por Finanzas"

Observado → Requiere correcciones

Rechazado → Requiere nueva factura

Usuario SUBE confirmación de estado (email, captura).

Si está conforme, sistema programa seguimiento de pago según HES específico:

Factoring: Según días definidos en HES.

Pago directo: Según plazo establecido en HES.

Contado: Seguimiento inmediato.

Otros: Según condiciones particulares del HES.

Postcondiciones: Estado de facturación confirmado y documentado.

UC009: Gestionar Pagos (AJUSTADO)
Objetivo: Registrar y reconciliar pagos recibidos.

Precondiciones:

Factura conforme en el sistema de la clínica.

Flujo Principal:

Usuario recibe consolidado de pago.

Usuario identifica facturas que corresponden al monto por cliente, fechas, o forma de pago del HES.

Usuario registra MANUALMENTE según tipo de pago del HES:

Monto bruto pagado

Descuentos aplicables (si es factoring, etc.)

Monto neto recibido

Fecha y método de pago

Usuario SUBE/ADJUNTA comprobante de pago.

Sistema marca facturas como "Pagadas" y cierra el ciclo.

Flujos Alternativos:

Pago no identificable: Marcar como "Pago por Identificar".

Pago parcial: Sistema permite registrar pagos parciales.

Diferencias con HES: Sistema alerta si hay diferencias.

Postcondiciones: Pagos reconciliados y proceso completo cerrado.

UC010: Generar Reportes y Dashboard (AJUSTADO)
Objetivo: Proveer vista consolidada y reportes del negocio.

Flujo Principal:

Sistema presenta dashboard principal con alertas:

📱 Solicitudes pendientes de procesar

📋 RFQs pendientes de cotizar

🚨 Servicios ejecutados sin OC (alertas rojas)

🟠 Servicios ejecutados SIN HES (bloqueo de facturación)

📄 Facturas pendientes de pago

⏰ Alertas de vencimiento según diferentes tipos de pago del HES

Usuario puede generar reportes personalizados con filtros.

El reporte incluye columnas como Forma de Pago y % Descuento.

Sistema aplica códigos de colores automáticos.

Postcondiciones: Dashboard actualizado con alertas de HES y reportes disponibles.

UC011: Gestión de Documentos Adjuntos
Objetivo: Subir, visualizar y gestionar todos los documentos del proceso.

Flujo Principal:

Dentro de cualquier registro, el usuario accede a "Gestionar Documentos".

Sistema permite:

Subir nuevos archivos (PDF, JPG, PNG, etc.).

Visualizar, descargar, eliminar y renombrar documentos.

Sistema guarda archivos con nomenclatura organizada y registra logs para auditoría.

Postcondiciones: Documentos organizados, seguros y accesibles para auditoría completa.

Flujos de Proceso Completos (Corregidos)
FLUJO A: Con RFQ Formal
UC000 → UC001 → UC002 → UC003 → UC004 → UC005 → UC006 (HES) → UC007 → UC008 → UC009

FLUJO B: Solicitud Directa
UC000 → UC002 → UC003 → UC004 → UC005 → UC006 (HES) → UC007 → UC008 → UC009

FLUJO C: Servicio Urgente
UC000 → UC002 → UC003 → UC005 (sin OC) → UC004 (posterior) → UC006 (HES) → UC007 → UC008 → UC009

🔴 PUNTO CRÍTICO: Sin HES registrado, NO ES POSIBLE FACTURAR.

(UC010 y UC011 son transversales y están disponibles en cualquier momento del proceso)

Gestión de Documentos
DOCUMENTOS QUE GENERA EL SISTEMA:
✅ Cotizaciones (se guardan automáticamente)

DOCUMENTOS QUE SE SUBEN AL SISTEMA:
📄 Solicitudes (WhatsApp/email)

📄 RFQ del cliente

📄 Órdenes de Compra

📄 Documentos del servicio ejecutado

📄 HES del cliente (requisito indispensable)

📄 Facturas generadas por contabilidad

📄 Comprobantes de pago