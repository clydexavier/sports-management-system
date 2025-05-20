<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PodiumRequests\StorePodiumRequest;
use App\Http\Requests\PodiumRequests\ShowPodiumRequest;
use App\Http\Requests\PodiumRequests\UpdatePodiumRequest;
use App\Http\Requests\PodiumRequests\DestroyPodiumRequest;
use Illuminate\Support\Facades\Storage;

use App\Models\Podium;
use App\Models\OverallTeam;
use App\Models\Event;
use App\Models\IntramuralGame;
use App\Models\User;



class PodiumController extends Controller
{
    //

    public function index(Request $request, string $intrams_id)
    {
        \Log::info('Incoming data: ', $request->all());
        $perPage = 12;

        $type = $request->query('type');
        $search = $request->query('search');
        $intrams = IntramuralGame::findOrFail($intrams_id);
        $query = Podium::with([
            'event',
            'gold',
            'silver',
            'bronze',
        ])->where('intrams_id', $intrams_id);

        // Filter by event type (from events table)
        if ($type && $type !== 'all') {
            $query->whereHas('event', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        // Search by event name
        if ($search) {
            $query->whereHas('event', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        // Filter to only include standalone events and parent events (where parent_id is null)
        $query->whereHas('event', function ($q) {
            $q->whereNull('parent_id');
        });

        $podiums = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $podiums->map(function ($podium) {
            return [
                'event' => [
                    'name' => $podium->event->category . ' ' . $podium->event->name,
                    'type' => $podium->event->type,
                ],
                'gold_team_logo' => $podium->gold?->team_logo_path 
                    ? asset('storage/' . $podium->gold->team_logo_path) 
                    : null,
                'gold_team_name' => $podium->gold->name,    
                'silver_team_logo' => $podium->silver?->team_logo_path 
                    ? asset('storage/' . $podium->silver->team_logo_path) 
                    : null,
                'silver_team_name' => $podium->silver->name,
                'bronze_team_logo' => $podium->bronze?->team_logo_path 
                    ? asset('storage/' . $podium->bronze->team_logo_path) 
                    : null,
                'bronze_team_name' => $podium->bronze->name,
                'medals' => $podium->event->gold,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $podiums->currentPage(),
                'per_page' => $podiums->perPage(),
                'total' => $podiums->total(),
                'last_page' => $podiums->lastPage(),
            ],
            'intrams_name' => $intrams->name, 
        ], 200);
    }


    
    public function store(StorePodiumRequest $request) 
    {
        $validated = $request->validated();
        $podium = Podium::create($validated);
        
        $event = Event::find($validated['event_id']);
        if ($event) {
            $event->status = 'completed';
            $event->save();
        }
        return response()->json($podium, 201);
    }

    public function show(ShowPodiumRequest $request)
    {   
        $validated = $request->validated();
        $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();

        $gold_team = OverallTeam::find($podium->gold_team_id);
        $silver_team = OverallTeam::find($podium->silver_team_id);
        $bronze_team = OverallTeam::find($podium->bronze_team_id);

        
        return response()->json([
            'id' => $podium->id,
            'intrams_id' => $podium->intrams_id,
            'event_id' => $podium->event_id,
            'gold_team_id' => $podium->gold_team_id,
            'gold_team_name' => $gold_team?->name,
            'silver_team_id' => $podium->silver_team_id,
            'silver_team_name' => $silver_team?->name,
            'bronze_team_id' => $podium->bronze_team_id,
            'bronze_team_name' => $bronze_team?->name,
            'created_at' => $podium->created_at,
            'updated_at' => $podium->updated_at,
        ]);
    }

    public function update(UpdatePodiumRequest $request)
    {
        $validated = $request->validated();

        $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();

        $podium->update($validated);

        // Update the related event status to "completed"
        $event = Event::find($validated['event_id']);
        if ($event) {
            $event->status = 'completed';
            $event->save();
        }

        return response()->json($podium, 200);
    }


    public function destroy(DestroyPodiumRequest $request)
    {
        $validated = $request->validated();
        $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();
        $podium->delete();

        return response()->json(204);
    }
    /**
     * Generate a PDF of the podium results for an intramural
     * 
     * @param Request $request
     * @param string $intrams_id
     * @return \Illuminate\Http\Response
     */
    public function generatePodiumPDF(Request $request, string $intrams_id)
    {
        // Get intramural game
        $intrams = IntramuralGame::findOrFail($intrams_id);
        
        // Get all completed events with podiums for this intramural
        $events = Event::where('intrams_id', $intrams_id)
                    ->where('status', 'completed')
                    ->get();
        
        // Create new PDF document
        $pdf = new \FPDF('P', 'mm', 'A4');
        
        // Set document information
        $pdf->SetTitle('Podium Results - ' . $intrams->name);
        $pdf->SetAuthor('System Generated');
        
        // Create variables to track layout
        $eventsPerPage = 2; // Two events per page (half page each)
        $eventCount = 0;
        
        // Process each event
        foreach ($events as $event) {
            // Get podium for this event
            $podium = Podium::where('event_id', $event->id)
                            ->where('intrams_id', $intrams_id)
                            ->first();
            
            if (!$podium) {
                continue; // Skip if no podium exists for this event
            }
            
            // Get teams
            $goldTeam = OverallTeam::find($podium->gold_team_id);
            $silverTeam = OverallTeam::find($podium->silver_team_id);
            $bronzeTeam = OverallTeam::find($podium->bronze_team_id);
            
            // Get tsecretary who submitted the podium
            $tsecretary = User::where('event_id', $event->id)
                            ->where('role', 'tsecretary')
                            ->first();
            
            // Add a new page for every two events
            if ($eventCount % $eventsPerPage === 0) {
                $pdf->AddPage();
                // Add intrams title at the top of each page
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->Cell(0, 10, $intrams->name . ' - Podium Results', 0, 1, 'C');
                $pdf->Ln(2);
            }
            
            // Determine Y position based on event count
            // First event starts at y=30, second at y=150 (half page)
            $yStart = ($eventCount % $eventsPerPage === 0) ? 30 : 150;
            $pdf->SetY($yStart);
            
            // Add event header
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, $event->name, 0, 1, 'C');
            
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 6, 'Category: ' . $event->category, 0, 1, 'C');
            $pdf->Ln(10);
            
            // Define margin from left edge
            $leftMargin = 30;
            $pdf->SetX($leftMargin);
            
            // Set consistent color for all labels (black)
            $pdf->SetTextColor(0, 0, 0);
            
            // Gold (First Place)
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, 'First:', 0, 0, 'L'); // Left-aligned
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 8, $goldTeam ? $goldTeam->name : 'N/A', 0, 1, 'L');
            $pdf->SetX($leftMargin);
            
            // Silver (Second Place)
            $pdf->SetTextColor(0, 0, 0); // Reset to black for label
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, 'Second:', 0, 0, 'L'); // Left-aligned
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 8, $silverTeam ? $silverTeam->name : 'N/A', 0, 1, 'L');
            $pdf->SetX($leftMargin);
            
