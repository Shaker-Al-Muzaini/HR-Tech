<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 font-sans" dir="rtl">
    <Sidebar :collapsed="collapsed" @toggle-collapse="collapsed = !collapsed" />

    <div class="transition-all duration-300" :class="collapsed ? 'mr-[72px]' : 'mr-64'">
      <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-xl border-b border-gray-100">
        <div class="px-6 py-3 flex items-center justify-between">
          <div class="flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span class="text-gray-300">/</span>
            <span class="font-bold text-gray-900">{{ pageTitle }}</span>
          </div>

          <button class="group relative flex items-center gap-2 p-1 pr-3 rounded-full bg-gray-50 hover:bg-gray-100 border border-gray-200 transition-all duration-300 active:scale-95">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-white font-bold text-sm shadow-md">H</div>
            <svg class="w-3 h-3 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
        </div>
      </header>

      <main class="p-6">
        <slot />
      </main>
    </div>

    <GlobalUI />
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";

const collapsed = ref(false);
const page = usePage();

const pageTitle = computed(() => {
  const url = page.url || "/";
  if (url === "/" || url.startsWith("/?")) return "الرئيسية";
  if (url.startsWith("/hr/completed")) return "سجل المقابلات";
  if (url.startsWith("/hr/interview")) return "غرفة المقابلة";
  if (url.startsWith("/hr/candidates")) return "المرشحون";
  if (url.startsWith("/hr/reports")) return "التقارير";
  if (url.startsWith("/hr/settings")) return "الإعدادات";
  return "لوحة التحكم";
});
</script>
