<script>
import {closeAllOverlays, closeOverlay, deepParse, endLoading, notify, openOverlay, startLoading} from "@/utils.js";
import axios from "axios";
import config from "@/config.json";
import { marked } from "marked";

export default {
    name: "ProbeView",
    data () {
        return {
            variant: {},
            answers: [],
            lessonNumber: 0,
            result: [],
            config: config,

            dateEnd: null,
            endString: "",
            dateInterval: null,
            pauseDate: null,

            answerNumber: -1,
            rightHtml: "",
            marked: marked,

            theme: "",
        }
    },
    mounted () {
        this.theme = window.Telegram.WebApp.colorScheme;
        this.initVariants();

        this.offAllBackButton();
        window.Telegram.WebApp.BackButton.onClick(this.onFirstNumber);
    },
    unmounted () {
        clearInterval(this.dateInterval);

        this.offAllBackButton();
        window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
    },
    methods: {
        closeAllOverlays,
        openOverlay, closeOverlay,
        async initVariants () {
            if (!this.user.id) return;
            if (Object.keys(this.variant).length !== 0) return

            let variant;
            for (let probe of this.user.probes) {
                if (probe.variants == null) continue;
                variant = probe.variants.find(vr => vr.id === Number(this.$route.query.id));
                if (variant != null) break;
            }

            if (variant == null) {
                await axios.post(config.backend + "variant/" + Number(this.$route.query.id), {
                    initData: window.Telegram.WebApp.initData
                }).then((response) => {
                    this.variant = response.data;
                    endLoading('probe_loading');

                    let newUser = {...this.user};
                    if (newUser.probes.find(pr => pr.id === response.data.probe_id).variants === undefined)
                        newUser.probes.find(pr => pr.id === response.data.probe_id).variants = [];
                    newUser.probes.find(pr => pr.id === response.data.probe_id).variants.push(response.data);
                    this.$store.commit('setUser', newUser);

                    this.beforeInit();
                })
            } else {
                this.variant = variant;
                this.beforeInit();
                endLoading('probe_loading');
            }
        },
        beforeInit () {
            this.variant = deepParse(this.variant);
            for (let exercise in this.variant.exercises) {
                this.variant.exercises[exercise].html = marked.parse(this.variant.exercises[exercise].text);
            }

            this.dateEnd = new Date();

            let probe = this.user.probes.find(pr => pr.id === this.variant.probe_id);
            let subject = this.user.subjects.find(sb => sb.id === probe.subject_id);
            let time = subject.state_description[probe.type]?.time;

            this.dateEnd.setMinutes(this.dateEnd.getMinutes() + time);
            this.initTimerInterval();
        },
        getTime (time) {
            if (time == null) return "";

            let hours = Math.floor(time / 60);
            let minutes = Math.floor(time % 60);

            return hours + "ч " + minutes.toString().padStart(2, '0') + "м";
        },
        pause () {
            document.body.style.overflow = "hidden";
            this.pauseDate = new Date();
            clearInterval(this.dateInterval);

            let el = this.$refs.stop;
            el.style.display = "";
            el.style.opacity = "0";
            requestAnimationFrame(() => {
                el.style.opacity = "1";
            })
        },
        unpause () {
            document.body.style.overflow = "";
            let el = this.$refs.stop;
            el.style.opacity = "0";
            el.addEventListener("transitionend", () => {
                el.style.display = "none";
            }, {once: true});

            this.dateEnd.setTime(this.dateEnd.getTime() + (new Date().getTime() - this.pauseDate.getTime()));
            this.pauseDate = null;

            this.initTimerInterval();
        },
        initTimerInterval () {
            let timeLeft = this.dateEnd.getTime() - new Date().getTime();
            this.endString = this.getTime(timeLeft / (1000*60))

            clearInterval(this.dateInterval);
            this.dateInterval = setInterval(() => {
                let timeLeft = this.dateEnd.getTime() - new Date().getTime();
                this.endString = this.getTime(timeLeft / (1000*60))
            }, 5000);
        },
        async nextQuestion () {
            if (this.lessonNumber === this.variant.exercises.length - 1) {
                for (let i = 0; i < this.variant.exercises.length; i++) {
                    if (this.answers[i] === undefined) {
                        if (!confirm("Вы не ответили на все номера. Продолжить?")) return;
                        break;
                    }
                }
                startLoading("loading_lesson");
                await this.sendData();
            }
            else this.lessonNumber++;
        },
        async sendData () {
            await axios.post(config.backend + "variant/" + this.$route.query.id + "/check", {
                initData: window.Telegram.WebApp.initData,
                answers: this.answers
            }).then((response) => {
                this.lessonNumber = this.variant?.exercises.length;
                endLoading("loading_lesson");

                this.result = response.data;
                for (let answer in this.result) {
                    this.result[answer].html = marked.parse(this.result[answer].text);
                }
            });
        },
        restart () {
            if (confirm("Вы уверены, что хотите начать заново?")) {
                this.lessonNumber = 0;
                this.answers = [];
                this.result = [];
            }
        },
        toMainResult () {
            this.answerNumber = -1;
        },
        toPrevNumber () {
            this.lessonNumber -= 1;
        },
        onFirstNumber () {
            if (confirm("Вы уверены, что хотите выйти?")) {
                window.backByQueryFunction();
            }
        },
        offAllBackButton () {
            window.Telegram.WebApp.BackButton.offClick(window.backByQueryFunction);
            window.Telegram.WebApp.BackButton.offClick(this.toMainResult);
            window.Telegram.WebApp.BackButton.offClick(this.toPrevNumber);
            window.Telegram.WebApp.BackButton.offClick(this.onFirstNumber);

            window.Telegram.WebApp.BackButton.show();
        }
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
    },
    watch: {
        async user () {
            await this.initVariants();
        },
        lessonNumber () {
            this.offAllBackButton();
            if (this.lessonNumber === 0)
                window.Telegram.WebApp.BackButton.onClick(this.onFirstNumber);
            else if (this.lessonNumber !== this.variant?.exercises?.length)
                window.Telegram.WebApp.BackButton.onClick(this.toPrevNumber);
            else
                window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
        },
        answerNumber () {
            this.offAllBackButton();
            if (this.answerNumber !== -1)
                window.Telegram.WebApp.BackButton.onClick(this.toMainResult);
            else
                window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
        }
    },
}
</script>

