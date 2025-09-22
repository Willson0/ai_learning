<script>
    import adminnav from '@/components/adminnav.vue';
    import {removeLoading, togglePopup} from "@/assets/admin.js";
    import axios from "axios";
    import config from "@/config.json";
    export default {
        data () {
            return {
                courses: [],
                levels: [],
                courseSearch: "",
                selectedCourse: null,
                selectedLesson: null,
                expandedQuestionIndex: null,
                newFile: null,
                config: config,
            }
        },
        components: {
            adminnav
        },
        async mounted () {
            removeLoading();

            await axios.get (config.backend + "admin/courses", {
                withCredentials: true
            }).then((response) => {
                this.courses = response.data.courses;
                this.levels = response.data.levels;
            })
        },
        methods: {
            deleteCourse() {
                if (!this.selectedCourse) alert("Выберите курс!");
                if (!this.selectedCourse.id) {
                    const index = this.courses.findLastIndex(a => a.title === this.selectedCourse.title);
                    if (index !== -1) {
                        this.courses.splice(index, 1);
                    }
                    return this.selectedCourse = null;
                }

                if (!confirm("Вы уверены, что хотите удалить курс?")) return;

                axios.delete(config.backend + "admin/courses/" + this.selectedCourse.id, {
                    withCredentials: true
                }).then((response) => {
                    alert('Курс удален');
                    this.selectedCourse = null;
                    this.courses = response.data.courses;
                });
            },
            formatDateUTC(dateStr) {
                if (!dateStr) return "Не создано"
                let date = new Date(dateStr);
                return `${date.getDate()}.${date.getMonth() + 1}.${date.getFullYear()}`;
            },
            selectLesson(id) {
                togglePopup('courses_overlay');
                this.selectedLesson = {...this.selectedCourse.lessons.find(a => a.id === id)};
                this.selectedLesson.isExam = this.selectedLesson.count_tries > 0;
                this.selectedLesson.videos = JSON.parse(this.selectedLesson.videos);
                this.selectedLesson.questions = JSON.parse(this.selectedLesson.questions);
            },
            saveCourse() {
                if (this.selectedCourse.id) {
                    let oldCourse = {...this.courses.find(a => a.id === this.selectedCourse.id)};
                    let fd = new FormData();

                    for (let key in this.selectedCourse) {
                        if (this.selectedCourse[key] !== oldCourse[key]) fd.append(key, this.selectedCourse[key]);
                    }
                    axios.post(config.backend + "admin/courses/" + this.selectedCourse.id, fd, {
                        withCredentials: true
                    }).then((response) => {
                        alert('Курс сохранен');
                        this.courses = response.data.courses;
                        this.selectedCourse = null;
                    });
                } else {
                    axios.post(config.backend + "admin/courses", this.selectedCourse, {
                        withCredentials: true
                    }).then((response) => {
                        alert('Курс сохранен');
                        this.courses = response.data.courses;

                        const index = this.courses.findLastIndex(a => a.title === this.selectedCourse.title);
                        if (index !== -1) {
                            this.selectedCourse = this.courses[index];
                        } else this.selectedCourse = null;
                    }).catch((error) => {});
                }
            },
            deleteLesson() {
                if (!this.selectedLesson) return alert("Выберите урок!");
                if (!confirm("Вы уверены, что хотите удалить урок?")) return;

                if (!this.selectedLesson.id) {
                    alert("Урок не сохранен. Удаление невозможно!");
                }

                axios.delete(config.backend + "admin/lessons/" + this.selectedLesson.id, {
                    withCredentials: true
                }).then((response) => {
                    alert('Урок удален');
                    this.selectedLesson = null;
                    this.courses = response.data.courses;
                    this.selectedCourse = {...this.courses.find(a => a.id === this.selectedCourse.id)};
                    this.selectedLesson.isExam = this.selectedLesson.count_tries > 0;
                    togglePopup('courses_overlay');
                })
            },
            saveLesson() {
                if (this.selectedLesson.id) {
                    let oldLesson = {...this.courses.find(a => a.id === this.selectedCourse.id).lessons.find(a => a.id === this.selectedLesson.id)};
                    let fd = new FormData();

                    for (let key in this.selectedLesson) {
                        if (key === 'isExam') {
                            if (this.selectedLesson[key]) fd.append('count_tries', 2);
                            else fd.append('count_tries', 0);
                        }
                        else if (JSON.stringify(this.selectedLesson[key]) !== JSON.stringify(oldLesson[key])) {
                            if (typeof this.selectedLesson[key] === 'string') fd.append(key, this.selectedLesson[key]);
                            else fd.append(key, JSON.stringify(this.selectedLesson[key]));
                        }
                    }
                    if (this.newFile) fd.append('file', this.newFile);

                    axios.post(config.backend + "admin/lessons/" + this.selectedLesson.id, fd, {
                        withCredentials: true
                    }).then((response) => {
                        alert('Курс сохранен');
                        this.courses = response.data.courses;
                        this.selectedCourse = {...this.courses.find(a => a.id === this.selectedCourse.id)};
                        // this.selectedLesson = {...this.courses.find(a => a.id === this.selectedCourse.id).lessons.find(a => a.id === this.selectedLesson.id)};
                        // this.selectedLesson.questions = JSON.parse(this.selectedLesson.questions);
                        // this.selectedLesson.isExam = this.selectedLesson.count_tries > 0;
                        togglePopup('courses_overlay');
                        this.selectedLesson = null;
                    });
                } else {
                    if (this.selectedLesson.isExam) this.selectedLesson.count_tries = 2;
                    if (!this.newFile) return alert("Выберите файл!");

                    let fd = new FormData();
                    for (let key in this.selectedLesson) {
                        if (typeof this.selectedLesson[key] === 'string') fd.append(key, this.selectedLesson[key]);
                        else fd.append(key, JSON.stringify(this.selectedLesson[key]));
                    }
                    fd.append('file', this.newFile);

                    axios.post(config.backend + "admin/lessons", fd, {
                        withCredentials: true
                    }).then((response) => {
                        alert('Курс сохранен');
                        this.courses = response.data.courses;
                        this.selectedCourse = {...this.courses.find(a => a.id === this.selectedCourse.id)};
                        togglePopup('courses_overlay');
                        this.selectedLesson = null;
                    }).catch((error) => {
                        alert (error.response.data.message ?? error.response.data);
                    });
                }
            },
            moveItemInPlace(array, fromIndex, toIndex) {
                if (fromIndex === toIndex) return;
                const item = array.splice(fromIndex, 1)[0];
                array.splice(toIndex, 0, item);
            },
            toggleAnswers(idx) {
                this.expandedQuestionIndex =
                    this.expandedQuestionIndex === idx ? null : idx;
            },
            setCorrectAnswer(questionIdx, answerIdx) {
                this.selectedLesson.questions[questionIdx].right_answer = answerIdx;
            },
            addAnswer(questionIdx) {
                this.selectedLesson.questions[questionIdx].answers.push( "Новый ответ");
                if (
                    typeof this.selectedLesson.questions[questionIdx].right_answer !==
                    "number"
                ) {
                    this.selectedLesson.questions[questionIdx].right_answer = 0;
                }
            },
            deleteAnswer(questionIdx, answerIdx) {
                this.selectedLesson.questions[questionIdx].answers.splice(answerIdx, 1);
                const question = this.selectedLesson.questions[questionIdx];
                if (question.right_answer === answerIdx) {
                    question.right_answer = 0;
                } else if (question.right_answer > answerIdx) {
                    question.right_answer--;
                }
            },
            addQuestion () {
                this.selectedLesson.questions.push({
                    question: "Новый вопрос",
                    answers: ["Новый ответ"],
                    right_answer: 0
                });
            },
            newLesson () {
                if (!this.selectedCourse.id) return alert ("Сначала сохраните курс!");
                this.selectedCourse.lessons.push({
                    title: "Новый урок",
                    description: "Описание",
                    number: this.selectedCourse.lessons.length + 1,
                    count_tries: 0,
                    videos: {"vk": "", "youtube": "", "rutube": ""},
                    questions: [],
                    course_id: this.selectedCourse.id
                });
                alert("Чтобы урок появился, нужно сохранить его в оверлее!")
            },
            newCourse () {
                this.courses.push({
                    title: "Новый курс",
                    description: "Описание",
                    level: 1,
                    required_course: 0,
                    lessons: [],
                });
                alert("Чтобы курс появился, нужно сохранить его!")
            }
        }
    }
