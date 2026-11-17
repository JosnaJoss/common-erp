<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Industry Process Templates (Pre-configured templates for 12 industries)
        Schema::create('industry_process_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('industry_type', 50); // food_processing, garments, beverages, furniture, construction, automobile, agriculture, healthcare, printing, wholesale, cosmetics, jewellery
            $table->string('department_name', 100);
            $table->string('process_name', 100);
            $table->string('process_code', 50);
            $table->text('description')->nullable();
            $table->string('default_uom', 20)->default('KG');
            $table->boolean('requires_qc')->default(false);
            $table->integer('display_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 2. Daily Operations Master
        Schema::create('daily_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('operation_number', 50);
            $table->date('operation_date');
            $table->foreignId('department_head_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('work_order_ref', 100)->nullable();
            $table->string('process_name', 100);
            $table->text('work_description')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('work_status', ['draft', 'in_progress', 'completed', 'paused'])->default('completed');
            $table->enum('approval_status', [
                'draft', 'submitted', 'supervisor_approved', 
                'department_head_approved', 'admin_approved', 'rejected'
            ])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_date')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('stock_posted')->default(false);
            $table->timestamp('stock_posted_at')->nullable();
            $table->decimal('total_labor_cost', 15, 2)->default(0);
            $table->decimal('total_material_cost', 15, 2)->default(0);
            $table->decimal('total_wastage_cost', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'operation_number']);
        });

        // 3. Employee Participation in Activity
        Schema::create('daily_operation_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->enum('employment_type', ['salaried', 'daily_wage', 'hourly', 'contract', 'piece_rate'])->default('salaried');
            $table->decimal('hours_worked', 8, 2)->default(8.00);
            $table->decimal('units_completed', 12, 2)->default(0.00);
            $table->decimal('rate_per_unit', 15, 2)->default(0.00);
            $table->decimal('wage_earned', 15, 2)->default(0.00);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        // 4. Material / Stock Consumption
        Schema::create('daily_operation_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('source_godown_id')->constrained('godowns')->cascadeOnDelete();
            $table->string('batch_no', 50)->default('DEFAULT');
            $table->decimal('opening_quantity', 15, 3)->default(0);
            $table->decimal('issued_quantity', 15, 3)->default(0);
            $table->decimal('consumed_quantity', 15, 3);
            $table->decimal('returned_quantity', 15, 3)->default(0);
            $table->decimal('wastage_quantity', 15, 3)->default(0);
            $table->decimal('remaining_quantity', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->timestamps();
        });

        // 5. Production / Output Generated
        Schema::create('daily_operation_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('dest_godown_id')->constrained('godowns')->cascadeOnDelete();
            $table->string('batch_number', 50)->default('DEFAULT');
            $table->decimal('produced_quantity', 15, 3);
            $table->decimal('rejected_quantity', 15, 3)->default(0);
            $table->decimal('wastage_quantity', 15, 3)->default(0);
            $table->enum('quality_status', ['pending', 'passed', 'failed', 'conditional'])->default('passed');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 6. Wastage & Loss Recording (Granular per activity)
        Schema::create('daily_operation_wastages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('wastage_name', 100);
            $table->enum('wastage_type', [
                'process_loss', 'scrap_trimming', 'spillage', 
                'burnt_defective', 'packaging_damage', 'handling_loss', 'other'
            ])->default('process_loss');
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 20)->default('KG');
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('recoverable_quantity', 15, 3)->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // 7. Packing & Finished Goods Distribution
        Schema::create('daily_operation_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('destination_godown_id')->constrained('godowns')->cascadeOnDelete();
            $table->enum('destination_type', [
                'factory_sales_counter', 'head_office_warehouse', 
                'delivery_van', 'branch_warehouse', 'packing_warehouse', 'other'
            ])->default('branch_warehouse');
            $table->string('batch_no', 50)->default('DEFAULT');
            $table->decimal('quantity', 15, 3);
            $table->foreignId('stock_transfer_id')->nullable()->constrained('stock_transfers')->nullOnDelete();
            $table->enum('status', ['pending', 'dispatched', 'received'])->default('received');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_operation_distributions');
        Schema::dropIfExists('daily_operation_wastages');
        Schema::dropIfExists('daily_operation_outputs');
        Schema::dropIfExists('daily_operation_materials');
        Schema::dropIfExists('daily_operation_employees');
        Schema::dropIfExists('daily_operations');
        Schema::dropIfExists('industry_process_templates');
    }
};

