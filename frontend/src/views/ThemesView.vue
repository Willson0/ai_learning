<script>
import DoubleSelector from "@/components/DoubleSelectorComponent.vue";
import {closeOverlay, getDate, notify, openOverlay, toLink} from "@/utils.js";
import axios from "axios";
import config from "@/config.json"

export default {
    name: "ThemesView",
    components: {DoubleSelector},
    data () {
        return {
            colors: {
                1: "#00C896",
                2: '#FFB800',
                3: '#FF5C5C',
            },
            selectedLevels: [],
            selectedSubjects: [],
            isProbes: false,
            dates: [],
            hours: [],
            minutes: [],
            type: 'ege',
            months: ["янв", "фев", "мар", "апр", "май", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"],

            mouseDown: false,
            startY: 0,
            scrollTop: 0,
            scrollElement: null,

            probeToSchedule: null,
        }
    },
    unmounted () {
        window.removeEventListener('mouseup', this.mouseup);
        window.removeEventListener('mousemove', this.mousemove);
    },
    mounted () {
        let start = new Date();
        start.setDate(start.getDate() - 3);
        for (let i = 0; i < 7; i++) {
            let date = new Date(start);
            date.setDate(date.getDate() + i);

            this.dates.push(date);
        }
        this.hours = [9, 10, 11, 12, 13, 14, 15];
        this.minutes = [57, 58, 59, 0, 1, 2, 3];

        window.addEventListener('mouseup', this.mouseup);
        window.addEventListener('mousemove', this.mousemove);

        let schedule_date = this.$refs.schedule_date;
        let isScrollingDate;
        schedule_date.addEventListener('scroll', (ev) => {
            let first = schedule_date.children[0];
            if (ev.target.scrollTop <= first.offsetHeight) {
                for (let i = 0; i < 7; i++) {
                    let date = new Date(this.dates[0]);
                    date.setDate(date.getDate() - 1);

                    this.dates.unshift(date);
                }
                requestAnimationFrame(() => {
                    ev.target.scrollTop += (first.getBoundingClientRect().height + 8) * 7;
                })
            } else {
                let last = schedule_date.children[schedule_date.children.length - 1];
                if (ev.target.scrollTop >= last.offsetHeight + last.offsetTop - ev.target.clientHeight) {
                    for (let i = 0; i < 7; i++) {
                        let date = new Date(this.dates[this.dates.length - 1]);
                        date.setDate(date.getDate() + 1);

                        this.dates.push(date);
                        // ev.target.scrollTop -= last.offsetHeight + 8;
                    }
                }
            }

            window.clearTimeout(isScrollingDate);
            isScrollingDate = setTimeout(() => {
                this.scrollingFunction(schedule_date, isScrollingDate);
            }, 200);
        });

        let schedule_hours = this.$refs.schedule_hours;
        let isScrollingHours;
        schedule_hours.addEventListener('scroll', (ev) => {
            let first = schedule_hours.children[0];
            if (ev.target.scrollTop <= first.offsetHeight) {
                for (let i = 0; i < 7; i++) {
                    let hour = this.hours[0] - 1;
                    if (hour < 0) hour = 23;
                    this.hours.unshift(hour);
                }
                requestAnimationFrame(() => {
                    ev.target.scrollTop += (first.getBoundingClientRect().height + 8) * 7;
                })
            } else {
                let last = schedule_hours.children[schedule_hours.children.length - 1];
                if (ev.target.scrollTop >= last.offsetHeight + last.offsetTop - ev.target.clientHeight) {
                    for (let i = 0; i < 7; i++) {
                        let hour = this.hours[this.hours.length - 1] + 1;
                        if (hour > 23) hour = 0;
                        this.hours.push(hour);
                    }
                }
            }

            window.clearTimeout(isScrollingHours);
            isScrollingHours = setTimeout(() => {
                this.scrollingFunction(schedule_hours, isScrollingHours);
            }, 200);
        });

        let schedule_minutes = this.$refs.schedule_minutes;
        let isScrollingMinutes;
        schedule_minutes.addEventListener('scroll', (ev) => {
            let first = schedule_minutes.children[0];
            if (ev.target.scrollTop <= first.offsetHeight) {
                for (let i = 0; i < 7; i++) {
                    let minute = this.minutes[0] - 1;
                    if (minute < 0) minute = 59;
                    this.minutes.unshift(minute);
                }
                requestAnimationFrame(() => {
                    ev.target.scrollTop += (first.getBoundingClientRect().height + 8) * 7;
                })
            } else {
                let last = schedule_minutes.children[schedule_minutes.children.length - 1];
                if (ev.target.scrollTop >= last.offsetHeight + last.offsetTop - ev.target.clientHeight) {
                    for (let i = 0; i < 7; i++) {
                        let minute = this.minutes[this.minutes.length - 1] + 1;
                        if (minute > 59) minute = 0;
                        this.minutes.push(minute);
                    }
                }
            }

            window.clearTimeout(isScrollingMinutes);
            isScrollingMinutes = setTimeout(() => {
                this.scrollingFunction(schedule_minutes, isScrollingMinutes);
            }, 200);
        });
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
        filteredProbes () {
            if (!this.user.id) return;
            return this.user?.probes?.filter(pr => {
                if (this.selectedSubjects.length > 0 && !this.selectedSubjects.includes(pr.subject_id))
                    return false;
                return (pr.type === this.type)
            });
        }
    },
    methods: {
        getDate,
        openOverlay, closeOverlay, toLink,
        openSchedule () {
            let schedule = this.user.schedule.find(sch => sch.probe_id === this.probeToSchedule);
            if (schedule) {
                let start = new Date(schedule.date.replace(' ', 'T') + 'Z');
                start.setDate(start.getDate() - 3);

                let startHours = start.getHours() - 3;
                let startMinutes = start.getMinutes() - 3;
                if (startHours < 0) startHours += 24;
                if (startMinutes < 0) startMinutes += 60;

                this.dates = [];
                this.hours = [];
                this.minutes = [];
                for (let i = 0; i < 7; i++) {
                    let date = new Date(start);
                    date.setDate(date.getDate() + i);

                    this.dates.push(date);

                    let hour = startHours + i;
                    if (hour > 23) hour -= 24;
                    this.hours.push(hour);

                    let minute = startMinutes + i;
                    if (minute > 59) minute -= 60;
                    this.minutes.push(minute);
                }
            }

            requestAnimationFrame(() => {
                openOverlay('themes_schedule_overlay', 'themes_schedule_background');
                this.$nextTick(() => {
                    document.querySelectorAll('.themes_schedule_overlay_main>div')
                        .forEach(el => {
                            el.scrollTop = (el.scrollHeight - el.clientHeight) / 2;
                        });
                })
            });
        },
        mousedown(ev) {
            document.body.classList.add("grabbing");
            this.scrollElement = ev.target.closest('.column');

            this.mouseDown = true;
            this.startY = ev.pageY;
        },
        mousemove (ev) {
            if (!this.mouseDown) return;

            ev.preventDefault();
            let slider = this.scrollElement;

            const walk = (ev.pageY - this.startY) * 1; // 1 = чувствительность
            slider.scrollTop -= walk;

            this.startY = ev.pageY;
        },
        mouseup (ev) {
            document.body.classList.remove("grabbing");
            this.mouseDown = false;

            this.scrollingFunction(this.scrollElement, null);
        },
        getRussianVariant (count) {
            if (count % 10 === 1 && count % 100 !== 11) {
                return 'вариант';
            } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
                return 'варианта';
            } else {
                return 'вариантов';
            }
        },
        scrollingFunction (container, isScrolling) {
            if (this.mouseDown) return;

            if (!container) return;
            const containerRect = container.getBoundingClientRect();
            const containerCenter = containerRect.top + containerRect.height / 2;

            let closestChild = null;
            let index = 0;
            let closestDistance = Infinity;

            Array.from(container.children).forEach(child => {
                const childRect = child.getBoundingClientRect();
                const childCenter = childRect.top + childRect.height / 2;
                const distance = Math.abs(childCenter - containerCenter);

                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestChild = child;
                    index = Array.from(container.children).indexOf(child);
                }
            });

            const elementRect = closestChild.getBoundingClientRect();
            let scroll = container.scrollTop + ((elementRect.top + elementRect.height / 2) -
                (containerRect.top + containerRect.height / 2));
            container.scrollTo({
                top: scroll,
                behavior: 'smooth'
            })

            requestAnimationFrame(() => {
                window.clearTimeout(isScrolling);
            });

            return index;
        },
        async createSchedule () {
            if (this.isLoading) return;

            let date_index = this.scrollingFunction(this.$refs.schedule_date, null);
            let hourse_index = this.scrollingFunction(this.$refs.schedule_hours, null);
            let minutes_index = this.scrollingFunction(this.$refs.schedule_minutes, null);

            let date = new Date(this.dates[date_index]);
            date.setFullYear(new Date().getFullYear());
            date.setHours(this.hours[hourse_index]);
            date.setMinutes(this.minutes[minutes_index]);
            date.setSeconds(0);

            this.isLoading = true;
            closeOverlay('themes_schedule_overlay', 'themes_schedule_background');

            let newUser = {...this.user};
            newUser.schedule = newUser.schedule.filter(sch => sch.probe_id !== this.probeToSchedule);
            newUser.schedule.push({
                "user_id": newUser.id,
                "probe_id": this.probeToSchedule,
                "date": date.toISOString().replace('T', ' ').replace('Z', ''),
            });
            this.$store.commit('setUser', newUser);

            await axios.post(config.backend + "schedule/" + this.probeToSchedule, {
                initData: window.Telegram.WebApp.initData,
                date: date.toISOString(),
            }).then((response) => {
                notify("Успешно запланировано", 0);
            }).finally(() => {
                this.isLoading = false;
            })
        }
    }
}
</script>

