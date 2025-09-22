<script>
import config from "@/config.json";
import {removeLoading} from "@/assets/admin.js";
export default {
    name: "adminLoginView",
    data () {
        return {
            username: '',
            password: '',
        }
    },
    async mounted() {
        document.body.classList.add("no-scroll");

        this.styleTag = document.createElement('link');
        this.styleTag.rel = 'stylesheet';
        this.styleTag.href = new URL('@/assets/admin.css', import.meta.url).href;
        document.head.appendChild(this.styleTag);

        document.body.style.backgroundColor = "#12121c";

        this.styleTag.onload = () => {
            console.log('CSS загружен!');
        };
    },
    methods: {
        login() {
            fetch (config.backend + 'admin/login', {
                method: 'POST',
                headers: {
                    "Content-Type": "application/json",
                    "Access-Control-Allow-Origin": '127.0.0.1:8000',
                },
                body: JSON.stringify({
                    "login": this.username,
                    "password": this.password,
                }),
                credentials: 'include',
            }).then((response) => {
                if (response.status === 403) return alert ("Неправильный логин или пароль");
                else if (response.ok) this.$router.push({name:'admin'});
                else alert ("Произошла непредвиденная ошибка. Обратитесь к разработчику");
            })
        }
    }
}
</script>

<template>
    <div class="adminLogin">
        <div class="adminLogin_main">
            <div class="adminLogin_main_site">
                <h2>CoinQuest</h2>
            </div>
            <div class="adminLogin_main_title">
                <h1>Welcome back!</h1>
                <p>Please enter your credentials to access the admin panel and manage the content of the website.</p>
            </div>
            <form class="adminLogin_main_form" @submit.prevent="login">
                <input required v-model="username" type="text" placeholder="Username">
                <input required v-model="password" type="password" placeholder="Password">
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>