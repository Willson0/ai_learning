<script>
import {closeOverlay, levels, notify, openOverlay, toLink} from "@/utils.js";
    import config from "@/config.json";
import axios from "axios";

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

                selectedLevel: "",
                faculty: "",
                levels: levels,
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
            closeOverlay,
            openOverlay,
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
            },
            async sendSettings () { // TODO: unique
                if (this.selectedLevel !== 'student') this.faculty = "";
                let newUser =
                    {...this.user, level: this.selectedLevel,
                        faculty: this.faculty === '' ? null : this.faculty,
                        isFirst: false};
                this.$store.commit('setUser', newUser);

                let data = {};
                data["initData"] = window.Telegram.WebApp.initData;
                data["level"] = this.selectedLevel;
                data["faculty"] = this.faculty === '' ? null : this.faculty;

                closeOverlay('overlay_first_loading', 'background_first_loading');

                await axios.post(config.backend + 'auth/settings', data).then((response) => {
                    notify('Успешно сохранено')
                }).catch((error) => {
                    alert (error.response.data.message || 'Ошибка при отправке данных. Попробуйте позже.');
                });
            },
            async activeTrial () {
                if (this.user.payment_method_id == null) return this.openAddCard();
                if (!confirm('Вы действительно хотите активировать пробную подписку?')) return;

                await axios.post(config.backend + "subscription/trial", {
                    initData: window.Telegram.WebApp.initData,
                }).then((response) => {
                    notify("Пробная подписка активирована", 0);

                    let newUser = {...this.user};
                    newUser.used_trial = 1;
                    newUser.is_sub = 1;
                    newUser.sub_date = new Date();
                    newUser.sub_date.setDate(newUser.sub_date.getDate() + 7);
                    this.$store.commit('setUser', newUser);
                }).catch((error) => {
                    if (error.response.status === 420) {
                        let link = error.response.data.channel;
                        if (link.startsWith('@')) link = link.split('@')[1];
                        link = "https://t.me/" + link;

                        if (confirm(`Для активации требуется подписка на телеграм канал ${error.response.data.channel}. Перейти?`))
                            window.Telegram.WebApp.openTelegramLink(link);
                        return;
                    }

                    notify(error.response.data.message || 'Ошибка при активации подписки', 1);
                });
            },
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
    <div class="background_first_loading background" style="display: none" @click.stop="closeOverlay('overlay_first_loading', 'background_first_loading')"></div>
    <div class="overlay overlay_first_loading" style="display: none">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="overlay_first_loading_title">Настройки</div>
        <div class="ai_overlay_newSubject">
            <div class="ai_overlay_newSubject_select" id="new_select">
                <div class="ai_overlay_newSubject_select_title">Уровень обучения</div>
                <div class="ai_overlay_newSubject_select_main">
                    <div v-for="(level, key) in levels" @click="selectedLevel = key" :class="{'active': selectedLevel === key}">{{level}}</div>
                </div>
            </div>
            <input v-if="selectedLevel === 'student'" type="text" v-model="faculty" placeholder="Факультет" id="new_name">
        </div>
        <button @click="sendSettings">Сохранить</button>
    </div>
    <div class="home">
        <div @click="toLink('profile')" class="home_profile">
            <img :src="avatar" alt="">
            <div class="home_profile_header">
                <div>Профиль</div>
                <div class="home_profile_header_level">
                    Уровень: <span>{{ levels[user.level] }}</span>
                </div>
            </div>
            <div class="home_profile_buttons">
                <svg @click.stop="selectedLevel = user.level; faculty = user.faculty; openOverlay('overlay_first_loading', 'background_first_loading')" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M25.2694 18.5269L24.0509 17.2391C24.2061 16.3005 24.1863 15.3412 23.9925 14.4098L25.1817 13.122C25.274 13.0191 25.3357 12.8924 25.3598 12.7562C25.384 12.62 25.3695 12.4797 25.3182 12.3513C24.824 11.1319 24.0954 10.0214 23.1736 9.08304C23.0835 8.99282 22.971 8.92813 22.8477 8.89557C22.7244 8.86301 22.5947 8.86374 22.4718 8.89768L20.6782 9.35621C20.1258 8.95896 19.5267 8.6313 18.8943 8.38061L18.4556 6.70257C18.4231 6.56647 18.3511 6.44299 18.2488 6.34756C18.1466 6.25213 18.0184 6.18898 17.8805 6.16599C17.273 6.05238 16.6561 5.99685 16.0381 6.00014C15.2649 6.00381 14.4952 6.10543 13.7474 6.30257C13.6226 6.33397 13.5087 6.39863 13.4177 6.48968C13.3267 6.58073 13.2621 6.69476 13.2307 6.81964L12.7628 8.54646C12.2397 8.77872 11.7429 9.06655 11.2812 9.40499L9.50703 8.96597C9.38247 8.93338 9.25141 8.93522 9.12781 8.9713C9.00421 9.00738 8.89271 9.07635 8.80518 9.17085C7.83602 10.1785 7.09467 11.383 6.63139 12.7025C6.58647 12.8301 6.57725 12.9675 6.60474 13.0999C6.63223 13.2323 6.69538 13.3547 6.78736 13.4537L8.0156 14.7318C7.95321 15.1422 7.92072 15.5552 7.91812 15.9708C7.91812 16.2635 7.91812 16.5562 7.96686 16.8488L6.68988 18.2732C6.60394 18.3679 6.54432 18.4835 6.51695 18.6085C6.48959 18.7335 6.49543 18.8634 6.53391 18.9854C6.93449 20.2754 7.59837 21.468 8.4835 22.4878C8.57252 22.5926 8.68924 22.6701 8.82027 22.7115C8.95131 22.753 9.09135 22.7566 9.22434 22.722L10.901 22.2927C11.4883 22.7865 12.1453 23.1908 12.8506 23.4927L13.338 25.239C13.3755 25.3656 13.4457 25.4801 13.5416 25.5709C13.6374 25.6617 13.7555 25.7255 13.8839 25.7561C14.5867 25.9151 15.3061 25.9971 16.0284 26C16.6042 25.9935 17.1761 25.9382 17.744 25.8341C17.8786 25.8104 18.0038 25.7493 18.1055 25.6579C18.2071 25.5665 18.2812 25.4484 18.3192 25.3171L18.7773 23.6683C19.619 23.3671 20.4035 22.9251 21.0973 22.361L22.813 22.7512C22.9416 22.7812 23.076 22.7751 23.2015 22.7339C23.327 22.6926 23.4387 22.6176 23.5246 22.5171C24.3794 21.5677 25.0416 20.461 25.4741 19.2586C25.5091 19.1295 25.5086 18.9933 25.4726 18.8645C25.4365 18.7357 25.3663 18.619 25.2694 18.5269ZM16.1161 19.7659C15.6235 19.7751 15.134 19.6862 14.676 19.5042C14.2181 19.3222 13.8009 19.0508 13.4488 18.7059C13.0967 18.361 12.8166 17.9494 12.625 17.4951C12.4334 17.0408 12.334 16.5529 12.3327 16.0597C12.3313 15.5666 12.428 15.0782 12.6171 14.6228C12.8062 14.1675 13.084 13.7543 13.4342 13.4075C13.7844 13.0606 14.2001 12.7869 14.657 12.6024C15.1139 12.4179 15.603 12.3263 16.0957 12.3328C17.0807 12.3302 18.0264 12.7193 18.7247 13.4146C19.423 14.1098 19.8168 15.0542 19.8194 16.0401C19.822 17.0259 19.4331 17.9724 18.7385 18.6713C18.0438 19.3702 17.1002 19.7643 16.1152 19.7669" fill="#7B61FF"/>
                </svg>
                <svg @click.stop="toLink('notification')" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.8814 8.50215C11.4866 6.99001 12.9411 6 14.5575 6L17.4425 6C19.0588 6 20.5134 6.99001 21.1186 8.50215L23.3193 14L24.4636 16.0011C25.9884 18.6678 24.0809 22 21.0295 22H10.9705C7.91913 22 6.01158 18.6678 7.53643 16.0011L8.68071 14L10.8814 8.50215Z" fill="#7B61FF"/>
                    <path d="M18.9735 23C18.9735 23.7956 18.6602 24.5587 18.1026 25.1213C17.5449 25.6839 16.7886 26 16 26C15.2114 26 14.4551 25.6839 13.8974 25.1213C13.3398 24.5587 13.0265 23.7956 13.0265 23H18.9735Z" fill="#7B61FF"/>
                </svg>
            </div>
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
<!--        -->
        <div class="subscription_trial" v-if="user.used_trial !== 1 && user.is_sub === 0">
            <div class="subscription_trial_title">Пробная подписка на 7 дней</div>
            <div class="subscription_trial_description">Попробуйте расширенные функции с пробной подпиской</div>
            <button @click="activeTrial">Подключить</button>
        </div>
<!--        -->
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