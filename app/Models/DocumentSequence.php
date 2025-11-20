<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentSequence extends Model
{
    protected $fillable = [
        'document_type',
        'prefix',
        'reset_period',
        'number_length',
        'year',
        'month',
        'last_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'last_number' => 'integer',
        'number_length' => 'integer',
    ];

    /**
     * Generate next document number for a given document type
     *
     * @param string $documentType - e.g., 'daily_payment_request'
     * @param string $prefix - e.g., 'SCI-FIN-PAY'
     * @param string $resetPeriod - 'none', 'yearly', or 'monthly'
     * @param int $numberLength - Number of digits for the sequence
     * @return string - Generated document number
     */
    public static function generateNumber(
        string $documentType,
        string $prefix,
        string $resetPeriod = 'none',
        int $numberLength = 6
    ): string {
        if (!in_array($resetPeriod, ['none', 'yearly', 'monthly'])) {
            throw new InvalidArgumentException("Invalid reset period: {$resetPeriod}");
        }

        return DB::transaction(function () use ($documentType, $prefix, $resetPeriod, $numberLength) {
            $now = now();
            $year = $resetPeriod !== 'none' ? $now->year : null;
            $month = $resetPeriod === 'monthly' ? $now->month : null;

            // Build query to find or create sequence
            $query = self::where('document_type', $documentType);

            if ($resetPeriod === 'monthly') {
                $query->where('year', $year)->where('month', $month);
            } elseif ($resetPeriod === 'yearly') {
                $query->where('year', $year)->whereNull('month');
            } else {
                $query->whereNull('year')->whereNull('month');
            }

            // Lock row for update to prevent race conditions
            $sequence = $query->lockForUpdate()->first();

            if (!$sequence) {
                // Create new sequence
                $sequence = self::create([
                    'document_type' => $documentType,
                    'prefix' => $prefix,
                    'reset_period' => $resetPeriod,
                    'number_length' => $numberLength,
                    'year' => $year,
                    'month' => $month,
                    'last_number' => 1,
                ]);
                $nextNumber = 1;
            } else {
                // Increment counter
                $nextNumber = $sequence->last_number + 1;
                $sequence->update([
                    'last_number' => $nextNumber,
                    'prefix' => $prefix, // Update prefix in case it changed
                    'number_length' => $numberLength, // Update length in case it changed
                ]);
            }

            // Format the document number
            return self::formatNumber($prefix, $nextNumber, $numberLength);
        });
    }

    /**
     * Format document number with prefix and padded number
     *
     * @param string $prefix
     * @param int $number
     * @param int $length
     * @return string
     */
    protected static function formatNumber(string $prefix, int $number, int $length): string
    {
        $paddedNumber = str_pad($number, $length, '0', STR_PAD_LEFT);
        return "{$prefix}-{$paddedNumber}";
    }

    /**
     * Get current number for a document type without incrementing
     *
     * @param string $documentType
     * @param string $resetPeriod
     * @return int
     */
    public static function getCurrentNumber(string $documentType, string $resetPeriod = 'yearly'): int
    {
        $now = now();
        $year = $resetPeriod !== 'none' ? $now->year : null;
        $month = $resetPeriod === 'monthly' ? $now->month : null;

        $query = self::where('document_type', $documentType);

        if ($resetPeriod === 'monthly') {
            $query->where('year', $year)->where('month', $month);
        } elseif ($resetPeriod === 'yearly') {
            $query->where('year', $year)->whereNull('month');
        } else {
            $query->whereNull('year')->whereNull('month');
        }

        $sequence = $query->first();
        return $sequence ? $sequence->last_number : 0;
    }

    /**
     * Preview what the next number would be without generating it
     *
     * @param string $documentType
     * @param string $prefix
     * @param string $resetPeriod
     * @param int $numberLength
     * @return string
     */
    public static function previewNextNumber(
        string $documentType,
        string $prefix,
        string $resetPeriod = 'yearly',
        int $numberLength = 6
    ): string {
        $currentNumber = self::getCurrentNumber($documentType, $resetPeriod);
        $nextNumber = $currentNumber + 1;
        return self::formatNumber($prefix, $nextNumber, $numberLength);
    }

    /**
     * Reset sequence to a specific number (use with caution!)
     *
     * @param string $documentType
     * @param int $number
     * @param string $resetPeriod
     * @return bool
     */
    public static function resetToNumber(
        string $documentType,
        int $number,
        string $resetPeriod = 'yearly'
    ): bool {
        $now = now();
        $year = $resetPeriod !== 'none' ? $now->year : null;
        $month = $resetPeriod === 'monthly' ? $now->month : null;

        $query = self::where('document_type', $documentType);

        if ($resetPeriod === 'monthly') {
            $query->where('year', $year)->where('month', $month);
        } elseif ($resetPeriod === 'yearly') {
            $query->where('year', $year)->whereNull('month');
        } else {
            $query->whereNull('year')->whereNull('month');
        }

        return $query->update(['last_number' => $number]) > 0;
    }
}
