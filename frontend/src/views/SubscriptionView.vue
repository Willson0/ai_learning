<script>
import IphoneSwitcherComponent from "@/components/IphoneSwitcherComponent.vue";
import {closeAllOverlays, closeOverlay, notify, openOverlay} from "@/utils.js";
import axios from "axios";
import config from "@/config.json";

export default {
    name: 'SubscriptionView',
    components: {IphoneSwitcherComponent},
    data() {
        return {
            paymentLoaded: false,
        }
    },
    mounted () {

    },
    computed: {
        user () {
            return this.$store.state.user;
        }
    },
    methods: {
        closeOverlay, openOverlay,
        splitBy4(str) {
            return str.replace(/(.{4})/g, '$1 ').trim();
        },
        async sendData (field, value) {
            let newUser = {...this.user, [field]: value};
            this.$store.commit('setUser', newUser);

            let data = {};
            data["initData"] = window.Telegram.WebApp.initData;
            data[field] = value;

            await axios.post(config.backend + 'auth/settings', data).then((response) => {

            }).catch((error) => {
                alert (error.response.data.message || 'Ошибка при отправке данных. Попробуйте позже.');
            });
        },
        async openAddCard () {
            openOverlay('subscription_overlay', 'subscription_background')

            if (this.paymentLoaded) return;
            this.paymentLoaded = true;

            await axios.post(config.backend + "payment/linkcard", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                const root = document.documentElement;
                const checkout = new window.YooMoneyCheckoutWidget({
                    confirmation_token: response.data,
                    return_url: "https://" + window.location.hostname,
                    error_callback: function(error) {
                        console.log(error)
                    },
                    customization: {
                        colors: {
                            background: getComputedStyle(root).getPropertyValue('--addiction').trim(),
                            control_primary: getComputedStyle(root).getPropertyValue('--accent').trim(),
                            control_primary_content: "#FFFFFF",
                            text: getComputedStyle(root).getPropertyValue('--text').trim(),
                            border: getComputedStyle(root).getPropertyValue('--divider').trim(),
                            control_secondary: getComputedStyle(root).getPropertyValue('--grey').trim(),
                        }
                    },
                });
                checkout.render('subscription_overlay');
            });
        },
        async deleteCard () {
            if (!confirm('Вы уверены, что хотите отвязать карту?')) return;

            let newUser = {...this.user};
            newUser.card = null;
            newUser.payment_method_id = null;
            this.$store.commit('setUser', newUser);

            closeAllOverlays();
            await axios.post(config.backend + "payment/unlinkcard", {
                initData: window.Telegram.WebApp.initData,
            });
        },
        async activeTrial () {
            if (this.user.payment_method_id == null) return this.openAddCard();
            if (!confirm('Вы действительно хотите активировать пробную подписку?')) return;

            let newUser = {...this.user};
            newUser.used_trial = 1;
            newUser.is_sub = 1;
            newUser.sub_date = new Date();
            newUser.sub_date.setDate(newUser.sub_date.getDate() + 7);
            this.$store.commit('setUser', newUser);

            await axios.post(config.backend + "subscription/trial", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
               notify("Пробная подписка активирована", 0);
            }).catch((error) => {
                notify(error.response.data.message || 'Ошибка при активации подписки', 1);
            });
        }
    },
}
</script>

