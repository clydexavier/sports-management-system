<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\IntramuralGame;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\DocumentRequests\StoreDocumentRequest;
use App\Http\Requests\DocumentRequests\UpdateDocumentRequest;
use App\Http\Requests\DocumentRequests\ShowDocumentRequest;
use App\Http\Requests\DocumentRequests\DestroyDocumentRequest;


class DocumentController extends Controller
{
    /**
     * Display a listing of the documents for a specific intramural game.
     */
    public function index(string $intrams_id)
    {
        $documents = Document::where('intrams_id', $intrams_id)->get();
        return response()->json($documents, 200);
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(StoreDocumentRequest $request)
    {
        $validated = $request->validated();

        // Store file in local storage
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('documents', 'local'); // Stores in storage/app/documents

            // Store file details
            $validated['file_path'] = $filePath;
            $validated['mime_type'] = $file->getClientMimeType();
            $validated['size'] = $file->getSize();
        }

        $document = Document::create($validated);

        return response()->json($document, 201);
    }



    /**
     * Display a specific document.
     */
    public function show(ShowDocumentRequest $request)
    {
        $validated = $request->validated();
        $document = Document::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();
        return response()->json($document, 200);
    }

    /**
     * Update document metadata (name, not file content).
     */
    public function update(UpdateDocumentRequest $request)
    {
        $validated = $request->validated();
        $document = Document::findOrFail($validated['id']);
        $document->update($validated);

        return response()->json(['message' => 'Document updated successfully', 'document' => $document], 200);
    }

    /**
     * Delete a document.
     */
    public function destroy(DestroyDocumentRequest $request)
    {
        $validated = $request->validated();
        $document = Document::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();

        // Delete the stored file
        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully.'], 204);
    }

    /**
     * Download a document file.
     */
    public function download(string $intrams_id, string $id)
    {
        $document = Document::where('id', $id)->where('intrams_id', $intrams_id)->firstOrFail();
        
        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Extract the original filename from the file path
        $originalFilename = pathinfo($document->file_path, PATHINFO_BASENAME);

        return response()->download(storage_path("app/{$document->file_path}"), $originalFilename);
    }
}
