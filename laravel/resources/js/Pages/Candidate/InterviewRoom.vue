<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 flex items-center justify-center p-6 font-sans" dir="rtl">

    <Transition
      enter-active-class="transition duration-500 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100">
      <div v-if="interviewEnded" class="fixed inset-0 bg-black/90 backdrop-blur-lg z-50 flex items-center justify-center p-6">
        <div class="bg-white/10 backdrop-blur-2xl rounded-3xl shadow-2xl p-10 max-w-md text-center border border-white/20">
          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-emerald-500/20 flex items-center justify-center border-2 border-emerald-500/50">
            <svg class="w-14 h-14 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-white mb-3">تم إنهاء المقابلة</h2>
          <p class="text-white/70 mb-8">شكراً لمشاركتك — سيتم التواصل معك قريباً</p>
          <button @click="closeSession"
                  class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all">
            إغلاق الغرفة
          </button>
        </div>
      </div>
    </Transition>

    <div class="w-full max-w-6xl">

      <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/50">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-bold text-white">غرفة المقابلة</h1>
            <p class="text-xs text-white/60">جلسة مباشرة مع فريق التوظيف</p>
          </div>
        </div>

        <div class="flex items-center gap-2 px-4 py-2 rounded-full backdrop-blur-lg border transition-all"
             :class="statusBadgeClass">
          <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="statusDotColor"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="statusDotColor"></span>
          </span>
          <span class="text-xs font-bold">{{ status }}</span>
        </div>
      </div>

      <Transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0">
        <div v-if="cameraError"
             class="mb-5 p-5 rounded-2xl bg-gradient-to-l from-red-500/20 to-orange-500/20 backdrop-blur-2xl border border-red-400/30">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center border border-red-400/50 flex-shrink-0">
              <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <p class="font-bold text-red-200 mb-1">{{ cameraError.title }}</p>
              <p class="text-sm text-red-300/80 mb-3">{{ cameraError.message }}</p>
              <button @click="startCamera"
                      class="px-4 py-2 bg-red-600 text-white font-bold text-xs rounded-lg hover:bg-red-700 transition-all active:scale-95">
                إعادة المحاولة
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <Transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0">
        <div v-if="waitingForHr && cameraActive && !interviewEnded"
             class="mb-5 p-6 rounded-2xl bg-gradient-to-l from-indigo-500/20 to-purple-500/20 backdrop-blur-2xl border border-indigo-400/30 shadow-2xl">
          <div class="flex items-center gap-5">
            <div class="relative w-14 h-14 flex-shrink-0">
              <div class="absolute inset-0 bg-indigo-500 rounded-full opacity-40 animate-ping"></div>
              <div class="relative w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                <svg class="w-7 h-7 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-bold text-white mb-1">في غرفة الانتظار</h3>
              <p class="text-sm text-white/70">تم إشعار المشرف — سيتم قبولك خلال لحظات...</p>
            </div>
            <div class="flex gap-1">
              <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
              <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
              <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
            </div>
          </div>
        </div>
      </Transition>

      <Transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0">
        <div v-if="hrLeftNotification && cameraActive && !waitingForHr && !interviewEnded"
             class="mb-5 p-5 rounded-2xl bg-gradient-to-l from-amber-500/20 to-orange-500/20 backdrop-blur-2xl border border-amber-400/30">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-amber-500/20 flex items-center justify-center border border-amber-400/50">
              <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
            </div>
            <div>
              <p class="font-bold text-amber-200">المشرف غادر مؤقتاً</p>
              <p class="text-sm text-amber-300/80">ابق في مكانك — قد يعود قريباً</p>
            </div>
          </div>
        </div>
      </Transition>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <div class="lg:col-span-2">
          <div class="relative bg-black rounded-3xl overflow-hidden shadow-2xl aspect-video border border-white/10">
            <video ref="localVideo" class="w-full h-full object-cover transform -scale-x-100" autoplay playsinline muted></video>

            <Transition
              enter-active-class="transition duration-500 ease-out"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100">
              <div v-if="remoteVideoActive"
                   class="absolute bottom-5 left-5 w-44 h-32 bg-gray-900 rounded-2xl overflow-hidden shadow-2xl border-2 border-emerald-500/70 z-20">
                <video ref="remoteVideo" class="w-full h-full object-cover" autoplay playsinline></video>
                <div class="absolute bottom-2 right-2 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white text-xs px-2.5 py-1 rounded-lg font-bold shadow-lg">
                  HR
                </div>
              </div>
            </Transition>

            <div v-if="!cameraActive" class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-gray-900 to-black">
              <div class="w-24 h-24 mb-6 rounded-full bg-white/5 flex items-center justify-center border border-white/10">
                <svg class="w-12 h-12 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <p class="text-white/60 text-lg mb-6">الكاميرا والميكروفون بانتظار الإذن</p>
              <button @click="startCamera"
                      class="group px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-2xl shadow-2xl shadow-indigo-500/40 hover:shadow-3xl hover:scale-105 active:scale-95 transition-all">
                <span class="flex items-center gap-3">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                  </svg>
                  السماح بالكاميرا والميكروفون
                </span>
              </button>
            </div>

            <div v-if="waitingForHr && cameraActive"
                 class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-10">
              <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-indigo-500/20 flex items-center justify-center border-2 border-indigo-400/50">
                  <svg class="w-10 h-10 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <p class="text-xl font-bold text-white">بانتظار موافقة المشرف</p>
                <p class="text-sm text-white/60 mt-2">سيبدأ الاتصال بمجرد الموافقة</p>
              </div>
            </div>

            <div v-if="connectionStatus === 'connected'" class="absolute top-5 right-5 flex items-center gap-2 bg-red-600/90 backdrop-blur-lg text-white px-4 py-2 rounded-full text-sm font-bold shadow-2xl">
              <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
              </span>
              مباشر
            </div>
          </div>

          <div v-if="cameraActive" class="mt-5 p-4 rounded-2xl bg-white/5 backdrop-blur-2xl border border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full" :class="micLevel > 0.1 ? 'bg-emerald-500' : 'bg-gray-500'"></div>
                <span class="text-xs text-white/60">الميكروفون</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span class="text-xs text-white/60">الكاميرا</span>
              </div>
            </div>

            <button @click="stopInterview"
                    class="px-6 py-2.5 bg-red-600/90 hover:bg-red-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-red-500/20 transition-all active:scale-95 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
              مغادرة
            </button>
          </div>
        </div>

        <div class="lg:col-span-1 flex flex-col gap-4">

          <div class="p-5 rounded-2xl bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-2xl border border-white/10">
            <h3 class="text-sm font-bold text-white mb-4">حالتك الحالية</h3>
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-xs text-white/60">الاتصال</span>
                <span class="text-sm font-bold" :class="connectionStatus === 'connected' ? 'text-emerald-400' : 'text-yellow-400'">
                  {{ connectionStatus === 'connected' ? '✅ متصل' : '⏳ جاري' }}
                </span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-xs text-white/60">المشرف</span>
                <span class="text-sm font-bold" :class="remoteVideoActive ? 'text-emerald-400' : 'text-gray-400'">
                  {{ remoteVideoActive ? '✅ موجود' : '⏳ بانتظار' }}
                </span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-xs text-white/60">المعايرة</span>
                <span class="text-sm font-bold" :class="isCalibrating ? 'text-yellow-400' : 'text-emerald-400'">
                  {{ isCalibrating ? '⏳ جارية' : '✅ مكتملة' }}
                </span>
              </div>
            </div>
          </div>

          <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-purple-500/10 backdrop-blur-2xl border border-indigo-400/20">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
              </svg>
              <h3 class="text-sm font-bold text-white">نصائح للمقابلة</h3>
            </div>
            <ul class="space-y-2.5">
              <li class="flex items-start gap-2 text-xs text-white/70">
                <span class="text-emerald-400 mt-0.5">✓</span>
                انظر نحو الكاميرا أثناء التحدث
              </li>
              <li class="flex items-start gap-2 text-xs text-white/70">
                <span class="text-emerald-400 mt-0.5">✓</span>
                تحدث بوضوح وبصوت عادي
              </li>
              <li class="flex items-start gap-2 text-xs text-white/70">
                <span class="text-emerald-400 mt-0.5">✓</span>
                اجلس في مكان هادئ ومضاء
              </li>
              <li class="flex items-start gap-2 text-xs text-white/70">
                <span class="text-emerald-400 mt-0.5">✓</span>
                احتفظ بهاتفك بعيداً
              </li>
            </ul>
          </div>

          <div v-if="isCalibrating" class="p-5 rounded-2xl bg-gradient-to-br from-yellow-500/10 to-amber-500/10 backdrop-blur-2xl border border-yellow-400/20">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl bg-yellow-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-400 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm font-bold text-yellow-200">جاري المعايرة</p>
                <p class="text-xs text-yellow-200/60">النظر للكاميرا والتحدث بشكل طبيعي</p>
              </div>
            </div>
            <div class="h-2 bg-white/10 rounded-full overflow-hidden">
              <div class="h-full bg-gradient-to-r from-yellow-500 to-amber-500 rounded-full animate-pulse" style="width: 60%"></div>
            </div>
          </div>

        </div>
      </div>

      <div class="mt-5 text-center text-xs text-white/40">
        <p>© Smart HR Tech — جميع الحقوق محفوظة</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted, nextTick } from 'vue';
