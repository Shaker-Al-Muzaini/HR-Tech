<template>
  <aside class="fixed top-0 right-0 h-screen bg-white border-l border-gray-100 shadow-2xl z-40 flex flex-col transition-all duration-300"
         :class="collapsed ? 'w-[72px]' : 'w-64'">
    <div class="flex items-center gap-3 px-4 h-16 border-b border-gray-100 flex-shrink-0">
      <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 flex-shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
        </svg>
      </div>
      <div v-if="!collapsed" class="min-w-0 flex-1">
        <p class="font-bold text-gray-900 text-sm truncate">HR Tech</p>
        <p class="text-[10px] text-gray-500 truncate">Smart Interview</p>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-2.5 space-y-1 sidebar-scroll">
      <a v-for="item in navItems" :key="item.name"
         :href="item.disabled ? 'javascript:void(0)' : item.href"
         class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl font-bold text-sm transition-all duration-200"
         :class="[
           item.disabled ? 'text-gray-300 cursor-not-allowed' :
           isActive(item.href) ? 'bg-gradient-to-l from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30' :
           'text-gray-600 hover:bg-indigo-50 hover:text-indigo-700'
         ]"
         @click="item.disabled && $event.preventDefault()">
        <div v-if="isActive(item.href) && !item.disabled"
             class="absolute right-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-full bg-white/80"></div>

        <component :is="getIcon(item.icon)" class="w-5 h-5 flex-shrink-0" />
        <span v-if="!collapsed" class="flex-1 truncate">{{ item.label }}</span>

        <div v-if="collapsed"
             class="absolute right-full mr-2 px-3 py-1.5 bg-gray-900 text-white text-xs font-bold rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap shadow-xl">
          {{ item.label }}
        </div>
      </a>
    </nav>

    <div class="border-t border-gray-100 p-2.5 space-y-1 flex-shrink-0">
      <button class="group relative w-full flex items-center gap-3 px-3 py-2.5 rounded-xl font-bold text-sm transition-all duration-200"
              :class="muted ? 'text-gray-400 hover:bg-gray-50' : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-700'"
              @click="toggleMute">
        <svg v-if="muted" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>
        </svg>
        <svg v-else class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
        </svg>
        <span v-if="!collapsed" class="flex-1 text-right">{{ muted ? 'الأصوات مقلقة' : 'الأصوات مفعّلة' }}</span>
      </button>

      <button class="group relative w-full flex items-center gap-3 px-3 py-2.5 rounded-xl font-bold text-sm text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200"
              @click="$emit('toggle-collapse')">
        <svg class="w-5 h-5 flex-shrink-0 transition-transform" :class="collapsed ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
        </svg>
        <span v-if="!collapsed" class="flex-1 text-right">تصغير القائمة</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed, h } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useSound } from "@/composables/useSound";

defineProps({ collapsed: { type: Boolean, default: false } });
defineEmits(["toggle-collapse"]);

const page = usePage();
const { muted, toggleMute } = useSound();

const currentUrl = computed(() => page.url || "/");

const navItems = [
  { name: "dashboard",  label: "الرئيسية",      icon: "home",  href: "/" },
  { name: "completed",  label: "سجل المقابلات", icon: "check", href: "/hr/completed" },
  { name: "candidates", label: "المرشحون",      icon: "users", href: "/hr/candidates", disabled: true },
  { name: "reports",    label: "التقارير",      icon: "chart", href: "/hr/reports",    disabled: true },
  { name: "settings",   label: "الإعدادات",     icon: "cog",   href: "/hr/settings",   disabled: true },
];

const isActive = (href) => {
  const url = currentUrl.value;
  if (href === "/") return url === "/" || url.startsWith("/?");
  return url === href || url.startsWith(href + "/") || url.startsWith(href + "?");
};

const icons = {
  home: () => h("svg", { fill: "none", stroke: "currentColor", viewBox: "0 0 24 24" }, [
    h("path", { "stroke-linecap": "round", "stroke-linejoin": "round", "stroke-width": 2, d: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" }),
  ]),
  check: () => h("svg", { fill: "none", stroke: "currentColor", viewBox: "0 0 24 24" }, [
    h("path", { "stroke-linecap": "round", "stroke-linejoin": "round", "stroke-width": 2, d: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" }),
  ]),
  users: () => h("svg", { fill: "none", stroke: "currentColor", viewBox: "0 0 24 24" }, [
    h("path", { "stroke-linecap": "round", "stroke-linejoin": "round", "stroke-width": 2, d: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" }),
  ]),
  chart: () => h("svg", { fill: "none", stroke: "currentColor", viewBox: "0 0 24 24" }, [
    h("path", { "stroke-linecap": "round", "stroke-linejoin": "round", "stroke-width": 2, d: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" }),
  ]),
  cog: () => h("svg", { fill: "none", stroke: "currentColor", viewBox: "0 0 24 24" }, [
    h("path", { "stroke-linecap": "round", "stroke-linejoin": "round", "stroke-width": 2, d: "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" }),
    h("path", { "stroke-linecap": "round", "stroke-linejoin": "round", "stroke-width": 2, d: "M15 12a3 3 0 11-6 0 3 3 0 016 0z" }),
  ]),
};

const getIcon = (name) => icons[name] || icons.home;
</script>

<style scoped>
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 2px; }
.sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
.sidebar-scroll { scrollbar-width: thin; scrollbar-color: #e5e7eb transparent; }
</style>