<template>
    <div class="loading_lesson loading" style="display: none"></div>
    <div class="probe_loading loading"></div>
    <div class="probe_stop" ref="stop" style="display: none"></div>
    <div class="probe" v-if="lessonNumber !== variant?.exercises?.length && answerNumber === -1">
        <div class="probe_header">
            <svg @click="pauseDate === null ? pause() : unpause()" width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="12" fill="#7B61FF"/>
                <path d="M20 16V32" stroke="white" stroke-width="2" stroke-linecap="round"/>
                <path d="M28 16V32" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <div class="probe_header_time"><div>Осталось: {{ endString }}</div></div>
            <button class="cancel">Завершить</button>
        </div>
        <div class="probe_title">Задание {{ lessonNumber + 1 }}</div>
        <div class="probe_text markdown-body" :class="theme" v-html="variant?.exercises?.[lessonNumber]?.html"></div>
        <div class="probe_input">
            <div>Ответ: </div>
            <input v-model="answers[lessonNumber]" type="text">
        </div>
        <div class="lesson_test_content_footer">
            <div class="lesson_test_content_footer_numbers">
                <div :class="{active: key === lessonNumber}" @click="lessonNumber = key" v-for="(question, key) in variant.exercises">
                    <div>{{ key + 1 }}</div>
                </div>
            </div>
            <button @click="nextQuestion()">Дальше</button>
        </div>
    </div>
    <div class="probe_result" v-else-if="answerNumber === -1">
        <div class="probe_result_title">Экзамен окончен</div>
        <div class="probe_result_main">
            <div>
                <div class="probe_result_main_title">Ваш результат</div>
                <div class="probe_result_main_points">{{ result?.filter(res => res.is_right === true)?.reduce((acc, a) => acc + a.points, 0) }} из {{ result?.reduce((acc, a) => acc + a.points, 0) }}</div>
            </div>
            <div>
                <div class="probe_result_main_title">Правильные ответы</div>
                <div class="probe_result_main_points">{{ result?.filter(res => res.is_right === true)?.length }} из {{ result?.length }}</div>
            </div>
        </div>
        <div class="home_supportChat_main">
            <div class="home_supportChat_main_background"></div>
            <div class="home_supportChat_main_upper">
                <div class="home_supportChat_main_upper_header" v-if="result?.filter(res => res.is_right === true)?.reduce((acc, a) => acc + a.points, 0) / result?.reduce((acc, a) => acc + a.points, 0) < 0.5">
                    Не очень хороший результат! Но при должной подготовке и проверке ошибок — все возможно!
                </div>
                <div class="home_supportChat_main_upper_header" v-else>
                    Отличный результат! Продолжайте в том же духе!
                </div>
            </div>
            <div class="home_supportChat_main_downer">
                <div></div>
                <button @click="openOverlay('probe_result_overlay', 'probe_result_background')">Проверить ответы</button>
            </div>
        </div>
        <button @click="restart()">Пройти заново</button>
    </div>
    <div class="background probe_result_background" @click="closeOverlay('probe_result_overlay', 'probe_result_background')" style="display: none"></div>
    <div class="overlay probe_result_overlay" style="display: none">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="probe_result_overlay_title">Проверка ответов</div>
        <div class="probe_result_overlay_answers">
            <div v-for="(answer, key) in result" @click="closeAllOverlays(); answerNumber = key">
                <div>{{ key + 1 }}</div>
                <svg v-if="answer.is_right" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="24" rx="12" fill="#00C896"/>
                    <path d="M8 11.5556L10.8571 16L16 8" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="24" rx="12" fill="#FF5C5C"/>
                    <path d="M15.6465 7.64648C15.8417 7.45122 16.1583 7.45122 16.3535 7.64648C16.5487 7.84175 16.5488 8.15827 16.3535 8.35352L12.707 12L16.3535 15.6465C16.5487 15.8417 16.5488 16.1583 16.3535 16.3535C16.1583 16.5488 15.8417 16.5487 15.6465 16.3535L12 12.707L8.35352 16.3535C8.15827 16.5488 7.84175 16.5487 7.64648 16.3535C7.45122 16.1583 7.45122 15.8417 7.64648 15.6465L11.293 12L7.64648 8.35352C7.45122 8.15825 7.45122 7.84175 7.64648 7.64648C7.84175 7.45122 8.15825 7.45122 8.35352 7.64648L12 11.293L15.6465 7.64648Z" fill="white"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="probe_exercise" v-if="answerNumber !== -1">
        <div class="probe_exercise_title">Задание {{ answerNumber + 1 }}</div>
        <div class="probe_exercise_result">
            <svg v-if="result[answerNumber]?.is_right" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="24" rx="12" fill="#00C896"/>
                <path d="M8 11.5556L10.8571 16L16 8" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="24" rx="12" fill="#FF5C5C"/>
                <path d="M15.6465 7.64648C15.8417 7.45122 16.1583 7.45122 16.3535 7.64648C16.5487 7.84175 16.5488 8.15827 16.3535 8.35352L12.707 12L16.3535 15.6465C16.5487 15.8417 16.5488 16.1583 16.3535 16.3535C16.1583 16.5488 15.8417 16.5487 15.6465 16.3535L12 12.707L8.35352 16.3535C8.15827 16.5488 7.84175 16.5487 7.64648 16.3535C7.45122 16.1583 7.45122 15.8417 7.64648 15.6465L11.293 12L7.64648 8.35352C7.45122 8.15825 7.45122 7.84175 7.64648 7.64648C7.84175 7.45122 8.15825 7.45122 8.35352 7.64648L12 11.293L15.6465 7.64648Z" fill="white"/>
            </svg>
            <div>{{ result[answerNumber]?.is_right ? 'Правильный' : 'Неправильный' }} ответ</div>
        </div>
        <div class="probe_exercise_text markdown-body" :class="theme" v-html="result[answerNumber]?.html"></div>
        <div class="probe_exercise_footer">
            <div>
                <div>
                    <div class="probe_exercise_footer_title">Ваш ответ</div>
                    <div class="probe_exercise_footer_value">{{ result[answerNumber]?.user_answer !== '' ? result[answerNumber]?.user_answer : "Отсутствует" }}</div>
                </div>
            </div>
            <div style="cursor:pointer" @click="rightHtml = marked.parse(result[answerNumber]?.right_answer.description); openOverlay('probe_exercise_overlay', 'probe_exercise_background')">
                <div>
                    <div class="probe_exercise_footer_title">Правильный ответ</div>
                    <div class="probe_exercise_footer_value">{{ result[answerNumber]?.right_answer.value }}</div>
                </div>
                <svg width="10" height="18" viewBox="0 0 10 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L8.29289 8.29289C8.68342 8.68342 8.68342 9.31658 8.29289 9.70711L1 17" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
        <div class="lesson_test_content_footer">
            <div class="lesson_test_content_footer_numbers">
                <div :class="{active: key === answerNumber}" @click="answerNumber = key" v-for="(question, key) in result">
                    <div>{{ key + 1 }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="probe_exercise_background background" @click="closeOverlay('probe_exercise_overlay', 'probe_exercise_background')" style="display: none"></div>
    <div class="probe_exercise_overlay overlay" style="display: none">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="probe_exercise_overlay_title">Решение</div>
        <div class="probe_exercise_overlay_text markdown-body" :class="theme" v-html="rightHtml"></div>
    </div>
</template>

<style scoped>

</style>