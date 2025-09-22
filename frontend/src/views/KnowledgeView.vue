<script>
import SearchComponent from "@/components/SearchComponent.vue";
import {toLink} from "@/utils.js";

export default {
    name: "KnowledgeView",
    methods: {toLink},
    components: {SearchComponent},
    data () {
        return {
            type: "ege",
            search: "",
        }
    },
    mounted () {
        this.type = this.$route.query.type || "ege";
    },
    computed: {
        user () {
            return this.$store.state.user;
        }
    },
    watch: {
        type () {
            this.$router.push({query: {...this.$route.query, type: this.type}})
        }
    }
}
</script>

<template>
    <div class="knowledge">
        <search-component @inp="search = $event" style="margin-top: 0;"/>
        <div class="knowledge_selector">
            <div :class="{'active': type === 'ege'}" @click="type = 'ege'">ЕГЭ</div>
            <div :class="{'active': type === 'oge'}" @click="type = 'oge'">ОГЭ</div>
            <div :class="{'active': type === 'vpr'}" @click="type = 'vpr'">ВПР</div>
        </div>
        <div class="knowledge_title">Подборка статей</div>
        <div class="knowledge_description">Статьи для помощи в подготовке к экзаменам</div>
        <div class="knowledge_subjects">
            <div v-for="subject in user.subjects?.filter(sub => sub.name.trim().toLowerCase().includes(search.trim().toLowerCase()))" @click="toLink('catalog', subject.id, type)">
                <div class="knowledge_subjects_item_title">{{subject.name}}</div>
                <div class="knowledge_subjects_item_description">{{subject.state_description[type]?.description}}</div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>