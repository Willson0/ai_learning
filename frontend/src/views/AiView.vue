<script>
import DoubleSelector from "@/components/DoubleSelectorComponent.vue";
import SearchComponent from "@/components/SearchComponent.vue";
import {checkRules, closeOverlay, deepParse, notify, openOverlay, toLink} from "@/utils.js";
import axios from "axios";
import config from "@/config.json";

export default {
    name: "AiView",
    components: {SearchComponent, DoubleSelector},
    data () {
        return {
            subjects: [],
            chatName: "",
            filterDate: null,
            selectedSubjects: [],
            isExercise: false,
            isLoading: false,
        }
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
        chatList () {
            if (!this.user.id) return;
            return this.user.chats.filter((item) => {
                if (this.filterDate != null) {
                    let date = new Date();
                    date = date.setDate(date.getDate() - this.filterDate);

                    if (new Date(item.created_at) < date) return false;
                }

                if (!this.isExercise) {
                    let inSelected = true;
                    if (this.selectedSubjects.length !== 0) {
                        let subjects = item.subjects;
                        inSelected = subjects.some((sub) => {
                            if (this.selectedSubjects.includes(sub)) return true;
                        })
                    }
                    return (item.subjects && item.subjects.length !== 0 && inSelected)
                }
                else return (!item.subjects || item.subjects.length === 0)
            });
        },
    },
    methods: {
        toLink, closeOverlay, openOverlay,
        addSubject (id) {
            if (this.subjects.includes(id))
                return this.subjects = this.subjects.filter((item) => item !== id);

            if (this.subjects.length >= 3) this.subjects.shift();
            this.subjects.push(id);
        },
        getSubjects (array) {
            if (!this.user.id) return;
            if (array.length === 0) return;

            let str = "";
            array.forEach((item) => {
                let subj = this.user.subjects.find((sub) => sub.id === item);
                if (subj) str += subj.name + ", "
            });
            if (str.length !== 0) return str.slice(0, -2);
            else return "";
        },
        getDate (date) {
            if (!date) return "";

            let dat = new Date(date);
            const months = ["янв", "фев", "мар", "апр", "май", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"];

            return `${dat.getDate()} ${months[dat.getMonth()]} ${dat.getHours().toString().padStart(2, '0')}:${dat.getMinutes().toString().padStart(2, '0')}`
        },
        async storeChat () {
            if (this.isLoading) return;
            let rules = [
                ['new_name', this.chatName.length === 0, "Введите название чата"],
            ]
            if (!this.isExercise) rules.push(['new_select', this.subjects.length === 0, "Выберите предметы"]);
            if (checkRules(rules)) return;

            let button = this.$refs.new_button;
            button.style.transition = "opacity 0.2s";
            button.style.opacity = "0.7";

            this.isLoading = true;
            await axios.post(config.backend + "chat", {
                initData: window.Telegram.WebApp.initData,
                name: this.chatName,
                subjects: !this.isExercise ? this.subjects : [],
            }).then((response) => {
                let chats = [...this.user.chats];
                chats = chats.filter((item) => item.id != null);
                chats.push(response.data);

                let newUser = deepParse({...this.user, chats: chats});
                this.$store.commit('setUser', newUser);

                closeOverlay('ai_overlay_newSubject', 'ai_background_newSubject');

                this.chatName = "";
                this.subjects = [];
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
    <div class="ai_filter_background background" @click="closeOverlay('ai_filter_overlay', 'ai_filter_background')" style="display: none"></div>
    <div class="ai_filter_overlay overlay" style="display: none">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="themes_filter_overlay_title">Фильтры</div>
        <div class="themes_filter_overlay_block">
            <div class="themes_filter_overlay_block_title">Дата</div>
            <div class="themes_filter_overlay_block_list">
                <div v-for="days in [[1, 'За сегодня'], [7, 'За неделю'], [30, 'За месяц']]"
                     :class="{'active': filterDate === days[0]}"
                     @click="filterDate === days[0] ? filterDate = null : filterDate = days[0]">
                    <div>{{ days[1] }}</div>
                </div>
            </div>
        </div>
        <div class="themes_filter_overlay_block" v-if="!isExercise">
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
        <button @click="closeOverlay('ai_filter_overlay', 'ai_filter_background')">Применить</button>
    </div>
    <div class="background ai_background_newSubject" @click="closeOverlay('ai_overlay_newSubject', 'ai_background_newSubject')" style="display: none;"></div>
    <div class="overlay ai_overlay_newSubject" style="display: none;">
        <div class="overlay_closeArea">
            <svg width="67" height="2" viewBox="0 0 67 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.5 1H65.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="ai_overlay_newSubject_title">Создание чата</div>
        <input type="text" v-model="chatName" placeholder="Название" id="new_name">
        <div class="ai_overlay_newSubject_select" v-if="!isExercise" id="new_select">
            <div class="ai_overlay_newSubject_select_title">Выбор предмета для чата</div>
            <div class="ai_overlay_newSubject_select_description">Максимальное количество предметов: 3</div>
            <div class="ai_overlay_newSubject_select_main">
                <div :class="{active: subjects.includes(subject.id)}"
                     @click="addSubject(subject.id)"
                     v-for="subject in user.subjects">{{ subject.name }}</div>
            </div>
        </div>
        <button ref="new_button" class="accept" @click="storeChat">Создать чат</button>
    </div>
    <div class="ai">
        <double-selector @change="isExercise = $event" style="margin-top: 0;" first="По предмету" second="По заданию"/>
        <button @click="openOverlay('ai_filter_overlay', 'ai_filter_background')" class="ai_filters accept">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 18H8C8.55 18 9 17.55 9 17C9 16.45 8.55 16 8 16H4C3.45 16 3 16.45 3 17C3 17.55 3.45 18 4 18ZM3 7C3 7.55 3.45 8 4 8H20C20.55 8 21 7.55 21 7C21 6.45 20.55 6 20 6H4C3.45 6 3 6.45 3 7ZM4 13H14C14.55 13 15 12.55 15 12C15 11.45 14.55 11 14 11H4C3.45 11 3 11.45 3 12C3 12.55 3.45 13 4 13Z" fill="white"/>
                </svg>
                <div>Фильтры</div>
            </div>
        </button>
        <div class="ai_chats">
            <div class="ai_chats_title">Чаты</div>
            <div class="ai_chats_new">
                <div>Новый чат</div>
                <button class="accept" @click="openOverlay('ai_overlay_newSubject', 'ai_background_newSubject')">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.1 17.1V9.89561H0.9C0.402981 9.89561 5.98325e-05 9.49261 0 8.99561C2.6335e-08 8.49855 0.402944 8.0956 0.9 8.0956H8.1V0.9C8.1 0.402944 8.50294 -5.64782e-10 9 0C9.49706 2.6335e-08 9.9 0.402944 9.9 0.9V8.0956H17.1C17.5971 8.0956 18 8.49855 18 8.99561C17.9999 9.49261 17.597 9.89561 17.1 9.89561H9.9V17.1C9.9 17.5971 9.49706 18 9 18C8.50294 18 8.1 17.5971 8.1 17.1Z" fill="white"/>
                    </svg>
                </button>
            </div>
            <search-component />
            <div class="ai_chats_list">
                <div @click="toLink('chat', chat.id)" v-for="chat in chatList?.sort((a, b) => a.id - b.id)">
                    <div>
                        <div class="ai_chats_list_item_title">{{ chat.name }}</div>
                        <div class="ai_chats_list_item_subject" v-if="!isExercise">Предмет: {{ getSubjects(chat.subjects) }}</div>
                    </div>
                    <div class="ai_chats_list_item_date">
                        {{ getDate(chat.created_at) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>