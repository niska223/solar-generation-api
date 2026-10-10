<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add SLSEA operator roles/jurisdiction to the users table and create the
     * devices table. This migration is additive and does not touch existing
     * seeded data (provinces, districts, substations, installations, readings).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('district')->after('password');
            $table->foreignId('province_id')
                ->nullable()
                ->after('role')
                ->constrained('provinces')
                ->restrictOnDelete();
            $table->foreignId('district_id')
                ->nullable()
                ->after('province_id')
                ->constrained('districts')
                ->restrictOnDelete();
            $table->boolean('is_active')->default(true)->after('district_id');
            $table->index('role');
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solar_installation_id')
                ->unique()
                ->constrained('solar_installations')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('device_identifier', 100)->unique();
            $table->string('password_hash');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['province_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'province_id', 'district_id', 'is_active']);
        });
    }
};
