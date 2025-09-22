<script>
import adminnav from "@/components/adminnav.vue";
import axios from "axios";
import config from "@/config.json"
export default {
    name: "SupportAdmin",
    components: {
        adminnav,
    },
    data() {
        return {
            filter: "open",
            dialogs: [],
            selected: null,
            newMessage: "",
            newImages: [],
            isMobile: false,
            config: config,
            interval: null,
        };
    },
    mounted() {
        this.checkMobile();
        window.addEventListener("resize", this.checkMobile);

        this.fetchData();
        this.interval = setInterval(() => {
            this.fetchData();
        }, 10000);
    },
    unmounted () {
        clearInterval(this.interval);
    },
    beforeDestroy() {
        window.removeEventListener("resize", this.checkMobile);
    },
    computed: {
        filteredDialogs() {
            return this.dialogs.filter(d =>
                this.filter === "open" ? !d.is_closed : d.is_closed
            );
        }
    },
    methods: {
        async fetchData () {
            await axios.get(config.backend + "admin/support", {
                withCredentials: true,
            }).then((response) => {
                this.dialogs = response.data;
                if (this.selected) this.selected = this.dialogs.find(d => d.id === this.selected.id);
            }).catch((error) => {
                alert(error.response.data.error || "Ошибка при загрузке диалогов");
            })
        },
        selectDialog(dialog) {
            this.selected = dialog;
            this.newMessage = "";
            this.newImages = [];
        },
        handleFiles(event) {
            const files = event.target.files;

            this.newImages = [];
            for (let f of files) {
                this.newImages.push({
                    file: f,
                    preview: URL.createObjectURL(f)
                });
            }
        },
        async sendMessage() {
            if (!this.newMessage && this.newImages.length === 0) return;

            this.selected.dialog.push({
                from: "admin",
                text: this.newMessage,
                images: this.newImages.length ? this.newImages.map(a => a.preview) : undefined
            });

            let fd = new FormData();
            fd.append("initData", window.Telegram.WebApp.initData);
            if (this.newMessage) fd.append("message", this.newMessage);
            if (this.newImages.length) {
                for (let img of this.newImages) fd.append("images[]", img.file)
            }

            this.newMessage = "";
            this.newImages = [];
            this.$refs.fileInput.value = "";

            await axios.post(config.backend + `admin/support/${this.selected.id}/send`, fd, {
                withCredentials: true,
            }).then((response) => {
                this.dialogs = response.data;
                this.selected = this.dialogs.find(d => d.id === this.selected.id);;
            }).catch((error) => {
                alert(error.response.data.error || "Ошибка при отправке сообщения",)
            });
        },
        closeDialog() {
            if (!confirm("Вы уверены, что хотите закрыть диалог?")) return;

            axios.get(config.backend + `admin/support/${this.selected.id}/close`, {
                withCredentials: true,
            }).then((response) => {
                this.dialogs = response.data;
                this.selected = this.dialogs.find(d => d.id === this.selected.id);
                alert ("Диалог успешно закрыт");
            }).catch((error) => {
                alert(error.response.data.error || "Ошибка при закрытии диалога");
            })
        },
        checkMobile() {
            this.isMobile = window.innerWidth <= 768;
        }
    },
};
</script>

<template>
    <adminnav>
        <div class="support-admin">
            <h2>Техподдержка</h2>

            <div class="layout">
                <!-- Список диалогов -->
                <div class="dialogs-panel" v-if="!isMobile || !selected">
                    <div class="filter">
                        <button
                            :class="{active: filter === 'open'}"
                            @click="filter = 'open'">
                            Открытые
                        </button>
                        <button
                            :class="{active: filter === 'closed'}"
                            @click="filter = 'closed'">
                            Закрытые
                        </button>
                    </div>

                    <div class="dialogs-list">
                        <div
                            v-for="dialog in filteredDialogs"
                            :key="dialog.id"
                            class="dialog-card"
                            @click="selectDialog(dialog)"
                        >
                            <p><b>#{{ dialog.id }}</b> — User {{ dialog.user_id }}</p>
