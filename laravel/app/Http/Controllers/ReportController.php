<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function show($id)
    {
        $interview = Interview::with(['candidate', 'sessions.recording'])->findOrFail($id);

        $report = $this->reportService->build($interview);

        // ─── بيانات إضافية: التسجيل ───
        $session   = $interview->sessions()->orderByDesc('id')->first();
        $recording = $session?->recording;

        return Inertia::render('HR/InterviewReport', [
            'interview' => [
                'id'              => $interview->id,
                'job_title'       => $interview->job_title,
                'department'      => $interview->department,
                'status'          => $interview->status,
                'completion_type' => $interview->completion_type,
                'scheduled_at'    => $interview->scheduled_at?->toIso8601String(),
                'started_at'      => $interview->started_at?->toIso8601String(),
                'ended_at'        => $interview->ended_at?->toIso8601String(),
                'hr_notes'        => $interview->hr_notes,
                'final_decision'  => $interview->final_decision,
                'candidate'       => $interview->candidate ? [
                    'id'               => $interview->candidate->id,
                    'name'             => $interview->candidate->name,
                    'email'            => $interview->candidate->email,
                    'applied_position' => $interview->candidate->applied_position,
                ] : null,
            ],
            'session'   => $report['session'],
            'verdict'   => $report['verdict'],
            'stats'     => $report['stats'],
            'windows'   => $report['windows'],
            'recording' => $recording ? [
                'exists'          => true,
                'status'          => $recording->status,
                'human_duration'  => $recording->human_duration,
                'human_size'      => $recording->human_size,
            ] : [
                'exists' => false,
            ],
        ]);
    }

    public function saveNotes(Request $request, $id)
    {
        $interview = Interview::findOrFail($id);

        $validated = $request->validate([
            'hr_notes'       => 'nullable|string|max:5000',
            'final_decision' => 'nullable|in:accepted,rejected,under_review',
        ]);

        $interview->update($validated);

        return back()->with('success', 'تم حفظ القرار');
    }
}