<?php

namespace App\Support;

use App\Models\ReferenceCounter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Atomic, gap-safe reference-number generation - the same guarantee
 * PaymentRequestCounter already gives payment_requests, generalised to a
 * single shared table (see reference_counters, Phase 1) so Activity and
 * Retirement references don't each need their own dedicated counter
 * table. Never derive a reference from MAX(id)+1 or COUNT(*)+1 - both
 * are unsafe under concurrent requests.
 */
class ReferenceGenerator
{
    /**
     * @param  string  $counterKey  e.g. 'activity', 'activity_retirement'
     * @param  string  $prefix  e.g. 'ACT', 'RET'
     */
    public static function next(string $counterKey, string $prefix, int $seqLength = 5): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($counterKey, $year, $prefix, $seqLength) {
            $counter = ReferenceCounter::where('counter_key', $counterKey)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                try {
                    $counter = ReferenceCounter::create([
                        'counter_key' => $counterKey,
                        'year' => $year,
                        'last_sequence' => 0,
                    ]);
                } catch (QueryException $e) {
                    // Another concurrent request created this
                    // (counter_key, year) row first (unique constraint,
                    // see Phase 1 migration) - a duplicate-key error
                    // doesn't poison a MySQL/InnoDB transaction, so just
                    // fetch and lock the row that won the race instead
                    // of failing the whole operation.
                    $counter = ReferenceCounter::where('counter_key', $counterKey)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $counter->increment('last_sequence');

            $sequence = str_pad((string) $counter->last_sequence, $seqLength, '0', STR_PAD_LEFT);

            return "{$prefix}/{$year}/{$sequence}";
        });
    }
}
