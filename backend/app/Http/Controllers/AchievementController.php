<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function pin (Achievement $achievement, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $pinned_achievements = json_decode($user->pinned_achievements);
        if (!$pinned_achievements) $pinned_achievements = [];

        if (in_array($achievement->id, $pinned_achievements)) return response()->json($pinned_achievements);
        if (count($pinned_achievements) >= 5) array_shift($pinned_achievements);

        $pinned_achievements[] = $achievement->id;

        $user->pinned_achievements = json_encode($pinned_achievements);
        $user->save();

        return response()->json($pinned_achievements);
    }

    public function unpin (Achievement $achievement, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $pinned_achievements = json_decode($user->pinned_achievements);
        if (!$pinned_achievements) $pinned_achievements = [];

        if (!in_array($achievement->id, $pinned_achievements)) return response()->json($pinned_achievements);

        $key = array_search($achievement->id, $pinned_achievements);
        unset($pinned_achievements[$key]);

        $user->pinned_achievements = json_encode($pinned_achievements);
        $user->save();

        return response()->json($pinned_achievements);
    }
}
