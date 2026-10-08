<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();

        // Stats
        $stats = [
            'today_count' => Interview::whereDate('scheduled_at', $now->toDateString())
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->count(),
            'week_completed' => Interview::where('status', 'completed')
                ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
                ->count(),
            'pending_review' => Interview::where('status', 'completed')
                ->whereNull('final_decision')
                ->count(),
            'upcoming_count' => Interview::where('status', 'scheduled')
                ->where('scheduled_at', '>', $now)
                ->count(),
        ];

        // Filters
        $filter = $request->input('filter', 'all');
        $search = $request->input('search', '');
        $perPage = (int) $request->input('per_page', 5);

        $query = Interview::with(['candidate', 'sessions' => function ($q) {
            $q->orderByDesc('id');
        }]);

        if ($filter === 'today') {
            $query->whereDate('scheduled_at', $now->toDateString());
        } elseif ($filter === 'upcoming') {
            $query->where('status', 'scheduled')->where('scheduled_at', '>', $now);
        } elseif ($filter === 'completed') {
            $query->where('status', 'completed');
        } elseif ($filter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('candidate', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw("CASE status
            WHEN 'in_progress' THEN 1
            WHEN 'scheduled' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'cancelled' THEN 4
            ELSE 5 END")
            ->orderBy('scheduled_at', 'asc');

        $interviews = $query->paginate($perPage)->withQueryString();

        $interviews->getCollection()->transform(function ($interview) use ($now) {
            $session = $interview->sessions->first();

            // ═══ Status تلقائي حسب الوقت ═══
            $scheduled = $interview->scheduled_at;
            $duration = $interview->duration_minutes ?? 30;
            $endTime = $scheduled ? $scheduled->copy()->addMinutes($duration) : null;

            $displayStatus = 'scheduled';
            if ($interview->status === 'cancelled') {
                $displayStatus = 'cancelled';
            } elseif ($interview->status === 'completed') {
                $displayStatus = 'completed';
            } elseif ($interview->status === 'in_progress') {
                $displayStatus = 'in_progress';
            } elseif ($scheduled && $endTime) {
                if ($endTime->isPast()) {
                    $displayStatus = 'missed';
                } elseif ($scheduled->isPast() && !$endTime->isPast()) {
                    $displayStatus = 'starting_soon';
                } else {
                    $displayStatus = 'scheduled';
                }
            }

            return [
                'id' => $interview->id,
                'job_title' => $interview->job_title,
                'department' => $interview->department,
                'status' => $interview->status,
                'display_status' => $displayStatus,
                'scheduled_at' => $interview->scheduled_at?->toIso8601String(),
                'duration_minutes' => $interview->duration_minutes,
                'language' => $interview->language,
                'final_decision' => $interview->final_decision,
                'is_today' => $interview->scheduled_at && $interview->scheduled_at->isToday(),
                'is_past' => $interview->scheduled_at && $interview->scheduled_at->isPast(),
                'candidate' => $interview->candidate ? [
                    'id' => $interview->candidate->id,
                    'name' => $interview->candidate->name,
                    'email' => $interview->candidate->email,
                    'phone' => $interview->candidate->phone,
                    'applied_position' => $interview->candidate->applied_position,
                ] : null,
                'session' => $session ? [
                    'id' => $session->id,
                    'candidate_token' => $session->candidate_token,
                    'status' => $session->status,
                ] : null,
            ];
        });

        $counts = [
            'all' => Interview::count(),
            'today' => Interview::whereDate('scheduled_at', $now->toDateString())->count(),
            'upcoming' => Interview::where('status', 'scheduled')->where('scheduled_at', '>', $now)->count(),
            'completed' => Interview::where('status', 'completed')->count(),
            'cancelled' => Interview::where('status', 'cancelled')->count(),
        ];

        return Inertia::render('HR/Dashboard', [
            'stats' => $stats,
            'interviews' => $interviews,
            'counts' => $counts,
            'filters' => [
                'filter' => $filter,
                'search' => $search,
                'per_page' => $perPage,
            ],
            'candidates' => Candidate::orderBy('name')->get(['id', 'name', 'email', 'phone', 'applied_position']),
        ]);
    }
}