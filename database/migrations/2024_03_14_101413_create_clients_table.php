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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->string('company_email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('description')->nullable();
            $table->enum('status', ['Pending', 'Active', 'Inactive'])->default('Pending');
            $table->string('logo')->default('storage/client-logo/default.jpeg');    
            $table->timestamps();
            $table->softDeletes();

            $table->foreignUuid('main_admin')->nullable()->references('id')->on('users')
            ->onUpdate('cascade')->onDelete('cascade');
        });
        Schema::create('client_user', function (Blueprint $table) {
            $table->foreignUuid('client_id')->constrained();
            $table->foreignUuid('user_id')->constrained();
            // $table->unsignedBigInteger('client_id');
            // $table->unsignedBigInteger('user_id');

            // $table->foreign('client_id')->references('id')->on('clients')
            //     ->onUpdate('cascade')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')
            //     ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['user_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
        Schema::dropIfExists('client_user');
    }
};
