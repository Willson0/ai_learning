<script>
export default {
    name: "IphoneSwitcherComponent",
    data () {
        return {
            isActive: false,
        }
    },
    async mounted () {
    },
    methods: {
        click (ev) {
            this.$emit("change", !this.isActiveProp);
        },
    },
    props: {
        isActiveProp: {
            required: true,
        }
    }
}
</script>

<template>
    <div @click="click" class="iphoneSwitcher"
         :class="isActiveProp ? 'active' : ''">
        <div></div>
    </div>
</template>

<style scoped>

</style>