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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            // ESP32 unique id
            $table->string('device_uid')->unique();

            // authentication token
            $table->string('device_token')->unique();

            // serial number
            $table->string('serial_no')->unique();

            // fridge assign later by admin
            $table->foreignId('fridge_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            // device status
            $table->string('status')->default('offline');

            // optional info
            $table->string('firmware_version')->nullable();
            
            // mqtt config
            $table->string('mqtt_broker')->nullable();
            $table->integer('mqtt_port')->nullable();
            $table->string('mqtt_topic')->nullable();
            $table->integer('mqtt_username')->nullable();
            $table->string('mqtt_password')->nullable();

            // last heartbeat
            $table->timestamp('last_seen')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};