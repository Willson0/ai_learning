<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use YooKassa\Client;

class SubscriptionController extends Controller
{
    public function trial (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if ($user->user_trial == 1) abort(403,"Пробная подписка уже использовалась");
        if ($user->is_sub == 1) abort(403,"У вас уже есть подписка");
        if ($user->payment_method_id == null) abort(403,"У вас не выбрана оплата");

        $botToken = env("TELEGRAM_BOT_TOKEN");
        $apiUrl = "https://api.telegram.org/bot{$botToken}/getChatMember";

        $response = Http::get($apiUrl, [
            'chat_id' => env("TG_CHANNEL"),
            'user_id' => $user->telegram_id,
        ]);

        $inChannel = false;
        if ($response->failed()) $inChannel = false;
        else {
            $result = $response->json();
            if (isset($result['ok']) && $result['ok']) {
                $status = $result['result']['status'];
                if ($status === 'member' || $status === 'administrator' || $status === 'creator')
                    $inChannel = true;
                else $inChannel = false;
            }
        }
        if (!$inChannel) return response(json_encode(["channel" => env("TG_CHANNEL")]), 420);

        $user->used_trial = 1;
        $user->is_sub = 1;
        $user->sub_date = Carbon::now()->addDays(7);
        $user->save();

        return response()->json(["ok" => true]);
    }

    public function buy (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if ($user->is_sub == 1) abort(403,"У вас уже есть подписка");

        $formattedPrice = env('SUB_PRICE', '300.00');
        if ($user->spend_bonus && $user->bonus > 0) {
            $subPrice = env('SUB_PRICE', '300.00');

            $subPriceValue = floatval($subPrice);
            $maxPoints = $subPriceValue * 0.2;
            $usedPoints = min($user->bonus, $maxPoints);
            $finalPrice = $subPriceValue - $usedPoints;

            $user->bonus -= $usedPoints;
            $user->save();

            $formattedPrice = number_format($finalPrice, 2, '.', '');
        }

        $client = new Client();
        $client->setAuth(env("SHOP_ID"), env("YOOKASSA_API_KEY"));
        if ($user->payment_method_id == null)
            $response = $client->createPayment(
                [
                    "amount" => [
                        "value" => $formattedPrice,
                        "currency" => "RUB"
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => 'https://yookassa.ru/',
                    ],
                    "capture" => true,
                    "save_payment_method" => true,
                    "description" => "Подписка на 30 дней",
                ],
                $user->id . "_sub_" . time()
            );
        else $response = $client->createPayment(
            [
                'amount' => [
                    'value' =>  $formattedPrice,
                    'currency' => 'RUB',
                ],
                'capture' => true,
                'payment_method_id' => $user->payment_method_id,
                'description' => "Подписка на 30 дней",
            ],
            $user->id . "_sub_" . time()
        );
        $paymentID = $response->id;

        $payment = Payment::create([
            "user_id" => $user->id,
            "is_autopayment" => 0,
            "payment_id" => $paymentID,
            "is_bought" => false,
            "summa" => intval($formattedPrice),
            "amount" => 30,
        ]);

        if ($user->payment_method_id == null)
            return response()->json(["url" => $response->confirmation->getConfirmationUrl()]);
        else return response()->json(["ok" => true]);
    }
}
