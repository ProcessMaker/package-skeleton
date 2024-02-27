/* eslint-disable import/no-extraneous-dependencies */
import Vue from 'vue';
import BootstrapVue from 'bootstrap-vue';
import VModal from 'vue-js-modal';
import axios from 'axios';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';
import '@fortawesome/fontawesome-free/css/all.min.css';

Vue.use(VModal);
Vue.use(BootstrapVue);

window.ProcessMaker = {
  apiClient: axios.create({
    baseURL: '/api/1.0/',
  }),
};
