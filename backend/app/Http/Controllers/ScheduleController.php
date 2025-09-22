<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Probe;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function store (Probe $probe, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if (!$request->has("date")) abort(400);

        $old = Schedule::where("user_id", $user->id)->where("probe_id", $probe->id)->first();
        if ($old) $old->delete();

        Schedule::create([
            "user_id" => $user->id,
            "probe_id" => $probe->id,
            "date" => Carbon::parse($request["date"], 'UTC'),
        ]);

        Notification::create([
            "title" => "Новое расписание",
            "body" => "Вы записались на пробник: {$probe->title}",
            "read" => 0,
            "user_id" => $user->id,
        ]);

        return response()->json(["success" => true]);
    }
}
