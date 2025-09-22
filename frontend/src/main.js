import { createApp } from 'vue'
import './assets/main.css'
import './assets/markdown.css'
import App from './App.vue'
import router from "./router.js";
import store from './storage.js';

createApp(App).use(store).use(router).mount('#app');