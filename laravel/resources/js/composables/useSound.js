import { ref } from "vue";

const muted = ref(localStorage.getItem("hr_sounds_muted") === "true");
let audioCtx = null;

function getCtx() {
  if (audioCtx) return audioCtx;
  try { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
  catch (e) { console.warn("Web Audio API not supported"); return null; }
  return audioCtx;
}

function playTone(frequencies, options = {}) {
  if (muted.value) return;
  const ctx = getCtx();
  if (!ctx) return;
  if (ctx.state === "suspended") ctx.resume().catch(() => {});
  const { type = "sine", duration = 0.15, gap = 0.08, volume = 0.15 } = options;
  const startTime = ctx.currentTime;
  frequencies.forEach((freq, i) => {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = type;
    osc.frequency.value = freq;
    const t0 = startTime + i * gap;
    const t1 = t0 + duration;
    gain.gain.setValueAtTime(0, t0);
    gain.gain.linearRampToValueAtTime(volume, t0 + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.001, t1);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(t0);
    osc.stop(t1 + 0.02);
  });
}

const sounds = {
  success: () => playTone([523.25, 659.25, 783.99], { type: "sine", duration: 0.12, gap: 0.06, volume: 0.12 }),
  error: () => playTone([311.13, 233.08], { type: "sawtooth", duration: 0.18, gap: 0.1, volume: 0.1 }),
  warning: () => playTone([440, 349.23], { type: "triangle", duration: 0.15, gap: 0.1, volume: 0.1 }),
  delete: () => playTone([392, 311.13, 196], { type: "triangle", duration: 0.14, gap: 0.08, volume: 0.12 }),
  notification: () => playTone([880, 1174.66], { type: "sine", duration: 0.1, gap: 0.12, volume: 0.1 }),
  search: () => playTone([1600], { type: "sine", duration: 0.03, gap: 0, volume: 0.05 }),
  navigate: () => playTone([523.25, 783.99], { type: "sine", duration: 0.08, gap: 0.04, volume: 0.08 }),
  open: () => playTone([659.25, 880], { type: "sine", duration: 0.08, gap: 0.04, volume: 0.08 }),
  close: () => playTone([880, 659.25], { type: "sine", duration: 0.08, gap: 0.04, volume: 0.08 }),
};

export function useSound() {
  const play = (name) => { if (sounds[name]) sounds[name](); };
  const toggleMute = () => {
    muted.value = !muted.value;
    localStorage.setItem("hr_sounds_muted", String(muted.value));
    if (!muted.value) play("notification");
  };
  return { play, muted, toggleMute, sounds: Object.keys(sounds) };
}
