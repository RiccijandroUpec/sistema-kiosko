<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\PdfFile;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return $this->adminDashboard();
        }

        return $this->userDashboard();
    }

    /**
     * Show the user dashboard.
     */
    private function userDashboard()
    {
        $user = Auth::user();

        $stats = [
            'total_pdfs' => PdfFile::where('user_id', $user->id)->count(),
            'completed_jobs' => PrintJob::where('user_id', $user->id)->where('status', 'completed')->count(),
            'pending_jobs' => PrintJob::where('user_id', $user->id)->where('status', 'pending')->count(),
            'total_spent' => PrintJob::where('user_id', $user->id)->where('status', 'completed')->sum('cost'),
        ];

        $recentJobs = PrintJob::where('user_id', $user->id)->latest()->take(10)->get();
        $recentPdfs = PdfFile::where('user_id', $user->id)->latest()->take(6)->get();

        return view('dashboard', compact('stats', 'recentJobs', 'recentPdfs'));
    }

    /**
     * Show the admin dashboard.
     */
    private function adminDashboard()
    {
        $stats = [
            'total_users' => \App\Models\User::count(),
            'total_pdfs' => PdfFile::count(),
            'total_jobs' => PrintJob::count(),
            'pending_jobs' => PrintJob::where('status', 'pending')->count(),
            'total_revenue' => PrintJob::where('status', 'completed')->sum('cost'),
        ];

        $recentJobs = PrintJob::latest()->take(10)->get();
        $topUsers = \App\Models\User::withCount('printJobs')->orderByDesc('print_jobs_count')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentJobs', 'topUsers'));
    }
}
