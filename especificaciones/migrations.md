# Laravel 12 - Complete Database Migrations Guide

## Migration Order and Commands

```bash
# 1. Users table (already exists in Laravel, but modify if needed)
php artisan make:migration modify_users_table --table=users

# 2. System Configuration
php artisan make:migration create_system_configurations_table
php artisan make:migration create_process_states_table

# 3. Client Management
php artisan make:migration create_clients_table
php artisan make:migration create_client_contacts_table

# 4. Business Process
php artisan make:migration create_requests_table
php artisan make:migration create_rfqs_table
php artisan make:migration create_quotes_table
php artisan make:migration create_quote_details_table
php artisan make:migration create_quote_transport_table

# 5. Service Execution
php artisan make:migration create_purchase_orders_table
php artisan make:migration create_executed_services_table

# 6. Critical Authorization
php artisan make:migration create_hes_table

# 7. Financial Management
php artisan make:migration create_invoices_table
php artisan make:migration create_payments_table

# 8. Support Tables
php artisan make:migration create_attachments_table
php artisan make:migration create_audit_log_table
```

---

## Complete Migration Specifications

### 1. Users Table (Laravel Default - Modifications if needed)

**File**: `modify_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    // Laravel default users table already has:
    // - id (bigint unsigned, auto_increment, primary key)
    // - name (varchar 255)
    // - email (varchar 255, unique)
    // - email_verified_at (timestamp nullable)
    // - password (varchar 255)
    // - remember_token (varchar 100, nullable)
    // - created_at (timestamp nullable)
    // - updated_at (timestamp nullable)
    
    // Add any additional fields if needed
    if (!Schema::hasColumn('users', 'is_active')) {
        $table->boolean('is_active')->default(true);
    }
});
```

### 2. System Configurations Table

**File**: `create_system_configurations_table.php`

```php
Schema::create('system_configurations', function (Blueprint $table) {
    $table->id();
    $table->string('key', 100)->unique();
    $table->text('value')->nullable();
    $table->enum('type', ['string', 'integer', 'decimal', 'boolean', 'json'])->default('string');
    $table->text('description')->nullable();
    $table->boolean('is_editable')->default(true);
    $table->string('group_name', 50)->default('general');
    $table->timestamps();
    
    // Indexes
    $table->index(['group_name', 'key']);
});
```

### 3. Process States Table

**File**: `create_process_states_table.php`

```php
Schema::create('process_states', function (Blueprint $table) {
    $table->id();
    $table->string('entity', 50);
    $table->string('code', 50);
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->string('color', 7)->nullable();
    $table->integer('display_order')->default(0);
    $table->boolean('is_initial_state')->default(false);
    $table->boolean('is_final_state')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    // Constraints
    $table->unique(['entity', 'code'], 'uk_state_entity_code');
    
    // Indexes
    $table->index(['entity', 'is_active']);
    $table->index(['entity', 'display_order']);
});
```

### 4. Clients Table

**File**: `create_clients_table.php`

```php
Schema::create('clients', function (Blueprint $table) {
    $table->id();
    $table->string('client_code', 20)->unique();
    $table->string('business_name', 255);
    $table->string('legal_name', 255);
    $table->enum('client_type', ['clinic', 'hospital', 'private_company', 'public_institution', 'others']);
    $table->string('tax_id', 20)->unique();
    $table->text('fiscal_address')->nullable();
    $table->string('main_phone', 50)->nullable();
    $table->string('main_email', 255)->nullable();
    $table->string('business_sector', 100)->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['client_type', 'is_active']);
    $table->index(['is_active', 'deleted_at']);
});
```

### 5. Client Contacts Table

**File**: `create_client_contacts_table.php`

```php
Schema::create('client_contacts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
    $table->string('full_name', 255);
    $table->string('position', 150)->nullable();
    $table->string('department', 150)->nullable();
    $table->string('phone', 50)->nullable();
    $table->string('email', 255)->nullable();
    $table->boolean('is_primary_contact')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['client_id', 'is_active']);
    $table->index(['client_id', 'is_primary_contact']);
    $table->index(['email', 'is_active']);
});
```

### 6. Requests Table

**File**: `create_requests_table.php`

```php
Schema::create('requests', function (Blueprint $table) {
    $table->id();
    $table->string('request_number', 20)->unique();
    $table->foreignId('client_id')->constrained('clients');
    $table->foreignId('contact_id')->nullable()->constrained('client_contacts');
    $table->string('requesting_department', 150)->nullable();
    $table->text('service_description');
    $table->dateTime('request_date');
    $table->dateTime('required_service_date')->nullable();
    $table->enum('urgency', ['normal', 'urgent'])->default('normal');
    $table->enum('origin', ['whatsapp', 'email', 'phone', 'in_person', 'system'])->default('email');
    $table->foreignId('state_id')->constrained('process_states');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['client_id', 'state_id']);
    $table->index(['request_date', 'urgency']);
    $table->index(['state_id', 'request_date']);
    $table->fullText('service_description');
});
```

### 7. RFQs Table

