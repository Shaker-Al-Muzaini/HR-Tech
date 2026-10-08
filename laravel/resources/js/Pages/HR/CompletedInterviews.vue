<script setup>
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import HRLayout from "@/Layouts/HRLayout.vue";
import { useToast } from "@/composables/useToast";
import { useConfirm } from "@/composables/useConfirm";

defineOptions({ layout: HRLayout });

const props = defineProps({
  interviews: Object,
  counts: Object,
  filters: Object,
});

const page = usePage();
const toast = useToast();
const { confirm } = useConfirm();

// ═══════════════════════════════════════════════════════════
// View Mode (grid / list) — يُحفظ في localStorage
// ═══════════════════════════════════════════════════════════
const viewMode = ref(localStorage.getItem("completed_view_mode") || "grid");
const toggleView = (mode) => {
  viewMode.value = mode;
  localStorage.setItem("completed_view_mode", mode);
};

// ═══════════════════════════════════════════════════════════
// Filters State
// ═══════════════════════════════════════════════════════════
const searchInput = ref(props.filters?.search || "");
const completionType = ref(props.filters?.completion_type || "all");
const decision = ref(props.filters?.decision || "all");
const dateFrom = ref(props.filters?.date_from || "");
const dateTo = ref(props.filters?.date_to || "");
const perPage = ref(props.filters?.per_page || 12);

const hasActiveFilters = computed(() =>
  searchInput.value || completionType.value !== "all" ||
  decision.value !== "all" || dateFrom.value || dateTo.value
);

