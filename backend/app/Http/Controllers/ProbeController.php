<?php

namespace App\Http\Controllers;

use App\Models\Probe;
use App\Models\User;
use Illuminate\Http\Request;

class ProbeController extends Controller
{
    public function getVariants (Probe $probe, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        $variants = $probe->variants;

        foreach ($variants as &$variant) {
            $exercises = json_decode($variant->exercises, true);
            foreach ($exercises as &$exercise) {
                unset($exercise["right_answer"]);
            }
            unset($exercise);

            $variant->exercises = $exercises;
        }
        unset($variant);

        return response()->json($variants);
    }
}
