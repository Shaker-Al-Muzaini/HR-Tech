<script setup>
import { ref, reactive, computed } from "vue";
import { router } from "@inertiajs/vue3";
import HRLayout from "@/Layouts/HRLayout.vue";
import { useToast } from "@/composables/useToast";

defineOptions({ layout: HRLayout });

const props = defineProps({
  interview: Object,
  session: Object,
  verdict: Object,
  stats: Object,
  windows: Array,
  recording: Object,
});

const toast = useToast();

const form = reactive({
  hr_notes: props.interview?.hr_notes || "",
  final_decision: props.interview?.final_decision || "",
});

const decisions = [
  { value: "accepted",     label: "✅ قبول",         activeClass: "border-green-500 bg-green-50 text-green-800" },
  { value: "under_review", label: "⏸️ قيد المراجعة",  activeClass: "border-yellow-500 bg-yellow-50 text-yellow-800" },
  { value: "rejected",     label: "❌ رفض",          activeClass: "border-red-500 bg-red-50 text-red-800" },
];

const saving = ref(false);

// ═══════════════════════════════════════════════════════════
// Computed
// ═══════════════════════════════════════════════════════════
const scoreColor = computed(() => {
  if (!props.verdict) return "#6b7280";
  const c = props.verdict.recommendation.color;
  return { green: "#10b981", blue: "#3b82f6", yellow: "#f59e0b", red: "#ef4444" }[c] || "#6b7280";
});

const scoreTextColor = computed(() => {
  if (!props.verdict) return "text-gray-600";
  const c = props.verdict.recommendation.color;
  return { green: "text-green-600", blue: "text-blue-600", yellow: "text-yellow-600", red: "text-red-600" }[c] || "text-gray-600";
});

const completionTypeLabel = computed(() => ({
  ended_by_hr: "أنهاها المشرف",
  time_expired: "انتهى الوقت المحدد",
  candidate_left: "غادر المرشح",
}[props.interview?.completion_type] || "—"));

const completionTypeBadge = computed(() => ({
  ended_by_hr: "bg-emerald-100 text-emerald-800",
  time_expired: "bg-amber-100 text-amber-800",
  candidate_left: "bg-slate-100 text-slate-700",
}[props.interview?.completion_type] || "bg-gray-100 text-gray-800"));

const formatDateTime = (iso) => {
  if (!iso) return "—";
  return new Date(iso).toLocaleString("ar-EG", {
    day: "numeric", month: "long", year: "numeric",
    hour: "2-digit", minute: "2-digit",
  });
};

// ═══════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════
const statusBadge = (s) => ({
  excellent: "bg-green-100 text-green-800",
  good: "bg-blue-100 text-blue-800",
  warning: "bg-yellow-100 text-yellow-800",
  danger: "bg-red-100 text-red-800",
}[s] || "bg-gray-100 text-gray-800");

const borderColor = (s) => ({
  excellent: "border-green-500",
  good: "border-blue-500",
  warning: "border-yellow-500",
  danger: "border-red-500",
}[s] || "border-gray-300");

const scoreTextColorByStatus = (s) => ({
  excellent: "text-green-600",
  good: "text-blue-600",
  warning: "text-yellow-600",
  danger: "text-red-600",
}[s] || "text-gray-600");

const noteClass = (t) => ({
  success: "bg-green-50 border-r-4 border-green-500 text-green-800",
  info:    "bg-blue-50 border-r-4 border-blue-500 text-blue-800",
  warning: "bg-yellow-50 border-r-4 border-yellow-500 text-yellow-800",
  danger:  "bg-red-50 border-r-4 border-red-500 text-red-800",
}[t] || "bg-gray-50 text-gray-800");

const noteIcon = (t) => ({ success: "✅", info: "ℹ️", warning: "⚠️", danger: "🚨" }[t] || "ℹ️");

const num = (val, d = 2) => {
  if (val === null || val === undefined) return "-";
  const n = Number(val);
  return isNaN(n) ? "-" : n.toFixed(d);
};

const qualityLabel = (q) => ({ excellent: "ممتازة", acceptable: "مقبولة", low: "منخفضة" }[q] || "-");

