<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use Illuminate\Support\Facades\Auth;

class PrintHistoryController extends Controller
{
    /**
     * Show the user's print history.
     */
    public function index()
    {
        $user = Auth::user();
        $printJobs = PrintJob::where('user_id', $user->id)
            ->with('pdfFile')
            ->latest()
            ->paginate(15);

        return view('print-history', compact('printJobs'));
    }

    /**
     * Show the print job details.
     */
    public function show($id)
    {
        $printJob = PrintJob::findOrFail($id);

        // Check if user owns this job or is admin
        if ($printJob->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        return view('print-job-detail', compact('printJob'));
    }
}
