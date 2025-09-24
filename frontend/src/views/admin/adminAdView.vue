<script>
import adminnav from "@/components/adminnav.vue";
import axios from 'axios';
import config from "@/config.json";

export default {
    name: 'AdAdmin',
    components: {adminnav},
    data() {
        return {
            config: config,
            ads: [],
            loading: false,
            showForm: false,
            isEditing: false,
            form: {
                id: null,
                link: '',
                previewFromServer: null,
            },
            preview: null,
            file: null,
            errors: {},
            submitting: false,
            showConfirm: false,
            toDelete: null,
            lightboxSrc: null,
            placeholder: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="150"><rect width="100%" height="100%" fill="%23181824"/><text x="50%" y="50%" fill="%23999" font-size="14" text-anchor="middle" dy=".35em">Нет изображения</text></svg>'
        };
    },
    mounted() {
        axios.defaults.withCredentials = true;
        this.fetchAds();
    },
    methods: {
        async fetchAds() {
            this.loading = true;
            try {
                const { data } = await axios.get(config.backend + 'admin/ads');
                this.ads = data;
            } catch (e) {
                console.error(e);
                alert('Ошибка при загрузке объявлений');
            } finally {
                this.loading = false;
            }
        },

        openCreate() {
            this.resetForm();
            this.showForm = true;
            this.isEditing = false;
        },
        openEdit(ad) {
            this.resetForm();
            this.isEditing = true;
            this.form.id = ad.id;
            this.form.link = ad.link;
            this.form.previewFromServer = config.storage + ad.picture || null;
            this.showForm = true;
        },
        closeForm() {
            if (this.submitting) return;
            this.showForm = false;
            this.resetForm();
        },

        onFileChange(e) {
            const file = e.target.files[0];
            this.errors.picture = null;
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                this.errors.picture = 'Файл должен быть изображением';
                this.$refs.fileInput.value = null;
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.errors.picture = 'Файл слишком большой (макс 2 МБ)';
                this.$refs.fileInput.value = null;
                return;
            }
            this.file = file;
            const reader = new FileReader();
            reader.onload = () => {
                this.preview = reader.result;
            };
            reader.readAsDataURL(file);
        },

        clearFile() {
            this.file = null;
            this.preview = null;
            this.form.previewFromServer = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = null;
        },

        validate() {
            this.errors = {};
            if (!this.form.link || !/^https:\/\/.+/i.test(this.form.link)) {
                this.errors.link = 'Ссылка обязана начинаться с https://';
            }
            if (!this.isEditing && !this.file) {
                this.errors.picture = 'Добавьте изображение';
            }
            return Object.keys(this.errors).length === 0;
        },

        async submitForm() {
            if (!this.validate()) return;
            this.submitting = true;

            try {
                const payload = new FormData();
                payload.append('link', this.form.link);
                if (this.file) payload.append('picture', this.file);

                if (this.isEditing) {
                    await axios.post(config.backend + 'admin/ads/' + this.form.id, payload);
                } else {
                    await axios.post(config.backend + 'admin/ads', payload);
                }

                await this.fetchAds();
                this.closeForm();
            } catch (e) {
                console.error(e);
                alert('Ошибка при сохранении: ' + (e.message || ''));
            } finally {
                this.submitting = false;
            }
        },

        confirmDelete(ad) {
            this.toDelete = ad;
            this.showConfirm = true;
        },

        async deleteAd() {
            if (!this.toDelete) return;
            try {
                await axios.delete(config.backend + 'admin/ads/' + this.toDelete.id);
                this.ads = this.ads.filter(a => a.id !== this.toDelete.id);
                this.showConfirm = false;
                this.toDelete = null;
            } catch (e) {
                console.error(e);
                alert('Ошибка при удалении');
            }
        },

        previewImage(ad) {
            this.lightboxSrc = config.storage + ad.picture || this.placeholder;
        },

        resetForm() {
            this.form = { id: null, link: '', previewFromServer: null };
            this.preview = null;
            this.file = null;
            this.errors = {};
            this.submitting = false;
            if (this.$refs.fileInput) this.$refs.fileInput.value = null;
        }
    }
};
</script>

