<script>
import adminnav from "@/components/adminnav.vue";
import axios from "axios";
import config from "@/config.json";
import {removeLoading} from "@/assets/admin.js";
export default {
    name: "CurrenciesAdmin",
    components: {
        adminnav,
    },
    data() {
        return {
            currencies: [],
            newCurrency: {
                tvSymbol: "",
                coingeckoId: ""
            },
            editingId: null,
            editForm: {
                tvSymbol: "",
                coingeckoId: ""
            }
        };
    },
    async mounted () {
        await this.fetchData();
    },
    methods: {
        async fetchData() {
            await axios.get(config.backend + "admin/currencies", {
                withCredentials: true
            }).then((response) => {
                this.currencies = response.data;
                removeLoading();
            }).catch((error) => {
                alert(error.response.data.message || error.response.data.error || error.response.data || error.message || error);
            });
        },
        async addCurrency() {
            await axios.post(config.backend + "admin/currencies", this.newCurrency,{
                withCredentials: true
            }).then((response) => {
                this.currencies = response.data;
                this.newCurrency = {
                    "tvSymbol": "",
                    "coingeckoId": ""
                }
                removeLoading();
            }).catch((error) => {
                alert(error.response.data.message || error.response.data.error || error.response.data || error.message || error);
            });
        },
        editCurrency(currency) {
            this.editingId = currency.id;
            this.editForm = { tvSymbol: currency.tvSymbol, coingeckoId: currency.coingeckoId };
        },
        async saveEdit(id) {
            await axios.post(config.backend + "admin/currencies/" + id, this.editForm,{
                withCredentials: true
            }).then((response) => {
                this.currencies = response.data;
                this.editingId = null;
                removeLoading();
            }).catch((error) => {
                alert(error.response.data.message || error.response.data.error || error.response.data || error.message || error);
            });
        },
        cancelEdit() {
            this.editingId = null;
        },
        async deleteCurrency(id) {
            if (window.confirm(`Вы действительно хотите удалить криптовалюту "${this.currencies.find(a => a.id === id).coingeckoId}"?`)) {
                await axios.delete(config.backend + "admin/currencies/" + id,{
                    withCredentials: true
                }).then((response) => {
                    this.currencies = response.data;
                    removeLoading();
                }).catch((error) => {
                    alert(error.response.data.message || error.response.data.error || error.response.data || error.message || error);
                });
            }
        }
    }
};
</script>

<template>
    <adminnav>
        <div class="currencies-admin">
            <!-- Links with Tooltip -->
            <div class="tips">
                <a href="https://www.coingecko.com/ru" target="_blank" title="Find CoinGecko coin IDs here" class="tips-link">
                    CoinGecko IDs
                </a>
                <a href="https://www.tradingview.com/markets/cryptocurrencies/" target="_blank" title="Find TradingView symbols here" class="tips-link">
                    TradingView Symbols
                </a>
            </div>

            <!-- Table -->
            <table class="crypto-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>TradingView Symbol</th>
                    <th>CoinGecko ID</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(currency, idx) in currencies" :key="currency.id" :class="{ editing: editingId===currency.id }">
                    <td>{{ idx + 1 }}</td>
                    <td v-if="editingId!==currency.id">{{ currency.tvSymbol }}</td>
                    <td v-else>
                        <input v-model="editForm.tvSymbol" class="edit-input"/>
                    </td>
                    <td v-if="editingId!==currency.id">{{ currency.coingeckoId }}</td>
                    <td v-else>
                        <input v-model="editForm.coingeckoId" class="edit-input"/>
                    </td>
                    <td class="actions">
                        <template v-if="editingId===currency.id">
                            <button @click="saveEdit(currency.id)" class="save-btn">Save</button>
                            <button @click="cancelEdit" class="cancel-btn">Cancel</button>
                        </template>
                        <template v-else>
                            <button @click="editCurrency(currency)" class="edit-btn">Edit</button>
                            <button @click="deleteCurrency(currency.id)" class="delete-btn">Delete</button>
                        </template>
                    </td>
                </tr>
                <tr v-if="currencies.length < 1">
                    <td colspan="4" style="text-align:center;color:#666;">No currencies added yet.</td>
                </tr>
                </tbody>
            </table>

            <!-- Add new currency form -->
            <form class="add-form" @submit.prevent="addCurrency">
                <h3>Add new currency</h3>
                <input v-model="newCurrency.tvSymbol" placeholder="TradingView Symbol" required class="add-input"/>
                <input v-model="newCurrency.coingeckoId" placeholder="CoinGecko ID" required class="add-input"/>
                <button type="submit" class="add-btn">Add</button>
            </form>
        </div>
    </adminnav>
