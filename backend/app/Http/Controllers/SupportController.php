<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupportSendRequest;
use App\Http\utils;
use App\Models\Support;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    public function index (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        $openSupport = Support::where("user_id", $user["id"])->where("is_closed", 0)->first();

        if ($openSupport) {
            $openSupport->dialog = json_decode($openSupport->dialog, true);
            return response()->json($openSupport);
        }
        return response()->json(["dialog" => []]);
    }

    public function send (SupportSendRequest $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if (!$request->has("message") AND !$request->has("images")) abort(400);

        $support = Support::where("user_id", $user->id)->where("is_closed", 0)->first();
        if (!$support) {
            $support = Support::create([
                "dialog" => json_encode([]),
                "user_id" => $user["id"],
                "is_closed" => 0
            ]);
            $support = Support::find($support->id);
            utils::logging($user->id, "Пользователь &{user} создал обращение в поддержку №$support->id", ["user" => $user]);
        }

        $support->dialog = json_decode($support->dialog, true);

        $message = [
            "from" => "user",
            "text" => $request->message,
        ];

        if ($request->has("images")) {
            $images = $request->file("images");
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

        return response()->json($support);
    }
}
