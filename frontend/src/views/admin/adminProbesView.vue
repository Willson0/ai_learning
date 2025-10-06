<template>
    <adminnav>
        <div class="admin-root">
            <div class="sidebar">
                <div class="header">
                    <h2>Пробники</h2>
                    <button class="btn btn-add" @click="openProbeForm()">+ Добавить</button>
                </div>

                <div class="filter">
                    <label>Фильтр по предмету</label>
                    <select v-model="filterSubject">
                        <option value="">Все предметы</option>
                        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>

                <ul class="probe-list">
                    <li
                        v-for="probe in filteredProbes"
                        :key="probe.id"
                        :class="{ active: selectedProbe && selectedProbe.id === probe.id }"
                        @click="selectProbe(probe)"
                    >
                        <div class="probe-title">
                            <strong>{{ probe.title }}</strong>
                            <small class="meta">{{ probe.type }} · {{ subjectName(probe.subject_id) }}</small>
                        </div>
                        <div class="probe-actions">
                            <button class="tiny" @click.stop="openProbeForm(probe)">✎</button>
                            <button class="tiny danger" @click.stop="deleteProbe(probe)">🗑</button>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="content">
                <div v-if="!selectedProbe" class="empty">
                    <h3>Выберите пробник слева или создайте новый</h3>
                </div>

                <div v-else class="probe-panel">
                    <div class="panel-header">
                        <div>
<!--                            <h2>{{ selectedProbe.title }}</h2>-->
                            <div class="meta-row">
                                <span class="chip">{{ selectedProbe.type }}</span>
                                <span class="meta-subject"> {{ subjectName(selectedProbe.subject_id) }}</span>
                            </div>
                        </div>

                        <div class="right-controls">
                            <button class="btn" @click="openProbeForm(selectedProbe)">Редактировать пробник</button>
                            <button class="btn btn-add" @click="openVariantForm({ probe_id: selectedProbe.id })">+ Новый вариант</button>
                        </div>
                    </div>

                    <div class="variants-area">
                        <div v-if="variantsForSelected.length === 0" class="no-variants">
                            Нет вариантов — создайте первый вариант
                        </div>

                        <!-- Compact list of variants: collapsed by default; expand one at a time -->
                        <div
                            v-for="(variant, index) in variantsForSelected"
                            :key="variant.id !== undefined ? variant.id : variant.__local_id || ('new_' + index)"
                            class="variant-card"
                        >
                            <div class="variant-header" @click="toggleVariant(variant)">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="chev" :class="{open: isVariantExpanded(variant)}">▸</div>
                                    <div>
                                        <strong>Вариант {{ index + 1 }}</strong>
                                        <div class="variant-title">{{ variant.title || 'Без заголовка' }}</div>
                                    </div>
                                </div>

                                <div class="variant-actions">
                                    <!-- reorder buttons -->
                                    <button class="tiny" @click.stop="moveVariantUp(variant)" :disabled="!canMoveVariantUp(variant)">↑</button>
                                    <button class="tiny" @click.stop="moveVariantDown(variant)" :disabled="!canMoveVariantDown(variant)">↓</button>

                                    <button class="tiny" @click.stop="openVariantForm(variant)">✎</button>
                                    <button class="tiny danger" @click.stop="deleteVariant(variant)">🗑</button>
                                </div>
                            </div>

                            <!-- expanded area (only for selected expanded variant) -->
                            <div v-if="isVariantExpanded(variant)" class="variant-body">
                                <div class="exercises-list">
                                    <div
                                        class="exercise-item"
                                        v-for="(ex, idx) in variant.exercises"
                                        :key="ex.__local_id || idx"
                                    >
                                        <div class="ex-top">
                                            <div class="ex-title">Задание {{ idx + 1 }} — {{ ex.points || 0 }} баллов</div>
                                            <div class="ex-controls">
                                                <button class="tiny" @click="moveExerciseUp(variant, idx)" :disabled="idx===0">↑</button>
                                                <button class="tiny" @click="moveExerciseDown(variant, idx)" :disabled="idx===variant.exercises.length-1">↓</button>
                                                <button class="tiny danger" @click="removeExercise(variant, idx)">✖</button>
                                            </div>
                                        </div>

                                        <div class="ex-edit-row">
                                            <div class="editor-col">
                                                <label>Текст задания (Markdown)</label>
                                                <textarea
                                                    :ref="setExerciseTextRef(variant, idx)"
                                                    class="md-editor-textarea"
                                                >{{ ex.text }}</textarea>
                                                <div class="small-row">
                                                    <label>Баллы</label>
                                                    <input type="number" v-model.number="ex.points" min="0" />
                                                    <label>Правильный ответ (значение)</label>
                                                    <input type="text" v-model="ex.right_answer.value" />
                                                </div>
