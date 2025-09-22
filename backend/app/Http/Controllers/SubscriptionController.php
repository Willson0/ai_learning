<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use YooKassa\Client;

class SubscriptionController extends Controller
{
    public function trial (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if ($user->user_trial == 1) abort(403,"Пробная подписка уже использовалась");
        if ($user->is_sub == 1) abort(403,"У вас уже есть подписка");
        if ($user->payment_method_id == null) abort(403,"У вас не выбрана оплата");

        $user->used_trial = 1;
        $user->is_sub = 1;
        $user->sub_date = Carbon::now()->addDays(7);
        $user->save();

        return response()->json(["ok" => true]);
    }

    public function buy (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if ($user->is_sub == 1) abort(403,"У вас уже есть подписка");
        if ($user->payment_method_id == null) abort(403,"У вас не выбрана оплата");

        $client = new Client();
        $client->setAuth(env("SHOP_ID"), env("YOOKASSA_API_KEY"));
        $response = $client->createPayment(
            [
                'amount' => [
                    'value' =>  env("SUB_PRICE"),
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
            "summa" => intval(env("SUB_PRICE")),
            "days" => 30,
        ]);

        return response()->json(["ok" => true]);
    }
}