**File**: `create_rfqs_table.php`

```php
Schema::create('rfqs', function (Blueprint $table) {
    $table->id();
    $table->string('rfq_number', 50)->unique();
    $table->foreignId('request_id')->constrained('requests');
    $table->dateTime('received_date');
    $table->dateTime('quote_deadline');
    $table->text('detailed_description')->nullable();
    $table->foreignId('state_id')->constrained('process_states');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['request_id', 'state_id']);
    $table->index('quote_deadline');
    $table->index(['state_id', 'quote_deadline']);
    $table->unique(['rfq_number', 'request_id']);
});
```

### 8. Quotes Table

**File**: `create_quotes_table.php`

```php
Schema::create('quotes', function (Blueprint $table) {
    $table->id();
    $table->string('quote_number', 20)->unique();
    $table->foreignId('request_id')->constrained('requests');
    $table->foreignId('rfq_id')->nullable()->constrained('rfqs');
    $table->foreignId('destination_contact_id')->nullable()->constrained('client_contacts');
    $table->text('service_description');
    $table->text('pickup_address');
    $table->text('delivery_address');
    $table->dateTime('service_start_date');
    $table->dateTime('service_end_date')->nullable();
    $table->decimal('subtotal', 12, 2);
    $table->decimal('tax_percentage', 5, 2)->default(18.00);
    $table->decimal('tax_amount', 12, 2);
    $table->decimal('total', 12, 2);
    $table->enum('currency', ['PEN', 'USD'])->default('PEN');
    $table->enum('proposed_payment_method', ['factoring', 'direct_payment', 'cash', 'others'])->default('factoring');
    $table->integer('version')->default(1);
    $table->foreignId('parent_quote_id')->nullable()->constrained('quotes');
    $table->dateTime('generation_date');
    $table->dateTime('sent_date')->nullable();
    $table->dateTime('response_date')->nullable();
    $table->dateTime('expiration_date')->nullable();
    $table->foreignId('state_id')->constrained('process_states');
    $table->string('template_used', 100)->default('standard');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['request_id', 'version']);
    $table->index(['state_id', 'generation_date']);
    $table->index(['generation_date', 'expiration_date']);
    $table->index(['parent_quote_id', 'version']);
    $table->fullText('service_description');
});
```

### 9. Quote Details Table

**File**: `create_quote_details_table.php`

```php
Schema::create('quote_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
    $table->integer('item_order');
    $table->integer('quantity');
    $table->string('unit_of_measure', 20)->default('UNIT');
    $table->string('item_description', 255);
    $table->decimal('unit_price', 10, 2)->nullable();
    $table->decimal('item_subtotal', 10, 2)->nullable();
    $table->text('item_notes')->nullable();
    $table->timestamps();
    
    // Constraints
    $table->unique(['quote_id', 'item_order'], 'uk_quote_order');
    
    // Indexes
    $table->fullText('item_description');
});
```

### 10. Quote Transport Table

**File**: `create_quote_transport_table.php`

```php
Schema::create('quote_transport', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
    $table->string('driver_name', 255)->nullable();
    $table->string('driver_license', 50)->nullable();
    $table->string('vehicle_plate', 20)->nullable();
    $table->string('vehicle_model', 100)->nullable();
    $table->string('vehicle_capacity', 50)->nullable();
    $table->boolean('includes_insurance')->default(false);
    $table->text('transport_notes')->nullable();
    $table->timestamps();
    
    // Indexes
    $table->index('vehicle_plate');
    $table->index('driver_license');
});
```

### 11. Purchase Orders Table

**File**: `create_purchase_orders_table.php`

```php
Schema::create('purchase_orders', function (Blueprint $table) {
    $table->id();
    $table->string('po_number', 50)->unique();
    $table->string('solped_number', 50)->nullable();
    $table->foreignId('quote_id')->constrained('quotes');
    $table->dateTime('issue_date');
    $table->dateTime('validity_date')->nullable();
    $table->dateTime('scheduled_execution_date')->nullable();
    $table->string('assigned_technician', 255)->nullable();
    $table->string('reception_area', 255)->nullable();
    $table->decimal('authorized_amount', 12, 2);
    $table->enum('currency', ['PEN', 'USD'])->default('PEN');
    $table->text('special_conditions')->nullable();
    $table->boolean('is_urgent')->default(false);
    $table->foreignId('state_id')->constrained('process_states');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['quote_id', 'state_id']);
    $table->index('scheduled_execution_date');
    $table->index(['is_urgent', 'state_id']);
});
```

### 12. Executed Services Table

**File**: `create_executed_services_table.php`

```php
Schema::create('executed_services', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->constrained('purchase_orders');
    $table->dateTime('start_date');
    $table->dateTime('end_date')->nullable();
    $table->string('main_technician', 255);
    $table->json('work_team')->nullable();
    $table->text('work_description')->nullable();
    $table->text('incidents')->nullable();
    $table->decimal('execution_hours', 5, 2)->nullable();
    $table->foreignId('state_id')->constrained('process_states');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['purchase_order_id', 'state_id']);
    $table->index(['start_date', 'main_technician']);
    $table->index(['state_id', 'start_date']);
});
```

