<script>
    import {toLink} from "@/utils.js";
    import config from "@/config.json";

    export default {
        data () {
            return {
                topColors: {
                    1: "#FFB800",
                    2: "#96C7FF",
                    3: "#E4865E",
                },
                selected: 0,
                startX: 0,
                transformX: 0,
                isMouseDown: false,
                transition: "none",
                selectedToken: 0,
                startTransformX: 0,
                timeToNext: 5,
                timerInterval: null,
                firstTimer: true,
                mouseDownTime: 0,
                config: config,
            }
        },
        async mounted () {
            window.addEventListener("mouseup", this.mouseup);
            window.addEventListener("mousemove", this.mousemove);

            window.addEventListener("touchend", this.mouseup);
            window.addEventListener("touchmove", this.mousemove);

            this.initSlider();
        },
        unmounted () {
            clearInterval(this.timerInterval);
        },
        watch: {
            user () {
                this.initSlider();
            }
        },
        methods: {
            toLink,
            async initSlider () {
                if (!this.user.id) return
                if (this.user.ads.length === 1) return;
                try {
                    requestAnimationFrame(async () => {
                        this.transformX = -document.querySelector(".profile_news").getBoundingClientRect().width;

                        await this.timer();
                        this.timerInterval = setInterval(() => {
                            this.timer();
                        }, this.timeToNext * 1000);
                    })
                } catch (e) {}
            },
            openLink (link) {
                window.Telegram.WebApp.openLink(link);
            },
            getDate (date) {
                if (!date) return "";

                let dat = new Date(date);
                const months = ["янв", "фев", "мар", "апр", "май", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"];

                return `${dat.getDate()} ${months[dat.getMonth()]} ${dat.getHours().toString().padStart(2, '0')}:${dat.getMinutes().toString().padStart(2, '0')}`
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
            mousedown (ev) {
                this.mouseDownTime = Date.now();

                this.startX = (ev.clientX || ev.touches?.[0]?.clientX);
                this.startTransformX = this.transformX;
                this.isMouseDown = true;

                let el = this.$refs.news_bar;
                el.style.transition = "1s";
                el.style.width = "0%";

                document.body.style.cursor = "grabbing";
                clearInterval(this.timerInterval);
            },
            mousemove (ev) {
                const clientX = ev.clientX || ev.touches?.[0]?.clientX;
                if (!clientX) return;

                if (this.isMouseDown) {
                    const deltaX = clientX - this.startX;
                    let newTransformX = this.startTransformX + deltaX * 1.7;

                    const maxX = document.querySelector(".profile_news>div").scrollWidth - document.querySelector(".profile_news").clientWidth;
                    if (newTransformX > 0 || newTransformX < -maxX) return;

                    if (Math.abs(newTransformX) < 10) {
                        newTransformX = -document.querySelector(".profile_news").getBoundingClientRect().width * this.user.ads.length;
                        this.startX = (ev.clientX || ev.touches?.[0]?.clientX);
                        this.startTransformX = this.transformX;
                    } else if (Math.abs(newTransformX) > maxX - 10) {
                        newTransformX = -document.querySelector(".profile_news").getBoundingClientRect().width
                        this.startX = (ev.clientX || ev.touches?.[0]?.clientX);
                        this.startTransformX = this.transformX;
                    }
                    this.transformX = newTransformX;
                }
            },
            mouseup (ev) {
                document.body.style.cursor = "auto";
                if (this.isMouseDown) {
                    this.isMouseDown = false;

                    const elapsedMs = Date.now() - this.mouseDownTime;
                    const elapsedPx = Math.abs(this.transformX - this.startTransformX);
                    let newSelected = "";
                    if (elapsedMs < 200 && elapsedPx < 20) {
                        let newSelected = Math.round(Math.abs(this.transformX) / document.querySelector(".profile_news").clientWidth);
                        for (let i = 0; i < this.user.ads.length; i++) {
                            if (newSelected === i + 1) window.Telegram.WebApp.openLink(this.user.ads[i].link);
                        }
                        return;
                    }

                    this.timer();
                    this.timerInterval = setInterval(() => {
                        this.timer();
                    }, this.timeToNext * 1000);

                    newSelected = Math.round(Math.abs(this.transformX) / document.querySelector(".profile_news").clientWidth);
                    this.selected = newSelected;

                    this.transition = "0.3s";
                    requestAnimationFrame(() => {
                        this.transformX = -document.querySelector(".profile_news").getBoundingClientRect().width * this.selected;
                        setTimeout(() => {
                            this.transition = "none";
                            const maxX = document.querySelector(".profile_news>div").scrollWidth - document.querySelector(".profile_news").clientWidth;
                            if (Math.abs(this.transformX) < 10) {
                                this.transformX = -document.querySelector(".profile_news").getBoundingClientRect().width * this.user.ads.length;
                            } else if (Math.abs(this.transformX) > maxX - 10) {
                                this.transformX = -document.querySelector(".profile_news").getBoundingClientRect().width
                            }
                        }, 300);
                    })
                }
            },
            async timer () {
                if (!this.firstTimer) {
                    this.transition = "0.3s";
                    requestAnimationFrame(() => {
                        this.transformX -= document.querySelector(".profile_news").clientWidth;
                        setTimeout(() => {
                            this.transition = "none";
                            const maxX = document.querySelector(".profile_news>div").scrollWidth - document.querySelector(".profile_news").clientWidth;
                            if (Math.abs(this.transformX) < 10) {
                                this.transformX = -document.querySelector(".profile_news").getBoundingClientRect().width * this.user.ads.length;
                            } else if (Math.abs(this.transformX) > maxX - 10) {
                                this.transformX = -document.querySelector(".profile_news").getBoundingClientRect().width
                            }
                        }, 300);
                    })
                }
                this.firstTimer = false;

                let el = this.$refs.news_bar;
                el.style.transition = "none";
                el.style.width = "0%";
                requestAnimationFrame(() => {
                    el.style.transition = this.timeToNext + "s";
                    el.style.width = "100%";
                });
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
            lastChat () {
                if (!this.user.id) return;
                return this.user.chats.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))[0];
            },
            countLesson () {
                if (!this.user.id) return;
                let counter = 0;
                for (let course of this.user.courses) {
                    for (let lesson of course.lessons) {
                        if (lesson.count_tries > 0 && lesson.user_points >= 50) counter++;
                        else if (lesson.count_tries === 0 && lesson.user_points != null) counter++;
                    }
                }
                return counter;
            },
            percentCourse () {
                if (!this.user.id) return 0;

                const totalLessons = this.user.courses.reduce((sum, course) => sum + course.lessons.length, 0);
                if (totalLessons === 0 || totalLessons == null) return 100;
                return this.countLesson / totalLessons * 100;
            },
        }
    }
</script>

<template>
    <div class="home">
        <div @click="toLink('profile')" class="home_profile">
            <img :src="avatar" alt="">
            <div>Профиль</div>
            <svg @click.stop="toLink('notification')" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.8814 8.50215C11.4866 6.99001 12.9411 6 14.5575 6L17.4425 6C19.0588 6 20.5134 6.99001 21.1186 8.50215L23.3193 14L24.4636 16.0011C25.9884 18.6678 24.0809 22 21.0295 22H10.9705C7.91913 22 6.01158 18.6678 7.53643 16.0011L8.68071 14L10.8814 8.50215Z" fill="#7B61FF"/>
                <path d="M18.9735 23C18.9735 23.7956 18.6602 24.5587 18.1026 25.1213C17.5449 25.6839 16.7886 26 16 26C15.2114 26 14.4551 25.6839 13.8974 25.1213C13.3398 24.5587 13.0265 23.7956 13.0265 23H18.9735Z" fill="#7B61FF"/>
            </svg>
        </div>
<!--        <div class="home_ads">-->
<!--            <div id="simplify">-->
<!--                <img id="simplify_stars" src="/stars.png" alt="">-->
<!--                <div>Проще обучаться<br>с нашим ботом!</div>-->
<!--                <button>Попробовать</button>-->
<!--                <img id="simplify_img" src="/ad1.png" alt="">-->
<!--            </div>-->
<!--        </div>-->
        <div v-if="user.ads?.length > 0" :style="{ cursor: isMouseDown ? 'grabbing' : 'pointer'}"  class="profile_news">
            <div :style="'transform: translateX(' + transformX + 'px); transition: ' + transition" @mousedown="user.ads?.length > 1 ? mousedown($event) : ''" @touchstart="user.ads?.length > 1 ? mousedown($event) : ''">
                <img v-if="user.ads?.length > 1" :src="config.storage + user.ads[user.ads.length-1].picture" alt="">
                <img @click="user.ads?.length === 1 ? openLink(ad.link) : ''" v-for="ad in user.ads" :src="config.storage + ad.picture" alt="">
                <img v-if="user.ads?.length > 1" :src="config.storage + user.ads[0].picture" alt="">
            </div>
            <div class="profile_news_bar"><div ref="news_bar" :style="'transition: ' + timeToNext + 's'"></div></div>
        </div>
        <div @click="toLink('subscription')" class="home_subscription">
            <img id="home_subscription_background" src="/subscription_background.png" alt="">
            <img id="home_subscription_crown" src="/crown.png" alt="">
            <div>
                <div>Моя подписка</div>
                <svg width="10" height="19" viewBox="0 0 10 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1.5L8.29289 8.79289C8.68342 9.18342 8.68342 9.81658 8.29289 10.2071L1 17.5" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
        <div class="home_supportChat">
            <div class="home_supportChat_header" @click="toLink('ai')">
                <div class="home_supportChat_header_title">Чат с помощником</div>
                <svg width="10" height="19" viewBox="0 0 10 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1.5L8.29289 8.79289C8.68342 9.18342 8.68342 9.81658 8.29289 10.2071L1 17.5" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="home_supportChat_main" v-if="lastChat" @click="toLink('chat', lastChat.id)">
                <div class="home_supportChat_main_background"></div>
                <div class="home_supportChat_main_upper">
                    <div class="home_supportChat_main_upper_header">
                        <div>Последний чат</div>
                        <div>{{ getDate(lastChat.created_at) }}</div>
                    </div>
                    <div class="home_supportChat_main_upper_name">{{ lastChat.name }}</div>
                </div>
                <div class="home_supportChat_main_downer">
                    <div></div>
                    <button @click.stop="toLink('ai')">Перейти к чатам</button>
                </div>
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
            <div class="home_supportChat_main home_schedule" v-if="user.schedule?.length > 0">
                <div class="home_supportChat_main_background"></div>
                <div class="home_supportChat_main_upper">
                    <div class="home_supportChat_main_upper_header">
                        <div>Запланировано</div>
                        <div class="home_supportChat_main_upper_name">{{ user.probes?.find(a => a.id === user.schedule?.sort((a,b) => new Date(b.date) - new Date(a.date))[0]?.probe_id).title }}</div>
                    </div>
                    <div>{{ getDate(user.schedule?.sort((a,b) => new Date(b.date) - new Date(a.date))[0].date?.replace(' ', "T") + "Z") }}</div>
                </div>
                <div class="home_supportChat_main_downer">
                    <div></div>
                    <button @click.stop="toLink('themes')">Подготовиться</button>
                </div>
            </div>
        </div>
        <div class="home_rating">
            <img src="/leaders_background.png" alt="">
            <div class="home_rating_header" @click="toLink('rating')">
                <div>Рейтинг среди друзей</div>
                <svg width="10" height="19" viewBox="0 0 10 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1.5L8.29289 8.79289C8.68342 9.18342 8.68342 9.81658 8.29289 10.2071L1 17.5" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="home_rating_description">
                Проходите тесты, зарабатывайте баллы и повышайте уровень!
            </div>
            <div class="home_rating_your">
                <img :src="avatar" alt="">
                <div class="home_rating_your_name">Вы</div>
                <div class="home_rating_your_points">{{ user.total_points }} {{ getRussianPoints(Number(user.total_points)) }}</div>
            </div>
            <div class="home_rating_list">
                <div @click="toLink('user', us.id)" v-for="(us, key) in user.friends?.sort((a, b) => Number(b.total_points) - Number(a.total_points))?.slice(0, 3)">
                    <div class="home_rating_list_number" :style="{background: topColors[key+1]}"><div>{{ key+1 }}</div></div>
                    <img :src="us.avatar.startsWith('http') ? us.avatar : config.storage + us.avatar" alt="">
                    <div class="home_rating_list_name">{{ us.fullname }}</div>
                    <div class="home_rating_list_points">{{ us.total_points }} {{ getRussianPoints(Number(us.total_points)) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>