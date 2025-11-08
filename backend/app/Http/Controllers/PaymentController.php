<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use YooKassa\Client;

class PaymentController extends Controller
{
    public function linkCard (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $client = new Client();
        $client->setAuth(env("SHOP_ID"), env("YOOKASSA_API_KEY"));

        $botname = env("BOT_NAME");
        $response = $client->createPayment(
            [
                "amount" => [
                    "value" => "1.00",
                    "currency" => "RUB"
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => 'https://yookassa.ru/',
                ],
                "capture" => true,
                "save_payment_method" => true,
                "description" => "Заказ №72",
                'receipt' => [
                    'customer' => [
                        'email' => "666.well@mail.ru",
                    ],
                    'items' => [
                        [
                            'description' => "Привязка способа оплаты",
                            'quantity' => '1.00',
                            'amount' => [
                                'value' => "1.00",
                                'currency' => 'RUB',
                            ],
                            'vat_code' => 2,
                            'payment_mode' => 'full_payment',
                            'payment_subject' => 'commodity',
                        ],
                    ],
                ],
            ],
            $user->id . "_" . time() . "_linkCard"
        );
        $paymentID = $response->id;
        $payment = Payment::create([
            "user_id" => $user->id,
            "is_autopayment" => 0,
            "payment_id" => $paymentID,
            "is_bought" => false,
            "summa" => 1,
            "amount" => 0,
        ]);

//        return response()->json($response["confirmation"]["confirmation_token"]);
        return response()->json(["url" => $response->confirmation->getConfirmationUrl()]);
    }

    public function unLinkCard (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $user->payment_method_id = null;
        $user->card = null;

        $user->save();
        return response()->json(["ok" => true]);
    }

    public function webhook (Request $request)
    {
        Log::info($request);
        $payment = Payment::where("payment_id", $request->object["id"] ?? $request["id"])->first();

        if ($payment->is_bought) return response('ok', 200);
        if ($request->event === "payment.succeeded" || $request->status === "succeeded") {
            $user = User::find($payment->user_id);
            if (!$user) {
                Log::error("User not found $payment->id: $payment->user_id");
                return response('ok', 200);
            }
            if ($request->object["payment_method"]["saved"] ?? null) {
                if (!$user->payment_method_id) {
                    Telegram::sendMessage([
                        "chat_id" => $user->telegram_id,
                        "text" => "💳 Ваша карта успешно привязана!",
                        "parse_mode" => "HTML"
                    ]);
                }
                $user->payment_method_id = $request->object["payment_method"]["id"];
            }
            if ($request->object["payment_method"]["card"] ?? null)
                $user->card = json_encode($request->object["payment_method"]["card"]);

            $payment->is_bought = true;

            if ($payment->summa > 10) {
                $user->sub_date = Carbon::now()->addDays(30);
                $user->is_sub = true;
            }
            $user->save();
        } else if (($request->event === "payment.canceled" || $request->status === "canceled") && $payment->is_autopayment) {
            if ($payment->autopayment == 1) {
                $user = User::find($payment->user_id);
                $user->is_sub = 0;
                $user->sub_date = null;
                $user->save();
            }

            $payment->delete();
        } else if ($request->event === "payment.waiting_for_capture" || $request->status === "waiting_for_capture") {
            $object = $request->input('object');
            $paymentId = $object['id'];

            $client = new Client();
            $client->setAuth(env('SHOP_ID'), env('YOOKASSA_API_KEY'));

            try {
                $client->capturePayment([], $paymentId, uniqid('capture_', true));
            } catch (\Throwable $e) {
                Log::error('YooKassa capturePayment error: ' . $e->getMessage());
            }
        }

        $payment->save();
        return response()->json(["ok" => true]);
    }
}
