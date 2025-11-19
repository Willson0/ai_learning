import {createRouter, createWebHistory} from 'vue-router';
import MainView from "@/views/MainView.vue";
import adminAchievementsView from "@/views/admin/adminAchievementsView.vue";
import adminSupportView from "@/views/admin/adminSupportView.vue";
import adminCoursesView from "@/views/admin/adminCoursesView.vue";
import AdminUserIndexView from "@/views/admin/adminUserIndexView.vue";
import AdminUsersView from "@/views/admin/adminUsersView.vue";
import AdminLoginView from "@/views/admin/adminLoginView.vue";
import AdminView from "@/views/admin/adminView.vue";
import adminStatesView from "@/views/admin/adminStatesView.vue";
import adminAdView from "@/views/admin/adminAdView.vue";
import adminProbesView from "@/views/admin/adminProbesView.vue";
import adminSubjectsView from "@/views/admin/adminSubjectsView.vue";
import adminLoggingView from "@/views/admin/adminLoggingView.vue";


const routes = [
    {
        path: "/",
        component: MainView,
    },
    {
        path: "/admin/login",
        component: AdminLoginView,
        meta: { title: 'CryptoCourses | Admin\'s Authorization' },
        name: 'adminlogin'
    },
    {
        path: "/admin",
        component: AdminView,
        meta: { title: 'CryptoCourses | Admin', h: 'Дашборд' },
        name: 'admin'
    },
    {
        path: "/admin/users",
        component: AdminUsersView,
        meta: { title: 'CryptoCourses | Users', h: 'Пользователи' },
        name: 'users'
    },
    {
        path: "/admin/users/:id",
        component: AdminUserIndexView,
        meta: { title: 'CryptoCourses | User', h: 'Пользователь' },
        name: 'user'
    },
    {
        path: "/admin/courses",
        component: adminCoursesView,
        meta: { title: 'CryptoCourses | Courses', h: 'Курсы' },
        name: 'courses'
    },
    {
        path: "/admin/achievements",
        component: adminAchievementsView,
        meta: { title: 'CryptoCourses | Achievements', h: 'Достижения' },
        name: 'achievements'
    },
    {
        path: "/admin/support",
        component: adminSupportView,
        meta: { title: 'CryptoCourses | Support', h: 'Поддержка' },
        name: 'support'
    },
    {
        path: "/admin/probes",
        component: adminProbesView,
        meta: { title: 'CryptoCourses | Probes', h: 'Пробники' },
        name: 'probes'
    },
    {
        path: "/admin/states",
        component: adminStatesView,
        meta: { title: 'CryptoCourses | States', h: 'Статьи' },
        name: 'states'
    },
    {
        path: "/admin/ads",
        component: adminAdView,
        meta: { title: 'CryptoCourses | Ads', h: 'Рекламы' },
        name: 'ads'
    },
    {
        path: "/admin/subjects",
        component: adminSubjectsView,
        meta: { title: 'CryptoCourses | Subjects', h: 'Предметы' },
        name: 'subjects'
    },
    {
        path: "/admin/logging",
        component: adminLoggingView,
        meta: { title: 'CryptoCourses | Logging', h: 'Логи' },
        name: 'logging'
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router;