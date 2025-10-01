<script>
import config from "@/config.json"
import {removeLoading} from "@/assets/admin.js";
export default {
    data() {
        return {
            admin: {},
            config,
            isLoaded: false,
        }
    },
    async mounted() {
        removeLoading();
        // document.body.style.backgroundColor = "#14141e"
        // document.querySelectorAll(".adminnav_main_nav_main>div>p").forEach((el)=>{
        //     el.addEventListener("mouseenter", (ev) => ev.stopPropagation());
        // })

        this.styleTag = document.createElement('link');
        this.styleTag.rel = 'stylesheet';
        this.styleTag.href = new URL('@/assets/admin.css', import.meta.url).href;
        document.head.appendChild(this.styleTag);

        let nav = document.querySelector(".adminnav_main_nav");
        nav.addEventListener("mouseenter", (ev) => {
            nav.classList.add("active");
        });

        nav.addEventListener("mouseleave", () => {
            nav.classList.remove("active");
        });

        let accountmenu = document.querySelector(".adminnav_buttons_account_menu");
        document.addEventListener('click', (event) => {
            if (!accountmenu.parentElement.contains(event.target) && accountmenu.classList.contains("active")) {
                accountmenu.classList.remove("active");
            }
        });
        document.body.style.backgroundColor = "#12121c";

        this.styleTag.onload = () => {
            console.log('CSS загружен!');

            this.$nextTick(() => {
                nav.style.width = nav.clientWidth + 'px';
            });
        };

        await fetch (config.backend + "admin/profile", {
            method: "GET",
            credentials: "include",
        }).then((response) => {
            if (response.status === 401) return this.$router.push({name: "adminlogin"});
            return response.json();
        }).then((response) => {
            this.admin = response;
            console.log(response);
        });
    },
    methods: {
        showaccount () {
            document.querySelector(".adminnav_buttons_account_menu").classList.toggle("active");
        },
        showmenu () {
            document.querySelector(".adminnav_main_nav").classList.toggle("active");
        },
        async logout () {
            await fetch (config.backend + "admin/logout", {
                method: "POST",
                credentials: "include",
            }).then((response) => {
                if (!response.ok) return alert ("Error");
                this.$router.push("/");
            })
        }
    }
}
</script>

<template>
    <div class="notifyContainer"></div>
    <div class="loadPopup" style="width:100%; height:100%;position:fixed;display:block;transition:0.2s;background-color:#12121C;z-index:999999;">
        <p>Loading...</p>
    </div>
<div class="adminnav">
    <header class="adminnav_header">
        <div class="adminnav_slidebar">
            <i @click="showmenu()" class="fa-solid fa-list"></i>
            <div class="adminnav_title">
                {{ $route.meta.h }}
            </div>
        </div>
        <div class="adminnav_buttons">
<!--            <i class="fa-solid fa-magnifying-glass"></i>-->
<!--            <i class="fa-solid fa-envelopes-bulk"></i>-->
            <div class="adminnav_buttons_account">
                <img @click="showaccount()" src="https://avatars.mds.yandex.net/i?id=44ea903732525bafef17f89f82b94c625203a2e9-12314646-images-thumbs&n=13" alt="">
                <div class="adminnav_buttons_account_menu">
                    <div class="adminnav_buttons_account_menu_main_triangle"></div>
                    <div class="adminnav_buttons_account_menu_main">
<!--                        <div>-->
<!--                            <div class="adminnav_buttons_account_menu_main_button">-->
<!--                                <p>Profile</p>-->
<!--                            </div>-->
<!--                            <div class="adminnav_buttons_account_menu_main_button">-->
<!--                                <p>Settings</p>-->
<!--                            </div>-->
<!--                        </div>-->
                        <div class="adminnav_buttons_account_menu_main_line"></div>
                        <div @click="logout()" class="adminnav_buttons_account_menu_main_button">
                            <p>Logout</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="adminnav_main">
        <nav class="adminnav_main_nav">
            <div class="adminnav_main_nav_background"></div>
            <div class="adminnav_main_nav_website">
                <div class="adminnav_main_nav_website_logo">
<!--                    <img src="/logo.png" alt="">-->
                </div>
                <p>AiModi</p>
            </div>
            <div class="adminnav_main_nav_line"></div>
            <div class="adminnav_main_nav_main">
                <div @click="$router.push({'name': 'admin'})">
                    <div v-if="$route.name === 'admin'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-solid fa-code-branch"></i>
                    <p>Dashboard</p>
                </div>
                <div @click="$router.push('/admin/users')">
                    <div v-if="$route.path === '/admin/users'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-solid fa-user"></i>
                    <p>Пользователи</p>
                </div>
                <div @click="$router.push('/admin/courses')">
                    <div v-if="$route.path === '/admin/courses'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-solid fa-book"></i>
                    <p>Курсы</p>
                </div>
                <div @click="$router.push('/admin/achievements')">
                    <div v-if="$route.path === '/admin/achievements'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-solid fa-trophy"></i>
                    <p>Достижения</p>
                </div>
                <div @click="$router.push('/admin/support')">
                    <div v-if="$route.path === '/admin/support'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-solid fa-headset"></i>
                    <p>Поддержка</p>
                </div>
                <div @click="$router.push('/admin/probes')">
                    <div v-if="$route.path === '/admin/probes'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-solid fa-file-contract"></i>
                    <p>Пробники</p>
                </div>
                <div @click="$router.push('/admin/states')">
                    <div v-if="$route.path === '/admin/states'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-solid fa-newspaper"></i>
                    <p>Статьи</p>
                </div>
                <div @click="$router.push('/admin/ads')">
                    <div v-if="$route.path === '/admin/ads'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-brands fa-adversal"></i>
                    <p>Рекламы</p>
                </div>
                <div @click="$router.push('/admin/subjects')">
                    <div v-if="$route.path === '/admin/subjects'" class="adminnav_main_nav_main_el_point">&middot;</div>
                    <i class="fa-solid fa-graduation-cap"></i>
                    <p>Предметы</p>
                </div>
            </div>
        </nav>
        <div class="adminnav_main_main">
            <slot></slot>
        </div>
    </div>
</div>
</template>

<style scoped>
* {
    color: white;
}
.loadPopup {
    width:100%;
    height:100%;
    position:fixed;
    display:block;
    transition:0.2s;
    background-color:#12121C;
    z-index:999999;
    color:white;
}

.loadPopup>p {
    position:absolute;
    left:50%;
    top:50%;
    font-size:30px;
    transform:translate(-50%, -50%);
}
</style>