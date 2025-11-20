<?php

namespace App\Http\Controllers;

use App\Http\Requests\post\PostStoreRequest;
use App\Http\Requests\post\PostUpdateRequest;
use App\Http\utils;
use App\Models\Mailing;
use App\Models\Post;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index () {
        $posts = Post::whereNotNull('date')
            ->where(function ($query) {
                $query->where('date', '>', Carbon::now("Europe/Moscow"))
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('end_count')
                            ->orWhereNotNull('end_date');
                    });
            })
            ->where(function ($query) {
                $query->whereNull("end_count")
                    ->orWhere("end_count", "!=", 0);
            })
            ->where(function ($query) {
                $query->whereNull("end_date")
                    ->orWhere("end_date", ">", Carbon::now("Europe/Moscow"));
            })->get();
        return response()->json($posts);
    }

    public function store (PostStoreRequest $request) {
        $data = $request->validated();

        if (isset($data["date"])) $data["date"] = Carbon::parse($data["date"]);
        if (isset($data["time_repeat"])) if ($data["time_repeat"] < 30)
            abort (409, "Время повторного поста не может быть меньше заданного.");
        if (isset($data["time_repeat"]) AND !(isset($data["end_date"]) OR isset($data["end_count"])))
            abort (409, "Укажите окончание повтора: определенную дату или кол-во раз");
        if (!isset($data["time_repeat"]) AND (isset($data["end_date"]) OR isset($data["end_count"])))
            abort (409, "Укажите кол-во повторов");
        if (isset($data["end_date"]) AND isset($data["end_count"]))
            abort (409, "Не может быть указано два окончания повтора");

        if (isset($data["attachment"])) {
            try {
                utils::validateTelegramAttachment($data["attachment"]);
            } catch (Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }

            $ext = $data["attachment"]->getClientOriginalExtension();
            $time = time();
            Storage::disk("public")->putFileAs("posts", $data["attachment"], "post_" . $time . ".$ext");

            $data["attachment"] = "posts/post_" . $time . ".$ext";
        }

        $data["text"] = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $data["text"]);
        $data["text"] = preg_replace('/__(.+?)__/s', '_$1_', $data["text"]);

        $post = Post::create($data);
        Mailing::create([
            "post_id" => $post["id"],
            "next" => $post["date"],
            "status" => 0
        ]);

        return response()->json($post, 201);
    }

    public function destroy (Post $post, Request $request) {
        if ($post->attachment) Storage::disk("public")->delete($post["attachment"]);
        $post->delete();

        return response()->json(null, 204);
    }

    public function update (Post $post, PostUpdateRequest $request) {
        $data = $request->validated();

        if (isset($data["date"])) $data["date"] = Carbon::parse($data["date"]);
        if (isset($data["end_date"])) {
            $data["end_date"] = Carbon::parse($data["end_date"]);
            if ($data["end_date"] < Carbon::now("Europe/Moscow")) abort (409, "Дата окончания не может быть раньше сегодняшней");
        }
        if (isset($data["time_repeat"])) if ($data["time_repeat"] < 30)
            abort (409, "Время повторного поста не может быть меньше заданного.");
        if (isset($data["end_date"]) AND isset($data["end_count"]) )
            abort (409, "Не может быть указано два окончания повтора");

        if (isset($data["end_date"])) $data["end_count"] = null;
        else if (isset($data["end_count"])) $data["end_date"] = null;

        if ($request->hasAny("attachment")) {
            if ($request->hasFile('attachment')) {
                try {
                    utils::validateTelegramAttachment($data["attachment"]);
                } catch (Exception $e) {
                    return response()->json(['error' => $e->getMessage()]);
                }

                if ($post->attachment) Storage::disk("public")->delete($post["attachment"]);
                $ext = $request["attachment"]->getClientOriginalExtension(); $time = time();
                Storage::disk("public")->putFileAs("posts", $request["attachment"], "post_" . $time . ".$ext");
                $data["attachment"] = "posts/post_" . $time . ".$ext";
            }
            else if (!json_decode($request->attachment)) $data["attachment"] = null;
        }

        Log::critical($data);
        $post->update($data);

        Mailing::where("post_id", $post["id"])->where("status", 0)->delete();
        Mailing::create([
            "post_id" => $post["id"],
            "next" => $post["date"],
            "status" => 0
        ]);

        return response()->json($post, 200);
    }
}
