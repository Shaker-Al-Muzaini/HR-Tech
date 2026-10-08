<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 font-sans" dir="rtl">
    
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-xl border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl blur-lg opacity-40"></div>
            <div class="relative w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
            </div>
          </div>
          <div>
            <h1 class="text-lg font-bold text-gray-900">غرفة المقابلة</h1>
            <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
              <span v-if="candidateName">👤 {{ candidateName }}</span>
              <span v-if="jobTitle" class="text-gray-300">•</span>
              <span v-if="jobTitle">💼 {{ jobTitle }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button @click="goToDashboard" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition-all active:scale-95">🏠 لوحة التحكم</button>
          <a :href="`/hr/interview/${interviewId}/report`" class="px-4 py-2 bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl hover:bg-emerald-200 transition-all active:scale-95">📊 التقرير</a>
          <button @click="endInterview" class="px-4 py-2 bg-red-600 text-white font-bold text-xs rounded-xl hover:bg-red-700 shadow-lg shadow-red-500/20 transition-all active:scale-95">إنهاء</button>
        </div>
      </div>
    </header>

    <div v-if="!candidateToken" class="m-6 p-6 bg-gradient-to-l from-amber-50 to-orange-50 border-2 border-amber-200 rounded-2xl">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
        </div>
        <div>
          <h3 class="font-bold text-amber-900">لا توجد جلسة نشطة</h3>
          <p class="text-sm text-amber-700">تأكد من فتح الرابط الصحيح</p>
        </div>
      </div>
    </div>

    <div v-else-if="candidateWaiting && !videoActive && !approved" class="flex-1 flex items-center justify-center p-6">
      <div class="max-w-2xl w-full text-center">
        <div class="relative w-32 h-32 mx-auto mb-8">
          <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full opacity-20 animate-ping"></div>
          <div class="absolute inset-2 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full opacity-40 animate-pulse"></div>
          <div class="relative w-full h-full bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full flex items-center justify-center shadow-2xl">
            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-3">المرشح في غرفة الانتظار</h1>
        <p class="text-lg text-gray-500 mb-8">{{ candidateName || 'المرشح' }} جاهز للبدء</p>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 text-right max-w-md mx-auto">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-2xl shadow-lg">
              {{ initials(candidateName) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-bold text-gray-900 text-lg truncate">{{ candidateName || 'غير معروف' }}</p>
              <p class="text-sm text-gray-500 truncate">{{ jobTitle || 'الوظيفة غير محددة' }}</p>
            </div>
          </div>
        </div>

        <button @click="approveCandidate" :disabled="approving"
                class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-lg rounded-2xl shadow-2xl hover:scale-105 active:scale-95 transition-all disabled:opacity-50">
          {{ approving ? 'جاري الموافقة...' : 'السماح بالدخول' }}
        </button>
      </div>
    </div>

    <main v-else-if="candidateToken" class="max-w-7xl w-full mx-auto px-6 py-6 grid grid-cols-12 gap-5">
      <div class="col-span-8 flex flex-col space-y-5">
        
        <div class="bg-black rounded-2xl aspect-video relative overflow-hidden shadow-2xl">
          <video v-if="videoActive" ref="candidateVideo"
                 class="absolute inset-0 w-full h-full object-cover"
                 autoplay playsinline></video>

          <div v-if="!videoActive" class="absolute inset-0 flex flex-col items-center justify-center text-white bg-gradient-to-br from-gray-900 to-black">
            <div class="w-20 h-20 mb-4 rounded-full bg-white/5 flex items-center justify-center">
              <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
            </div>
            <p class="text-gray-400">{{ videoStatusMessage }}</p>
          </div>

          <div v-if="videoActive" class="absolute top-4 right-4 flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-2xl">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
            </span>
            بث مباشر
          </div>

          <div class="absolute bottom-4 left-4 w-48 h-32 bg-black rounded-xl overflow-hidden shadow-2xl border-2 border-emerald-500 z-20">
            <video ref="hrSelfVideo" class="w-full h-full object-cover transform -scale-x-100" autoplay playsinline muted></video>
            <div class="absolute bottom-1.5 right-1.5 bg-emerald-600 text-white text-[10px] px-2 py-0.5 rounded font-bold shadow">
              أنت
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-white p-5 rounded-2xl shadow-lg border border-gray-100/80">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                </svg>
              </div>
              <h3 class="font-bold text-gray-800 text-sm">مؤشرات الصوت</h3>
            </div>
            <div v-if="latestMetrics" class="space-y-2.5">
              <div class="flex justify-between"><span class="text-xs text-gray-500">Pitch</span><span class="font-bold tabular-nums text-sm">{{ round(latestMetrics.prosody?.pitch_mean_hz) }} Hz</span></div>
              <div class="flex justify-between"><span class="text-xs text-gray-500">التباين</span><span class="font-bold tabular-nums text-sm">{{ round(latestMetrics.prosody?.pitch_variance) }}</span></div>
              <div class="flex justify-between"><span class="text-xs text-gray-500">معدل الكلام</span><span class="font-bold tabular-nums text-sm">{{ round(latestMetrics.prosody?.speech_rate_syllables_per_sec) }} م/ث</span></div>
              <div class="flex justify-between"><span class="text-xs text-gray-500">الصمت</span><span class="font-bold tabular-nums text-sm">{{ round(latestMetrics.prosody?.pause_ratio) }}</span></div>
            </div>
            <p v-else class="text-sm text-gray-400 text-center py-4">في انتظار البيانات...</p>
          </div>

          <div class="bg-white p-5 rounded-2xl shadow-lg border border-gray-100/80">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <h3 class="font-bold text-gray-800 text-sm">حركة الوجه</h3>
            </div>
            <div v-if="latestMetrics" class="space-y-2.5">
              <div class="flex justify-between"><span class="text-xs text-gray-500">AU1</span><span class="font-bold tabular-nums text-sm">{{ round(latestMetrics.face?.au1_inner_brow_raise) }}</span></div>
              <div class="flex justify-between"><span class="text-xs text-gray-500">AU4</span><span class="font-bold tabular-nums text-sm">{{ round(latestMetrics.face?.au4_brow_lowerer) }}</span></div>
              <div class="flex justify-between"><span class="text-xs text-gray-500">AU12</span><span class="font-bold tabular-nums text-sm">{{ round(latestMetrics.face?.au12_lip_corner_puller) }}</span></div>
              <div class="flex justify-between"><span class="text-xs text-gray-500">حركة الرأس</span><span class="font-bold tabular-nums text-sm">{{ round(latestMetrics.face?.head_movement_variance) }}</span></div>
            </div>
            <p v-else class="text-sm text-gray-400 text-center py-4">في انتظار البيانات...</p>
          </div>
        </div>
      </div>

      <div class="col-span-4 flex flex-col space-y-5">
        <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 rounded-2xl p-5 text-white shadow-2xl">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
              <span class="text-xs font-bold">متصل</span>
            </div>
            <span class="text-xs opacity-60 tabular-nums">{{ lastUpdate }}</span>
          </div>
          <div class="space-y-3">
            <div class="flex justify-between"><span class="text-sm opacity-80">الفيديو</span><span class="font-bold text-sm">{{ videoStatus }}</span></div>
            <div class="flex justify-between"><span class="text-sm opacity-80">AI Engine</span><span class="font-bold text-sm">{{ aiStatus }}</span></div>
            <div class="flex justify-between pt-3 border-t border-white/20">
              <span class="text-sm opacity-80">النوافذ</span>
              <span class="font-bold text-xl tabular-nums">{{ windowCount }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-5 border border-gray-100/80">
          <h3 class="font-bold text-gray-800 text-sm mb-4">⚠️ تنبيهات</h3>
          <div v-if="latestMetrics" class="space-y-2">
            <div v-if="latestMetrics.objects?.phone_detected" class="p-3 bg-red-50 border-r-4 border-red-500 text-red-800 rounded-lg text-sm font-bold">🚨 هاتف!</div>
            <div v-if="latestMetrics.objects?.person_count > 1" class="p-3 bg-orange-50 border-r-4 border-orange-500 text-orange-800 rounded-lg text-sm font-bold">👥 أكثر من شخص!</div>
            <div v-if="latestMetrics.quality?.human_review_required" class="p-3 bg-amber-50 border-r-4 border-amber-500 text-amber-800 rounded-lg text-sm">⚠️ جودة منخفضة</div>
            <div v-if="!latestMetrics.objects?.phone_detected && latestMetrics.objects?.person_count <= 1 && !latestMetrics.quality?.human_review_required"
                 class="p-3 bg-emerald-50 border-r-4 border-emerald-500 text-emerald-700 rounded-lg text-sm font-bold">✅ طبيعي</div>
          </div>
          <p v-else class="text-sm text-gray-400 text-center py-4">لا توجد تنبيهات...</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-5 border border-gray-100/80 flex-1">
          <h3 class="font-bold text-gray-800 text-sm mb-4">💡 أسئلة مقترحة</h3>
          <ul class="space-y-2">
            <li v-for="q in suggestedQuestions" :key="q.id"
                class="p-3 bg-gradient-to-l from-indigo-50 to-blue-50 hover:from-indigo-100 text-indigo-900 rounded-xl text-xs cursor-pointer transition-all border border-indigo-100/60">
              {{ q.text }}
            </li>
          </ul>
        </div>
      </div>
    </main>

    <div v-if="candidateLeftNotification" class="fixed bottom-6 left-6 w-96 bg-white rounded-2xl shadow-2xl border-l-4 border-red-500 p-5 z-40">
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
          <span class="text-xl">👋</span>
        </div>
        <div class="flex-1">
          <p class="font-bold text-gray-900">غادر المرشح</p>
          <p class="text-xs text-gray-500 mt-1">تحويل للتقرير خلال {{ redirectCountdown }} ثانية</p>
          <a :href="`/hr/interview/${interviewId}/report`" class="inline-block mt-3 px-4 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg">الانتقال للتقرير</a>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  interviewId: String,
  candidateToken: String,
  candidateName: String,
  jobTitle: String,
  interviewStatus: String,
  fastapiUrl: String,
});

const videoStatus = ref('غير متصل');
const aiStatus = ref('غير متصل');
const videoActive = ref(false);
const videoStatusMessage = ref('في انتظار بث المرشح...');
const latestMetrics = ref(null);
const windowCount = ref(0);
const lastUpdate = ref('--:--');
const suggestedQuestions = ref([
  { id: 1, text: "حدثنا عن كيفية تعاملك مع ضغط العمل؟" },
  { id: 2, text: "ما هي أبرز إنجازاتك في وظيفتك السابقة؟" },
  { id: 3, text: "كيف تتصرف إذا اختلفت مع مديرك في الرأي؟" }
]);

const candidateWaiting = ref(false);
const approving = ref(false);
const approved = ref(false);
const webrtcStarted = ref(false);
const candidateLeftNotification = ref(false);
const redirectCountdown = ref(15);

const candidateVideo = ref(null);
const hrSelfVideo = ref(null);
const hrLocalStream = ref(null);

let ws = null;
let peerConnection = null;
let reconnectTimer = null;
let redirectTimer = null;
let countdownTimer = null;
let pollTimer = null;
let iceDisconnectTimer = null;

const FASTAPI_URL = props.fastapiUrl || import.meta.env.VITE_FASTAPI_URL || 'http://localhost:8001';
const FASTAPI_API_KEY = import.meta.env.VITE_FASTAPI_API_KEY || 'dev-api-key-change-in-production';

const initials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
};

watch(hrLocalStream, async (stream) => {
  if (stream) {
    await nextTick();
    if (hrSelfVideo.value) {
      hrSelfVideo.value.srcObject = stream;
      await hrSelfVideo.value.play().catch(() => {});
    }
  }
});

const startPolling = () => {
  const checkStatus = async () => {
    if (!props.candidateToken || approved.value) return;
    try {
      const r = await fetch(`${FASTAPI_URL}/api/webrtc/status/${props.candidateToken}`);
      if (r.ok) {
        const data = await r.json();
        if (data.candidate_waiting && !videoActive.value && !approved.value) {
          candidateWaiting.value = true;
        }
      }
    } catch (e) {}
  };
  checkStatus();
  pollTimer = setInterval(checkStatus, 2000);
};

const connectWebSocket = () => {
  const sessionId = props.candidateToken;
  if (!sessionId) return;
  const wsUrl = FASTAPI_URL.replace('http', 'ws') + `/api/webrtc/ws/${sessionId}?role=hr`;
  if (ws) ws.close();

  try {
    ws = new WebSocket(wsUrl);
    ws.onopen = () => { aiStatus.value = 'متصل ✅'; };
    ws.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        if (data.event === 'candidate_waiting') {
          if (!approved.value && !videoActive.value) candidateWaiting.value = true;
          return;
        }
        if (data.event === 'candidate_left') {
          videoActive.value = false;
          candidateLeftNotification.value = true;
          candidateWaiting.value = false;
          if (candidateVideo.value) candidateVideo.value.srcObject = null;
          redirectCountdown.value = 15;
          countdownTimer = setInterval(() => {
            redirectCountdown.value--;
            if (redirectCountdown.value <= 0) clearInterval(countdownTimer);
          }, 1000);
          redirectTimer = setTimeout(() => router.visit(`/hr/interview/${props.interviewId}/report`), 15000);
          return;
        }
        if (data.event === 'interview_ended') {
          setTimeout(() => router.visit(`/hr/interview/${props.interviewId}/report`), 2000);
          return;
        }
        latestMetrics.value = data;
        windowCount.value = (data.window_index || 0) + 1;
        lastUpdate.value = new Date().toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
      } catch (e) {}
    };
    ws.onclose = () => {
      aiStatus.value = 'غير متصل ❌';
      if (reconnectTimer) clearTimeout(reconnectTimer);
      reconnectTimer = setTimeout(() => { if (props.candidateToken) connectWebSocket(); }, 3000);
    };
  } catch (e) {}
};

