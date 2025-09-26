<script>
import axios from 'axios';
import config from "@/config.json"
import {notify} from "@/utils.js";
export default {
    name: "NotificationView",
    data () {
        return {
            isLoading: false,
            config: config,
        }
    },
    async mounted () {
        await this.read();
    },
    unmounted () {
        let newUser = {...this.user};
        newUser.notifications?.forEach((n) => {
            n.read = 1;
        });
        this.$store.commit('setUser', newUser);
    },
    watch: {
        async user () {
            await this.read();
        }
    },
    computed: {
        user () {
           return this.$store.state.user;
        }
    },
    methods: {
        async read () {
            if (!this.user.id) return;
            await axios.post(config.backend + "auth/read_notifications", {
                initData: window.Telegram.WebApp.initData,
            });
        },
        async acceptFriend (id) {
            if (this.isLoading) return;

            this.isLoading = true;

            let newUser = {...this.user};
            newUser.friends.find(fr => fr.id === id).is_accepted = 1;
            this.$store.commit('setUser', newUser);

            await axios.post(config.backend + "friend/" + id + "/accept", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                notify("Пользователь добавлен в друзья", 0);
            }).finally(() => {
                this.isLoading = false;
            })
        },
        async declineFriend (id) {
            if (this.isLoading) return;

            this.isLoading = true;

            let newUser = {...this.user};
            newUser.friends = newUser.friends.filter(fr => fr.id !== id);
            this.$store.commit('setUser', newUser);

            await axios.post(config.backend + "friend/" + id + "/decline", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                notify("Заявка проигнорирована", 0);
            }).finally(() => {
                this.isLoading = false;
            })
        }
    }
}
</script>

<template>
    <div class="notification">
        <div class="notification_new">
            <div class="notification_new_title">Новые</div>
            <div v-if="(user.notifications?.filter(n => n.read === 0)?.length === 0) && (user.friends?.filter(fr => (fr.is_accepted === 0) && (fr.receiver_id === user.id)))" style="margin-top: 20px;">Тут пока что ничего нет...</div>
            <div class="notification_new_list" v-if="user.friends?.filter(fr => (fr.is_accepted === 0) && (fr.receiver_id === user.id))?.length !== 0">
                <div v-for="us in user.friends?.filter(fr => (fr.is_accepted === 0) && (fr.receiver_id === user.id))">
                    <img :src="us.avatar.startsWith('http') ? us.avatar : config.storage + us.avatar" alt="">
                    <div>
                        <div class="notification_new_list_title">Запрос в друзья</div>
                        <div class="notification_new_list_description">{{ us.fullname }} хочет добавить вас в друзья</div>
                        <div class="notification_new_list_buttons">
                            <button class="accept" @click="acceptFriend(us.id)">Добавить</button>
                            <button class="cancel" @click="declineFriend(us.id)">Скрыть</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="notification_old_list">
                <div v-for="note in user.notifications?.filter(n => n.read === 0)">
                    <div class="notification_old_list_title">{{ note.title }}</div>
                    <div class="notification_old_list_description">{{ note.body }}</div>
                </div>
            </div>
        </div>
        <div class="notification_old">
            <div class="notification_old_title">Просмотренные</div>
            <div v-if="user.notifications?.filter(n => n.read === 1)?.length === 0" style="margin-top: 20px;">Тут пока что ничего нет...</div>
            <div class="notification_old_list">
                <div v-for="note in user.notifications?.filter(n => n.read === 1)">
                    <div class="notification_old_list_title">{{ note.title }}</div>
                    <div class="notification_old_list_description">{{ note.body }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>