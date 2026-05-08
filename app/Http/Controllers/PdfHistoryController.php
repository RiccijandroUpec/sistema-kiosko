<?php

namespace App\Http\Controllers;

use App\Models\PdfFile;
use Illuminate\Support\Facades\Auth;

class PdfHistoryController extends Controller
{
    /**
     * Show the user's PDF files.
     */
    public function index()
    {
        $user = Auth::user();
        $pdfFiles = PdfFile::where('user_id', $user->id)
            ->withCount('printJobs')
            ->latest()
            ->paginate(20);

        return view('pdf-history', compact('pdfFiles'));
    }

    /**
     * Delete a PDF file.
     */
    public function destroy($id)
    {
        $pdfFile = PdfFile::findOrFail($id);

        // Check if user owns this file
        if ($pdfFile->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Delete file from storage
        \Storage::delete('public/' . $pdfFile->file_path);

        // Delete from database
        $pdfFile->delete();

        return redirect()->route('pdf-history')->with('success', 'Archivo eliminado exitosamente');
    }
}
