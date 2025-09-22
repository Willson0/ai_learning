<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Chat;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Probe;
use App\Models\Schedule;
use App\Models\State;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;

class UserController extends Controller
{
    public function show (User $user, Request $request) {
        User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $user->total_points = UserLesson::where("user_id", $user->id)->sum('points');
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

        $user->schedule = Schedule::where("user_id", $user->id)->get();
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

        return response()->json($user);
    }
}
