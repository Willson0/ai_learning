<script>
import adminnav from "@/components/adminnav.vue"
import axios from 'axios';
import config from "@/config.json"
import {marked} from "marked";
import {deepParse} from "@/utils.js";

export default {
    name: 'AdminStates',
    components: {
        adminnav,
    },
    data() {
        return {
            subjects: [],
            states: [],
            selected: null,
            filter: '',
            loadingStates: false,
            loadingSubjects: false,

            // modal / form
            showModal: false,
            isEditing: false,
            form: this.emptyForm(),
            saving: false,

            // delete confirm
            showConfirm: false,
            confirmTarget: null,

            // markdown editor
            simpleMdInstance: null,
        };
    },
    computed: {
        filteredStates() {
            const q = this.filter.trim().toLowerCase();
            if (!q) return this.states;
            return this.states.filter(s =>
                ((s.title || '').toLowerCase().includes(q)) ||
                ((s.description || '').toLowerCase().includes(q))
            );
        },
        hasExternalMd() {
            // we will initialize external editor if global SimpleMDE/EasyMDE is available
            return !!(window.SimpleMDE || window.EasyMDE);
        }
    },
    mounted() {
        this.fetchSubjects();
        this.fetchStates();
        // make overlay listen for ESC key (modal has tabindex)
        document.addEventListener('keydown', this.onKeyDown);
    },
    beforeDestroy() {
        document.removeEventListener('keydown', this.onKeyDown);
        this.destroyExternalMd();
    },
    methods: {
        deepParse,
        emptyForm() {
            return {
                id: null,
                subject_id: '',
                type: '',
                title: '',
                text: '',
                description: '',
                materials: { rutube: '', vk: '', youtube: '' }
            };
        },
        onKeyDown(e) {
            if (e.key === 'Escape') {
                if (this.showModal) this.closeModal();
                if (this.showConfirm) this.showConfirm = false;
            }
        },

        // --- API ---
        async fetchSubjects() {
            this.loadingSubjects = true;
            await axios.get(config.backend + "admin/subjects", {
                withCredentials: true
            }).then((response) => {
                this.subjects = response.data;
                console.log(this.subjects);
            }).catch((err) => {
                console.error(err);
                alert('Не удалось загрузить список предметов.');
            }).finally(() => {
                this.loadingSubjects = false;
            })
        },
        async fetchStates() {
            this.loadingStates = true;
            await axios.get(config.backend + "admin/states", {
                withCredentials: true
            }).then((response) => {
                this.states = deepParse(response.data);
            }).catch((err) => {
                console.error(err);
                alert('Не удалось загрузить статьи.');
            }).finally(() => {
                this.loadingStates = false;
            })
        },
        subjectName(id) {
            const s = this.subjects.find(x => x.id === id) || {};
            return s.name || '—';
        },

        // CRUD helpers
        openAdd() {
            this.isEditing = false;
            this.form = this.emptyForm();
            this.showModal = true;
            this.$nextTick(() => this.initExternalMd());
        },
        openEdit(item) {
            // ensure we have fresh object copy
            this.selected = item;
            this.isEditing = true;
            this.form = {
                id: item.id || null,
                subject_id: item.subject_id || '',
                type: item.type || '',
                title: item.title || '',
                text: item.text || '',
                description: item.description || '',
                materials: Object.assign({ rutube: '', vk: '', youtube: '' }, item.materials || {})
            };
            this.showModal = true;
            this.$nextTick(() => this.initExternalMd());
        },
        closeModal() {
            this.showModal = false;
            this.isEditing = false;
            this.destroyExternalMd();
        },

        confirmDelete(item) {
            this.confirmTarget = item;
            this.showConfirm = true;
        },

        async doDelete(item) {
            if (!item || !item.id) return;
            await axios.delete(config.backend + `admin/states/${item.id}`, {
                withCredentials: true,
            }).then((response) => {
                this.states = this.states.filter(s => s.id !== item.id);
                if (this.selected && this.selected.id === item.id) this.selected = null;
                this.showConfirm = false;
                this.confirmTarget = null;
            }).catch((error) => {
                console.error(error);
                alert('Не удалось удалить статью.');
            })
        },

        // Save (create or update)
        async save() {
            // ensure external editor content synced
            if (this.hasExternalMd && this.simpleMdInstance) {
                this.form.text = this.simpleMdInstance.value();
            }

            // basic validation
            if (!this.form.subject_id || !this.form.type || !this.form.title) {
                alert('Заполните обязательные поля: предмет, тип, заголовок.');
                return;
            }

            this.saving = true;
            try {
                const payload = {
                    subject_id: this.form.subject_id,
                    type: this.form.type,
                    title: this.form.title,
                    text: this.form.text,
                    description: this.form.description,
                    materials: this.form.materials
                };

                let res;
                if (this.isEditing && this.form.id) {
                    await axios.post(config.backend + `admin/states/${this.form.id}`, payload, {
                        withCredentials: true,
                    }).then((response) => {
                        this.states = deepParse(response.data);
                    })
                } else {
                    await axios.post(config.backend + `admin/states`, payload, {
                        withCredentials: true,
                    }).then((response) => {
                        this.states = deepParse(response.data);
                    })
                }
                this.selected = null;
                this.closeModal();
            } catch (err) {
                console.error(err);
                alert('Ошибка при сохранении.');
            } finally {
                this.saving = false;
            }
        },

        // destroy and init external md (SimpleMDE/EasyMDE)
        initExternalMd() {
            this.destroyExternalMd();
            if (!this.hasExternalMd) return;

            const El = this.$refs.externalMdEl;
            if (!El) return;

            const ctor = window.EasyMDE || window.SimpleMDE;
            try {
                this.simpleMdInstance = new ctor({
                    element: El,
                    autoDownloadFontAwesome: false,
                    spellChecker: false,
                    initialValue: this.form.text || '',
                    placeholder: "Напишите текст в Markdown..."
                });

                // Keep form.text in sync on change
                this.simpleMdInstance.codemirror.on('change', () => {
                    this.form.text = this.simpleMdInstance.value();
                });
            } catch (err) {
                console.warn('Не удалось инициализировать внешний MD редактор:', err);
                this.simpleMdInstance = null;
            }
        },
        destroyExternalMd() {
            if (this.simpleMdInstance) {
                try {
                    if (this.simpleMdInstance.toTextArea) this.simpleMdInstance.toTextArea();
                } catch (e) { /* */ }
                this.simpleMdInstance = null;
            }
        },

        // Minimal Markdown renderer fallback.
        // Если подключён полнофункциональный парсер на странице — используй его (внешний),
        // иначе используем упрощённый, безопасный конвертер (поддержка заголовков, bold/italic, links, code).
        renderMarkdown(md) {
            if (!md) return '';
            // If Marked, showdown, etc exist globally, prefer them:
            if (window.marked) {
                try { return window.marked.parse(md); } catch (e) { /* fallback */ }
            }

            return marked.parse(md);
        }
    }
};
</script>