</script>

<template>
    <div class="popup courses_overlay">
        <div class="sub-card" v-if="selectedLesson">
            <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:8px">
                <div class="sub-card-title">Детали урока</div>
                <div class="row">
                    <button @click="saveLesson" class="btn" id="deleteLessonBtn" title="Удалить урок">
                        Сохранить
                    </button>
                    <button @click="deleteLesson" class="btn danger" id="deleteLessonBtn" title="Удалить урок">
                        Удалить урок
                    </button>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-row">
                    <label class="label">Заголовок урока</label>
                    <input v-model="selectedLesson.title" class="input" id="l_title" placeholder="Напр., Введение"> </div>
                <div class="form-row">
                    <label class="label">Экзамен?</label>
                    <input v-model="selectedLesson.isExam" type="checkbox" id="l_exam" class="checkbox"> </div>
                <div class="form-row" style="grid-column:1/-1">
                    <label class="label">Описание</label>
                    <textarea v-model="selectedLesson.description" id="l_desc" placeholder="Описание содержания урока"></textarea>
                </div>
                <div class="form-row">
                    <label class="label">VK Video URL</label>
                    <input v-model="selectedLesson.videos.vk" class="input" id="l_vk" placeholder="https://vk.com/video/..."> </div>
                <div class="form-row">
                    <label class="label">File: <a style="text-decoration: underline" v-if="selectedLesson.file" :href="config.storage + selectedLesson.file">нынешний</a></label>
                    <input @change="newFile = $event.target.files[0]" class="input" type="file" id="l_rutube" placeholder="https://rutube.ru/video/..."> </div>
                <div class="form-row" >
                    <label class="label">YouTube URL</label>
                    <input v-model="selectedLesson.videos.youtube" class="input" id="l_youtube" placeholder="https://youtu.be/..."> </div>
                <div class="form-row">
                    <label class="label">Номер урока (в курсе)</label>
                    <input v-model="selectedLesson.number" type="number" class="input" id="l_youtube" placeholder="1"> </div>
            </div>
            <div class="divider"></div>
            <div class="row" style="justify-content:space-between">
                <div class="sub-card-title">Вопросы</div>
                <div class="row">
                    <button @click="addQuestion" class="btn" id="addQuestionBtn">
                        <svg class="icon-16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        </svg> Добавить вопрос </button>
                </div>
            </div>
            <div id="questionsList" class="list">
                <template v-for="(question, key) in selectedLesson.questions">
                    <div class="question-item">
                        <i @click="moveItemInPlace(selectedLesson.questions, key, key-1)" class="fa-solid fa-chevron-up" v-if="key !== 0"></i>
                        <i @click="moveItemInPlace(selectedLesson.questions, key, key+1)" class="fa-solid fa-chevron-down" v-if="key !== selectedLesson.questions.length - 1"></i>
                        <input v-model="question.question" class="question-title">
                        <button
                            class="show-answers-btn" style="margin-left: auto"
                            @click="toggleAnswers(key)">
                            Ответы
                        </button>
                        <button
                            class="show-answers-btn" style="background-color: red; margin-left: 0;"
                            @click="selectedLesson.questions.splice(key, 1);">
                            Удалить
                        </button>
                    </div>
                    <div v-if="expandedQuestionIndex === key" class="answers-menu">
                        <div v-for="(answer, answerIdx) in selectedLesson.questions[expandedQuestionIndex].answers" class="answer-item">
                            <label>
                                <input
                                    type="radio"
                                    :name="'question' + expandedQuestionIndex"
                                    :checked="selectedLesson.questions[expandedQuestionIndex].right_answer === answerIdx"
                                    @change="setCorrectAnswer(expandedQuestionIndex, answerIdx)"
                                />
                                <input
                                    v-model="selectedLesson.questions[expandedQuestionIndex].answers[answerIdx]"
                                    class="edit-answer-input"
                                    type="text"
                                />
                            </label>
                            <button class="delete-btn" @click="deleteAnswer(expandedQuestionIndex, answerIdx)">
                                Удалить
                            </button>
                        </div>
                        <button class="add-btn" @click="addAnswer(expandedQuestionIndex)">Добавить ответ</button>
                    </div>
                </template>
            </div>
        </div>
    </div>
    <adminnav>
        <div class="admin_courses">
            <div class="container">
                <div class="grid">
                    <!-- Sidebar: Course list -->
                    <div class="panel" id="coursesPanel">
                        <div class="panel-header">
                            <div class="section-title">Курсы</div>
                            <div class="row">
                                <input v-model="courseSearch" id="courseSearch" class="input" placeholder="Поиск..." style="width:140px">
                                <button @click="newCourse" class="btn primary" id="addCourseBtn">
                                    <svg class="icon-16" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                    </svg> Новый </button>
                            </div>
                        </div>
                        <div class="panel-body" style="padding-top:10px">
                            <div class="course-list" id="courseList">
                                <div @click="selectedCourse = {...course}" class="course-item" v-for="course in courses.filter(a => a.title.toLowerCase().trim().includes(courseSearch.toLowerCase().trim()))" :key="course.id">
                                    <div class="course-title">{{ course.title }}</div>
                                    <div class="course-meta"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Main area -->
                    <div class="panel" v-if="selectedCourse">
                        <div class="panel-header">
                            <div class="section-title">Детали курса</div>
                            <button @click="deleteCourse" class="btn danger" id="deleteCourseBtn" title="Удалить курс">
                                Удалить
                            </button>
                        </div>
                        <div class="panel-body">
                            <div id="courseEditor" style="display:grid; gap:14px">
                                <div class="form-grid">
                                    <div class="form-row">
                                        <label class="label">ID (неизменяемо)</label>
                                        <input class="input readonly" id="c_id" readonly v-model="selectedCourse.id"> </div>
                                    <div class="form-row">
                                        <label class="label">Уровень</label>
                                        <select id="c_level" v-model="selectedCourse.level">
                                            <option :value="1">{{ levels[1] }}</option>
                                            <option :value="2">{{ levels[2] }}</option>
                                            <option :value="3">{{ levels[3] }}</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label class="label">Заголовок</label>
                                        <input class="input" id="c_title" v-model="selectedCourse.title" placeholder="Напр., Основы криптовалюты"> </div>
                                    <div class="form-row">
                                        <label class="label">Обязательный курс (ID)</label>
                                        <select id="c_prereq" v-model="selectedCourse.required_course">
                                            <option value="0">Не нужен</option>
                                            <option v-for="course in courses" :value="course.id">({{course.id}}) {{ course.title }}</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label class="label">Описание</label>
                                        <textarea id="c_desc" v-model="selectedCourse.description" placeholder="Описание курса"></textarea>
                                    </div>
                                    <div class="form-row">
                                        <label class="label">Создан</label>
                                        <input :value="formatDateUTC(selectedCourse.created_at)" class="input readonly" id="c_created" readonly> </div>
                                    <div class="form-row">
                                        <label class="label">Изменён</label>
                                        <input :value="formatDateUTC(selectedCourse.updated_at)" class="input readonly" id="c_updated" readonly> </div>
                                    <button @click="saveCourse" style="text-align:center; display: block !important" class="btn" id="deleteCourseBtn" title="Удалить курс">
                                        {{ selectedCourse.id ? "Сохранить" : "Создать" }}
                                    </button>
                                </div>

                                <div class="divider"></div>
                                <div class="row" style="justify-content:space-between">
                                    <div class="section-title">Уроки</div>
                                    <div class="row"> <span class="pill" id="lessonCount">{{ selectedCourse.lessons.length }} уроков</span>
                                        <button @click="newLesson" class="btn" id="addLessonBtn">
                                            <svg class="icon-16" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                            </svg> Новый урок </button>
                                    </div>
                                </div>
                                <div class="list" id="lessonsList"></div>
                                <div class="lessonEditorWrap" id="lessonEditorWrap">
                                    <div @click="selectLesson(lesson.id)" v-for="(lesson, key) in selectedCourse.lessons">
                                        <div class="course-title">{{ lesson.title }}</div>
                                        <div class="course-meta"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    <!-- Toasts -->
        <div class="toasts" id="toasts"></div>
        <!-- Modal -->
        <div class="modal-backdrop" id="modalBackdrop" role="dialog" aria-modal="true">
            <div class="modal">
                <div class="modal-header" id="modalTitle">Подтверждение</div>
                <div class="modal-body" id="modalBody"></div>
                <div class="modal-actions">
                    <button class="btn" id="modalCancel">Отмена</button>
                    <button class="btn danger" id="modalOk">Удалить</button>
                </div>
            </div>
        </div>
        </div>
    </adminnav>
