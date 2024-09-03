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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->json('adress')->nullable();
        });
        Schema::table('archive_orders', function (Blueprint $table) {
            //
            $table->json('adress')->nullable();
        });
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->json('adress')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn('adress');
        });
        Schema::table('archive_orders', function (Blueprint $table) {
            //
            $table->dropColumn('adress');
        });
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->dropColumn('adress');
        });
    }
};