### 13. HES Table (CRITICAL)

**File**: `create_hes_table.php`

```php
Schema::create('hes', function (Blueprint $table) {
    $table->id();
    $table->string('hes_number', 50)->unique();
    $table->foreignId('executed_service_id')->constrained('executed_services');
    $table->string('reference_po_number', 50);
    $table->string('reference_solped_number', 50)->nullable();
    $table->dateTime('approval_date');
    $table->string('approver_name', 255);
    $table->string('approver_position', 150)->nullable();
    $table->decimal('authorized_amount', 12, 2);
    $table->enum('currency', ['PEN', 'USD'])->default('PEN');
    $table->enum('payment_method', ['factoring', 'direct_payment', 'cash', 'others']);
    $table->integer('payment_days')->nullable();
    $table->decimal('discount_percentage', 5, 2)->nullable();
    $table->string('imputation_type', 100)->nullable();
    $table->foreignId('state_id')->constrained('process_states');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['executed_service_id', 'state_id']);
    $table->index(['approval_date', 'payment_method']);
    $table->index(['payment_method', 'state_id']);
});
```

### 14. Invoices Table (CRITICAL)

**File**: `create_invoices_table.php`

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number', 50)->unique();
    $table->foreignId('hes_id')->constrained('hes'); // CRITICAL: NOT NULL - HES REQUIRED
    $table->dateTime('issue_date');
    $table->dateTime('sent_to_client_date')->nullable();
    $table->dateTime('due_date')->nullable();
    $table->decimal('subtotal', 12, 2);
    $table->decimal('tax_amount', 12, 2);
    $table->decimal('total', 12, 2);
    $table->enum('currency', ['PEN', 'USD'])->default('PEN');
    $table->string('accounting_area', 100)->nullable();
    $table->foreignId('state_id')->constrained('process_states');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['hes_id', 'state_id']);
    $table->index('issue_date');
    $table->index(['due_date', 'state_id']);
});
```

### 15. Payments Table

**File**: `create_payments_table.php`

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained('invoices');
    $table->dateTime('payment_date');
    $table->decimal('invoiced_amount', 12, 2);
    $table->decimal('discount_amount', 12, 2)->default(0.00);
    $table->decimal('discount_percentage', 5, 2)->default(0.00);
    $table->decimal('net_received_amount', 12, 2);
    $table->enum('currency', ['PEN', 'USD'])->default('PEN');
    $table->string('payment_method', 100)->nullable();
    $table->string('bank_reference', 255)->nullable();
    $table->string('origin_bank', 100)->nullable();
    $table->integer('delay_days')->nullable();
    $table->foreignId('state_id')->constrained('process_states');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['invoice_id', 'state_id']);
    $table->index('payment_date');
    $table->index('bank_reference');
    $table->index(['state_id', 'payment_date']);
});
```

### 16. Attachments Table (Polymorphic)

**File**: `create_attachments_table.php`

```php
Schema::create('attachments', function (Blueprint $table) {
    $table->id();
    $table->string('attachable_type', 255);
    $table->unsignedBigInteger('attachable_id');
    $table->string('file_name', 255);
    $table->string('original_name', 255);
    $table->string('file_path', 500);
    $table->string('mime_type', 100);
    $table->bigInteger('size_bytes');
    $table->string('file_hash', 64)->nullable();
    $table->boolean('is_public')->default(false);
    $table->enum('category', ['evidence', 'official', 'backup', 'generated'])->default('evidence');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['attachable_type', 'attachable_id']);
    $table->index(['category', 'is_public']);
    $table->index('file_hash');
    $table->index('uploaded_by');
});
```

### 17. Audit Log Table

**File**: `create_audit_log_table.php`

```php
Schema::create('audit_log', function (Blueprint $table) {
    $table->id();
    $table->string('table_name', 100);
    $table->unsignedBigInteger('record_id');
    $table->enum('action', ['INSERT', 'UPDATE', 'DELETE']);
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->foreignId('user_id')->nullable()->constrained('users');
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamp('created_at')->useCurrent();
    
    // Indexes
    $table->index(['table_name', 'record_id']);
    $table->index(['user_id', 'created_at']);
    $table->index(['created_at', 'action']);
});
```

---

## Seeders Required

### 1. Process States Seeder

```bash
php artisan make:seeder ProcessStatesSeeder
```

### 2. System Configurations Seeder

```bash
php artisan make:seeder SystemConfigurationsSeeder
```

---

## Migration Execution Order

```bash
php artisan migrate --step
```

**CRITICAL NOTES:**

1. **HES is MANDATORY** for invoice creation (`hes_id` NOT NULL)
2. **Foreign key constraints** must be respected in creation order
3. **Soft deletes** preserve historical data
4. **Polymorphic relationships** in attachments table
5. **JSON fields** for work_team and audit values
6. **Unique constraints** prevent duplicates
7. **Indexes** optimize query performance

**Run migrations in the exact order specified to avoid foreign key constraint errors.**