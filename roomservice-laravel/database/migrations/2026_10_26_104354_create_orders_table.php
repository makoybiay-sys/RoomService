<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->integer('floor_number');
            $table->string('room_number');
            $table->string('guest_name')->nullable();
            $table->enum('status', ['PENDING', 'PREPARING', 'READY', 'DELIVERING', 'DELIVERED', 'CHARGED', 'CANCELLED', 'VOIDED'])->default('PENDING');
            $table->integer('estimated_minutes')->nullable();
            $table->enum('payment_method', ['CASH', 'CHARGE_TO_ROOM'])->nullable();
            $table->enum('payment_status', ['UNPAID', 'PAID'])->default('UNPAID');
            $table->foreignId('chef_id')->nullable()->constrained('staff_accounts')->nullOnDelete();
            $table->foreignId('waiter_id')->nullable()->constrained('staff_accounts')->nullOnDelete();
            $table->foreignId('coffee_manager_id')->nullable()->constrained('staff_accounts')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('waiter_arrived')->default(false);
            $table->timestamp('arrived_at')->nullable();
            $table->boolean('arrival_seen_by_guest')->default(false);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('special_notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};