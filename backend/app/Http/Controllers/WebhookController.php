<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    public function tg (Request $request) {
        $update = Telegram::getWebhookUpdate();

        if ($update->has('message')) {
            $message = $update->getMessage();

            $requestUser = $message["from"];
            $user = User::where("telegram_id", "=", $requestUser["id"])->first();

            if (!$user) {
                $user = User::create([
                    "telegram_id" => $requestUser["id"],
                    "username" => $requestUser["username"] ?? "",
                    "fullname" => $requestUser["first_name"] ??
                            $requestUser["last_name"] ?? $requestUser["username"],
                    "avatar" => $requestUser["photo_url"] ?? "",
                    "autopayment" => true,
                    "bonus" => 0,
                    "tokens" => 10,
                    "spend_bonus" => false,
                    "from_user_id" => null,
                    "card" => null,
                    "pinned_achievements" => json_encode([]),
                    "data" => "{}",
                ]);
            }

            $text = $message->getText();

            if (strpos($text, '/start') === 0) {
                $parts = explode(' ', $text);
                $param = isset($parts[1]) ? $parts[1] : null;

                $referral = User::where("id", $param)->first();
                if ($referral) {
                    $user->from_user_id = $referral->id;
                    $user->save();

                    $bonuses = intval(env("BONUS_FROM_USER"));
                    $referral->bonus += $bonuses;
                    $referral->save();

                    $text = "Пользователь {$user->fullname} перешел по вашей реферальной ссылке!
Вам начислено *$bonuses бонусов*!";

                    $escape_chars = ['[', ']', '(', ')', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
                    foreach ($escape_chars as $char) {
                        $text = str_replace($char, '\\' . $char, $text);
                    }

                    Telegram::sendMessage([
                        "chat_id" => $referral->telegram_id,
                        "text" => $text,
                        "parse_mode" => "MarkdownV2"
                    ]);
                }

                $text = "*Добро пожаловать в наш бот! 👋*
Здесь вы сможете подготовиться к самым сложным экзаменам на 100% с помощью нейросетей.

📚 Начните обучение прямо сейчас — помощь нейросетей доступна каждому!";
                $escape_chars = ['[', ']', '(', ')', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
                foreach ($escape_chars as $char) {
                    $text = str_replace($char, '\\' . $char, $text);
                }

                Telegram::sendPhoto([
                    'chat_id' => $user->telegram_id,
                    'caption' => $text,
                    'parse_mode' => 'MarkdownV2',
                    "photo" => InputFile::create(Storage::disk("public")->path("start_message.jpg")),
                    "reply_markup" => json_encode([
                        "inline_keyboard" => [
                            [
                                [
                                    "text" => "Открыть веб-приложение",
                                    "web_app" => [
                                        "url" => "https://" . env("DOMAIN")
                                    ]
                                ]
                            ]
                        ]
                    ])
                ]);
            }
        }
        return response("ok", 200);
    }
}
