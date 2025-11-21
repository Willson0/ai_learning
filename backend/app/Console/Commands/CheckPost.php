<?php

namespace App\Console\Commands;

use App\Http\utils;
use App\Models\Mailing;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class CheckPost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:check-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запускает каждую секунду проверку постов на время отправки';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Checking posts...");
        $schedules = Mailing::where("next", "<=", Carbon::now("Europe/Moscow"))->where("status", 0)->get();
        foreach ($schedules as $schedule) {
            $schedule->status = 1;
            $schedule->save();

            $post = $schedule->post;
            $users = User::all();

            foreach ($users as $user) {
                $photos = utils::sendToGroupByBot($user->telegram_id, $post->text, $post->attachments);
                if ($photos !== null) {
                    $json = json_encode($photos);
                    if ($post->attachments !== $json) {
                        $post->attachments = $json;
                        $post->save();
                        Log::critical("Post $post->id changed");
                    }
                }
                usleep(50000);
            }

            if ($post->end_count !== null) $post->update(["end_count" => $post->end_count-1]);
            if ($post->end_count === null AND $post->end_time === null) {}
            else if (($post->end_time === null || $post->end_time > Carbon::now("Europe/Moscow"))
                && ($post->end_count === null || $post->end_count > 0)) {
                Mailing::create([
                    "post_id" => $post->id,
                    "next" => Carbon::now("Europe/Moscow")->addMinutes($post->time_repeat),
                    "status" => 0,
                ]);
            }
        }
    }
}
