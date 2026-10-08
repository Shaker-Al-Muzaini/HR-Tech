import ToastContainer from "@/Components/ToastContainer.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { h } from "vue";

export default {
  install(app) {
    const GlobalUI = {
      name: "GlobalUI",
      setup() {
        return () => h("div", [h(ToastContainer), h(ConfirmModal)]);
      },
    };
    app.component("GlobalUI", GlobalUI);
  },
};
