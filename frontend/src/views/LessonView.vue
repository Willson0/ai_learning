<script>
import {closeOverlay, deepParse, endLoading, notify, openOverlay, startLoading, toLink} from "@/utils.js";
import ChatComponent from "@/components/ChatComponent.vue";
import config from '@/config.json';
import axios from "axios";

export default {
    name: "LessonView",
    components: {ChatComponent},
    data () {
        return {
            lesson: {},
            answers: [],
            lessonNumber: -1,
            result: null,
            isCourseRestart: false,
            realResult: null,
            config: config,

            mouseDown: false,
            startX: 0,
            scrollLeft: 0,
            isDragging: false,

            hints: [],
            chat_id: -1,
        }
    },
    async mounted () {
        this.calcTop();
        window.addEventListener('resize', this.calcTop);

        await axios.post(config.backend + "lesson/" + this.$route.query.id, {
            initData: window.Telegram.WebApp.initData,
        }).then((response) => {
            this.lesson = response.data;
            endLoading("loading_lesson");
        })

        window.addEventListener('mouseup', this.mouseup);
        window.addEventListener('mousemove', this.mousemove);
    },
    methods: {
        toLink,
        openOverlay,
        closeOverlay,
        calcTop () {
            let svg = this.$refs.result_svg;
            if (svg) {
                let mainBlockHeight = svg.querySelector('rect');
                svg.style.top = "calc(50% - 50px - " + mainBlockHeight.getBoundingClientRect().height / 2 + "px)";

                let text = this.$refs.result_text;
                if (text) {
                    text.style.top = svg.getBoundingClientRect().top + "px";
                }
            }
        },
        openUrl (url) {
            window.Telegram.WebApp.openLink(url);
        },
        async nextQuestion () {
            // if (this.answers[this.lessonNumber] === undefined) return;
            if (this.lessonNumber === this.lesson.questions.length - 1) {
                for (let i = 0; i < this.lesson.questions.length; i++) {
                    if (this.answers[i] === undefined) {
                        return notify("Ответьте на все вопросы!", 1);
                    }
                }

                startLoading("loading_lesson");
                await this.sendData();
            }
            else this.lessonNumber++;
        },
        async sendData () {
            await axios.post(config.backend + "lesson/" + this.$route.query.id + "/check", {
                initData: window.Telegram.WebApp.initData,
                answers: this.answers
            }).then((response) => {
                this.lessonNumber = this.lesson?.questions.length;

                let realResult = response.data.points;
                this.realResult = realResult;
                this.result = 0;
                endLoading("loading_lesson");
                setTimeout(() => {
                    let interval = setInterval(() => {
                        if (realResult === this.result) {
                            clearInterval(interval);
                            // let title = this.$refs.endPoint;
                            // title.parentNode.style.transform = "translate(-50%, -50%)";
                            // title.querySelector("span").style.opacity = "1";
                            //
                            // document.querySelectorAll('.lesson_test_result>button, .lesson_test_result_description').forEach((item) => {
                            //     item.style.opacity = "1";
                            // });
                            // if (this.$refs.end_svg) this.$refs.end_svg.style.opacity = "1";
                        }
                        else this.result += 1;
                    }, 10);
                }, 200);

                if (this.lesson.count_tries > 0 && this.realResult < 50 &&
                    this.user.courses.find((course) => course.id === this.lesson.course_id).lessons.find((lesson) => lesson.id === this.lesson.id).user_count_tries + 1 === this.lesson.count_tries) {
                    this.isCourseRestart = true;
                }

                axios.post(config.backend + "auth/profile", {
                    "initData": window.Telegram.WebApp.initData,
                }).then((response) => {
                    let user = response.data;
                    user.courses.forEach(course => {
                        const lessons = course.lessons;
                        const total = lessons.length;
                        const completed = lessons.filter(lesson =>
                            // lesson.user_points !== null && lesson.user_points >= 50
                            lesson.user_points !== null
                        ).length;
                        course.progress = total > 0 ? Math.round((completed / total) * 100) : 0;
                    });
                    this.$store.dispatch("updateUser", deepParse(user));
                })
            });
        },
        restart () {
            this.answers = [];
            this.lessonNumber = -1;
        },
        prevQuestion () {
            this.lessonNumber--;
        },

        mousedown(ev) {
            let el = this.$refs.newsNav;

            this.mouseDown = true;
            this.startX = ev.pageX - el.offsetLeft;
            this.scrollLeft = el.scrollLeft;

            document.body.classList.add("grabbing");
        },
        mousemove (ev) {
            if (!this.mouseDown) return;

            if (Math.abs(ev.pageX - this.startX) > 5) {
                this.isDragging = true
            }

            ev.preventDefault();
            let slider = this.$refs.newsNav;

            const x = ev.pageX - slider.offsetLeft;
            const walk = (x - this.startX) * 1; // 1 = чувствительность
            console.log(x, this.startX);
            slider.scrollLeft = this.scrollLeft - walk;
        },
        mouseup (ev) {
            document.body.classList.remove("grabbing");

            this.mouseDown = false;
            setTimeout(() => {
                this.isDragging = false;
            }, 100);
        },
        async getHint () {
            if (this.hints[this.lessonNumber] !== undefined)
                return openOverlay("lesson_hint_overlay", "lesson_hint_background");

            if (this.user.free_hints === 0 && this.user.hints === 0) return notify("У вас нет больше нет подсказок!", 1);

            openOverlay("lesson_hint_overlay", "lesson_hint_background");
            this.hints[this.lessonNumber] = "Загрузка...";

            let newUser = {...this.user};
            if (newUser.free_hints > 0) newUser.free_hints--;
            else if (newUser.hints > 0) newUser.hints--;
            this.$store.dispatch("updateUser", newUser);

            await axios.post(config.backend + "lesson/" + this.lesson.id + "/hint/" + this.lessonNumber, {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                this.hints[this.lessonNumber] = response.data.hint;
            })
        }
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
        lessonProgress () {
            let counter = 0;
            for (let i = 0; i < this.lesson.questions.length; i++) {
                if (this.answers[i] !== undefined) {
                    counter++;
                }
            }
            return counter;
        }
    },
    unmounted () {
        window.Telegram.WebApp.BackButton.offClick(this.prevQuestion);
        window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);

        window.removeEventListener('mouseup', this.mouseup);
        window.removeEventListener('mousemove', this.mousemove);
    },
    watch: {
        lessonNumber (oldValue, newValue) {
            if (this.lessonNumber === 0) {
                window.Telegram.WebApp.BackButton.offClick(window.backByQueryFunction);
                window.Telegram.WebApp.BackButton.onClick(this.prevQuestion);
            }
            if (this.lessonNumber === -1 || this.lessonNumber === this.lesson.questions.length) {
                window.Telegram.WebApp.BackButton.offClick(this.prevQuestion);
                window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
            }
            if (this.lessonNumber === -1 || this.lessonNumber === 0) {
                let oldBlock = this.lessonNumber === 0 ? this.$refs.theory : this.$refs.test;
                let newBlock = this.lessonNumber === 0 ? this.$refs.test : this.$refs.theory;

                oldBlock.style.opacity = "0";
                oldBlock.addEventListener("transitionend", () => {
                    oldBlock.style.display = "none";
                    newBlock.style.display = "";
                    newBlock.style.opacity = "0";
                    requestAnimationFrame( () => {
                        newBlock.style.opacity = "1";
                    })
                }, {once: true});
            }
            requestAnimationFrame(() => {
                this.calcTop();
            })
        }
    }
}
</script>

