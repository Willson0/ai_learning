<script>
import adminnav from "@/components/adminnav.vue";
import config from "@/config.json"
import {removeLoading} from "@/assets/admin.js";
export default {
    data () {
        return {
            dateasc:true,
            nameasc:null,
            blocked:false,
            fromdate: '',
            todate: '',
            page: 1,
            countpage: 10,
            inputpage: '...',
            selectedusers: [],
            products: [],
            search: '',
            config: config,
        }
    },
    components: {
        adminnav,
    },
    async mounted () {
        document.body.style.backgroundColor = "#12121c";

        document.addEventListener("click", (ev) => {
            let sort = document.querySelector(".admin_users_main_header_buttons_sort_main");
            let filter = document.querySelector(".admin_users_main_header_buttons_filter_main");

            if (!sort.parentElement.contains(ev.target) && sort.classList.contains("active"))
                this.showsort();
            if (!filter.parentElement.contains(ev.target) && filter.classList.contains("active"))
                this.showfilter();
        })

        let input = document.querySelector(".admin_users_main_main_foot_paginator input");

        input.addEventListener("focus", () => {
            input.disabled = false;
            input.value = '';
        });

        input.addEventListener("blur", () => {
            if (input.value == '') return input.value = '...';
            if (input.value > this.countpage) return input.value = '...';

            this.page = Number(input.value);
            if (this.countpage - Number(this.inputpage) < 3) return this.inputpage = '...';
            this.fetchproducts();
        });

        input.addEventListener('keydown', function(event) {
            if ((event.key < '0' || event.key > '9') && event.key !== 'Backspace') {
                event.preventDefault();
            }
            if (event.key === 'Enter') {
                input.blur();
            }
        });

        await this.fetchproducts();
    },
    methods: {
        async fetchproducts() {
            let url = config.backend + 'admin/users?limit=10';
            url += `&page=${this.page}`
            if (this.dateasc !== null) url += `&datesort=${this.dateasc?'asc':'desc'}`;
            if (this.nameasc !== null) url += `&namesort=${this.nameasc?'asc':'desc'}`;
            if (this.fromdate !== '') url += `&datefrom=${this.fromdate}`;
            if (this.todate !== '') url += `&dateto=${this.todate}`;
            if (this.search) url += `&s=${this.search}`

            await fetch(url, {
                method: "GET",
                credentials: 'include',
            }).then((response) => {
                if (response.status === 401) return this.$route.push({"name": "adminLogin"})
                else if (!response.ok)
                    return alert ("Произошла непредвиденная ошибка! Обратитесь к разработчику.");
                return response.json();
            }).then((response) => {
                this.products = response.data;
                this.countpage = response.count;
                removeLoading();
            })
        },
        showsort () {
            let el = document.querySelector(".admin_users_main_header_buttons_sort_main");

            if (el.style.display === 'block') {
                el.classList.remove("active");
                setTimeout(() => {
                    el.style.display=""
                }, 200);
            } else {
                el.style.display = 'block';
                requestAnimationFrame(() => {
                    el.classList.add("active");
                })
            }
        },
        showfilter() {
            let el = document.querySelector(".admin_users_main_header_buttons_filter_main");

            if (el.style.display === 'block') {
                el.classList.remove("active");
                setTimeout(() => {
                    el.style.display=""
                }, 200);
            } else {
                el.style.display = 'block';
                requestAnimationFrame(() => {
                    el.classList.add("active");
                })
            }
        },
        formatDate(dateString) {
            const date = new Date(dateString);
            const months = [
                'янв', 'фев', 'мар', 'апр', 'мая', 'июн',
                'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'
            ];

            const day = date.getDate();
            const month = months[date.getMonth()];
            const year = date.getFullYear();
            const hour = String(date.getHours()).padStart(2, '0');
            const minute = String(date.getMinutes()).padStart(2, '0');

            return `${day} ${month} ${year} год, ${hour}:${minute}`
        },
        async changepage (page) {
            if (page <= 0 || page > this.countpage) return;
            if (page > 3 && (this.countpage-2) > page) {
                this.inputpage = `${page}`;
                this.page = page;
            } else {
                this.page = page;
                this.inputpage = '...';
            }
            await this.fetchproducts();
        },
    }
}
</script>

