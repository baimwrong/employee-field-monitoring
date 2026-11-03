<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $actionFilter = $request->input('action_type');
        $userFilter = $request->input('user_id');
        $dateFilter = $request->input('date');
        $search = $request->input('search');

        $query = ActivityLog::with('user');

        if ($actionFilter) {
            $query->where('action_type', $actionFilter);
        }

        if ($userFilter) {
            $query->where('user_id', $userFilter);
        }

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('details', 'ilike', "%{$search}%")
                  ->orWhere('ip_address', 'ilike', "%{$search}%");
            });
        }

        $activities = $query->latest('created_at')->paginate(20)->withQueryString();

        $todayCount = ActivityLog::whereDate('created_at', Carbon::today())->count();
        $checkInCount = ActivityLog::where('action_type', 'like', '%checkin%')->count();
        $reportCount = ActivityLog::where('action_type', 'like', '%inspection%')->count();
        $assignmentCount = ActivityLog::where('action_type', 'like', '%area%')->count();

        $users = User::all();
        $actionTypes = ActivityLog::select('action_type')->distinct()->pluck('action_type');

        return view('activities.index', compact(
            'activities',
            'todayCount',
            'checkInCount',
            'reportCount',
            'assignmentCount',
            'users',
            'actionTypes',
            'actionFilter',
            'userFilter',
            'dateFilter',
            'search'
        ));
    }
}
