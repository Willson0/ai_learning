<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProbeController;
use App\Http\Controllers\ScheduleController;
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


    Route::group(["prefix" => "admin", "middleware" => CheckAdminMiddleware::class], function () {
        Route::get("profile", [AdminController::class, "profile"]);
        Route::post("logout", [AdminController::class, "logout"]);
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminController::class, 'users']);
            Route::get('{user}', [AdminController::class, 'showUser']);
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
        Route::prefix('news')->group(function () {
            Route::get('/', [AdminController::class, 'news']);
            Route::post('/', [AdminController::class, 'createNews']);
            Route::post('{news}', [AdminController::class, 'updateNews']);
            Route::delete('{news}', [AdminController::class, 'deleteNews']);

            Route::prefix('category')->group(function () {
                Route::post('/', [AdminController::class, 'createNewsCategory']);
                Route::post('{category}', [AdminController::class, 'updateNewsCategory']);
                Route::delete('{category}', [AdminController::class, 'deleteNewsCategory']);
            });
        });
        Route::prefix('tournaments')->group(function () {
            Route::get('/', [AdminController::class, 'tournaments']);
            Route::post('/', [AdminController::class, 'createTournament']);
            Route::post('{tournament}', [AdminController::class, 'updateTournament']);
            Route::delete('{tournament}', [AdminController::class, 'deleteTournament']);
        });
        Route::prefix('currencies')->group(function () {
            Route::get('/', [AdminController::class, 'currencies']);
            Route::post('/', [AdminController::class, 'createCurrency']);
            Route::post('{currency}', [AdminController::class, 'updateCurrency']);
            Route::delete('{currency}', [AdminController::class, 'deleteCurrency']);
        });
        Route::prefix('achievements')->group(function () {
            Route::get('/', [AdminController::class, 'achievements']);
            Route::post('/', [AdminController::class, 'createAchievement']);
            Route::post('{achievement}', [AdminController::class, 'updateAchievement']);
            Route::delete('{achievement}', [AdminController::class, 'deleteAchievement']);
        });
        Route::prefix('fiats')->group(function () {
            Route::get('/', [AdminController::class, 'fiats']);
            Route::post('/', [AdminController::class, 'createFiat']);
            Route::post('{fiat}', [AdminController::class, 'updateFiat']);
            Route::delete('{fiat}', [AdminController::class, 'deleteFiat']);
        });
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminController::class, 'orders']);
            Route::post('/', [AdminController::class, 'createOrder']);
            Route::post('{order}', [AdminController::class, 'updateOrder']);
            Route::delete('{order}', [AdminController::class, 'deleteOrder']);
        });
        Route::prefix('support')->group(function () {
            Route::get('/', [AdminController::class, 'supports']);
            Route::get('/{support}/close', [AdminController::class, 'supportClose']);
            Route::post('/{support}/send', [AdminController::class, 'supportSend']);
        });
        Route::prefix('whitelist')->group(function () {
            Route::get('/', [AdminController::class, 'whitelist']);
            Route::post('/', [AdminController::class, 'addWhitelist']);
            Route::post('{whitelist}', [AdminController::class, 'updateWhitelist']);
            Route::delete('{whitelist}', [AdminController::class, 'removeWhitelist']);
        });
    });
});
