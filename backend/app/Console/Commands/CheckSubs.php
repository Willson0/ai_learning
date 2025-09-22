<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use YooKassa\Client;

class CheckSubs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subs:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::whereNotNull("sub_date")->where('sub_date', '<=', Carbon::now())->get();
        foreach ($users as $user) {
            if ($user->autopayment == 1 && $user->payment_method_id != null) {
                $client = new Client();
                $client->setAuth(env("SHOP_ID"), env("YOOKASSA_API_KEY"));
                $response = $client->createPayment(
                    [
                        'amount' => [
                            'value' => env("SUB_PRICE"),
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
                    "is_autopayment" => 1,
                    "payment_id" => $paymentID,
                    "is_bought" => false,
                    "summa" => intval(env("SUB_PRICE")),
                    "days" => 30,
                ]);
            }
        }
    }
}