<template>
    <adminnav>
        <div class="admin-states">
            <!-- Layout: sidebar list + panel -->
            <div class="main_container">
                <aside class="sidebar">
                    <div class="header">
                        <h2>Статьи</h2>
                        <button class="btn primary" @click="openAdd">+ Новая</button>
                    </div>

                    <input v-model="filter" class="search" placeholder="Поиск по заголовку / описанию" />

                    <div class="list">
                        <template v-if="loadingStates">
                            <div class="loading">Загрузка...</div>
                        </template>
                        <template v-else-if="filteredStates.length === 0">
                            <div class="empty">Нет статей</div>
                        </template>
                        <ul>
                            <li v-for="s in filteredStates" :key="s.id" :class="{active: selected && selected.id === s.id}">
                                <div class="item" @click="openEdit(s)">
                                    <div class="meta">
                                        <div class="title">{{ s.title || '(без заголовка)' }}</div>
                                        <div class="sub">{{ s.type }} — {{ subjectName(s.subject_id) }} </div>
                                    </div>
                                    <div class="actions">
                                        <button class="btn ghost" @click.stop="openEdit(s)">Ред.</button>
                                        <button class="btn danger" @click.stop="confirmDelete(s)">Удал.</button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </aside>

                <main class="panel">
                    <div v-if="!selected" class="empty-panel">
                        Выберите статью или нажмите «Новая» для создания.
                    </div>

                    <div v-else class="detail">
                        <div class="detail-header">
                            <h2>{{ selected.title || 'Новая статья' }}</h2>
                            <div class="detail-actions">
                                <button class="btn primary" @click="openEdit(selected)">Редактировать</button>
                                <button class="btn danger" @click="confirmDelete(selected)">Удалить</button>
                            </div>
                        </div>

                        <div class="detail-body">
                            <div class="row">
                                <label>Предмет:</label>
                                <div>{{ subjectName(selected.subject_id) }}</div>
                            </div>
                            <div class="row">
                                <label>Тип:</label>
                                <div>{{ selected.type }}</div>
                            </div>
                            <div class="row">
                                <label>Описание:</label>
                                <div>{{ selected.description }}</div>
                            </div>
                            <div class="row markdown-preview">
                                <label>Текст (предпросмотр):</label>
                                <div v-html="renderMarkdown(selected.text || '')"></div>
                            </div>

                            <div class="row">
                                <label>Материалы:</label>
                                <div class="materials">
                                    <div v-for="(v, k) in selected.materials || {}" :key="k" class="mat-row">
                                        <strong>{{ k }}:</strong> <span>{{ v }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>

            <!-- Modal: add / edit -->
            <div v-if="showModal" class="modal-overlay" @keydown.esc="closeModal" tabindex="0">
                <div class="modal" @click.stop>
                    <div class="modal-header">
                        <h3>{{ isEditing ? 'Редактировать статью' : 'Новая статья' }}</h3>
                        <button class="btn ghost" @click="closeModal">✕</button>
                    </div>

                    <div class="modal-body">
                        <form @submit.prevent="save">
                            <label>Предмет</label>
                            <select v-model="form.subject_id" required>
                                <option value="" disabled>Выберите предмет</option>
                                <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                            </select>

                            <label>Тип</label>
                            <select v-model="form.type" required>
                                <option value="" disabled>Выберите тип</option>
                                <option value="ege">ЕГЭ</option>
                                <option value="oge">ОГЭ</option>
                                <option value="vpr">ВПР</option>
                            </select>

                            <label>Заголовок</label>
                            <input v-model="form.title" required maxlength="250" />

                            <label>Короткое описание</label>
                            <input v-model="form.description" maxlength="350" />

                            <label>Материалы (ссылки)</label>
                            <div class="materials-edit">
                                <div class="mat-edit-row">
                                    <label>rutube</label>
                                    <input v-model="form.materials.rutube" placeholder="https://..." />
                                </div>
                                <div class="mat-edit-row">
                                    <label>vk</label>
                                    <input v-model="form.materials.vk" placeholder="https://..." />
                                </div>
                                <div class="mat-edit-row">
                                    <label>youtube</label>
                                    <input v-model="form.materials.youtube" placeholder="https://..." />
                                </div>
                            </div>

                            <label>Текст (Markdown)</label>

                            <!-- If external editor is available, host its element; else fallback textarea with preview -->
                            <div class="markdown-editor">
                                <textarea v-show="!hasExternalMd" v-model="form.text" rows="10" placeholder="Markdown..."></textarea>
                                <div v-show="hasExternalMd">
                                    <!-- element for external editor -->
                                    <textarea ref="externalMdEl"></textarea>
                                </div>

                                <div class="md-preview">
                                    <div class="md-preview-title">Предпросмотр</div>
                                    <div class="md-preview-body" v-html="renderMarkdown(form.text || '')"></div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn" @click="closeModal">Отмена</button>
                                <button type="submit" class="btn primary" :disabled="saving">{{ saving ? 'Сохраняю...' : 'Сохранить' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Confirm delete -->
            <div v-if="showConfirm" class="confirm-overlay">
                <div class="confirm">
                    <p>Удалить статью «{{ confirmTarget.title || '' }}»?</p>
                    <div class="confirm-actions">
                        <button class="btn" @click="showConfirm = false">Отмена</button>
                        <button class="btn danger" @click="doDelete(confirmTarget)">Удалить</button>
                    </div>
                </div>
            </div>
        </div>
    </adminnav>
</template>

<style scoped>
/* Palette */

/* Base */
.admin-states {
    color: var(--text);
    font-family: Inter, "Helvetica Neue", Arial, sans-serif;
    background: var(--bg);
    min-height: 100vh;
    padding: 20px;
    box-sizing: border-box;
    position: relative;
}

/* Container layout */
.main_container {
    display: flex;
    gap: 18px;
    align-items: stretch;
}

/* Sidebar */
.sidebar {
    width: 360px;
    background: linear-gradient(180deg, rgba(255,255,255,0.02), transparent);
    border-radius: 12px;
    padding: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.6);
    border: 1px solid rgba(255,255,255,0.03);
}

.header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}
.header h2 { margin:0; font-size:18px; }
.search {
    width:100%;
    margin-bottom:10px;
    padding:8px 10px;
    border-radius:8px;
    background: transparent;
    border: 1px solid rgba(255,255,255,0.06);
    color: var(--text);
    box-sizing: border-box;
}

/* list */
.list { max-height: 72vh; overflow:auto; padding-right:6px; }
.list ul { list-style:none; padding:0; margin:0; }
.list li { margin-bottom:8px; }
.item {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:10px;
    border-radius:10px;
    transition: background .12s;
    cursor: pointer;
}
.item:hover { background: rgba(255,255,255,0.02); }

.item .meta { text-align:left; }
.item .title { font-weight:600; }
.item .sub { font-size:12px; color:var(--muted); }

.actions { display:flex; gap:8px; align-items:center; }
.btn { padding:6px 10px; border-radius:8px; border: none; background:transparent; color:var(--text); cursor:pointer; }
.btn.ghost { border:1px solid rgba(255,255,255,0.04); }
.btn.primary { background: var(--accent); color: #fff; }
.btn.danger { background: var(--danger); color: #fff; }

/* active */
li.active .item { box-shadow: 0 6px 14px rgba(56,148,102,0.08); border:1px solid rgba(56,148,102,0.12); }

/* Panel */
.panel {
    flex:1;
    background: linear-gradient(180deg, rgba(255,255,255,0.018), transparent);
    border-radius: 12px;
    padding: 18px;
    min-height: 72vh;
    border: 1px solid rgba(255,255,255,0.03);
    box-shadow: 0 6px 18px rgba(0,0,0,0.5);
}

.empty-panel { color: var(--muted); font-size:16px; padding:30px; }

.detail-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.detail-header h2 { margin:0; }
.detail-body { margin-top:12px; }
.row { display:flex; gap:12px; padding:8px 0; align-items:flex-start; }
.row label { width:120px; color:var(--muted); font-size:14px; }
.markdown-preview { background: rgba(255,255,255,0.01); padding:12px; border-radius:8px; }

/* modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    display:flex;
    align-items:center;
    justify-content:center;
    background: rgba(0,0,0,0.6);
    z-index: 1100;
    overflow-y: auto;
}
.modal {
    width: 880px;
    max-width: calc(100% - 100px);
    background: var(--card);
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 18px 60px rgba(0,0,0,0.75);
    position:relative;
    top: 50px;
    margin-left: 120px;
}
.modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.modal-body form { display:flex; flex-direction:column; gap:10px; }
.modal-body label { color: var(--muted); font-size:13px; }
.modal-body input, .modal-body select, .modal-body textarea {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.04);
    padding:8px 10px;
    border-radius:8px;
    color:var(--text);
    outline:none;
}

/* markdown editor area */
.markdown-editor {
    display:flex; gap:12px;
}
.EasyMDEContainer {
    max-width: 60%;
}
.markdown-editor textarea { flex:1; min-height: 220px; resize:vertical; }
.markdown-editor>div {
    width: 50%;
}
.md-preview { width: 360px; background: rgba(255,255,255,0.015); border-radius:8px; padding:10px; overflow:auto; max-height:360px; }
.md-preview-title { font-size:13px; color:var(--muted); margin-bottom:6px; }
.md-preview-body { font-size:14px; line-height:1.45; color:var(--text); }

/* materials */
.materials-edit { display:grid; grid-template-columns: 1fr 1fr; gap:8px; }
.mat-edit-row { display:flex; flex-direction:column; gap:6px; }

/* modal footer */
.modal-footer { display:flex; justify-content:flex-end; gap:8px; margin-top:8px; }

/* confirm */
.confirm-overlay { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.5); z-index:1200; }
.confirm { background:var(--card); padding:20px; border-radius:10px; box-shadow:0 12px 40px rgba(0,0,0,0.5); }
.confirm-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:12px; }
</style>
