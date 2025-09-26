<script>
import {toLink} from "@/utils.js";
import config from "@/config.json"

export default {
    name: "HelpView",
    data () {
        return {
            config: config,
        }
    },
    methods: {
        toLink,
        openFaq () {
            window.Telegram.WebApp.openLink(config.faq)
        }
    }
}
</script>

<template>
    <div class="help">
        <div class="help_faq" @click="openFaq">
            FAQ
        </div>
        <div class="home_supportChat_main">
            <div class="home_supportChat_main_background"></div>
            <div class="home_supportChat_main_upper">
                <div class="home_supportChat_main_upper_header">Чат с поддержкой</div>
                <div class="home_supportChat_main_upper_name">Поможем и ответим на все вопросы</div>
            </div>
            <div class="home_supportChat_main_downer">
                <div></div>
                <button @click="toLink('support')">Перейти в чат</button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>