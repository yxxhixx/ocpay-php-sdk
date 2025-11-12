<?php

/**
 * Laravel Migration Example
 * 
 * Run: php artisan make:migration add_payment_fields_to_orders_table
 * Then copy the content below
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_ref')->nullable()->after('status');
            $table->text('payment_url')->nullable()->after('payment_ref');
            $table->timestamp('payment_link_created_at')->nullable()->after('payment_url');
            $table->timestamp('paid_at')->nullable()->after('payment_link_created_at');
            $table->string('payment_failed_reason')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_ref',
                'payment_url',
                'payment_link_created_at',
                'paid_at',
                'payment_failed_reason'
            ]);
        });
    }
};