</template>

<style scoped>
.currencies-admin {
    padding: 32px 24px 24px 24px;
    background: #12121C;
    border-radius: 18px;
    min-width: 360px;
    width: 100%;
    margin: 30px auto;
    box-shadow: 0 8px 32px rgba(56,148,102,0.07), 0 1.5px 9px rgba(56,148,102,0.22);
    color: #fff;
    font-family: 'Inter',sans-serif;
}

h2 {
    color: #389466;
    margin-bottom: 18px;
    font-size: 2rem;
    letter-spacing: 1px;
}

.tips {
    display: flex;
    gap: 15px;
    margin-bottom: 16px;
}
.tips-link {
    color: #389466;
    font-size: 0.95rem;
    text-decoration: underline dotted;
    display: flex;
    align-items: center;
}
.tips-link .icon {
    font-size: 1.1em;
    margin-left: 4px;
    vertical-align: text-bottom;
}

.crypto-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
    background: #161822;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
}

.crypto-table th,
.crypto-table td {
    padding: 12px 10px;
    text-align: left;
}

.crypto-table th {
    background: #232339;
    color: #389466;
    font-size: 1.09rem;
    letter-spacing: 0.7px;
    border-bottom: 2px solid #1c1c2b;
}

.crypto-table tr:not(:last-child) td {
    border-bottom: 1px solid #232339;
}

.crypto-table tr.editing {
    background: #213b2f;
    animation: blink 0.3s;
}
@keyframes blink { from { background:#38946622; } to{ background:#213b2f; } }

.actions {
    text-align: right !important;
    white-space: nowrap;
    width: fit-content;
}

button, .add-btn {
    background: none;
    border: none;
    border-radius: 7px;
    color: #389466;
    padding: 5px 16px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.12s, color 0.15s;
    margin-left: 3px;
}

.edit-btn { background: #1c3327; }
.delete-btn { background: #392020; color:#fa6060; }
.save-btn { background: #207a58; color:#fff; }
.cancel-btn { background: #212327; color:#ccc; }

.edit-btn {
    margin-left: auto;
}

button:hover, .add-btn:hover {
    background: #389466;
    color: #fff;
}
.delete-btn:hover {
    background: #f84e4e;
    color: #fff;
}

.edit-input, .add-input {
    background: #18192a;
    border: 1px solid #232339;
    color: #dbffe4;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: 1em;
    width: 95%;
    margin-bottom: 2px;
    outline: none;
    transition: border-color 0.14s;
}
.edit-input:focus, .add-input:focus {
    border-color: #389466;
}

.add-form {
    margin-top: 15px;
    padding: 15px;
    background: #18192a;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(56,148,102,0.10);
}

.add-form h3 {
    margin: 0 0 10px 0;
    color: #4afaac;
    font-size: 1.09rem;
    font-weight: 600;
}

.add-input { margin-right: 10px; width: 40%; }
.add-btn {
    background: #389466;
    color: #fff;
    font-weight: 700;
    letter-spacing: 0.7px;
    padding: 7px 18px;
}
.add-btn:hover {
    background: #21704d;
    color: #fff;
}
@media(max-width:600px) {
    .currencies-admin { padding: 8vw 2vw; max-width: 97vw; min-width: unset; font-size:95%; }
    .add-form .add-input { width: 100%; margin-bottom:8px; }
}
</style>