import { useToast } from '@/composables/useToast';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps({
  token: { type: String, required: true },
  sessionUuid: { type: String, default: null },
  language: { type: String, default: 'ar' },
});

const toast = useToast();
const { confirm } = useConfirm();

const localVideo = ref(null);
const remoteVideo = ref(null);
const cameraActive = ref(false);
const remoteVideoActive = ref(false);
const hrLeftNotification = ref(false);
const interviewEnded = ref(false);
const waitingForHr = ref(false);
const hrApproved = ref(false);
const status = ref('بانتظار الصلاحيات');
const isCalibrating = ref(false);
const connectionStatus = ref('idle');
const micLevel = ref(0);
const cameraError = ref(null);

let localStream = null;
let remoteStream = new MediaStream();
let peerConnection = null;
let ws = null;
let reconnectTimer = null;
let audioContext = null;
let analyser = null;
let micAnimation = null;

const statusBadgeClass = computed(() => {
  if (waitingForHr.value) return 'bg-indigo-500/20 text-indigo-200 border-indigo-400/30';
  if (connectionStatus.value === 'connected') return 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30';
  if (connectionStatus.value === 'error') return 'bg-red-500/20 text-red-300 border-red-400/30';
  return 'bg-yellow-500/20 text-yellow-200 border-yellow-400/30';
});

