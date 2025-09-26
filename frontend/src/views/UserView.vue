<script>
import {copy, deepParse, endLoading, getDate, notify, toLink} from "@/utils.js";
import config from "@/config.json";
import axios from "axios";
import SearchComponent from "@/components/SearchComponent.vue";
import DoubleSelector from "@/components/DoubleSelectorComponent.vue";

export default {
    name: "UserView",
    components: {DoubleSelector, SearchComponent},
    data() {
        return {
            averageColor: "",
            config: config,
            us: null,
            activeMenu: false,
            isLoading: false,

            isFriends: false,
            isCommon: false,
            search: "",
            selectedUser: null,
            topColors: {
                1: "#FFB800",
                2: "#96C7FF",
                3: "#E4865E",
            }
        }
    },
    async mounted () {
        await axios.post(config.backend + "user/" + this.$route.query.id, {
            initData: window.Telegram.WebApp.initData,
        }).then((response) => {
            this.us = deepParse(response.data);
            endLoading("user_loading");
        }).catch((error) => {
            notify("Не удалось загрузить данные пользователя", 1);
        });
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
        friends () {
            if (!this.us.id) return;
            return this.us.friends.filter(fr => fr.is_accepted === 1);
        },
        unpinnedAchievements () {
            if (!this.us.pinned_achievements) return;
            let countPinned = this.user.achievements?.filter(ach => this.hasAchievement(ach) && this.us.pinned_achievements?.includes(ach.id))?.length ?? 0;

            let achs = this.user.achievements?.filter(ach => this.hasAchievement(ach) && !this.us.pinned_achievements?.includes(ach.id)).slice(0, 5 - countPinned);
            return achs;
        },
        countLesson () {
            if (!this.us.id) return;
            let counter = 0;
            for (let course of this.us.courses) {
                for (let lesson of course.lessons) {
                    if (lesson.count_tries > 0 && lesson.user_points >= 50) counter++;
                    else if (lesson.count_tries === 0 && lesson.user_points != null) counter++;
                }
            }
            return counter;
        },
        percentCourse () {
            if (!this.us.id) return 0;

            const totalLessons = this.us.courses.reduce((sum, course) => sum + course.lessons.length, 0);
            if (totalLessons === 0 || totalLessons == null) return 100;
            return this.countLesson / totalLessons * 100;
        },
        commonFriends () {
            if (!this.us.id || !this.user.id) return;

            let userFriends = this.user.friends.filter(fr => fr.is_accepted === 1).map(fr => {
                if (fr.sender_id === this.user.id) return fr.receiver_id;
                else return fr.sender_id;
            });

            let usFriends = this.us.friends.filter(fr => fr.is_accepted === 1).map(fr => {
                if (fr.sender_id === this.us.id) return fr.receiver_id;
                else return fr.sender_id;
            });

            return userFriends.filter(fr => usFriends.includes(fr));
        }
    },
    methods: {
        getDate,
        toLink,
        getRussianFriends (count) {
            if (count === 1) {
                return "друг";
            } else if (count > 1 && count < 5) {
                return "друга";
            } else {
                return "друзей";
            }
        },
        hasAchievement (achievement) {
            return this.us?.data?.[achievement.parameter] >= achievement?.value;
        },
        showMenu (ev, ref) {
            let el = this.$refs[ref];
            let target = ev.target.closest('svg');

            el.style.top = target.getBoundingClientRect().bottom + 'px';
            el.style.right = (window.innerWidth - (target.getBoundingClientRect().right + 10)) + 'px';
            el.style.display = "";

            document.addEventListener('click', (e) => {
                if (!el.contains(e.target) && !target.contains(e.target)) {
                    el.style.display = "none";
                }
            });
        },
        async share () {
            copy('https://t.me/' + config.bot + "?startapp=" + "user_" + this.us.id);
            this.$refs.addiction_menu.style.display = "none";
        },
        async friend () {
            if (this.isLoading) return;

            let newUser = {...this.user};
            newUser.friends.push({
                "sender_id": this.user.id,
                "receiver_id": this.us.id,
                "is_accepted": 0,
            });
            this.$store.commit('setUser', newUser);
            this.$refs.friend_menu.style.display = "none";

            this.isLoading = true;
            await axios.post(config.backend + "friend/" + this.us.id + "/friend", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                let newUser = {...this.user};
                newUser.friends = newUser.friends.filter(a => a.id !== undefined);
                newUser.friends.push(response.data);
                this.$store.commit('setUser', newUser);

                notify("Запрос отправлен", 0);
            }).finally(() => {
                this.isLoading = false;
            })
        },
        unfriend () {
            if (this.isLoading) return;

            let newUser = {...this.user};
            newUser.friends = newUser.friends.filter(a => {
               !(a.sender_id === this.us.id && a.receiver_id === this.user.id) &&
               !(a.sender_id === this.user.id && a.receiver_id === this.us.id)
            });
            this.$store.commit('setUser', newUser);

            this.isLoading = true;
            axios.post(config.backend + "friend/" + this.us.id + "/delete", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                notify("Друг успешно удален", 0);
            }).finally(() => {
                this.isLoading = false;
            });
        },
        getRussianPoints (count) {
            if (count === 1) {
                return 'балл';
            } else if (count >= 2 && count <= 4) {
                return 'балла';
            } else {
                return 'баллов';
            }
        },
    },
}
</script>

