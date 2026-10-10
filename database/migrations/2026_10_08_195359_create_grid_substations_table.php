
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grid_substations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')
                ->constrained('districts')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grid_substations');
    }
};
