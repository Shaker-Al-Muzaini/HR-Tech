<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0">
      <div v-if="state.show"
           class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
           dir="rtl"
           @click.self="_cancel">
        <Transition appear
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="p-6">
              <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4" :class="iconBgClass">
                <svg v-if="state.type === 'danger'" class="w-7 h-7" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <svg v-else-if="state.type === 'warning'" class="w-7 h-7" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <svg v-else-if="state.type === 'success'" class="w-7 h-7" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <svg v-else class="w-7 h-7" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <h3 class="text-lg font-bold text-center text-gray-900 mb-2">{{ state.title }}</h3>
              <p class="text-center text-gray-600 text-sm leading-relaxed">{{ state.message }}</p>
            </div>
            <div class="flex gap-3 px-6 pb-6">
              <button @click="_cancel" class="flex-1 px-4 py-2.5 text-gray-700 font-bold text-sm border border-gray-200 rounded-xl hover:bg-gray-50 transition-all duration-300 active:scale-95">{{ state.cancelText }}</button>
              <button @click="_accept" class="flex-1 px-4 py-2.5 text-white font-bold text-sm rounded-xl transition-all duration-300 active:scale-95 shadow-lg" :class="confirmBtnClass">{{ state.confirmText }}</button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from "vue";
import { useConfirm } from "@/composables/useConfirm";
const { state, _accept, _cancel } = useConfirm();
const iconBgClass = computed(() => ({ danger: "bg-red-100", warning: "bg-amber-100", success: "bg-emerald-100", info: "bg-blue-100" }[state.type] || "bg-gray-100"));
const iconColorClass = computed(() => ({ danger: "text-red-600", warning: "text-amber-600", success: "text-emerald-600", info: "text-blue-600" }[state.type] || "text-gray-600"));
const confirmBtnClass = computed(() => ({ danger: "bg-red-600 hover:bg-red-700 shadow-red-500/30", warning: "bg-amber-500 hover:bg-amber-600 shadow-amber-500/30", success: "bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/30", info: "bg-blue-600 hover:bg-blue-700 shadow-blue-500/30" }[state.type] || "bg-indigo-600 hover:bg-indigo-700 shadow-indigo-500/30"));
</script>
