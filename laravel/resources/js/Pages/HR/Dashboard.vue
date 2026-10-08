<script setup>
import { ref, computed, reactive, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import DateTimePicker from "@/Components/DateTimePicker.vue";
import HRLayout from "@/Layouts/HRLayout.vue";
import { useToast } from "@/composables/useToast";
import { useConfirm } from "@/composables/useConfirm";

defineOptions({ layout: HRLayout });

const props = defineProps({
  stats: Object,
  interviews: Object,
  counts: Object,
  filters: Object,
  candidates: Array,
});

const page = usePage();
const toast = useToast();
const { confirm } = useConfirm();

const searchInput = ref(props.filters?.search || "");
const perPage = ref(props.filters?.per_page || 5);
const showModal = ref(false);
const isEditMode = ref(false);
const newCandidateMode = ref(false);
const submitting = ref(false);
const editingId = ref(null);

const emptyForm = () => ({
  candidate_id: null,
  candidate_name: "",
  candidate_email: "",
  candidate_phone: "",
  candidate_position: "",
  job_title: "",
  department: "",
  scheduled_at: "",
  duration_minutes: 30,
  language: "ar",
});

const form = reactive(emptyForm());

const statCards = computed(() => [
  { label: "مقابلات اليوم", value: props.stats.today_count, icon: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z", iconBg: "bg-blue-100", iconColor: "text-blue-600", valueColor: "text-blue-600", bgCircle: "bg-blue-500" },
  { label: "أُجريت هذا الأسبوع", value: props.stats.week_completed, icon: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z", iconBg: "bg-emerald-100", iconColor: "text-emerald-600", valueColor: "text-emerald-600", bgCircle: "bg-emerald-500" },
  { label: "بانتظار المراجعة", value: props.stats.pending_review, icon: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z", iconBg: "bg-amber-100", iconColor: "text-amber-600", valueColor: "text-amber-600", bgCircle: "bg-amber-500" },
  { label: "مقابلات قادمة", value: props.stats.upcoming_count, icon: "M13 10V3L4 14h7v7l9-11h-7z", iconBg: "bg-purple-100", iconColor: "text-purple-600", valueColor: "text-purple-600", bgCircle: "bg-purple-500" },
]);

const filterTabs = [
  { value: "all", label: "الكل" },
  { value: "today", label: "اليوم" },
  { value: "upcoming", label: "قادمة" },
  { value: "completed", label: "مكتملة" },
  { value: "cancelled", label: "ملغاة" },
];

const visiblePages = computed(() => {
  const current = props.interviews.current_page;
  const last = props.interviews.last_page;
  const pages = [];
  if (last <= 7) { for (let i = 1; i <= last; i++) pages.push(i); return pages; }
  if (current <= 4) return [1, 2, 3, 4, 5, "...", last];
  if (current >= last - 3) return [1, "...", last - 4, last - 3, last - 2, last - 1, last];
  return [1, "...", current - 1, current, current + 1, "...", last];
});

const initials = (name) => {
  if (!name) return "?";
  return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
};

const formatDate = (iso) => {
  if (!iso) return "—";
  const d = new Date(iso);
  const today = new Date();
  if (d.toDateString() === today.toDateString()) return "اليوم";
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  if (d.toDateString() === tomorrow.toDateString()) return "غداً";
  return d.toLocaleDateString("ar-EG", { day: "numeric", month: "short", year: "numeric" });
};

const formatTime = (iso) => {
  if (!iso) return "";
  return new Date(iso).toLocaleTimeString("ar-EG", { hour: "2-digit", minute: "2-digit" });
};

const statusLabel = (s) => ({
  scheduled: "مجدولة",
  in_progress: "جارية الآن",
  starting_soon: "على وشك البدء",
  completed: "مكتملة",
  cancelled: "ملغاة",
  missed: "فائتة",
  expired: "منتهية",
}[s] || s);

const statusClass = (s) => ({
  scheduled: "bg-blue-50 text-blue-700",
  in_progress: "bg-emerald-50 text-emerald-700",
  starting_soon: "bg-amber-50 text-amber-700 animate-pulse",
  completed: "bg-gray-100 text-gray-700",
  cancelled: "bg-red-50 text-red-700",
  missed: "bg-orange-50 text-orange-700",
  expired: "bg-slate-100 text-slate-700",
}[s] || "bg-gray-100 text-gray-700");

const statusDot = (s) => ({
  scheduled: "bg-blue-500",
  in_progress: "bg-emerald-500 animate-pulse",
  starting_soon: "bg-amber-500 animate-pulse",
  completed: "bg-gray-500",
  cancelled: "bg-red-500",
  missed: "bg-orange-500",
  expired: "bg-slate-500",
}[s] || "bg-gray-500");

const changeFilter = (filter) => {
  router.get("/", { filter, search: searchInput.value, per_page: perPage.value }, {
    preserveState: true, preserveScroll: true, replace: true,
  });
};

let searchTimer = null;
const debouncedSearch = () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    router.get("/", { filter: props.filters.filter, search: searchInput.value, per_page: perPage.value }, {
      preserveState: true, preserveScroll: true, replace: true,
    });
  }, 400);
};

const changePerPage = () => {
  router.get("/", { filter: props.filters.filter, search: searchInput.value, per_page: perPage.value }, {
    preserveState: true, preserveScroll: true, replace: true,
  });
};

const goToPage = (page) => {
  router.get("/", {
    filter: props.filters.filter,
    search: searchInput.value,
    per_page: perPage.value,
    page: page,
  }, { preserveState: true, preserveScroll: true });
};

const openCreateModal = () => {
  Object.assign(form, emptyForm());
  const d = new Date(Date.now() + 60 * 60 * 1000);
  d.setMinutes(0);
  form.scheduled_at = d.toISOString().slice(0, 16);
  isEditMode.value = false;
  editingId.value = null;
  newCandidateMode.value = false;
  showModal.value = true;
};

const openEditModal = (interview) => {
  Object.assign(form, {
    candidate_id: interview.candidate?.id,
    candidate_name: interview.candidate?.name || "",
    candidate_email: interview.candidate?.email || "",
    candidate_phone: interview.candidate?.phone || "",
    candidate_position: interview.candidate?.applied_position || "",
    job_title: interview.job_title,
    department: interview.department || "",
    scheduled_at: interview.scheduled_at ? interview.scheduled_at.slice(0, 16) : "",
    duration_minutes: interview.duration_minutes || 30,
    language: interview.language || "ar",
  });
  isEditMode.value = true;
  editingId.value = interview.id;
  newCandidateMode.value = true;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  newCandidateMode.value = false;
};

const submitForm = () => {
  submitting.value = true;
  const url = isEditMode.value ? `/hr/interviews/${editingId.value}` : "/hr/interviews";
  const method = isEditMode.value ? "put" : "post";

  router[method](url, form, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      submitting.value = false;
      closeModal();
      router.reload({
        only: ["interviews", "stats", "counts", "candidates"],
        onSuccess: () => toast.success(isEditMode.value ? "تم تحديث المقابلة" : "تم إنشاء المقابلة"),
      });
    },
    onError: () => {
      submitting.value = false;
      toast.error("حدث خطأ — تحقق من البيانات");
    },
  });
};