const qualityClass = (q) => ({
  excellent: "bg-green-100 text-green-800",
  acceptable: "bg-yellow-100 text-yellow-800",
  low: "bg-red-100 text-red-800",
}[q] || "bg-gray-100 text-gray-800");

// ═══════════════════════════════════════════════════════════
// Save Notes → Toast + Redirect (الميزة 2)
// ═══════════════════════════════════════════════════════════
const saveNotes = async () => {
  saving.value = true;

  router.post(`/hr/interview/${props.interview.id}/report/notes`, form, {
    preserveScroll: true,
    onSuccess: () => {
      saving.value = false;
      toast.success("تم حفظ القرار بنجاح");
      // تحويل تلقائي إلى لوحة التحكم بعد 1.2 ثانية
      setTimeout(() => router.visit("/"), 1200);
    },
    onError: () => {
      saving.value = false;
      toast.error("فشل حفظ القرار — حاول مرة أخرى");
    },
  });
};
</script>

<template>
  <div class="space-y-5">

    <!-- ═══ Header ═══ -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">تقرير المقابلة</h1>
        <p class="text-sm text-gray-500 mt-1">
          <span v-if="interview.candidate?.name">المرشح: {{ interview.candidate.name }}</span>
          <span v-if="interview.job_title" class="mx-2">|</span>
          <span v-if="interview.job_title">الوظيفة: {{ interview.job_title }}</span>
        </p>
      </div>

      <div class="flex items-center gap-2">
        <a href="/hr/completed"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 font-bold text-xs rounded-xl border border-gray-200 hover:bg-gray-50 transition-all active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
          سجل المقابلات
        </a>
        <a href="/"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          لوحة التحكم
        </a>
      </div>
    </div>

    <!-- ═══ Interview Meta Card ═══ -->
    <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-4">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div>
          <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider mb-1">نوع الاكتمال</p>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold" :class="completionTypeBadge">
            {{ completionTypeLabel }}
          </span>
        </div>
        <div>
          <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider mb-1">بدأت</p>
          <p class="font-semibold text-gray-900 text-xs">{{ formatDateTime(interview.started_at) }}</p>
        </div>
        <div>
          <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider mb-1">انتهت</p>
          <p class="font-semibold text-gray-900 text-xs">{{ formatDateTime(interview.ended_at) }}</p>
        </div>
        <div>
          <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider mb-1">المدة المخططة</p>
          <p class="font-semibold text-gray-900 text-xs">{{ interview.duration_minutes }} دقيقة</p>
        </div>
      </div>
    </div>

    <!-- ═══ Recording (Placeholder — الميزة 5) ═══ -->
    <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-l from-slate-50 to-white flex items-center justify-between">
        <h3 class="font-bold text-gray-900 flex items-center gap-2">
          <span class="w-1 h-4 bg-gradient-to-b from-red-500 to-pink-500 rounded-full"></span>
          🎥 تسجيل المقابلة
        </h3>
        <span v-if="recording?.exists && recording.status === 'ready'"
              class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full">
          جاهز — {{ recording.human_duration }} • {{ recording.human_size }}
        </span>
      </div>

      <div v-if="recording?.exists && recording.status === 'ready'" class="aspect-video bg-black">
        <video controls class="w-full h-full" :src="recording.stream_url"></video>
      </div>

      <div v-else class="p-12 text-center">
        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
          <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
          </svg>
        </div>
        <p class="text-gray-600 font-semibold mb-1">لا يوجد تسجيل متاح</p>
        <p class="text-xs text-gray-400">سيتم توفير ميزة التسجيل في تحديث قادم</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         No Data State
         ═══════════════════════════════════════════════════════ -->
    <div v-if="!verdict"
         class="bg-white rounded-2xl shadow-sm p-12 text-center border-2 border-dashed border-gray-200">
      <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-700 mb-3">لا توجد بيانات تحليلية</h2>
      <p class="text-gray-500 mb-8 max-w-md mx-auto">
        لم تُجرَ هذه المقابلة فعلياً، أو لم يتم تسجيل أي بيانات تحليلية.
      </p>
      <a href="/"
         class="inline-flex items-center gap-2 px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
        🏠 العودة للوحة التحكم
      </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         Report Content
         ═══════════════════════════════════════════════════════ -->
    <template v-else>

      <!-- Final Score Card -->
      <div class="bg-white rounded-2xl shadow-sm p-8 flex flex-col md:flex-row items-center gap-8">
        <div class="relative w-40 h-40 flex-shrink-0">
          <svg class="w-40 h-40 transform -rotate-90" viewBox="0 0 160 160">
            <circle cx="80" cy="80" r="70" stroke="#e5e7eb" stroke-width="12" fill="none" />
            <circle cx="80" cy="80" r="70" :stroke="scoreColor" stroke-width="12" fill="none" stroke-linecap="round" :stroke-dasharray="`${(verdict.overall_score / 100) * 440} 440`" />
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-5xl font-bold" :class="scoreTextColor">{{ verdict.overall_score }}</span>
            <span class="text-sm text-gray-500">من 100</span>
          </div>
        </div>

        <div class="flex-1 text-center md:text-right">
          <div class="flex items-center justify-center md:justify-start gap-3 mb-3">
            <span class="text-4xl">{{ verdict.recommendation.icon }}</span>
            <h2 class="text-3xl font-bold" :class="scoreTextColor">{{ verdict.recommendation.label }}</h2>
          </div>
          <p class="text-gray-600 mb-4">
            بناءً على تحليل {{ stats.total_windows }} نافذة زمنية، ومدة {{ stats.duration }} ثانية
          </p>

          <div class="flex flex-wrap gap-3 justify-center md:justify-start">
            <span class="px-4 py-2 rounded-full text-sm font-bold" :class="statusBadge(verdict.integrity.status)">🛡️ النزاهة: {{ verdict.integrity.score }}</span>
            <span class="px-4 py-2 rounded-full text-sm font-bold" :class="statusBadge(verdict.confidence.status)">🎯 الثقة: {{ verdict.confidence.score }}</span>
            <span class="px-4 py-2 rounded-full text-sm font-bold" :class="statusBadge(verdict.composure.status)">😌 الاتزان: {{ verdict.composure.score }}</span>
            <span class="px-4 py-2 rounded-full text-sm font-bold" :class="statusBadge(verdict.quality.status)">📡 الجودة: {{ verdict.quality.score }}</span>
          </div>
        </div>
      </div>

      <!-- Detailed Verdicts -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-6 border-r-4" :class="borderColor(verdict.integrity.status)">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">🛡️ النزاهة</h3>
            <span class="text-3xl font-bold" :class="scoreTextColorByStatus(verdict.integrity.status)">{{ verdict.integrity.score }}</span>
          </div>
          <p class="text-gray-700 mb-3">{{ verdict.integrity.label }}</p>
          <div class="flex gap-3 text-sm">
            <span v-if="verdict.integrity.details.phone_alerts > 0" class="px-3 py-1 bg-red-100 text-red-700 rounded-full font-bold">📱 {{ verdict.integrity.details.phone_alerts }} هاتف</span>
            <span v-if="verdict.integrity.details.multi_person_alerts > 0" class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full font-bold">👥 {{ verdict.integrity.details.multi_person_alerts }} شخص</span>
            <span v-if="verdict.integrity.details.phone_alerts === 0 && verdict.integrity.details.multi_person_alerts === 0" class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-bold">✓ لا مخالفات</span>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-r-4" :class="borderColor(verdict.confidence.status)">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">🎯 الثقة</h3>
            <span class="text-3xl font-bold" :class="scoreTextColorByStatus(verdict.confidence.status)">{{ verdict.confidence.score }}</span>
          </div>
          <p class="text-gray-700">{{ verdict.confidence.label }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-r-4" :class="borderColor(verdict.composure.status)">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">😌 الاتزان الانفعالي</h3>
            <span class="text-3xl font-bold" :class="scoreTextColorByStatus(verdict.composure.status)">{{ verdict.composure.score }}</span>
          </div>
          <p class="text-gray-700">{{ verdict.composure.label }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-r-4" :class="borderColor(verdict.quality.status)">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">📡 جودة التسجيل</h3>
            <span class="text-3xl font-bold" :class="scoreTextColorByStatus(verdict.quality.status)">{{ verdict.quality.score }}</span>
          </div>
          <p class="text-gray-700">{{ verdict.quality.label }}</p>
        </div>
      </div>

      <!-- Notes -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-lg mb-4 flex items-center gap-2">📝 ملاحظات النظام</h3>
        <ul class="space-y-2">
          <li v-for="(note, idx) in verdict.notes" :key="idx" class="p-3 rounded-lg flex items-start gap-3" :class="noteClass(note.type)">
            <span class="text-xl">{{ noteIcon(note.type) }}</span>
            <span class="font-medium">{{ note.text }}</span>
          </li>
        </ul>
      </div>

      <!-- Technical Details -->
      <details class="bg-white rounded-xl shadow-sm overflow-hidden">
        <summary class="px-6 py-4 cursor-pointer hover:bg-gray-50 font-bold text-gray-700 flex items-center gap-2">
          📊 عرض التفاصيل التقنية (للمراجعة المتقدمة)
        </summary>
        <div class="p-6 border-t bg-gray-50">
          <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs bg-white rounded">
              <thead class="bg-gray-100 sticky top-0">
                <tr>
                  <th class="px-3 py-2 text-right">#</th>
                  <th class="px-3 py-2 text-right">الوقت</th>
                  <th class="px-3 py-2 text-right">Pitch</th>
                  <th class="px-3 py-2 text-right">AU1</th>
                  <th class="px-3 py-2 text-right">AU4</th>
                  <th class="px-3 py-2 text-right">AU12</th>
                  <th class="px-3 py-2 text-right">الجودة</th>
                  <th class="px-3 py-2 text-right">تنبيهات</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="w in windows" :key="w.id" class="hover:bg-gray-50">
                  <td class="px-3 py-2 font-bold">{{ w.window_index }}</td>
                  <td class="px-3 py-2">{{ num(w.start_time_sec, 1) }}-{{ num(w.end_time_sec, 1) }}</td>
                  <td class="px-3 py-2">{{ num(w.pitch_mean_hz, 0) }}</td>
                  <td class="px-3 py-2">{{ num(w.au1_inner_brow_raise) }}</td>
                  <td class="px-3 py-2">{{ num(w.au4_brow_lowerer) }}</td>
                  <td class="px-3 py-2">{{ num(w.au12_lip_corner_puller) }}</td>
                  <td class="px-3 py-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="qualityClass(w.overall_quality)">{{ qualityLabel(w.overall_quality) }}</span>
                  </td>
                  <td class="px-3 py-2">
                    <span v-if="w.phone_detected" class="text-red-600">📱</span>
                    <span v-if="w.person_count > 1" class="text-orange-600">👥</span>
                    <span v-if="!w.phone_detected && w.person_count <= 1" class="text-green-600">✓</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </details>

      <!-- HR Decision -->
      <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-indigo-100">
        <h3 class="font-bold text-lg mb-4 border-b pb-2 flex items-center gap-2">
          ✍️ قرار HR النهائي
          <span class="text-xs font-normal text-gray-500">(سيتم الحفظ والعودة للوحة التحكم)</span>
        </h3>

        <form @submit.prevent="saveNotes" class="space-y-4">
          <div class="flex gap-3">
            <label v-for="opt in decisions" :key="opt.value" class="flex-1 cursor-pointer">
              <input type="radio" v-model="form.final_decision" :value="opt.value" class="peer hidden">
              <div class="p-4 rounded-lg border-2 text-center transition cursor-pointer active:scale-95"
                   :class="form.final_decision === opt.value ? opt.activeClass : 'border-gray-200 hover:border-gray-300'">
                <span class="font-bold">{{ opt.label }}</span>
              </div>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">ملاحظات HR</label>
            <textarea v-model="form.hr_notes" rows="5" placeholder="اكتب ملاحظاتك..."
                      class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"></textarea>
          </div>

          <div class="flex justify-end gap-3">
            <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:shadow-xl shadow-lg shadow-indigo-500/30 disabled:opacity-50 font-bold transition-all active:scale-95">
              <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              <span>{{ saving ? "جاري الحفظ..." : "حفظ القرار" }}</span>
            </button>
          </div>
        </form>
      </div>

    </template>
  </div>
</template>
