import {createRouter, createWebHistory} from 'vue-router';
import MainView from "@/views/MainView.vue";


const routes = [
    {
        path: "/",
        component: MainView,
    },
    // {
    //     path: "/admin/login",
    //     component: AdminLoginView,
    //     meta: { title: 'CryptoCourses | Admin\'s Authorization' },
    //     name: 'adminlogin'
    // },
    // {
    //     path: "/admin",
    //     component: AdminView,
    //     meta: { title: 'CryptoCourses | Admin', h: 'Дашборд' },
    //     name: 'admin'
    // },
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router;