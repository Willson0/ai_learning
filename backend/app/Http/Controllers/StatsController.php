<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\News;
use App\Models\ProbeUser;
use App\Models\User;
use App\Models\UserLesson;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index() {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $accountarr = [];

        for ($month = 1; $month <= 12; $month++) {
            $startMonth = Carbon::create($currentYear, $month, 1)->startOfMonth();
            $endMonth = Carbon::create($currentYear, $month, 1)->endOfMonth();

            $count = User::whereBetween("created_at", [$startMonth, $endMonth])->count();
            $accountarr[] = $count;
        }
        $startMonth = Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();
        $money = User::count();
        $money30d = UserLesson::where("created_at", ">=", Carbon::now()->subDays(30))->count();
        $usersperday = Lesson::where("created_at", ">=", Carbon::now()->subDays(30))->count();
        $logsperday = ProbeUser::count();

        return response()->json(["accounts" => $accountarr,
            "money" => $money, "money30" => $money30d, "usersPerDay" => $usersperday,
            "logsPerDay" => $logsperday],
            200);
    }
}
