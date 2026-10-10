
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solar_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grid_substation_id')
                ->constrained('grid_substations')
                ->restrictOnDelete();
            $table->string('meter_id')->unique();
            $table->decimal('capacity_kw', 10, 2);
            $table->string('address')->nullable();
            $table->date('installed_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solar_installations');
    }
};