</template>

<style scoped>

.admin_courses {
    display: grid;
    grid-template-rows: auto 1fr;
    min-height: 100vh;
}

header {
    position: sticky;
    top: 0;
    z-index: 10;
    background: linear-gradient(180deg, #141420, transparent);
    padding: 16px 22px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 16px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
    font-size: 18px;
    letter-spacing: .3px;
}

.brand .logo {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary), #7aa7ff);
    box-shadow: 0 6px 18px rgba(59, 130, 246, .35), inset 0 0 12px rgba(255, 255, 255, .2);
    display: grid;
    place-items: center;
    color: white;
    font-size: 14px;
}

.subtle {
    color: var(--muted);
    font-weight: 500
}

.container {
    padding: 16px 22px 28px 22px;
    max-width: unset !important;
}

.grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 18px;
    align-items: start;
}

@media (max-width: 1000px) {
    .grid {
        grid-template-columns: 1fr
    }
}
/* Panels / Cards */

.panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--card-radius);
    box-shadow: var(--shadow-soft);
}

.panel-header {
    padding: 14px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border-radius: var(--card-radius);
}

.panel-body {
    padding: 14px
}

.section-title {
    font-weight: 700;
    letter-spacing: .2px
}

.muted {
    color: var(--muted)
}
/* Course list */

