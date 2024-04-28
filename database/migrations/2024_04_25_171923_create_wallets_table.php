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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->float('balance', 10, 2); // saldo, gunakan float karna saldo uang biasanya akan banyak | panjang numbernya 10 dan 2 angka di belakang koma
            $table->string('pin')->nullable();
            $table->foreignId('user_id')->constrained('users'); // column user_id merupakan foreign key yg mengarah ke table users
            $table->string('card_number')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
