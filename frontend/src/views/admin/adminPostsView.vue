<script>
import adminnav from '@/components/adminnav.vue';
import {endLoading} from "@/utils.js";
import {removeLoading} from "@/assets/admin.js";
import axios from "axios";
import config from "@/config.json";
export default {
    name: 'AdminNewsPanel',
    data() {
        return {
            newsList: [],
            categories: [],
            newCategoryName: '',
            categoriesById: {},
            showEditor: false,
            editingNews: this.emptyNews(),
            dragOver: false,
            config: config
        }
    },
    components: {
        adminnav
    },
    async mounted () {
        this.fetchData();
    },
    methods: {
        emptyNews() {
            return {
                id: null,
                title: '',
                category_id: '',
                image: '',
                link: ''
            };
        },
        async fetchData () {
            await axios.get(config.backend + "admin/news", {
                withCredentials: true,
            }).then((response) => {
                this.categories = response.data.categories;
                this.newsList = response.data.news;
                removeLoading();

                this.mapCategories();
            })
        },
        mapCategories() {
            this.categoriesById = {};
            for(const cat of this.categories) this.categoriesById[cat.id]=cat;
        },
        async addCategory() {
            let name = (this.newCategoryName || '').trim();
            if(!name) return;

            await axios.post(config.backend + "admin/news/category", {
                name: name,
            }, {
                withCredentials: true,
            }).then((response) => {
                this.categories = response.data.categories;
                this.newCategoryName = '';
                this.mapCategories();
            }).catch((error) => {
                alert (error.response.data.message ?? error.message);
            });
        },
        updateCategory(cat) {
            if(!cat.name.trim()) return;

            axios.post(config.backend + "admin/news/category/" + cat.id, {
                name: cat.name,
            }, {
                withCredentials: true,
            }).then((response) => {
                this.categories = response.data.categories;
                this.mapCategories();
            }).catch((error) => {
                alert (
                    error.response.data.message ??
                    error.message
                );
            });
        },
        async deleteCategory(id) {
            if(!confirm(`Удалить категорию "${this.categories.find(a => a.id === id).name}"?`)) return;

            await axios.delete(config.backend + "admin/news/category/" + id, {
                withCredentials: true,
            }).then((response) => {
                this.categories = response.data.categories;
                this.mapCategories();
            }).catch((error) => {
                alert (error.response.data.message ?? error.message);
            });
        },
        async deleteNews(id) {
            if(!confirm(`Вы действительно хотите удалить "${this.newsList.find(a => a.id === id).title}"?`)) return;
            await axios.delete(config.backend + "admin/news/" + id, {
                withCredentials: true,
            }).then((response) => {
                this.newsList = response.data.news;
            }).catch((error) => {
                alert (error.response.data.message ?? error.message);
            });
        },
        openEditor(news) {
            this.editingNews = news
                ? {...news}
                : this.emptyNews();
            this.showEditor = true;
        },
        cancelEditor() {
            this.editingNews = this.emptyNews();
            this.showEditor = false;
            this.dragOver = false;
        },
        async saveNews() {
            if (this.editingNews.id) {
                let fd = new FormData();
                for(let key in this.editingNews) {
                    if (key === "image_file") fd.append("image", this.editingNews[key]);
                    else if (key === "image") continue;
                    else if (this.editingNews[key] !== this.newsList.find(a => a.id === this.editingNews.id)[key]) fd.append(key, this.editingNews[key]);
                }
                await axios.post(config.backend + "admin/news/" + this.editingNews.id, fd, {
                    withCredentials: true,
                }).then((response) => {
                    this.newsList = response.data.news;
                    this.cancelEditor();
                }).catch((error) => {
                    alert (
                        error.response.data.message ??
                        error.message
                    );
                });
            } else {
                let fd = new FormData();
                for(let key in this.editingNews) {
                    if (key === "image_file") fd.append("image", this.editingNews[key]);
                    else if (key === "image") continue;
                    else fd.append(key, this.editingNews[key]);
                }
                await axios.post(config.backend + "admin/news", fd, {
                    withCredentials: true,
                }).then((response) => {
                    this.newsList = response.data.news;
                    this.cancelEditor();
                }).catch((error) => {
                    alert (
                        error.response.data.message ??
                        error.message
                    );
                });
            }
        },
        // ========== IMAGE UPLOAD ===========
        async onFileSelect(e) {
            let file = e.target.files[0];
            if (!file) return;
            await this.handleImageUpload(file);
            this.$refs.imgInp.value = '';
        },
        async onFileDrop(e) {
            let file = e.dataTransfer.files[0];
            if (!file) return;
            await this.handleImageUpload(file);
            this.dragOver = false;
        },
        async handleImageUpload(file) {
            this.editingNews.image_file = file;

            let reader = new FileReader();
            reader.onload = e => this.editingNews.image = e.target.result;
            reader.readAsDataURL(file);
        },
        removeImage() {
            this.editingNews.image = '';
        }
    }
}
</script>