const statusDotColor = computed(() => {
  if (waitingForHr.value) return 'bg-indigo-400';
  if (connectionStatus.value === 'connected') return 'bg-emerald-500';
  if (connectionStatus.value === 'error') return 'bg-red-500';
  return 'bg-yellow-500';
});

const FASTAPI_URL = import.meta.env.VITE_FASTAPI_URL || 'http://localhost:8001';
const FASTAPI_API_KEY = import.meta.env.VITE_FASTAPI_API_KEY || 'dev-api-key-change-in-production';

const startMicMonitoring = () => {
  try {
    audioContext = new AudioContext();
    const source = audioContext.createMediaStreamSource(localStream);
    analyser = audioContext.createAnalyser();
    analyser.fftSize = 256;
    source.connect(analyser);

    const dataArray = new Uint8Array(analyser.frequencyBinCount);
    const update = () => {
      if (!analyser) return;
      analyser.getByteFrequencyData(dataArray);
      const avg = dataArray.reduce((a, b) => a + b, 0) / dataArray.length;
      micLevel.value = avg / 255;
      micAnimation = requestAnimationFrame(update);
    };
    update();
  } catch (e) {}
};

const startCamera = async () => {
  cameraError.value = null;
  try {
    status.value = 'جاري الاتصال بالكاميرا...';
    connectionStatus.value = 'connecting';

    localStream = await navigator.mediaDevices.getUserMedia({
      video: { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 30 } },
      audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
        sampleRate: 48000,
        channelCount: 1,
      }
    });

    localVideo.value.srcObject = localStream;
    cameraActive.value = true;
    startMicMonitoring();

    waitingForHr.value = true;
    status.value = 'في غرفة الانتظار';

    connectWebSocket();
    toast.success('تم تشغيل الكاميرا بنجاح');
  } catch (err) {
    console.error("Camera error:", err);
    status.value = 'فشل الكاميرا';
    connectionStatus.value = 'error';

    // تحديد نوع الخطأ بدقة
    let title = 'فشل الوصول للكاميرا';
    let message = 'حدث خطأ غير متوقع.';

    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
      title = 'تم رفض الإذن';
      message = 'يرجى السماح بالوصول للكاميرا من إعدادات المتصفح أو النظام.';
    } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
      title = 'لا توجد كاميرا';
      message = 'لم يتم العثور على كاميرا متصلة بجهازك.';
    } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
      title = 'الكاميرا مشغولة';
      message = 'الكاميرا مستخدمة من تطبيق آخر. أغلق التطبيقات وحاول مجدداً.';
    } else if (err.name === 'OverconstrainedError') {
      title = 'إعدادات غير مدعومة';
      message = 'الكاميرا لا تدعم الإعدادات المطلوبة.';
    }

    cameraError.value = { title, message };
    toast.error(title);
  }
};