<template>
    <adminnav>
        <div class="admin_users_main">
            <div class="admin_users_main_header">
                <div class="admin_users_main_header_search">
                    <input @input="fetchproducts()" v-model="search" placeholder="Искать по имени" type="text">
                </div>
                <div style="height: 100%; display: flex; margin: auto 0; padding: 10px 20px; cursor: pointer; background-color: #389466; border-radius: 10px;" @click="$router.push('/admin/whitelist')">Вайт-лист</div>
                <div class="admin_users_main_header_buttons">
                    <div class="admin_users_main_header_buttons_sort">
                        <div @click="showsort()" class="admin_users_main_header_buttons_el_title">
                            <i class="fa-solid fa-sort"></i> <p>Sort by</p>
                        </div>
                        <div class="admin_users_main_header_buttons_sort_main">
                            <h4>Сортировка</h4>
                            <hr>
                            <h5>По дате</h5>
                            <div>
                                <div @click="dateasc = true; nameasc=null" :class="dateasc ? 'active' : ''">
                                    <div></div>
                                    <p>Сначала старые</p>
                                </div>
                                <div @click="dateasc = false; nameasc=null" :class="dateasc === false ? 'active' : ''">
                                    <div></div>
                                    <p>Сначала новые</p>
                                </div>
                            </div>
                            <hr>
                            <h5>По названию</h5>
                            <div>
                                <div @click="nameasc = true; dateasc=null" :class="nameasc ? 'active' : ''">
                                    <div></div>
                                    <p>A-Z</p>
                                </div>
                                <div @click="nameasc = false; dateasc=null" :class="nameasc === false ? 'active' : ''">
                                    <div></div>
                                    <p>Z-A</p>
                                </div>
                            </div>
                            <hr>
                            <div class="admin_users_main_header_buttons_sort_buttons">
                                <button @click="nameasc=null; dateasc=true">Reset</button>
                                <button @click="showsort(); fetchproducts()">Apply now</button>
                            </div>
                        </div>
                    </div>
                    <div class="admin_users_main_header_buttons_filter">
                        <div @click="showfilter()" class="admin_users_main_header_buttons_el_title">
                            <i class="fa-solid fa-filter"></i> Filter
                        </div>
                        <div class="admin_users_main_header_buttons_filter_main">
                            <h4>Filter by</h4>
                            <hr>
                            <div class="admin_users_main_header_buttons_filter_main_title">
                                <h5>Date range</h5>
                                <p @click="fromdate = ''; todate = ''">Reset</p>
                            </div>
                            <div class="admin_users_main_header_buttons_filter_main_date">
                                <div>
                                    <p>From:</p>
                                    <input v-model="fromdate" type="date">
                                </div>
                                <div>
                                    <p>To:</p>
                                    <input v-model="todate" type="date">
                                </div>
                            </div>
                            <hr>
                            <div class="admin_users_main_header_buttons_sort_buttons">
                                <button @click="fromdate = ''; todate = ''">Reset</button>
                                <button @click="showfilter(); fetchproducts()">Apply now</button>
                            </div>
                        </div>
                    </div>
<!--                    <div>-->
<!--                        <div @click="downloadexport()" class="admin_users_main_header_buttons_el_title">-->
<!--                            <i class="fa-solid fa-file-excel"></i> Export-->
<!--                        </div>-->
<!--                    </div>-->
                </div>
            </div>
            <div class="admin_users_main_main">
                <table class="admin_users_main_main_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Telegram ID</th>
                            <th>Имя</th>
                            <th>Аватар</th>
                            <th>Баланс</th>
                            <th>Зарегистрировался</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="cursor: pointer" @click="$router.push('/admin/users/' + product.id)" class="admin_products_main_main_table_tr" v-for="(product, key) in products">
                            <th>{{ product.id }}</th>
                            <th>{{ product.telegram_id }}</th>
                            <th>{{ product.fullname ?? '?' }}</th>
                            <th><a target="_blank" :href="product.avatar"><img :src="product.avatar" alt=""></a></th>
                            <th>{{ product.balance ?? '?'}}</th>
                            <th>{{ product.created_at ? formatDate(product.created_at) : '-' }}</th>
                        </tr>
                    </tbody>
                </table>
                <div class="admin_users_main_main_foo">
                    <div :style="page === 1 ? 'background-color:gray;opacity: 0.5;cursor: not-allowed;' : ''" @click="changepage(page-1)" class="admin_users_main_main_foot_button">
                        <i class="fa-solid fa-arrow-left"></i>
                        Previous
                    </div>
                    <div class="admin_users_main_main_foot_paginator" v-if="countpage > 6">
                        <div @click="changepage(i)" v-for="i in 3" :class="i === page ? 'active' : ''">{{i}}</div>
                        <input v-model="inputpage" :class="Number(inputpage) === page ? 'active' : ''" class="admin_users_main_main_foot_paginator_input">
                        <div @click="changepage(i)" v-for="i in [countpage-2, countpage-1, countpage]" :class="i === page ? 'active' : ''">{{i}}</div>
                    </div>
                    <div class="admin_users_main_main_foot_paginator" v-else>
                        <div @click="changepage(i)" v-for="i in countpage" :class="i === page ? 'active' : ''">{{i}}</div>
                    </div>
                    <div :style="page === countpage ? 'background-color:gray;opacity: 0.5;cursor: not-allowed;' : ''" @click="changepage(page+1)" class="admin_users_main_main_foot_button">
                        Next
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </adminnav>
</template>

<style scoped>
    * {
        color: white;
    }
</style>