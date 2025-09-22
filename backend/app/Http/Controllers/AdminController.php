<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCreateAchievementRequest;
use App\Http\Requests\AdminCreateCourseRequest;
use App\Http\Requests\AdminCreateCurrencyRequest;
use App\Http\Requests\AdminCreateFiatRequest;
use App\Http\Requests\AdminCreateLessonRequest;
use App\Http\Requests\AdminCreateNewsRequest;
use App\Http\Requests\AdminCreateTournamentRequest;
use App\Http\Requests\AdminUpdateAchievementRequest;
use App\Http\Requests\AdminUpdateCourseRequest;
use App\Http\Requests\AdminUpdateCurrencyRequest;
use App\Http\Requests\AdminUpdateFiatRequest;
use App\Http\Requests\AdminUpdateLessonRequest;
use App\Http\Requests\adminUpdateNewsCategoryRequest;
use App\Http\Requests\AdminUpdateNewsRequest;
use App\Http\Requests\AdminUpdateOrderRequest;
use App\Http\Requests\AdminUpdateTournamentRequest;
use App\Http\utils;
use App\Models\Achievements;
use App\Models\Admin;
use App\Models\AdminCookie;
use App\Models\Course;
use App\Models\Currency;
use App\Models\FiatCurrency;
use App\Models\Lesson;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Order;
use App\Models\Picture;
use App\Models\Support;
use App\Models\Tournament;
use App\Models\User;
use App\Models\WhiteList;
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
        $user->currencies = Currency::all();
        try {
            $response = Http::get('https://api.coingecko.com/api/v3/coins/markets', [
                'vs_currency' => "usd",
                'ids' => implode(',', array_column($user->currencies->toArray(), 'coingeckoId')),
            ]);
            if ($response->status() === 429) throw new \Exception('Too many requests');
            Cache::put('currenciesData', $response->json(), now()->addMinutes(5));

            $user->currenciesData = $response->json();
        } catch (\Exception $e) {
            if (Cache::has('currenciesData')) $user->currenciesData = Cache::get('currenciesData');
            else $user->currenciesData = null;
        }
        if ($user->currenciesData) {
            $user->currenciesData = array_map(function ($coin) {
                return [
                    'name'        => $coin['name'] ?? null,
                    'symbol'      => strtoupper($coin['symbol'] ?? ''),
                    'logo'        => $coin['image'] ?? null,
                    'price'       => $coin['current_price'] ?? null,
                    'coingeckoId' => $coin['id'] ?? null,
                ];
            }, $user->currenciesData);
        }
        $user->crypto = json_decode($user->crypto, true);

        return $user;
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
        Tournament::where("object_id", $course->id)->delete();
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

    public function news (Request $request) {
        $categories = NewsCategory::all();
        $news = News::all();

        return response()->json(["news" => $news, "categories" => $categories]);
    }

    public function createNews (AdminCreateNewsRequest $request) {
        $validate = $request->validated();

        $picture = $request->file("image");
        $time = time();
        $url = "news/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("news", $picture, "image_$time" . "." . $picture->extension());
        $validate["image"] = $url;

        News::create($validate);

        return $this->news($request);
    }

    public function updateNews (News $news, AdminUpdateNewsRequest $request) {
        $validate = $request->validated();

        if ($request->has("image")) {
            Storage::disk("public")->delete($news->image);

            $picture = $request->file("image");
            $time = time();
            $url = "news/image_$time" . "." . $picture->extension();
            Storage::disk("public")->putFileAs("news", $picture, "image_$time" . "." . $picture->extension());
            $validate["image"] = $url;
        }

        $news->update($validate);
        return $this->news($request);
    }

    public function deleteNews (News $news, Request $request) {
        Storage::disk("public")->delete($news->image);
        $news->delete();

        return $this->news($request);
    }

    public function createNewsCategory (Request $request) {
        if (!$request->has("name")) abort(400, "Не указано название категории");
        NewsCategory::create(["name" => $request->name]);

        return $this->news($request);
    }

    public function deleteNewsCategory (NewsCategory $category, Request $request) {
        News::where("category_id", $category->id)->delete();
        $category->delete();

        return $this->news($request);
    }

    public function updateNewsCategory (NewsCategory $category, adminUpdateNewsCategoryRequest $request) {
        $validate = $request->validated();
        $category->update($validate);

        return $this->news($request);
    }

    public function tournaments () {
        $tournaments = Tournament::all();
        $lessons = Course::all();
        $exams = Lesson::where("count_tries", ">", 0)->get();

        return response()->json(["tournaments" => $tournaments, "lessons" => $lessons, "exams" => $exams]);
    }

    public function deleteTournament (Tournament $tournament, Request $request) {
        $tournament->delete();
        return $this->tournaments();
    }

    public function updateTournament (Tournament $tournament, AdminUpdateTournamentRequest $request) {
        $validate = $request->validated();
        if ($request->has("type")) {
            if ($validate["type"] === "lesson" && !isset($validate["object_id"]) && $tournament->object_id === 0)
                abort(400, "Не указан урок");
            if ($validate["type"] === 'time') $validate["object_id"] = 0;
        }

        $tournament->update($validate);
        return $this->tournaments();
    }

    public function createTournament (AdminCreateTournamentRequest $request) {
        $validate = $request->validated();
        if ($validate["type"] === "lesson" && !isset($validate["object_id"])) abort(400, "Не указан урок");

        Tournament::create($validate);
        return $this->tournaments();
    }

    public function currencies (Request $request) {
        return Currency::all();
    }

    public function updateCurrency (Currency $currency, AdminUpdateCurrencyRequest $request) {
        $currency->update($request->validated());
        return $this->currencies($request);
    }

    public function deleteCurrency (Currency $currency, Request $request) {
        $currency->delete();
        return $this->currencies($request);
    }

    public function createCurrency (AdminCreateCurrencyRequest $request) {
        Currency::create($request->validated());
        return $this->currencies($request);
    }

    public function achievements () {
        return Achievements::all();
    }

    public function updateAchievement (Achievements $achievement, AdminUpdateAchievementRequest $request) {
        $validate = $request->validated();
        if ($validate["type"] === "lessons" && $validate["progress"] < 0) abort(400, "Прогресс не может быть меньше 0");
        if ($validate["type"] === "channel" && $validate["progress"][0] != '@') abort(400, "Телеграм канал должен начинаться с символа @");
        if ($validate["type"] === "tournament" && !Tournament::where("id", $validate["progress"])->exists()) abort(400, "Такого турнира не существует");

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

    public function deleteAchievement (Achievements $achievement, Request $request) {
        Storage::disk("public")->delete($achievement->image);
        $achievement->delete();
        return $this->achievements();
    }

    public function createAchievement (AdminCreateAchievementRequest $request) {
        $validate = $request->validated();

        if ($validate["type"] === "lessons" && $validate["progress"] < 0) abort(400, "Прогресс не может быть меньше 0");
        if ($validate["type"] === "channel" && $validate["progress"][0] != '@') abort(400, "Телеграм канал должен начинаться с символа @");
        if ($validate["type"] === "tournament" && !Tournament::where("id", $validate["progress"])->exists()) abort(400, "Такого турнира не существует");

        $picture = $request->file("image");
        $time = time();
        $url = "achievements/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("achievements", $picture, "image_$time" . "." . $picture->extension());
        $validate["image"] = $url;

        Achievements::create($validate);
        return $this->achievements();
    }

    public function fiats () {
        return FiatCurrency::all();
    }

    public function updateFiat (FiatCurrency $fiat, AdminUpdateFiatRequest $request) {
        $validate = $request->validated();
        if ($request->has("image")) {
            Storage::disk("public")->delete($fiat->image);

            $picture = $request->file("image");
            $time = time();
            $url = "fiats/image_$time" . "." . $picture->extension();
            Storage::disk("public")->putFileAs("fiats", $picture, "image_$time" . "." . $picture->extension());
            $validate["image"] = $url;
        }
        $fiat->update($validate);
        return $this->fiats();
    }

    public function deleteFiat (FiatCurrency $fiat, Request $request) {
        Order::where("fiat_currency_id", $fiat->id)->delete();

        Storage::disk("public")->delete($fiat->image);
        $fiat->delete();
        return $this->fiats();
    }

    public function createFiat (AdminCreateFiatRequest $request) {
        $validate = $request->validated();

        $picture = $request->file("image");
        $time = time();
        $url = "fiats/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("fiats", $picture, "image_$time" . "." . $picture->extension());
        $validate["image"] = $url;

        FiatCurrency::create($validate);
        return $this->fiats();
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

        try {
            $user = User::find($support->user_id);
            Telegram::sendMessage([
                "chat_id" => $user->telegram_id,
                "text" => "*🔔 Вам пришло новое сообщение в поддержку*",
                "parse_mode" => "MarkdownV2"
            ]);
        } catch (\Exception $e) {}

        return $this->supports();
    }

    public function orders () {
        return Order::all();
    }

    public function deleteOrder (Order $order) {
        $order->delete();
        return $this->orders();
    }

    public function updateOrder (Order $order, AdminUpdateOrderRequest $request) {
        $validate = $request->validated();
        if (isset($validate["min_limit"]) && isset($validate["max_limit"]))
            if ($validate["min_limit"] > $validate["max_limit"]) {
                unset($validate["min_limit"]);
                unset($validate["max_limit"]);
            }
        if ($request->has("user_avatar")) {
            Storage::disk("public")->delete($order->user_avatar);

            $picture = $request->file("user_avatar");
            $time = time();
            $url = "orders/image_$time" . "." . $picture->extension();
            Storage::disk("public")->putFileAs("orders", $picture, "image_$time" . "." . $picture->extension());
            $validate["user_avatar"] = $url;
        }
        $order->update($validate);
        return $this->orders();
    }

    public function createOrder (AdminUpdateOrderRequest $request) {
        $validate = $request->validated();

        if (isset($validate["min_limit"]) && isset($validate["max_limit"]))
            if ($validate["min_limit"] > $validate["max_limit"]) {
                unset($validate["min_limit"]);
                unset($validate["max_limit"]);
            }

        $picture = $request->file("user_avatar");
        $time = time();
        $url = "orders/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("orders", $picture, "image_$time" . "." . $picture->extension());
        $validate["user_avatar"] = $url;

        Order::create($validate);
        return $this->orders();
    }

    public function whitelist (Request $request) {
        return Whitelist::all();
    }

    public function removeWhitelist (WhiteList $whitelist, Request $request) {
        $whitelist->delete();
        return $this->whitelist($request);
    }

    public function updateWhitelist (WhiteList $whitelist, Request $request) {
        if (!$request->has("value")) abort (400, "Не указано значение");
        $whitelist->update([
            "value" => $request->value
        ]);
        return $this->whitelist($request);
    }

    public function addWhitelist (Request $request) {
        if (!$request->has("value")) abort (400, "Не указано значение");
        WhiteList::create([
            "value" => $request->value
        ]);

        $user = User::where("telegram_id", $request->value)->orWhere("username", $request->value)->first();
        if ($user) {
            $caption = ">🎉 *Поздравляем!*
>Вы получили доступ к боту.

*Добро пожаловать в наш бот! 👋*

Здесь вы сможете открыть демо-крипто-счёт, пройти интерактивные курсы и освоить основы работы с криптовалютой

📚 Начните обучение прямо сейчас — первые шаги в мире крипты доступны каждому!";
            $escape_chars = ['[', ']', '(', ')', '~', '`', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
            foreach ($escape_chars as $char) {
                $caption = str_replace($char, '\\' . $char, $caption);
            }

            try {
                Telegram::sendPhoto([
                    'chat_id' => $user->telegram_id,
                    'caption' => $caption,
                    'parse_mode' => 'MarkdownV2',
                    "photo" => InputFile::create(Storage::disk("public")->path("whitelist_message.jpg")),
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
            } catch (\Exception $e) {}
        }

        return $this->whitelist($request);
    }
}