<!--                                                <label>Предпросмотр текста</label>-->
<!--                                                <div class="md-preview" v-html="renderMarkdown(ex.text)"></div>-->
                                            </div>

                                            <div class="preview-col">
                                                <label>Решение (Markdown)</label>
                                                <textarea
                                                    :ref="setExerciseSolutionRef(variant, idx)"
                                                    class="md-editor-textarea"
                                                >{{ ex.right_answer.description }}</textarea>

<!--                                                <label>Предпросмотр решения</label>-->
<!--                                                <div class="md-preview" v-html="renderMarkdown(ex.right_answer.description)"></div>-->
                                            </div>
                                        </div>
                                    </div> <!-- exercise-item -->
                                </div> <!-- exercises-list -->

                                <div class="variant-footer">
                                    <button class="btn" @click="addExercise(variant)">+ Добавить задание</button>
                                    <button class="btn btn-save" @click="saveVariant(variant)">Сохранить вариант</button>
                                </div>
                            </div>
                        </div> <!-- variant-card -->
                    </div> <!-- variants-area -->
                </div>
            </div>

            <!-- Probe Form Modal -->
            <div v-if="probeForm.visible" class="modal-backdrop" @click.self="closeProbeForm()">
                <div class="modal">
                    <h3>{{ probeForm.editing ? 'Редактировать пробник' : 'Новый пробник' }}</h3>
                    <div class="form-row">
                        <label>Заголовок</label>
                        <input v-model="probeForm.data.title" />
                    </div>
                    <div class="form-row">
                        <label>Тип</label>
                        <select v-model="probeForm.data.type">
                            <option value="ege">ege</option>
                            <option value="oge">oge</option>
                            <option value="vpr">vpr</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>Предмет</label>
                        <select v-model.number="probeForm.data.subject_id">
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button class="btn" @click="submitProbeForm()">Сохранить</button>
                        <button class="btn" @click="closeProbeForm()">Отмена</button>
                    </div>
                </div>
            </div>

            <!-- Variant Form Modal (for create/edit header/title) -->
            <div v-if="variantForm.visible" class="modal-backdrop" @click.self="closeVariantForm()">
                <div class="modal">
                    <h3>{{ variantForm.editing ? 'Редактировать вариант' : 'Новый вариант' }}</h3>
<!--                    <div class="form-row">-->
<!--                        <label>Заголовок варианта</label>-->
<!--                        <input v-model="variantForm.data.title" />-->
<!--                    </div>-->
                    <div class="form-row">
                        <label>Пробник</label>
                        <select v-model.number="variantForm.data.probe_id">
                            <option v-for="p in probes" :key="p.id" :value="p.id">{{ p.title }}</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button class="btn" @click="submitVariantForm()">Сохранить</button>
                        <button class="btn" @click="closeVariantForm()">Отмена</button>
                    </div>
                </div>
            </div>

        </div>
    </adminnav>
</template>

<script>
import adminnav from "@/components/adminnav.vue"
import axios from "axios";
import config from "@/config.json";
import { marked } from "marked";
import {deepParse} from "@/utils.js";

