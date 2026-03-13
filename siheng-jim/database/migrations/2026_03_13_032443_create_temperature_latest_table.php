<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temperature_latest', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sensor_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('temperature',5,2);

            $table->boolean('alert_status')
                ->default(false);

            $table->timestamp('recorded_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temperature_latest');
    }
};