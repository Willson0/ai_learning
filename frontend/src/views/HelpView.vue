<script>
import {toLink} from "@/utils.js";

export default {
    name: "HelpView",
    methods: {toLink}
}
</script>

<template>
    <div class="help">
        <div class="help_faq">
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