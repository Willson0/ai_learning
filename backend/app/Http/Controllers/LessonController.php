<?php

namespace App\Http\Controllers;

use App\Http\utils;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserLesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function show (Lesson $lesson, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        utils::checkAccessToLesson($user, $lesson);

        $lesson->oldResult = $lesson->userLessons()->where('user_id', $user->id)->latest()->first()?->points;
        $lesson->videos = json_decode($lesson->videos, true);

        $questions = json_decode($lesson->questions, true);
        foreach ($questions as &$question) {
            unset($question['right_answer']);
            unset($question["hint"]);
        }
        $lesson->questions = $questions;

        return response()->json($lesson);
    }

    public function hint (Lesson $lesson, $index, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $question = json_decode($lesson->questions, true)[$index];
        $hint = $question["hint"];
        if ($hint == null) response()->json(["hint" => "Отсутствует"]);

        if ($user->free_hints === 0 AND $user->hints === 0 AND $user->is_sub == 0) return response()->json(["hint" => "У вас не осталось подсказок"]);

        if ($user->is_sub == 0) {
            if ($user->free_hints > 0) $user->free_hints -= 1;
            else if ($user->hints > 0) $user->hints -= 1;
        }

        $user->save();
        utils::addData($user, "uses_hints", 1);

        return response()->json(["hint" => $question["hint"]]);
    }

    public function checkAnswers (Lesson $lesson, Request $request)
    {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if (!isset($request["answers"])) return;
        utils::checkAccessToLesson($user, $lesson);

        $rightAnswers = json_decode($lesson->questions, true);
        if (count($rightAnswers) != count($request["answers"])) abort(403);

        $count = 0;
        for ($i = 0; $i < count($rightAnswers); $i++) {
            if ($rightAnswers[$i]["right_answer"] == $request["answers"][$i]) $count++;
        }
        $points = round($count / count($rightAnswers) * 100);
        utils::addData($user, "mistake_count", count($rightAnswers) - $count);

        $lastPassing = $lesson->userLessons()->where('user_id', $user->id)->latest()->first();
        if ($lesson->count_tries > 0) {
            if ($points == 100) utils::addData($user, "exam_without_mistakes", 1);

            $user_counts = $lesson->userLessons()->where('user_id', $user->id)->count();
            if ($lastPassing && $lastPassing->points >= 50) {
                $lastPassing->update(["points" => max($points, $lastPassing->points)]);
                utils::addData($user, "repass_count", 1);
            }
            else if ($user_counts + 1 >= $lesson->count_tries && $points < 50) {
                $courseLessons = Lesson::where('course_id', $lesson->course_id)->get()->pluck('id');
                UserLesson::where('user_id', $user->id)->whereIn('lesson_id', $courseLessons)->delete();
            } else {
                UserLesson::create([
                    "user_id" => $user->id,
                    "lesson_id" => $lesson->id,
                    "points" => $points,
                ]);
                utils::addData($user, "pass_count", 1);
            }
        } else {
            if ($lastPassing) {
                $lastPassing->update(["points" => max($points, $lastPassing->points)]);
                utils::addData($user, "repass_count", 1);
            }
            else {
                UserLesson::create([
                    "user_id" => $user->id,
                    "lesson_id" => $lesson->id,
                    "points" => $points,
                ]);
                utils::addData($user, "pass_count", 1);
            }
        }

        return response()->json(["points" => $points]);
    }
}
