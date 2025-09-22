<?php

namespace App\Http\Controllers;

use App\Http\utils;
use App\Models\FriendRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserLesson;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function unfriend (User $friend, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $req = FriendRequest::where(function ($query) use ($user, $friend) {
            $query->where("sender_id", $user->id)->where("receiver_id", $friend->id);
        })->orWhere(function ($query) use ($user, $friend) {
            $query->where("sender_id", $friend->id)->where("receiver_id", $user->id);
        })->firstOrFail();

        $req->delete();
        return response()->json(["success" => true]);
    }

    public function accept (User $friend, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $req = FriendRequest::where("sender_id", $friend->id)
            ->where("receiver_id", $user->id)->firstOrFail();

        $req->update(["is_accepted" => 1]);

        Notification::create([
            "title" => "Заявка в друзья принята",
            "body" => "Пользователь {$user->fullname} принял вашу заявку в друзья",
            "read" => 0,
            "user_id" => $friend->id,
        ]);

        return response()->json(["success" => true]);
    }

    public function decline (User $friend, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $req = FriendRequest::where("sender_id", $friend->id)
            ->where("receiver_id", $user->id)->firstOrFail();

        $req->delete();
        return response()->json(["success" => true]);
    }

    public function add (User $us, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        if (FriendRequest::where(function ($query) use ($user, $us) {
            $query->where("sender_id", $user->id)->where("receiver_id", $us->id);
        })->orWhere(function ($query) use ($user, $us) {
            $query->where("sender_id", $us->id)->where("receiver_id", $user->id);
        })->exists()) abort (400, "Already sent");

        $friend = FriendRequest::create([
            "sender_id" => $user->id,
            "receiver_id" => $us->id,
            "is_accepted" => 0,
        ]);
        $response = $friend;
        $response["avatar"] = $us->avatar;
        $response["fullname"] = $us->fullname;
        $response["total_points"] = UserLesson::where("user_id", $us->id)->sum("points");

        Notification::create([
            "title" => "Заявка в друзья",
            "body" => "Пользователь {$user->fullname} отправил вам заявку в друзья",
            "read" => 0,
            "user_id" => $us->id,
        ]);

        return response()->json($response);
    }
}