export default {
    name: "AdminProbesVariants",
    components: {adminnav},
    data() {
        return {
            probes: [],
            variants: [],
            subjects: [],
            selectedProbe: null,
            filterSubject: "",

            probeForm: {
                visible: false,
                editing: false,
                data: { id: null, title: "", type: "ege", subject_id: null },
            },

            variantForm: {
                visible: false,
                editing: false,
                // data: { id: null, title: "", probe_id: null, exercises: [] },
                data: { id: null, probe_id: null, exercises: [] },
            },

            editorsMap: { text: {}, solution: {} },

            // expanded variant id (only one expanded at a time)
            expandedVariantId: null,
        };
    },
    computed: {
        filteredProbes() {
            if (!this.filterSubject) return this.probes;
            return this.probes.filter((p) => p.subject_id == this.filterSubject);
        },
        // variants for selected probe, sorted by 'order' if present, otherwise by id
        variantsForSelected() {
            if (!this.selectedProbe) return [];
            const list = this.variants.filter((v) => v.probe_id === this.selectedProbe.id);
            // ensure exercises are proper arrays and have local ids
            list.forEach((v) => {
                if (!Array.isArray(v.exercises)) v.exercises = [];
                v.exercises.forEach((ex) => {
                    if (!ex.__local_id) ex.__local_id = this._localId();
                    if (!ex.right_answer) ex.right_answer = { value: "", description: "" };
                });
            });
            list.sort((a, b) => {
                const oa = (a.order === undefined || a.order === null) ? 0 : a.order;
                const ob = (b.order === undefined || b.order === null) ? 0 : b.order;
                if (oa !== ob) return oa - ob;
                // fallback stable order
                return (a.id || 0) - (b.id || 0);
            });
            return list;
        },
    },
    mounted() {
        axios.defaults.withCredentials = true;
        this.loadSubjects();
        this.loadProbes();
        this.loadVariants();

        if (!this.subjects || this.subjects.length === 0) {
            this.subjects = [
                { id: 1, name: "Русский язык" },
                { id: 2, name: "Математика" },
                { id: 3, name: "Физика" },
            ];
        }

        this.$nextTick(() => {
            this.initAllEditors();
        });
    },
    methods: {
        _localId() {
            return "l_" + Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
        },

        // Backend: probes
        async loadSubjects () {
            try {
                const resp = await axios.get(config.backend + "admin/subjects");
                this.subjects = resp.data || [];
            } catch (e) {
                console.error("loadSubjects error", e);
                alert("Ошибка загрузки loadSubjects");
            }
        },
        async loadProbes() {
            try {
                const resp = await axios.get(config.backend + "admin/probes");
                this.probes = deepParse(resp.data) || [];
            } catch (e) {
                console.error("loadProbes error", e);
                alert("Ошибка загрузки пробников");
            }
        },
        async saveProbeToServer(payload, editing = false) {
            try {
                if (editing && payload.id) {
                    const resp = await axios.post(config.backend + "admin/probes/" + payload.id, payload);
                    this.probes = deepParse(resp.data) || [];
                } else {
                    const resp = await axios.post(config.backend + "admin/probes", payload);
                    this.probes = deepParse(resp.data) || [];
                }
            } catch (e) {
                console.error(e);
                alert("Ошибка сохранения пробника");
            }
        },
        async deleteProbe(probe) {
            if (!confirm("Удалить пробник и все его варианты?")) return;
            try {
                const resp = await axios.delete(config.backend + "admin/probes/" + probe.id);
                this.probes = deepParse(resp.data) || [];
                if (this.selectedProbe && this.selectedProbe.id === probe.id) this.selectedProbe = null;
            } catch (e) {
                console.error(e);
                alert("Ошибка удаления пробника");
            }
        },

        // Backend: variants
        async loadVariants() {
            try {
                const resp = await axios.get(config.backend + "admin/variants");
                this.variants = (deepParse(resp.data) || []).map((v) => {
                    try {
                        if (typeof v.exercises === "string") v.exercises = JSON.parse(v.exercises);
                    } catch (err) {}
                    if (!Array.isArray(v.exercises)) v.exercises = [];
                    v.exercises.forEach((ex) => {
                        if (!ex.__local_id) ex.__local_id = this._localId();
                        if (!ex.right_answer) ex.right_answer = { value: "", description: "" };
                    });
                    return v;
                });
            } catch (e) {
                console.error("loadVariants error", e);
                alert("Ошибка загрузки вариантов");
            }
            this.$nextTick(() => this.initAllEditors());
        },

        async saveVariantToServer(payload, editing = false) {
            try {
                const payloadCopy = JSON.parse(JSON.stringify(payload));
                payloadCopy.exercises = payloadCopy.exercises.map((ex) => {
                    if (ex.__local_id) delete ex.__local_id;
                    return ex;
                });

                if (editing && payloadCopy.id) {
                    const resp = await axios.post(config.backend + "admin/variants/" + payloadCopy.id, payloadCopy);
                    this.variants = deepParse(resp.data) || [];
                } else {
                    const resp = await axios.post(config.backend + "admin/variants", payloadCopy);
                    this.variants = deepParse(resp.data) || [];
                }
            } catch (e) {
                console.error(e);
                alert("Ошибка сохранения варианта");
            }
            this.$nextTick(() => this.initAllEditors());
        },
        async deleteVariant(variant) {
            if (!variant.id) {
                // not saved yet — just remove locally
                if (!confirm("Удалить локальный вариант?")) return;
                this.variants = this.variants.filter((v) => v !== variant);
                return;
            }
            if (!confirm("Удалить вариант?")) return;
            try {
                const resp = await axios.delete(config.backend + "admin/variants/" + variant.id);
                this.variants = deepParse(resp.data) || [];
            } catch (e) {
                console.error(e);
                alert("Ошибка удаления варианта");
            }
        },

        // UI helpers
        selectProbe(probe) {
            this.selectedProbe = probe;
            this.expandedVariantId = null;
            this.$nextTick(() => this.initAllEditors());
        },
        subjectName(id) {
            const s = this.subjects.find((x) => x.id === id);
            return s ? s.name : "—";
        },

        // Probe form
        openProbeForm(probe = null) {
            if (probe) {
                this.probeForm.editing = true;
                this.probeForm.data = { ...probe };
            } else {
                this.probeForm.editing = false;
                this.probeForm.data = {
                    id: null,
                    title: "",
                    type: "ege",
                    subject_id: this.subjects.length ? this.subjects[0].id : null,
                };
            }
            this.probeForm.visible = true;
        },
        closeProbeForm() {
            this.probeForm.visible = false;
        },
        async submitProbeForm() {
            const payload = { ...this.probeForm.data };
            await this.saveProbeToServer(payload, this.probeForm.editing);
            this.probeForm.visible = false;
        },

        // Variant header form
        openVariantForm(variant = null) {
            if (variant && variant.id) {
                this.variantForm.editing = true;
                this.variantForm.data = {
                    id: variant.id,
                    // title: variant.title || "",
                    probe_id: variant.probe_id,
                    exercises: variant.exercises ? JSON.parse(JSON.stringify(variant.exercises)) : [],
                };
            } else {
                this.variantForm.editing = false;
                this.variantForm.data = {
                    id: null,
                    // title: "",
                    probe_id: variant && variant.probe_id ? variant.probe_id : (this.selectedProbe ? this.selectedProbe.id : (this.probes[0] ? this.probes[0].id : null)),
                    exercises: [],
                };
            }
            this.variantForm.visible = true;
        },
        closeVariantForm() {
            this.variantForm.visible = false;
        },
        async submitVariantForm() {
            const payload = { ...this.variantForm.data };
            if (!Array.isArray(payload.exercises)) payload.exercises = [];
            payload.exercises.forEach((ex) => {
                if (!ex.__local_id) ex.__local_id = this._localId();
            });
            await this.saveVariantToServer(payload, this.variantForm.editing);
            this.variantForm.visible = false;
            this.$nextTick(() => this.initAllEditors());
        },

        // Exercises manipulation
        addExercise(variant) {
            const newEx = {
                __local_id: this._localId(),
                text: "Новый вопрос (отредактируйте текст в Markdown)",
                right_answer: { value: "", description: "Решение (Markdown)" },
                points: 1,
            };
            variant.exercises.push(newEx);
            this.$nextTick(() => this.initEditorsForVariant(variant));
        },
        removeExercise(variant, idx) {
            const keyText = this._editorKey(variant, idx, "text");
            const keySol = this._editorKey(variant, idx, "solution");
            this.destroyEditorIfExists(keyText);
            this.destroyEditorIfExists(keySol);
            variant.exercises.splice(idx, 1);
            this.$nextTick(() => this.initAllEditors());
        },
        moveExerciseUp(variant, idx) {
            if (idx === 0) return;
            this.swapAndPreserveArray(variant.exercises, idx - 1, idx);
            this.$nextTick(() => this.initAllEditors());
        },
        moveExerciseDown(variant, idx) {
            if (idx === variant.exercises.length - 1) return;
            this.swapAndPreserveArray(variant.exercises, idx, idx + 1);
            this.$nextTick(() => this.initAllEditors());
        },
        swapAndPreserveArray(arr, i, j) {
            [arr[i], arr[j]] = [arr[j], arr[i]];
        },

        // Save an inline variant
        async saveVariant(variant) {
            this.syncEditorsToExercises();
            await this.saveVariantToServer(variant, !!variant.id);
            alert("Вариант сохранён");
        },

        // Markdown rendering — use imported marked
        renderMarkdown(md) {
            if (!md) return "";
            try {
                return marked.parse(md);
            } catch (e) {
                console.warn("marked parse error", e);
                return md.replace(/</g, "&lt;");
            }
        },

        // Editors handling
        setExerciseTextRef(variant, idx) {
            return `et_${variant.id !== undefined && variant.id !== null ? variant.id : 'newp'}_${variant.probe_id || 'np'}_${idx}`;
        },
        setExerciseSolutionRef(variant, idx) {
            return `es_${variant.id !== undefined && variant.id !== null ? variant.id : 'newp'}_${variant.probe_id || 'np'}_${idx}`;
        },
        _editorKey(variant, idx, type) {
            return `${type}_${variant.id || 'new'}_${variant.probe_id || 'np'}_${variant.exercises[idx].__local_id || idx}`;
        },

        initAllEditors() {
            this.$nextTick(() => {
                // Destroy editors whose element no longer present
                ["text", "solution"].forEach((t) => {
                    for (const key in this.editorsMap[t]) {
                        const ed = this.editorsMap[t][key];
                        if (!ed || !ed.element) continue;
                        if (!document.body.contains(ed.element)) {
                            try {
                                ed.toTextArea();
                                if (ed.codemirror) ed.codemirror.toTextArea();
                            } catch (e) {}
                            delete this.editorsMap[t][key];
                        }
                    }
                });

                // Initialize editors for visible textareas (only expanded variant(s) exist in DOM)
                for (const refName in this.$refs) {
                    if (!refName) continue;
                    const nodes = this.$refs[refName];
                    const node = Array.isArray(nodes) ? nodes[0] : nodes;
                    if (!node || node.tagName !== "TEXTAREA") continue;

                    if (refName.startsWith("et_")) {
                        const parts = refName.split("_");
                        const idx = parseInt(parts[3]);
                        const variant = this._findVariantByRefParts(parts);
                        if (!variant) continue;
                        const key = this._editorKey(variant, idx, "text");
                        if (!this.editorsMap.text[key]) {
                            try {
                                const e = new window.EasyMDE({
                                    element: node,
                                    spellChecker: false,
                                    status: false,
                                    toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "image", "|", "preview"],
                                });
                                this.editorsMap.text[key] = e;
                            } catch (e) {
                                console.warn("EasyMDE init error", e);
                            }
                        }
                    } else if (refName.startsWith("es_")) {
                        const parts = refName.split("_");
                        const idx = parseInt(parts[3]);
                        const variant = this._findVariantByRefParts(parts);
                        if (!variant) continue;
                        const key = this._editorKey(variant, idx, "solution");
                        if (!this.editorsMap.solution[key]) {
                            try {
                                const e = new window.EasyMDE({
                                    element: node,
                                    spellChecker: false,
                                    status: false,
                                    toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "|", "preview"],
                                });
                                this.editorsMap.solution[key] = e;
                            } catch (e) {
                                console.warn("EasyMDE init error", e);
                            }
                        }
                    }
                }
            });
        },

        initEditorsForVariant(variant) {
            this.$nextTick(() => this.initAllEditors());
        },

        _findVariantByRefParts(parts) {
            // parts like ['et','<variantId or newp>','<probeId or np>','<idx>']
            const vid = parts[1];
            const pid = parts[2];
            let variant = null;
            if (vid && vid !== "newp") {
                const vidNum = Number(vid);
                if (!isNaN(vidNum)) variant = this.variants.find((v) => v.id === vidNum);
            }
            if (!variant && pid && pid !== "np") {
                const pidNum = Number(pid);
                if (!isNaN(pidNum)) variant = this.variants.find((v) => v.probe_id === pidNum);
            }
            if (!variant && this.selectedProbe) {
                variant = this.variants.find((v) => v.probe_id === this.selectedProbe.id);
            }
            return variant;
        },

        destroyEditorIfExists(key) {
            if (this.editorsMap.text[key]) {
                try {
                    this.editorsMap.text[key].toTextArea();
                } catch (e) {}
                delete this.editorsMap.text[key];
            }
            if (this.editorsMap.solution[key]) {
                try {
                    this.editorsMap.solution[key].toTextArea();
                } catch (e) {}
                delete this.editorsMap.solution[key];
            }
        },

        syncEditorsToExercises() {
            // only sync visible/expanded variant editors
            for (const variant of this.variantsForSelected) {
                if (!this.isVariantExpanded(variant)) continue;
                variant.exercises.forEach((ex, idx) => {
                    const keyText = this._editorKey(variant, idx, "text");
                    const keySol = this._editorKey(variant, idx, "solution");
                    if (this.editorsMap.text[keyText]) {
                        try {
                            ex.text = this.editorsMap.text[keyText].value();
                        } catch (e) {}
                    } else {
                        const refName = this.setExerciseTextRef(variant, idx);
                        const node = this.$refs[refName];
                        if (node) ex.text = node.value;
                    }
                    if (this.editorsMap.solution[keySol]) {
                        try {
                            ex.right_answer.description = this.editorsMap.solution[keySol].value();
                        } catch (e) {}
                    } else {
                        const refNameS = this.setExerciseSolutionRef(variant, idx);
                        const nodeS = this.$refs[refNameS];
                        if (nodeS) ex.right_answer.description = nodeS.value;
                    }
                });
            }
        },

        // ===== variant expand/collapse =====
        toggleVariant(variant) {
            if (this.expandedVariantId === variant.id) {
                this.expandedVariantId = null;
            } else {
                this.expandedVariantId = variant.id;
                this.$nextTick(() => this.initEditorsForVariant(variant));
            }
        },
        isVariantExpanded(variant) {
            return this.expandedVariantId === variant.id;
        },

        // ===== reorder variants (UI + persist) =====
        canMoveVariantUp(variant) {
            if (!variant || variant.probe_id !== (this.selectedProbe && this.selectedProbe.id)) return false;
            const list = this.variantsForSelected;
            const idx = list.findIndex((v) => v === variant || v.id === variant.id);
            return idx > 0;
        },
        canMoveVariantDown(variant) {
            if (!variant || variant.probe_id !== (this.selectedProbe && this.selectedProbe.id)) return false;
            const list = this.variantsForSelected;
            const idx = list.findIndex((v) => v === variant || v.id === variant.id);
            return idx >= 0 && idx < list.length - 1;
        },
        async moveVariantUp(variant) {
            if (!this.canMoveVariantUp(variant)) return;
            await this._reorderVariant(variant, -1);
        },
        async moveVariantDown(variant) {
            if (!this.canMoveVariantDown(variant)) return;
            await this._reorderVariant(variant, +1);
        },
        async _reorderVariant(variant, delta) {
            // Reorder in local this.variants by adjusting 'order' property among variants of same probe
            const probeId = variant.probe_id;
            const list = this.variants
                .filter((v) => v.probe_id === probeId)
                .sort((a, b) => {
                    const oa = (a.order === undefined || a.order === null) ? 0 : a.order;
                    const ob = (b.order === undefined || b.order === null) ? 0 : b.order;
                    if (oa !== ob) return oa - ob;
                    return (a.id || 0) - (b.id || 0);
                });

            const idx = list.findIndex((v) => v === variant || v.id === variant.id);
            if (idx === -1) return;
            const newIdx = idx + delta;
            if (newIdx < 0 || newIdx >= list.length) return;

            // swap order values (or assign if missing)
            const a = list[idx];
            const b = list[newIdx];
            const oa = (a.order === undefined || a.order === null) ? idx : a.order;
            const ob = (b.order === undefined || b.order === null) ? newIdx : b.order;
            // swap
            a.order = ob;
            b.order = oa;

            // Write back to this.variants (replace by id)
            this.variants = this.variants.map((v) => {
                if (v.id === a.id) return { ...v, order: a.order };
                if (v.id === b.id) return { ...v, order: b.order };
                return v;
            });

            // Persist new order for all variants of this probe (server must accept 'order' field)
            await this.saveVariantsOrder(probeId);

            // keep expanded variant visible (update nextTick for editors)
            this.$nextTick(() => this.initAllEditors());
        },

        async saveVariantsOrder(probeId) {
            // for all variants of probeId, set order according to current sorted order and POST each to server
            const list = this.variants
                .filter((v) => v.probe_id === probeId)
                .sort((a, b) => (a.order || 0) - (b.order || 0));

            // Assign sequential indexes (0..n-1)
            for (let i = 0; i < list.length; i++) {
                const v = list[i];
                v.order = i;
                // Only persist if variant has id (server-side)
                if (v.id) {
                    try {
                        const payloadCopy = JSON.parse(JSON.stringify(v));
                        payloadCopy.exercises = payloadCopy.exercises.map((ex) => {
                            if (ex.__local_id) delete ex.__local_id;
                            return ex;
                        });
                        const resp = await axios.post(config.backend + "admin/variants/" + v.id, payloadCopy);
                        // server returns updated variants array — update local store
                        if (resp && resp.data) {
                            this.variants = resp.data || this.variants;
                        }
                    } catch (e) {
                        console.error("Ошибка при сохранении порядка вариантов", e);
                    }
                }
            }
            // refresh editors
            this.$nextTick(() => this.initAllEditors());
        },
    },
};
</script>

