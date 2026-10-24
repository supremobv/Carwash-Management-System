<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('washer')->index();
            $t->boolean('active')->default(true);
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('phone', 40)->index();
            $t->string('email')->nullable(); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id(); $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->string('plate', 30)->unique(); $t->string('make_model');
            $t->string('size', 20); $t->string('color', 40)->nullable(); $t->timestamps();
        });
        Schema::create('services', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->text('description')->nullable();
            $t->unsignedInteger('price_cents'); $t->unsignedSmallInteger('duration_minutes');
            $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::create('bays', function (Blueprint $t) {
            $t->id(); $t->string('name')->unique(); $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::create('wash_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('bay_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 20)->default('waiting')->index();
            $t->dateTime('scheduled_at')->nullable()->index();
            $t->dateTime('started_at')->nullable(); $t->dateTime('completed_at')->nullable();
            $t->unsignedInteger('subtotal_cents'); $t->unsignedInteger('discount_cents')->default(0);
            $t->unsignedInteger('tax_cents')->default(0); $t->unsignedInteger('total_cents');
            $t->text('notes')->nullable(); $t->timestamps(); $t->index(['status', 'created_at']);
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('wash_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('service_id')->constrained()->restrictOnDelete();
            $t->string('name'); $t->unsignedInteger('price_cents'); $t->unsignedSmallInteger('duration_minutes');
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('wash_order_id')->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->integer('amount_cents'); $t->string('method', 20); $t->string('reference')->nullable();
            $t->uuid('idempotency_key')->unique(); $t->timestamps();
        });
        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('unit', 20); $t->decimal('quantity', 12, 2)->default(0);
            $t->decimal('reorder_level', 12, 2)->default(5); $t->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id(); $t->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->decimal('quantity', 12, 2); $t->string('reason'); $t->timestamps();
        });
        Schema::create('expenses', function (Blueprint $t) {
            $t->id(); $t->string('description'); $t->string('category', 40);
            $t->unsignedInteger('amount_cents'); $t->date('spent_on')->index();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('business_name')->default('AquaFlow'); $t->string('tagline')->default('A little care. A better shine.');
            $t->string('email')->nullable(); $t->string('phone')->nullable(); $t->string('address')->nullable();
            $t->string('currency', 3)->default('PHP'); $t->string('timezone')->default('Asia/Manila');
            $t->decimal('tax_rate', 5, 2)->default(0); $t->string('logo_path')->nullable();
            $t->text('receipt_footer')->nullable(); $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action'); $t->string('subject'); $t->string('ip_address', 45)->nullable(); $t->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['audit_logs','settings','expenses','stock_movements','inventory_items','payments','order_items','wash_orders','bays','services','vehicles','customers'] as $table) Schema::dropIfExists($table);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role','active']));
    }
};
