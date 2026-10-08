<template>
  <Teleport to="body">
    <div class="fixed bottom-6 left-6 z-[100] flex flex-col gap-2 pointer-events-none" dir="rtl">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0 scale-95"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in absolute"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95 translate-x-[-100%]"
        move-class="transition duration-200">
        <div v-for="toast in toasts" :key="toast.id"
             class="pointer-events-auto min-w-[300px] max-w-md px-5 py-3.5 rounded-2xl shadow-2xl backdrop-blur-lg flex items-center gap-3 cursor-pointer group"
             :class="toastClass(toast.type)"
             @click="remove(toast.id)">
          <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-white/20">
            <svg v-if="toast.type === 'success'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
            <svg v-else-if="toast.type === 'error'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <svg v-else-if="toast.type === 'warning'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <span class="flex-1 text-sm font-bold leading-tight">{{ toast.message }}</span>
          <button class="opacity-50 group-hover:opacity-100 transition-opacity" @click.stop="remove(toast.id)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from "@/composables/useToast";
const { toasts, remove } = useToast();
const toastClass = (type) => ({
  success: "bg-emerald-600/95 text-white shadow-emerald-500/30",
  error: "bg-red-600/95 text-white shadow-red-500/30",
  warning: "bg-amber-500/95 text-white shadow-amber-500/30",
  info: "bg-slate-800/95 text-white shadow-slate-500/30",
}[type] || "bg-slate-800/95 text-white");
</script>
