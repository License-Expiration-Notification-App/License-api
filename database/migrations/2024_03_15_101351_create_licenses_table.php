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
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained();
            $table->foreignUuid('subsidiary_id')->constrained();
            $table->foreignUuid('license_type_id')->constrained();
            $table->foreignUuid('mineral_id')->constrained();
            // $table->integer('license_type_id');
            // $table->string('mineral');
            // $table->integer('client_id');
            // $table->integer('subsidiary_id');
            $table->string('license_no')->unique();
            $table->integer('state_id')->nullable();
            $table->integer('lga_id')->nullable();
            $table->date('license_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('renewed_date')->nullable();
            $table->string('status')->nullable();
            $table->integer('added_by')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