const approveCandidate = async () => {
  if (approving.value || approved.value) return;
  approving.value = true;

  try {
    const r = await fetch(`${FASTAPI_URL}/api/webrtc/approve/${props.candidateToken}`, {
      method: 'POST',
      headers: { 'X-API-Key': FASTAPI_API_KEY },
    });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);

    approved.value = true;
    candidateWaiting.value = false;
    videoStatusMessage.value = 'جاري الاتصال...';
    await connectWebRTC();
    approving.value = false;
  } catch (error) {
    approving.value = false;
    approved.value = false;
    alert('فشل: ' + error.message);
  }
};

const connectWebRTC = async () => {
  if (webrtcStarted.value) return;
  webrtcStarted.value = true;
  if (!props.candidateToken) return;
  videoStatus.value = 'جاري الاتصال...';

  try {
    hrLocalStream.value = await navigator.mediaDevices.getUserMedia({
      video: { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 30 } },
      audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
        sampleRate: 48000,
        channelCount: 1,
      },
    });

    peerConnection = new RTCPeerConnection({
      iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
      ],
      bundlePolicy: 'max-bundle',
      rtcpMuxPolicy: 'require',
      iceCandidatePoolSize: 2,
    });

    const hrAudio = hrLocalStream.value.getAudioTracks()[0];
    const hrVideo = hrLocalStream.value.getVideoTracks()[0];

    // [0] و [1]: HR يرسل
    if (hrAudio) {
      peerConnection.addTransceiver(hrAudio, {
        direction: 'sendonly',
        streams: [hrLocalStream.value],
      });
      console.log('🎤 [HR] audio (sendonly)');
    }
    if (hrVideo) {
      peerConnection.addTransceiver(hrVideo, {
        direction: 'sendonly',
        streams: [hrLocalStream.value],
      });
      console.log('🎥 [HR] video (sendonly)');
    }

    // [2] و [3]: HR يستقبل
    peerConnection.addTransceiver('audio', { direction: 'recvonly' });
    peerConnection.addTransceiver('video', { direction: 'recvonly' });
    console.log('📥 [HR] recv transceivers added');

    peerConnection.ontrack = (event) => {
      console.log('🎯 [HR] Received:', event.track.kind);
      if (event.streams && event.streams[0]) {
        nextTick(() => {
          if (candidateVideo.value) {
            candidateVideo.value.srcObject = event.streams[0];
            candidateVideo.value.play().catch(() => {});
          }
        });
        videoActive.value = true;
        videoStatus.value = 'متصل ✅';
        videoStatusMessage.value = '';
      }
    };

    peerConnection.oniceconnectionstatechange = () => {
      if (!peerConnection) return;
      const s = peerConnection.iceConnectionState;
      console.log('🔗 HR ICE:', s);
      if (s === 'connected' || s === 'completed') {
        if (iceDisconnectTimer) { clearTimeout(iceDisconnectTimer); iceDisconnectTimer = null; }
        videoStatus.value = 'متصل ✅';
      } else if (s === 'disconnected') {
        if (!iceDisconnectTimer) {
          iceDisconnectTimer = setTimeout(() => {
            if (peerConnection && peerConnection.iceConnectionState === 'disconnected') {
              videoStatus.value = 'انقطاع مؤقت ⚠️';
            }
            iceDisconnectTimer = null;
          }, 5000);
        }
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

    const response = await fetch(`${FASTAPI_URL}/api/webrtc/offer`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-API-Key': FASTAPI_API_KEY },
      body: JSON.stringify({
        session_id: props.candidateToken,
        role: 'hr',
        sdp: peerConnection.localDescription.sdp,
        type: peerConnection.localDescription.type,
      }),
    });

    if (!response.ok) throw new Error(`FastAPI: ${response.status}`);

    const data = await response.json();
    await peerConnection.setRemoteDescription(
      new RTCSessionDescription({ type: data.type, sdp: data.sdp })
    );
  } catch (error) {
    console.error('HR WebRTC error:', error);
    videoStatus.value = 'خطأ ❌';
    webrtcStarted.value = false;
  }
};

