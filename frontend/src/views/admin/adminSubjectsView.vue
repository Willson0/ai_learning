<template>
    <adminnav>
        <div class="admin-subjects">
            <div class="header">
                <h1>Предметы</h1>
                <div class="controls">
                    <input v-model="search" placeholder="Поиск по названию" class="search" />
                    <button class="btn accent" @click="reload" :disabled="loading">Обновить</button>
                </div>
            </div>

            <div v-if="notice.text" :class="['notice', notice.type]">
                {{ notice.text }}
                <button class="close" @click="notice.text = ''">✕</button>
            </div>

            <div v-if="loading" class="loading">Загрузка...</div>
            <div v-if="!loading && filteredSubjects.length === 0" class="empty">Предметы не найдены.</div>

            <div class="grid">
                <transition-group name="cards" tag="div" class="cards">
                    <div class="card" v-for="(subject, idx) in filteredSubjects" :key="subject.id ?? ('new-'+idx)">
                        <div class="card-head">
                            <div class="title">
                                <strong>{{ subject.name || 'Без названия' }}</strong>
                                <small class="id">#{{ subject.id ?? '—' }}</small>
                            </div>
                            <div class="card-actions">
                                <button class="icon-btn" @click="toggleExpand(subject)">{{ subject._expanded ? 'Свернуть' : 'Редактировать' }}</button>
                            </div>
                        </div>

                        <!-- Preview: user-friendly display (НЕ JSON) -->
                        <div class="card-body" v-if="!subject._expanded">
                            <div class="exams-list">
                                <div v-for="key in requiredKeys" :key="key" class="exam-row">
                                    <div class="exam-name">{{ examLabel(key) }}</div>
                                    <div class="exam-time">{{ subject._sdObj[key]?.time ?? 0 }} мин</div>
                                    <div class="exam-desc">{{ subject._sdObj[key]?.description || '—' }}</div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button class="btn" @click="subject._expanded = true">Открыть</button>
                            </div>
                        </div>

                        <!-- Structured editor -->
                        <transition name="expand">
                            <div v-if="subject._expanded" class="editor">
                                <div class="editor-grid">
                                    <div v-for="key in requiredKeys" :key="key" class="exam-block">
                                        <h3>{{ examLabel(key) }}</h3>
                                        <label class="field">
                                            Время (мин)
                                            <input type="number" min="0" v-model.number="subject._sdObj[key].time" />
                                        </label>
                                        <label class="field">
                                            Описание
                                            <textarea rows="3" v-model="subject._sdObj[key].description" placeholder="Краткое описание экзамена"></textarea>
                                        </label>
                                    </div>
                                </div>

                                <div class="editor-actions">
<!--                                    <div class="left">-->
<!--                                        <label class="field-inline">-->
<!--                                            Название-->
<!--                                            <input disabled v-model="subject.name" placeholder="Например: Русский язык" />-->
<!--                                        </label>-->
<!--                                    </div>-->
                                    <div class="right">
                                        <button class="btn" @click="subject._expanded = false">Закрыть</button>
                                        <button class="btn accent" :disabled="subject._saving" @click="saveSubject(subject)">
                                            <span v-if="subject._saving">Сохраняем...</span>
                                            <span v-else>Сохранить</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </transition-group>
            </div>

            <!-- Add new subject -->
<!--            <div class="add-card card">-->
<!--                <div class="card-head"><div class="title"><strong>Добавить предмет</strong></div></div>-->
<!--                <div class="card-body">-->
<!--                    <label class="field">Название-->
<!--                        <input v-model="newSubject.name" placeholder="Например: Русский язык" />-->
<!--                    </label>-->

<!--                    <div class="editor-grid add-grid">-->
<!--                        <div v-for="key in requiredKeys" :key="key" class="exam-block">-->
<!--                            <h3>{{ examLabel(key) }}</h3>-->
<!--                            <label class="field">-->
<!--                                Время (мин)-->
<!--                                <input type="number" min="0" v-model.number="newSubject.state_description[key].time" />-->
<!--                            </label>-->
<!--                            <label class="field">-->
<!--                                Описание-->
<!--                                <textarea rows="2" v-model="newSubject.state_description[key].description"></textarea>-->
<!--                            </label>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="card-footer">-->
<!--                    <button class="btn" @click="resetNew">Сбросить</button>-->
<!--                    <button class="btn accent" :disabled="creating" @click="createSubject">-->
<!--                        <span v-if="creating">Создаём...</span>-->
<!--                        <span v-else>Создать предмет</span>-->
<!--                    </button>-->
<!--                </div>-->
<!--            </div>-->
        </div>
    </adminnav>
</template>

<script>
import axios from 'axios';
import config from '@/config.json';
import adminnav from "@/components/adminnav.vue";

