import { reactive } from "vue";

const state = reactive({
  show: false,
  title: "",
  message: "",
  type: "warning",
  confirmText: "تأكيد",
  cancelText: "إلغاء",
  resolve: null,
});

export function useConfirm() {
  const confirm = (opts = {}) => {
    return new Promise((resolve) => {
      state.title = opts.title ?? "تأكيد العملية";
      state.message = opts.message ?? "هل أنت متأكد؟";
      state.type = opts.type ?? "warning";
      state.confirmText = opts.confirmText ?? "تأكيد";
      state.cancelText = opts.cancelText ?? "إلغاء";
      state.resolve = resolve;
      state.show = true;
    });
  };
  const _accept = () => {
    if (state.resolve) state.resolve(true);
    state.show = false;
    state.resolve = null;
  };
  const _cancel = () => {
    if (state.resolve) state.resolve(false);
    state.show = false;
    state.resolve = null;
  };
  return { confirm, state, _accept, _cancel };
}