const round = (val) => {
  if (val === null || val === undefined) return '-';
  return typeof val === 'number' ? val.toFixed(2) : val;
};

const cleanupAll = () => {
  if (reconnectTimer) clearTimeout(reconnectTimer);
  if (redirectTimer) clearTimeout(redirectTimer);
  if (countdownTimer) clearInterval(countdownTimer);
  if (pollTimer) clearInterval(pollTimer);
  if (iceDisconnectTimer) clearTimeout(iceDisconnectTimer);
  if (ws) { try { ws.close(); } catch (e) {} ws = null; }
  if (peerConnection) { try { peerConnection.close(); } catch (e) {} peerConnection = null; }
  if (hrLocalStream.value) { hrLocalStream.value.getTracks().forEach(t => t.stop()); hrLocalStream.value = null; }
};

const goToDashboard = () => { cleanupAll(); router.visit('/'); };

const endInterview = () => {
  if (!confirm('هل أنت متأكد من إنهاء المقابلة؟')) return;
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify({ action: 'end_interview' }));
  }
  cleanupAll();
  setTimeout(() => router.visit(`/hr/interview/${props.interviewId}/report`), 500);
};

onMounted(() => {
  connectWebSocket();
  startPolling();
});

onUnmounted(() => { cleanupAll(); });
</script>