<template>
  <div>
    <!-- Trigger Button -->
    <button type="button"
            @click.stop="openPicker"
            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-white hover:border-indigo-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all flex items-center justify-between text-right">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>
        <div class="text-right">
          <p class="text-sm font-bold text-gray-900">{{ displayDate }}</p>
          <p class="text-xs text-gray-500">{{ displayTime }}</p>
        </div>
      </div>
      <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>

    <!-- Modal Overlay -->
    <!-- Modal Overlay -->
    <Teleport to="body">
      <div v-if="isOpen"
           class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
           @click.self="closePicker"
           dir="rtl">
        
        <!-- Modal Box -->
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden"
             @click.stop>

          <!-- ═══ Header (ثابت) ═══ -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="font-bold text-lg text-gray-900">اختر التاريخ والوقت</h3>
            <button type="button"
                    @click="closePicker"
                    class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 transition">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- ═══ Body (Scrollable) ═══ -->
          <div class="flex-1 overflow-y-auto px-5 py-4">

            <!-- Quick Shortcuts -->
            <div class="grid grid-cols-3 gap-2 mb-4">
              <button type="button"
                      @click="setQuick('now')"
                      class="px-2 py-2 text-xs font-bold rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                بعد ساعة
              </button>
              <button type="button"
                      @click="setQuick('tomorrow')"
                      class="px-2 py-2 text-xs font-bold rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition">
                غداً 10ص
              </button>
              <button type="button"
                      @click="setQuick('week')"
                      class="px-2 py-2 text-xs font-bold rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition">
                بعد أسبوع
              </button>
            </div>

            <!-- Month Nav -->
            <div class="flex items-center justify-between mb-3">
              <button type="button"
                      @click="changeMonth(-1)"
                      class="w-9 h-9 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </button>
              <p class="text-sm font-bold text-gray-900">{{ monthLabel }}</p>
              <button type="button"
                      @click="changeMonth(1)"
                      class="w-9 h-9 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
              </button>
            </div>

            <!-- Weekdays -->
            <div class="grid grid-cols-7 gap-1 mb-2">
              <div v-for="day in weekdays" :key="day"
                   class="text-center text-xs font-bold text-gray-400 py-1">
                {{ day }}
              </div>
            </div>

            <!-- Calendar -->
            <div class="grid grid-cols-7 gap-1 mb-4">
              <button v-for="(day, idx) in calendarDays" :key="idx"
                      type="button"
                      :disabled="!day.current"
                      @click="selectDay(day)"
                      class="aspect-square rounded-lg text-sm font-bold transition-all"
                      :class="dayClass(day)">
                {{ day.day }}
              </button>
            </div>

            <!-- Time Selection -->
            <div class="border-t border-gray-100 pt-4 mb-4">
              <p class="text-xs font-bold text-gray-500 mb-2">الوقت</p>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">الساعة</label>
                  <select v-model.number="selectedHour"
                          class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-bold text-center focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option v-for="h in 24" :key="h" :value="h - 1">
                      {{ String(h - 1).padStart(2, '0') }}
                    </option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">الدقيقة</label>
                  <select v-model.number="selectedMinute"
                          class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-bold text-center focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option v-for="m in [0, 15, 30, 45]" :key="m" :value="m">
                      {{ String(m).padStart(2, '0') }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Preview -->
            <div class="bg-indigo-50 rounded-lg p-3">
              <p class="text-xs text-indigo-600 font-medium mb-1">الموعد المحدد:</p>
              <p class="text-sm font-bold text-indigo-900">
                {{ previewDate }}
              </p>
            </div>
          </div>

          <!-- ═══ Actions (ثابت في الأسفل) ═══ -->
          <div class="flex gap-2 px-5 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
            <button type="button"
                    @click="closePicker"
                    class="flex-1 px-4 py-2.5 text-gray-700 font-semibold border border-gray-200 rounded-lg hover:bg-white transition bg-white">
              إلغاء
            </button>
            <button type="button"
                    @click="confirm"
                    class="flex-1 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-lg hover:shadow-lg hover:shadow-indigo-500/30 transition-all active:scale-95">
              تأكيد
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const today = new Date();
const currentMonth = ref(today.getMonth());
const currentYear = ref(today.getFullYear());
const selectedDate = ref(today);
const selectedHour = ref(today.getHours());
const selectedMinute = ref(0);

const weekdays = ['س', 'ح', 'ن', 'ث', 'ر', 'خ', 'ج'];
const monthNames = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

const monthLabel = computed(() => `${monthNames[currentMonth.value]} ${currentYear.value}`);

// ═══════════════════════════════════════════════════════════
// Display
// ═══════════════════════════════════════════════════════════
const displayDate = computed(() => {
  if (!props.modelValue) return 'اختر التاريخ والوقت';
  try {
    return new Date(props.modelValue).toLocaleDateString('ar-EG', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch {
    return 'اختر التاريخ';
  }
});

const displayTime = computed(() => {
  if (!props.modelValue) return 'لم يتم التحديد';
  try {
    return new Date(props.modelValue).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
});

const previewDate = computed(() => {
  const d = new Date(selectedDate.value);
  d.setHours(selectedHour.value);
  d.setMinutes(selectedMinute.value);
  return d.toLocaleDateString('ar-EG', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }) + ' — ' + String(selectedHour.value).padStart(2, '0') + ':' + String(selectedMinute.value).padStart(2, '0');
});

// ═══════════════════════════════════════════════════════════
// Calendar
// ═══════════════════════════════════════════════════════════
const calendarDays = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value, 1);
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0);
  const startDay = firstDay.getDay();
  const daysInMonth = lastDay.getDate();
  const days = [];

  for (let i = 0; i < startDay; i++) {
    days.push({ day: '', current: false });
  }
  for (let d = 1; d <= daysInMonth; d++) {
    days.push({ day: d, current: true, fullDate: new Date(currentYear.value, currentMonth.value, d) });
  }
  return days;
});

