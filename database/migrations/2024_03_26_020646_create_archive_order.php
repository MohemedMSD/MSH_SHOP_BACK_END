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
        Schema::create('archive_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')
            ->onDelete('cascade');
            $table->double('total_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->boolean('received')->default(0);
            $table->string('status')->nullable();
            $table->integer('part');
            $table->json('products')->nullable();
            $table->string('session_id', 2000)->nullable();
            $table->integer('order_id')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_orders');
    }
};