export default {
    name: 'AdminSubjects',
    components: {adminnav},
    data() {
        return {
            subjects: [],
            loading: false,
            creating: false,
            newSubject: this.emptyNewSubject(),
            notice: { text: '', type: '' },
            search: '',
            requiredKeys: ['ege', 'oge', 'vpr'],
        };
    },
    computed: {
        backend() {
            return (config.backend || '').replace(/\/?$/, '/') ;
        },
        filteredSubjects() {
            if (!this.search) return this.subjects;
            const q = this.search.toLowerCase();
            return this.subjects.filter(s => (s.name || '').toLowerCase().includes(q));
        },
    },
    mounted() {
        axios.defaults.withCredentials = true;
        this.loadSubjects();
    },
    methods: {
        examLabel(key) {
            const map = { ege: 'ЕГЭ', oge: 'ОГЭ', vpr: 'ВПР' };
            return map[key] || key.toUpperCase();
        },
        emptyNewSubject() {
            return {
                name: '',
                state_description: {
                    ege: { time: 235, description: '' },
                    oge: { time: 120, description: '' },
                    vpr: { time: 90, description: '' },
                },
            };
        },
        async loadSubjects() {
            this.loading = true;
            this.notice = { text: '', type: '' };
            try {
                const url = this.backend + 'admin/subjects';
                const res = await axios.get(url);
                this.subjects = (res.data || []).map(s => this.enrichSubject(s));
            } catch (e) {
                this.notice = { text: 'Ошибка загрузки предметов: ' + (e.response?.data?.message || e.message || e), type: 'error' };
            } finally {
                this.loading = false;
            }
        },
        enrichSubject(s) {
            // clone safer
            const subj = Object.assign({}, s);
            let sd = {};
            try {
                sd = typeof subj.state_description === 'string' ? JSON.parse(subj.state_description) : (subj.state_description ? JSON.parse(JSON.stringify(subj.state_description)) : {});
            } catch {
                sd = subj.state_description || {};
            }
            // ensure keys
            this.requiredKeys.forEach(k => {
                if (!sd[k]) sd[k] = { time: 0, description: '' };
                else {
                    sd[k].time = Number.isFinite(sd[k].time) ? sd[k].time : Number(sd[k].time) || 0;
                    sd[k].description = typeof sd[k].description === 'string' ? sd[k].description : String(sd[k].description || '');
                }
            });

            subj._sdObj = sd;
            subj._expanded = false;
            subj._saving = false;
            return subj;
        },
        toggleExpand(subject) {
            subject._expanded = !subject._expanded;
        },
        validateSdObj(sdObj) {
            for (const k of this.requiredKeys) {
                const item = sdObj[k];
                if (!item) return `Отсутствует раздел ${k}`;
                if (!Number.isFinite(item.time) || item.time < 0) return `В разделе ${k} неверное время`;
                if (typeof item.description !== 'string') return `В разделе ${k} неверное описание`;
            }
            return null;
        },
        async saveSubject(subject) {
            subject._saving = true;
            this.notice.text = '';
            try {
                const err = this.validateSdObj(subject._sdObj);
                if (err) { this.notice = { text: err, type: 'error' }; subject._saving = false; return; }

                const payload = {
                    id: subject.id,
                    name: subject.name,
                    state_description: subject._sdObj, // объект; если нужен string -> JSON.stringify(...)
                };

                const idPart = subject.id || 'new';
                const url = this.backend + 'admin/subjects/' + idPart;
                const res = await axios.post(url, payload);

                if (Array.isArray(res.data)) {
                    this.subjects = res.data.map(s => this.enrichSubject(s));
                } else if (res.data && typeof res.data === 'object') {
                    // replace or add
                    const enriched = this.enrichSubject(res.data);
                    const idx = this.subjects.findIndex(s => s.id === enriched.id);
                    if (idx >= 0) {
                        // replace immutably to keep reactivity without this.$set
                        this.subjects.splice(idx, 1, enriched);
                    } else {
                        this.subjects.unshift(enriched);
                    }
                } else {
                    await this.loadSubjects();
                }
                this.notice = { text: 'Сохранено успешно', type: 'success' };
                subject._expanded = false;
            } catch (e) {
                this.notice = { text: 'Ошибка сохранения: ' + (e.response?.data?.message || e.message || e), type: 'error' };
            } finally {
                subject._saving = false;
            }
        },
        async createSubject() {
            this.creating = true;
            this.notice.text = '';
            try {
                if (!this.newSubject.name || !this.newSubject.name.trim()) {
                    this.notice = { text: 'Введите название предмета', type: 'error' };
                    this.creating = false;
                    return;
                }
                const err = this.validateSdObj(this.newSubject.state_description);
                if (err) { this.notice = { text: err, type: 'error' }; this.creating = false; return; }

                const url = this.backend + 'admin/subjects/' + (this.newSubject.id || 'new');
                const payload = {
                    name: this.newSubject.name,
                    state_description: this.newSubject.state_description,
                };
                const res = await axios.post(url, payload);
                if (Array.isArray(res.data)) {
                    this.subjects = res.data.map(s => this.enrichSubject(s));
                } else if (res.data && typeof res.data === 'object') {
                    this.subjects.unshift(this.enrichSubject(res.data));
                } else {
                    await this.loadSubjects();
                }
                this.notice = { text: 'Предмет создан', type: 'success' };
                this.resetNew();
            } catch (e) {
                this.notice = { text: 'Ошибка создания: ' + (e.response?.data?.message || e.message || e), type: 'error' };
            } finally {
                this.creating = false;
            }
        },
        resetNew() {
            this.newSubject = this.emptyNewSubject();
        },
        async reload() {
            await this.loadSubjects();
            this.notice = { text: 'Данные обновлены', type: 'info' };
        }
    }
};
</script>