const dayClass = (day) => {
  if (!day.current) return 'text-gray-200 cursor-default';
  const isSelected = selectedDate.value && day.fullDate.toDateString() === selectedDate.value.toDateString();
  const isToday = day.fullDate.toDateString() === today.toDateString();
  if (isSelected) return 'bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/30';
  if (isToday) return 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100';
  return 'text-gray-700 hover:bg-gray-100';
};

// ═══════════════════════════════════════════════════════════
// Actions
// ═══════════════════════════════════════════════════════════
const openPicker = () => {
  if (props.modelValue) {
    const d = new Date(props.modelValue);
    selectedDate.value = d;
    selectedHour.value = d.getHours();
    selectedMinute.value = d.getMinutes();
    currentMonth.value = d.getMonth();
    currentYear.value = d.getFullYear();
  }
  isOpen.value = true;
};

const closePicker = () => {
  isOpen.value = false;
};

const changeMonth = (delta) => {
  let m = currentMonth.value + delta;
  let y = currentYear.value;
  if (m < 0) { m = 11; y--; }
  if (m > 11) { m = 0; y++; }
  currentMonth.value = m;
  currentYear.value = y;
};

const selectDay = (day) => {
  if (!day.current) return;
  selectedDate.value = day.fullDate;
};

const setQuick = (type) => {
  const now = new Date();
  if (type === 'now') {
    now.setHours(now.getHours() + 1);
    now.setMinutes(0);
  } else if (type === 'tomorrow') {
    now.setDate(now.getDate() + 1);
    now.setHours(10);
    now.setMinutes(0);
  } else if (type === 'week') {
    now.setDate(now.getDate() + 7);
    now.setHours(10);
    now.setMinutes(0);
  }
  selectedDate.value = new Date(now);
  selectedHour.value = now.getHours();
  selectedMinute.value = now.getMinutes();
  currentMonth.value = now.getMonth();
  currentYear.value = now.getFullYear();
};

const confirm = () => {
  const d = new Date(selectedDate.value);
  d.setHours(selectedHour.value);
  d.setMinutes(selectedMinute.value);
  d.setSeconds(0);

  const iso = d.getFullYear() + '-' +
    String(d.getMonth() + 1).padStart(2, '0') + '-' +
    String(d.getDate()).padStart(2, '0') + 'T' +
    String(d.getHours()).padStart(2, '0') + ':' +
    String(d.getMinutes()).padStart(2, '0');

  emit('update:modelValue', iso);
  isOpen.value = false;
};

watch(() => props.modelValue, (val) => {
  if (!val) return;
  try {
    const d = new Date(val);
    selectedDate.value = d;
    selectedHour.value = d.getHours();
    selectedMinute.value = d.getMinutes();
    currentMonth.value = d.getMonth();
    currentYear.value = d.getFullYear();
  } catch {}
}, { immediate: true });
</script>