const cancelInterview = async (interview) => {
  const ok = await confirm({
    title: "إلغاء المقابلة؟",
    message: `سيتم إلغاء مقابلة ${interview.candidate?.name || "غير معروف"}. لا يمكن التراجع بعد الإلغاء.`,
    type: "warning",
    confirmText: "تأكيد الإلغاء",
    cancelText: "تراجع",
  });
  if (!ok) return;

  submitting.value = true;
  router.post(`/hr/interviews/${interview.id}/cancel`, {}, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      submitting.value = false;
      router.reload({
        only: ["interviews", "stats", "counts"],
        onSuccess: () => toast.warning("تم إلغاء المقابلة"),
      });
    },
    onError: () => {
      submitting.value = false;
      toast.error("فشل الإلغاء");
    },
  });
};

const deleteInterview = async (interview) => {
  const ok = await confirm({
    title: "تأكيد الحذف",
    message: `سيتم حذف مقابلة ${interview.candidate?.name || "غير معروف"} نهائياً. لا يمكن التراجع.`,
    type: "danger",
    confirmText: "حذف نهائياً",
    cancelText: "إلغاء",
  });
  if (!ok) return;

  submitting.value = true;
  router.delete(`/hr/interviews/${interview.id}`, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      submitting.value = false;
      router.reload({
        only: ["interviews", "stats", "counts"],
        onSuccess: () => toast.error("تم حذف المقابلة"),
      });
    },
    onError: () => {
      submitting.value = false;
      toast.error("فشل الحذف");
    },
  });
};

const joinRoom = (interview) => {
  toast.info("جاري فتح غرفة المقابلة...");
  router.visit(`/hr/interview/${interview.id}/session`);
};

