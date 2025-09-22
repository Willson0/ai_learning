<script>
import { marked } from "marked";
export default {
    name: "ArticleView",
    data () {
        return {
            state: null,
            textMarkdown: "",
            theme: "",
        }
    },
    mounted () {
        this.theme = window.Telegram.WebApp.colorScheme;
        this.getState();
    },
    methods: {
        getState () {
            if (!this.user.id) return;
            this.state = this.user.states.find(st => st.id === Number(this.$route.query.id));

            this.textMarkdown = marked.parse(this.state.text);
        },
        openLink(url) {
            window.Telegram.WebApp.openLink(url);
        }
    },
    computed: {
        user () {
            return this.$store.state.user;
        }
    },
    watch: {
        user () {
            this.getState();
        }
    }
}
</script>

<template>
    <div class="article" v-if="state != null">
        <div class="article_title">{{ state.title }}</div>
        <div class="article_description">{{ state.description }}</div>
        <div class="article_materials">
            <div class="article_materials_title">Видеоматериалы</div>
            <div class="article_materials_list">
                <div @click="openLink(state.materials?.rutube)">
                    <svg width="33" height="32" viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.2824 32H24.7144C29.012 32 32.4968 28.5152 32.4968 24.2176V7.7824C32.5 3.4944 29.028 0.0128 24.74 0H8.26C3.972 0.0128 0.5 3.4944 0.5 7.7824V24.2144C0.5 28.5152 3.9848 32 8.2824 32Z" fill="#100943"/>
                        <path d="M24.74 0H16.5C16.5 8.8352 23.6648 16 32.5 16V7.7824C32.5 3.4944 29.028 0.0128 24.74 0Z" fill="#ED143B"/>
                        <path d="M20.2666 15.2704H10.8138V11.5296H20.2666C20.8202 11.5296 21.2042 11.6256 21.3962 11.7952C21.5882 11.9648 21.7098 12.2752 21.7098 12.7296V14.0736C21.7098 14.5536 21.5914 14.864 21.3962 15.0336C21.2042 15.2 20.8202 15.2704 20.2666 15.2704ZM20.9162 8H6.8042V24H10.8138V18.7936H18.2026L21.7066 24H26.1962L22.3306 18.7712C23.7546 18.56 24.3946 18.1216 24.9226 17.4048C25.4506 16.6848 25.7162 15.5328 25.7162 14V12.8C25.7162 11.888 25.6202 11.168 25.4506 10.6176C25.281 10.0672 24.9962 9.5872 24.5866 9.1552C24.1546 8.7456 23.6746 8.4608 23.0986 8.2688C22.5226 8.096 21.8026 8 20.9162 8Z" fill="white"/>
                    </svg>
                </div>
                <div @click="openLink(state.materials?.vk)">
                    <svg width="33" height="32" viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <mask id="mask0_447_14061" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="33" height="32">
                            <path d="M32.5 0H0.5V32H32.5V0Z" fill="white"/>
                        </mask>
                        <g mask="url(#mask0_447_14061)">
                            <path d="M0.5 11.6992C0.5 2.0672 2.564 0 12.1992 0H20.8008C30.4328 0 32.5 2.064 32.5 11.6992V20.3008C32.5 29.9328 30.436 32 20.8008 32H12.1992C2.5672 32 0.5 29.936 0.5 20.3008V11.6992Z" fill="#0077FF"/>
                            <path d="M17.6935 32H20.7431C30.1959 32 32.3975 30.032 32.4935 20.9664L32.4967 20.3008V11.6992C32.4967 11.456 32.4967 11.2192 32.4935 10.9824C32.3879 1.9616 30.1799 0 20.7431 0H17.6935C8.01354 0 5.93994 2.0672 5.93994 11.6992V20.3008C5.93994 29.9328 8.01354 32 17.6935 32Z" fill="#FF2B42"/>
                            <path d="M23.0792 13.2318C24.6952 14.1662 25.5048 14.6334 25.7768 15.2414C26.0136 15.7726 26.0136 16.3806 25.7768 16.9118C25.5048 17.5198 24.6952 17.987 23.0792 18.9214L18.6472 21.4814C17.028 22.419 16.2184 22.883 15.556 22.8158C14.9768 22.755 14.4488 22.451 14.1096 21.9806C13.7192 21.4398 13.7192 20.5086 13.7192 18.6398V13.5198C13.7192 11.6542 13.7192 10.7198 14.1096 10.179C14.452 9.70865 14.9768 9.40465 15.556 9.34385C16.2216 9.27345 17.028 9.74065 18.6472 10.675L23.0792 13.2318Z" fill="white"/>
                        </g>
                    </svg>
                </div>
                <div @click="openLink(state.materials?.youtube)">
                    <svg width="47" height="32" viewBox="0 0 47 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M23.4759 32C23.4759 32 37.8861 32 41.4605 31.04C43.473 30.496 44.9866 28.928 45.5178 26.992C46.5 23.44 46.5 15.968 46.5 15.968C46.5 15.968 46.5 8.544 45.5178 5.024C44.9866 3.04 43.473 1.504 41.4605 0.976002C37.8861 0 23.4759 0 23.4759 0C23.4759 0 9.09783 0 5.53955 0.976002C3.55916 1.504 2.01348 3.04 1.44995 5.024C0.5 8.544 0.5 15.968 0.5 15.968C0.5 15.968 0.5 23.44 1.44995 26.992C2.01348 28.928 3.55916 30.496 5.53955 31.04C9.09783 32 23.4759 32 23.4759 32Z" fill="#FF0033"/>
                        <path d="M30.6378 16.0002L18.7412 9.2002V22.8002L30.6378 16.0002Z" fill="white"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="article_content markdown-body" :class="theme" v-html="textMarkdown">
        </div>
    </div>
    <div class="article_footer" onclick="window.backByQueryFunction()">
        <button>Вернуться</button>
    </div>
</template>

<style>

</style>