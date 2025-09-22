<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class CheckSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:check';

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
        $active = Schedule::where("date", "<=", Carbon::now("UTC"))->with("user")->with("probe")->get();
        foreach ($active as $task) {
            $title = $task->probe->title;
            $text = "⏰ *Вам пора пройти пробник «{$title}»!*\n\nНе откладывайте, чтобы успеть подготовиться вовремя.";
            $escape_chars = ['[', ']', '(', ')', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
            foreach ($escape_chars as $char)
                $text = str_replace($char, '\\' . $char, $text);

            try {
                Telegram::sendMessage([
                    "chat_id" => $task->user->telegram_id,
                    "text" => $text,
                    'parse_mode' => 'MarkdownV2',
                    "reply_markup" => json_encode([
                        "inline_keyboard" => [
                            [
                                [
                                    "text" => "Открыть пробник",
                                    "web_app" => [
                                        "url" => "https://" . env("DOMAIN") . "?s=probe-list&id={$task->probe->id}"
                                    ]
                                ]
                            ]
                        ]
                    ])
                ]);

                $task->delete();
            } catch (Exception $e) {
                Log::error($e);
            }
        }
    }
}