<template>
    <adminnav>
        <div class="admin-news">

            <!-- Категории Новости — Управление -->
            <div class="news-categories-panel">
                <h2>Категории новостей</h2>
                <div class="categories-list">
                    <div v-for="cat in categories" :key="cat.id" class="cat-item">
                        <input v-model="cat.name" @blur="updateCategory(cat)" class="cat-input" />
                        <button class="btn-icon danger" @click="deleteCategory(cat.id)" title="Delete">
                            ✕
                        </button>
                    </div>
                    <div class="cat-item add-cat">
                        <input v-model="newCategoryName" placeholder="Новая категория..." class="cat-input" @keydown.enter="addCategory" />
                        <button class="btn-icon" @click="addCategory" title="Add">+</button>
                    </div>
                </div>
            </div>


            <!-- Новости — Управление -->
            <div class="news-list-panel">
                <h2>Новости</h2>
                <button class="main-btn" @click="openEditor()">+ Добавить новость</button>
                <div class="news-list">
                    <div
                        v-for="news in newsList.sort((a,b) => b.id - a.id)"
                        :key="news.id"
                        class="news-card"
                        @click="openEditor(news)"
                    >
                        <div class="news-card-img" :style="news.image ? 'background-image:url(' + (news.image.startsWith('https') ? news.image : config.storage + news.image) + ')' : ''"></div>
                        <div class="news-card-body">
                            <div class="news-title">{{ news.title }}</div>
                            <div class="news-cat">
                  <span v-if="categoriesById[news.category_id]">
                    {{ categoriesById[news.category_id].name }}
                  </span>
                            </div>
                            <div class="news-link"><a :href="news.link" target="_blank" @click.stop>Link</a></div>
                        </div>
                        <button class="btn-icon edit" @click.stop="openEditor(news)" title="Edit">✎</button>
                        <button class="btn-icon danger" @click.stop="deleteNews(news.id)" title="Delete">✕</button>
                    </div>
                </div>
            </div>

            <!-- Модальное окно для создания/редактирования новости -->
            <div v-if="showEditor" class="modal-bg" @mousedown.self="cancelEditor">
                <div class="modal">
                    <div class="modal-header">
                        <span>{{ editingNews.id ? 'Изменить новость' : 'Добавить новость' }}</span>
                        <button class="btn-icon danger" @click="cancelEditor">✕</button>
                    </div>
                    <form @submit.prevent="saveNews" class="editor-form">
                        <div class="image-upload-block"
                             @dragover.prevent
                             @drop.prevent="onFileDrop"
                             :class="{drophover: dragOver}">
                            <div v-if="editingNews.image" class="image-preview">
                                <img :src="(editingNews.image.startsWith('https') || editingNews.image.startsWith('data:image/') ? editingNews.image : config.storage + editingNews.image)" alt="news image" />
                                <button class="btn-icon danger img-remove" title="Remove image"
                                        @click.prevent="removeImage">✕</button>
                            </div>
                            <div v-else class="img-placeholder" @click="$refs.imgInp.click()">
                                <span>Drag & drop image here<br>or <b>click to select</b></span>
                            </div>
                            <input type="file"
                                   ref="imgInp"
                                   style="display:none"
                                   @change="onFileSelect"
                                   accept="image/*"
                            />
                        </div>

                        <!-- Title -->
                        <div class="input-group">
                            <label>Заголовок</label>
                            <input v-model="editingNews.title" required maxlength="200" />
                        </div>

                        <!-- Category -->
                        <div class="input-group">
                            <label>Категория</label>
                            <select v-model="editingNews.category_id" required>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <!-- Link -->
                        <div class="input-group">
                            <label>Ссылка</label>
                            <input v-model="editingNews.link" type="url" required maxlength="200" />
                        </div>

                        <div class="btn-row">
                            <button class="main-btn" type="submit">
                                {{ editingNews.id ? 'Обновить' : 'Создать' }}
                            </button>
                            <button class="main-btn outline" type="button" @click="cancelEditor">Отменить</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </adminnav>
