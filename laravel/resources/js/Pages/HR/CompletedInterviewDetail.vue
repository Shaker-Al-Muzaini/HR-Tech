<script setup>
import { ref, reactive, computed } from "vue";
import { router } from "@inertiajs/vue3";
import HRLayout from "@/Layouts/HRLayout.vue";
import { useToast } from "@/composables/useToast";
import { useConfirm } from "@/composables/useConfirm";

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
const { confirm } = useConfirm();

const form = reactive({
  hr_notes: props.interview?.hr_notes || "",
  final_decision: props.interview?.final_decision || "",
});

const decisions = [
  { value: "accepted",     label: "قبول",         icon: "✅", activeClass: "border-green-500 bg-green-50 text-green-800 ring-2 ring-green-500/30" },
  { value: "under_review", label: "قيد المراجعة", icon: "⏸️", activeClass: "border-yellow-500 bg-yellow-50 text-yellow-800 ring-2 ring-yellow-500/30" },
  { value: "rejected",     label: "رفض",          icon: "❌", activeClass: "border-red-500 bg-red-50 text-red-800 ring-2 ring-red-500/30" },
];

const saving = ref(false);

// ═══════════════════════════════════════════════════════════
// Computed
// ═══════════════════════════════════════════════════════════
const completionLabel = computed(() => ({
  ended_by_hr: "أنهاها المشرف",
  time_expired: "انتهى الوقت المحدد",
  candidate_left: "غادر المرشح",
}[props.interview?.completion_type] || "غير محدد"));

const completionBadge = computed(() => ({
  ended_by_hr: "bg-emerald-100 text-emerald-800 border-emerald-200",
  time_expired: "bg-amber-100 text-amber-800 border-amber-200",
  candidate_left: "bg-slate-100 text-slate-700 border-slate-200",
}[props.interview?.completion_type] || "bg-gray-100 text-gray-700 border-gray-200"));

const scoreColor = computed(() => {
  if (!props.verdict) return "#9ca3af";
  const c = props.verdict.recommendation.color;
  return { green: "#10b981", blue: "#3b82f6", yellow: "#f59e0b", red: "#ef4444" }[c] || "#9ca3af";
});

const scoreTextColor = computed(() => {
  if (!props.verdict) return "text-gray-400";
  const c = props.verdict.recommendation.color;
  return { green: "text-emerald-600", blue: "text-blue-600", yellow: "text-amber-600", red: "text-red-600" }[c] || "text-gray-400";
});

const decisionLabel = computed(() => ({
  accepted: "مقبول",
  rejected: "مرفوض",
  under_review: "قيد المراجعة",
}[props.interview?.final_decision] || "بانتظار القرار"));

const decisionBadge = computed(() => ({
  accepted: "bg-green-100 text-green-800 border-green-200",
  rejected: "bg-red-100 text-red-800 border-red-200",
  under_review: "bg-yellow-100 text-yellow-800 border-yellow-200",
}[props.interview?.final_decision] || "bg-gray-100 text-gray-700 border-gray-200"));

// ═══════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════
const initials = (name) => {
  if (!name) return "?";
  return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
};

const formatDateTime = (iso) => {
  if (!iso) return "—";
  return new Date(iso).toLocaleString("ar-EG", {
    day: "numeric", month: "long", year: "numeric",
    hour: "2-digit", minute: "2-digit",
  });
};

const formatDate = (iso) => {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("ar-EG", { day: "numeric", month: "long", year: "numeric" });
};

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

// ═══════════════════════════════════════════════════════════
// Actions
// ═══════════════════════════════════════════════════════════
const goBack = () => router.visit("/hr/completed");

const viewFullReport = () => router.visit(`/hr/interview/${props.interview.id}/report`);

