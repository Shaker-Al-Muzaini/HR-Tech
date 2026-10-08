<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\TimeWindow;

/**
 * ReportService — يحسب التقرير النهائي من time_windows
 * يُستخدم من ReportController و CompletedInterviewsController
 */
class ReportService
{
    /**
     * يبني التقرير الكامل للمقابلة المعطاة
     */
    public function build(Interview $interview): array
    {
        $session = $this->resolveSession($interview);

        if (! $session) {
            return $this->emptyReport();
        }

        $baseQuery    = TimeWindow::where('interview_session_id', $session->id);
        $totalWindows = (clone $baseQuery)->count();

        if ($totalWindows === 0) {
            return [
                'session'      => $this->serializeSession($session),
                'verdict'      => null,
                'stats'        => ['total_windows' => 0, 'duration' => 0],
                'windows'      => [],
            ];
        }

        // ─── متوسطات ───
        $duration          = (clone $baseQuery)->max('end_time_sec') ?? 0;
        $phoneAlerts       = (clone $baseQuery)->where('phone_detected', true)->count();
        $multiPersonAlerts = (clone $baseQuery)->where('person_count', '>', 1)->count();
        $lowQualityCount   = (clone $baseQuery)->where('overall_quality', 'low')->count();

        $avgPitchVariance = (clone $baseQuery)->avg('pitch_variance') ?? 0;
        $avgSpeechRate    = (clone $baseQuery)->avg('speech_rate') ?? 0;
        $avgPauseRatio    = (clone $baseQuery)->avg('pause_ratio') ?? 0;
        $avgAU4           = (clone $baseQuery)->avg('au4_brow_lowerer') ?? 0;
        $avgAU12          = (clone $baseQuery)->avg('au12_lip_corner_puller') ?? 0;
        $avgHeadMovement  = (clone $baseQuery)->avg('head_movement_variance') ?? 0;
        $avgSnr           = (clone $baseQuery)->avg('audio_snr_db') ?? 0;

        // ─── الدرجات ───
        $integrityScore = max(0, 100 - ($phoneAlerts * 20) - ($multiPersonAlerts * 25));

        $confidenceScore = 100;
        if ($avgPauseRatio > 0.6) $confidenceScore -= 25;
        elseif ($avgPauseRatio > 0.4) $confidenceScore -= 10;
        if ($avgSpeechRate < 0.4) $confidenceScore -= 20;
        if ($avgPitchVariance > 10000) $confidenceScore -= 15;
        $confidenceScore = max(0, min(100, $confidenceScore));

        $composureScore = 100;
        if ($avgAU4 > 0.8) $composureScore -= 20;
        elseif ($avgAU4 > 0.6) $composureScore -= 10;
        if ($avgAU12 < 0.3) $composureScore -= 15;
        if ($avgHeadMovement > 0.005) $composureScore -= 15;
        $composureScore = max(0, min(100, $composureScore));

        $qualityScore = 100;
        if ($avgSnr < 20) $qualityScore -= 20;
        elseif ($avgSnr < 35) $qualityScore -= 10;
        $qualityScore -= $lowQualityCount * 5;
        $qualityScore = max(0, min(100, $qualityScore));

        $overallScore = round(
            ($integrityScore * 0.4) +
            ($confidenceScore * 0.25) +
            ($composureScore * 0.2) +
            ($qualityScore * 0.15)
        );

        $verdict = [
            'overall_score'  => $overallScore,
            'recommendation' => $this->getRecommendation($overallScore),
            'integrity' => [
                'score'  => $integrityScore,
                'status' => $this->getIntegrityStatus($integrityScore),
                'label'  => $this->getIntegrityLabel($integrityScore, $phoneAlerts, $multiPersonAlerts),
                'details' => [
                    'phone_alerts'        => $phoneAlerts,
                    'multi_person_alerts' => $multiPersonAlerts,
                ],
            ],
            'confidence' => [
                'score'  => $confidenceScore,
                'status' => $this->getScoreStatus($confidenceScore),
                'label'  => $this->getConfidenceLabel($confidenceScore, $avgPauseRatio, $avgSpeechRate),
            ],
            'composure' => [
                'score'  => $composureScore,
                'status' => $this->getScoreStatus($composureScore),
                'label'  => $this->getComposureLabel($composureScore, $avgAU4, $avgAU12),
            ],
            'quality' => [
                'score'  => $qualityScore,
                'status' => $this->getScoreStatus($qualityScore),
                'label'  => $qualityScore >= 80 ? 'جودة تسجيل ممتازة'
                          : ($qualityScore >= 60 ? 'جودة تسجيل مقبولة' : 'جودة تسجيل منخفضة'),
            ],
            'notes' => $this->generateNotes([
                'phone_alerts'        => $phoneAlerts,
                'multi_person_alerts' => $multiPersonAlerts,
                'avg_pause_ratio'     => $avgPauseRatio,
                'avg_speech_rate'     => $avgSpeechRate,
                'avg_au4'             => $avgAU4,
                'avg_au12'            => $avgAU12,
                'avg_pitch_variance'  => $avgPitchVariance,
            ]),
        ];

        $windows = TimeWindow::where('interview_session_id', $session->id)
            ->orderBy('window_index')
            ->get([
                'id', 'window_index', 'start_time_sec', 'end_time_sec',
                'pitch_mean_hz', 'speech_rate',
                'au1_inner_brow_raise', 'au4_brow_lowerer', 'au12_lip_corner_puller',
                'phone_detected', 'person_count', 'overall_quality',
            ]);

        return [
            'session' => $this->serializeSession($session),
            'verdict' => $verdict,
            'stats'   => [
                'total_windows' => $totalWindows,
                'duration'      => round($duration),
            ],
            'windows' => $windows,
        ];
    }

