<?php

namespace App\Http\Controllers;

use App\Http\utils;
use App\Models\ProbeUser;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    public function show (Variant $variant, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $exercises = json_decode($variant->exercises, true);
        foreach ($exercises as &$exercise) {
            unset($exercise["right_answer"]);
        }
        unset($exercise);

        $variant->exercises = $exercises;

        return response()->json($variant);
    }

    public function checkAnswers (Variant $variant, Request $request)
    {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if (!isset($request["answers"])) abort (400);

        $answers = $request["answers"];
        $exercises = json_decode($variant->exercises, true);

        $count = 0;
        for ($i = 0; $i < count($exercises); $i++) {
            $exercises[$i]["user_answer"] = $answers[$i] ?? "";

            if (str($exercises[$i]["user_answer"])->trim()->lower() == str($exercises[$i]["right_answer"]["value"])->trim()->lower()) {
                $exercises[$i]["is_right"] = true;
                $count += $exercises[$i]["points"];
            } else $exercises[$i]["is_right"] = false;
        }

        ProbeUser::create([
            "user_id" => $user->id,
            "variant_id" => $variant->id,
            "points" => $count
        ]);

        return response()->json($exercises);
    }
}
