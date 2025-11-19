<?php

namespace App\Http\Controllers;

use App\Http\Requests\admin\AdminCreateAchievementRequest;
use App\Http\Requests\admin\AdminCreateAdRequest;
use App\Http\Requests\admin\AdminCreateCourseRequest;
use App\Http\Requests\admin\AdminCreateLessonRequest;
use App\Http\Requests\admin\AdminCreateProbeRequest;
use App\Http\Requests\admin\AdminCreateStateRequest;
use App\Http\Requests\admin\AdminCreateVariantRequest;
use App\Http\Requests\admin\AdminUpdateAchievementRequest;
use App\Http\Requests\admin\AdminUpdateAdRequest;
use App\Http\Requests\admin\AdminUpdateCourseRequest;
use App\Http\Requests\admin\AdminUpdateLessonRequest;
use App\Http\Requests\admin\AdminUpdateProbeRequest;
use App\Http\Requests\admin\AdminUpdateStateRequest;
use App\Http\Requests\admin\AdminUpdateVariantRequest;
use App\Http\utils;
use App\Models\Achievement;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\AdminCookie;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Log;
use App\Models\Probe;
use App\Models\State;
use App\Models\Subject;
use App\Models\Support;
use App\Models\User;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use stdClass;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class AdminController extends Controller
{
    public function profile (Request $request) {
        return $request->get("user");
    }
    public function login (Request $request) {
        $admin = Admin::where("login", $request->login)->first();
        if (!$admin or !password_verify($request->password, $admin->password))
            abort (403, "Неверный логин или пароль");

        $cookie = utils::gen_cookie($admin, isadmin: true);
        $respcookie = Cookie::forever("admin", $cookie);

        return response()
            ->json(["Message" => "Успешная авторизация!", "cookie" => $cookie])
            ->withCookie($respcookie);
    }
    public function logout (Request $request) {
        $admin = $request->get("user");
        AdminCookie::where("cookie", $request->cookie("admin"))->delete();

        $respcookie = Cookie::forget("admin");

        return response()->json(["Message" => "Вы успешно вышли из системы администрации."])->withCookie($respcookie);
    }

    public function users (Request $request) {
        return utils::index(User::class, $request, true);
    }
    public function showUser (User $user, Request $request) {
        $user->levels = json_decode(env("LEVELS"), true);
        $user->courses = Course::all();
        foreach ($user->courses as &$course) {
            $course->lessons = Lesson::where("course_id", $course->id)->
            leftJoin('user_lessons', function($join) use ($user) {
                $join->on('lessons.id', '=', 'user_lessons.lesson_id')
                    ->where('user_lessons.user_id', '=', $user->id);
            })
                ->select('lessons.id', 'lessons.title', 'lessons.description', "lessons.count_tries", 'lessons.number', 'user_lessons.id as user_lesson_id', 'user_lessons.points as user_points', 'user_lessons.created_at as user_lesson_created_at')
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

        return $user;
    }

    public function giveSubscription (User $user, Request $request) {
        $days = $request->days;
        if ($days === 0) $user->is_sub = 0;
        else {
            $user->is_sub = 1;
            $user->sub_date = Carbon::now()->addDays($days);
        }
        $user->save();

        return User::find($user->id);
    }

    public function courses () {
        $response = [];
        $response["courses"] =  Course::all();
        $response["levels"] = json_decode(env("LEVELS"), true);
        foreach ($response["courses"] as &$course) {
            $course->lessons = Lesson::where("course_id", $course->id)->get();
        }
        return response()->json($response);
    }

    public function deleteCourse (Course $course) {
        Lesson::where("course_id", $course->id)->delete();
        $course->delete();

        return $this->courses();
    }

    public function updateCourse (Course $course, AdminUpdateCourseRequest $request) {
        $validate = $request->validated();
        if (isset($validate["required_course"]) && $validate["required_course"] === $course->id) unset($validate["required_course"]);

        $course->update($validate);
        return $this->courses();
    }

    public function createCourse (AdminCreateCourseRequest $request) {
        $validate = $request->validated();
        Course::create($validate);

        return $this->courses();
    }

    public function deleteLesson (Lesson $lesson) {
        $lesson->delete();

        Lesson::where('course_id', $lesson->course_id)
            ->where('number', '>', $lesson->number)
            ->decrement('number');

        return $this->courses();
    }

    public function updateLesson (Lesson $lesson, AdminUpdateLessonRequest $request) {
        $validate = $request->validated();
        $courseId = $lesson->course_id;

        if (isset($validate['number']) && $validate['number'] != $lesson->number) {
            $oldNumber = $lesson->number;
            $newNumber = $validate['number'];

            if ($newNumber < $oldNumber) {
                Lesson::where('course_id', $courseId)
                    ->whereBetween('number', [$newNumber, $oldNumber - 1])
                    ->increment('number');
            } else {
                Lesson::where('course_id', $courseId)
                    ->whereBetween('number', [$oldNumber + 1, $newNumber])
                    ->decrement('number');
            }

            $validate['number'] = $newNumber;
        }
        if ($request->has("file")) {
            if ($lesson->file) Storage::disk("public")->delete($lesson->file);

            $picture = $request->file("file");
            $time = time();
            $url = "lesson/image_$time" . "." . $picture->extension();
            Storage::disk("public")->putFileAs("lesson", $picture, "image_$time" . "." . $picture->extension());
            $validate["file"] = $url;
        }

        $lesson->update($validate);

        $lessons = Lesson::where('course_id', $courseId)
            ->orderBy('number')
            ->get();
        $number = 1;
        foreach ($lessons as $lesson) {
            if ($lesson->number != $number) {
                $lesson->number = $number;
                $lesson->save();
            }
            $number++;
        }

        return $this->courses();
    }

    public function createLesson (AdminCreateLessonRequest $request) {
        $lesson = $request->validated();
        $courseId = $lesson["course_id"];

        $newNumber = intval($lesson["number"]);
        $oldNumber = 9999;

        if ($newNumber < $oldNumber) {
            Lesson::where('course_id', $courseId)
                ->whereBetween('number', [$newNumber, $oldNumber - 1])
                ->increment('number');
        } else {
            Lesson::where('course_id', $courseId)
                ->whereBetween('number', [$oldNumber + 1, $newNumber])
                ->decrement('number');
        }

        $picture = $request->file("file");
        $time = time();
        $url = "lesson/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("lesson", $picture, "image_$time" . "." . $picture->extension());
        $lesson["file"] = $url;

        Lesson::create($lesson);

        $lessons = Lesson::where('course_id', $courseId)
            ->orderBy('number')
            ->get();
        $number = 1;
        foreach ($lessons as $lesson) {
            if ($lesson->number != $number) {
                $lesson->number = $number;
                $lesson->save();
            }
            $number++;
        }

        return $this->courses();
    }

    public function achievements () {
        return Achievement::all();
    }

    public function updateAchievement (Achievement $achievement, AdminUpdateAchievementRequest $request) {
        $validate = $request->validated();

        if ($request->has("image")) {
            Storage::disk("public")->delete($achievement->image);

            $picture = $request->file("image");
            $time = time();
            $url = "achievements/image_$time" . "." . $picture->extension();
            Storage::disk("public")->putFileAs("achievements", $picture, "image_$time" . "." . $picture->extension());
            $validate["image"] = $url;
        }
        $achievement->update($validate);
        return $this->achievements();
    }

    public function deleteAchievement (Achievement $achievement, Request $request) {
        Storage::disk("public")->delete($achievement->image);
        $achievement->delete();
        return $this->achievements();
    }

    public function createAchievement (AdminCreateAchievementRequest $request) {
        $validate = $request->validated();

        $picture = $request->file("image");
        $time = time();
        $url = "achievements/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("achievements", $picture, "image_$time" . "." . $picture->extension());
        $validate["image"] = $url;

        Achievement::create($validate);
        return $this->achievements();
    }

    public function supports () {
        $supports = Support::all();
        foreach ($supports as &$support) $support->dialog = json_decode($support->dialog, true);
        return $supports;
    }

    public function supportClose (Support $support) {
        $support->is_closed = true;
        $support->save();

        try {
            $user = User::find($support->user_id);
            Telegram::sendMessage([
                "chat_id" => $user->telegram_id,
                "text" => "*🚫 Администратор закрыл чат поддержки*",
                "parse_mode" => "MarkdownV2"
            ]);
        } catch (\Exception $e) {}

        return $this->supports();
    }

    public function supportSend (Support $support, Request $request) {
        $text = $request->message;
        $images = $request->file("images");

        if (!$text AND !$images) abort(400, "Не указано сообщение");
        if ($support->is_closed) abort (409, "Саппорт уже закрыт");

        $support->dialog = json_decode($support->dialog, true);

        $message = [
            "from" => "admin",
            "text" => $text,
        ];

        if ($request->has("images")) {
            $message["images"] = [];

            $index = 0;
            foreach ($images as $image) {
                $time = time();
                $url = "support/image_$time" . "_" . $index . "." . $image->extension();
                Storage::disk("public")->putFileAs("support", $image, "image_$time" . "_" . $index . "." . $image->extension());

                $message["images"][] = $url;
                $index++;
            }
        }

        $dialog = $support->dialog;
        $dialog[] = $message;
        $support->dialog = $dialog;

        $support->save();

//        try {
//            $user = User::find($support->user_id);
//            Telegram::sendMessage([
//                "chat_id" => $user->telegram_id,
//                "text" => "*🔔 Вам пришло новое сообщение в поддержку*",
//                "parse_mode" => "MarkdownV2"
//            ]);
//        } catch (\Exception $e) {}

        return $this->supports();
    }

    public function subjects () {
        return Subject::all();
    }

    public function updateSubject (Subject $subject, Request $request) {
        if (!$request->has("state_description")) abort (400);
        $subject->state_description = $request->state_description;
        $subject->save();

        return $this->subjects();
    }

    public function states () {
        return State::all();
    }
    public function updateState (State $state, AdminUpdateStateRequest $request) {
        $validate = $request->validated();
        $validate["materials"] = json_encode($validate["materials"]);

        $state->update($validate);
        return $this->states();
    }

    public function deleteState (State $state, Request $request) {
        $state->delete();
        return $this->states();
    }

    public function createState (AdminCreateStateRequest $request) {
        $validate = $request->validated();
        $validate["materials"] = json_encode($validate["materials"]);

        State::create($validate);
        return $this->states();
    }

    public function ads () {
        return Ad::all();
    }
    public function updateAd (Ad $ad, AdminUpdateAdRequest $request) {
        $validate = $request->validated();

        if ($request->has("picture")) {
            Storage::disk("public")->delete($ad->picture);

            $picture = $request->file("picture");
            $time = time();
            $url = "ad/image_$time" . "." . $picture->extension();
            Storage::disk("public")->putFileAs("ad", $picture, "image_$time" . "." . $picture->extension());
            $validate["picture"] = $url;
        }

        $ad->update($validate);
        return $this->ads();
    }

    public function deleteAd (Ad $ad, Request $request) {
        $ad->delete();
        return $this->ads();
    }

    public function createAd (AdminCreateAdRequest $request) {
        $validate = $request->validated();

        $picture = $request->file("picture");
        $time = time();
        $url = "ads/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("ads", $picture, "image_$time" . "." . $picture->extension());
        $validate["picture"] = $url;

        Ad::create($validate);
        return $this->ads();
    }


    public function probes () {
        return Probe::all();
    }
    public function updateProbe (Probe $probe, AdminUpdateProbeRequest $request) {
        $validate = $request->validated();

        $probe->update($validate);
        return $this->probes();
    }

    public function deleteProbe (Probe $probe, Request $request) {
        $probe->delete();
        return $this->probes();
    }

    public function createProbe (AdminCreateProbeRequest $request) {
        $validate = $request->validated();

        Probe::create($validate);
        return $this->probes();
    }


    public function variants () {
        return Variant::all();
    }
    public function updateVariant (Variant $variant, AdminUpdateVariantRequest $request) {
        $validate = $request->validated();
        $validate["exercises"] = json_encode($validate["exercises"]);

        $variant->update($validate);
        return $this->variants();
    }

    public function deleteVariant (Variant $variant, Request $request) {
        $variant->delete();
        return $this->variants();
    }

    public function createVariant (AdminCreateVariantRequest $request) {
        $validate = $request->validated();
        $validate["exercises"] = json_encode($validate["exercises"]);

        Variant::create($validate);
        return $this->variants();
    }

    public function logs (Request $request) {
        return Log::limit(100)->get();
    }

    public function setTrial (Request $request) {
        if (!$request->has("days")) abort(400, "Не введено количество дней");
        if ($request->days < 0) abort(400, "Количество дней не может быть меньше 0");
        utils::setTrial($request->days);
        return response()->json(["ok" => true]);
    }
}
