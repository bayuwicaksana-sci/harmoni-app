<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;
use App\Exceptions\SequenceGeneratorException;

class SequenceGenerator
{
    /**
     * Generate next sequence number for a document type
     */
    public function generateNextId(string $documentType): string
    {
        return DB::transaction(function () use ($documentType) {
            // Lock the sequence row to prevent concurrent updates
            $sequence = DocumentSequence::where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                throw new SequenceGeneratorException("Sequence not found for document type: {$documentType}");
            }

            // Increment the sequence
            $sequence->last_number++;

            // Build the ID
            $id = $this->buildId($sequence);

            // Save the updated sequence
            $sequence->save();

            return $id;
        }, 5); // Retry up to 5 times on deadlock
    }

    /**
     * Build the formatted ID
     */
    protected function buildId(DocumentSequence $sequence): string
    {
        $parts = [$sequence->prefix];

        // Add year if configured
        if ($sequence->use_year) {
            $year = $sequence->year ?? now()->year;
            $parts[] = $year;
        }

        // Add padded number
        $paddedNumber = str_pad(
            $sequence->last_number,
            $sequence->min_digits,
            '0',
            STR_PAD_LEFT
        );
        $parts[] = $paddedNumber;

        return implode('-', $parts);
    }

    /**
     * Reset yearly sequences (call this from a scheduled job)
     */
    public function resetYearlySequences(): void
    {
        $currentYear = now()->year;

        DocumentSequence::where('use_year', true)
            ->where(function ($query) use ($currentYear) {
                $query->whereNull('year')
                    ->orWhere('year', '<', $currentYear);
            })
            ->update([
                'year' => $currentYear,
                'last_number' => 0
            ]);
    }

    /**
     * Get current sequence info without incrementing
     */
    public function getCurrentSequenceInfo(string $documentType): array
    {
        $sequence = DocumentSequence::where('document_type', $documentType)->first();

        if (!$sequence) {
            throw new SequenceGeneratorException("Sequence not found for document type: {$documentType}");
        }

        return [
            'current_number' => $sequence->last_number,
            'next_number' => $sequence->last_number + 1,
            'prefix' => $sequence->prefix,
            'use_year' => $sequence->use_year,
            'year' => $sequence->year
        ];
    }
}
