
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solar_installation_id')
                ->constrained('solar_installations')
                ->restrictOnDelete();
            $table->dateTime('recorded_at');
            $table->decimal('power_kw', 10, 3);
            $table->decimal('energy_kwh', 14, 3);
            $table->decimal('voltage', 8, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['solar_installation_id', 'recorded_at'],
                'reading_installation_time_unique'
            );
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_readings');
    }
};