const copyCandidateLink = async (interview) => {
  if (!interview.session?.candidate_token) {
    toast.error("لا يوجد رابط لهذه المقابلة");
    return;
  }
  const link = `${window.location.origin}/interview/${interview.session.candidate_token}`;
  try {
    await navigator.clipboard.writeText(link);
    toast.success("تم نسخ رابط المرشح");
  } catch (err) {
    const textarea = document.createElement("textarea");
    textarea.value = link;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    document.body.removeChild(textarea);
    toast.success("تم نسخ رابط المرشح");
  }
};

onMounted(() => {
  if (page.props.flash?.success) toast.success(page.props.flash.success);
  if (page.props.flash?.warning) toast.warning(page.props.flash.warning);
  if (page.props.flash?.error) toast.error(page.props.flash.error);
  if (page.props.errors?.error) toast.error(page.props.errors.error);
});
</script>

<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-3xl font-bold text-gray-900 tracking-tight">الصفحة الرئيسية</h2>
      <p class="text-sm text-gray-500 mt-1">إدارة المقابلات والمرشحين في مكان واحد</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="(card, idx) in statCards" :key="idx"
           class="group relative bg-white rounded-2xl p-5 border border-gray-100/80 hover:border-indigo-200/60 shadow-sm hover:shadow-xl hover:shadow-indigo-100/50 transition-all duration-500 cursor-default"
           :style="{ transitionDelay: `${idx * 40}ms` }">
        <div class="absolute top-0 right-0 w-24 h-24 rounded-full opacity-0 group-hover:opacity-10 transition-opacity duration-500 blur-2xl" :class="card.bgCircle"></div>
        <div class="relative flex items-start justify-between">
          <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ card.label }}</p>
            <p class="text-3xl font-bold mt-2 tabular-nums transition-transform group-hover:scale-110 origin-right duration-500" :class="card.valueColor">{{ card.value }}</p>
          </div>
          <div class="w-11 h-11 rounded-xl flex items-center justify-center transition-transform group-hover:rotate-6 duration-500" :class="card.iconBg">
            <svg class="w-5 h-5" :class="card.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm p-3">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex flex-wrap gap-1.5">
          <button v-for="tab in filterTabs" :key="tab.value" @click="changeFilter(tab.value)"
                  class="relative px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300 active:scale-95"
                  :class="filters.filter === tab.value
                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                    : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900'">
            {{ tab.label }}
            <span class="ml-1 opacity-70 tabular-nums">({{ counts[tab.value] }})</span>
          </button>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
          <div class="relative flex-1 md:w-64">
            <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input v-model="searchInput" @input="debouncedSearch" type="text" placeholder="ابحث..."
                   class="w-full pr-9 pl-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:border-transparent focus:bg-white transition-all duration-300" />
          </div>

          <button @click="openCreateModal"
                  class="group inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 transition-all duration-300 active:scale-95 text-xs whitespace-nowrap">
            <svg class="w-4 h-4 transition-transform group-hover:rotate-90 duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span class="hidden sm:inline">مقابلة جديدة</span>
            <span class="sm:hidden">جديدة</span>
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100/80 shadow-sm overflow-hidden">
      <div class="px-5 py-3 bg-gradient-to-l from-gray-50 to-white border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-3 text-xs">
          <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 rounded-lg">
            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
            </svg>
            <span class="font-bold text-indigo-700 tabular-nums">
              {{ interviews.from || 0 }} - {{ interviews.to || 0 }}
              <span class="text-indigo-500 font-medium">من {{ interviews.total }}</span>
            </span>
          </div>
        </div>

        <div class="flex items-center gap-1">
          <button v-if="interviews.prev_page_url" @click="goToPage(interviews.current_page - 1)"
                  class="group flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200 hover:border-indigo-200 transition-all duration-300 active:scale-95">
            <svg class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            السابق
          </button>
          <span v-else class="px-3 py-1.5 rounded-lg text-xs font-bold bg-gray-50 text-gray-300 border border-gray-100 cursor-not-allowed">السابق</span>

          <template v-for="(link, idx) in visiblePages" :key="idx">
            <button v-if="link !== '...'" @click="goToPage(link)"
                    class="w-8 h-8 rounded-lg text-xs font-bold transition-all duration-300 active:scale-90 tabular-nums"
                    :class="link === interviews.current_page
                      ? 'bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30 scale-105'
                      : 'bg-white text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200'">
              {{ link }}
            </button>
            <span v-else class="w-8 h-8 flex items-center justify-center text-gray-400 text-xs">…</span>
          </template>

          <button v-if="interviews.next_page_url" @click="goToPage(interviews.current_page + 1)"
                  class="group flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200 hover:border-indigo-200 transition-all duration-300 active:scale-95">
            التالي
            <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <span v-else class="px-3 py-1.5 rounded-lg text-xs font-bold bg-gray-50 text-gray-300 border border-gray-100 cursor-not-allowed">التالي</span>
        </div>

        <div class="flex items-center gap-2 text-xs">
          <span class="text-gray-500 font-medium">عرض</span>
          <select v-model.number="perPage" @change="changePerPage"
                  class="px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs font-bold bg-white text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer hover:border-indigo-300 transition-colors duration-300">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
          <span class="text-gray-500 font-medium">لكل صفحة</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full table-fixed">
          <thead class="bg-gray-50/70">
            <tr>
              <th class="w-[22%] px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">المرشح</th>
              <th class="w-[20%] px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">الوظيفة</th>
              <th class="w-[15%] px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">التوقيت</th>
              <th class="w-[8%] px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">المدة</th>
              <th class="w-[12%] px-5 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">الحالة</th>
              <th class="w-[23%] px-5 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">الإجراءات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="interviews.data.length === 0">
              <td colspan="6" class="py-16">
                <div class="text-center">
                  <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                  <h3 class="text-base font-bold text-gray-700 mb-1">لا توجد مقابلات</h3>
                  <p class="text-gray-500 text-xs mb-4">{{ filters.search ? "لا نتائج مطابقة" : "ابدأ بإنشاء أول مقابلة" }}</p>
                  <button v-if="!filters.search" @click="openCreateModal"
                          class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold text-xs transition-all duration-300 active:scale-95">
                    + إنشاء مقابلة
                  </button>
                </div>
              </td>
            </tr>

            <tr v-for="(interview, idx) in interviews.data" :key="interview.id"
                class="group hover:bg-indigo-50/40 transition-all duration-300"
                :style="{ animationDelay: `${idx * 50}ms` }">
              <td class="w-[22%] px-5 py-3.5">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs flex-shrink-0 shadow-md shadow-indigo-500/30 transition-transform group-hover:scale-110 duration-300">
                    {{ initials(interview.candidate?.name) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-gray-900 text-sm truncate">{{ interview.candidate?.name || "غير معروف" }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ interview.candidate?.email || "-" }}</p>
                  </div>
                </div>
              </td>

              <td class="w-[20%] px-5 py-3.5">
                <p class="font-semibold text-gray-900 text-sm truncate">{{ interview.job_title }}</p>
                <p class="text-xs text-gray-500 truncate">{{ interview.department || "—" }}</p>
              </td>

              <td class="w-[15%] px-5 py-3.5">
                <p class="font-semibold text-gray-900 text-sm">{{ formatDate(interview.scheduled_at) }}</p>
                <p class="text-xs text-gray-500 tabular-nums">{{ formatTime(interview.scheduled_at) }}</p>
              </td>

              <td class="w-[8%] px-5 py-3.5">
                <span class="text-sm font-medium text-gray-700 tabular-nums">{{ interview.duration_minutes }} د</span>
              </td>

              <td class="w-[12%] px-5 py-3.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold whitespace-nowrap" :class="statusClass(interview.display_status)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(interview.display_status)"></span>
                  {{ statusLabel(interview.display_status) }}
                </span>
              </td>

              <td class="w-[23%] px-5 py-3.5">
                <div class="flex items-center justify-end gap-1">
                  <button v-if="interview.session?.candidate_token" @click="copyCandidateLink(interview)"
                          class="p-1.5 rounded-lg text-purple-600 hover:bg-purple-100 hover:scale-110 active:scale-95 transition-all duration-300" title="نسخ رابط المرشح">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                    </svg>
                  </button>

                  <button v-if="['scheduled', 'in_progress', 'starting_soon'].includes(interview.display_status)" @click="joinRoom(interview)"
                          class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-100 hover:scale-110 active:scale-95 transition-all duration-300" title="دخول الغرفة">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                  </button>

                  <a v-if="interview.status === 'completed'" :href="`/hr/completed/${interview.id}`"
                     class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-100 hover:scale-110 active:scale-95 transition-all duration-300" title="عرض التفاصيل">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </a>

                  <button v-if="interview.status !== 'cancelled' && interview.status !== 'completed'" @click="openEditModal(interview)"
                          class="p-1.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:scale-110 active:scale-95 transition-all duration-300" title="تعديل">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </button>

                  <button v-if="interview.status === 'scheduled'" @click="cancelInterview(interview)"
                          class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-100 hover:scale-110 active:scale-95 transition-all duration-300" title="إلغاء المقابلة">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                  </button>

                  <button @click="deleteInterview(interview)"
                          class="p-1.5 rounded-lg text-red-500 hover:bg-red-100 hover:scale-110 active:scale-95 transition-all duration-300" title="حذف">
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

    <!-- ═══ Create/Edit Modal ═══ -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
          <Transition appear enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
              <div class="flex-shrink-0 bg-gradient-to-l from-indigo-50 to-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <div>
                  <h3 class="text-xl font-bold text-gray-900">{{ isEditMode ? "تعديل المقابلة" : "مقابلة جديدة" }}</h3>
                  <p class="text-sm text-gray-500 mt-0.5">{{ isEditMode ? "قم بتحديث البيانات أو تأجيل الموعد" : "املأ البيانات لإنشاء مقابلة جديدة" }}</p>
                </div>
                <button @click="closeModal" class="p-2 rounded-lg text-gray-400 hover:bg-white hover:text-gray-700 transition-all duration-300 active:scale-90">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>

              <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto modal-scroll">
                <div class="p-6 space-y-6">
                  <div>
                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                      <span class="w-1 h-4 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></span>
                      بيانات المرشح
                    </h4>

                    <div v-if="!isEditMode && !newCandidateMode">
                      <label class="block text-xs font-semibold text-gray-600 mb-2">اختر مرشحاً موجوداً أو أنشئ جديداً</label>
                      <div class="flex gap-2">
                        <select v-model="form.candidate_id" class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300">
                          <option :value="null">-- مرشح جديد --</option>
                          <option v-for="c in candidates" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
                        </select>
                        <button type="button" @click="newCandidateMode = true"
                                class="px-4 py-2.5 bg-indigo-50 text-indigo-600 font-bold text-xs rounded-xl hover:bg-indigo-100 transition-all duration-300 active:scale-95 whitespace-nowrap">
                          + مرشح جديد
                        </button>
                      </div>
                    </div>

                    <div v-if="(newCandidateMode || !form.candidate_id || isEditMode)" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                      <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">الاسم الكامل *</label>
                        <input v-model="form.candidate_name" type="text" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300" placeholder="مثال: أحمد محمد" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">البريد الإلكتروني *</label>
                        <input v-model="form.candidate_email" type="email" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300" placeholder="ahmed@example.com" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">رقم الهاتف</label>
                        <input v-model="form.candidate_phone" type="tel" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300" placeholder="0599123456" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">الوظيفة المتقدم لها</label>
                        <input v-model="form.candidate_position" type="text" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300" placeholder="مطور واجهات" />
                      </div>
                    </div>
                  </div>

                  <div>
                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                      <span class="w-1 h-4 bg-gradient-to-b from-purple-500 to-pink-500 rounded-full"></span>
                      تفاصيل المقابلة
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-2">عنوان الوظيفة *</label>
                        <input v-model="form.job_title" type="text" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300" placeholder="Frontend Developer" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">القسم</label>
                        <input v-model="form.department" type="text" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300" placeholder="Engineering" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">اللغة *</label>
                        <select v-model="form.language" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300">
                          <option value="ar">العربية</option>
                          <option value="en">English</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">التاريخ والوقت *</label>
                        <DateTimePicker v-model="form.scheduled_at" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">المدة (بالدقائق) *</label>
                        <select v-model="form.duration_minutes" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300">
                          <option :value="15">15 دقيقة</option>
                          <option :value="30">30 دقيقة</option>
                          <option :value="45">45 دقيقة</option>
                          <option :value="60">60 دقيقة</option>
                          <option :value="90">90 دقيقة</option>
                        </select>
                      </div>
                    </div>

                    <div v-if="isEditMode" class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                      <p class="text-xs text-amber-800 flex items-start gap-2">
                        <span class="text-base">ℹ️</span>
                        <span>الحالة تتغير تلقائياً حسب الوقت. لإلغاء المقابلة، استخدم زر "إلغاء" في الجدول.</span>
                      </p>
                    </div>
                  </div>
                </div>
              </form>

              <div class="flex-shrink-0 flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gradient-to-l from-gray-50 to-white">
                <button type="button" @click="closeModal" class="px-6 py-2.5 text-gray-700 font-bold text-sm hover:bg-white rounded-xl transition-all duration-300 active:scale-95">إلغاء</button>
                <button type="button" @click="submitForm" :disabled="submitting"
                        class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 disabled:opacity-50 transition-all duration-300 active:scale-95">
                  {{ submitting ? "جاري الحفظ..." : (isEditMode ? "حفظ التعديلات" : "إنشاء المقابلة") }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style>
.modal-scroll::-webkit-scrollbar { display: none !important; width: 0 !important; }
.modal-scroll { -ms-overflow-style: none !important; scrollbar-width: none !important; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

tbody tr {
  animation: fadeIn 0.4s ease-out;
  animation-fill-mode: both;
}
</style>