<style scoped>
/* Basic dark admin styles, accent color: #389466 */
.admin-root {
    display: flex;
    height: 100%;
    min-height: 600px;
    font-family: Inter, "Helvetica Neue", Arial, sans-serif;
    background: #12121C;
    color: #E6E6E9;
}

/* Sidebar */
.sidebar {
    width: 320px;
    background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border-right: 1px solid rgba(255,255,255,0.03);
    padding: 18px;
    box-sizing: border-box;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.header h2 { margin: 0; color: #fff; font-size: 18px; }
.btn-add {
    background: #389466;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
}

.filter { margin-bottom: 12px; }
.filter label { display:block; font-size:12px; color:#bfc6c0; margin-bottom:6px; }
.filter select { width:100%; padding:6px; border-radius:6px; background:#0E0E13; color:#E6E6E9; border:1px solid rgba(255,255,255,0.04); }

/* probe list */
.probe-list { list-style:none; margin:0; padding:0; max-height: calc(100vh - 200px); overflow:auto; }
.probe-list li {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px;
    border-radius:8px;
    margin-bottom:8px;
    cursor:pointer;
    transition: all .12s ease;
    background: transparent;
}
.probe-list li:hover { transform: translateY(-2px); background: rgba(56,148,102,0.04); }
.probe-list li.active { background: rgba(56,148,102,0.08); box-shadow: 0 6px 16px rgba(56,148,102,0.04); }

.probe-title { max-width: 200px; }
.probe-title small { display:block; color:#9aa49b; font-size: 12px; }

.probe-actions .tiny {
    border: none;
    background: transparent;
    color: #cfcfcf;
    margin-left: 6px;
    cursor: pointer;
}
.probe-actions .tiny.danger { color: #ff6b6b; }

/* Content area */
.content {
    flex: 1;
    padding: 20px;
    overflow: auto;
    box-sizing: border-box;
}

.empty { display:flex; align-items:center; justify-content:center; height:200px; color:#9aa49b; }

.probe-panel .panel-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom: 16px;
}
.panel-header h2 { margin:0; }
.meta-row { margin-top:6px; color:#9aa49b; font-size:13px; }

.chip {
    background: rgba(255,255,255,0.03);
    padding:6px 8px;
    border-radius:6px;
    margin-right:8px;
    font-size:12px;
}

.right-controls .btn {
    margin-left:10px;
}
.btn {
    background: rgba(255,255,255,0.03);
    border:1px solid rgba(255,255,255,0.03);
    color: #E6E6E9;
    padding:8px 12px;
    border-radius:8px;
    cursor:pointer;
}
.btn-save { background: linear-gradient(90deg,#389466, #2fa05a); border: none; color: white; }

.variants-area { margin-top: 14px; }

/* Variant card */
.variant-card {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.03);
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 12px;
}
.variant-header { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.variant-title { color:#bfc6c0; font-size:13px; margin-top:4px; max-width:420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.variant-actions .tiny { margin-left:8px; background:transparent; border:none; color:#cfcfcf; cursor:pointer; padding:4px 6px; border-radius:6px; }
.variant-actions .tiny.danger { color:#ff6b6b; }

.chev { transition: transform .12s ease; display:inline-block; color:#9aa49b; }
.chev.open { transform: rotate(90deg); color:#fff; }

.variant-body { margin-top: 10px; }

/* Exercise item */
.exercise-item {
    border-radius:8px;
    border:1px dashed rgba(255,255,255,0.02);
    padding:10px;
    margin-bottom:10px;
    background: linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.00));
}
.ex-top { display:flex; justify-content:space-between; align-items:center; }
.ex-title { font-weight:600; }
.ex-controls .tiny { margin-left:6px; border:none; background:transparent; cursor:pointer; color:#cfcfcf; }
.ex-controls .tiny.danger { color:#ff6b6b; }

.ex-edit-row { display:flex; gap:12px; margin-top:8px; }
.editor-col, .preview-col { flex:1; min-width: 0; }
.md-editor-textarea { width:100%; height:140px; border-radius:6px; background:#0E0E13; color:#E6E6E9; border:1px solid rgba(255,255,255,0.03); padding:8px; font-family: monospace; }
.md-preview { background:#0B0B10; padding:10px; border-radius:6px; min-height:140px; border:1px solid rgba(255,255,255,0.02); overflow:auto; color:#e6e6e6; }

.small-row { display:flex; gap:8px; align-items:center; margin-top:8px; }
.small-row label { font-size:12px; color:#bfc6c0; }
.small-row input[type="number"], .small-row input[type="text"] {
    background:#0E0E13; color:#E6E6E9; border:1px solid rgba(255,255,255,0.03); padding:6px; border-radius:6px;
}

/* Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index: 1000;
}
.modal {
    width: 560px;
    background: #0E0E13;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.7);
    color: #E6E6E9;
}
.form-row { margin-bottom: 12px; }
.form-row label { display:block; margin-bottom:6px; font-size:13px; color:#bfc6c0; }
.form-row input, .form-row select { width:100%; padding:8px; border-radius:8px; background:#0B0B10; color:#E6E6E9; border:1px solid rgba(255,255,255,0.03); }

.modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:10px; }

/* tiny danger */
.tiny.danger { color:#ff6b6b; }
</style>