</template>

<style scoped>
.admin-news {
    background: #12121C;
    color: #fff;
    padding: 32px;
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    min-height: 100vh;
}
h2 {
    color: #fff;
    margin: 0 0 16px;
    font-weight: 600;
    letter-spacing: 1px;
}
.news-categories-panel, .news-list-panel {
    background: #181828;
    border-radius: 18px;
    padding: 32px 28px 20px 28px;
    margin-bottom: 32px;
    box-shadow: 0 3px 18px #0004;
    margin-left: auto;
    margin-right: auto;
}

.categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    align-items: center;
}
.cat-item {
    background: #212130;
    border-radius: 8px;
    display: flex;
    align-items: center;
    padding: 6px 8px;
    gap: 6px;
    margin-bottom: 5px;
}
.add-cat {
    background: transparent;
}
.cat-input {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 15px;
    padding: 1.5px 5px;
    width: 110px;
    outline: none;
    border-bottom: 1.5px solid #38946640;
}
.cat-input:focus{
    border-color: #389466;
}
.btn-icon {
    background: transparent;
    border: none;
    color: #389466;
    font-size: 15px;
    cursor: pointer;
    padding: 2px 8px;
    border-radius: 4px;
    transition: background .18s, color .15s;
}
.btn-icon.danger {
    color: #F26F6F;
}
.btn-icon.edit {
    color: #44c3c9;
}
.btn-icon:active, .btn-icon:hover {
    background: #38946633;
}
.btn-icon.danger:active, .btn-icon.danger:hover {
    background: #F26F6F28;
    color: #fc8b8b;
}
.btn-icon.edit:hover {
    background: #46e2e840;
    color: #60f1f8;
}

.main-btn {
    border-radius: 8px;
    background: #389466;
    border: none;
    color: #fff;
    font-weight: 600;
    padding: 8px 24px;
    margin: 4px;
    font-size: 16px;
    box-shadow: none;
    cursor: pointer;
    transition: background .15s, color .15s;
}
.main-btn:hover, .main-btn:focus {
    background: #47b87a;
    color: #fff;
}
.main-btn.outline {
    background: transparent;
    border: 1.5px solid #389466;
    color: #389466;
    margin-left: 8px;
}
.main-btn.outline:hover {
    background: #38946615;
    color: #fff;
    border-color: #47b87a;
}

.news-list {
    display: flex;
    flex-wrap: wrap;
    gap: 19px;
    margin-top: 18px;
}
.news-card {
    position: relative;
    background: #1e1e2a;
    border-radius: 15px;
    width: 270px;
    box-shadow: 0 2px 9px #0004;
    cursor: pointer;
    transition: box-shadow .18s, transform .10s;
    display: flex;
    flex-direction: column;
    margin-bottom: 12px;
    overflow: hidden;
}
.news-card:hover {
    box-shadow: 0 5px 18px #0007;
    transform: translateY(-3px) scale(1.017) !important;
}
.news-card-img {
    background: #333 center/cover no-repeat;
    min-height: 120px;
    max-height: 130px;
    width: 100%;
    border-radius: 13px 13px 0 0;
    margin-bottom: 0;
    aspect-ratio: 2.25/1;
}
.news-card-body {
    padding: 15px 15px 11px 17px;
    flex: 1;
}
.news-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 6px;
    color: #fff;
    text-shadow: 0 1px 3px #1114;
}
.news-cat {
    font-size: 13px;
    color: #47b87a;
    margin-bottom: 4px;
}
.news-link a {
    font-size: 12px;
    color: #48c8da;
    text-decoration: underline dotted;
    letter-spacing: .1em;
    word-break: break-all;
}