<template>
    <div class="background now_card_background" @click="closeOverlay('now_card_overlay', 'now_card_background')" style="display: none"></div>
    <div class="overlay subscription_overlay now_card_overlay" style="display: none" v-if="user.card?.first6">
        <div class="overlay_closeArea">
            <svg width="66" height="2" viewBox="0 0 66 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 1H65" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="subscription_overlay_title">Привязанная карта</div>
        <div class="subscription_overlay_inputs">
            <div>
                <input disabled :value="splitBy4(user.card.first6.toString()) + '** **** ' + user.card.last4" placeholder="Номер карты">
            </div>
            <div>
                <div class="subscription_overlay_inputs_date">
                    <input disabled :value="user.card.expiry_month" type="number" min="0" max="12" placeholder="мм">
                    <div>/</div>
                    <input disabled :value="user.card.expiry_year.toString().slice(2)" type="number" min="2020" max="2100" placeholder="гг">
                </div>
                <input disabled value="***" placeholder="CCV">
            </div>
        </div>
        <button @click="deleteCard" style="background-color: var(--error)">Отвязать</button>
    </div>
    <div class="background subscription_background" @click="closeOverlay('subscription_overlay', 'subscription_background')" style="display: none"></div>
    <div class="overlay subscription_overlay" style="display: none">
        <div class="overlay_closeArea">
            <svg width="66" height="2" viewBox="0 0 66 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 1H65" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div id="subscription_overlay"></div>
    </div>
    <div class="subscription">
        <div class="subscription_trial" v-if="user.used_trial !== 1">
            <div class="subscription_trial_title">Пробная подписка на 7 дней</div>
            <div class="subscription_trial_description">Попробуйте расширенные функции с пробной подпиской</div>
            <button @click="activeTrial">Подключить</button>
        </div>
        <div class="subscription_your">
            <div class="subscription_your_title">Ваша подписка</div>
            <div class="home_supportChat_main">
                <div class="home_supportChat_main_background"></div>
                <div class="home_supportChat_main_upper">
                    <div class="home_supportChat_main_upper_header">
                        <div>Тариф</div>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="1" y="1" width="14" height="14" rx="3" stroke="#7B61FF" stroke-width="2"/>
                            <path d="M8 12.4447V7.11133" stroke="#7B61FF" stroke-width="2"/>
                            <path d="M8 3.55566V5.33344" stroke="#7B61FF" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="home_supportChat_main_upper_name">
                        Бесплатный
                    </div>
                </div>
                <div class="home_supportChat_main_downer">
                    <div></div>
                    <button>Улучшить тариф</button>
                </div>
            </div>
            <div class="subscription_your_loyalty">
                <svg width="34" height="35" viewBox="0 0 34 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M34 17.5C34 26.8888 26.3888 34.5 17 34.5C7.61116 34.5 0 26.8888 0 17.5C0 8.11116 7.61116 0.5 17 0.5C26.3888 0.5 34 8.11116 34 17.5Z" fill="#7B61FF"/>
                    <path d="M15.9524 27.5C15.2302 27.5 14.422 27.4524 13.5278 27.3571C12.6508 27.2619 11.8082 27.1 11 26.8714V7.5H22.3492V10.4143H13.9921V14.7857H15.7718C17.5086 14.7857 18.9874 14.9952 20.2083 15.4143C21.4464 15.8333 22.3836 16.5095 23.0198 17.4429C23.6733 18.3571 24 19.5857 24 21.1286C24 23.2619 23.3036 24.8619 21.9107 25.9286C20.5351 26.9762 18.5489 27.5 15.9524 27.5ZM16.1071 24.5571C16.9497 24.5571 17.7235 24.4619 18.4286 24.2714C19.1508 24.081 19.7355 23.7381 20.1825 23.2429C20.6296 22.7476 20.8532 22.0524 20.8532 21.1571C20.8532 20.2048 20.6382 19.481 20.2083 18.9857C19.7956 18.4714 19.1938 18.119 18.4028 17.9286C17.6118 17.7381 16.6832 17.6429 15.6171 17.6429H13.9921V24.4429C14.2328 24.4619 14.5251 24.4905 14.869 24.5286C15.213 24.5476 15.6257 24.5571 16.1071 24.5571Z" fill="white"/>
                </svg>
                <div class="subscription_your_loyalty_text">
                    <div class="subscription_your_loyalty_title">Тратить 20% бонусов при покупке подписки</div>
                    <div class="subscription_your_loyalty_description">У вас {{ user.bonus }} бонусов</div>
                </div>
                <iphone-switcher-component :is-active-prop="user.spend_bonus" @change="sendData('spend_bonus', $event)" />
            </div>
        </div>
        <div class="subscription_payments">
            <div class="subscription_payments_title">Способы оплаты</div>
            <div class="subscription_payments_autopayment">
                <div class="subscription_payments_autopayment_text">Автоплатеж</div>
                <iphone-switcher-component :is-active-prop="user.autopayment" @change="sendData('autopayment', $event)" />
            </div>
            <div class="subscription_payments_addCard" @click="openAddCard" v-if="!user.payment_method_id">
                <svg width="50" height="34" viewBox="0 0 50 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="0.5" y="0.5" width="49" height="33" rx="11.5" stroke="#7B61FF"/>
                    <path d="M24.1 25.1V17.8956H16.9C16.403 17.8956 16.0001 17.4926 16 16.9956C16 16.4985 16.4029 16.0956 16.9 16.0956H24.1V8.9C24.1 8.40294 24.5029 8 25 8C25.4971 8 25.9 8.40294 25.9 8.9V16.0956H33.1C33.5971 16.0956 34 16.4985 34 16.9956C33.9999 17.4926 33.597 17.8956 33.1 17.8956H25.9V25.1C25.9 25.5971 25.4971 26 25 26C24.5029 26 24.1 25.5971 24.1 25.1Z" fill="#7B61FF"/>
                </svg>
                <div>Привязать карту</div>
            </div>
            <div @click="openOverlay('now_card_overlay', 'now_card_background')" class="subscription_payments_addCard" style="padding: 16px;" v-else>
                <svg xmlns="http://www.w3.org/2000/svg" width="800px" height="800px" viewBox="0 0 24 24" fill="none">
                    <path opacity="0.5" d="M9.99976 20H13.9998C17.771 20 19.6566 20 20.8282 18.8284C21.9998 17.6569 21.9998 15.7712 21.9998 12C21.9998 11.5581 21.9981 10.392 21.9962 10H2C1.99811 10.392 1.99976 11.5581 1.99976 12C1.99976 15.7712 1.99976 17.6569 3.17133 18.8284C4.34291 20 6.22852 20 9.99976 20Z" fill="var(--grey)"/>
                    <path d="M9.99484 4H14.0052C17.7861 4 19.6766 4 20.8512 5.11578C21.6969 5.91916 21.9337 7.07507 22 9V10H2V9C2.0663 7.07507 2.3031 5.91916 3.14881 5.11578C4.3234 4 6.21388 4 9.99484 4Z" fill="var(--text)"/>
                    <path d="M12.5 15.25C12.0858 15.25 11.75 15.5858 11.75 16C11.75 16.4142 12.0858 16.75 12.5 16.75H14C14.4142 16.75 14.75 16.4142 14.75 16C14.75 15.5858 14.4142 15.25 14 15.25H12.5Z" fill="var(--text)"/>
                    <path d="M6 15.25C5.58579 15.25 5.25 15.5858 5.25 16C5.25 16.4142 5.58579 16.75 6 16.75H10C10.4142 16.75 10.75 16.4142 10.75 16C10.75 15.5858 10.4142 15.25 10 15.25H6Z" fill="var(--text)"/>
                </svg>
                <div>{{ splitBy4(user?.card?.first6.toString()) }}** **** {{ user.card?.last4 }}</div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>