<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('queue_number');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('package_name');
            $table->decimal('package_price', 12, 2)->default(0);
            $table->date('order_date');
            $table->date('agreed_start_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->enum('work_status', ['belum_dikerjakan', 'on_progress', 'selesai'])->default('belum_dikerjakan');
            $table->enum('order_status', ['active', 'cancelled'])->default('active');
            $table->decimal('addon_total', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
