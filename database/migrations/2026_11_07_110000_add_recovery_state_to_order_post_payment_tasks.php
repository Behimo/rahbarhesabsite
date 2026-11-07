<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_post_payment_tasks', function (Blueprint $table) {
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('last_error')->nullable();
            $table->index(['completed_at', 'rejected_at'], 'post_payment_open_tasks');
        });
    }

    public function down(): void
    {
        Schema::table('order_post_payment_tasks', function (Blueprint $table) {
            $table->dropIndex('post_payment_open_tasks');
            $table->dropColumn(['attempts', 'last_attempt_at', 'rejected_at', 'last_error']);
        });
    }
};