.course-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px
}

.course-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
    align-items: center;
    padding: 12px;
    border: 1px solid var(--border);
    background: var(--surface-2);
    border-radius: 10px;
    cursor: pointer;
    transition: transform .08s ease, border-color .15s ease, background .15s ease;
}

.course-item:hover {
    border-color: #2f3450;
    background: #212434
}

.course-item.active {
    outline: 2px solid rgba(59, 130, 246, .55);
    background: #1a2341
}

.course-title {
    font-weight: 600
}

.course-meta {
    font-size: 12px;
    color: var(--muted)
}

.pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: #202338;
    color: var(--muted);
    font-size: 12px
}

.tag {
    display: inline-block;
    background: #26304e;
    border: 1px solid #314066;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: 11px;
    color: #a5b4fc
}

.item-actions {
    display: flex;
    gap: 6px
}
/* Buttons */

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: var(--btn-radius);
    background: #1c1e2c;
    color: var(--text);
    cursor: pointer;
    font-weight: 600;
    transition: transform .06s ease, background .15s ease, border-color .15s ease;
}

.btn:hover {
    background: #23263a
}

.btn:active {
    transform: translateY(1px)
}

.btn.primary {
    background: linear-gradient(180deg, var(--primary), var(--primary-2));
    border-color: rgba(255, 255, 255, .12)
}

