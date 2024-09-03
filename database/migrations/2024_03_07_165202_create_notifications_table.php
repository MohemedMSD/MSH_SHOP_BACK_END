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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('content');
            $table->string('response')->default('pending');
            $table->string('subject');
            $table->foreignId('user_id')->references('id')->on('users')
            ->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->references('id')->on('orders')
            ->onDelete('cascade');
            $table->boolean('deleted_from_receive')->default(0);
            $table->boolean('deleted_from_send')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