            // Bronze (Third Place)
            $pdf->SetTextColor(0, 0, 0); // Reset to black for label
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, 'Third:', 0, 0, 'L'); // Left-aligned
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 8, $bronzeTeam ? $bronzeTeam->name : 'N/A', 0, 1, 'L');
            
            // Reset text color to black
            $pdf->SetTextColor(0, 0, 0);
            
            // Add medal counts if available
            if ($event->gold > 0 || $event->silver > 0 || $event->bronze > 0) {
                $pdf->Ln(5);
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->Cell(0, 6, 'Medal Value: Gold(' . $event->gold . ') Silver(' . $event->silver . ') Bronze(' . $event->bronze . ')', 0, 1, 'C');
            }
            
            // Add tsecretary information at the bottom of each half-page
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetY($yStart + 95);
            $pdf->Cell(0, 5, 'Submitted by: ' . ($tsecretary ? $tsecretary->name : 'System'), 0, 1, 'R');
            $pdf->Cell(0, 5, 'Date: ' . now()->format('Y-m-d'), 0, 1, 'R');
            
            // Increment event counter
            $eventCount++;
        }
        
        // Handle case when no events have podiums
        if ($eventCount === 0) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, $intrams->name . ' - Podium Results', 0, 1, 'C');
            $pdf->Ln(20);
            $pdf->SetFont('Arial', 'I', 12);
            $pdf->Cell(0, 10, 'No completed events with podium results found.', 0, 1, 'C');
        }
        
        // Prepare filename - use a standardized name for easy replacement
        $filename = 'podium_results_' . $intrams_id . '.pdf';
        $storagePath = 'podium_pdfs/' . $filename;
        
        // Check if a previous PDF exists and delete it
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
            \Log::info('Replaced existing podium PDF for intramural ID: ' . $intrams_id);
        }
        
        // Ensure directory exists
        Storage::makeDirectory('public/podium_pdfs');
        
        // Save PDF to storage
        Storage::put('public/' . $storagePath, $pdf->Output('S'));
        
        // Return file for download
        return response()->download(
            storage_path('app/public/' . $storagePath),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Delete the podium PDF for a specific intramural
     * 
     * @param string $intrams_id
     * @return \Illuminate\Http\Response
     */
    public function deletePodiumPDF(Request $request, string $intrams_id)
    {
        // Verify intramural exists
        $intrams = IntramuralGame::findOrFail($intrams_id);
        
        // Define the standard filename pattern used in generatePodiumPDF
        $filename = 'podium_results_' . $intrams_id . '.pdf';
        $storagePath = 'podium_pdfs/' . $filename;
        
        // Check if PDF exists
        if (!Storage::disk('public')->exists($storagePath)) {
            return response()->json([
                'message' => 'No podium PDF found for this intramural'
            ], 404);
        }
        
        // Delete the file
        Storage::disk('public')->delete($storagePath);
        
        return response()->json([
            'message' => 'Podium PDF successfully deleted'
        ], 200);
    }
    
    
    /**
     * Generate a PDF of the podium results for a specific event
     * 
     * @param Request $request
     * @param string $intrams_id
     * @param string $event_id
     * @return \Illuminate\Http\Response
     */
    public function generateSingleEventPodiumPDF(Request $request, string $intrams_id, string $event_id)
    {
        // Get intramural game
        $intrams = IntramuralGame::findOrFail($intrams_id);
        
        // Get the specific event
        $event = Event::where('id', $event_id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();
                    
        // Get podium for this event
        $podium = Podium::where('event_id', $event_id)
                        ->where('intrams_id', $intrams_id)
                        ->first();
        
        if (!$podium) {
            return response()->json([
                'message' => 'No podium exists for this event'
            ], 404);
        }
        
        // Get teams
        $goldTeam = OverallTeam::find($podium->gold_team_id);
        $silverTeam = OverallTeam::find($podium->silver_team_id);
        $bronzeTeam = OverallTeam::find($podium->bronze_team_id);
        
        // Get tsecretary who submitted the podium
        $tsecretary = User::where('event_id', $event_id)
                        ->where('role', 'tsecretary')
                        ->first();
        
        // Create new PDF document
        $pdf = new \FPDF('P', 'mm', 'A4');
        
        // Set document information
        $pdf->SetTitle($event->name . ' - Podium Results');
        $pdf->SetAuthor('System Generated');
        
        // Add page
        $pdf->AddPage();
        
        // Starting at top of page
        $pdf->SetY(30);
        
        // Add intrams and event titles - using smaller fonts
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, $intrams->name . ' - Podium Results', 0, 1, 'C');
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, $event->name, 0, 1, 'C');
        
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 6, 'Category: ' . $event->category, 0, 1, 'C');
        $pdf->Ln(5);
        
        // Define margin from left edge - reduced to match other podium format
        $leftMargin = 30;
        $pdf->SetX($leftMargin);
        
        // Set consistent color for all labels (black)
        $pdf->SetTextColor(0, 0, 0);
        
        // Gold (First Place)
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 8, 'First:', 0, 0, 'L'); // Left-aligned
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, $goldTeam ? $goldTeam->name : 'N/A', 0, 1, 'L');
        $pdf->SetX($leftMargin);
        
        // Silver (Second Place)
        $pdf->SetTextColor(0, 0, 0); // Reset to black for label
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 8, 'Second:', 0, 0, 'L'); // Left-aligned
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, $silverTeam ? $silverTeam->name : 'N/A', 0, 1, 'L');
        $pdf->SetX($leftMargin);
        
        // Bronze (Third Place)
        $pdf->SetTextColor(0, 0, 0); // Reset to black for label
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 8, 'Third:', 0, 0, 'L'); // Left-aligned
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, $bronzeTeam ? $bronzeTeam->name : 'N/A', 0, 1, 'L');
        
        // Reset text color to black
        $pdf->SetTextColor(0, 0, 0);
        
        // Add medal counts if available
        if ($event->gold > 0 || $event->silver > 0 || $event->bronze > 0) {
            $pdf->Ln(3);
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(0, 6, 'Medal Value: Gold(' . $event->gold . ') Silver(' . $event->silver . ') Bronze(' . $event->bronze . ')', 0, 1, 'C');
        }
        
        // Add tsecretary information at the bottom of the half-page (around Y=125)
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetY(125); // Position at end of half page
        $pdf->Cell(0, 5, 'Submitted by: ' . ($tsecretary ? $tsecretary->name : 'System'), 0, 1, 'R');
        $pdf->Cell(0, 5, 'Date: ' . now()->format('Y-m-d'), 0, 1, 'R');
        
        // Use a very simple filename pattern for testing
        $filename = 'event_' . $event_id . '.pdf';
        $storagePath = 'podium_pdfs/' . $filename;
        
        // Log for debugging
        \Log::info('Generating single event podium PDF', [
            'event_id' => $event_id,
            'file_path' => $storagePath
        ]);
        
        // Ensure directory exists
        Storage::makeDirectory('public/podium_pdfs');
        
        // Check if a previous PDF exists for this event and delete it
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
            \Log::info('Replaced existing event podium PDF', [
                'event_id' => $event_id,
                'path' => $storagePath
            ]);
        }
        
        // Save PDF to storage
        Storage::put('public/' . $storagePath, $pdf->Output('S'));
        
        // Return file for download
        return response()->download(
            storage_path('app/public/' . $storagePath),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Delete the podium PDF for a specific event
     * 
     * @param Request $request
     * @param string $intrams_id
     * @param string $event_id
     * @return \Illuminate\Http\Response
     */
    public function deleteSingleEventPodiumPDF(Request $request, string $intrams_id, string $event_id)
    {
        // Verify intramural and event exist
        $intrams = IntramuralGame::findOrFail($intrams_id);
        $event = Event::where('id', $event_id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();
        
        // Use the simple filename pattern
        $filename = 'event_' . $event_id . '.pdf';
        $storagePath = 'podium_pdfs/' . $filename;
        
        // Log files in directory for debugging
        \Log::debug('Files in podium_pdfs directory:', Storage::disk('public')->files('podium_pdfs'));
        \Log::debug('Looking for file:', ['path' => $storagePath]);
        
        // Check if PDF exists
        if (!Storage::disk('public')->exists($storagePath)) {
            return response()->json([
                'message' => 'No podium PDF found for this event'
            ], 404);
        }
        
        // Delete the file
        Storage::disk('public')->delete($storagePath);
        \Log::info('Deleted podium PDF for event: ' . $event->name . ' (ID: ' . $event_id . ')');
        
        // Return success response
        return response()->json([
            'message' => 'Podium PDF for event "' . $event->name . '" successfully deleted'
        ], 200);
    }

    
}