// ═══════════════════════════════════════════════════════════
// Stats Cards (قابلة للنقر)
// ═══════════════════════════════════════════════════════════
const statCards = computed(() => [
  { key: "all",            label: "إجمالي المكتملة",    value: props.counts.all,            icon: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z",           color: "indigo",  filter: null },
  { key: "ended_by_hr",    label: "أنهاها المشرف",       value: props.counts.ended_by_hr,    icon: "M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1", color: "emerald", filter: { completion_type: "ended_by_hr" } },
  { key: "time_expired",   label: "انتهى الوقت",         value: props.counts.time_expired,   icon: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",               color: "amber",   filter: { completion_type: "time_expired" } },
  { key: "candidate_left", label: "غادر المرشح",         value: props.counts.candidate_left, icon: "M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1", color: "slate",   filter: { completion_type: "candidate_left" } },
  { key: "pending",        label: "بانتظار القرار",       value: props.counts.pending,        icon: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",               color: "purple",  filter: { decision: "pending" } },
  { key: "accepted",       label: "مقبولون",             value: props.counts.accepted,       icon: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z",             color: "green",   filter: { decision: "accepted" } },
]);

const colorMap = {
  indigo:  { bg: "bg-indigo-50",  text: "text-indigo-600",  ring: "ring-indigo-500/30",  circle: "bg-indigo-500" },
  emerald: { bg: "bg-emerald-50", text: "text-emerald-600", ring: "ring-emerald-500/30", circle: "bg-emerald-500" },
  amber:   { bg: "bg-amber-50",   text: "text-amber-600",   ring: "ring-amber-500/30",   circle: "bg-amber-500" },
  slate:   { bg: "bg-slate-50",   text: "text-slate-600",   ring: "ring-slate-500/30",   circle: "bg-slate-500" },
  purple:  { bg: "bg-purple-50",  text: "text-purple-600",  ring: "ring-purple-500/30",  circle: "bg-purple-500" },
  green:   { bg: "bg-green-50",   text: "text-green-600",   ring: "ring-green-500/30",   circle: "bg-green-500" },
};

// ═══════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════
const initials = (name) => {
  if (!name) return "?";
  return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
};

const formatDate = (iso) => {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("ar-EG", { day: "numeric", month: "short", year: "numeric" });
};

const formatTime = (iso) => {
  if (!iso) return "";
  return new Date(iso).toLocaleTimeString("ar-EG", { hour: "2-digit", minute: "2-digit" });
};

const completionLabel = (t) => ({
  ended_by_hr: "أنهاها المشرف",
  time_expired: "انتهى الوقت",
  candidate_left: "غادر المرشح",
}[t] || "غير محدد");

const completionBadge = (t) => ({
  ended_by_hr: "bg-emerald-100 text-emerald-800",
  time_expired: "bg-amber-100 text-amber-800",
  candidate_left: "bg-slate-100 text-slate-700",
}[t] || "bg-gray-100 text-gray-700");

const decisionLabel = (d) => ({
  accepted: "مقبول",
  rejected: "مرفوض",
  under_review: "قيد المراجعة",
}[d] || "بانتظار القرار");

const decisionBadge = (d) => ({
  accepted: "bg-green-100 text-green-800 border-green-200",
  rejected: "bg-red-100 text-red-800 border-red-200",
  under_review: "bg-yellow-100 text-yellow-800 border-yellow-200",
}[d] || "bg-gray-100 text-gray-700 border-gray-200");

const scoreColor = (score) => {
  if (score === null || score === undefined) return "#9ca3af";
  if (score >= 85) return "#10b981";
  if (score >= 70) return "#3b82f6";
  if (score >= 55) return "#f59e0b";
  return "#ef4444";
};

const scoreTextColor = (score) => {
  if (score === null || score === undefined) return "text-gray-400";
  if (score >= 85) return "text-emerald-600";
  if (score >= 70) return "text-blue-600";
  if (score >= 55) return "text-amber-600";
  return "text-red-600";
};

// ═══════════════════════════════════════════════════════════
// Navigation / Filters
// ═══════════════════════════════════════════════════════════
const buildQuery = (overrides = {}) => ({
  search: searchInput.value,
  completion_type: completionType.value,
  decision: decision.value,
  date_from: dateFrom.value,
  date_to: dateTo.value,
  per_page: perPage.value,
  ...overrides,
});

const applyFilters = () => {
  router.get("/hr/completed", buildQuery(), {
    preserveState: true, preserveScroll: true, replace: true,
  });
};

let searchTimer = null;
const debouncedSearch = () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(applyFilters, 400);
};

const applyStatFilter = (card) => {
  if (!card.filter) {
    completionType.value = "all";
    decision.value = "all";
  } else {
    if (card.filter.completion_type) {
      completionType.value = card.filter.completion_type;
      decision.value = "all";
    }
    if (card.filter.decision) {
      decision.value = card.filter.decision;
      completionType.value = "all";
    }
  }
  applyFilters();
};

const clearFilters = () => {
  searchInput.value = "";
  completionType.value = "all";
  decision.value = "all";
  dateFrom.value = "";
  dateTo.value = "";
  applyFilters();
};

const goToPage = (p) => {
  router.get("/hr/completed", buildQuery({ page: p }), {
    preserveState: true, preserveScroll: true,
  });
};

const visiblePages = computed(() => {
  const current = props.interviews.current_page;
  const last = props.interviews.last_page;
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
  if (current <= 4) return [1, 2, 3, 4, 5, "...", last];
  if (current >= last - 3) return [1, "...", last - 4, last - 3, last - 2, last - 1, last];
  return [1, "...", current - 1, current, current + 1, "...", last];
});

// ═══════════════════════════════════════════════════════════
// Actions
// ═══════════════════════════════════════════════════════════
const viewDetails = (interview) => router.visit(`/hr/completed/${interview.id}`);

const viewReport = (interview) => router.visit(`/hr/interview/${interview.id}/report`);

const deleteInterview = async (interview) => {
  const ok = await confirm({
    title: "حذف المقابلة؟",
    message: `سيتم حذف مقابلة ${interview.candidate?.name || "غير معروف"} نهائياً. لا يمكن التراجع.`,
    type: "danger",
    confirmText: "حذف نهائياً",
    cancelText: "إلغاء",
  });
  if (!ok) return;

  router.delete(`/hr/interviews/${interview.id}`, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      toast.error("تم حذف المقابلة");
      router.reload({ only: ["interviews", "counts"] });
    },
    onError: () => toast.error("فشل الحذف"),
  });
};

// ═══════════════════════════════════════════════════════════
// Lifecycle
// ═══════════════════════════════════════════════════════════
onMounted(() => {
  if (page.props.flash?.success) toast.success(page.props.flash.success);
  if (page.props.flash?.warning) toast.warning(page.props.flash.warning);
  if (page.props.flash?.error) toast.error(page.props.flash.error);
});
</script>

<template>
  <div class="space-y-5">

    <!-- ═══ Header ═══ -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">📋 سجل المقابلات المكتملة</h1>
        <p class="text-sm text-gray-500 mt-1">جميع المقابلات المنتهية مع الدرجات والقرارات</p>
      </div>

      <!-- View Toggle -->
      <div class="inline-flex items-center bg-white rounded-2xl p-1 shadow-sm border border-gray-100 self-start">
        <button @click="toggleView('grid')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                :class="viewMode === 'grid'
                  ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                  : 'text-gray-600 hover:bg-gray-50'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
          </svg>
          بطاقات
        </button>
        <button @click="toggleView('list')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                :class="viewMode === 'list'
                  ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                  : 'text-gray-600 hover:bg-gray-50'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
          </svg>
          جدول
        </button>
      </div>
    </div>

    <!-- ═══ Stats Cards (قابلة للنقر) ═══ -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
      <button v-for="(card, idx) in statCards" :key="card.key"
              @click="applyStatFilter(card)"
              class="group relative bg-white rounded-2xl p-4 border border-gray-100/80 hover:border-indigo-200/60 shadow-sm hover:shadow-xl hover:shadow-indigo-100/50 transition-all duration-500 cursor-pointer text-right active:scale-95"
              :style="{ transitionDelay: `${idx * 30}ms` }">
        <div class="absolute top-0 right-0 w-20 h-20 rounded-full opacity-0 group-hover:opacity-10 transition-opacity duration-500 blur-2xl" :class="colorMap[card.color].circle"></div>
        <div class="relative flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide truncate">{{ card.label }}</p>
            <p class="text-2xl font-bold mt-1.5 tabular-nums transition-transform group-hover:scale-110 origin-right duration-500" :class="colorMap[card.color].text">{{ card.value }}</p>
          </div>
          <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform group-hover:rotate-6 duration-500" :class="colorMap[card.color].bg">
            <svg class="w-4 h-4" :class="colorMap[card.color].text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon"></path>
            </svg>
          </div>
        </div>
      </button>
    </div>

    <!-- ═══ Filters Bar ═══ -->
    <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

        <!-- Search -->
        <div class="md:col-span-4 relative">
          <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <input v-model="searchInput" @input="debouncedSearch" type="text" placeholder="ابحث عن مرشح أو وظيفة..."
                 class="w-full pr-9 pl-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent focus:bg-white transition-all duration-300" />
        </div>

        <!-- Completion Type -->
        <div class="md:col-span-2">
          <select v-model="completionType" @change="applyFilters"
                  class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer">
            <option value="all">كل الأنواع</option>
            <option value="ended_by_hr">أنهاها المشرف</option>
            <option value="time_expired">انتهى الوقت</option>
            <option value="candidate_left">غادر المرشح</option>
          </select>
        </div>

        <!-- Decision -->
        <div class="md:col-span-2">
          <select v-model="decision" @change="applyFilters"
                  class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer">
            <option value="all">كل القرارات</option>
            <option value="pending">بانتظار القرار</option>
            <option value="accepted">مقبول</option>
            <option value="rejected">مرفوض</option>
            <option value="under_review">قيد المراجعة</option>
          </select>
        </div>

        <!-- Date From -->
        <div class="md:col-span-2">
          <input v-model="dateFrom" @change="applyFilters" type="date" placeholder="من تاريخ"
                 class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer" />
        </div>

        <!-- Clear -->
        <div class="md:col-span-2 flex gap-2">
          <input v-model="dateTo" @change="applyFilters" type="date" placeholder="إلى تاريخ"
                 class="flex-1 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer" />
          <button v-if="hasActiveFilters" @click="clearFilters"
                  class="px-3 py-2.5 bg-red-50 text-red-600 rounded-xl text-xs font-bold hover:bg-red-100 transition-all active:scale-95 flex-shrink-0"
                  title="مسح الفلاتر">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ═══ Pagination Info ═══ -->
    <div class="flex items-center justify-between text-xs">
      <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 rounded-lg">
        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
        </svg>
        <span class="font-bold text-indigo-700 tabular-nums">
          {{ interviews.from || 0 }} - {{ interviews.to || 0 }}
          <span class="text-indigo-500 font-medium">من {{ interviews.total }}</span>
        </span>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-gray-500 font-medium">عرض</span>
        <select v-model.number="perPage" @change="applyFilters"
                class="px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs font-bold bg-white text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer">
          <option :value="6">6</option>
          <option :value="12">12</option>
          <option :value="24">24</option>
          <option :value="48">48</option>
        </select>
        <span class="text-gray-500 font-medium">لكل صفحة</span>
      </div>
    </div>

    <!-- ═══ Empty State ═══ -->
    <div v-if="interviews.data.length === 0"
         class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-16 text-center">
      <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center">
        <svg class="w-12 h-12 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <h3 class="text-xl font-bold text-gray-800 mb-2">
        {{ hasActiveFilters ? "لا توجد نتائج مطابقة" : "لا توجد مقابلات مكتملة بعد" }}
      </h3>
      <p class="text-gray-500 text-sm mb-6 max-w-md mx-auto">
        {{ hasActiveFilters
          ? "جرّب تغيير الفلاتر أو كلمة البحث"
          : "بعد انتهاء أول مقابلة ستظهر هنا مع التفاصيل والدرجات" }}
      </p>
      <button v-if="hasActiveFilters" @click="clearFilters"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-bold text-sm transition-all active:scale-95">
        مسح الفلاتر
      </button>
      <a v-else href="/"
         class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:shadow-xl shadow-lg shadow-indigo-500/30 font-bold text-sm transition-all active:scale-95">
        🏠 العودة للرئيسية
      </a>
    </div>

    <!-- ═══ GRID VIEW ═══ -->
    <div v-else-if="viewMode === 'grid'"
         class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="(interview, idx) in interviews.data" :key="interview.id"
           class="group relative bg-white rounded-2xl border border-gray-100/80 shadow-sm hover:shadow-2xl hover:shadow-indigo-100/50 hover:border-indigo-200/60 transition-all duration-500 overflow-hidden cursor-pointer"
           :style="{ animationDelay: `${idx * 40}ms` }"
           @click="viewDetails(interview)">

        <!-- Decorative gradient on hover -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

        <div class="p-5 space-y-4">

          <!-- Top: Candidate + Score -->
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0 flex-1">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-indigo-500/30 flex-shrink-0 transition-transform group-hover:scale-110 duration-500">
                {{ initials(interview.candidate?.name) }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="font-bold text-gray-900 text-sm truncate">{{ interview.candidate?.name || "غير معروف" }}</p>
                <p class="text-xs text-gray-500 truncate">{{ interview.job_title }}</p>
              </div>
            </div>

            <!-- Score Ring -->
            <div class="relative w-14 h-14 flex-shrink-0">
              <svg class="w-14 h-14 transform -rotate-90" viewBox="0 0 56 56">
                <circle cx="28" cy="28" r="24" stroke="#f3f4f6" stroke-width="4" fill="none" />
                <circle cx="28" cy="28" r="24"
                        :stroke="scoreColor(interview.overall_score)"
                        stroke-width="4" fill="none" stroke-linecap="round"
                        :stroke-dasharray="`${((interview.overall_score || 0) / 100) * 150.8} 150.8`"
                        class="transition-all duration-1000" />
              </svg>
              <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-sm font-bold tabular-nums" :class="scoreTextColor(interview.overall_score)">
                  {{ interview.overall_score ?? "—" }}
                </span>
              </div>
            </div>
          </div>

          <!-- Recommendation -->
          <div v-if="interview.recommendation" class="flex items-center gap-2">
            <span class="text-xl">{{ interview.recommendation.icon }}</span>
            <span class="text-sm font-bold" :class="{
              'text-emerald-600': interview.recommendation.color === 'green',
              'text-blue-600': interview.recommendation.color === 'blue',
              'text-amber-600': interview.recommendation.color === 'yellow',
              'text-red-600': interview.recommendation.color === 'red',
            }">{{ interview.recommendation.label }}</span>
          </div>

          <!-- Badges -->
          <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold"
                  :class="completionBadge(interview.completion_type)">
              {{ completionLabel(interview.completion_type) }}
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border"
                  :class="decisionBadge(interview.final_decision)">
              {{ decisionLabel(interview.final_decision) }}
            </span>
            <span v-if="interview.recording?.exists"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700">
              <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
              🎥 تسجيل
            </span>
          </div>

          <!-- Info Grid -->
          <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-gray-100">
            <div>
              <p class="text-[10px] text-gray-400 font-bold uppercase">الانتهاء</p>
              <p class="font-semibold text-gray-700">{{ formatDate(interview.ended_at) }}</p>
            </div>
            <div>
              <p class="text-[10px] text-gray-400 font-bold uppercase">الوقت</p>
              <p class="font-semibold text-gray-700">{{ formatTime(interview.ended_at) }}</p>
            </div>
          </div>

        </div>

        <!-- Action Bar (appears on hover) -->
        <div class="border-t border-gray-100 bg-gray-50/50 px-5 py-3 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity duration-300">
          <button @click.stop="viewDetails(interview)"
                  class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            التفاصيل
          </button>

          <div class="flex items-center gap-1">
            <button @click.stop="viewReport(interview)"
                    class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:scale-110 transition-all" title="التقرير الكامل">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </button>
            <button @click.stop="deleteInterview(interview)"
                    class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 hover:scale-110 transition-all" title="حذف">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ LIST VIEW ═══ -->
    <div v-else class="bg-white rounded-2xl border border-gray-100/80 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50/70 border-b border-gray-100">
            <tr>
              <th class="px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">المرشح</th>
              <th class="px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">الوظيفة</th>
              <th class="px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">انتهت في</th>
              <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider">الدرجة</th>
              <th class="px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">الاكتمال</th>
              <th class="px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">القرار</th>
              <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">إجراءات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(interview, idx) in interviews.data" :key="interview.id"
                class="group hover:bg-indigo-50/30 transition-all duration-200 cursor-pointer"
                :style="{ animationDelay: `${idx * 30}ms` }"
                @click="viewDetails(interview)">
              <td class="px-5 py-4">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs flex-shrink-0 shadow-md shadow-indigo-500/30">
                    {{ initials(interview.candidate?.name) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-gray-900 text-sm truncate">{{ interview.candidate?.name || "غير معروف" }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ interview.candidate?.email || "—" }}</p>
                  </div>
                </div>
              </td>

              <td class="px-5 py-4">
                <p class="font-semibold text-gray-900 text-sm truncate">{{ interview.job_title }}</p>
                <p class="text-xs text-gray-500 truncate">{{ interview.department || "—" }}</p>
              </td>

              <td class="px-5 py-4">
                <p class="font-semibold text-gray-900 text-xs">{{ formatDate(interview.ended_at) }}</p>
                <p class="text-xs text-gray-500 tabular-nums">{{ formatTime(interview.ended_at) }}</p>
              </td>

              <td class="px-5 py-4 text-center">
                <div class="inline-flex items-center gap-2">
                  <div class="relative w-10 h-10">
                    <svg class="w-10 h-10 transform -rotate-90" viewBox="0 0 40 40">
                      <circle cx="20" cy="20" r="16" stroke="#f3f4f6" stroke-width="3" fill="none" />
                      <circle cx="20" cy="20" r="16"
                              :stroke="scoreColor(interview.overall_score)"
                              stroke-width="3" fill="none" stroke-linecap="round"
                              :stroke-dasharray="`${((interview.overall_score || 0) / 100) * 100.5} 100.5`" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                      <span class="text-xs font-bold tabular-nums" :class="scoreTextColor(interview.overall_score)">
                        {{ interview.overall_score ?? "—" }}
                      </span>
                    </div>
                  </div>
                </div>
              </td>

              <td class="px-5 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold whitespace-nowrap" :class="completionBadge(interview.completion_type)">
                  {{ completionLabel(interview.completion_type) }}
                </span>
              </td>

              <td class="px-5 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border whitespace-nowrap" :class="decisionBadge(interview.final_decision)">
                  {{ decisionLabel(interview.final_decision) }}
                </span>
              </td>

              <td class="px-5 py-4">
                <div class="flex items-center justify-end gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                  <button @click.stop="viewReport(interview)"
                          class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-100 transition-all" title="التقرير">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </button>
                  <button @click.stop="deleteInterview(interview)"
                          class="p-1.5 rounded-lg text-red-500 hover:bg-red-100 transition-all" title="حذف">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══ Pagination ═══ -->
    <div v-if="interviews.last_page > 1" class="flex items-center justify-center gap-1 pt-2">
      <button v-if="interviews.prev_page_url" @click="goToPage(interviews.current_page - 1)"
              class="group flex items-center gap-1 px-4 py-2 rounded-xl text-xs font-bold bg-white text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200 hover:border-indigo-200 transition-all active:scale-95">
        <svg class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
        السابق
      </button>
      <span v-else class="px-4 py-2 rounded-xl text-xs font-bold bg-gray-50 text-gray-300 border border-gray-100 cursor-not-allowed">السابق</span>

      <template v-for="(link, idx) in visiblePages" :key="idx">
        <button v-if="link !== '...'" @click="goToPage(link)"
                class="w-9 h-9 rounded-xl text-xs font-bold transition-all duration-300 active:scale-90 tabular-nums"
                :class="link === interviews.current_page
                  ? 'bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30 scale-105'
                  : 'bg-white text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200'">
          {{ link }}
        </button>
        <span v-else class="w-9 h-9 flex items-center justify-center text-gray-400 text-xs">…</span>
      </template>

      <button v-if="interviews.next_page_url" @click="goToPage(interviews.current_page + 1)"
              class="group flex items-center gap-1 px-4 py-2 rounded-xl text-xs font-bold bg-white text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200 hover:border-indigo-200 transition-all active:scale-95">
        التالي
        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </button>
      <span v-else class="px-4 py-2 rounded-xl text-xs font-bold bg-gray-50 text-gray-300 border border-gray-100 cursor-not-allowed">التالي</span>
    </div>

  </div>
</template>

<style scoped>
@keyframes cardIn {
  from { opacity: 0; transform: translateY(12px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.grid > div {
  animation: cardIn 0.4s ease-out both;
}

tbody tr {
  animation: cardIn 0.3s ease-out both;
}
</style>