<style scoped>
/* -- стиль в темной палитре, основной фон #12121C, акцент #389466 -- */
.admin-subjects {
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
    color: #e6eef6;
    background: linear-gradient(180deg, #0f0f16 0%, #12121C 100%);
    padding: 20px;
    border-radius: 10px;
}

/* header */
.header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
.header h1 { margin:0; font-size:20px; color:#fff; }
.controls { display:flex; gap:8px; align-items:center; }
.search {
    background:#0b0b12; border:1px solid rgba(255,255,255,0.06); color:#dfeff1; padding:8px 10px; border-radius:8px; min-width:200px;
}

/* buttons */
.btn { background:transparent; border:1px solid rgba(255,255,255,0.06); color:#cfe8e0; padding:8px 12px; border-radius:8px; cursor:pointer; }
.btn.accent { background:linear-gradient(90deg,#389466,#2f8c5a); border:none; color:#fff; box-shadow:0 6px 18px rgba(56,148,102,0.14); }
.btn:hover { transform:translateY(-1px); }
.btn[disabled] { opacity:0.5; cursor:default; transform:none; }

/* notice */
.notice { display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:8px; margin-bottom:10px; }
.notice.info { background: rgba(56,148,102,0.08); border:1px solid rgba(56,148,102,0.12); color:#bfe7cf; }
.notice.success { background: rgba(56,148,102,0.12); border:1px solid rgba(56,148,102,0.18); color:#d6f6e3; }
.notice.error { background: rgba(255,50,50,0.07); border:1px solid rgba(255,50,50,0.16); color:#ffcfcf; }
.notice .close { background:transparent; border:none; color:inherit; cursor:pointer; }

/* cards grid */
.grid { margin-top:10px; }
.cards { display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:12px; }
.card {
    background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border:1px solid rgba(255,255,255,0.04); padding:12px; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.45); display:flex; flex-direction:column;
}
.card-head { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px; }
.title { display:flex; gap:8px; align-items:baseline; }
.title small.id { color: rgba(255,255,255,0.45); font-size:12px; }

/* preview */
.exams-list { display:flex; flex-direction:column; gap:8px; }
.exam-row { display:grid; grid-template-columns: 120px 80px 1fr; gap:8px; align-items:start; background: rgba(0,0,0,0.18); padding:10px; border-radius:8px; }
.exam-name { font-weight:600; color:#fff; }
.exam-time { color: #cfe8e0; font-size:13px; }
.exam-desc { color:#dfeff1; font-size:13px; }

/* editor */
.editor { margin-top:10px; }
.editor-grid { display:grid; grid-template-columns:1fr; gap:12px; }
.exam-block { background: rgba(255,255,255,0.02); padding:8px; border-radius:8px; }
.exam-block h3 { margin:0 0 6px 0; font-size:14px; color:#dfeff1; }
.field { display:flex; flex-direction:column; gap:6px; margin-bottom:6px; }
.field input, .field textarea, .field-inline input {
    background: rgba(0,0,0,0.22); color:#e8f6ef; border:1px solid rgba(255,255,255,0.04); padding:8px; border-radius:6px;
}
.field-inline { display:flex; gap:8px; align-items:center; }

/* footer */
.card-footer, .editor-actions { display:flex; justify-content:space-between; align-items:center; margin-top:10px; gap:8px; }
.card-footer { padding-top:8px; border-top:1px dashed rgba(255,255,255,0.02); }

.editor-actions {
    flex-direction: column;
    gap: 10px;
}

/* add new */
.add-card { margin-top:18px; }

/* misc */
.loading, .empty { color: rgba(255,255,255,0.6); padding:20px; text-align:center; }
.icon-btn { background:transparent; border:none; color:#dfeff1; cursor:pointer; padding:6px 8px; border-radius:8px; }
.expand-enter-active, .expand-leave-active { transition: all .18s ease; }
.expand-enter-from { opacity:0; transform: translateY(-6px); }
.expand-enter-to { opacity:1; transform:none; }
.expand-leave-to { opacity:0; transform: translateY(-6px); }
</style>
