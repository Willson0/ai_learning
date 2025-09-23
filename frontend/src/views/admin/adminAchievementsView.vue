<script>
import adminnav from '@/components/adminnav.vue'
import axios from 'axios';
import config from '@/config.json';
import {removeLoading} from "@/assets/admin.js";
export default {
    name: 'AchievementTable',
    components: {
        adminnav
    },
    mounted () {
        this.fetchData();
    },
    data() {
        return {
            achievements: [],
            editIndex: null,
            editForm: {
                image: '',
                name: "",
                description: "",
                parameter: "",
                value: 0,
            },
            newAchievement: {
                image: null,    // Сохраняем сам файл
                preview: '',    // Сюда положим base64-картинку для превью
                name: "",
                description: "",
                parameter: "mistake_count",
                value: 0,
            },
            config: config,
            options: {
                "mistake_count": "совершенных ошибок",
                "repass_count": "перепрохождений уроков",
                "pass_count": "пройденных уроков",
                "exam_without_mistakes": "экзаменов без ошибок",
                "uses_hints": "использованных подсказок"
            }
        };
    },
    methods: {
        async fetchData() {
            await axios.get(config.backend + 'admin/achievements', {
                withCredentials: true
            }).then((response) => {
                this.achievements = response.data;
                removeLoading();
            }).catch((error) => {
                alert(error.response.data.message || error.message || error);
            });
        },
        isEditing(idx) {
            return this.editIndex === idx;
        },
        startEdit(idx, achievement) {
            this.editIndex = idx;
            this.editForm = { ...achievement };
        },
        cancelEdit() {
            this.editIndex = null;
            this.editForm = { image: '', progress: 0 };
        },
        async saveEdit(idx) {
            let fd = new FormData();
            if (this.editForm.file) fd.append('image', this.editForm.file);
            fd.append('name', this.editForm.name);
            fd.append('description', this.editForm.description);
            fd.append('parameter', this.editForm.parameter);
            fd.append('value', this.editForm.value);

            await axios.post(config.backend + 'admin/achievements/' + idx, fd, {withCredentials: true}).then((response) => {
                this.achievements = response.data;
                this.cancelEdit();
            });
        },
        async removeAchievement(idx) {
            if(confirm('Удалить достижение?')) {
                await axios.delete(config.backend + 'admin/achievements/' + idx, {
                    withCredentials: true
                }).then((response) => {
                    this.achievements = response.data;
                });
            }
        },
        async onFileChange(e) {
            let file = e.target.files[0];
            if (!file) return;
            this.$refs.imgInp.value = '';

            this.editForm.file = file;

            let reader = new FileReader();
            reader.onload = e => this.editForm.image = e.target.result;
            reader.readAsDataURL(file);
        },
        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) {
                this.newAchievement.image = null;
                this.newAchievement.preview = '';
                return;
            }
            this.newAchievement.image = file;
            // Показываем превью (base64)
            const reader = new FileReader();
            reader.onload = (e) => {
                this.newAchievement.preview = e.target.result;
            };
            reader.readAsDataURL(file);
        },
        async addAchievement() {
            if (!this.newAchievement.image || this.newAchievement.progress < 0 || this.newAchievement.progress > 100) {
                alert('Выберите изображение и укажите прогресс (0-100)!');
                return;
            }

            let fd = new FormData();
            fd.append('image', this.newAchievement.image);
            fd.append('name', this.newAchievement.name);
            fd.append('description', this.newAchievement.description);
            fd.append('parameter', this.newAchievement.parameter);
            fd.append('value', this.newAchievement.value);

            await axios.post(config.backend + 'admin/achievements', fd, {withCredentials: true}).then((response) => {
                this.achievements = response.data;

                this.newAchievement.image = null;
                this.newAchievement.preview = '';
                this.newAchievement.name = "";
                this.newAchievement.description = "";
                this.newAchievement.parameter = "";
                this.newAchievement.value = 0;
            });
        }
    }
};
</script>

