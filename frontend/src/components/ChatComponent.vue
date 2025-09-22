<script>
import axios from "axios";
import config from "@/config.json";
import {deepParse, notify, toLink} from "@/utils.js";

export default {
    name: "ChatComponent",
    data () {
        return {
            chat: null,
            newMessage: "",
            pictures: [],
            isLoading: false,
            isFirstLoading: true,
            mediaRecorder: null,
            isRecording: false,

            recordingTimer: 0,
            recordingInterval: null,
            audioBlob: null,
            audioChunks: [],
            waveform: "",
            audio: null,
            isAudioPaused: false,
            activeAudio: -2,
        }
    },
    async mounted () {
        await this.initChat();
    },
    methods: {
        toLink,
        toFormatTime (seconds) {
            let minutes = Math.floor(seconds / 60);
            let secondsLeft = seconds % 60;
            return minutes + ":" + secondsLeft.toString().padStart(2, "0");
        },
        async initChat () {
            if (!this.user.id) return;
            if (this.chat_id === -1) return;
            if (!this.isFirstLoading) return;

            this.isFirstLoading = false;
            this.chat = this.user.chats?.find((ch) => ch.id === this.chat_id);
            this.$emit("chatload", this.chat);

            requestAnimationFrame(() => {
                this.$nextTick(() => {
                    let el = document.querySelector('.chat_main');
                    el.scrollTo({
                        top: el.scrollHeight,
                        behavior: 'smooth'
                    });
                });
            })

            for (let mes in this.chat.dialog) {
                if (this.chat.dialog[mes].audio != null && this.chat.dialog[mes].blob == null) {
                    let response = await fetch(config.backend + "getstorage?filename=" + this.chat.dialog[mes].audio);
                    let blob = await response.blob();
                    this.chat.dialog[mes].blob = blob;

                    this.generateAudioSVG(blob).then((response) => {
                        this.chat.dialog[mes].svg = response;
                    });
                }
            }
        },
        async storeChat () {
            await axios.post(config.backend + "chat", {
                initData: window.Telegram.WebApp.initData,
                name: "Новый чат по заданию",
                subjects: [],
            }).then((response) => {
                let chats = [...this.user.chats];
                chats = chats.filter((item) => item.id != null);
                chats.push(response.data);

                let newUser = {...this.user, chats: chats};
                this.$store.commit('setUser', newUser);

                this.chat = deepParse(response.data);
                this.sendMessage();
            }).catch((error) => {
                notify(error.response?.data?.message || "Ошибка на сервере", 1);
            })
        },
        async sendMessage () {
            if (this.isLoading) return;
            if (this.user.tokens === 0) return notify("У вас закончились токены", 1);
            if (this.chat == null && this.chat_id === -1) return this.storeChat();

            this.newMessage = this.newMessage.trim();
            if (this.newMessage.length === 0 && this.pictures.length === 0 && this.audioBlob == null) return;

            if (this.audioBlob) {
                const newBlob = this.audioBlob.slice(0, this.audioBlob.size, this.audioBlob.type);
                this.chat.dialog.push({
                    role: "user",
                    content: "1",
                    audio: "1",
                    blob: newBlob,
                    svg: this.waveform,
                })
            } else if (this.pictures.length > 0) {
                this.chat.dialog.push({
                    role: "user",
                    content: [
                        {
                            type: "text",
                            text: this.newMessage,
                        },
                        {
                            type: "image_url",
                            image_url: {
                                url: this.pictures[0].preview,
                        }
                        }
                    ]
                })
            } else this.chat.dialog.push({
                        role: "user",
                        content: this.newMessage,
                    })
            requestAnimationFrame(() => {
                const elem = document.querySelector('.chat_main');
                elem.scrollTo({
                    top: elem.scrollHeight,
                    behavior: 'smooth'
                });
            })

            let fd = new FormData();
            fd.append("initData", window.Telegram.WebApp.initData);

            if (!this.audioBlob) {
                fd.append("content", this.newMessage);
                for (let img of this.pictures) fd.append("image", img.file);
            } else {
                fd.append("audio", this.audioBlob);
            }

            this.newMessage = "";
            this.pictures = [];

            this.isLoading = true;
            let response;
            if (!this.audioBlob) {
                response = await fetch(config.backend + `chat/${this.chat.id}/send`, {
                    method: 'POST',
                    body: fd
                });
            } else {
                this.audioBlob = null;
                response = await fetch(config.backend + `chat/${this.chat.id}/audio`, {
                    method: 'POST',
                    body: fd,
                });
            }
            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            this.chat.dialog.push({
                role: "assistant",
                content: "",
            });

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                const chunk = decoder.decode(value);
                if (chunk.startsWith("[Stream error]")) {
                    notify("Ошибка при генерации. Попробуйте позже", 1);
                    this.chat.dialog.pop();
                    break;
                }
                this.chat.dialog[this.chat.dialog.length - 1].content += chunk;
            }

            requestAnimationFrame(() => {
                const elem = document.querySelector('.chat_main');
                elem.scrollTo({
                    top: elem.scrollHeight,
                    behavior: 'smooth'
                });
            })

            let newUser = {...this.user};

            let index = newUser.chats.findIndex((ch) => ch.id === this.chat_id);
            newUser.chats[index] = this.chat;
            newUser.tokens--;

            this.$store.commit("setUser", newUser);
            this.isLoading = false;
        },
        addFiles (ev) {
            this.pictures = [];
            // for (let file of ev.target.files) {
            let file = ev.target.files[0];
                this.pictures.push({
                    file: file,
                    preview: URL.createObjectURL(file),
                })
            // }
            ev.target.value = "";
        },
        removePhoto (index) {
            this.pictures.splice(index, 1);
        },
        async activeMicrophone (ev) {
            if (this.isLoading) return;

            this.isRecording = true;
            ev.preventDefault();

            this.recordingInterval = setInterval(() => {
                this.recordingTimer += 1;
            }, 1000);

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

                try {
                    this.mediaRecorder = new MediaRecorder(stream);
                } catch (err) {
                    console.error("MediaRecorder error:", err);
                }

                this.mediaRecorder.ondataavailable = (event) => {
                    this.audioChunks.push(event.data);
                };

                this.mediaRecorder.onerror = e => {
                    console.error('Recorder error:', e.error);
                };

                this.mediaRecorder.start();
            } catch (error) {
                notify("Доступ к микрофону запрещен!", 1);
                this.cancelRecording();
            }
        },
        cancelRecording () {
            this.mediaRecorder.stop();
            this.mediaRecorder = null;

            this.isRecording = false;
            this.recordingTimer = 0;
            this.audioBlob = null;
            clearInterval(this.recordingInterval);
        },
        async inactiveMicrophone (needSend = false) {
            if (this.mediaRecorder) {
                this.mediaRecorder.stop();
                this.mediaRecorder.onstop = async () => {
                    this.audioBlob = new Blob(this.audioChunks);
                    this.audioChunks = [];

                    this.generateAudioSVG(this.audioBlob).then((response) => {
                        this.waveform = response;
                        if (needSend) this.sendMessage();
                    });
                };
            }
            this.cancelRecording();
        },
        async generateAudioSVG (blob) {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const arrayBuffer = await blob.arrayBuffer();
            const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

            const channelData = audioBuffer.getChannelData(0);
            const samples = 40;
            const chunkSize = Math.floor(channelData.length / samples);
            const waveform = [];

            for(let i = 0; i < samples; i++) {
                let sum = 0;
                for(let j = 0; j < chunkSize; j++) {
                    sum += Math.abs(channelData[i * chunkSize + j]);
                }
                waveform.push(sum / chunkSize);
            }
            const width = 300;
            const height = 40;
            const barWidth = width / waveform.length;

            const sorted = [...waveform].sort((a, b) => a - b);
            const p95index = Math.floor(sorted.length * 0.95);
            const perc95 = sorted[p95index] || 1;

            const normalized = waveform.map(v => v / perc95);
            const clipped = normalized.map(v => Math.min(v, 1));

            const minBarHeight = 6;
            return clipped.map((v, i) => {
                const barHeight = Math.max(v * height, minBarHeight);
                return `<rect
        x="${i * barWidth}"
        y="${(height - barHeight) / 2}"
        width="${barWidth - 2}"
        height="${barHeight}"
        rx="2" ry="2" fill="#fff"/>`;
            }).join('');
        },
        async playAudio (blob, svg, key) {
            if (this.activeAudio === key) {
                if (this.audio != null && !this.audio.paused) {
                    this.isAudioPaused = true;
                    return this.audio.pause();
                } else if (this.audio != null) {
                    this.isAudioPaused = false;
                    return this.audio.play();
                }
            }

            document.querySelectorAll('.waveform rect').forEach((rect) => {
                rect.setAttribute('fill', '#fff');
            });
            if (this.audio != null) this.audio.pause();

            this.activeAudio = key;
            this.audio = new Audio(URL.createObjectURL(blob));
            this.isAudioPaused = false;
            await this.audio.play();

            this.audio.addEventListener('ended', () => {
                this.isAudioPaused = true;
            });

            svg = svg.closest('div').querySelector('.waveform');
            let audioElem = svg.querySelectorAll('rect');
            const barsCount = audioElem.length;
            this.audio.addEventListener('timeupdate', () => {
                const progress = this.audio.currentTime / (this.audio.duration || 1);
                const activeBars = Math.floor(barsCount * progress);

                audioElem.forEach((bar, i) => {
                    if (i < activeBars) bar.setAttribute('fill', 'var(--main)');
                    else bar.setAttribute('fill', '#fff');
                });
            });

            svg.addEventListener('click', (ev) => {
                if (key !== this.activeAudio) return;

                const rect = svg.getBoundingClientRect();
                const x = ev.clientX - rect.left;
                const percent = x / rect.width;

                const duration = this.audio.duration || 1;
                this.audio.currentTime = percent * duration;
            });
        },
    },
    props: {
        chat_id: {
            type: Number,
            required: true,
        }
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
    },
    watch: {
        user () {
            this.initChat();
        },
        audioBlob () {
            let send = this.$refs.send_button;
            let voice = this.$refs.voice_button;

            let oldEl = this.audioBlob != null ? voice : send;
            let newEl = this.audioBlob != null ? send : voice;

            oldEl.style.opacity = '0';
            oldEl.addEventListener('transitionend', () => {
                oldEl.style.display = 'none';
                newEl.style.display = '';
                newEl.style.opacity = '1';
            }, {once: true});
        },
        newMessage () {
            let send = this.$refs.send_button;
            let voice = this.$refs.voice_button;

            let oldEl = this.newMessage.trim().length > 0 ? voice : send;
            let newEl = this.newMessage.trim().length > 0 ? send : voice;

            oldEl.style.opacity = '0';
            oldEl.addEventListener('transitionend', () => {
                oldEl.style.display = 'none';
                newEl.style.display = '';
                newEl.style.opacity = '1';
            }, {once: true});
        }
    }
}
</script>

