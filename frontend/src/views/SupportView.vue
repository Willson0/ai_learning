<script>
import axios from 'axios';
import config from "@/config.json"
import {endLoading, notify, toLink} from "@/utils.js";
import PhotoSlider from "@/components/PhotoSlider.vue";
export default {
    name: "SupportView",
    components: {PhotoSlider},
    data () {
        return {
            config: config,
            support: {},
            newMessage: "",
            pictures: [],
            mouseDown: false,
            startX: 0,
            scrollLeft: 0,
            isDragging: false,
            startIndex: null,
            sliderID: -1,
            firstLoading: true,
            interval: null,
        }
    },
    async mounted () {
        window.addEventListener('mouseup', this.mouseup);
        window.addEventListener('mousemove', this.mousemove);

        await this.fetchData();
        this.interval = setInterval(() => {
            this.fetchData();
        }, 10000);
    },
    unmounted () {
        clearInterval(this.interval);

        window.removeEventListener('mouseup', this.mouseup);
        window.removeEventListener('mousemove', this.mousemove);
    },
    methods: {
        async fetchData () {
            await axios.post(config.backend + "support", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                if (this.support.id && !response.data.id) {
                    alert ("Ваша заявка была закрыта. Спасибо за обращение!");
                    return toLink("profile");
                }

                this.support = response.data;

                if (this.firstLoading) {
                    this.firstLoading = false;
                    endLoading("support_loading");

                    requestAnimationFrame(() => {
                        const elem = document.querySelector('.support_dialog');
                        elem.scrollTop = elem.scrollHeight;
                    })
                }
            }).catch((error) => {
                notify(error.response.data.message, 1);
            });
        },
        async sendMessage () {
            this.newMessage = this.newMessage.trim();
            if (this.newMessage.length === 0 && this.pictures.length === 0) return;

            this.support.dialog.push({
                from: "user",
                text: this.newMessage,
                images: this.pictures.map((file) => file.preview),
            })
            requestAnimationFrame(() => {
                const elem = document.querySelector('.support_dialog');
                elem.scrollTo({
                    top: elem.scrollHeight,
                    behavior: 'smooth'
                });
            })

            let fd = new FormData();
            fd.append("message", this.newMessage);
            fd.append("initData", window.Telegram.WebApp.initData);

            for (let img of this.pictures) fd.append("images[]", img.file);

            this.newMessage = "";
            this.pictures = [];

            await axios.post(config.backend + "support/send", fd).then((response) => {
                if (this.support.id && !response.data.id) {
                    alert ("Ваша заявка была закрыта. Спасибо за обращение!");
                    return toLink("profile");
                }

                this.support = response.data;
            }).catch((error) => {
                notify(error.response.data.message, 1);
            });
        },
        addFiles (ev) {
            for (let file of ev.target.files) {
                this.pictures.push({
                    file: file,
                    preview: URL.createObjectURL(file),
                })
            }
            ev.target.value = "";
        },
        removePhoto (index) {
            this.pictures.splice(index, 1);
        },
        mousedown(ev) {
            let el = this.$refs.newsNav;

            document.body.classList.add("grabbing");

            this.mouseDown = true;
            this.startX = ev.pageX - el.offsetLeft;
            this.scrollLeft = el.scrollLeft;
        },
        mousemove (ev) {
            if (!this.mouseDown) return;

            if (Math.abs(ev.pageX - this.startX) > 5) {
                this.isDragging = true
            }

            ev.preventDefault();
            let slider = this.$refs.newsNav;

            const x = ev.pageX - slider.offsetLeft;
            const walk = (x - this.startX) * 1; // 1 = чувствительность
            slider.scrollLeft = this.scrollLeft - walk;
        },
        mouseup (ev) {
            document.body.classList.remove("grabbing");

            this.mouseDown = false;
            setTimeout(() => {
                this.isDragging = false;
            }, 100);
        },
    },
    computed: {

    }
}
</script>

