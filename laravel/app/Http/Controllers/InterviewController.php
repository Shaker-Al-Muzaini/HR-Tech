<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Interview;
use App\Models\InterviewSession;
use App\Models\TimeWindow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InterviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'candidate_id'      => 'nullable|exists:candidates,id',
            'candidate_name'    => 'required_without:candidate_id|string|max:100',
            'candidate_email'   => 'required_without:candidate_id|email|max:150',
            'candidate_phone'   => 'nullable|string|max:20',
            'candidate_position'=> 'nullable|string|max:100',
            'job_title'         => 'required|string|max:150',
            'department'        => 'nullable|string|max:100',
            'scheduled_at'      => 'required|date',
            'duration_minutes'  => 'required|integer|min:15|max:180',
            'language'          => 'required|in:ar,en',
        ]);

        DB::beginTransaction();
        try {
            if (!empty($validated['candidate_id'])) {
                $candidate = Candidate::findOrFail($validated['candidate_id']);
            } else {
                $candidate = Candidate::create([
                    'name'             => $validated['candidate_name'],
                    'email'            => $validated['candidate_email'],
                    'phone'            => $validated['candidate_phone'] ?? null,
                    'applied_position' => $validated['candidate_position'] ?? $validated['job_title'],
                ]);
            }

            $interview = Interview::create([
                'candidate_id'      => $candidate->id,
                'hr_user_id'        => auth()->id() ?? 1,
                'job_title'         => $validated['job_title'],
                'department'        => $validated['department'] ?? null,
                'status'            => 'scheduled',
                'language'          => $validated['language'],
                'scheduled_at'      => Carbon::parse($validated['scheduled_at']),
                'duration_minutes'  => $validated['duration_minutes'],
            ]);

            InterviewSession::create([
                'interview_id' => $interview->id,
                'status'       => 'pending',
            ]);

            DB::commit();
            return redirect()->route('dashboard')->with('success', 'تم إنشاء المقابلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $interview = Interview::findOrFail($id);

        // ⚠️ الحالة مستبعدة — لا يتم تعديلها يدوياً
        $validated = $request->validate([
            'job_title'        => 'sometimes|required|string|max:150',
            'department'       => 'nullable|string|max:100',
            'scheduled_at'     => 'sometimes|required|date',
            'duration_minutes' => 'sometimes|required|integer|min:15|max:180',
            'language'         => 'sometimes|required|in:ar,en',
            'candidate_name'   => 'sometimes|required|string|max:100',
            'candidate_email'  => 'sometimes|required|email|max:150',
            'candidate_phone'  => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['scheduled_at'])) {
                $validated['scheduled_at'] = Carbon::parse($validated['scheduled_at']);
            }

            $interview->update($validated);

            if ($interview->candidate) {
                $candidateData = [];
                if (isset($validated['candidate_name'])) $candidateData['name'] = $validated['candidate_name'];
                if (isset($validated['candidate_email'])) $candidateData['email'] = $validated['candidate_email'];
                if (array_key_exists('candidate_phone', $validated)) $candidateData['phone'] = $validated['candidate_phone'];
                if (!empty($candidateData)) $interview->candidate->update($candidateData);
            }

            DB::commit();
            return redirect()->route('dashboard')->with('success', 'تم تحديث المقابلة');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $interview = Interview::with('sessions')->findOrFail($id);

        DB::beginTransaction();
        try {
            foreach ($interview->sessions as $session) {
                TimeWindow::where('interview_session_id', $session->id)->delete();
                $session->delete();
            }
            $interview->delete();
            DB::commit();
            return redirect()->route('dashboard')->with('success', 'تم حذف المقابلة');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * إلغاء مقابلة (الطريقة الوحيدة لتغيير الحالة يدوياً)
     */
    public function cancel($id)
    {
        $interview = Interview::findOrFail($id);
        $interview->update(['status' => 'cancelled']);
        return redirect()->route('dashboard')->with('success', 'تم إلغاء المقابلة');
    }

    public function resetSession($id)
    {
        $interview = Interview::with('sessions')->findOrFail($id);
        $session = $interview->sessions()->latest('id')->first();

        if (!$session) {
            return redirect()->back()->withErrors(['error' => 'لا توجد جلسة']);
        }

        DB::beginTransaction();
        try {
            TimeWindow::where('interview_session_id', $session->id)->delete();
            $session->update([
                'status'                    => 'pending',
                'ended_at'                  => null,
                'window_count'              => 0,
                'candidate_joined_at'       => null,
                'calibration_completed_at'  => null,
            ]);
            $interview->update([
                'status'         => 'scheduled',
                'started_at'     => null,
                'ended_at'       => null,
                'hr_notes'       => null,
                'final_decision' => null,
            ]);
            DB::commit();
            return redirect()->route('dashboard')->with('success', 'تم إعادة تعيين الجلسة');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}