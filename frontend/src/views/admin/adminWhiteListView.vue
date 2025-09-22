<script>
import adminnav from '@/components/adminnav.vue';
import config from '@/config.json';
import axios from 'axios';

export default {
    name: 'WhitelistVirtual',
    components: {
        adminnav,
    },
    data() {
        return {
            allItems: [], // тут должен быть fetch
            visibleItems: [],
            newValue: '',
            editIndex: -1,
            editValue: '',
            search: '',
            lastLoadedIndex: 0,
            loadingMore: false,
        };
    },
    async created() {
        // Здесь эмулируем большие данные (тестирование)
        const example = [];
        for(let i = 1; i <= 2000; i++) {
            example.push({id: i, value: "user" + i});
        }
        await axios.get(config.backend + "admin/whitelist", {
            withCredentials: true,
        }).then((response) => {
            this.allItems = response.data;
            this.visibleItems = this.allItems;
        }).catch((error) => {
            alert (error.response.data.error || "Ошибка при загрузке whitelist");
        });
    },
    methods: {
        onSearch() {
            const q = this.search.trim().toLowerCase();
            if (!q) {
                this.visibleItems = this.allItems;
            } else {
                const filtered = this.allItems.filter(
                    item => item.value.toLowerCase().includes(q)
                );
                this.visibleItems = filtered;
            }
        },
        addItem() {
            const value = this.newValue.trim();
            if (!value) return;

            axios.post(config.backend + "admin/whitelist",  {
                value: value,
            }, {
                withCredentials: true,
            }).then((response) => {
                this.allItems = response.data;
                this.visibleItems = this.allItems;
            })
        },
        deleteItem(id) {
            if (!confirm("Удалить этот whitelist?")) return;

            axios.delete(config.backend + "admin/whitelist/" + id, {
                withCredentials: true,
            }).then((response) => {
                this.allItems = response.data;
                this.visibleItems = this.allItems;
            });
        },
        startEdit(index, value) {
            this.editIndex = index;
            this.editValue = value;
        },
        saveEdit(index, id) {
            this.editValue = this.editValue.trim();
            if (!this.editValue) return;

            axios.post(config.backend + "admin/whitelist/" + id, {
                value: this.editValue,
            }, {
                withCredentials: true,
            }).then((response) => {
                this.allItems = response.data;
                this.visibleItems = this.allItems;
                this.editIndex = -1;
                this.editValue = '';
            });
        },
        cancelEdit() {
            this.editIndex = -1;
            this.editValue = '';
        },
        resetIndexes() {
            this.visibleItems = this.allItems;
            if(this.search) this.onSearch();
        }
    }
};
</script>

<template>
    <adminnav>
        <div class="whitelist-container">
            <div class="header-row">
                <input
                    class="search-input"
                    v-model="search"
                    placeholder="Поиск по ID или username…"
                    @input="onSearch"
                />
                <div class="add-block">
                    <input
                        class="add-input"
                        v-model="newValue"
                        placeholder="Новый Telegram ID/username"
                        @keyup.enter="addItem"
                    />
                    <button class="btn btn-add" @click="addItem">+</button>
                </div>
            </div>
            <div
                class="virtual-list"
                ref="list"
            >
                <div
                    v-for="(item, i) in visibleItems"
                    :key="item.id"
                    class="item-row"
                    :class="{ editing: editIndex === i }"
                >
                    <div class="item-value" v-if="editIndex !== i">{{ item.value }}</div>
                    <input
                        v-else
                        class="edit-input"
                        v-model="editValue"
                        @keyup.enter="saveEdit(i, item.id)"
                        @keyup.esc="cancelEdit"
                    />
                    <div class="actions">
                        <button v-if="editIndex !== i" class="btn btn-edit" @click="startEdit(i, item.value)">✏</button>
                        <button v-else class="btn btn-save" @click="saveEdit(i, item.id)">✔</button>
                        <button v-if="editIndex === i" class="btn btn-cancel" @click="cancelEdit">✖</button>
                        <button class="btn btn-delete" @click="deleteItem(item.id)">🗑</button>
                    </div>
                </div>
                <div v-if="!visibleItems.length && !loadingMore" class="no-data">Нет данных</div>
            </div>
        </div>
    </adminnav>
</template>

<style scoped>
.whitelist-container {
    background: #12121C;
    color: #fff;
    padding: 24px 14px 16px 14px;
    border-radius: 11px;
    width: 100%;
    box-shadow: 0 2px 24px -7px #000;
    font-family: 'Inter', Arial, sans-serif;
    height: fit-content;
}
.header-row {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 10px;
}
.search-input {
    flex-grow: 1;
    padding: 9px 12px;
    border: 1.3px solid #389466;
    border-radius: 6px;
    background: #181824;
    color: #efe;
    font-size: 1.06em;
    outline: none;
}
.add-block {
    display: flex;
    gap: 5px;
    align-items: center;
}
.add-input, .edit-input {
    width: 170px;
    padding: 8px 9px;
    background: #23233a;
    border: 1px solid #389466;
    border-radius: 6px;
    color: #fff;
    font-size: 1em;
    outline: none;
}
.btn {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #389466;
    border-radius: 6px;
    transition: background 0.18s, color 0.18s;
    padding: 5px 10px;
    font-size: 1.07em;
}
.btn-add {
    font-size: 1.22em;
    background: linear-gradient(90deg, #389466 50%, #47c695 100%);
    color: #181824;
    font-weight: bold;
    transition: 0.2s;
}
.btn-add:hover { background: linear-gradient(90deg, #47c695 50%, #389466 100%); color: #fff; }

.btn-edit { color: #389466; }
.btn-edit:hover { background: #192b1f; color: #49f3b1; }
.btn-delete { color: #ef5350;}
.btn-delete:hover { background: #261215; color: #fff; }
.btn-save { background: #389466; color: #fff;}
.btn-cancel { color: #bbb; }
.btn-cancel:hover { background: #333; color: #fff; }

.virtual-list {
    height: 660px;
    overflow-y: auto;
    background: #161624;
    border-radius: 7px;
    border: 1.3px solid #212122;
}
.item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 13px;
    border-bottom: 1px solid #242430;
    transition: background 0.14s;
}
.item-row.editing { background: #1f3f32; }
.item-value {
    flex: 1;
    color: #cde8d4;
    font-size: 1.04em;
    word-break: break-all;
}
.actions {
    display: flex;
    gap: 2px;
}
.no-data, .loading {
    color: #879;
    text-align: center;
    padding: 24px 0 10px 0;
    font-size: 1.06em;
}
</style>
