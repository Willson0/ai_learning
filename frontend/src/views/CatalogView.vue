<script>
import {toLink} from "@/utils.js";

export default {
    name: "CatalogView",
    methods: {toLink},
    data () {
        return {
            type: 'ege',
            subject_id: null,
        }
    },
    mounted () {
        this.type = this.$route.query.type || 'ege';
        this.subject_id = Number(this.$route.query.id) || null;
        if (this.subject_id == null) window.backByQueryFunction();
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
        filtered () {
            if (!this.user.id) return;
            return this.user.states?.filter(st => (st.subject_id === this.subject_id) && (st.type === this.type));
        }
    }
}
</script>

<template>
    <div class="catalog" v-if="user">
        <div class="catalog_title">{{ user.subjects?.find(el => el.id === subject_id)?.name }}</div>
        <div class="catalog_description">{{ user.subjects?.find(el => el.id === subject_id)?.state_description[type].description }}</div>
        <div class="catalog_list">
            <div v-if="!filtered?.length">Тут пока что ничего нет...</div>
            <div v-for="state in filtered" @click="toLink('article', state.id)">
                {{ state.title }}
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>