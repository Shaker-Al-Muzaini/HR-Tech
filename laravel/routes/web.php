<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\CompletedInterviewsController;
use App\Http\Controllers\RecordingController;
use App\Models\Interview;
use App\Models\InterviewSession;

// ═══════════════════════════════════════════════════════════
// لوحة التحكم (HR)
// ═══════════════════════════════════════════════════════════
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('hr')->group(function () {

    // ─── CRUD المقابلات ───
    Route::post('/interviews', [InterviewController::class, 'store'])->name('hr.interviews.store');
    Route::put('/interviews/{id}', [InterviewController::class, 'update'])->name('hr.interviews.update');
    Route::delete('/interviews/{id}', [InterviewController::class, 'destroy'])->name('hr.interviews.destroy');
    Route::post('/interviews/{id}/cancel', [InterviewController::class, 'cancel'])->name('hr.interviews.cancel');
    Route::post('/interviews/{id}/reset', [InterviewController::class, 'resetSession'])->name('hr.interviews.reset');

    // ─── غرفة المقابلة الحية ───
    Route::get('/interview/{id}/session', function ($id) {
        $interview = Interview::with(['candidate'])->findOrFail($id);
        $session   = $interview->sessions()->orderByDesc('id')->first();

        if (! $session) {
            return Inertia::render('HR/LiveInterview', [
                'interviewId'     => (string) $id,
                'candidateToken'  => null,
                'candidateName'   => $interview->candidate?->name ?? 'غير معروف',
                'jobTitle'        => $interview->job_title ?? '-',
                'interviewStatus' => $interview->status,
                'fastapiUrl'      => env('FASTAPI_URL', 'http://localhost:8001'),
            ]);
        }

        // ═══ Route Guard: منع الدخول للغرف المنتهية ═══
        $blockedStatuses = ['completed', 'cancelled', 'expired'];
        if (in_array($interview->status, $blockedStatuses) ||
            in_array($session->status, ['completed', 'cancelled', 'failed'])) {
            return redirect()
                ->route('hr.completed.show', ['id' => $id])
                ->with('warning', 'لا يمكن الدخول — المقابلة انتهت');
        }

        return Inertia::render('HR/LiveInterview', [
            'interviewId'     => (string) $id,
            'candidateToken'  => $session->candidate_token,
            'candidateName'   => $interview->candidate?->name ?? 'غير معروف',
            'jobTitle'        => $interview->job_title ?? '-',
            'interviewStatus' => $interview->status,
            'fastapiUrl'      => env('FASTAPI_URL', 'http://localhost:8001'),
        ]);
    })->name('hr.interview.live');

    // ─── التقرير ───
    Route::get('/interview/{id}/report', [ReportController::class, 'show'])->name('hr.interview.report');
    Route::post('/interview/{id}/report/notes', [ReportController::class, 'saveNotes'])->name('hr.interview.report.save');

    // ─── سجل المقابلات المكتملة ───
    Route::get('/completed', [CompletedInterviewsController::class, 'index'])->name('hr.completed.index');
    Route::get('/completed/{id}', [CompletedInterviewsController::class, 'show'])->name('hr.completed.show');

    // ─── التسجيلات ───
    Route::get('/recording/{id}/stream', [RecordingController::class, 'stream'])->name('hr.recording.stream');
    Route::get('/recording/{id}/download', [RecordingController::class, 'download'])->name('hr.recording.download');
});

// ═══════════════════════════════════════════════════════════
// المرشح
// ═══════════════════════════════════════════════════════════
Route::get('/interview/{token}', function ($token) {
    $session = InterviewSession::with('interview')->where('candidate_token', $token)->first();

    if (! $session) {
        abort(404, 'رابط المقابلة غير صالح.');
    }

    // ═══ Route Guard: منع الدخول للغرف المنتهية ═══
    $blockedSessionStatuses = ['completed', 'cancelled', 'failed'];
    $blockedInterviewStatuses = ['completed', 'cancelled', 'expired'];

    $sessionBlocked   = in_array($session->status, $blockedSessionStatuses);
    $interviewBlocked = $session->interview &&
                        in_array($session->interview->status, $blockedInterviewStatuses);

    if ($sessionBlocked || $interviewBlocked) {
        return Inertia::render('Candidate/InterviewEnded', [
            'message' => 'هذه المقابلة قد انتهت بالفعل. شكراً لمشاركتك.',
        ]);
    }

    return Inertia::render('Candidate/InterviewRoom', [
        'token'       => $token,
        'sessionUuid' => $session->session_uuid,
        'language'    => $session->interview->language ?? 'ar',
    ]);
})->name('candidate.join');

// ═══════════════════════════════════════════════════════════
// API لتحديث حالة الجلسة (من المرشح)
// ═══════════════════════════════════════════════════════════
Route::post('/api/session/{token}/started', function ($token) {
    $session = InterviewSession::where('candidate_token', $token)->firstOrFail();

    $session->update([
        'status'              => 'active',
        'candidate_joined_at' => now(),
    ]);

    if ($session->interview) {
        $session->interview->update([
            'status'     => 'active',
            'started_at' => now(),
        ]);
    }

    return response()->json(['ok' => true]);
})->name('session.started');