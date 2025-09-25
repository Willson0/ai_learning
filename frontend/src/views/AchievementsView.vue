<script>
import {notify, showPopup} from "@/utils.js";
import DoubleSelector from "@/components/DoubleSelectorComponent.vue";
import config from "@/config.json";
import axios from "axios";

export default {
    name: "AchievementsView",
    components: {DoubleSelector},
    methods: {
        showPopup,
        hasAchievement (achievement) {
            return this.user?.data?.[achievement.parameter] >= achievement?.value;
        },
        async pin (id) {
            await axios.post(config.backend + `achievement/${id}/pin`, {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                let newUser = {...this.user, pinned_achievements: response.data};
                this.$store.dispatch("updateUser", newUser);
                notify("Успешно закреплено");
            }).catch((error) => {
                notify(error.response.data.message, 1)
            })
        },
        async unpin (id) {
            await axios.post(config.backend + `achievement/${id}/unpin`, {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                let newUser = {...this.user, pinned_achievements: response.data};
                this.$store.dispatch("updateUser", newUser);
                notify("Успешно откреплено");
            }).catch((error) => {
                notify(error.response.data.message, 1)
            })
        },
    },
    data () {
        return {
            isHas: false,
            config: config,
            selectedAchievement: {},
        }
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
    }
}
</script>

<template>
    <div class="background achievements_background" style="display: none; background-color: transparent"></div>
    <div class="popup achievements_overlay" style="display: none">
        <div class="achievements_overlay_title">{{ selectedAchievement.name }}</div>
        <div class="achievements_overlay_description">{{ selectedAchievement.description }}</div>
        <div class="home_learning_progress">
            <div class="home_learning_progress_bar" :style="{width: ((user?.data?.[selectedAchievement.parameter] ?? 0) / selectedAchievement.value * 100) + '%'}">
                <div>{{ user?.data?.[selectedAchievement.parameter] ?? 0 }}/{{selectedAchievement.value}}</div>
            </div>
        </div>
        <button v-if="hasAchievement(selectedAchievement) && !user.pinned_achievements.includes(selectedAchievement.id)" @click="pin(selectedAchievement.id)">Закрепить на витрине</button>
        <button v-else-if="hasAchievement(selectedAchievement)" @click="unpin(selectedAchievement.id)">Открепить от витрины</button>
    </div>
    <div class="achievements" v-if="user.achievements">
        <div class="achievements_title">Получено достижений</div>
        <div class="home_learning_progress">
            <div class="home_learning_progress_bar" :style="{width: (this.user.achievements.filter(ach => hasAchievement(ach)).length / this.user.achievements.length) * 100 + '%'}">
                <div>{{ this.user.achievements.filter(ach => hasAchievement(ach)).length }}/{{ this.user.achievements.length }}</div>
            </div>
        </div>
        <double-selector first="Все" second="Полученные" @change="isHas = $event"/>
        <div class="achievements_main">
            <div @click="selectedAchievement = ach; showPopup('achievements_overlay', 'achievements_background')" v-for="ach in [...user.achievements.filter(a => hasAchievement(a)), ...user.achievements.filter(a => !isHas && !hasAchievement(a))]">
                <svg width="108" height="108" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <clipPath id="myCustomClip">
                            <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z" />
                        </clipPath>
                    </defs>
                    <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z"
                        fill="var(--addiction)" id="myPlaceholder" style=""/>
                    <image v-if="ach.image" x="0" y="0" width="48" height="48"
                        :href="hasAchievement(ach) ? config.storage + ach.image : ''"
                        clip-path="url(#myCustomClip)" preserveAspectRatio="xMidYMid slice"/>
                    <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z"
                          id="myPlaceholder" style="" stroke="var(--main)" stroke-width="1"/>
                </svg>
                <div>{{ ach.name }}</div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>