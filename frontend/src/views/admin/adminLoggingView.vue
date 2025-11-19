<script>
import axios from 'axios';
import config from '@/config.json';
import adminnav from "@/components/adminnav.vue";
import {deepParse} from "@/utils.js";

export default {
    name: 'AdminLogsPage',
    data() {
        return {
            logs: [],
            loading: true
        };
    },
    components: {adminnav},
    mounted() {
        axios.defaults.withCredentials = true;
        axios
            .get(config.backend + 'admin/log')
            .then(res => {
                this.logs = (deepParse(res.data) || []).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            })
            .catch(err => {
                console.error('Failed to load logs', err);
            })
            .finally(() => { this.loading = false; });
    },
    methods: {
        formatDate(dateStr) {
            if (!dateStr) return '';
            // ensure compatibility with "YYYY-MM-DD HH:MM:SS"
            const d = new Date(dateStr.replace(' ', 'T'));
            return d.toLocaleString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        },

        renderLogText(log) {
            let text = log.text || '';
            const data = log.data || {};

            // Заменяем все вхождения &{user} и &{referral} безопасно
            if (text.indexOf('&{user}') !== -1) {
                const user = data.user;
                if (user && user.id && user.fullname) {
                    const link = '<a href="/admin/users/' + this.escapeHtml(user.id) + '" class="admin_logging-link">' + this.escapeHtml(user.fullname) + '</a>';
                    text = text.split('&{user}').join(link);
                } else {
                    text = text.split('&{user}').join(this.escapeHtml((user && user.fullname) || 'пользователь'));
                }
            }

            if (text.indexOf('&{referral}') !== -1) {
                const r = data.referral;
                if (r && r.id && r.fullname) {
                    const link = '<a href="/admin/users/' + this.escapeHtml(r.id) + '" class="admin_logging-link">' + this.escapeHtml(r.fullname) + '</a>';
                    text = text.split('&{referral}').join(link);
                } else {
                    text = text.split('&{referral}').join(this.escapeHtml((r && r.fullname) || 'реферал'));
                }
            }

            return text;
        },

        escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    }
};
</script>

<template>
    <adminnav>
        <div class="admin_logging-page-root">
            <div class="admin_logging-container">
                <h1 class="admin_logging-title">Логи пользователей</h1>

                <div v-if="loading" class="admin_logging-preloader-wrap">
                    <div class="admin_logging-preloader" aria-hidden="true"></div>
                    <div class="admin_logging-preloader-text">Загрузка логов...</div>
                </div>

                <div v-else class="admin_logging-logs-list">
                    <div v-if="logs.length === 0" class="admin_logging-empty">Логов не найдено</div>

                    <div
                        v-for="(log, index) in logs"
                        :key="log.id || (log.created_at + index)"
                        class="admin_logging-log-card"
                    >
                        <div class="admin_logging-log-meta">{{ formatDate(log.created_at) }}</div>
                        <div class="admin_logging-log-text" v-html="renderLogText(log)"></div>
                    </div>
                </div>
            </div>
        </div>
    </adminnav>
</template>

<style scoped>
.admin_logging-page-root {
    min-height: 100vh;
    background: #12121C;
    color: #E6E7EA;
    font-family: Inter, Roboto, system-ui, -apple-system, 'Segoe UI', 'Helvetica Neue', Arial;
    padding: 28px 18px;
    display: flex;
    justify-content: center;
    box-sizing: border-box;
}

.admin_logging-container {
    width: 100%;
    max-width: 960px;
}

.admin_logging-title {
    text-align: center;
    color: #389466;
    font-size: 28px;
    margin: 8px 0 22px;
    font-weight: 700;
}

.admin_logging-logs-list {
    display: grid;
    gap: 14px;
}

.admin_logging-log-card {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.04);
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.6);
    transition: 0.18s;
}

.admin_logging-log-card:hover {
    transform: translateY(-6px);
    border-color: rgba(56,148,102,0.18);
}

.admin_logging-log-meta {
    font-size: 13px;
    color: rgba(255,255,255,0.45);
    margin-bottom: 10px;
}

.admin_logging-log-text {
    font-size: 15px;
    line-height: 1.5;
    color: #E6E7EA;
}

.admin_logging-link {
    color: #389466;
    text-decoration: none;
    border-bottom: 1px dashed rgba(56,148,102,0.18);
}

.admin_logging-empty {
    text-align: center;
    color: rgba(255,255,255,0.3);
    padding: 26px;
    border-radius: 10px;
    background: rgba(255,255,255,0.01);
    border: 1px dashed rgba(255,255,255,0.02);
}

/* Preloader */
.admin_logging-preloader-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 36px 0;
}

.admin_logging-preloader {
    width: 64px;
    height: 64px;
    position: relative;
}

.admin_logging-preloader::before, {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
}

.admin_logging-preloader::before {
    border: 6px solid transparent;
    border-top-color: #389466;
    border-right-color: rgba(56,148,102,0.6);
    animation: admin_logging-rotateA 1.1s linear infinite;
}


@keyframes admin_logging-rotateA { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes admin_logging-rotateB { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.admin_logging-preloader-text {
    color: rgba(255,255,255,0.6);
    font-size: 14px;
}

@media (max-width: 600px) {
    .admin_logging-title { font-size: 20px; }
    .admin_logging-log-card { padding: 12px; }
    .admin_logging-log-text { font-size: 14px; }
}
</style>
