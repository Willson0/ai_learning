<script>
import adminnav from "@/components/adminnav.vue";
import {removeLoading} from "@/assets/admin.js";
import axios from "axios";
import config from "@/config.json"

export default {
    name: "AdminTournaments",
    components: {
        adminnav,
    },
    data() {
        return {
            tournaments: [],
            lessons: [],
            exams: [],
            modalOpened: false,
            editMode: false,
            form: {
                id: null,
                name: "",
                type: "time",
                object_id: 0,
                date_start: "",
                date_end: ""
            }
        };
    },
    mounted () {
        this.fetchData();
    },
    methods: {
        toInputDate(carbonDate) {
            // возвращает только YYYY-MM-DD
            if (!carbonDate) return ''
            // подходит для строк "2024-07-03 00:00:00" или с T и временем
            return carbonDate.slice(0, 10)
        },
        async fetchData () {
            await axios.get(config.backend + 'admin/tournaments', {
                withCredentials: true
            }).then((response) => {
                this.tournaments = response.data.tournaments;
                this.lessons = response.data.lessons;
                this.exams = response.data.exams;
                removeLoading();
            });
        },
        openAdd() {
            this.editMode = false;
            this.form = {
                id: null,
                name: "",
                type: "time",
                object_id: 0,
                date_start: "",
                date_end: ""
            };
            this.modalOpened = true;
        },
        openEdit(t) {
            this.editMode = true;
            this.form = {
                ...t,
                date_start: this.toInputDate(t.date_start),
                date_end: this.toInputDate(t.date_end)
            };
            this.modalOpened = true;
        },
        closeModal() {
            this.modalOpened = false;
        },
        lessonName(id) {
            const lesson = this.lessons.find(l => l.id === id);
            return lesson ? lesson.name : "—";
        },
        formatDate(dateString) {
            const date = new Date(dateString);
            const months = [
                'янв', 'фев', 'мар', 'апр', 'мая', 'июн',
                'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'
            ];

            const day = date.getDate();
            // const month = months[date.getMonth()];
            const month = String(date.getMonth()+1).padStart(2, '0');
            const year = date.getFullYear();
            const hour = String(date.getHours()).padStart(2, '0');
            const minute = String(date.getMinutes()).padStart(2, '0');

            return `${day}.${month}.${year}`
        },
        isPast(t) {
            return new Date(t.date_end) < new Date();
        },
        async saveTournament() {
            if (this.editMode) {
                let data = {};
                for (let key in this.form)
                    if (this.form[key] !== this.tournaments.find(a => a.id === this.form.id)[key]) data[key] = this.form[key];

                await axios.post(config.backend + 'admin/tournaments/' + this.form.id, data, {
                    withCredentials: true,
                }).then((response) => {
                    this.tournaments = response.data.tournaments;
                }).catch((error) => {
                    alert('Ошибка: ' + error.response.data.message);
                });
            } else {
                await axios.post(config.backend + 'admin/tournaments', {...this.form}, {
                    withCredentials: true,
                }).then((response) => {
                    this.tournaments = response.data.tournaments;
                }).catch((error) => {
                    alert('Ошибка: ' + error.response.data.message);
                });
            }
            this.closeModal();
        },
        async deleteTournament(id) {
            if (!confirm(`Вы действительно хотите удалить турнир "${this.tournaments.find(a => a.id === id).name}"?`)) return;
            await axios.delete(config.backend + `admin/tournaments/${id}`, {
                withCredentials: true
            }).then((response) => {
                this.tournaments = response.data.tournaments;
            })
        }
    }
};
</script>

