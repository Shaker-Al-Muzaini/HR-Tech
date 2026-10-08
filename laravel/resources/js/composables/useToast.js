import { reactive } from "vue";
import { useSound } from "./useSound";

const state = reactive({ toasts: [] });
let nextId = 1;
const { play } = useSound();

function addToast(message, type = "info", options = {}) {
  const id = nextId++;
  const duration = options.duration ?? 3500;
  state.toasts.push({ id, message, type, createdAt: Date.now() });
  const soundMap = { success: "success", error: "error", warning: "warning", info: "notification" };
  play(soundMap[type] || "notification");
  if (duration > 0) setTimeout(() => removeToast(id), duration);
  return id;
}

function removeToast(id) {
  const idx = state.toasts.findIndex((t) => t.id === id);
  if (idx !== -1) state.toasts.splice(idx, 1);
}

export function useToast() {
  return {
    toasts: state.toasts,
    success: (msg, opts) => addToast(msg, "success", opts),
    error: (msg, opts) => addToast(msg, "error", opts),
    warning: (msg, opts) => addToast(msg, "warning", opts),
    info: (msg, opts) => addToast(msg, "info", opts),
    remove: removeToast,
  };
}
