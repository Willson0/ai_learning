<script>
import adminnav from "@/components/adminnav.vue";
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import axios from "axios";
import config from "@/config.json";
import {notify} from "@/utils.js";

import { Editor, EditorContent } from '@tiptap/vue-3'
import Document from '@tiptap/extension-document'
import Paragraph from '@tiptap/extension-paragraph'
import Text from '@tiptap/extension-text'
import Bold from '@tiptap/extension-bold'
import Italic from '@tiptap/extension-italic'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import {getFileType} from "@/assets/admin.js";

export default {
    name: "adminMailingView",
    components: {adminnav, VueDatePicker, EditorContent},
    data () {
        return {
            styleTag: null,
            posts: [],

            photo: "",
            confirming: false,
            date: new Date(),
            time: {hours:0, minutes:0, seconds:0},
            dateTime: "",
            repeat: 0,
            not_repeat: "count",
            not_repeat_date: "",
            repeat_time: "",
            count: "",
            editedPost: {},
            timeKey: 0,
            newPostText: "",
            
            config: config,

            editor: null,
            editEditor: null,
            attachments: [],
        }
    },
    async mounted () {
        this.editor = new Editor({
            extensions: [
                Document,
                Paragraph,
                Text,
                Bold,
                Italic,
                Underline,
                Link.configure({ openOnClick: false }),
            ],
            content: '',
        });
        this.editEditor = new Editor({
            extensions: [
                Document,
                Paragraph,
                Text,
                Bold,
                Italic,
                Underline,
                Link.configure({ openOnClick: false }),
            ],
            content: '',
        });

        this.styleTag = document.createElement('link');
        this.styleTag.rel = 'stylesheet';
        this.styleTag.href = new URL('@/assets/mailing.css', import.meta.url).href;
        document.head.appendChild(this.styleTag);

        document.querySelectorAll('textarea').forEach(t => {
            t.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        axios.defaults.withCredentials = true;
        await axios.get(config.backend + "admin/mailing").then((response) => {
            this.posts = response.data;
        });
    },
    unmounted () {
        if (this.styleTag && this.styleTag.parentNode) this.styleTag.parentNode.removeChild(this.styleTag);
        this.styleTag.remove();
    },
    computed: {
        html() {
            return this.editor ? this.editor.getHTML()
                .replace(/<p>/g, '')
                .replace(/<\/p>/g, '\n') : ''
        },
        editHTML () {
            return this.editEditor ? this.editEditor.getHTML()
                .replace(/<p>/g, '')
                .replace(/<\/p>/g, '\n') : ''
        }
    },
    methods: {
        getFileType,
        toggleBold() {
            this.editor.chain().focus().toggleBold().run()
        },
        toggleItalic() {
            this.editor.chain().focus().toggleItalic().run()
        },
        toggleUnderline() {
            this.editor.chain().focus().toggleUnderline().run()
        },
        setLink() {
            const url = window.prompt('Введите ссылку')
            if (url) {
                this.editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
            }
        },
        toggleBoldEdit() {
            this.editEditor.chain().focus().toggleBold().run()
        },
        toggleItalicEdit() {
            this.editEditor.chain().focus().toggleItalic().run()
        },
        toggleUnderlineEdit() {
            this.editEditor.chain().focus().toggleUnderline().run()
        },
        setLinkEdit() {
            const url = window.prompt('Введите ссылку')
            if (url) {
                this.editEditor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
            }
        },

        notify,
        showpopup (cl) {
            document.body.style.overflow = "hidden";
            document.querySelector('.popup.' + cl).classList.add('active');
        },
        hidepopup(cl) {
            document.querySelectorAll('.popup').forEach(el => el.classList.remove('active'));
            document.body.style.overflow = "";
        },
        ondragover(ev) {
            ev.preventDefault();
        },
        drop (ev) {
            ev.preventDefault();
            const files = ev.dataTransfer.files;

            this.photo = files[0];
            document.querySelectorAll(".newPost_image>img").forEach((el) => el.src = URL.createObjectURL(files[0]));
        },
        addimg (ev) {
            let files = ev.target.files;
            for (let file of files) {
                this.attachments.push(file);
            }
            this.$refs.photoInput.value = "";
        },
        checkpost () {
            let body = this.html;
            if (body.length < 10) return this.notify("Количество символов в теле поста должно быть больше 10!", 1)

            this.notify ("Пост успешно сохранен! Выберите время публикации.")
            this.hidepopup();
            this.showpopup('schedule')
        },
        async checkedit () {
            document.querySelector(".popup.edit_post .selectDate_publishDate_calendar").style.border = "";
            document.querySelector(".popup.edit_post .selectDate_repeat_main_title .dp__main input").style.border = "";
            document.querySelector(".popup.edit_post .selectDate_repeat_interval>input").style.border = "";
            
            let body = this.editHTML;
            if (body.length < 10) return this.notify("Количество символов в теле поста должно быть больше 10!", 1);

            if (!this.date) {
                document.querySelector(".popup.edit_post .selectDate_publishDate_calendar").style.border = "2px #f44336 solid";
                return this.notify("Выберите дату публикации!", 1);
            }
            if (this.repeat) {
                if (this.not_repeat === "date" && !this.editedPost.end_date) {
                    document.querySelector(".popup.edit_post .selectDate_repeat_main_title .dp__main input").style.border = "1px #f44336 solid"
                    return this.notify("Выберите дату окончания!", 1);
                }
                if (!this.editedPost.time_repeat) this.editedPost.time_repeat = 30;
                if (this.editedPost.time_repeat < 30) {
                    document.querySelector(".popup.edit_post .selectDate_repeat_interval>input").style.border = "1px #f44336 solid";
                    return this.notify(`Время повтора не может быть меньше ${30} минуты!`, 1);
                }
                if (this.not_repeat === "count" && this.editedPost.end_count < 1) this.editedPost.end_count = 1;
            }

            let datetime = new Date(this.date);
            datetime.setHours(this.time.hours + 3);
            datetime.setMinutes(this.time.minutes);


            if (this.not_repeat === "date") {
                this.editedPost.end_date = new Date(this.editedPost.end_date);
                this.editedPost.end_date.setHours(this.editedPost.end_date.getHours() + 3);
                this.editedPost.end_date = this.editedPost.end_date.toISOString();
            }

            let original = this.posts.filter(el => el.id === this.editedPost.id)[0];
            if (!original) return this.notify("401. Ошибка авторизации", 1);
            

            let formdata = new FormData();
            for (let key in this.editedPost) {
                console.log(key);
                if (key === "attachment") {
                    if (!this.editedPost[key] && this.photo) formdata.append("attachment", this.photo);
                    if (!this.editedPost[key] && !this.photo) formdata.append("attachment", null);
                }
                else if (key === "date") formdata.append("date", datetime.toISOString());
                else if (key === "time_repeat" && !this.repeat) formdata.append("time_repeat", null);
                else if (key === "end_date" && !this.repeat) formdata.append("end_date", null);
                else if (key === "end_count" && !this.repeat) formdata.append("end_count", null);
                else if (key === "text") formdata.append("text", this.editHTML);
                else if (this.editedPost[key] !== original[key]) formdata.append(key, this.editedPost[key]);
            }
            for (let pair of formdata.entries()) {
                console.log(pair[0] + ": " + pair[1]);
            }

            await axios.post(config.backend + "admin/mailing/" + this.editedPost.id, formdata).then((response) => {
                if (response.data["error"]) return notify (response.data["error"], 1);

                notify(`Пост №${this.editedPost.id} успешно обновлен!`);
                axios.get(config.backend + "admin/mailing").then((response) => {
                    this.posts = response.data;
                    this.hidepopup();
                })
            }).catch((response) => {
                notify(`Непредвиденная ошибка! ${response}`, 1);
            })
        },
        saveSchedule () {
            document.querySelector(".selectDate_publishDate_calendar").style.border = "";
            document.querySelector(".selectDate_repeat_main_title .dp__main input").style.border = "";
            document.querySelector(".selectDate_repeat_interval>input").style.border = "";

            if (!this.date) {
                document.querySelector(".selectDate_publishDate_calendar").style.border = "2px #f44336 solid";
                return this.notify("Выберите дату публикации!", 1);
            }
            if (this.repeat) {
                if (this.not_repeat === "date" && !this.not_repeat_date) {
                    document.querySelector(".selectDate_repeat_main_title .dp__main input").style.border = "1px #f44336 solid"
                    return this.notify("Выберите дату окончания!", 1);
                }
                if (!this.repeat_time) this.repeat_time = 30;
                if (this.repeat_time < 30) {
                    document.querySelector(".selectDate_repeat_interval>input").style.border = "1px #f44336 solid";
                    return this.notify(`Время повтора не может быть меньше ${30} минуты!`, 1);
                }
                if (this.not_repeat === "count" && this.count < 1) this.count = 1;
            }

            let datetime = new Date(this.date);
            datetime.setHours(this.time.hours + 3);
            datetime.setMinutes(this.time.minutes);

            let notDate = 0;
            if (this.not_repeat === "date") {
                notDate = new Date(this.not_repeat_date);
                notDate.setHours(notDate.getHours() + 3);
            }

            let data = new FormData();
            data.append("text", this.html);
            if (this.attachments.length > 0)
                for (let img of this.attachments) data.append("attachments[]", img);
            if (this.date) data.append("date", datetime.toISOString());
            if (this.repeat) {
                data.append("time_repeat", this.repeat_time);
                if (this.not_repeat === "date") data.append("end_date", notDate.toISOString());
                else if (this.not_repeat === "count") data.append("end_count", this.count);
            }

            axios.post(config.backend + "admin/mailing", data).then((response) => {
                if (response.data["error"]) return notify (response.data["error"], 1);
                notify("Новый пост успешно добавлен!");

                this.hidepopup();
                axios.get(config.backend + "admin/mailing").then((response) => {
                    this.posts = response.data;
                });
                this.editor.commands.setContent('');
            }).catch((response) => {
                notify(`Произошла ошибка в ходе сохранения поста! ${response}`, 1);
            })
        },
        updateEdit (post) {
            this.editedPost = { ...post };
            this.editEditor.commands.setContent('<p>' + this.editedPost.text.replace(/(\n)/g, "</p><p>") + '</p>');

            this.photo = null;
            this.showpopup('edit_post');

            let datetime = new Date(post.date + "+03:00");
            this.date = new Date(datetime);
            this.date.setHours(23);
            this.date.setMinutes(59);

            this.time = {hours:datetime.getHours(), minutes:datetime.getMinutes(), seconds:0};
            this.timeKey += 1;

            if (post.time_repeat) this.repeat = true;
            if (post.end_count) this.not_repeat = "count";
            else if (post.end_date) this.not_repeat = "date";
        },
        removeAttachment (id) {
            this.attachments = this.attachments.filter((a, idx) => idx !== id);
        },
        createURL (file) {
            return URL.createObjectURL(file);
        }
    },
}
</script>

<template>
    <div class="popup_notification_container"></div>
    <div class="popup schedule">
        <div>
            <div class="selectDate">
                <div class="selectDate_title">Расписание</div>
                <div class="selectDate_publishDate">
                    <div class="selectDate_publishDate_title">Выберите дату публикации</div>
                    <div class="selectDate_publishDate_calendar">
                        <VueDatePicker dark v-model="date" :min-date="new Date()" inline auto-apply :enable-time-picker="false"/>
                        <VueDatePicker dark v-model="time" inline auto-apply :time-picker="true" :enable-time-picker="true" :enable-seconds="false"/>
                    </div>
                </div>
                <div class="selectDate_repeat">
                    <div class="selectDate_repeat_header">
                        <div class="selectDate_repeat_title">
                            <input v-model="repeat" type="checkbox" name="" id="selectDate_repeat">
                            <label for="selectDate_repeat">Повторять</label>
                        </div>
                        <div :style="repeat ? '' : 'color:gray;'" class="selectDate_repeat_interval">
                            Через каждые <input :disabled="!repeat" type="number" v-model="repeat_time" :min="30" :placeholder="30"> минут
                        </div>
                    </div>
                    <div :style="repeat ? '' : 'color:gray;'" class="selectDate_repeat_main">
                        <div class="selectDate_repeat_main_title_text">Не повторять после:</div>
                        <div>
                            <div class="selectDate_repeat_main_title">
                                <div>
                                    <input v-model="not_repeat" :disabled="!repeat" value="date" type="radio"
                                           name="selectDate_repeat_not" id="selectDate_repeat_not_date">
                                    <label for="selectDate_repeat_not_date">Определенной даты</label>
                                </div>
                                <div>
                                    <VueDatePicker dark :min-date="new Date()" v-model="not_repeat_date" auto-apply :disabled="!repeat || not_repeat !== 'date'"/>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div>
                            <div class="selectDate_repeat_main_title">
                                <div>
                                    <input v-model="not_repeat" :disabled="!repeat" value="count" type="radio"
                                           name="selectDate_repeat_not" id="selectDate_repeat_not_count">
                                    <label for="selectDate_repeat_not_count">Определенного количества раз</label>
                                </div>
                                <div class="selectDate_repeat_main_count_input">
                                    <input v-model="count" type="number" :disabled="!repeat || not_repeat !== 'count'" min="1" placeholder="1"><label for="">раз</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="popup_buttons">
                <button @click="hidepopup" class="popup_cancel">Отмена</button>
                <button @click="saveSchedule" class="newPost_button">Сохранить</button>
            </div>
        </div>
    </div>
    <div class="popup edit_post">
        <div>
            <div class="edit_post_main">
                <div class="newPost">
                    <div class="newPost_groupName">Ai Моди Бот🧑‍🎓</div>
                    <div class="newPost_image">
                        <template v-for="attach in editedPost.attachment">
                            <img :style="confirming ? 'filter:blur(3px);' : ''" @click="confirming = true" :src="config.storage + editedPost.attachment" v-show="(editedPost.attachment || photo) && (photo?.type?.startsWith('image/') || (getFileType(editedPost.attachment) === 'image'))" alt="">
                            <video v-show="(editedPost.attachment || photo) && (photo?.type?.startsWith('video/') || (getFileType(editedPost.attachment) === 'video'))" controls :src="config.storage + editedPost.attachment"></video>
                        </template>

                        <div v-if="confirming" class="newPost_image_confirm">
                            <button @click="confirming = 0; editedPost.attachment = null; photo = null" class="newPost_button delete">Удалить</button>
                            <button @click="confirming = 0" class="newPost_button cancel">Отмена</button>
                        </div>
                        <label @drop="drop" @dragover="ondragover" class="newPost_image_borders" for="photo">
                            <div>
                                <i class="fa-regular fa-image"></i>
                                <div>Выберите или перетащите картинку для поста</div>
                            </div>
                        </label>
                        <input ref="photoInput" @change="addimg" style="display:none" type="file" id="photo" accept="image/*,video/*,.gif">
                    </div>
                    <div>
                        <div class="editor-buttons" style="margin-top:10px;">
                            <button @click="toggleBoldEdit"><b>B</b></button>
                            <button @click="toggleItalicEdit"><i>I</i></button>
                            <button @click="toggleUnderlineEdit" style="text-decoration:underline;">U</button>
                            <button @click="setLinkEdit">🔗</button>
                        </div>
                        <div class="editor-box">
                            <editor-content v-if="editEditor" :editor="editEditor" />
                        </div>
                    </div>
                    <div class="newPost_statistics"><div>20:31</div></div>
                </div>
                <div class="selectDate">
                    <div class="selectDate_title">Расписание</div>
                    <div class="selectDate_publishDate">
                        <div class="selectDate_publishDate_title">Выберите дату публикации</div>
                        <div class="selectDate_publishDate_calendar">
                            <VueDatePicker dark v-model="date" :min-date="new Date()" inline auto-apply :enable-time-picker="false"/>
                            <VueDatePicker dark :key="timeKey" v-model="time" inline auto-apply :time-picker="true" :enable-time-picker="true" :enable-seconds="false"/>
                        </div>
                    </div>
                    <div class="selectDate_repeat">
                        <div class="selectDate_repeat_header">
                            <div class="selectDate_repeat_title">
                                <input v-model="repeat" type="checkbox" name="" id="selectDate_repeat">
                                <label for="selectDate_repeat">Повторять</label>
                            </div>
                            <div :style="repeat ? '' : 'color:gray;'" class="selectDate_repeat_interval">
                                Через каждые <input :disabled="!repeat" type="number" v-model="editedPost.time_repeat" :min="30" :placeholder="30"> минут
                            </div>
                        </div>
                        <div :style="repeat ? '' : 'color:gray;'" class="selectDate_repeat_main">
                            <div class="selectDate_repeat_main_title_text">Не повторять после:</div>
                            <div>
                                <div class="selectDate_repeat_main_title">
                                    <div>
                                        <input v-model="not_repeat" :disabled="!repeat" value="date" type="radio"
                                               name="selectDate_repeat_not" id="selectDate_repeat_not_date">
                                        <label for="selectDate_repeat_not_date">Определенной даты</label>
                                    </div>
                                    <div>
                                        <VueDatePicker dark :min-date="new Date()" v-model="editedPost.end_date" auto-apply :disabled="!repeat || not_repeat !== 'date'"/>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div>
                                <div class="selectDate_repeat_main_title">
                                    <div>
                                        <input v-model="not_repeat"  :disabled="!repeat" value="count" type="radio"
                                               name="selectDate_repeat_not" id="selectDate_repeat_not_count">
                                        <label for="selectDate_repeat_not_count">Определенного количества раз</label>
                                    </div>
                                    <div class="selectDate_repeat_main_count_input">
                                        <input v-model="editedPost.end_count" type="number" :disabled="!repeat || not_repeat !== 'count'" min="1" placeholder="1"><label for="">раз</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="popup_buttons">
                <button @click="hidepopup" class="popup_cancel">Отмена</button>
                <button @click="checkedit" class="newPost_button">Сохранить</button>
            </div>
        </div>
    </div>
    <div class="popup post">
        <div>
            <div class="newPost">
                <div class="newPost_groupName">Ai Моди Бот🧑‍🎓</div>
                <div class="newPost_image">
                    <div v-for="(attachment, key) in attachments" class="newPost_image_item">
                        <img :src="createURL(attachment)" :style="confirming === key ? 'filter:blur(3px);' : ''" @click="confirming = key" v-show="(attachment) && attachment.type.startsWith('image/')" alt="">
                        <video :src="createURL(attachment)" v-show="(attachment) && attachment.type.startsWith('video/')" controls @click="confirming = key"></video>

                        <div v-if="confirming === key" class="newPost_image_confirm">
                            <button @click="confirming = null; removeAttachment(key)" class="newPost_button delete">Удалить</button>
                            <button @click="confirming = null" class="newPost_button cancel">Отмена</button>
                        </div>
                    </div>
                    <div>
                        <label @drop="drop" @dragover="ondragover" class="newPost_image_borders" for="photo">
                            <div>
                                <i class="fa-regular fa-image"></i>
                                <div>Выберите или перетащите картинку для поста</div>
                            </div>
                        </label>
                        <input multiple ref="photoInput" @change="addimg" style="display:none" type="file" id="photo" accept="image/*,video/*,.gif">
                    </div>
                </div>
<!--                <textarea v-model="newPostText" class="newPost_text" name="" id=""></textarea>-->
                <div>
                    <div class="editor-buttons" style="margin-top:10px;">
                        <button @click="toggleBold"><b>B</b></button>
                        <button @click="toggleItalic"><i>I</i></button>
                        <button @click="toggleUnderline" style="text-decoration:underline;">U</button>
                        <button @click="setLink">🔗</button>
                    </div>
                    <div class="editor-box">
                        <editor-content v-if="editor" :editor="editor" />
                    </div>
                </div>
                <div class="newPost_statistics"><div>20:31</div></div>
            </div>
            <div class="popup_buttons">
                <button @click="hidepopup" class="popup_cancel">Отмена</button>
                <button @click="checkpost" class="newPost_button">Сохранить</button>
            </div>
        </div>
    </div>
    <adminnav>
        <div class="nav_main">
            <div class="nav_main_title">Мои посты</div>
            <div class="nav_main_block">
                <div class="nav_main_block_title">Новый пост</div>
                <div class="nav_main_buttons">
                    <button @click="showpopup('post'); newPostText.focus()">Создать в Web</button>
                </div>
            </div>
            <div class="nav_main_block">
                <div class="nav_main_block_title">Запланированные посты</div>
                <div v-if="posts?.length === 0">Ничего нет...</div>
                <div class="nav_main_list">
                    <div v-for="post in posts" @click="updateEdit(post)">
                        <div class="nav_main_list_img">
                            <img v-if="post.attachment" :src="config.storage + post.attachment" alt="Ошибка загрузки">
                            <div v-else><p>Нет изображения</p></div>
                        </div>
                        <div class="nav_main_list_info">
                            <div lang="ru" v-html="post.text">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </adminnav>
</template>

<style scoped>

</style>