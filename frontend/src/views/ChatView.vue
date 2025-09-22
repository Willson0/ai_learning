<script>
import ChatComponent from "@/components/ChatComponent.vue";
import {checkRules, closeOverlay, deepParse, notify, openOverlay, toLink} from "@/utils.js";
import axios from "axios";
import config from "@/config.json";

export default {
    name: "ChatView",
    components: {ChatComponent},
    data() {
        return {
            activeMenu: false,
            chat: null,
            isLoading: false,
            subjects: [],
            chatName: "",
        }
    },
    mounted () {

    },
    computed: {
        user () {
            return this.$store.state.user;
        }
    },
    methods: {
        openOverlay,
        closeOverlay,
        showMenu (ev) {
            this.activeMenu = !this.activeMenu;
            let el = this.$refs.chat_menu;
            let target = ev.target.closest('svg');

            el.style.top = target.getBoundingClientRect().bottom + 'px';
            el.style.right = (window.innerWidth - (target.getBoundingClientRect().right + 10)) + 'px';

            document.addEventListener('click', (e) => {
                if (!el.contains(e.target) && !target.contains(e.target)) {
                    this.activeMenu = false;
                }
            });
        },
        async deleteChat () {
            if (this.isLoading) return;

            this.activeMenu = false;
            if (!confirm(`Вы уверены, что хотите удалить ${this.chat.name}?`)) return;

            this.isLoading = true;

            let newUser = {...this.user};
            newUser.chats = newUser.chats.filter((chat) => chat.id !== this.chat.id);
            this.$store.commit('setUser', deepParse(newUser));

            toLink('ai');

            await axios.post(config.backend + `chat/${this.chat.id}/delete`, {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                notify("Чат успешно удален", 0);
            }).catch((error) => {
                notify(error.response.data.error, 1);
            }).finally(() => {
                this.isLoading = false;
            })
        },
        addSubject (id) {
            if (this.subjects.includes(id))
                return this.subjects = this.subjects.filter((item) => item !== id);

            if (this.subjects.length >= 3) this.subjects.shift();
            this.subjects.push(id);
        },
        async editChat () {
            if (this.isLoading) return;
            let rules = [
                ['new_name', this.chatName.length === 0, "Введите название чата"],
            ]
            if (this.chat.subjects?.length !== 0) rules.push(['new_select', this.subjects.length === 0, "Выберите предметы"]);
            if (checkRules(rules)) return;

            let button = this.$refs.new_button;
            button.style.transition = "opacity 0.2s";
            button.style.opacity = "0.7";

            this.isLoading = true;
            closeOverlay('ai_overlay_newSubject', 'ai_background_newSubject');

            let oldSubjects = [...this.chat.subjects];

            this.chat.name = this.chatName;
            this.chat.subjects = this.subjects;

            await axios.post(config.backend + "chat/" + this.chat.id, {
                initData: window.Telegram.WebApp.initData,
                name: this.chatName,
                subjects: oldSubjects?.length !== 0 ? this.subjects : [],
            }).then((response) => {
                let chats = [...this.user.chats];
                chats = chats.filter((item) => item.id !== this.chat.id);
                chats.push(response.data);

                let newUser = deepParse({...this.user, chats: chats});
                this.$store.commit('setUser', newUser);

                this.chatName = "";
                this.subjects = [];

                notify("Чат успешно обновлён", 0);
            }).catch((error) => {
                notify(error.response.data?.message || "Ошибка на сервере", 1);
            }).finally(() => {
                this.isLoading = false;
                button.style.opacity = "";
            })
        }
    },
}
</script>

<template>
    <div class="background ai_background_newSubject" @click="closeOverlay('ai_overlay_newSubject', 'ai_background_newSubject')" style="display: none;"></div>
    <div class="overlay ai_overlay_newSubject" style="display: none;">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="ai_overlay_newSubject_title">Редактирование чата</div>
        <input type="text" v-model="chatName" placeholder="Название" id="new_name">
        <div class="ai_overlay_newSubject_select" id="new_select" v-if="chat?.subjects?.length !== 0">
            <div class="ai_overlay_newSubject_select_title">Выбор предмета для чата</div>
            <div class="ai_overlay_newSubject_select_description">Максимальное количество предметов: 3</div>
            <div class="ai_overlay_newSubject_select_main">
                <div :class="{active: subjects.includes(subject.id)}"
                     @click="addSubject(subject.id)"
                     v-for="subject in user.subjects">{{ subject.name }}</div>
            </div>
        </div>
        <button ref="new_button" class="accept" @click="editChat">Сохранить</button>
    </div>
    <div v-show="activeMenu" ref="chat_menu" class="chat_menu">
        <button @click="activeMenu = false; chatName = chat.name; subjects = chat.subjects; openOverlay('ai_overlay_newSubject', 'ai_background_newSubject')">Редактировать чат</button>
        <hr>
        <button @click="deleteChat">Удалить чат</button>
    </div>
    <div class="chat_header" v-if="chat">
        <div>{{ chat.name }}</div>
        <svg @click="showMenu" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="10" cy="16" r="2" fill="white"/>
            <circle cx="16" cy="16" r="2" fill="white"/>
            <circle cx="22" cy="16" r="2" fill="white"/>
        </svg>
    </div>
    <div class="chat_view">
        <chat-component :chat_id="Number($route.query.id)" @chatload="chat = $event"/>
    </div>
</template>

<style scoped>

</style>