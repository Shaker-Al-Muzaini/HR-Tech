<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompletedInterviewsController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * قائمة المقابلات المكتملة
     */
    public function index(Request $request)
    {
        $search         = $request->input('search', '');
        $completionType = $request->input('completion_type', 'all');
        $decision       = $request->input('decision', 'all');
        $dateFrom       = $request->input('date_from');
        $dateTo         = $request->input('date_to');
        $perPage        = (int) $request->input('per_page', 12);

        $query = Interview::with([
            'candidate',
            'sessions' => fn ($q) => $q->orderByDesc('id'),
            'sessions.recording',
        ])->where('status', 'completed');

        // ─── فلترة حسب نوع الاكتمال ───
        if ($completionType !== 'all') {
            $query->where('completion_type', $completionType);
        }

        // ─── فلترة حسب القرار ───
        if ($decision !== 'all') {
            if ($decision === 'pending') {
                $query->whereNull('final_decision');
            } else {
                $query->where('final_decision', $decision);
            }
        }

        // ─── فلترة حسب التاريخ ───
        if ($dateFrom) {
            $query->whereDate('ended_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('ended_at', '<=', $dateTo);
        }

        // ─── البحث ───
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('candidate', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        $interviews = $query
            ->orderByDesc('ended_at')
            ->paginate($perPage)
            ->withQueryString();

        // ─── تحويل البيانات ───
        $interviews->getCollection()->transform(function ($interview) {
            $session   = $interview->sessions->first();
            $recording = $session?->recording;

            // ─── حساب التقرير (خفيف — بدون windows) ───
            $report    = $this->reportService->build($interview);
            $verdict   = $report['verdict'];

            return [
                'id'              => $interview->id,
                'job_title'       => $interview->job_title,
                'department'      => $interview->department,
                'completion_type' => $interview->completion_type,
                'scheduled_at'    => $interview->scheduled_at?->toIso8601String(),
                'started_at'      => $interview->started_at?->toIso8601String(),
                'ended_at'        => $interview->ended_at?->toIso8601String(),
                'duration_minutes'=> $interview->duration_minutes,
                'final_decision'  => $interview->final_decision,
                'candidate'       => $interview->candidate ? [
                    'id'    => $interview->candidate->id,
                    'name'  => $interview->candidate->name,
                    'email' => $interview->candidate->email,
                ] : null,
                'session' => $session ? [
                    'id'            => $session->id,
                    'window_count'  => $session->window_count,
                ] : null,
                'recording' => $recording ? [
                    'exists'         => true,
                    'status'         => $recording->status,
                    'human_duration' => $recording->human_duration,
                ] : ['exists' => false],
                'overall_score'   => $verdict['overall_score'] ?? null,
                'recommendation'  => $verdict['recommendation'] ?? null,
            ];
        });

        // ─── الإحصائيات ───
        $counts = [
            'all'            => Interview::where('status', 'completed')->count(),
            'ended_by_hr'    => Interview::where('status', 'completed')->where('completion_type', 'ended_by_hr')->count(),
            'time_expired'   => Interview::where('status', 'completed')->where('completion_type', 'time_expired')->count(),
            'candidate_left' => Interview::where('status', 'completed')->where('completion_type', 'candidate_left')->count(),
            'pending'        => Interview::where('status', 'completed')->whereNull('final_decision')->count(),
            'accepted'       => Interview::where('status', 'completed')->where('final_decision', 'accepted')->count(),
            'rejected'       => Interview::where('status', 'completed')->where('final_decision', 'rejected')->count(),
        ];

        return Inertia::render('HR/CompletedInterviews', [
            'interviews' => $interviews,
            'counts'     => $counts,
            'filters'    => [
                'search'          => $search,
                'completion_type' => $completionType,
                'decision'        => $decision,
                'date_from'       => $dateFrom,
                'date_to'         => $dateTo,
                'per_page'        => $perPage,
            ],
        ]);
    }

    /**
     * تفاصيل مقابلة مكتملة
     */
    public function show($id)
    {
        $interview = Interview::with(['candidate', 'sessions.recording'])->findOrFail($id);

        if ($interview->status !== 'completed') {
            return redirect()
                ->route('hr.interview.report', ['id' => $id])
                ->with('warning', 'هذه المقابلة لم تكتمل بعد');
        }

        $report    = $this->reportService->build($interview);
        $session   = $interview->sessions()->orderByDesc('id')->first();
        $recording = $session?->recording;

        return Inertia::render('HR/CompletedInterviewDetail', [
            'interview' => [
                'id'              => $interview->id,
                'job_title'       => $interview->job_title,
                'department'      => $interview->department,
                'status'          => $interview->status,
                'completion_type' => $interview->completion_type,
                'scheduled_at'    => $interview->scheduled_at?->toIso8601String(),
                'started_at'      => $interview->started_at?->toIso8601String(),
                'ended_at'        => $interview->ended_at?->toIso8601String(),
                'expired_at'      => $interview->expired_at?->toIso8601String(),
                'duration_minutes'=> $interview->duration_minutes,
                'hr_notes'        => $interview->hr_notes,
                'final_decision'  => $interview->final_decision,
                'candidate'       => $interview->candidate ? [
                    'id'               => $interview->candidate->id,
                    'name'             => $interview->candidate->name,
                    'email'            => $interview->candidate->email,
                    'phone'            => $interview->candidate->phone,
                    'applied_position' => $interview->candidate->applied_position,
                ] : null,
            ],
            'session'   => $report['session'],
            'verdict'   => $report['verdict'],
            'stats'     => $report['stats'],
            'windows'   => $report['windows'],
            'recording' => $recording ? [
                'exists'         => true,
                'status'         => $recording->status,
                'human_duration' => $recording->human_duration,
                'human_size'     => $recording->human_size,
                'stream_url'     => route('hr.recording.stream', ['id' => $recording->id]),
                'download_url'   => route('hr.recording.download', ['id' => $recording->id]),
            ] : ['exists' => false],
        ]);
    }
}