<!--                            <p class="preview">{{ dialog.dialog[dialog.dialog.length-1]?.text }}</p>-->
                            <span v-if="!dialog.is_closed" class="open-label">Открыт</span>
                            <span v-else class="closed-label">Закрыт</span>
                        </div>
                    </div>
                </div>

                <!-- Окно диалога -->
                <div class="chat-panel" v-if="selected">
                    <!-- mobile back button -->
                    <button v-if="isMobile" class="back-btn" @click="selected = null">← Назад</button>

                    <h3>
                        Диалог #{{ selected.id }} — Пользователь {{ selected.user_id }}
                    </h3>

                    <div class="messages">
                        <div
                            v-for="(msg, index) in selected.dialog"
                            :key="index"
                            :class="['message', msg.from === 'admin' ? 'admin' : 'user']"
                        >
                            <p>{{ msg.text }}</p>
                            <div v-if="msg.images" class="images">
                                <img
                                    v-for="(img, i) in msg.images"
                                    :key="i"
                                    :src="config.storage + img"
                                    alt="img"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Управление открытым диалогом -->
                    <div v-if="!selected.is_closed" class="reply-box">
              <textarea
                  v-model="newMessage"
                  placeholder="Введите сообщение..."
              ></textarea>
                        <input ref="fileInput" type="file" accept="image/*" multiple @change="handleFiles" />
                        <div class="reply-actions">
                            <button @click="sendMessage">Отправить</button>
                            <button class="danger" @click="closeDialog">Закрыть диалог</button>
                        </div>
                    </div>
                </div>

                <!-- если ничего не выбрано -->
                <div v-if="!selected && !isMobile" class="placeholder">
                    <p>Выберите диалог слева</p>
                </div>
            </div>
        </div>
    </adminnav>
</template>

<style scoped>
.support-admin {
    background: #12121C;
    color: white;
    padding: 20px;
    border-radius: 12px;
    height: 100%;
}
h2, h3 {
    color: #389466;
}
.layout {
    display: flex;
    height: 70vh;
    gap: 10px;
}
.dialogs-panel {
    flex: 1;
    background: #1E1E2C;
    border-radius: 10px;
    padding: 10px;
    display: flex;
    flex-direction: column;
}
.chat-panel {
    flex: 3;
    background: #1E1E2C;
    border-radius: 10px;
    padding: 15px;
    display: flex;
    flex-direction: column;
}
.placeholder {
    flex: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #777;
    font-size: 18px;
}
.filter {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}
.filter button {
    flex: 1;
    background: #232336;
    border: none;
    padding: 8px;
    border-radius: 6px;
    color: white;
    cursor: pointer;
}
.filter button.active {
    background: #389466;
}
.dialogs-list {
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.dialog-card {
    background: #232336;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
}
.dialog-card:hover {
    background: #2f2f46;
}
.preview {
    font-size: 13px;
    color: #bbb;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.open-label {
    color: #389466;
}
.closed-label {
    color: #999;
}
.messages {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 10px;
}
.message {
    padding: 8px 12px;
    border-radius: 8px;
    max-width: 70%;
}
.message.user {
    background: #232336;
    align-self: flex-start;
}
.message.admin {
    background: #389466;
    align-self: flex-end;
}
.images {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 5px;
}
.images img {
    max-width: 120px;
    border-radius: 6px;
    object-fit: cover;
}
.reply-box {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
textarea {
    background: #12121C;
    color: white;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 8px;
    min-height: 60px;
}
input[type=file] {
    color: white;
}
.reply-actions {
    display: flex;
    gap: 10px;
}
button {
    background: #389466;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    color: white;
    cursor: pointer;
}
button.danger {
    background: #d14;
}
button:hover {
    opacity: 0.9;
}
.back-btn {
    margin-bottom: 10px;
    background: #232336;
}
@media (max-width: 768px) {
    .layout {
        flex-direction: column;
        height: auto;
    }
    .dialogs-panel {
        flex: none;
        max-height: 60vh;
    }
    .chat-panel {
        flex: none;
        max-height: 100vh;
    }
}
</style>