<template>
    <adminnav>
        <div class="add-form">
            <input
                type="file"
                accept="image/*"
                class="file-input"
                @change="onFileSelected"
            />
            <img v-if="newAchievement.preview" :src="newAchievement.preview" class="achievement-image preview" alt="Превью" />
            <input
                v-model="newAchievement.name"
                class="edit-input short"
                type="text"
                placeholder="Заголовок" style="width: 150px;"
            />
            <input
                v-model="newAchievement.description"
                class="edit-input short"
                type="text"
                placeholder="Описание" style="width: 150px;"
            />
            <input
                v-model="newAchievement.value"
                class="edit-input short"
                type="number"
                placeholder="Прогресс, уроков"
            />
            <select v-model="newAchievement.parameter" name="" id="">
                <option :value="key" v-for="(opt, key) in options">{{opt}}</option>
            </select>
            <button class="save-btn" @click="addAchievement">Добавить</button>
        </div>
        <table class="achievement-table">
            <thead>
            <tr>
                <th>Изображение</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Требование</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(achievement, idx) in achievements" :key="achievement.id" :class="{ editing: editIndex === idx }">
                <td>
                    <img v-if="!isEditing(idx)" :src="config.storage + achievement.image" class="achievement-image" alt="Achievement"/>
                    <input
                        v-else
                        type="file"
                        accept="image/*"
                        class="file-input"
                        @change="onFileChange"
                        ref="imgInp"
                    />
                </td>
                <td>
            <span v-if="!isEditing(idx)">
              {{ achievement.name }}
            </span>
                    <input
                        v-else
                        v-model="editForm.name"
                        type="text"
                        class="edit-input short"
                        placeholder="Название"
                        style="width: 150px;"
                    />
                </td>
                <td>
                    <span v-if="!isEditing(idx)">
              {{ achievement.description }}
            </span>
                    <input
                        v-else
                        v-model="editForm.description"
                        type="text"
                        class="edit-input short"
                        placeholder="Описание"
                        style="width: 150px;"
                    />
                </td>
                <td>
            <span v-if="!isEditing(idx)">
              {{ achievement.value }} {{ options[achievement.parameter] }}
            </span>
                    <template v-else>
                        <input
                            v-model.number="editForm.value"
                            type="number"
                            min="0"
                            class="edit-input short"
                            placeholder="Progress"
                        />
                        <select v-model="newAchievement.parameter" name="" id="">
                            <option :value="key" v-for="(opt, key) in options">{{opt}}</option>
                        </select>
                    </template>
                </td>
                <td>
                    <div v-if="!isEditing(idx)" class="action-buttons">
                        <button class="edit-btn" @click="startEdit(idx, achievement)">✏️</button>
                        <button class="del-btn" @click="removeAchievement(achievement.id)">🗑️</button>
                    </div>
                    <div v-else class="action-buttons">
                        <button class="save-btn" @click="saveEdit(achievement.id)">Сохранить</button>
                        <button class="cancel-btn" @click="cancelEdit">Отмена</button>
                    </div>
                </td>
            </tr>
            <tr v-if="achievements.length === 0">
                <td colspan="3" class="empty-row">Ачивок пока нет</td>
            </tr>
            </tbody>
        </table>
    </adminnav>
</template>

<style scoped>
.achievement-table-wrapper {
    background: #12121C;
    color: #fff;
    padding: 36px 30px;
    border-radius: 18px;
    min-height: 340px;
    box-shadow: 0 2px 12px 0 #0005;
    max-width: 800px;
    margin: 36px auto;
}
.table-title {
    margin-bottom: 28px;
    font-size: 2rem;
    letter-spacing: 0.06em;
    color: #389466;
    font-weight: 600;
    text-align: left;
}
.achievement-table {
    width: 100%;
    border-collapse: collapse;
    background: #1a1a28;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 2px 0 #0004;
}
.achievement-table th, .achievement-table td {
    padding: 16px 14px;
    text-align: left;
    border-bottom: 1px solid #22222F;
    font-size: 1.02rem;
}
.achievement-table th {
    background: #151521;
    font-weight: 700;
    color: #58cc98;
    letter-spacing: 0.045em;
    text-transform: uppercase;
}
.achievement-table tr:last-child td {
    border-bottom: none;
}
.achievement-table tr.editing {
    background: #181824;
}
.achievement-image {
    height: 100px;
    width: 100px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #389466;
    background: #232336;
}
.progress-bar-bg {
    width: 125px;
    height: 16px;
    background: #252535;
    border-radius: 8px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
    margin-right: 10px;
    box-shadow: 0 1px 1px #0002 inset;
}
.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #389466 0%, #55e6b2 100%);
    transition: width 0.4s cubic-bezier(.4,2,.55,.44);
    border-radius: 8px 0 0 8px;
}
.action-buttons {
    display: flex;
    gap: 8px;
}
.edit-btn, .del-btn, .save-btn, .cancel-btn {
    background: #212127;
    border: none;
    color: #389466;
    padding: 8px 10px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s, color 0.2s;
    outline: none;
}
.edit-btn:hover, .save-btn:hover {
    background: #389466;
    color: #12121C;
}
.del-btn {
    color: #ff5961;
}
.del-btn:hover {
    background: #ff5961;
    color: #fff;
}
.cancel-btn {
    color: #c1c1c8;
}
.cancel-btn:hover {
    background: #222;
    color: #ff5961;
}
.edit-input {
    background: #232336;
    border: 1px solid #389466;
    color: #fff;
    border-radius: 7px;
    padding: 7px 10px;
    font-size: 1rem;
    width: 210px;
    box-sizing: border-box;
    transition: border 0.2s;
    outline: none;
}
.edit-input:focus {
    border: 1.5px solid #55e6b2;
}
.edit-input.short {
    width: 90px;
}
.empty-row {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 28px 0;
}
@media (max-width:600px) {
    .achievement-table-wrapper { padding:12px; }
    .table-title { font-size:1.23rem; }
    .achievement-image { height:32px; width:32px;}
    .progress-bar-bg { width:85px; }
}
.add-form {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    align-items: center;
}
.add-form .edit-input {
    margin: 0;
}
.add-form .save-btn {
    font-size: 1rem;
    padding: 8px 16px;
}
select option {
    color: grey;
}
.file-input {
    background: #232336;
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 7px 8px;
    cursor: pointer;
    font-size: 1rem;
    width: 210px;
    margin-right: 7px;
}
.achievement-image.preview {
    border: 1.5px solid #389466;
    width: 42px;
    height: 42px;
    margin-right: 6px;
}


</style>
