<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('contact_person')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('pincode', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('gst_number', 50)->nullable();
            $table->string('pan_number', 50)->nullable();
            $table->foreignId('account_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ledger_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->enum('account_type', ['debit', 'credit'])->default('credit');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('shop_name')->nullable();
            $table->text('address')->nullable();
            $table->string('pincode', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('territory', 100)->nullable();
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gst_number', 50)->nullable();
            $table->string('pan_number', 50)->nullable();
            $table->foreignId('account_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ledger_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('price_level', ['minimum', 'normal', 'maximum'])->default('normal');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->enum('account_type', ['debit', 'credit'])->default('debit');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
    }
};

