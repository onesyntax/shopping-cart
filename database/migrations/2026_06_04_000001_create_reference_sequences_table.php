<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-prefix counters for generated references (ORD-1, INV-2, ...). Holding the
 * count in the database makes references unique across HTTP requests, which an
 * in-process counter cannot guarantee once orders persist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_sequences', function (Blueprint $table) {
            $table->string('prefix')->primary();
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_sequences');
    }
};
