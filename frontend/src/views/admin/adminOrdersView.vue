<script>
import adminnav from "@/components/adminnav.vue";
import { removeLoading } from "@/assets/admin.js";
import axios from "axios";
import config from "@/config.json";

export default {
    components: { adminnav },
    data() {
        return {
            orders: [],
            modalOpened: false,
            editMode: false,
            form: {
                id: null,
                user: "",
                user_avatar: "",
                is_safe: false,
                remain: 0,
                payment_method: "sbp",
                price: 0,
                currency_id: null,
                fiat_currency_id: null,
                min_limit: 0,
                max_limit: 0,
            },
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        async fetchData() {
            await axios
                .get(config.backend + "admin/orders", { withCredentials: true })
                .then((response) => {
                    this.orders = response.data;
                    removeLoading();
                });
        },
        openAdd() {
            this.editMode = false;
            this.form = {
                id: null,
                user: "",
                user_avatar: "",
                is_safe: false,
                remain: 0,
                payment_method: "sbp",
                price: 0,
                currency_id: null,
                fiat_currency_id: null,
                min_limit: '',
                max_limit: '',
            };
            this.modalOpened = true;
        },
        openEdit(order) {
            this.editMode = true;
            this.form = { ...order };
            this.form.is_safe = this.form.is_safe ? true : false;
            this.modalOpened = true;
        },
        closeModal() {
            this.modalOpened = false;
        },
        async saveOrder() {
            if (this.editMode) {
                let data = {};
                for (let key in this.form) {
                    if (this.form[key] !== this.orders.find(a => a.id === this.form.id)[key]) {
                        data[key] = this.form[key];
                    }
                }

                await axios
                    .post(config.backend + "admin/orders/" + this.form.id, data, { withCredentials: true })
                    .then((response) => {
                        this.orders = response.data;
                    })
                    .catch((error) => {
                        alert("Ошибка: " + error.response.data.message);
                    });
            } else {
                await axios
                    .post(config.backend + "admin/orders", { ...this.form }, { withCredentials: true })
                    .then((response) => {
                        this.orders = response.data;
                    })
                    .catch((error) => {
                        alert("Ошибка: " + error.response.data.message);
                    });
            }
            this.closeModal();
        },
        async deleteOrder(id) {
            if (!confirm(`Вы действительно хотите удалить ордер #${id}?`)) return;
            await axios
                .delete(config.backend + `admin/orders/${id}`, { withCredentials: true })
                .then((response) => {
                    this.orders = response.data;
                });
        },
    },
};
</script>

<template>
    <adminnav>
        <div class="admin-orders">
            <div class="header">
                <button class="btn-main" @click="openAdd">Добавить ордер</button>
            </div>

            <div class="admin_users_main_main">
                <table class="admin_users_main_main_table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Аватар</th>
                        <th>Надежный</th>
                        <th>Остаток</th>
                        <th>Метод оплаты</th>
                        <th>Цена</th>
                        <th>Криптовалюта</th>
                        <th>Фиат</th>
                        <th>Мин. лимит</th>
                        <th>Макс. лимит</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="order in orders.sort((a, b) => b.id - a.id)"
                        :key="order.id"
                        @click="openEdit(order)"
                        class="admin_products_main_main_table_tr"
                        style="cursor:pointer"
                    >
                        <td>{{ order.id }}</td>
                        <td>{{ order.user }}</td>
                        <td><img :src="order.user_avatar" alt="avatar" style="width:40px;height:40px;border-radius:50%;" /></td>
                        <td>{{ order.is_safe ? "✅" : "❌" }}</td>
                        <td>{{ order.remain }}</td>
                        <td>{{ order.payment_method }}</td>
                        <td>{{ order.price }}</td>
                        <td>{{ order.currency_id }}</td>
                        <td>{{ order.fiat_currency_id }}</td>
                        <td>{{ order.min_limit }}</td>
                        <td>{{ order.max_limit }}</td>
                        <td style="color:darkred" @click.stop="deleteOrder(order.id)">Удалить</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Модальное окно -->
        <div v-if="modalOpened" class="modal-bg" @click="closeModal">
            <div class="modal" @click.stop>
                <h3>{{ editMode ? "Редактировать ордер" : "Добавить ордер" }}</h3>

                <div class="form-group">
                    <label>Пользователь</label>
                    <input v-model="form.user" type="text" />
                </div>

                <div class="form-group">
                    <label>Аватар</label>
                    <input v-model="form.user_avatar" type="text" placeholder="URL аватара" />
                </div>

                <div class="form-group">
                    <label>Надежный продавец</label>
                    <input v-model="form.is_safe" type="checkbox" />
                </div>

                <div class="form-group">
                    <label>Остаток криптовалюты</label>
                    <input v-model="form.remain" type="number" />
                </div>

                <div class="form-group">
                    <label>Метод оплаты</label>
                    <select v-model="form.payment_method">
                        <option value="sbp">СБП</option>
                        <option value="tbank">Т-Банк</option>
                        <option value="sber">Сбер</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Цена</label>
                    <input v-model="form.price" type="number" />
                </div>

                <div class="form-group">
                    <label>ID криптовалюты</label>
                    <input v-model="form.currency_id" type="number" />
                </div>

                <div class="form-group">
                    <label>ID фиата</label>
                    <input v-model="form.fiat_currency_id" type="number" />
                </div>

                <div class="form-group">
                    <label>Мин. лимит</label>
                    <input v-model="form.min_limit" type="number" />
                </div>

                <div class="form-group">
                    <label>Макс. лимит</label>
                    <input v-model="form.max_limit" type="number" />
                </div>

                <div class="modal-actions">
                    <button class="btn-main" @click="saveOrder">{{ editMode ? "Сохранить" : "Создать" }}</button>
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
