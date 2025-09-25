<script>
import NavComponent from "@/components/NavComponent.vue";
import axios from 'axios';
import config from "@/config.json"
import {closeAllOverlays, closeOverlay, deepParse, endLoading, getPrevWithClass, notify, toLink} from "@/utils.js";
import router from "@/router.js";
import HomeView from "@/views/HomeView.vue";
import ProfileView from "@/views/ProfileView.vue";
import AchievementsView from "@/views/AchievementsView.vue";
import RatingView from "@/views/RatingView.vue";
import SubscriptionView from "@/views/SubscriptionView.vue";
import NotificationView from "@/views/NotificationView.vue";
import AiView from "@/views/AiView.vue";
import ChatView from "@/views/ChatView.vue";
import KnowledgeView from "@/views/KnowledgeView.vue";
import CatalogView from "@/views/CatalogView.vue";
import ArticleView from "@/views/ArticleView.vue";
import HelpView from "@/views/HelpView.vue";
import ThemesView from "@/views/ThemesView.vue";
import CourseView from "@/views/CourseView.vue";
import LessonView from "@/views/LessonView.vue";
import ProbeListView from "@/views/ProbeListView.vue";
import ProbeView from "@/views/ProbeView.vue";
import SupportView from "@/views/SupportView.vue";
import UserView from "@/views/UserView.vue";