<template>
    <adminnav>
        <div class="admin-tournaments">
            <div class="header">
                <button class="btn-main" @click="openAdd">Добавить турнир</button>
            </div>

            <div class="admin_users_main_main">
                <table class="admin_users_main_main_table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата начала</th>
                        <th>Дата окончания</th>
                        <th>Тип</th>
                        <th>Тема</th>
                        <th>Изменено</th>
                        <th>Создано</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr style="cursor: pointer" @click="openEdit(tournament)" class="admin_products_main_main_table_tr" v-for="tournament in tournaments.sort((a, b) => b.id - a.id)">
                        <th>{{ tournament.id }}</th>
                        <th>{{ tournament.date_start ? formatDate(tournament.date_start) : '-' }}</th>
                        <th>{{ tournament.date_end ? formatDate(tournament.date_end) : '-' }}</th>
                        <th>{{ tournament.type === 'time' ? 'По времени' : 'По уроку' }}</th>
                        <th>{{ tournament.object_id === 0 ? '-' : lessons.find(a => a.id === tournament.object_id)?.title }}</th>
                        <th>{{ tournament.updated_at ? formatDate(tournament.updated_at) : '-' }}</th>
                        <th>{{ tournament.created_at ? formatDate(tournament.created_at) : '-' }}</th>
                        <th style="color:darkred" @click.stop="deleteTournament(tournament.id)">Удалить</th>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

            <!-- Модальное окно для создания/редактирования -->
            <div v-if="modalOpened" class="modal-bg" @click="closeModal">
                <div class="modal" @click.stop>
                    <h3>{{ editMode ? 'Редактировать турнир' : 'Добавить турнир' }}</h3>
                    <div class="form-group">
                        <label>Название</label>
                        <input v-model="form.name" type="text" />
                    </div>
                    <div class="form-group">
                        <label>Тип</label>
                        <select v-model="form.type">
                            <option value="time">По времени</option>
                            <option value="lesson">По уроку</option>
                            <option value="exam">По экзамену</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата старта</label>
                        <input v-model="form.date_start" type="date" />
                    </div>
                    <div class="form-group">
                        <label>Дата окончания</label>
                        <input v-model="form.date_end" type="date" />
                    </div>
                    <div
                        class="form-group"
                        v-if="form.type === 'lesson'"
                    >
                        <label>Урок</label>
                        <select v-model="form.object_id">
                            <option :value="lesson.id" v-for="lesson in lessons" :key="lesson.id">
                                {{ lesson.title }}
                            </option>
                        </select>
                    </div>
                    <div
                        class="form-group"
                        v-if="form.type === 'exam'"
                    >
                        <label>Экзамен</label>
                        <select v-model="form.object_id">
                            <option :value="lesson.id" v-for="lesson in exams" :key="lesson.id">
                                {{ lesson.title }}
                            </option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button class="btn-main" @click="saveTournament">{{ editMode ? 'Сохранить' : 'Создать' }}</button>
                        <button class="btn-cancel" @click="closeModal">Отмена</button>
                    </div>
                </div>
            </div>
    </adminnav>
</template>

<style scoped>
.admin-tournaments {
    background: #12121C;
    color: #fff;
    padding: 32px 24px;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 34px;
}
.btn-main {
    background: #389466;
    color: #fff;
    border: none;
    padding: 12px 26px;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.18s;
}
.btn-main:hover { background: #29754e; }
.btn-edit, .btn-del {
    background: none;
    border: none;
    font-size: 1.3em;
    margin-right: 6px;
    cursor: pointer;
}
.btn-edit { color: #389466; }
.btn-del { color: #cf4646; }
.btn-edit:hover { text-shadow: 0 0 6px #38946688; }
.btn-del:hover { text-shadow: 0 0 12px #cf464677; }
.btn-cancel {
    background: #2f2f41;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    margin-left: 14px;
    cursor: pointer;
}

.list-head, .tournament-row {
    display: grid;
    grid-template-columns: 2.4fr 1.1fr 1.2fr 1.2fr 1.5fr 1.2fr;
    gap: 18px;
    align-items: center;
    height: 45px;
    font-size: 1.03em;
}
.list-head {
    border-bottom: 2px solid #292947;
    text-transform: uppercase;
    color: #6ee4b2;
    background: #181824;
}
.tournament-row {
    margin-bottom: 6px;
    border-bottom: 1px solid #23233a;
    transition: background 0.14s;
}
.tournament-row:hover {
    background: #1b1e2c;
}
.tournament-row.inactive {
    opacity: 0.55;
}
.type-time {
    color: #389466;
    font-weight: 600;
}
.type-lesson {
    color: #6ee4b2;
    font-weight: 600;
}

.modal-bg {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(31, 39, 31, 0.48);
    z-index: 5000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadein 0.14s;
}
@keyframes fadein { from { opacity: 0; } to { opacity: 1; } }
.modal {
    background: #161925;
    border-radius: 14px;
    box-shadow: 0 12px 42px #153c2d44;
    padding: 34px 32px 20px 32px;
    min-width: 340px;
    color: #fff;
}
.modal h3 {
    color: #389466;
    margin: 0 0 18px 0;
}
.form-group {
    margin-bottom: 19px;
    display: flex;
    flex-direction: column;
}
.form-group label {
    color: #6ee4b2;
    margin-bottom: 6px;
    font-size: 1em;
}
input[type=text], input[type=date], select {
    background: #181824;
    border: 1.5px solid #38946655;
    color: #fff;
    padding: 9px 10px;
    border-radius: 7px;
    font-size: 1em;
    outline: none;
}
input[type=text]:focus, input[type=date]:focus, select:focus {
    border-color: #389466;
}
.modal-actions {
    text-align: right;
    margin-top: 10px;
}
</style>
