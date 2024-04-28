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
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->enum('action', ['cr', 'dr']); // enum yg berisi action credit & debit | cr itu menambahkan balance ke saldo dan klo dr mengurangi saldo kita
            $table->string('thumbnail')->nullable();
            $table->softDeletes(); // kolom utk menandakan data tersebut sudah di hapus atau blm
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_types');
    }
};