const deleteInterview = async () => {
  const ok = await confirm({
    title: "حذف المقابلة؟",
    message: `سيتم حذف مقابلة ${props.interview.candidate?.name || "غير معروف"} نهائياً. لا يمكن التراجع.`,
    type: "danger",
    confirmText: "حذف نهائياً",
    cancelText: "إلغاء",
  });
  if (!ok) return;

  router.delete(`/hr/interviews/${props.interview.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.error("تم حذف المقابلة");
      setTimeout(() => router.visit("/hr/completed"), 800);
    },
    onError: () => toast.error("فشل الحذف"),
  });
};

const saveNotes = async () => {
  saving.value = true;

  router.post(`/hr/interview/${props.interview.id}/report/notes`, form, {
    preserveScroll: true,
    onSuccess: () => {
      saving.value = false;
      toast.success("تم حفظ القرار بنجاح");
      setTimeout(() => router.visit("/hr/completed"), 1200);
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
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
      <div class="flex items-start gap-4">
        <button @click="goBack"
                class="w-10 h-10 rounded-2xl bg-white border border-gray-200 flex items-center justify-center hover:bg-gray-50 hover:border-indigo-200 transition-all active:scale-95 flex-shrink-0"
                title="رجوع">
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>

        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
            🎯 تفاصيل المقابلة المكتملة
          </h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ interview.candidate?.name }} — {{ interview.job_title }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <button @click="viewFullReport"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          التقرير الكامل
        </button>
        <button @click="deleteInterview"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 font-bold text-xs rounded-xl border border-red-200 hover:bg-red-100 transition-all active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
          حذف
        </button>
      </div>
    </div>

    <!-- ═══ Hero Card: Candidate + Score ═══ -->
    <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm overflow-hidden">
      <div class="bg-gradient-to-l from-indigo-50 via-purple-50 to-white p-6 md:p-8">
        <div class="flex flex-col md:flex-row items-center gap-6">

          <!-- Avatar -->
          <div class="relative flex-shrink-0">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl blur-xl opacity-30"></div>
            <div class="relative w-24 h-24 rounded-3xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-3xl shadow-2xl shadow-indigo-500/40">
              {{ initials(interview.candidate?.name) }}
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 text-center md:text-right min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 truncate">{{ interview.candidate?.name || "غير معروف" }}</h2>
            <p class="text-gray-600 mt-1 truncate">{{ interview.candidate?.email || "—" }}</p>
            <div class="flex flex-wrap items-center gap-2 mt-3 justify-center md:justify-start">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border" :class="completionBadge">
                {{ completionLabel }}
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border" :class="decisionBadge">
                {{ decisionLabel }}
              </span>
            </div>
          </div>

          <!-- Score Ring -->
          <div class="relative w-32 h-32 flex-shrink-0">
            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 128 128">
              <circle cx="64" cy="64" r="54" stroke="#e5e7eb" stroke-width="10" fill="none" />
              <circle cx="64" cy="64" r="54"
                      :stroke="scoreColor"
                      stroke-width="10" fill="none" stroke-linecap="round"
                      :stroke-dasharray="`${((verdict?.overall_score || 0) / 100) * 339.3} 339.3`"
                      class="transition-all duration-1000" />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-4xl font-bold tabular-nums" :class="scoreTextColor">{{ verdict?.overall_score ?? "—" }}</span>
              <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">من 100</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ Quick Info Grid ═══ -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">

      <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-4">
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">نوع الاكتمال</p>
        <p class="text-sm font-bold text-gray-900 mt-1.5 truncate">{{ completionLabel }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-4">
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">القرار</p>
        <p class="text-sm font-bold text-gray-900 mt-1.5 truncate">{{ decisionLabel }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-4">
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">التاريخ</p>
        <p class="text-sm font-bold text-gray-900 mt-1.5 truncate">{{ formatDate(interview.ended_at) }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-4">
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">المدة المخططة</p>
        <p class="text-sm font-bold text-gray-900 mt-1.5 truncate">{{ interview.duration_minutes }} د</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-4">
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">النوافذ المحللة</p>
        <p class="text-sm font-bold text-gray-900 mt-1.5 tabular-nums truncate">{{ stats?.total_windows ?? 0 }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-4">
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">مدة التحليل</p>
        <p class="text-sm font-bold text-gray-900 mt-1.5 tabular-nums truncate">{{ stats?.duration ?? 0 }} ث</p>
      </div>
    </div>

    <!-- ═══ Recording Section ═══ -->
    <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-l from-slate-50 to-white flex items-center justify-between">
        <h3 class="font-bold text-gray-900 flex items-center gap-2">
          <span class="w-1 h-4 bg-gradient-to-b from-red-500 to-pink-500 rounded-full"></span>
          🎥 تسجيل المقابلة
        </h3>
        <span v-if="recording?.exists && recording.status === 'ready'"
              class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full">
          {{ recording.human_duration }} • {{ recording.human_size }}
        </span>
      </div>

      <div v-if="recording?.exists && recording.status === 'ready'" class="aspect-video bg-black">
        <video controls class="w-full h-full" :src="recording.stream_url"></video>
      </div>

      <div v-else class="p-12 text-center bg-gradient-to-br from-gray-50 to-white">
        <div class="w-20 h-20 mx-auto mb-4 rounded-3xl bg-gradient-to-br from-red-50 to-pink-50 flex items-center justify-center border border-red-100">
          <svg class="w-10 h-10 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
          </svg>
        </div>
        <p class="text-gray-700 font-bold mb-1">لا يوجد تسجيل متاح لهذه المقابلة</p>
        <p class="text-xs text-gray-400">سيتم تفعيل ميزة التسجيل في تحديث قادم</p>
      </div>
    </div>

    <!-- ═══ Report Summary (KPIs) ═══ -->
    <div v-if="verdict" class="grid grid-cols-2 lg:grid-cols-4 gap-4">

      <div class="bg-white rounded-2xl border-r-4 shadow-sm p-5" :class="borderColor(verdict.integrity.status)">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-bold text-gray-500 uppercase">النزاهة</p>
          <span class="text-2xl font-bold tabular-nums" :class="scoreTextColorByStatus(verdict.integrity.status)">{{ verdict.integrity.score }}</span>
        </div>
        <p class="text-xs text-gray-600 line-clamp-2">{{ verdict.integrity.label }}</p>
      </div>

      <div class="bg-white rounded-2xl border-r-4 shadow-sm p-5" :class="borderColor(verdict.confidence.status)">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-bold text-gray-500 uppercase">الثقة</p>
          <span class="text-2xl font-bold tabular-nums" :class="scoreTextColorByStatus(verdict.confidence.status)">{{ verdict.confidence.score }}</span>
        </div>
        <p class="text-xs text-gray-600 line-clamp-2">{{ verdict.confidence.label }}</p>
      </div>

      <div class="bg-white rounded-2xl border-r-4 shadow-sm p-5" :class="borderColor(verdict.composure.status)">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-bold text-gray-500 uppercase">الاتزان</p>
          <span class="text-2xl font-bold tabular-nums" :class="scoreTextColorByStatus(verdict.composure.status)">{{ verdict.composure.score }}</span>
        </div>
        <p class="text-xs text-gray-600 line-clamp-2">{{ verdict.composure.label }}</p>
      </div>

      <div class="bg-white rounded-2xl border-r-4 shadow-sm p-5" :class="borderColor(verdict.quality.status)">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-bold text-gray-500 uppercase">الجودة</p>
          <span class="text-2xl font-bold tabular-nums" :class="scoreTextColorByStatus(verdict.quality.status)">{{ verdict.quality.score }}</span>
        </div>
        <p class="text-xs text-gray-600 line-clamp-2">{{ verdict.quality.label }}</p>
      </div>
    </div>

    <!-- ═══ No Data State ═══ -->
    <div v-else class="bg-white rounded-2xl shadow-sm p-12 text-center border-2 border-dashed border-gray-200">
      <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
      </div>
      <p class="text-gray-700 font-bold mb-1">لا توجد بيانات تحليلية</p>
      <p class="text-xs text-gray-400">لم تُجرَ المقابلة فعلياً أو لم تُسجل أي نوافذ تحليلية</p>
    </div>

    <!-- ═══ HR Decision Section ═══ -->
    <div class="bg-white rounded-2xl shadow-sm border-2 border-indigo-100 p-6">
      <h3 class="font-bold text-lg mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
        ✍️ قرار HR النهائي
        <span class="text-xs font-normal text-gray-500">(سيتم الحفظ والعودة للسجل)</span>
      </h3>

      <form @submit.prevent="saveNotes" class="space-y-4">
        <div class="grid grid-cols-3 gap-3">
          <label v-for="opt in decisions" :key="opt.value" class="cursor-pointer">
            <input type="radio" v-model="form.final_decision" :value="opt.value" class="peer hidden">
            <div class="p-4 rounded-xl border-2 text-center transition cursor-pointer active:scale-95"
                 :class="form.final_decision === opt.value ? opt.activeClass : 'border-gray-200 hover:border-gray-300 bg-white'">
              <p class="text-2xl mb-1">{{ opt.icon }}</p>
              <p class="font-bold text-sm">{{ opt.label }}</p>
            </div>
          </label>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">ملاحظات HR</label>
          <textarea v-model="form.hr_notes" rows="5" placeholder="اكتب ملاحظاتك حول المقابلة..."
                    class="w-full border border-gray-200 rounded-xl p-4 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"></textarea>
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" @click="goBack"
                  class="px-6 py-3 text-gray-700 font-bold text-sm rounded-xl border border-gray-200 hover:bg-gray-50 transition-all active:scale-95">
            إلغاء
          </button>
          <button type="submit" :disabled="saving"
                  class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:shadow-xl shadow-lg shadow-indigo-500/30 disabled:opacity-50 font-bold transition-all active:scale-95">
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ saving ? "جاري الحفظ..." : "حفظ القرار" }}</span>
          </button>
        </div>
      </form>
    </div>

    <!-- ═══ Meta Info (Collapsible) ═══ -->
    <details class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <summary class="px-6 py-4 cursor-pointer hover:bg-gray-50 font-bold text-gray-700 flex items-center gap-2 transition-colors">
        ℹ️ معلومات إضافية
      </summary>
      <div class="p-6 border-t border-gray-100 bg-gray-50/50">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase mb-1">التاريخ المجدول</p>
            <p class="font-semibold text-gray-900">{{ formatDateTime(interview.scheduled_at) }}</p>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase mb-1">وقت البدء</p>
            <p class="font-semibold text-gray-900">{{ formatDateTime(interview.started_at) }}</p>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase mb-1">وقت الانتهاء</p>
            <p class="font-semibold text-gray-900">{{ formatDateTime(interview.ended_at) }}</p>
          </div>
          <div v-if="interview.expired_at">
            <p class="text-xs font-bold text-gray-500 uppercase mb-1">وقت الانتهاء التلقائي</p>
            <p class="font-semibold text-gray-900">{{ formatDateTime(interview.expired_at) }}</p>
          </div>
          <div v-if="interview.candidate?.phone">
            <p class="text-xs font-bold text-gray-500 uppercase mb-1">الهاتف</p>
            <p class="font-semibold text-gray-900" dir="ltr">{{ interview.candidate.phone }}</p>
          </div>
          <div v-if="interview.candidate?.applied_position">
            <p class="text-xs font-bold text-gray-500 uppercase mb-1">الوظيفة المتقدم لها</p>
            <p class="font-semibold text-gray-900">{{ interview.candidate.applied_position }}</p>
          </div>
        </div>
      </div>
    </details>

  </div>
</template>