.btn.primary:hover {
    filter: brightness(1.05)
}

.btn.ghost {
    background: transparent
}

.btn.danger {
    background: linear-gradient(180deg, #ef4444, #b91c1c);
    border-color: rgba(0, 0, 0, .2)
}

.btn.icon {
    padding: 8px;
    border-radius: 10px
}

.icon-12 {
    width: 12px;
    height: 12px
}

.icon-14 {
    width: 14px;
    height: 14px
}

.icon-16 {
    width: 16px;
    height: 16px
}

.icon-18 {
    width: 18px;
    height: 18px
}
/* Form */

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px
}

.form-grid-1 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px
}

.form-row {
    display: flex;
    flex-direction: column;
    gap: 6px
}

.label {
    font-size: 12px;
    color: #b7bdd2
}

.input,
select,
textarea {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: #121527;
    color: var(--text);
    outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
    font-size: 14px
}

textarea {
    resize: vertical;
    min-height: 80px
}

.input:focus,
select:focus,
textarea:focus {
    border-color: #3d4a78;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, .18)
}

.readonly {
    background: #14172a;
    color: #adb0c3
}

.row {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap
}

.divider {
    height: 1px;
    background: var(--border);
    margin: 10px 0
}
/* Lessons list */

.list {
    display: flex;
    flex-direction: column;
    gap: 8px
}