<template>
    <div class="themes_schedule_background background" @click="closeOverlay('themes_schedule_overlay', 'themes_schedule_background')" style="display: none"></div>
    <div class="themes_schedule_overlay overlay" style="display: none">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="themes_filter_overlay_title">Запланировать</div>
        <div class="themes_schedule_overlay_main">
            <div class="themes_schedule_overlay_main_up"></div>
            <div class="themes_schedule_overlay_main_down"></div>
            <div class="column" ref="schedule_date" @mousedown.stop="mousedown">
                <div v-for="el in dates">{{ el.getDate() + ' ' + months[el.getMonth()] }}</div>
            </div>
            <hr>
            <div class="column" ref="schedule_hours" @mousedown.stop="mousedown">
                <div v-for="el in hours">{{ el.toString().padStart(2, '0') }}</div>
            </div>
            <hr>
            <div class="column" ref="schedule_minutes" @mousedown.stop="mousedown">
                <div v-for="el in minutes">{{ el.toString().padStart(2, '0') }}</div>
            </div>
        </div>
        <button @click="createSchedule">Запланировать</button>
    </div>
    <div class="themes_filter_background background" @click="closeOverlay('themes_filter_overlay', 'themes_filter_background')" style="display: none"></div>
    <div class="themes_filter_overlay overlay" style="display: none">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="themes_filter_overlay_title">Фильтры</div>
        <div class="themes_filter_overlay_block" v-if="!isProbes">
            <div class="themes_filter_overlay_block_title">Сложность</div>
            <div class="themes_filter_overlay_block_list">
                <div v-for="(level, key) in user.levels"
                     :class="{'active': selectedLevels.includes(key)}"
                     @click="selectedLevels.includes(key) ?
                        selectedLevels = selectedLevels.filter(a => a !== key)
                        : selectedLevels.push(key)">
                    <div :style="'background-color: ' + colors[key]" class="circle"></div>
                    <div>{{ level }}</div>
                </div>
            </div>
        </div>
        <div class="themes_filter_overlay_block">
            <div class="themes_filter_overlay_block_title">Предметы</div>
            <div class="themes_filter_overlay_block_list">
                <div v-for="subject in user.subjects"
                     :class="{'active': selectedSubjects.includes(subject.id)}"
                     @click="selectedSubjects.includes(subject.id) ?
                        selectedSubjects = selectedSubjects.filter(a => a !== subject.id)
                        : selectedSubjects.push(subject.id)">
                    <div>{{ subject.name }}</div>
                </div>
            </div>
        </div>
        <button @click="closeOverlay('themes_filter_overlay', 'themes_filter_background')">Применить</button>
    </div>
    <div class="themes">
        <double-selector @change="isProbes = $event" style="margin-top: 0;" first="Темы" second="Пробники"/>
        <div class="themes_selectors">
            <div class="knowledge_selector" v-if="isProbes">
                <div :class="{'active': type === 'ege'}" @click="type = 'ege'"><div>ЕГЭ</div></div>
                <div :class="{'active': type === 'oge'}" @click="type = 'oge'"><div>ОГЭ</div></div>
                <div :class="{'active': type === 'vpr'}" @click="type = 'vpr'"><div>ВПР</div></div>
            </div>
            <button @click="openOverlay('themes_filter_overlay', 'themes_filter_background')" class="ai_filters accept">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 18H8C8.55 18 9 17.55 9 17C9 16.45 8.55 16 8 16H4C3.45 16 3 16.45 3 17C3 17.55 3.45 18 4 18ZM3 7C3 7.55 3.45 8 4 8H20C20.55 8 21 7.55 21 7C21 6.45 20.55 6 20 6H4C3.45 6 3 6.45 3 7ZM4 13H14C14.55 13 15 12.55 15 12C15 11.45 14.55 11 14 11H4C3.45 11 3 11.45 3 12C3 12.55 3.45 13 4 13Z" fill="white"/>
                    </svg>
                    <div>Фильтры</div>
                </div>
            </button>
        </div>
        <div class="themes_title" v-if="!isProbes">Все темы</div>
        <div v-if="!isProbes && user.courses?.length === 0" style="margin-top: 20px;">Тут пока что ничего нет...</div>
        <div class="themes_list" v-if="!isProbes">
            <div class="themes_lesson" v-for="course in user.courses" @click="toLink('course', course.id)">
                <div>
                    <div class="themes_list_item_title">{{ course.title }}</div>
                    <div class="themes_list_item_progress">
                        <div class="themes_list_item_progress_text">{{ course.lessons?.filter(les => les.user_points !== null && les.user_points >= -1).length }}/{{ course.lessons.length }}</div>
                        <div class="themes_list_item_progress_bar">
                            <div :style="{width: course.progress + '%'}"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="themes_list_item_level">
                        <div class="circle" :style="{backgroundColor: colors[course.level]}"></div>
                        <div>{{ user.levels[course.level] }}</div>
                    </div>
                    <div class="themes_list_item_status">
                        <svg v-if="user.courses.find(c => c.id === course.required_course)?.progress < 100" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="24" height="24" rx="8" fill="#888888"/>
                            <path d="M8.22876 18.5997C7.88306 18.5997 7.58723 18.4767 7.34126 18.2308C7.09529 17.9848 6.9721 17.6888 6.97168 17.3426V11.0572C6.97168 10.7115 7.09487 10.4157 7.34126 10.1697C7.58765 9.92378 7.88348 9.80059 8.22876 9.80017H8.8573V8.54309C8.8573 7.67361 9.16382 6.93256 9.77685 6.31994C10.3899 5.70733 11.1309 5.40081 12 5.40039C12.8691 5.39997 13.6103 5.70649 14.2238 6.31994C14.8372 6.9334 15.1435 7.67445 15.1427 8.54309V9.80017H15.7712C16.1169 9.80017 16.413 9.92336 16.6594 10.1697C16.9057 10.4161 17.0287 10.712 17.0283 11.0572V17.3426C17.0283 17.6883 16.9053 17.9844 16.6594 18.2308C16.4134 18.4772 16.1173 18.6001 15.7712 18.5997H8.22876ZM12 15.457C12.3457 15.457 12.6417 15.334 12.8881 15.0881C13.1345 14.8421 13.2575 14.5461 13.2571 14.1999C13.2567 13.8538 13.1337 13.558 12.8881 13.3124C12.6426 13.0669 12.3465 12.9437 12 12.9429C11.6535 12.942 11.3576 13.0652 11.1125 13.3124C10.8674 13.5597 10.7442 13.8555 10.7429 14.1999C10.7417 14.5444 10.8649 14.8404 11.1125 15.0881C11.3601 15.3357 11.656 15.4587 12 15.457ZM10.1144 9.80017H13.8856V8.54309C13.8856 8.0193 13.7023 7.57409 13.3356 7.20744C12.969 6.84079 12.5238 6.65747 12 6.65747C11.4762 6.65747 11.031 6.84079 10.6643 7.20744C10.2977 7.57409 10.1144 8.0193 10.1144 8.54309V9.80017Z" fill="white"/>
                        </svg>
                        <svg v-else-if="course.progress === 100" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect y="0.5" width="24" height="24" rx="8" fill="#FFB800"/>
                            <path d="M9.74992 10.1668H6.74992C6.57311 10.1668 6.40354 10.0966 6.27851 9.97157C6.15349 9.84654 6.08325 9.67697 6.08325 9.50016V6.50016C6.08325 6.32335 6.15349 6.15378 6.27851 6.02876C6.40354 5.90373 6.57311 5.8335 6.74992 5.8335C6.92673 5.8335 7.0963 5.90373 7.22132 6.02876C7.34635 6.15378 7.41659 6.32335 7.41659 6.50016V8.8335H9.74992C9.92673 8.8335 10.0963 8.90373 10.2213 9.02876C10.3463 9.15378 10.4166 9.32335 10.4166 9.50016C10.4166 9.67697 10.3463 9.84654 10.2213 9.97157C10.0963 10.0966 9.92673 10.1668 9.74992 10.1668Z" fill="white"/>
                            <path d="M18.0001 13.1669C17.8233 13.1669 17.6537 13.0967 17.5287 12.9716C17.4037 12.8466 17.3334 12.677 17.3334 12.5002C17.3339 11.3266 16.9471 10.1856 16.2328 9.2543C15.5186 8.32301 14.517 7.65349 13.3833 7.34965C12.2497 7.0458 11.0475 7.12462 9.96322 7.57386C8.87896 8.02311 7.9733 8.81766 7.38677 9.83423C7.29818 9.98726 7.15244 10.0988 6.98159 10.1444C6.81075 10.19 6.6288 10.1658 6.47577 10.0772C6.32274 9.98865 6.21116 9.8429 6.16559 9.67206C6.12002 9.50121 6.14418 9.31926 6.23277 9.16623C6.96627 7.89572 8.09856 6.90279 9.45397 6.34149C10.8094 5.78018 12.3122 5.68188 13.7292 6.06182C15.1461 6.44177 16.3981 7.27872 17.2909 8.44285C18.1837 9.60697 18.6673 11.0332 18.6668 12.5002C18.6668 12.677 18.5965 12.8466 18.4715 12.9716C18.3465 13.0967 18.1769 13.1669 18.0001 13.1669ZM17.2501 19.1669C17.0733 19.1669 16.9037 19.0967 16.7787 18.9716C16.6537 18.8466 16.5834 18.677 16.5834 18.5002V16.1669H14.2501C14.0733 16.1669 13.9037 16.0967 13.7787 15.9716C13.6537 15.8466 13.5834 15.677 13.5834 15.5002C13.5834 15.3234 13.6537 15.1539 13.7787 15.0288C13.9037 14.9038 14.0733 14.8336 14.2501 14.8336H17.2501C17.4269 14.8336 17.5965 14.9038 17.7215 15.0288C17.8465 15.1539 17.9168 15.3234 17.9168 15.5002V18.5002C17.9168 18.677 17.8465 18.8466 17.7215 18.9716C17.5965 19.0967 17.4269 19.1669 17.2501 19.1669Z" fill="white"/>
                            <path d="M11.9999 19.1668C10.2324 19.1651 8.53768 18.4621 7.28782 17.2123C6.03796 15.9624 5.33502 14.2677 5.33325 12.5002C5.33325 12.3234 5.40349 12.1538 5.52851 12.0288C5.65354 11.9037 5.82311 11.8335 5.99992 11.8335C6.17673 11.8335 6.3463 11.9037 6.47132 12.0288C6.59635 12.1538 6.66659 12.3234 6.66659 12.5002C6.66608 13.6738 7.05296 14.8148 7.76719 15.7461C8.48142 16.6774 9.48307 17.3469 10.6167 17.6507C11.7503 17.9546 12.9525 17.8758 14.0368 17.4265C15.1211 16.9773 16.0267 16.1827 16.6133 15.1662C16.6571 15.0904 16.7155 15.024 16.785 14.9708C16.8545 14.9176 16.9338 14.8786 17.0184 14.856C17.103 14.8334 17.1912 14.8277 17.278 14.8393C17.3648 14.8508 17.4485 14.8793 17.5243 14.9232C17.6 14.967 17.6664 15.0254 17.7196 15.0949C17.7729 15.1644 17.8119 15.2437 17.8344 15.3283C17.857 15.4129 17.8627 15.5011 17.8511 15.5879C17.8396 15.6747 17.8111 15.7584 17.7673 15.8342C17.181 16.8452 16.34 17.685 15.3281 18.2697C14.3162 18.8545 13.1686 19.1638 11.9999 19.1668Z" fill="white"/>
                        </svg>
                        <svg v-else-if="course.progress === 100" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12.5" r="12" fill="#00C896"/>
                            <path d="M7 12.8158L10.4483 16.5L17 9.5" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <div class="themes_title" v-if="isProbes">Все пробники</div>
        <div v-if="isProbes && filteredProbes.length === 0" style="margin-top: 20px;">Тут пока что ничего нет...</div>
        <div class="themes_list" v-if="isProbes">
            <div v-for="probe in filteredProbes" @click="toLink('probe-list', probe.id)">
                <div>
                    <div class="themes_list_item_title">{{ probe.title }}</div>
                    <div @click.stop="probeToSchedule = probe.id; openSchedule()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M16.1786 3C16.4343 3 16.6796 3.10159 16.8604 3.28243C17.0413 3.46327 17.1429 3.70854 17.1429 3.96429V5.57143H19.7143C20.0553 5.57143 20.3823 5.70689 20.6234 5.94801C20.8645 6.18912 21 6.51615 21 6.85714V19.7143C21 20.0553 20.8645 20.3823 20.6234 20.6234C20.3823 20.8645 20.0553 21 19.7143 21H4.28571C3.94472 21 3.6177 20.8645 3.37658 20.6234C3.13546 20.3823 3 20.0553 3 19.7143V6.85714C3 6.51615 3.13546 6.18912 3.37658 5.94801C3.6177 5.70689 3.94472 5.57143 4.28571 5.57143H6.85714V3.96429C6.85714 3.70854 6.95874 3.46327 7.13958 3.28243C7.32041 3.10159 7.56568 3 7.82143 3C8.07717 3 8.32244 3.10159 8.50328 3.28243C8.68412 3.46327 8.78571 3.70854 8.78571 3.96429V5.57143H15.2143V3.96429C15.2143 3.70854 15.3159 3.46327 15.4967 3.28243C15.6776 3.10159 15.9228 3 16.1786 3ZM4.92857 11.3571V19.0714H19.0714V11.3571H4.92857ZM4.92857 9.42857H19.0714V7.5H4.92857V9.42857ZM14.9314 13.2471C15.112 13.4279 15.2134 13.673 15.2134 13.9286C15.2134 14.1841 15.112 14.4292 14.9314 14.61L12.0386 17.5029C11.8578 17.6834 11.6127 17.7849 11.3571 17.7849C11.1016 17.7849 10.8565 17.6834 10.6757 17.5029L9.06857 15.8957C8.97383 15.8074 8.89784 15.701 8.84514 15.5827C8.79243 15.4644 8.7641 15.3367 8.76181 15.2072C8.75953 15.0778 8.78334 14.9492 8.83184 14.8291C8.88034 14.709 8.95253 14.5999 9.04409 14.5084C9.13566 14.4168 9.24473 14.3446 9.3648 14.2961C9.48487 14.2476 9.61348 14.2238 9.74296 14.2261C9.87243 14.2284 10.0001 14.2567 10.1184 14.3094C10.2367 14.3621 10.3431 14.4381 10.4314 14.5329L11.3571 15.4586L13.5686 13.2471C13.7494 13.0666 13.9945 12.9651 14.25 12.9651C14.5055 12.9651 14.7506 13.0666 14.9314 13.2471Z" fill="#7B61FF"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="themes_list_item_progress">{{ probe.variants_count }} {{ getRussianVariant(probe.variants_count) }}</div>
                    <div class="themes_list_item_schedule" v-if="user.schedule?.find(sch => sch.probe_id === probe.id)">Запланирован {{ getDate(user.schedule?.find(sch => sch.probe_id === probe.id).date.replace(' ', 'T') + 'Z') }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>