    /**
     * يختار الجلسة الأفضل لعرض التقرير
     */
    public function resolveSession(Interview $interview)
    {
        $session = $interview->sessions()
            ->whereHas('timeWindows')
            ->orderByDesc('id')
            ->first();

        return $session ?: $interview->sessions()->orderByDesc('id')->first();
    }

    private function serializeSession($session): ?array
    {
        if (! $session) {
            return null;
        }
        return [
            'id'                  => $session->id,
            'status'              => $session->status,
            'candidate_token'     => $session->candidate_token,
            'candidate_joined_at' => $session->candidate_joined_at?->toIso8601String(),
            'ended_at'            => $session->ended_at?->toIso8601String(),
            'window_count'        => $session->window_count,
        ];
    }

    private function emptyReport(): array
    {
        return [
            'session' => null,
            'verdict' => null,
            'stats'   => ['total_windows' => 0, 'duration' => 0],
            'windows' => [],
        ];
    }

    // ─── Helpers ───

    private function getRecommendation($score): array
    {
        if ($score >= 85) return ['label' => 'مرشح موصى به', 'color' => 'green', 'icon' => '✅'];
        if ($score >= 70) return ['label' => 'مرشح مقبول', 'color' => 'blue', 'icon' => '👍'];
        if ($score >= 55) return ['label' => 'يحتاج مراجعة', 'color' => 'yellow', 'icon' => '⚠️'];
        return ['label' => 'غير موصى به', 'color' => 'red', 'icon' => '❌'];
    }

    private function getScoreStatus($score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'warning';
        return 'danger';
    }

    private function getIntegrityStatus($score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 50) return 'warning';
        return 'danger';
    }

    private function getIntegrityLabel($score, $phone, $multi): string
    {
        if ($phone === 0 && $multi === 0) return 'نزاهة كاملة — لا مخالفات';
        if ($phone > 0 && $multi > 0) return "⚠️ مخالفات: هاتف ($phone) وأشخاص ($multi)";
        if ($phone > 0) return "⚠️ تم كشف هاتف $phone مرة";
        return "⚠️ تم كشف شخص إضافي $multi مرة";
    }

    private function getConfidenceLabel($score, $pauseRatio, $speechRate): string
    {
        if ($score >= 80) return 'ثقة عالية — يتحدث بوضوح وبثبات';
        if ($score >= 60) return 'ثقة مقبولة — بعض التردد';
        if ($pauseRatio > 0.6) return 'تردد ملحوظ — فترات صمت طويلة';
        if ($speechRate < 0.4) return 'تردد — يتحدث ببطء شديد';
        return 'يحتاج تحسين الثقة';
    }

    private function getComposureLabel($score, $au4, $au12): string
    {
        if ($score >= 80) return 'اتزان انفعالي جيد — هادئ ومبتسم';
        if ($au4 > 0.8) return 'توتر ملحوظ — تعبيرات قلق';
        if ($au12 < 0.3) return 'برود عاطفي — قليل الابتسام';
        return 'اتزان متوسط';
    }

    private function generateNotes(array $data): array
    {
        $notes = [];

        if ($data['phone_alerts'] > 0) {
            $notes[] = ['type' => 'danger', 'text' => "تم كشف هاتف محمول {$data['phone_alerts']} مرة — احتمال غش"];
        }
        if ($data['multi_person_alerts'] > 0) {
            $notes[] = ['type' => 'danger', 'text' => "شخص إضافي في الصورة {$data['multi_person_alerts']} مرة"];
        }
        if ($data['avg_pause_ratio'] > 0.6) {
            $notes[] = ['type' => 'warning', 'text' => "نسبة صمت عالية (" . round($data['avg_pause_ratio'] * 100) . "%) — قد يدل على تردد"];
        }
        if ($data['avg_speech_rate'] < 0.4) {
            $notes[] = ['type' => 'warning', 'text' => "معدل كلام منخفض — تحدث ببطء"];
        }
        if ($data['avg_au4'] > 0.8) {
            $notes[] = ['type' => 'warning', 'text' => "توتر في تعبيرات الوجه (AU4) — قلق محتمل"];
        }
        if ($data['avg_pitch_variance'] > 12000) {
            $notes[] = ['type' => 'info', 'text' => "تقلب عالٍ في نبرة الصوت — قد يدل على توتر"];
        }
        if (empty($notes)) {
            $notes[] = ['type' => 'success', 'text' => "لا ملاحظات سلبية — مقابلة هادئة وطبيعية"];
        }

        return $notes;
    }
}