<template>
    <div class="loading support_loading"></div>
    <div class="support">
        <div class="chat">
            <div class="chat_main">
                <div class="chat_main_filler" v-if="0">
                    <svg width="48" height="41" viewBox="0 0 48 41" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 5.125C0 2.29454 2.30254 0 5.14286 0H42.8572C45.6975 0 48 2.29454 48 5.125V35.875C48 38.7055 45.6975 41 42.8571 41H5.14285C2.30253 41 0 38.7055 0 35.875V5.125Z" fill="#7B61FF"/>
                        <path d="M10.2857 17.0833C10.2857 15.1963 11.8207 13.6666 13.7143 13.6666C15.6078 13.6666 17.1429 15.1963 17.1429 17.0833C17.1429 18.9703 15.6078 20.4999 13.7143 20.4999C11.8207 20.4999 10.2857 18.9703 10.2857 17.0833Z" fill="#121212"/>
                        <path d="M30.8571 17.0833C30.8571 15.1963 32.3922 13.6666 34.2857 13.6666C36.1793 13.6666 37.7143 15.1963 37.7143 17.0833C37.7143 18.9703 36.1793 20.4999 34.2857 20.4999C32.3922 20.4999 30.8571 18.9703 30.8571 17.0833Z" fill="#121212"/>
                        <path d="M35.4722 23.9166H12.5278C9.73149 23.9166 8.94466 27.7367 11.5149 28.8344L22.9871 33.734C23.6339 34.0103 24.3661 34.0103 25.0129 33.734L36.4851 28.8344C39.0553 27.7367 38.2685 23.9166 35.4722 23.9166Z" fill="#121212"/>
                    </svg>
                    <div>Задайте интересующий вас вопрос</div>
                </div>
                <div class="chat_main_messages">
                    <div :style="{'max-width': message.images?.length ? '100%' : '', 'text-align': message.images?.length ? 'left' : ''}" :class="`from_${message.from}`" v-for="(message, keyMess) in support.dialog">
                        <photo-slider v-if="sliderID === keyMess" @close="sliderID = -1"
                                      :start-index="startIndex" :images="message.images" />
                        <div class="dialog_main_photos" v-if="message.images?.length"
                             :style="{
                            gridTemplateColumns: message.images.length === 1 ? '1fr'
                              : message.images.length % 3 === 0 ? 'repeat(3, 1fr)'
                              : message.images.length % 2 === 0 ? 'repeat(2, 1fr)'
                              : 'repeat(3, 1fr)'
                          }">
                            <img :src="photo.startsWith('blob:') ? photo : config.storage + photo" :style="{
                            height: message.images.length === 1 ? '300px'
                              : message.images.length % 3 === 0 ? '100px'
                              : message.images.length % 2 === 0 ? '150px'
                              : '100px'
                          }" v-for="(photo, key) in message.images" alt="" @click="startIndex = key; sliderID = keyMess;">
                        </div>
                        <div class="dialog_main_text" v-if="message.text">{{ message.text }}</div>
                    </div>
                </div>
            </div>
            <div v-if="pictures.length" class="dialog_attachment">
                <div @mousedown.stop="mousedown" ref="newsNav">
                    <div v-for="(file, key) in pictures">
                        <div class="dialog_attachment_trash" @click="removePhoto(key)">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.75 6.16667C2.75 5.70644 3.09538 5.33335 3.52143 5.33335L6.18567 5.3329C6.71502 5.31841 7.18202 4.95482 7.36214 4.41691C7.36688 4.40277 7.37232 4.38532 7.39185 4.32203L7.50665 3.94993C7.5769 3.72179 7.6381 3.52303 7.72375 3.34536C8.06209 2.64349 8.68808 2.1561 9.41147 2.03132C9.59457 1.99973 9.78848 1.99987 10.0111 2.00002H13.4891C13.7117 1.99987 13.9056 1.99973 14.0887 2.03132C14.8121 2.1561 15.4381 2.64349 15.7764 3.34536C15.8621 3.52303 15.9233 3.72179 15.9935 3.94993L16.1083 4.32203C16.1279 4.38532 16.1333 4.40277 16.138 4.41691C16.3182 4.95482 16.8778 5.31886 17.4071 5.33335H19.9786C20.4046 5.33335 20.75 5.70644 20.75 6.16667C20.75 6.62691 20.4046 7 19.9786 7H3.52143C3.09538 7 2.75 6.62691 2.75 6.16667Z" fill="#fff"/>
                                <path d="M11.6068 21.9998H12.3937C15.1012 21.9998 16.4549 21.9998 17.3351 21.1366C18.2153 20.2734 18.3054 18.8575 18.4855 16.0256L18.745 11.945C18.8427 10.4085 18.8916 9.6402 18.45 9.15335C18.0084 8.6665 17.2628 8.6665 15.7714 8.6665H8.22905C6.73771 8.6665 5.99204 8.6665 5.55047 9.15335C5.10891 9.6402 5.15777 10.4085 5.25549 11.945L5.515 16.0256C5.6951 18.8575 5.78515 20.2734 6.66534 21.1366C7.54553 21.9998 8.89927 21.9998 11.6068 21.9998Z" fill="#fff"/>
                            </svg>
                        </div>
                        <img draggable="false" :src="file.preview" alt="">
                    </div>
                </div>
            </div>
            <div class="chat_input">
                <label for="attach">
                    <svg width="35" height="36" viewBox="0 0 35 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M24.8046 9.63522C24.5957 9.42628 24.3477 9.26054 24.0747 9.14747C23.8017 9.03439 23.5091 8.9762 23.2136 8.9762C22.9182 8.9762 22.6256 9.03439 22.3526 9.14747C22.0796 9.26054 21.8316 9.42628 21.6226 9.63522L10.6826 20.5752C9.97942 21.2786 9.58441 22.2325 9.5845 23.2271C9.58459 24.2217 9.97979 25.1755 10.6831 25.8787C11.3865 26.5819 12.3404 26.9769 13.335 26.9769C14.3296 26.9768 15.2834 26.5816 15.9866 25.8782L23.6796 18.1852C23.8218 18.0527 24.0099 17.9806 24.2042 17.984C24.3985 17.9875 24.5839 18.0662 24.7213 18.2036C24.8587 18.341 24.9374 18.5264 24.9408 18.7207C24.9442 18.915 24.8721 19.103 24.7396 19.2452L17.0466 26.9382C16.5615 27.4361 15.9823 27.8326 15.3427 28.1049C14.703 28.3771 14.0157 28.5196 13.3206 28.5241C12.6254 28.5286 11.9363 28.395 11.2932 28.1311C10.6501 27.8671 10.0659 27.4781 9.57431 26.9865C9.08276 26.495 8.69373 25.9107 8.42978 25.2676C8.16584 24.6245 8.03223 23.9354 8.03673 23.2403C8.04123 22.5451 8.18373 21.8578 8.45597 21.2182C8.72822 20.5786 9.12478 19.9994 9.62264 19.5142L20.5616 8.57422C21.265 7.87099 22.2189 7.47598 23.2135 7.47607C24.2081 7.47617 25.1619 7.87136 25.8651 8.57472C26.5684 9.27807 26.9634 10.232 26.9633 11.2266C26.9632 12.2212 26.568 13.175 25.8646 13.8782L14.9316 24.8112L14.9236 24.8192L14.9166 24.8262L14.9146 24.8282L14.9116 24.8302C14.484 25.2294 13.9177 25.4463 13.3329 25.4349C12.748 25.4236 12.1905 25.185 11.7787 24.7696C11.3668 24.3542 11.1329 23.7947 11.1265 23.2098C11.1202 22.6249 11.3419 22.0605 11.7446 21.6362L19.5546 13.8262C19.696 13.6895 19.8854 13.6138 20.0821 13.6155C20.2787 13.6171 20.4669 13.6959 20.606 13.8349C20.7451 13.9738 20.8241 14.1619 20.8259 14.3586C20.8277 14.5552 20.7522 14.7447 20.6156 14.8862L12.8056 22.6962C12.664 22.836 12.5837 23.0263 12.5824 23.2253C12.5811 23.4242 12.6589 23.6156 12.7986 23.7572C12.9384 23.8988 13.1287 23.9791 13.3277 23.9805C13.5267 23.9818 13.718 23.904 13.8596 23.7642L24.8046 12.8162C25.0136 12.6073 25.1793 12.3592 25.2924 12.0863C25.4055 11.8133 25.4637 11.5207 25.4637 11.2252C25.4637 10.9297 25.4055 10.6372 25.2924 10.3642C25.1793 10.0912 25.0136 9.84415 24.8046 9.63522Z" fill="#7B61FF"/>
                    </svg>
                </label>
                <input type="file" @change="addFiles" multiple
                       style="display: none" accept="image/*" id="attach">
                <div>
                    <input v-model="newMessage" @keyup.enter="sendMessage" type="text" placeholder="Вопрос">
                </div>
                <svg @click="sendMessage" width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="40" rx="12" fill="#7B61FF"/>
                    <path d="M20 29L20 11M20 11L14.5 14.9322M20 11L25.5 14.9322" stroke="#303030" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>