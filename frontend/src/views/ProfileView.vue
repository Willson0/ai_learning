<script>
import {copy, toLink} from "@/utils.js";
import config from "@/config.json";

    export default {
        data() {
            return {
                averageColor: "",
                config: config,
            }
        },
        computed: {
            avatar () {
                return window.Telegram.WebApp.initDataUnsafe?.user?.photo_url;
            },
            name () {
                return window.Telegram.WebApp.initDataUnsafe?.user?.first_name;
            },
            user () {
                return this.$store.state.user;
            },
            friends () {
                if (!this.user.id) return;
                return this.user.friends.filter(fr => fr.is_accepted === 1);
            },
            unpinnedAchievements () {
                if (!this.user.pinned_achievements) return;
                let countPinned = this.user.achievements?.filter(ach => this.hasAchievement(ach) && this.user.pinned_achievements?.includes(ach.id))?.length ?? 0;

                let achs = this.user.achievements?.filter(ach => this.hasAchievement(ach) && !this.user.pinned_achievements?.includes(ach.id)).slice(0, 5 - countPinned);
                return achs;
            },
        },
        methods: {
            toLink,
            copyReferral () {
                copy('https://t.me/' + config.bot + "?start=" + this.user.id);
            },
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
                return this.user?.data?.[achievement.parameter] >= achievement?.value;
            }
        },
        mounted () {

        }
    }
</script>

<template>
    <div class="profile">
        {{ averageColor }}
        <div class="profile_header">
            <img class="profile_header_avatar" ref="avatar" :src="avatar" alt="">
            <img :src="avatar" id="avatar_background" alt="">
            <div id="avatar_background_black"></div>
            <div class="profile_header_text">
                <div class="profile_header_name">{{ name }}</div>
                <div class="profile_header_points">{{ user.total_points }} баллов</div>
            </div>
        </div>
        <div class="profile_friends" v-if="friends" @click="toLink('rating')">
            <div class="profile_friends_title">{{ friends.length }} {{ getRussianFriends(friends.length) }}</div>
            <div class="profile_friends_avatars">
                <img v-for="friend in friends.slice(0, 3)" :src="friend.avatar.startsWith('http') ? friend.avatar : config.storage + friend.avatar" alt="">
            </div>
        </div>
        <div @click="toLink('achievements')" class="profile_achievements">
            <div class="profile_achievements_header">
                <div>Витрина достижений</div>
                <svg width="10" height="19" viewBox="0 0 10 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1.5L8.29289 8.79289C8.68342 9.18342 8.68342 9.81658 8.29289 10.2071L1 17.5" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="profile_achievements_main">
                <template v-for="ach in user.achievements">
                    <svg v-if="hasAchievement(ach) && user.pinned_achievements?.includes(ach.id)"
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
        <div class="profile_referral">
            <div class="profile_referral_header">
                <div>Реферальная система</div>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1" y="1" width="14" height="14" rx="3" stroke="#7B61FF" stroke-width="2"/>
                    <path d="M8 12.4447V7.11133" stroke="#7B61FF" stroke-width="2"/>
                    <path d="M8 3.55566V5.33344" stroke="#7B61FF" stroke-width="2"/>
                </svg>
            </div>
            <div class="profile_referral_description">
                Приглашайте друзей и получайте бонусы!
            </div>
            <div class="profile_referral_bonus">
                <div class="profile_referral_bonus_background"></div>
                <div class="profile_referral_bonus_text">
                    <div>Ваши бонусы</div>
                    <div class="profile_referral_bonus_text_count">{{ user.bonus }}</div>
                </div>
                <div class="profile_referral_bonus_button_container">
                    <div></div>
                    <button @click="copyReferral">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_447_10369)">
                                <path d="M13 4C13 6.20914 11.2091 8 9 8C6.79086 8 5 6.20914 5 4C5 1.79086 6.79086 0 9 0C11.2091 0 13 1.79086 13 4Z" fill="white"/>
                                <path d="M0 12C0 10.8954 0.895431 10 2 10H7C8.10457 10 9 10.8954 9 12V16C9 17.1046 8.10457 18 7 18H2C0.89543 18 0 17.1046 0 16V12Z" fill="white"/>
                                <path d="M13 17V11C13 10.4477 13.4477 10 14 10C14.5523 10 15 10.4477 15 11V17C15 17.5523 14.5523 18 14 18C13.4477 18 13 17.5523 13 17Z" fill="white"/>
                                <path d="M11 13H17C17.5523 13 18 13.4477 18 14C18 14.5523 17.5523 15 17 15H11C10.4477 15 10 14.5523 10 14C10 13.4477 10.4477 13 11 13Z" fill="white"/>
                            </g>
                            <defs>
                                <clipPath id="clip0_447_10369">
                                    <rect width="18" height="18" fill="white"/>
                                </clipPath>
                            </defs>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="profile_referral_links" v-if="user.referrals?.length > 0">
                <img src="/links_background.png" alt="">
                <div class="profile_referral_links_title">Активные приглашения</div>
                <div class="profile_referral_links_list">
                    <div @click="toLink('user', us.id)" v-for="us in user.referrals">
                        <img :src="us.avatar.startsWith('http') ? us.avatar : config.storage + us.avatar" alt="">
                        <div>{{ us.fullname }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>