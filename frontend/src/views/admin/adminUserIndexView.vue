<script>
import adminnav from "@/components/adminnav.vue";
import axios from "axios";
import config from "@/config.json";
import {notify, removeLoading} from "@/assets/admin.js";

export default {
    name: "adminShowView.vue",
    components: {adminnav},
    data() {
        return {
            user: {},
            showModal: false,
            currentImageIdx: 0,
            isDeleted: false,
            config: config,
        }
    },
    methods: {
        formatDate(dateStr) {
            if (!dateStr) return;

            const [datePart] = dateStr.split(' ');
            const [year, month, day] = datePart.split('-');
            return `${day}.${month}.${year}`;
        },
        formatDateUTC(dateStr) {
            let date = new Date(dateStr);
            return `${date.getDate()}.${date.getMonth() + 1}.${date.getFullYear()}`;
        }
    },
    async mounted() {
        axios.defaults.withCredentials = true;

        await axios.get(config.backend + "admin/users/" + this.$route.params.id).then((response) => {
            this.user = response.data;
            removeLoading();
        }).catch((error) => {
            if (error.response) {
                alert(error.message);
            }
        });
    }
}
</script>

<template>
    <adminnav>
        <!-- Профиль -->
        <section class="profile" aria-label="Информация о пользователе">
            <div class="avatar">
                <a :href="user.avatar" target="_blank"><img :src="user.avatar" alt="Аватар пользователя" loading="lazy" decoding="async"></a>
            </div>

            <div class="profile-info">
                <h2 class="user-name">{{ user.fullname }}</h2>
                <div class="ids">
                    <div class="id-row" title="Telegram">
                        <svg class="id-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M9.033 15.183L8.85 18.36c.322 0 .46-.138.626-.303l1.503-1.44 3.115 2.29c.571.316.976.15 1.132-.53l2.05-9.62c.209-.947-.342-1.316-.97-1.086L4.7 10.02c-.928.362-.915.883-.158 1.116l3.26 1.017 7.56-4.767c.356-.217.68-.097.413.12" />
                        </svg>
                        <span class="id-label">Telegram:</span>
                        <span class="id-value">
          <a class="id-link" target="_blank" rel="noopener noreferrer">{{ user.telegram_id }}</a>
        </span>
                    </div>

                    <div class="id-row" title="Внутренний ID">
                        <svg class="id-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                        </svg>
                        <span class="id-label">ID:</span>
                        <span class="id-value">#{{user.id}}</span>
                    </div>
                </div>
            </div>
<!--            <div class="profile-crypto">-->
<!--                <div v-for="(crypt, key) in user.crypto">-->
<!--                    <img :src="user.currenciesData.find(x => x.coingeckoId === key).logo" alt=""> {{ user.currenciesData.find(x => x.coingeckoId === key).name }}: {{crypt.toFixed(2)}}-->
<!--                </div>-->
<!--            </div>-->
        </section>

        <!-- Курсы -->
        <section aria-label="Список пройденных курсов">
            <h3 class="section-title">Пройденные курсы</h3>

            <div class="courses-grid">
                <template v-for="course in user.courses">
                    <article style="cursor: pointer" @click="$router.push('/admin/lessons/' + lesson.id)" class="course-card" v-for="lesson in course.lessons.filter(item => item.user_points !== null)" tabindex="0" aria-label="JavaScript для начинающих">
                        <div class="course-top">
                            <div class="course-logo"></div>
                            <h4 class="course-title"></h4>
                            <span class="badge-done" :style="{'background': lesson.user_points >= 50 ? 'green' : 'red', 'color': 'white', 'white-space': 'nowrap'}">{{ lesson.user_points }} баллов</span>
                        </div>
                        <div class="course-meta">
                            <span>Название: {{ lesson.title }}</span>
                            <span>ID урока: {{ lesson.id }}</span>
                        </div>
                        <div class="course-meta">
                            <span>Пройден: {{ formatDateUTC(lesson.user_lesson_created_at) }}</span>
                        </div>
                    </article>
                </template>
            </div>
        </section>
    </adminnav>
</template>

<style scoped>
</style>