.news-card .btn-icon, .news-card .btn-icon.danger {
    position: absolute;
    top: 7px;
    right: 7px;
    background:#181828f0;
    z-index: 2;
    font-size: 16px;
    padding: 3px 7px;
}
.news-card .btn-icon.edit {
    top: 7px;
    right: 40px;
}
.news-card .btn-icon.danger {
    top: 7px;
    right: 7px;
}

.modal-bg {
    position: fixed; z-index: 1020;
    inset: 0;
    background: #12121cc7;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal {
    background: #181828;
    color: #fff;
    border-radius: 18px;
    min-width: 340px;
    max-width: 95vw;
    padding: 0;
    box-shadow: 0 18px 36px #1118;
    min-height: 100px;
    overflow: visible;
    position: relative;
    animation: popin .19s cubic-bezier(.1,1.4,.49,1.06);
}
@keyframes popin{
    from {transform:scale(0.88); opacity:.34;}
    to   {transform:scale(1); opacity:1;}
}
.modal-header {
    padding: 21px 32px 7px 28px;
    font-size: 19px;
    font-weight: 900;
    border-radius: 18px 18px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #101015;
    border-bottom: 1px solid #38946622;
}
.editor-form {
    padding: 22px 32px 22px 32px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.image-upload-block {
    margin: 0 auto 10px auto; text-align:center;
    background: #23233e;
    border: 2.5px dashed #47b87a99;
    border-radius: 13px;
    padding: 23px 0 19px 0;
    position: relative;
    transition: background .17s, border-color .15s;
    cursor: pointer;
    max-width: 260px;
    min-height: 106px;
    display: flex; align-items: center; justify-content: center;
}
.image-upload-block.drophover {
    background: #38946633;
    border-color: #48cf57;
}
.image-preview {
    position: relative;
    display: inline-block;
}
.image-preview img {
    border-radius: 10px;
    max-width: 180px; max-height: 120px;
    background: #343;
    box-shadow: 0 4px 14px #1125;
}
.img-remove {
    position: absolute; top:2px; right:3px; background: #f26f6fc0 !important; color:#fff !important; font-size:13px;
    border-radius:8px; padding:2px 7px;
}
.img-placeholder {
    color: #8ce6c3bb;
    font-size: 15px;
    font-weight: 400;
    cursor: pointer;
}
.img-placeholder:hover{
    color: #47b87a;
    text-shadow: 0 2px 11px #38946611;
}

.input-group {
    margin: 0 0 0.7em 0;
    display:flex; flex-direction: column;
}
.input-group label {
    margin-bottom: 4px;
    font-size: 15px;
    color: #389466;
    font-weight: 500;
}
.input-group input,
.input-group select {
    background: #212130;
    border: none;
    color: #fff;
    font-size: 15px;
    border-radius: 6px;
    padding: 8px 11px;
    margin-bottom: 1px;
    outline: none;
    transition: border-color .16s;
    border: 1px solid #38946620;
}
.input-group input:focus, .input-group select:focus {
    border-color: #47b87a;
    background: #232340;
}

.btn-row {
    display: flex; flex-direction: row; align-items: center;
    margin-top: 16px; gap: 11px;
    justify-content: flex-end;
}

@media (max-width: 700px){
    .admin-news { padding: 10px !important;}
    .news-categories-panel, .news-list-panel {
        padding: 14px 6vw !important;
    }
    .news-list { flex-direction: column;}
    .news-card { width: 97vw; min-width: 0;}
    .modal { padding: 0; min-width: 0;}
    .editor-form { padding: 16px 8vw; }
    .modal-header { padding: 17px 8vw 8px 8vw; }
}
</style>
