<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProbeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\CheckAdminMiddleware;
use App\Http\Middleware\CheckTelegram;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::group(["prefix" => "api"], function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::group(["prefix" => "auth", "middleware" => CheckTelegram::class], function () {
        Route::post("profile", [AuthController::class, "profile"]);
        Route::post("settings", [AuthController::class, "settings"]);
        Route::post("read_notifications", [AuthController::class, "readNotifications"]);
        Route::post("online", [AuthController::class, "getOnline"]);
    });

    Route::group(["prefix" => "support", "middleware" => CheckTelegram::class], function () {
        Route::post("/", [SupportController::class, "index"]);
        Route::post("/send", [SupportController::class, "send"]);
    });

    Route::group(["prefix" => "chat", "middleware" => CheckTelegram::class], function () {
        Route::post("/", [ChatController::class, "store"]);
        Route::post("/{chat}", [ChatController::class, "update"]);
        Route::post("/{chat}/send", [ChatController::class, "send"]);
        Route::post("/{chat}/audio", [ChatController::class, "audio"]);
        Route::post("/{chat}/delete", [ChatController::class, "delete"]);
    });

    Route::group(["prefix" => "lesson", "middleware" => CheckTelegram::class], function () {
        Route::post("/{lesson}", [LessonController::class, "show"]);
        Route::post("/{lesson}/check", [LessonController::class, "checkAnswers"]);
        Route::post("/{lesson}/hint/{index}", [LessonController::class, "hint"]);
    });

    Route::group(["prefix" => "friend", "middleware" => CheckTelegram::class], function () {
        Route::post("/{friend}/delete", [FriendController::class, "unfriend"]);
        Route::post("/{friend}/accept", [FriendController::class, "accept"]);
        Route::post("/{friend}/decline", [FriendController::class, "decline"]);
        Route::post("/{us}/friend", [FriendController::class, "add"]);
    });

    Route::group(["prefix" => "schedule", "middleware" => CheckTelegram::class], function () {
        Route::post("/{probe}", [ScheduleController::class, "store"]);
    });

    Route::group(["prefix" => "probe", "middleware" => CheckTelegram::class], function () {
        Route::post("/{probe}", [ProbeController::class, "getVariants"]);
    });
    Route::group(["prefix" => "variant", "middleware" => CheckTelegram::class], function () {
        Route::post("/{variant}", [VariantController::class, "show"]);
        Route::post("/{variant}/check", [VariantController::class, "checkAnswers"]);
    });

    Route::group(["prefix" => "webhook"], function () {
        Route::post("/tg", [WebhookController::class, 'tg']);
    });

    Route::group(["prefix" => "achievement", "middleware" => CheckTelegram::class], function () {
        Route::post("{achievement}/pin", [AchievementController::class, "pin"]);
        Route::post("{achievement}/unpin", [AchievementController::class, "unpin"]);
    });

    Route::group(["prefix" => "user", "middleware" => CheckTelegram::class], function () {
        Route::post("{user}", [UserController::class, "show"]);
        Route::post("{us}/share", [UserController::class, "share"]);
    });

    Route::group(["prefix" => "subscription", "middleware" => CheckTelegram::class], function () {
        Route::post("/trial", [SubscriptionController::class, "trial"]);
        Route::post("/buy", [SubscriptionController::class, "buy"]);
    });

    Route::group(["prefix" => "payment"], function () {
        Route::post("linkcard", [PaymentController::class, "linkCard"])->middleware([CheckTelegram::class]);
        Route::post("unlinkcard", [PaymentController::class, "unLinkCard"])->middleware([CheckTelegram::class]);
        Route::post("webhook", [PaymentController::class, "webhook"]);
    });

    Route::get('/getstorage', function (Request $request) {
        $filename = $request->query('filename');
        if (!$filename) abort(400, 'No filename specified');

        $path = storage_path('app/public/' . $filename);
        if (!file_exists($path)) abort(404);

        return response()->file($path);
    });

    Route::group(["prefix" => "stats", "middleware" => CheckAdminMiddleware::class], function () {
        Route::get("/", [StatsController::class, "index"]);
    });

    Route::post("/admin/login", [AdminController::class, "login"]);
    Route::group(["prefix" => "admin", "middleware" => CheckAdminMiddleware::class], function () {
        Route::get("profile", [AdminController::class, "profile"]);
        Route::post("logout", [AdminController::class, "logout"]);
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminController::class, 'users']);
            Route::get('{user}', [AdminController::class, 'showUser']);
            Route::post("{user}/sub", [AdminController::class, 'giveSubscription']);
        });
        Route::prefix('courses')->group(function () {
            Route::get('/', [AdminController::class, 'courses']);
            Route::post('/', [AdminController::class, 'createCourse']);
            Route::post('{course}', [AdminController::class, 'updateCourse']);
            Route::delete('{course}', [AdminController::class, 'deleteCourse']);
        });
        Route::prefix('lessons')->group(function () {
            Route::post('/', [AdminController::class, 'createLesson']);
            Route::post('{lesson}', [AdminController::class, 'updateLesson']);
            Route::delete('{lesson}', [AdminController::class, 'deleteLesson']);
        });
        Route::prefix('achievements')->group(function () {
            Route::get('/', [AdminController::class, 'achievements']);
            Route::post('/', [AdminController::class, 'createAchievement']);
            Route::post('{achievement}', [AdminController::class, 'updateAchievement']);
            Route::delete('{achievement}', [AdminController::class, 'deleteAchievement']);
        });
        Route::prefix('support')->group(function () {
            Route::get('/', [AdminController::class, 'supports']);
            Route::get('/{support}/close', [AdminController::class, 'supportClose']);
            Route::post('/{support}/send', [AdminController::class, 'supportSend']);
        });
        Route::prefix('subjects')->group(function () {
            Route::get('/', [AdminController::class, 'subjects']);
            Route::post("/{subject}", [AdminController::class, 'updateSubject']);
        });
        Route::prefix('states')->group(function () {
            Route::get('/', [AdminController::class, 'states']);
            Route::post('/', [AdminController::class, 'createState']);
            Route::post('{state}', [AdminController::class, 'updateState']);
            Route::delete('{state}', [AdminController::class, 'deleteState']);
        });
        Route::prefix('ads')->group(function () {
            Route::get('/', [AdminController::class, 'ads']);
            Route::post('/', [AdminController::class, 'createAd']);
            Route::post('{ad}', [AdminController::class, 'updateAd']);
            Route::delete('{ad}', [AdminController::class, 'deleteAd']);
        });
        Route::prefix('probes')->group(function () {
            Route::get('/', [AdminController::class, 'probes']);
            Route::post('/', [AdminController::class, 'createProbe']);
            Route::post('{probe}', [AdminController::class, 'updateProbe']);
            Route::delete('{probe}', [AdminController::class, 'deleteProbe']);
        });
        Route::prefix('variants')->group(function () {
            Route::get('/', [AdminController::class, 'variants']);
            Route::post('/', [AdminController::class, 'createVariant']);
            Route::post('{variant}', [AdminController::class, 'updateVariant']);
            Route::delete('{variant}', [AdminController::class, 'deleteVariant']);
        });
        Route::prefix('log')->group(function () {
            Route::get('/', [AdminController::class, 'logs']);
        });
        Route::prefix('trial')->group(function () {
            Route::post('/', [AdminController::class, 'setTrial']);
        });
        Route::prefix('mailing')->group(function () {
            Route::get("/", [PostController::class, 'index']);
            Route::post("/", [PostController::class, "store"]);
            Route::delete("/{post}", [PostController::class, "destroy"]);
            Route::post("/{post}", [PostController::class, "update"]);
        });
    });
});
