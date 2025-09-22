<script>
export default {
    name: "DoubleSelector",
    data () {
        return {
            isActive: false,
        }
    },
    props: {
        first: {
            type: String,
        },
        second: {
            type: String,
        }
    },
    watch: {
        isActive () {
            this.$emit('change', this.isActive);
        }
    }
}
</script>

<template>
    <div class="achievements_selector">
        <div class="achievements_selector_background" :class="{'right': isActive}"></div>
        <div class="achievements_selector_item" :class="{'active': !isActive}" @click="isActive = false"><div>{{ first }}</div></div>
        <div class="achievements_selector_item" :class="{'active': isActive}" @click="isActive = true"><div>{{ second }}</div></div>
    </div>
</template>

<style scoped>

</style>