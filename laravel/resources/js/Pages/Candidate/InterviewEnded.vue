<template>
  <div class="min-h-screen bg-gray-900 flex items-center justify-center p-6 text-white font-sans">
    <div class="max-w-md w-full bg-gray-800 rounded-2xl shadow-2xl p-10 text-center border border-gray-700">
      
      <!-- Icon -->
      <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-blue-900 flex items-center justify-center">
        <svg class="w-14 h-14 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>

      <!-- Message -->
      <h2 class="text-2xl font-bold mb-3">انتهت المقابلة</h2>
      <p class="text-gray-400 mb-8">{{ message }}</p>

      <!-- Info -->
      <div class="bg-gray-700 rounded-lg p-4 mb-6 text-sm text-gray-300">
        <p>شكراً لمشاركتك في المقابلة.</p>
        <p>سيتم التواصل معك قريباً.</p>
      </div>

      <!-- Action -->
      <a href="/"
         class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">
        العودة للصفحة الرئيسية
      </a>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  message: { type: String, default: 'هذه المقابلة قد انتهت بالفعل.' },
});
</script>