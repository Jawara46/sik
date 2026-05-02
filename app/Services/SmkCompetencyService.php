<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SmkRecord;
use Illuminate\Support\Facades\DB;

class SmkCompetencyService
{
    /**
     * Calculate and update the final UKK score based on Internal (30%) and External (70%) scores.
     * Generates a status label automatically if not explicitly provided.
     * 
     * @param array<string, mixed> $payload
     */
    public function calculateAndUpdateSmkRecord(int $studentId, array $payload): SmkRecord
    {
        return DB::transaction(function () use ($studentId, $payload) {
            $record = SmkRecord::query()->firstOrNew(['student_id' => $studentId]);
            
            $record->company_name = $payload['company_name'] ?? $record->company_name;
            $record->pkl_score = isset($payload['pkl_score']) ? (float) $payload['pkl_score'] : $record->pkl_score;
            
            // Note: We're keeping internal/external columns intact horizontally
            // $record->ukk_internal_score = $payload['ukk_internal_score'] ?? $record->ukk_internal_score;
            // $record->ukk_external_score = $payload['ukk_external_score'] ?? $record->ukk_external_score;

            // Make sure record is saved before attaching units
            $record->save();
            
            $finalScore = null;
            
            if (isset($payload['unit_scores']) && is_array($payload['unit_scores'])) {
                $totalScore = 0;
                $validUnits = 0;
                
                foreach ($payload['unit_scores'] as $unitId => $scoreStr) {
                    $score = trim((string)$scoreStr) === '' ? null : (float) $scoreStr;
                    
                    \App\Models\SmkRecordUnit::updateOrCreate(
                        [
                            'smk_record_id' => $record->id,
                            'smk_unit_id' => $unitId,
                        ],
                        [
                            'score' => $score
                        ]
                    );
                    
                    if ($score !== null) {
                        $totalScore += $score;
                        $validUnits++;
                    }
                }
                
                if ($validUnits > 0) {
                    $finalScore = round($totalScore / $validUnits, 2);
                    $record->ukk_final_score = $finalScore;
                }
            } else {
                // Keep the old final score if unit_scores is not passed but we still need status generation
                $finalScore = $record->ukk_final_score;
            }

            $status = !empty($payload['ukk_status']) ? $payload['ukk_status'] : null;
            
            // Auto-generate status if finalScore is available and status is not manually forced
            if (empty($status) && $finalScore !== null) {
                if ($finalScore >= 90) {
                    $status = 'Sangat Kompeten';
                } elseif ($finalScore >= 75) {
                    $status = 'Kompeten';
                } else {
                    $status = 'Tidak Kompeten';
                }
            }
            
            if ($status !== null) {
                $record->ukk_status = $status;
            }
            
            $record->save();
            
            return $record;
        });
    }
}
