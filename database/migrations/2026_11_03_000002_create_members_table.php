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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_code', 30)->unique();
            $table->string('name', 150);
            $table->string('phone', 20)->unique();
            $table->string('password', 255);
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact', 20)->nullable();
            $table->text('notes')->nullable();
            $table->date('join_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'join_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
