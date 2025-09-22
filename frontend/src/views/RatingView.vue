<script>
import SearchComponent from "@/components/SearchComponent.vue";
import axios from "axios";
import config from "@/config.json";
import {notify, toLink} from "@/utils.js";

export default {
    name: "RatingView",
    components: {SearchComponent},
    data () {
        return {
            activeMenu: false,
            selectedUser: null,
            search: "",
            isLoading: false,
            topColors: {
                1: "#FFB800",
                2: "#96C7FF",
                3: "#E4865E",
            }
        }
    },
    computed: {
        avatar () {
            return window.Telegram.WebApp.initDataUnsafe?.user?.photo_url;
        },
        name () {
            return window.Telegram.WebApp.initDataUnsafe?.user?.first_name;
        },
        user () {
            return this.$store.state.user;
        },
    },
    methods: {
        toLink,
        openMenu (ev) {
            let el = this.$refs.rating_list_user_menu;
            let target = ev.target.closest("svg");
            this.activeMenu = !this.activeMenu;

            el.style.top = (target.getBoundingClientRect().bottom + window.scrollY) + 'px';
            el.style.right = (window.innerWidth - target.getBoundingClientRect().right) + 'px';

            document.addEventListener('click', (e) => {
                if (!el.contains(e.target) && !e.target.closest("svg")) {
                    this.activeMenu = false;
                }
            });
        },
        getRussianPoints (count) {
            if (count === 1) {
                return 'балл';
            } else if (count >= 2 && count <= 4) {
                return 'балла';
            } else {
                return 'баллов';
            }
        },
        async deleteFriend () {
            if (this.isLoading) return;

            this.isLoading = true;
            this.activeMenu = false;

            let newUser = {...this.user};
            newUser.friends = newUser.friends.filter(fr => fr.id !== this.selectedUser);
            this.$store.commit('setUser', newUser);

            await axios.post(config.backend + "friend/" + this.selectedUser + "/delete", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                notify("Успешно удалено", 0);
            }).finally(() => {
                this.isLoading = false;
            })
        }
    }
}
</script>

<template>
    <div ref="rating_list_user_menu" class="rating_list_user_menu" @click="deleteFriend" v-show="activeMenu">
        Удалить из друзей
    </div>
    <div class="rating">
        <div class="rating_title">Ваш рейтинг</div>
        <div class="home_rating_your">
            <img :src="avatar" alt="">
            <div class="home_rating_your_name">Вы</div>
            <div class="home_rating_your_points">{{ user.total_points }} {{ getRussianPoints(Number(user.total_points)) }}</div>
        </div>
        <div class="rating_friend_title">
            Друзья <span>{{ user.friends?.length }}</span>
        </div>
        <search-component @inp="search = $event"/>
        <div class="home_rating_list rating_list">
            <div @click="toLink('user', us.id)" v-for="(us, key) in user.friends?.filter(fr => fr.is_accepted === 1 && fr.fullname.trim().toLowerCase().includes((search || '')?.trim()?.toLowerCase()))?.sort((a, b) => Number(b.total_points) - Number(a.total_points))">
                <div class="home_rating_list_number" :style="{background: key < 3 ? topColors[key + 1] : ''}"><div>{{ key+1 }}</div></div>
                <img :src="us.avatar" alt="">
                <div class="rating_list_user_info">
                    <div class="home_rating_list_name">{{ us.fullname }}</div>
                    <div class="home_rating_list_points">{{ us.total_points }} {{ getRussianPoints(Number(us.total_points)) }}</div>
                </div>
                <svg @click="selectedUser = us.id; openMenu($event)" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.7495 16.75C14.9921 16.75 15.9995 17.7574 15.9995 19V22.75C15.9995 23.9926 14.9921 25 13.7495 25H9.24951C8.00696 24.9999 6.99954 23.9926 6.99951 22.75V19C6.99951 17.7574 8.00694 16.7501 9.24951 16.75H13.7495ZM22.895 17.3223C23.2002 16.7814 23.8862 16.5896 24.4272 16.8945C24.9682 17.1998 25.16 17.8867 24.855 18.4277L21.4692 24.4277C21.2823 24.7589 20.9405 24.9738 20.561 24.998C20.1817 25.0221 19.8158 24.8524 19.5884 24.5479L17.3481 21.5479C16.9766 21.0501 17.079 20.3453 17.5767 19.9736C18.0745 19.602 18.7792 19.7043 19.1509 20.2021L20.3579 21.8184L22.895 17.3223ZM15.9995 7C18.2776 7 20.1245 8.84688 20.1245 11.125C20.1245 13.4032 18.2777 15.25 15.9995 15.25C13.7214 15.25 11.8745 13.4031 11.8745 11.125C11.8745 8.84689 13.7214 7.00003 15.9995 7Z" fill="#7B61FF"/>
                </svg>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>