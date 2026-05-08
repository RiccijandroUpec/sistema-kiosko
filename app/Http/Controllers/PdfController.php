<?php

namespace App\Http\Controllers;

use App\Models\PdfFile;
use App\Models\PrintJob;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Smalot\PdfParser\Parser;

class PdfController extends Controller
{
    protected $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
    }

    /**
     * Show the form for uploading a PDF.
     */
    public function showUploadForm()
    {
        return view('pdf.upload');
    }

    /**
     * Store a newly uploaded PDF file.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pdf_file' => 'required|mimes:pdf|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $pdfFile = $request->file('pdf_file');
        $originalName = $pdfFile->getClientOriginalName();
        
        // Store the file
        $filePath = $pdfFile->store('pdfs', 'public');
        
        // Parse PDF to get page count
        $parser = new Parser();
        $pdfContent = $parser->parseFile(storage_path('app/public/' . $filePath));
        $pagesCount = count($pdfContent->getPages());
        
        // Calculate file size in MB
        $fileSize = $pdfFile->getSize() / 1024 / 1024;
        
        // Save to database
        $pdfModel = PdfFile::create([
            'filename' => basename($filePath),
            'original_name' => $originalName,
            'user_id' => Auth::id(),
            'pages_count' => $pagesCount,
            'file_path' => $filePath,
            'file_size' => $fileSize,
        ]);

        return redirect()->route('pdf.show', $pdfModel->id)->with('success', 'PDF subido exitosamente!');
    }

    /**
     * Display the specified PDF file.
     */
    public function show($id)
    {
        $pdfFile = PdfFile::findOrFail($id);
        
        // Ensure the user owns this file
        if ($pdfFile->user_id !== Auth::id() && !Auth::user()->role === 'admin') {
            abort(403);
        }
        
        return view('pdf.show', compact('pdfFile'));
    }

    /**
     * Calculate print cost and create print job.
     */
    public function createPrintJob(Request $request, $id)
    {
        $pdfFile = PdfFile::findOrFail($id);
        
        // Validate print options
        $validator = Validator::make($request->all(), [
            'copies' => 'required|integer|min:1',
            'color_type' => 'required|in:bw,color',
            'paper_size' => 'required|in:a4,letter,legal',
            'orientation' => 'required|in:portrait,landscape',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // Calculate cost based on pages, copies, and color
        $baseCostPerPage = $request->color_type === 'color' ? 0.20 : 0.05; // $0.20 for color, $0.05 for black & white
        $totalCost = $pdfFile->pages_count * $request->copies * $baseCostPerPage;
        
        // Create print job
        $printJob = PrintJob::create([
            'user_id' => Auth::id(),
            'pdf_file_id' => $pdfFile->id,
            'copies' => $request->copies,
            'color_type' => $request->color_type,
            'paper_size' => $request->paper_size,
            'orientation' => $request->orientation,
            'cost' => $totalCost,
            'status' => 'pending',
        ]);
        
        // Process the print job (in a real implementation, this would likely be queued)
        $this->printService->queuePrintJob($printJob);
        
        return redirect()->route('dashboard')->with('success', 'Trabajo de impresión creado y procesado exitosamente!');
    }
}
