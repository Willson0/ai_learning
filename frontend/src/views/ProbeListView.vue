<script>
import {endLoading, toLink} from "@/utils.js";
import axios from "axios";
import config from "@/config.json"

export default {
    name: "ProbeListView",
    data () {
        return {
            variants: [],
        }
    },
    async mounted () {
        await this.initVariants();
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
        probe () {
            if (!this.user.id) return;
            return this.user.probes.find(pr => pr.id === Number(this.$route.query.id));
        }
    },
    watch: {
        async user () {
            await this.initVariants();
        }
    },
    methods: {
        toLink,
        async initVariants () {
            if (!this.user.id) return;
            if (this.variants.length > 0) return;
            let variants = this.user.probes.find(pr => pr.id === Number(this.$route.query.id)).variants;

            if (variants === undefined) {
                await axios.post(config.backend + "probe/" + Number(this.$route.query.id), {
                    initData: window.Telegram.WebApp.initData
                }).then((response) => {
                    this.variants = response.data;
                    endLoading('probeList_loading');

                    let newUser = {...this.user};
                    newUser.probes.find(pr => pr.id === Number(this.$route.query.id)).variants = response.data;
                    this.$store.commit('setUser', newUser);
                })
            } else {
                this.variants = variants;
                endLoading('probeList_loading');
            }
        },
        getTime (time) {
            if (time == null) return "";

            let hours = Math.floor(time / 60);
            let minutes = time % 60;

            return hours + "ч " + minutes.toString().padStart(2, '0') + "м";
        }
    }
}
</script>

<template>
    <div class="probeList_loading loading"></div>
    <div class="probeList" v-if="user && probe">
        <div class="probeList_title">{{ probe.title }}</div>
        <div class="probeList_time">Ограничение по времени: {{ getTime(user.subjects.find(s => s.id === probe.subject_id).state_description[probe.type].time) }} минут </div>
        <div class="probeList_variants">
            <div class="probeList_variants_title">Варианты</div>
            <div v-if="variants?.length === 0" style="margin-top: 20px;">Тут пока что ничего нет...</div>
            <div class="probeList_variants_list">
                <div v-for="(vr, key) in variants" @click="toLink('probe', vr.id)">Вариант {{ key + 1 }}</div>
            </div>
        </div>
        <button>Начать</button>
    </div>
</template>

<style scoped>

</style>