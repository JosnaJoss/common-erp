<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff Profile
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // if staff has ERP login
            
            // Personal
            $table->string('employee_id', 50);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('aadhaar_number', 50)->nullable();
            $table->string('pan_number', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->default('Male');
            $table->string('blood_group', 10)->nullable();
            $table->string('highest_education', 100)->nullable();
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->default('Single');
            $table->string('photo')->nullable();
            $table->text('permanent_address')->nullable();
            $table->text('temporary_address')->nullable();
            $table->string('emergency_contact_person', 100)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->string('emergency_contact_number', 50)->nullable();

            // Employment
            $table->string('designation', 100)->nullable();
            $table->enum('employee_type', ['salaried', 'daily_wage'])->default('salaried');
            $table->decimal('salary_amount', 15, 2)->default(0);
            $table->date('date_of_joining')->nullable();
            $table->date('last_working_date')->nullable();
            $table->enum('status', ['active', 'probation', 'resigned', 'terminated'])->default('active');

            // Salary & Benefits
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('hra', 15, 2)->default(0);
            $table->decimal('other_allowances', 15, 2)->default(0);
            $table->decimal('standard_deductions', 15, 2)->default(0);
            $table->boolean('pf_applicable')->default(false);
            $table->string('pf_number', 50)->nullable();
            $table->string('pf_uan', 50)->nullable();
            $table->decimal('pf_rate_percent', 5, 2)->default(12.00);
            $table->boolean('esi_applicable')->default(false);
            $table->string('esi_number', 50)->nullable();
            $table->decimal('esi_rate_percent', 5, 2)->default(0.75);
            $table->text('other_benefits')->nullable();

            // Driving License
            $table->string('license_number', 50)->nullable();
            $table->string('license_class', 50)->nullable();
            $table->date('license_start_date')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('license_document')->nullable();

            // Bank Details
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_ifsc', 30)->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->enum('bank_account_type', ['Savings', 'Current', 'Salary'])->default('Salary');
            $table->string('bank_holder_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_passbook_file')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_id']);
        });

        // Staff Documents
        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('document_type', 100); // Salary Certificate, Police Clearance, ID, Education
            $table->string('title');
            $table->string('file_path');
            $table->timestamps();
        });

        // Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shift_name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_duration_minutes')->default(60);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Holidays
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('holiday_name');
            $table->date('holiday_date');
            $table->enum('holiday_type', ['National', 'Festival', 'Company', 'Weekly Off'])->default('Festival');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Staff Attendance
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->enum('attendance_status', ['Present', 'Absent', 'Half Day', 'On Leave', 'Holiday'])->default('Present');
            $table->decimal('working_hours', 5, 2)->default(8.00);
            $table->decimal('overtime_hours', 5, 2)->default(0.00);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'staff_id', 'attendance_date']);
        });

        // Staff Leave Applications
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->enum('leave_type', ['Casual', 'Sick', 'Earned', 'Maternity', 'Unpaid'])->default('Casual');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('number_of_days', 5, 1)->default(1);
            $table->text('reason');
            $table->string('supporting_document')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_remarks')->nullable();
            $table->timestamps();
        });

        // Staff Targets
        Schema::create('staff_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('target_name');
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('target_frequency', ['One-time', 'Monthly', 'Custom'])->default('Monthly');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'achieved', 'expired'])->default('active');
            $table->timestamps();
        });

        // Incentives
        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('target_id')->nullable()->constrained('staff_targets')->nullOnDelete();
            $table->enum('incentive_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('incentive_amount', 15, 2)->default(0);
            $table->string('calculation_basis')->nullable();
            $table->date('effective_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Staff Allowances
        Schema::create('staff_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->enum('allowance_type', ['Travel', 'Food', 'Accommodation', 'Mobile', 'Other'])->default('Travel');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('frequency', ['Monthly', 'One-time', 'Specific Month'])->default('Monthly');
            $table->date('effective_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // Overtime Management
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Staff Payroll
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('salary_month', 7); // YYYY-MM
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('hra', 15, 2)->default(0);
            $table->decimal('other_allowances', 15, 2)->default(0);
            $table->decimal('incentives', 15, 2)->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->decimal('pf_deduction', 15, 2)->default(0);
            $table->decimal('esi_deduction', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->enum('payment_mode', ['Cash', 'Bank Transfer', 'Cheque'])->default('Bank Transfer');
            $table->string('transaction_reference', 100)->nullable();
            $table->enum('payroll_status', ['generated', 'approved', 'paid'])->default('generated');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'staff_id', 'salary_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('overtimes');
        Schema::dropIfExists('staff_allowances');
        Schema::dropIfExists('incentives');
        Schema::dropIfExists('staff_targets');
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('staff_documents');
        Schema::dropIfExists('staff');
    }
};