<template>
    <div v-if="us && user" style="display: none" ref="friend_menu" class="chat_menu">
        <button @click="unfriend" v-if="user.friends?.find(req =>
            ((req.sender_id === user.id && req.receiver_id === us.id) ||
            (req.sender_id === us.id && req.receiver_id === user.id)) &&
            req.is_accepted === 1
        )">Удалить из друзей</button>
        <button @click="unfriend" v-else-if="user.friends?.find(req =>
            ((req.sender_id === user.id && req.receiver_id === us.id) ||
            (req.sender_id === us.id && req.receiver_id === user.id)) &&
            req.is_accepted === 0
        )">Удалить заявку</button>
        <button @click="friend" v-else>Добавить в друзья</button>
    </div>
    <div style="display: none" ref="addiction_menu" class="chat_menu">
        <button @click="share">Поделиться</button>
    </div>
    <div class="user_loading loading"></div>
    <div class="profile" v-if="us != null && isFriends === false">
        {{ averageColor }}
        <div class="profile_header">
            <img class="profile_header_avatar" ref="avatar" :src="us.avatar.startsWith('http') ? us.avatar : config.storage + us.avatar" alt="">
            <img id="avatar_background" :src="us.avatar.startsWith('http') ? us.avatar : config.storage + us.avatar" alt="">
            <div id="avatar_background_black"></div>
            <div class="profile_header_text">
                <div class="profile_header_name">{{ us.fullname }}</div>
                <div class="profile_header_points">{{ us.total_points }} баллов</div>
            </div>
            <div class="user_buttons">
                <svg @click="showMenu($event, 'friend_menu')"  width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21.3333 9.33333C21.3333 12.2789 18.9455 14.6667 16 14.6667C13.0545 14.6667 10.6667 12.2789 10.6667 9.33333C10.6667 6.38781 13.0545 4 16 4C18.9455 4 21.3333 6.38781 21.3333 9.33333Z" fill="white"/>
                    <path d="M4 20C4 18.5272 5.19391 17.3333 6.66667 17.3333H13.3333C14.8061 17.3333 16 18.5272 16 20V25.3333C16 26.8061 14.8061 28 13.3333 28H6.66667C5.19391 28 4 26.8061 4 25.3333V20Z" fill="white"/>
                    <path d="M21.3333 26.6667V18.6667C21.3333 17.9303 21.9303 17.3333 22.6667 17.3333C23.403 17.3333 24 17.9303 24 18.6667V26.6667C24 27.403 23.403 28 22.6667 28C21.9303 28 21.3333 27.403 21.3333 26.6667Z" fill="white"/>
                    <path d="M18.6667 21.3333H26.6667C27.403 21.3333 28 21.9303 28 22.6667C28 23.403 27.403 24 26.6667 24H18.6667C17.9303 24 17.3333 23.403 17.3333 22.6667C17.3333 21.9303 17.9303 21.3333 18.6667 21.3333Z" fill="white"/>
                </svg>
                <svg @click="showMenu($event, 'addiction_menu')" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="10" cy="16" r="2" fill="white"/>
                    <circle cx="16" cy="16" r="2" fill="white"/>
                    <circle cx="22" cy="16" r="2" fill="white"/>
                </svg>
            </div>
        </div>
        <div class="profile_friends" v-if="friends" @click="isFriends = true">
            <div class="profile_friends_info">
                <div class="profile_friends_title">{{ friends.length }} {{ getRussianFriends(friends.length) }}</div>
                <div class="profile_friends_count">{{ commonFriends?.length }} общих</div>
            </div>
            <div class="profile_friends_avatars">
                <img v-for="friend in friends.slice(0, 3)" :src="friend.avatar.startsWith('http') ? friend.avatar : config.storage + friend.avatar" alt="">
            </div>
        </div>
        <div style="cursor: auto" class="profile_achievements">
            <div class="profile_achievements_header">
                <div>Витрина достижений</div>
            </div>
            <div class="profile_achievements_main">
                <template v-for="ach in user.achievements">
                    <svg v-if="hasAchievement(ach) && us.pinned_achievements?.includes(ach.id)"
                         width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <clipPath id="myCustomClip">
                                <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z" />
                            </clipPath>
                        </defs>
                        <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z"
                              fill="var(--addiction)" id="myPlaceholder" style=""/>
                        <image v-if="ach.image" x="0" y="0" width="48" height="48"
                               :href="config.storage + ach.image"
                               clip-path="url(#myCustomClip)" preserveAspectRatio="xMidYMid slice"/>
                    </svg>
                </template>
                <svg v-for="ach in unpinnedAchievements" width="72" height="72" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <clipPath id="myCustomClip">
                            <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z" />
                        </clipPath>
                    </defs>
                    <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z"
                          fill="var(--addiction)" id="myPlaceholder" style=""/>
                    <image x="0" y="0" width="48" height="48" :href="config.storage + ach.image"
                           clip-path="url(#myCustomClip)" preserveAspectRatio="xMidYMid slice"/>
                </svg>
                <svg v-for="ach in Math.max(0, 5 - (user.achievements?.filter(ach => hasAchievement(ach)).length ?? 0))" width="72" height="72" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <clipPath id="myCustomClip">
                            <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z" />
                        </clipPath>
                    </defs>
                    <path d="M10.7711 5.47958C18.0772 -1.82653 29.9228 -1.82653 37.2289 5.47958L42.5204 10.7711C49.8265 18.0772 49.8265 29.9228 42.5204 37.2289L37.2289 42.5204C29.9228 49.8265 18.0772 49.8265 10.7711 42.5204L5.47958 37.2289C-1.82653 29.9228 -1.82653 18.0772 5.47958 10.7711L10.7711 5.47958Z"
                          fill="var(--addiction)" id="myPlaceholder" style=""/>
                    <image x="0" y="0" width="48" height="48" :href="''"
                           clip-path="url(#myCustomClip)" preserveAspectRatio="xMidYMid slice"/>
                </svg>
            </div>
        </div>
        <div class="home_learning">
            <div class="home_learning_title">Учеба</div>
            <div class="home_learning_description">Прогресс по всем предметам</div>
            <div class="home_learning_progress">
                <div :style="{width: percentCourse + '%'}" class="home_learning_progress_bar">
                    <div>{{ percentCourse }}%</div>
                </div>
            </div>
        </div>
        <div class="user_schedule" v-if="us?.schedule?.length > 0">
            <div class="user_schedule_title">Запланировано</div>
            <div class="user_schedule_footer">
                <div style="font-weight: 500;">
                    {{ user.probes?.find(a => a.id === us.schedule?.sort((a,b) => new Date(b.date) - new Date(a.date))[0]?.probe_id).title }}
                </div>
                <div style="white-space: nowrap; margin-top: auto">
                    {{getDate(us.schedule?.sort((a,b) => new Date(b.date) - new Date(a.date))[0].date?.replace(' ', "T") + "Z")}}
                </div>
            </div>
        </div>
    </div>
    <div class="rating" v-else-if="us != null">
        <double-selector first="Все" second="Общие" @change="isCommon = $event"/>
        <div class="rating_friend_title">
            Друзья <span>{{ isCommon ? commonFriends?.length : user.friends?.length }}</span>
        </div>
        <search-component @inp="search = $event"/>
        <div class="home_rating_list rating_list">
            <div @click="toLink('user', fr.id)" v-for="(fr, key) in isCommon ? user.friends?.filter(fr => commonFriends.includes(fr.sender_id) || commonFriends.includes(fr.receiver_id))?.filter(fr => fr.fullname.trim().toLowerCase().includes((search || '')?.trim()?.toLowerCase()))?.sort((a, b) => Number(b.total_points) - Number(a.total_points)) : user.friends?.filter(fr => fr.is_accepted === 1 && fr.fullname.trim().toLowerCase().includes((search || '')?.trim()?.toLowerCase()))?.sort((a, b) => Number(b.total_points) - Number(a.total_points))">
                <div class="home_rating_list_number" :style="{background: key < 3 ? topColors[key + 1] : ''}"><div>{{ key+1 }}</div></div>
                <img :src="fr.avatar" alt="">
                <div class="rating_list_user_info">
                    <div class="home_rating_list_name">{{ fr.fullname }}</div>
                    <div class="home_rating_list_points">{{ fr.total_points }} {{ getRussianPoints(Number(fr.total_points)) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>