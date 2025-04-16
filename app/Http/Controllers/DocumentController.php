<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
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
    public function index(Request $request, string $intrams_id)
    {
        \Log::info('Incoming data: ', $request->all());

        $perPage = 12;

        $type = $request->query('type');
        $search = $request->query('search');

        $query = Document::where('intrams_id', $intrams_id);

        if ($type && $type !== 'All') {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate($perPage);


        $documentsData = $documents->items();
        foreach ($documentsData as $doc) {
            if ($doc->file_path) {
                $doc->file_path = asset('storage/' . $doc->file_path);            
            }
        }
       
        return response()->json([
            'data' => $documentsData,
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'last_page' => $documents->lastPage(),
            ]
        ], 200);
    }


    /**
     * Store a newly uploaded document.
     */
    public function store(StoreDocumentRequest $request)
    {
        \Log::info('Incoming data: ', $request->all());

        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Retrieve mime type and size before moving the file
            $validated['mime_type'] = $file->getClientMimeType();
            $validated['size'] = $file->getSize();

            // Store the file
            $path = $file->store('documents', 'public');
            $validated['file_path'] = $path;
        }

        $document = Document::create($validated);

        if ($document->file_path) {
            $document->file_url = asset('storage/' . $document->file_path);
        }

        return response()->json($document, 201);
    }


    /**
     * Display a specific document.
     */
    public function show(ShowDocumentRequest $request)
    {
        $validated = $request->validated();
        $document = Document::where('id', $validated['id'])
            ->where('intrams_id', $validated['intrams_id'])
            ->firstOrFail();

        if ($document->file_path) {
            $document->file_url = asset('storage/' . $document->file_path);
        }

        return response()->json($document, 200);
    }

    /**
     * Update document metadata (e.g. name), optionally update the file.
     */
    public function update(UpdateDocumentRequest $request)
    {
        $validated = $request->validated();

        $document = Document::where('id', $validated['id'])
            ->where('intrams_id', $validated['intrams_id'])
            ->firstOrFail();

        if ($request->has('remove_file') && $document->file_path) {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            $validated['file_path'] = null;
            $validated['mime_type'] = null;
            $validated['size'] = null;
        }

        if ($request->hasFile('file')) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $file = $request->file('file');
            $path = $file->store('documents', 'public');

            $validated['file_path'] = $path;
            $validated['mime_type'] = $file->getClientMimeType();
            $validated['size'] = $file->getSize();
        }

        $document->update($validated);

        if ($document->file_path) {
            $document->file_url = asset('storage/' . $document->file_path);
        }

        return response()->json(['message' => 'Document updated successfully', 'document' => $document], 200);
    }

    /**
     * Delete a document and its file.
     */
    public function destroy(DestroyDocumentRequest $request)
    {
        $validated = $request->validated();
        $document = Document::where('id', $validated['id'])
            ->where('intrams_id', $validated['intrams_id'])
            ->firstOrFail();

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully.'], 204);
    }

    /**
     * Download a document file.
     */
    public function download(string $intrams_id, string $id)
    {
        $document = Document::where('id', $id)
            ->where('intrams_id', $intrams_id)
            ->firstOrFail();

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $originalFilename = pathinfo($document->file_path, PATHINFO_BASENAME);
        return response()->download(storage_path("app/public/{$document->file_path}"), $originalFilename);
    }
}