<template>
    <adminnav>
        <div class="ad-admin">
            <header class="header">
                <h1>Управление рекламой</h1>
                <button class="btn btn-add" @click="openCreate">Добавить рекламу</button>
            </header>

            <section class="table-wrap">
                <table class="ads-table">
                    <thead>
                    <tr>
                        <th>Превью</th>
                        <th>Ссылка</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="loading">
                        <td colspan="3" class="center">Загрузка...</td>
                    </tr>
                    <tr v-for="ad in ads" :key="ad.id">
                        <td class="preview-cell">
                            <div class="thumb" @click="previewImage(ad)">
                                <img :src="config.storage + ad.picture || placeholder" :alt="`ad-${ad.id}`" />
                            </div>
                        </td>
                        <td class="link-cell">
                            <a :href="ad.link" target="_blank" rel="noopener">{{ ad.link }}</a>
                        </td>
                        <td class="actions-cell">
                            <button class="btn btn-edit" @click="openEdit(ad)">Редактировать</button>
                            <button class="btn btn-delete" @click="confirmDelete(ad)">Удалить</button>
                        </td>
                    </tr>
                    <tr v-if="!loading && ads.length===0">
                        <td colspan="3" class="center muted">Записей нет. Нажмите «Добавить рекламу».</td>
                    </tr>
                    </tbody>
                </table>
            </section>

            <!-- Modal: Create / Edit -->
            <div class="modal" v-if="showForm">
                <div class="modal-backdrop" @click="closeForm"></div>
                <div class="modal-card" role="dialog" aria-modal="true">
                    <header class="modal-header">
                        <h2>{{ isEditing ? 'Редактировать рекламу' : 'Добавить рекламу' }}</h2>
                        <button class="close" @click="closeForm">✕</button>
                    </header>

                    <form @submit.prevent="submitForm" class="modal-body">
                        <label class="field">
                            <span>Ссылка (обязательно, с https://)</span>
                            <input v-model="form.link" type="url" placeholder="https://" required />
                            <small v-if="errors.link" class="error">{{ errors.link }}</small>
                        </label>

                        <label class="field">
                            <span>Изображение</span>
                            <div class="file-row">
                                <div class="img-preview">
                                    <img :src="preview || form.previewFromServer || placeholder" alt="preview" />
                                </div>

                                <div class="file-controls">
                                    <input ref="fileInput" type="file" accept="image/*" @change="onFileChange" />
                                    <button type="button" class="btn btn-clear" v-if="preview || form.previewFromServer" @click="clearFile">Удалить файл</button>
                                    <small class="hint">Рекомендуемый размер: 1200×400, формат: JPG/PNG, не больше 2 МБ</small>
                                </div>
                            </div>
                            <small v-if="errors.picture" class="error">{{ errors.picture }}</small>
                        </label>

                        <footer class="modal-footer">
                            <button type="button" class="btn btn-cancel" @click="closeForm">Отмена</button>
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                {{ submitting ? (isEditing ? 'Сохраняю...' : 'Создаю...') : (isEditing ? 'Сохранить' : 'Создать') }}
                            </button>
                        </footer>
                    </form>
                </div>
            </div>

            <!-- Confirm Delete -->
            <div class="confirm" v-if="showConfirm">
                <div class="confirm-backdrop" @click="showConfirm = false"></div>
                <div class="confirm-card">
                    <p>Удалить эту рекламную запись?</p>
                    <div class="confirm-actions">
                        <button class="btn btn-cancel" @click="showConfirm = false">Отмена</button>
                        <button class="btn btn-delete" @click="deleteAd">Удалить</button>
                    </div>
                </div>
            </div>

            <!-- Image Lightbox -->
            <div class="lightbox" v-if="lightboxSrc" @click="lightboxSrc = null">
                <img :src="lightboxSrc" alt="preview large" />
            </div>
        </div>
    </adminnav>
</template>

<style scoped>
.ad-admin {
    --bg: #12121C;
    --accent: #389466;
    color: #e8eef0;
    background: var(--bg);
    min-height: 70vh;
    padding: 20px;
    font-family: Inter, Roboto, Arial, sans-serif;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}
.header h1 {
    margin: 0;
    font-size: 20px;
    letter-spacing: 0.2px;
}
.btn {
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}
.btn-add { background: linear-gradient(90deg,var(--accent), #2aa05b); color: white; }
.btn-edit { background: transparent; border: 1px solid rgba(255,255,255,0.06); color: #cfeee0; padding:6px 10px; margin-right:8px; }
.btn-delete { background: transparent; border: 1px solid rgba(255,0,0,0.12); color: #ff8a8a; padding:6px 10px; }
.btn-primary { background: var(--accent); color: #071009; }
.btn-cancel { background: transparent; border: 1px solid rgba(255,255,255,0.06); color: #cfcfcf; }
.btn-clear { background: transparent; color: #cfcfcf; border: 1px dashed rgba(255,255,255,0.06); }
.table-wrap { background: rgba(255,255,255,0.02); padding: 12px; border-radius: 12px; }
.ads-table { width: 100%; border-collapse: collapse; color: #dbeee2; }
.ads-table th { text-align: left; padding: 10px; font-size: 13px; color: #9fbfb2; }
.ads-table td { padding: 12px; border-top: 1px solid rgba(255,255,255,0.02); vertical-align: middle; }
.preview-cell .thumb { width: 220px; max-width: 35vw; height: 70px; display:flex; align-items:center; justify-content:center; overflow:hidden; border-radius:8px; background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.2)); cursor: pointer; }
.preview-cell img { height:100%; width:auto; }
.link-cell a { color: var(--accent); word-break: break-all; }
.actions-cell { display:flex; gap:8px; }
.center { text-align:center; padding: 28px; color: #9aa7a0; }
.muted { color: #7b847f; }

/* Modal */
.modal { position: fixed; inset: 0; z-index: 60; display:flex; align-items:center; justify-content:center; }
.modal-backdrop { position:absolute; inset:0; background: rgba(2,2,6,0.6); }
.modal-card { position:relative; width:780px; max-width:95%; background: linear-gradient(180deg, #0f1116, #15161c); border-radius:12px; box-shadow: 0 8px 30px rgba(0,0,0,0.7); overflow:hidden; }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid rgba(255,255,255,0.02); }
.modal-header h2 { margin:0; font-size:16px; }
.modal-body { padding: 16px 18px; display:flex; flex-direction:column; gap:12px; }
.field span { display:block; color:#abbcbc; margin-bottom:6px; }
.field input[type="url"] { width:100%; padding:10px; border-radius:8px; background: rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.03); color: #e8efe9; }
.file-row { display:flex; gap:12px; align-items:flex-start; }
.img-preview img { width:260px; max-width:35vw; height:90px; object-fit:cover; border-radius:8px; border:1px solid rgba(255,255,255,0.03); }
.file-controls { display:flex; flex-direction:column; gap:8px; }
.hint { color:#7d9088; font-size:12px; }
.error { color:#ff8a8a; font-size:13px; }
.modal-footer { display:flex; justify-content:flex-end; gap:10px; padding-top:6px; }
.close { background:transparent; border:none; color:#9fbfb2; font-size:18px; cursor:pointer; }

/* Confirm */
.confirm { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; z-index:70; }
.confirm-backdrop { position:absolute; inset:0; background:rgba(2,2,6,0.6); }
.confirm-card { position:relative; background:#0f1116; padding:18px; border-radius:10px; box-shadow:0 8px 30px rgba(0,0,0,0.7); z-index:71; }
.confirm-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:12px; }

/* Lightbox */
.lightbox { position:fixed; inset:0; background: rgba(1,1,2,0.85); display:flex; align-items:center; justify-content:center; z-index:80; }
.lightbox img { max-width:90vw; max-height:90vh; border-radius:8px; }

/* Responsive */
@media (max-width:720px) {
    .preview-cell .thumb { height:56px; }
    .img-preview img { width:180px; height:72px; }
}
</style>
