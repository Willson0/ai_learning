<script>
export default {
    name: "SearchComponent",
    data () {
        return {
            input: "",
        }
    }
}
</script>

<template>
    <div class="rating_friend_search">
        <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9.92984 10.4298C11.6848 8.67484 11.6848 5.82484 9.92984 4.06234C8.17484 2.30734 5.32484 2.30734 3.56234 4.06234C1.80734 5.81734 1.80734 8.66734 3.56234 10.4298C5.31734 12.1848 8.16734 12.1848 9.92984 10.4298Z" stroke="#888888" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10.125 10.625L15.75 16.25" stroke="#888888" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <input type="text" v-model="input" @input="$emit('inp', input)" placeholder="Поиск">
    </div>
</template>

<style scoped>

</style>