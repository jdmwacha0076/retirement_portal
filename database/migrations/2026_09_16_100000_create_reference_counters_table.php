<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic atomic reference-number counter, one row per (counter_key,
     * year) - e.g. ('activity', 2026), ('activity_retirement', 2026).
     * Used with the exact same lockForUpdate()-inside-DB::transaction()
     * pattern already proven by payment_request_counters, just
     * parameterised by key instead of being a dedicated table per domain -
     * this avoids two near-identical counter tables (one for Activity
     * references, one for Retirement references) when one generic table
     * serves both without weakening the atomicity guarantee at all.
     *
     * Never generate references from MAX(id)+1 or COUNT(*)+1 - both are
     * unsafe under concurrent requests. Always SELECT ... FOR UPDATE this
     * row (or INSERT it under a transaction if it doesn't exist yet),
     * increment last_sequence, and read the new value back in the same
     * transaction.
     */
    public function up(): void
    {
        Schema::create('reference_counters', function (Blueprint $table) {
            $table->id();
            $table->string('counter_key');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['counter_key', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_counters');
    }
};