.lesson-item,
.question-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 8px;
    align-items: center;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 10px
}

.drag {
    cursor: grab;
    color: #98a2c7;
    display: grid;
    place-items: center;
    padding: 4px
}

.drag:active {
    cursor: grabbing
}

.badge {
    font-size: 11px;
    color: #a0aec0;
    background: #1e243a;
    border: 1px solid #303955;
    border-radius: 999px;
    padding: 2px 8px
}

.lesson-item.active {
    outline: 2px solid rgba(59, 130, 246, .45);
    background: #1b2442
}

.grow {
    flex: 1
}
/* Lesson editor */

.sub-card {
    background: linear-gradient(180deg, #171a2a, #151827);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px
}

.sub-card-title {
    font-weight: 700;
    margin-bottom: 8px
}
/* Questions */

.question-item {
    grid-template-columns: auto 1fr auto
}

.answer-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 8px;
    align-items: center;
    background: #111425;
    border: 1px dashed #2b3250;
    border-radius: 10px;
    padding: 8px
}

.answers {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px
}

.q-head {
    display: flex;
    align-items: center;
    gap: 8px
}

.checkbox {
    width: 18px;
    height: 18px
}
/* Chips for empty states */

.empty {
    padding: 20px;
    border: 1px dashed #2a3150;
    border-radius: 12px;
    color: #9aa5c5;
    background: #14182c;
    text-align: center
}
/* Toasts */

.toasts {
    position: fixed;
    right: 16px;
    bottom: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 9999
}

.toast {
    background: #0e1326;
    border: 1px solid #2b365d;
    padding: 10px 12px;
    border-radius: 10px;
    box-shadow: var(--shadow-soft);
    display: flex;
    align-items: center;
    gap: 10px
}

.toast.success {
    border-color: #115e4b;
    background: #0b1816
}

.toast.warn {
    border-color: #5e4a11;
    background: #18140b
}

.toast.danger {
    border-color: #6b1c1c;
    background: #190e0e
}
/* Modal */

.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99
}

.modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: var(--shadow);
    max-width: 520px;
    width: 92%
}

.modal-header {
    padding: 14px;
    border-bottom: 1px solid var(--border);
    font-weight: 700
}

.modal-body {
    padding: 14px
}

.modal-actions {
    padding: 14px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 8px
}
/* Scrollbars */

::-webkit-scrollbar {
    height: 10px;
    width: 10px
}

::-webkit-scrollbar-thumb {
    background: #2a2f4b;
    border-radius: 10px
}
</style>