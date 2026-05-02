<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

use App\Models\Student;
use App\Models\Major;
use App\Models\WaMessageLog;
use App\Models\School;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalStudents = Student::count();
        
        // Group student counts by status
        $statusCounts = Student::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $lulusCount = $statusCounts['Lulus'] ?? 0;
        $tidakLulusCount = $totalStudents - $lulusCount; // Assume the rest are Tunda/Tidak Lulus

        $totalMajors = Major::count();
        $waSent = WaMessageLog::where('status', 'sent')->count();
        $school = School::first();

        // 5 recent WA logs
        $recentLogs = WaMessageLog::orderBy('created_at', 'desc')->take(6)->get();

        // 10 recent activity logs
        $recentActivities = ActivityLog::orderBy('created_at', 'desc')->take(10)->get();

        return view('admin.dashboard', compact(
            'totalStudents',
            'lulusCount',
            'tidakLulusCount',
            'totalMajors',
            'waSent',
            'school',
            'recentLogs',
            'recentActivities'
        ));
    }
}