<template>
    <div class="chat">
        <div class="chat_main">
            <div class="chat_main_filler" v-if="chat == null || chat?.dialog?.filter(mes => mes.role !== 'system').length === 0">
                <svg width="48" height="41" viewBox="0 0 48 41" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 5.125C0 2.29454 2.30254 0 5.14286 0H42.8572C45.6975 0 48 2.29454 48 5.125V35.875C48 38.7055 45.6975 41 42.8571 41H5.14285C2.30253 41 0 38.7055 0 35.875V5.125Z" fill="#7B61FF"/>
                    <path d="M10.2857 17.0833C10.2857 15.1963 11.8207 13.6666 13.7143 13.6666C15.6078 13.6666 17.1429 15.1963 17.1429 17.0833C17.1429 18.9703 15.6078 20.4999 13.7143 20.4999C11.8207 20.4999 10.2857 18.9703 10.2857 17.0833Z" fill="#121212"/>
                    <path d="M30.8571 17.0833C30.8571 15.1963 32.3922 13.6666 34.2857 13.6666C36.1793 13.6666 37.7143 15.1963 37.7143 17.0833C37.7143 18.9703 36.1793 20.4999 34.2857 20.4999C32.3922 20.4999 30.8571 18.9703 30.8571 17.0833Z" fill="#121212"/>
                    <path d="M35.4722 23.9166H12.5278C9.73149 23.9166 8.94466 27.7367 11.5149 28.8344L22.9871 33.734C23.6339 34.0103 24.3661 34.0103 25.0129 33.734L36.4851 28.8344C39.0553 27.7367 38.2685 23.9166 35.4722 23.9166Z" fill="#121212"/>
                </svg>
                <div>Задайте интересующий вас вопрос</div>
            </div>
            <div class="chat_main_messages">
                <div v-for="(message, key) in chat?.dialog" :class="'from_' + message.role" v-show="message.role !== 'system'">
                    <div v-if="message.audio != null" class="chat_main_messages_audio">
                        <svg class="waveform" v-html="message.svg" preserveAspectRatio="none" viewBox="0 0 300 40"></svg>
                        <svg v-if="audio == null || activeAudio !== key || isAudioPaused" @click="playAudio(message.blob, $event.target.closest('div'), key)" width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.6658 7.13397C13.3324 7.51888 13.3324 8.48112 12.6658 8.86602L2.16577 14.9282C1.4991 15.3131 0.665771 14.832 0.665771 14.0622L0.665771 1.93782C0.665771 1.16802 1.4991 0.686896 2.16577 1.0718L12.6658 7.13397Z" fill="white"/>
                        </svg>
                        <svg v-else @click="playAudio(this.audioBlob, $event.target.closest('div'), key)" width="10" height="16" style="width: 18px; height: 18px; min-width: unset;" viewBox="0 0 10 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 0C1.55228 0 2 0.447715 2 1V17C2 17.5523 1.55228 18 1 18C0.447715 18 0 17.5523 0 17V1C0 0.447715 0.447715 0 1 0ZM9 0C9.55229 0 10 0.447715 10 1V17C10 17.5523 9.55229 18 9 18C8.44771 18 8 17.5523 8 17V1C8 0.447715 8.44771 0 9 0Z" fill="white"/>
                        </svg>
                    </div>
                    <template v-else>
                        <div style="white-space: pre-line;">{{ typeof message.content === "string" ? message.content : message.content?.find(item => item.type === "text").text }}</div>
                        <div class="chat_main_messages_picture" v-if="typeof message.content !== 'string'">
                            <img :src="message.content?.find(item => item.type === 'image_url')?.image_url?.url" alt="">
                        </div>
                    </template>
                </div>
                <div class="chat_main_messages_noTokens" v-if="user.tokens <= 0">
                    <div>Вы потратили бесплатные запросы. Для неограниченного количества запросов оформите подписку</div>
                    <button @click="toLink('subscription')">Оформить</button>
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
        <div class="chat_input" v-if="isRecording === false">
            <label for="attach" v-show="audioBlob == null">
                <svg width="35" height="36" viewBox="0 0 35 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M24.8046 9.63522C24.5957 9.42628 24.3477 9.26054 24.0747 9.14747C23.8017 9.03439 23.5091 8.9762 23.2136 8.9762C22.9182 8.9762 22.6256 9.03439 22.3526 9.14747C22.0796 9.26054 21.8316 9.42628 21.6226 9.63522L10.6826 20.5752C9.97942 21.2786 9.58441 22.2325 9.5845 23.2271C9.58459 24.2217 9.97979 25.1755 10.6831 25.8787C11.3865 26.5819 12.3404 26.9769 13.335 26.9769C14.3296 26.9768 15.2834 26.5816 15.9866 25.8782L23.6796 18.1852C23.8218 18.0527 24.0099 17.9806 24.2042 17.984C24.3985 17.9875 24.5839 18.0662 24.7213 18.2036C24.8587 18.341 24.9374 18.5264 24.9408 18.7207C24.9442 18.915 24.8721 19.103 24.7396 19.2452L17.0466 26.9382C16.5615 27.4361 15.9823 27.8326 15.3427 28.1049C14.703 28.3771 14.0157 28.5196 13.3206 28.5241C12.6254 28.5286 11.9363 28.395 11.2932 28.1311C10.6501 27.8671 10.0659 27.4781 9.57431 26.9865C9.08276 26.495 8.69373 25.9107 8.42978 25.2676C8.16584 24.6245 8.03223 23.9354 8.03673 23.2403C8.04123 22.5451 8.18373 21.8578 8.45597 21.2182C8.72822 20.5786 9.12478 19.9994 9.62264 19.5142L20.5616 8.57422C21.265 7.87099 22.2189 7.47598 23.2135 7.47607C24.2081 7.47617 25.1619 7.87136 25.8651 8.57472C26.5684 9.27807 26.9634 10.232 26.9633 11.2266C26.9632 12.2212 26.568 13.175 25.8646 13.8782L14.9316 24.8112L14.9236 24.8192L14.9166 24.8262L14.9146 24.8282L14.9116 24.8302C14.484 25.2294 13.9177 25.4463 13.3329 25.4349C12.748 25.4236 12.1905 25.185 11.7787 24.7696C11.3668 24.3542 11.1329 23.7947 11.1265 23.2098C11.1202 22.6249 11.3419 22.0605 11.7446 21.6362L19.5546 13.8262C19.696 13.6895 19.8854 13.6138 20.0821 13.6155C20.2787 13.6171 20.4669 13.6959 20.606 13.8349C20.7451 13.9738 20.8241 14.1619 20.8259 14.3586C20.8277 14.5552 20.7522 14.7447 20.6156 14.8862L12.8056 22.6962C12.664 22.836 12.5837 23.0263 12.5824 23.2253C12.5811 23.4242 12.6589 23.6156 12.7986 23.7572C12.9384 23.8988 13.1287 23.9791 13.3277 23.9805C13.5267 23.9818 13.718 23.904 13.8596 23.7642L24.8046 12.8162C25.0136 12.6073 25.1793 12.3592 25.2924 12.0863C25.4055 11.8133 25.4637 11.5207 25.4637 11.2252C25.4637 10.9297 25.4055 10.6372 25.2924 10.3642C25.1793 10.0912 25.0136 9.84415 24.8046 9.63522Z" fill="#7B61FF"/>
                </svg>
            </label>
            <input type="file" @change="addFiles"
                   style="display: none" accept="image/*" id="attach">
            <div>
                <input v-show="audioBlob == null" v-model="newMessage" @keyup.enter="sendMessage" type="text" placeholder="Вопрос">
                <div v-show="audioBlob != null" class="chat_input_record">
                    <svg @click="playAudio(this.audioBlob, $event.target.closest('div'), -1)" v-if="audio == null || activeAudio !== -1 || isAudioPaused" style="width: 18px; height: 18px; min-width: unset;" class="chat_input_voice_send" width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.5 7.13397C13.1667 7.51888 13.1667 8.48112 12.5 8.86602L2 14.9282C1.33333 15.3131 0.499999 14.832 0.499999 14.0622L0.5 1.93782C0.5 1.16802 1.33333 0.686896 2 1.0718L12.5 7.13397Z" fill="white"/>
                    </svg>
                    <svg v-else @click="playAudio(this.audioBlob, $event.target.closest('div'), -1)" width="10" height="18" style="width: 18px; height: 18px; min-width: unset;" viewBox="0 0 10 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 0C1.55228 0 2 0.447715 2 1V17C2 17.5523 1.55228 18 1 18C0.447715 18 0 17.5523 0 17V1C0 0.447715 0.447715 0 1 0ZM9 0C9.55229 0 10 0.447715 10 1V17C10 17.5523 9.55229 18 9 18C8.44771 18 8 17.5523 8 17V1C8 0.447715 8.44771 0 9 0Z" fill="white"/>
                    </svg>
                    <svg v-html="waveform" class="waveform" ref="audioSVG" preserveAspectRatio="none" viewBox="0 0 300 40"></svg>
                </div>
            </div>
            <svg ref="send_button" style="display: none" @click="sendMessage" width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="12" fill="#7B61FF"/>
                <path d="M20 29L20 11M20 11L14.5 14.9322M20 11L25.5 14.9322" stroke="var(--text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg @click="activeMicrophone" ref="voice_button" width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M23.5 13.5C23.5 11.567 21.933 10 20 10C18.067 10 16.5 11.567 16.5 13.5V20C16.5 21.933 18.067 23.5 20 23.5C21.933 23.5 23.5 21.933 23.5 20V13.5Z" fill="#7B61FF" stroke="#7B61FF" stroke-width="2" stroke-linejoin="round"/>
                <path d="M12.5 19.5C12.5 23.642 15.858 27 20 27M20 27C24.142 27 27.5 23.642 27.5 19.5M20 27V30" stroke="#7B61FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="chat_input_recording" v-else>
            <div class="chat_input_recording_cancel" @click="cancelRecording">Отмена</div>
            <div class="chat_input_recording_time">
                <div class="circle"></div>
                <div>{{ toFormatTime(recordingTimer) }}</div>
            </div>
            <button class="chat_input_recording_pause" @click="inactiveMicrophone">
                <svg width="10" height="18" viewBox="0 0 10 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 0C1.55228 0 2 0.447715 2 1V17C2 17.5523 1.55228 18 1 18C0.447715 18 0 17.5523 0 17V1C0 0.447715 0.447715 0 1 0ZM9 0C9.55229 0 10 0.447715 10 1V17C10 17.5523 9.55229 18 9 18C8.44771 18 8 17.5523 8 17V1C8 0.447715 8.44771 0 9 0Z" fill="white"/>
                </svg>
            </button>
            <button class="chat_input_recording_send" @click="inactiveMicrophone(true)">
                <svg width="14" height="20" viewBox="0 0 14 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 19L7 0.999999M7 0.999999L1.5 4.93222M7 0.999999L12.5 4.93222" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>