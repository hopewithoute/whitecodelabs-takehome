import '../css/app.css';

import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import HistoryView from './views/HistoryView.vue';
import NewEntriesView from './views/NewEntriesView.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/entries/new' },
        { path: '/entries/new', name: 'entries.new', component: NewEntriesView },
        { path: '/entries/history', name: 'entries.history', component: HistoryView },
    ],
});

createApp(App).use(router).mount('#app');
