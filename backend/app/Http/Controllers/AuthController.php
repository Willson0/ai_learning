<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthSettingsRequest;
use App\Http\utils;
use App\Models\Achievement;
use App\Models\Ad;
use App\Models\Chat;
use App\Models\Course;
use App\Models\FriendRequest;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Probe;
use App\Models\Schedule;
use App\Models\State;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserLesson;
use Dompdf\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function profile (Request $request)
    {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->first();

        $isFirst = false;
        if (!$user || !$user->avatar) $isFirst = true;

        if (!$user) {
            $user = User::create([
                "telegram_id" => $request["initData"]["user"]["id"],
                "username" => $request["initData"]["user"]["username"] ?? "",
                "fullname" => $request["initData"]["user"]["first_name"]
                    ?? $request["initData"]["user"]["last_name"]
                        ?? $request["initData"]["user"]["username"],
                "avatar" => $request["initData"]["user"]["photo_url"],
                "autopayment" => true,
                "bonus" => 0,
                "tokens" => 10,
                "spend_bonus" => false,
                "from_user_id" => null,
                "card" => null,
                "pinned_achievements" => json_encode([]),
                "data" => "{}",
            ]);
            $user = User::find($user->id);
        } else {
            $user->update([
                "fullname" => $request["initData"]["user"]["first_name"]
                    ?? $request["initData"]["user"]["last_name"]
                        ?? $request["initData"]["user"]["username"],
                "username" => $request["initData"]["user"]["username"] ?? "",
                "avatar" => $request["initData"]["user"]["photo_url"]
            ]);
        }

        $user->levels = json_decode(env("LEVELS"), true);
        $user->subjects = Subject::all();
        $user->chats = Chat::where("user_id", $user->id)->get();
        $user->states = State::all();
        $user->probes = Probe::withCount('variants')->get();
        $user->total_points = UserLesson::where("user_id", $user->id)->sum('points');
        $user->achievements = Achievement::all();

        $user->referrals = User::where("from_user_id", $user->id)
            ->select('id', 'fullname', 'avatar')->get();

        $user->friends = User::join('friend_requests', function($join) use ($user) {
            $join->on('users.id', '=', 'friend_requests.sender_id')
                ->orOn('users.id', '=', 'friend_requests.receiver_id');
        })
            ->leftJoin('user_lessons', 'users.id', '=', 'user_lessons.user_id')
            ->where(function($q) use ($user) {
                $q->where('friend_requests.sender_id', $user->id)
                    ->orWhere('friend_requests.receiver_id', $user->id);
            })
            ->where('users.id', '!=', $user->id)
            ->groupBy('users.id', 'users.fullname', 'users.avatar', 'friend_requests.id', 'friend_requests.sender_id', 'friend_requests.receiver_id', 'friend_requests.is_accepted')
            ->select(
                'users.id',
                'users.fullname',
                'users.avatar',
                'friend_requests.id as request_id',
                'friend_requests.sender_id as sender_id',
                'friend_requests.receiver_id as receiver_id',
                "friend_requests.is_accepted as is_accepted",
                DB::raw('COALESCE(SUM(user_lessons.points), 0) as total_points')
            )
            ->get();

        $user->notifications = Notification::where("user_id", $user->id)->get();
        $user->schedule = Schedule::where("user_id", $user->id)->get();
        $user->ads = Ad::all();

        $user->courses = Course::all();
        foreach ($user->courses as &$course) {
            $course->lessons = Lesson::where("course_id", $course->id)->
                leftJoin('user_lessons', function($join) use ($user) {
                    $join->on('lessons.id', '=', 'user_lessons.lesson_id')
                        ->where('user_lessons.user_id', '=', $user->id);
                })
                ->select('lessons.id', 'lessons.title', 'lessons.description', "lessons.count_tries", 'lessons.number', 'lessons.course_id', 'user_lessons.id as user_lesson_id', 'user_lessons.points as user_points')
                ->orderBy('lessons.number')
                ->get()
                ->groupBy('id')
                ->map(function($items) {
                    $last = $items->whereNotNull('user_lesson_id')->sortByDesc('user_lesson_id')->first() ?? $items->first();
                    $last->user_count_tries = $items->whereNotNull('user_lesson_id')->count();
                    return $last;
                })
                ->values();
        }
        if ($isFirst) $user->isFirst = true;

        try {
            $user->trial = utils::getTrial();
            $user->online = utils::getOnline();
        } catch (\Exception $ex) {
            Log::critical($ex);
        }

        return response()->json($user);
    }

    public function settings (AuthSettingsRequest $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $validate = $request->validated();
        $user->update($validate);

        return response()->json($user);
    }

    public function readNotifications (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        Notification::where("user_id", $user->id)->update(["read" => true]);

        return response()->json(["success" => true]);
    }

    public function getOnline (Request $request) {
        return response()->json(["online" => utils::getOnline()]);
    }
}