export default {
    name: "MainView",
    data () {
        return {
            queryHistory: [],
            isGoingBack: false,
            firstLoading: true,
            touch: false,
            notWhiteList: false,

            dragStartY: 0,
            dragging: false,
            draggingOverlay: null,

            theme: "",
        }
    },
    components: {
        UserView,
        SupportView,
        ProbeView,
        ProbeListView,
        LessonView,
        CourseView,
        ThemesView,
        HelpView,
        ArticleView,
        CatalogView,
        KnowledgeView,
        ChatView,
        AiView,
        NotificationView,
        SubscriptionView,
        RatingView,
        AchievementsView,
        ProfileView,
        HomeView,
        NavComponent

    },
    async mounted () {
        this.theme = window.Telegram.WebApp.colorScheme;
        document.documentElement.classList.add(this.theme);

        document.addEventListener('gesturestart', function (e) {
            e.preventDefault();
        });
        document.addEventListener('gesturechange', function (e) {
            e.preventDefault();
        });
        document.addEventListener('gestureend', function (e) {
            e.preventDefault();
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            let now = new Date().getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);


        this.setHeaderColor();

        document.addEventListener('touchstart', function(event) {
            const activeElement = document.activeElement;
            if ((activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')
                && !activeElement.contains(event.target)
                && event.target !== activeElement) {
                if (event.target.tagName !== 'INPUT' && event.target.tagName !== 'TEXTAREA') {
                    activeElement.blur();
                }
            }
        }, { passive: true });

        window.Telegram.WebApp.expand();
        window.Telegram.WebApp.disableVerticalSwipes();
        if (window.Telegram.WebApp.initDataUnsafe.start_param) {
            let origParams = decodeURIComponent(window.Telegram.WebApp.initDataUnsafe.start_param);
            const params = origParams.split("_");

            const sessionKey = 'tg_start_param';
            if (!sessionStorage.getItem(sessionKey)) {
                if (/^[0-9]+$/.test(params[1]) && Number(params[1]) >= 0)  {
                    if (params[0] === "user") toLink("user", params[1])
                }
                else this.$router.push({ query: { s: 'home' }});

                sessionStorage.setItem(sessionKey, "1")
            }
        }
        else if (!this.$route.query.s) this.$router.push({ query: { s: 'home' }});

        this.fetchData();

        window.Telegram.WebApp.BackButton.onClick(this.backByQuery);
        window.backByQueryFunction = this.backByQuery;

        window.addEventListener("touchstart", () => this.touch = true);
        // window.addEventListener("touchend", () => this.touch = false);

        this.hideFooter();
        this.handleDrag();
    },
    watch: {
        $route(to, from) {
            clearInterval(this.$store.state.interval);
            document.body.style.overflow = "";
        },
        '$route.query' (to, from) {
            this.setHeaderColor();
            document.body.style.overflow = "";

            const footer = document.querySelector('.nav');
            if (footer) {
                footer.style.display = '';
                footer.style.opacity = "1";
            }

            this.$nextTick(() => {
                this.hideFooter();
                this.handleDrag();
            })

            document.body.style.overflow = "";
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (this.isGoingBack === true) {
                this.isGoingBack = false;
                return;
            }
            if (from.s === undefined) return;

            if (to.needback === "1" || to.needback == undefined || to.needback == null) {
                this.queryHistory.push(from);
            }

            window.Telegram.WebApp.BackButton.show();
        }
    },
    methods: {
        async fetchData () {
            axios.post(config.backend + "auth/profile", {
                "initData": window.Telegram.WebApp.initData,
            }).then((response) => {
                if (this.firstLoading) {
                    this.firstLoading = false;
                    endLoading();
                }

                let user = response.data;
                user.courses.forEach(course => {
                    const lessons = course.lessons;
                    const total = lessons.length;
                    const completed = lessons.filter(lesson =>
                        lesson.user_points !== null && lesson.user_points >= -1
                    ).length;

                    course.progress = total > 0 ? Math.round((completed / total) * 100) : 0;
                });

                user = deepParse(JSON.stringify(user));
                this.$store.dispatch("updateUser", user);
            }).catch((error) => {
                if (error.response.status === 423) {
                    notify ("Доступ запрещен. Вы не находитесь в белом списке", 1);
                    return this.notWhiteList = true;
                } else {
                    document.querySelector(".unreg").style.display = "flex";
                    endLoading();
                }
            }).finally(() => {
            });
        },
        backByQuery() {
            if (this.queryHistory.length > 0) {
                this.isGoingBack = true;

                const prevQuery = this.queryHistory.pop();
                this.$router.push({ query: prevQuery });

                if (this.queryHistory.length === 0) window.Telegram.WebApp.BackButton.hide();
            } else {
                this.$router.push({ query: {s: 'profile'} });
            }
        },
        hideFooter () {
            let footer = document.querySelector(".nav");
            if (footer) {
                document.querySelectorAll("input, textarea").forEach((el) => {
                    el.addEventListener("focus", () => {
                        if (this.touch) {
                            footer.style.opacity = "0";

                            let dialog = document.querySelector(".dialog")
                            if (dialog) dialog.style.height = "calc(100vh - 10px)";
                            document.querySelector(".nav").style.paddingBottom = "0px"
                        }
                    });
                    el.addEventListener("blur", () => {
                        footer.style.opacity = "1";

                        let dialog = document.querySelector(".dialog")
                        if (dialog) dialog.style.height = "";

                        document.querySelector(".nav").style.paddingBottom = "";
                    });
                })
            }
        },
        setHeaderColor () {
            const root = document.documentElement; // обычно переменные на :root
            const mainColor = getComputedStyle(root).getPropertyValue('--die').trim();
            window.Telegram.WebApp.setHeaderColor(mainColor);
        },
        handleDrag () {
            document.querySelectorAll('.overlay_closeArea').forEach(el => {
                let onmousedown = (ev) => {
                    this.dragStartY = ev.touches ? ev.touches[0].clientY : ev.clientY;
                    this.dragging = true;
                    this.draggingOverlay = el.closest(".overlay");

                    window.addEventListener('mousemove', this.onMoveDrag);
                    window.addEventListener('touchmove', this.onMoveDrag);
                    window.addEventListener('mouseup', this.onEndDrag);
                    window.addEventListener('touchend', this.onEndDrag);

                    document.documentElement.classList.add('user-unselect');
                }
                el.addEventListener('mousedown', onmousedown);
                el.addEventListener('touchstart', onmousedown);
            });
        },
        onMoveDrag(e) {
            if (this.dragging) {
                let el = this.draggingOverlay;
                let transformY = e.touches ? e.touches[0].clientY - this.dragStartY : e.clientY - this.dragStartY;
                if (transformY < 0) return;

                el.style.transition = 'none';
                el.style.transform = `translateY(${transformY}px)`;
            }
        },
        onEndDrag(e) {
            document.documentElement.classList.remove('user-unselect');
            if (!this.dragging) return;

            let el = this.draggingOverlay;
            el.style.transition = '';
            el.style.transform = 'translateY(0)';

            const endY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
            const deltaY = endY - this.dragStartY;

            if (deltaY > 50) closeAllOverlays();

            window.removeEventListener('mousemove', this.onMoveDrag);
            window.removeEventListener('touchmove', this.onMoveDrag);
            window.removeEventListener('mouseup', this.onEndDrag);
            window.removeEventListener('touchend', this.onEndDrag);
            this.dragging = false;
            this.dragStartY = null;
        },
    },
    computed: {
        name () {
            return window.Telegram.WebApp.initDataUnsafe?.user?.first_name;
        }
    }
}
</script>

<template>
    <div class="popup_notification_container"></div>
    <div class="loading">
<!--        <div v-if="!notWhiteList">Добрый день, {{name}}</div>-->
<!--        <div v-else>Вы не состоите<br>в белом списке</div>-->
    </div>
    <chat-view v-if="$route.query.s === 'chat'" />
    <catalog-view v-else-if="$route.query.s === 'catalog'" />
    <article-view v-else-if="$route.query.s === 'article'" />
    <lesson-view v-else-if="$route.query.s === 'lesson'" />
    <probe-list-view v-else-if="$route.query.s === 'probe-list'" />
    <probe-view v-else-if="$route.query.s === 'probe'" />
    <support-view v-else-if="$route.query.s === 'support'" />
    <nav-component v-else>
        <home-view v-if="$route.query.s === 'home'" />
        <profile-view v-else-if="$route.query.s === 'profile'" />
        <achievements-view v-else-if="$route.query.s === 'achievements'" />
        <rating-view v-else-if="$route.query.s === 'rating'" />
        <subscription-view v-else-if="$route.query.s === 'subscription'" />
        <notification-view v-else-if="$route.query.s === 'notification'" />
        <ai-view v-else-if="$route.query.s === 'ai'" />
        <knowledge-view v-else-if="$route.query.s === 'knowledge'" />
        <help-view v-else-if="$route.query.s === 'help'" />
        <themes-view v-else-if="$route.query.s === 'themes'" />
        <course-view v-else-if="$route.query.s === 'course'" />
        <user-view v-else-if="$route.query.s === 'user'" />
    </nav-component>
</template>

<style scoped>

</style>