<template>
    <div class="loading loading_lesson"></div>
    <div class="lesson_hint_background background" style="display: none" @click="closeOverlay('lesson_hint_overlay', 'lesson_hint_background')"></div>
    <div class="lesson_hint_overlay overlay" style="display: none">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="lesson_hint_overlay_title">Подсказка</div>
        <div class="lesson_hint_overlay_description" v-if="user.free_hints > 0">Бесплатных подсказок осталось: {{ user.free_hints }}</div>
        <div class="lesson_hint_overlay_description" v-else>Подсказок осталось: {{ user.hints }}</div>
        <div class="lesson_hint_overlay_content">{{ hints[lessonNumber] }}</div>
    </div>

    <div class="lesson_chat_background background" @click="closeOverlay('lesson_chat_overlay', 'lesson_chat_background')" style="display: none"></div>
    <div class="lesson_chat_overlay overlay" style="display: none">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="lesson_chat_overlay_title">Чат с ИИ</div>
        <chat-component :chat_id="chat_id" @chatload="chat_id = $event.id"/>
    </div>

    <div class="lesson_theory" ref="theory">
        <div class="lesson_theory_title">{{ lesson.title }}</div>
        <div class="lesson_theory_points">{{ lesson.oldResult ?? 0 }}/100 баллы</div>
        <div class="article_materials_list">
            <div @click="openUrl(lesson.videos?.rutube)">
                <svg width="33" height="32" viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8.2824 32H24.7144C29.012 32 32.4968 28.5152 32.4968 24.2176V7.7824C32.5 3.4944 29.028 0.0128 24.74 0H8.26C3.972 0.0128 0.5 3.4944 0.5 7.7824V24.2144C0.5 28.5152 3.9848 32 8.2824 32Z" fill="#100943"/>
                    <path d="M24.74 0H16.5C16.5 8.8352 23.6648 16 32.5 16V7.7824C32.5 3.4944 29.028 0.0128 24.74 0Z" fill="#ED143B"/>
                    <path d="M20.2666 15.2704H10.8138V11.5296H20.2666C20.8202 11.5296 21.2042 11.6256 21.3962 11.7952C21.5882 11.9648 21.7098 12.2752 21.7098 12.7296V14.0736C21.7098 14.5536 21.5914 14.864 21.3962 15.0336C21.2042 15.2 20.8202 15.2704 20.2666 15.2704ZM20.9162 8H6.8042V24H10.8138V18.7936H18.2026L21.7066 24H26.1962L22.3306 18.7712C23.7546 18.56 24.3946 18.1216 24.9226 17.4048C25.4506 16.6848 25.7162 15.5328 25.7162 14V12.8C25.7162 11.888 25.6202 11.168 25.4506 10.6176C25.281 10.0672 24.9962 9.5872 24.5866 9.1552C24.1546 8.7456 23.6746 8.4608 23.0986 8.2688C22.5226 8.096 21.8026 8 20.9162 8Z" fill="white"/>
                </svg>
            </div>
            <div @click="openUrl(lesson.videos?.vk)">
                <svg width="33" height="32" viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <mask id="mask0_447_14061" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="33" height="32">
                        <path d="M32.5 0H0.5V32H32.5V0Z" fill="white"/>
                    </mask>
                    <g mask="url(#mask0_447_14061)">
                        <path d="M0.5 11.6992C0.5 2.0672 2.564 0 12.1992 0H20.8008C30.4328 0 32.5 2.064 32.5 11.6992V20.3008C32.5 29.9328 30.436 32 20.8008 32H12.1992C2.5672 32 0.5 29.936 0.5 20.3008V11.6992Z" fill="#0077FF"/>
                        <path d="M17.6935 32H20.7431C30.1959 32 32.3975 30.032 32.4935 20.9664L32.4967 20.3008V11.6992C32.4967 11.456 32.4967 11.2192 32.4935 10.9824C32.3879 1.9616 30.1799 0 20.7431 0H17.6935C8.01354 0 5.93994 2.0672 5.93994 11.6992V20.3008C5.93994 29.9328 8.01354 32 17.6935 32Z" fill="#FF2B42"/>
                        <path d="M23.0792 13.2318C24.6952 14.1662 25.5048 14.6334 25.7768 15.2414C26.0136 15.7726 26.0136 16.3806 25.7768 16.9118C25.5048 17.5198 24.6952 17.987 23.0792 18.9214L18.6472 21.4814C17.028 22.419 16.2184 22.883 15.556 22.8158C14.9768 22.755 14.4488 22.451 14.1096 21.9806C13.7192 21.4398 13.7192 20.5086 13.7192 18.6398V13.5198C13.7192 11.6542 13.7192 10.7198 14.1096 10.179C14.452 9.70865 14.9768 9.40465 15.556 9.34385C16.2216 9.27345 17.028 9.74065 18.6472 10.675L23.0792 13.2318Z" fill="white"/>
                    </g>
                </svg>
            </div>
            <div @click="openUrl(lesson.videos?.youtube)">
                <svg width="47" height="32" viewBox="0 0 47 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M23.4759 32C23.4759 32 37.8861 32 41.4605 31.04C43.473 30.496 44.9866 28.928 45.5178 26.992C46.5 23.44 46.5 15.968 46.5 15.968C46.5 15.968 46.5 8.544 45.5178 5.024C44.9866 3.04 43.473 1.504 41.4605 0.976002C37.8861 0 23.4759 0 23.4759 0C23.4759 0 9.09783 0 5.53955 0.976002C3.55916 1.504 2.01348 3.04 1.44995 5.024C0.5 8.544 0.5 15.968 0.5 15.968C0.5 15.968 0.5 23.44 1.44995 26.992C2.01348 28.928 3.55916 30.496 5.53955 31.04C9.09783 32 23.4759 32 23.4759 32Z" fill="#FF0033"/>
                    <path d="M30.6378 16.0002L18.7412 9.2002V22.8002L30.6378 16.0002Z" fill="white"/>
                </svg>
            </div>
        </div>
        <button @click="lessonNumber = 0">Я изучил</button>
    </div>
    <div class="lesson_test" ref="test" style="display: none">
        <div class="lesson_test_progress" v-if="lessonNumber >= 0 && lessonNumber !== lesson.questions?.length">
            <div class="lesson_test_progress_title">Прогресс</div>
            <div class="home_learning_progress">
                <div class="home_learning_progress_bar" :style="{width: (lessonProgress / lesson.questions?.length) * 100 + '%'}">
                    <div>{{ lessonProgress }}/{{ lesson.questions?.length }}</div>
                </div>
            </div>
        </div>
        <div class="lesson_test_content" v-if="lessonNumber >= 0 && lessonNumber !== lesson.questions?.length">
            <div class="lesson_test_content_question">{{ lesson.questions[lessonNumber].question }}</div>
            <div class="lesson_test_content_buttons">
                <div @click="getHint">
                    <svg width="13" height="18" viewBox="0 0 13 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.594 12.7262L9.48567 12.9984C7.54783 13.6491 5.45217 13.6491 3.51433 12.9984L3.406 12.7262C3.30308 12.4757 3.25108 12.3493 3.16117 12.2339C3.07233 12.1174 2.91633 11.9997 2.60433 11.7656C1.51189 10.9433 0.704597 9.79623 0.296874 8.48693C-0.110848 7.17762 -0.0983135 5.77252 0.332703 4.47077C0.763719 3.16903 1.59135 2.03669 2.69829 1.23426C3.80522 0.431821 5.1353 0 6.5 0C7.8647 0 9.19478 0.431821 10.3017 1.23426C11.4087 2.03669 12.2363 3.16903 12.6673 4.47077C13.0983 5.77252 13.1108 7.17762 12.7031 8.48693C12.2954 9.79623 11.4881 10.9433 10.3957 11.7656C10.0837 11.9997 9.92767 12.1174 9.83883 12.2339C9.75 12.3504 9.698 12.4746 9.594 12.7262ZM4.17083 15.4271C4.26472 16.0022 4.31817 16.5823 4.33117 17.1675C4.33226 17.2448 4.35433 17.3204 4.39499 17.386C4.43566 17.4517 4.49337 17.5049 4.56192 17.54C5.16367 17.8425 5.82721 18 6.5 18C7.17279 18 7.83634 17.8425 8.43808 17.54C8.50663 17.5049 8.56435 17.4517 8.60501 17.386C8.64567 17.3204 8.66774 17.2448 8.66883 17.1675C8.68183 16.5823 8.73528 16.0022 8.82917 15.4271C7.29239 15.744 5.70761 15.744 4.17083 15.4271Z" fill="white"/>
                    </svg>
                    <div>Подсказка</div>
                </div>
                <div @click="openOverlay('lesson_chat_overlay', 'lesson_chat_background')">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="1" y="3" width="18.6667" height="16" rx="2" fill="white"/>
                        <rect x="5" y="8.3335" width="2.66667" height="2.66667" rx="1.33333" fill="#7B61FF"/>
                        <rect x="13" y="8.3335" width="2.66667" height="2.66667" rx="1.33333" fill="#7B61FF"/>
                        <path d="M14.7947 12.3335H5.87192C4.78447 12.3335 4.47848 13.8243 5.47801 14.2526L9.93941 16.1647C10.191 16.2725 10.4757 16.2725 10.7273 16.1647L15.1887 14.2526C16.1882 13.8243 15.8822 12.3335 14.7947 12.3335Z" fill="#7B61FF"/>
                    </svg>
                    <div>Спросить у ИИ</div>
                </div>
            </div>
            <div class="lesson_test_content_answers">
                <div v-for="(answ, key) in lesson.questions?.[lessonNumber]?.answers"
                     :class="{'active': answers[lessonNumber] === key}" @click="answers[lessonNumber] = key">
                    {{ answ }}
                </div>
            </div>
            <div class="lesson_test_content_footer">
                <div @mousedown.stop="mousedown" ref="newsNav" class="lesson_test_content_footer_numbers">
                    <div @click="!this.isDragging ? lessonNumber = num-1 : ''"
                         :class="{active: lessonNumber === num-1}" v-for="num in lesson.questions?.length">
                        <div>{{ num }}</div>
                    </div>
                </div>
                <button @click="nextQuestion()">{{ lessonNumber === lesson.questions?.length - 1 ? 'Узнать результат' : 'Ответить' }}</button>
            </div>
        </div>
        <div class="lesson_result" v-else-if="lessonNumber === lesson.questions?.length">
            <div v-if="lesson.count_tries > 0" class="lesson_result_text" ref="result_text">
                <div class="lesson_result_text_title" :class="{active: realResult >= 50}">{{ realResult >= 50 ? 'Успех!' : 'Неудача ;(' }}</div>
                <div class="lesson_result_text_description" v-if="realResult < 50 && !isCourseRestart">По совершению двух неудач весь прогресс по теме и урокам сбрасывается</div>
                <div class="lesson_result_text_description" v-else-if="realResult < 50">Придется начать сначала</div>
            </div>
            <svg ref="result_svg" v-if="realResult >= 50" viewBox="0 0 390 516" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M208 288C223.464 288 236 300.536 236 316L236 516L5.29259e-06 516L1.52588e-05 288L208 288Z" fill="#00C896" fill-opacity="0.75"/>
                <path d="M390 288L390 516L244 516L244 308C244 296.954 252.954 288 264 288L390 288Z" fill="#00C896"/>
                <rect x="95" width="199" height="173" rx="20" fill="#00C896" fill-opacity="0.05"/>
                <path d="M302 68C302 52.536 314.536 40 330 40H390L390 280H189C173.536 280 161 267.464 161 252L161 210C161 194.536 173.536 182 189 182H274C289.464 182 302 169.464 302 154L302 68Z" fill="#00C896" fill-opacity="0.5"/>
                <path d="M0 83H59C74.464 83 87 95.536 87 111V154C87 169.464 99.536 182 115 182H125C140.464 182 153 194.536 153 210V252C153 267.464 140.464 280 125 280H0V83Z" fill="#00C896" fill-opacity="0.35"/>
            </svg>
            <svg ref="result_svg" v-else width="390" height="516" viewBox="0 0 390 516" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M208 288C223.464 288 236 300.536 236 316L236 516L5.29259e-06 516L1.52588e-05 288L208 288Z" fill="#FF5C5C" fill-opacity="0.75"/>
                <path d="M390 288L390 516L244 516L244 308C244 296.954 252.954 288 264 288L390 288Z" fill="#FF5C5C"/>
                <rect x="95" width="199" height="173" rx="20" fill="#FF5C5C" fill-opacity="0.05"/>
                <path d="M302 68C302 52.536 314.536 40 330 40H390L390 280H189C173.536 280 161 267.464 161 252L161 210C161 194.536 173.536 182 189 182H274C289.464 182 302 169.464 302 154L302 68Z" fill="#FF5C5C" fill-opacity="0.5"/>
                <path d="M0 83H59C74.464 83 87 95.536 87 111V154C87 169.464 99.536 182 115 182H125C140.464 182 153 194.536 153 210V252C153 267.464 140.464 280 125 280H0V83Z" fill="#FF5C5C" fill-opacity="0.35"/>
            </svg>
            <div class="lesson_result_points">
                <div class="lesson_result_points_count" :class="{active: realResult >= 50}">{{ result }}</div>
                <div class="lesson_result_points_text">Баллов</div>
            </div>
            <div class="lesson_result_button">
                <button v-if="isCourseRestart" @click="toLink('course', lesson.course_id)">Вернуться</button>
                <button v-else-if="result < 50" @click="restart()">Начать заново</button>
                <button v-else onclick="window.backByQueryFunction()">Вернуться</button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>