const connectWebSocket = () => {
  const wsUrl = FASTAPI_URL.replace('http', 'ws') + `/api/webrtc/ws/${props.token}?role=candidate`;
  if (ws) ws.close();

  try {
    ws = new WebSocket(wsUrl);
    ws.onopen = () => { console.log('✅ WS connected'); };
    ws.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        if (data.event === 'hr_approved') {
          waitingForHr.value = false;
          hrApproved.value = true;
          status.value = 'جاري الاتصال...';
          startWebRTC();
          return;
        }
        if (data.event === 'hr_left') {
          hrLeftNotification.value = true;
        }
        if (data.event === 'interview_ended') {
          interviewEnded.value = true;
          if (peerConnection) peerConnection.close();
          if (localStream) localStream.getTracks().forEach(t => t.stop());
        }
      } catch (e) {}
    };
    ws.onclose = () => {
      if (reconnectTimer) clearTimeout(reconnectTimer);
      reconnectTimer = setTimeout(() => {
        if (!interviewEnded.value && !hrApproved.value) connectWebSocket();
      }, 3000);
    };
  } catch (e) {}
};

const startWebRTC = async () => {
  peerConnection = new RTCPeerConnection({
    iceServers: [
      { urls: 'stun:stun.l.google.com:19302' },
      { urls: 'stun:stun1.l.google.com:19302' }
    ],
    bundlePolicy: 'max-bundle',
    rtcpMuxPolicy: 'require',
  });

  const candAudio = localStream.getAudioTracks()[0];
  const candVideo = localStream.getVideoTracks()[0];

  if (candAudio) {
    peerConnection.addTransceiver(candAudio, {
      direction: 'sendonly',
      streams: [localStream],
    });
    console.log('🎤 [Candidate] audio transceiver (sendonly)');
  }

  if (candVideo) {
    peerConnection.addTransceiver(candVideo, {
      direction: 'sendonly',
      streams: [localStream],
    });
    console.log('🎥 [Candidate] video transceiver (sendonly)');
  }

  peerConnection.addTransceiver('audio', { direction: 'recvonly' });
  peerConnection.addTransceiver('video', { direction: 'recvonly' });
  console.log('📥 [Candidate] recv-only transceivers added');

  peerConnection.ontrack = (event) => {
    console.log('🎯 [Candidate] Received:', event.track.kind);
    remoteStream.addTrack(event.track);
    nextTick(() => {
      if (remoteVideo.value) {
        remoteVideo.value.srcObject = remoteStream;
        remoteVideo.value.play().catch(() => {});
      }
    });
    remoteVideoActive.value = true;
  };

  peerConnection.oniceconnectionstatechange = () => {
    if (!peerConnection) return;
    const s = peerConnection.iceConnectionState;
    console.log('🔗 Candidate ICE:', s);
    if (s === 'connected') {
      hrLeftNotification.value = false;
      status.value = 'متصل';
    }
  };

  const offer = await peerConnection.createOffer();
  let modifiedSdp = offer.sdp;
  modifiedSdp = modifiedSdp.replace(
    /a=fmtp:111 (.*)/,
    'a=fmtp:111 minptime=10;useinbandfec=1;usedtx=0;maxaveragebitrate=510000;stereo=1;sprop-stereo=1;maxplaybackrate=48000;ptime=20'
  );
  modifiedSdp = modifiedSdp.replace(/(m=video \d+ [^\r\n]+\r\n)/, '$1b=AS:2500\r\n');
  offer.sdp = modifiedSdp;

  await peerConnection.setLocalDescription(offer);

  try {
    const response = await fetch(`${FASTAPI_URL}/api/webrtc/offer`, {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-API-Key": FASTAPI_API_KEY },
      body: JSON.stringify({
        session_id: props.token,
        role: 'candidate',
        interview_id: props.sessionUuid,
        sdp: peerConnection.localDescription.sdp,
        type: peerConnection.localDescription.type,
        language: props.language
      })
    });

    if (!response.ok) throw new Error(`FastAPI: ${response.status}`);

    const data = await response.json();
    if (data.sdp) {
      await peerConnection.setRemoteDescription(new RTCSessionDescription({
        type: data.type,
        sdp: data.sdp
      }));
      status.value = 'متصل';
      connectionStatus.value = 'connected';
      isCalibrating.value = true;
      setTimeout(() => { isCalibrating.value = false; }, 8000);
    }
  } catch (error) {
    console.error("WebRTC error:", error);
    status.value = 'خطأ';
    connectionStatus.value = 'error';
    toast.error('فشل الاتصال بالخادم. تحقق من الشبكة.');
  }
};

const stopInterview = async () => {
  const ok = await confirm({
    title: 'مغادرة الغرفة؟',
    message: 'هل أنت متأكد من مغادرة الغرفة؟ لن تتمكن من العودة.',
    type: 'warning',
    confirmText: 'مغادرة',
    cancelText: 'البقاء',
  });
  if (!ok) return;

  if (localStream) localStream.getTracks().forEach(track => track.stop());
  if (peerConnection) peerConnection.close();
  if (ws) ws.close();
  window.location.href = '/';
};

const closeSession = () => {
  if (localStream) localStream.getTracks().forEach(track => track.stop());
  if (peerConnection) peerConnection.close();
  if (ws) ws.close();
  window.location.href = '/';
};

onUnmounted(() => {
  if (reconnectTimer) clearTimeout(reconnectTimer);
  if (micAnimation) cancelAnimationFrame(micAnimation);
  if (audioContext) audioContext.close();
  if (localStream) localStream.getTracks().forEach(t => t.stop());
  if (peerConnection) peerConnection.close();
  if (ws) ws.close();
});
</script>
