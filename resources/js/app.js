import Vue from "vue";
import axios from "axios";
import VueJsonPretty from "vue-json-pretty";

import JawabNotificationComponent from "./components/JawabNotificationComponent";
import JawabNotificationExtraInfo from "./components/JawabNotificationExtraInfo";
import JawabSchedulingComponent from "./components/JawabSchedulingComponent";
import JawabTargetComponent from "./components/JawabTargetComponent";

window.$ = window.jQuery = require("jquery");

require("bootstrap");

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
  axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
}

Vue.component("vue-json-pretty", VueJsonPretty);

import {
  MultiSelectComponent,
  MultiSelectPlugin
} from "@syncfusion/ej2-vue-dropdowns";

import { MultiSelect, CheckBoxSelection } from "@syncfusion/ej2-dropdowns";

MultiSelect.Inject(CheckBoxSelection);
Vue.use(MultiSelectPlugin);

Vue.component(MultiSelectPlugin.name, MultiSelectComponent);

import {
  DateTimePickerComponent,
  DateTimePickerPlugin,
} from "@syncfusion/ej2-vue-calendars";

Vue.use(DateTimePickerPlugin);

Vue.component(DateTimePickerPlugin.name, DateTimePickerComponent);

new Vue({
  el: "#cloud-messaging",
  components: {
    "jawab-notification-editor": JawabNotificationComponent,
    "jawab-notification-extra-info": JawabNotificationExtraInfo,
    "jawab-scheduling-editor": JawabSchedulingComponent,
    "jawab-target-editor": JawabTargetComponent,
  },
});
