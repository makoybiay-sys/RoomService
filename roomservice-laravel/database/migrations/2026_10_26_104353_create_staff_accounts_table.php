<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('staff_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->enum('role', ['ADMIN', 'FNB_MANAGER', 'FRONT_DESK', 'COFFEE_MANAGER', 'WAITER', 'KITCHEN']);
            $table->string('area')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('staff_accounts'); }
};