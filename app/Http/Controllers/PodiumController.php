<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PodiumRequests\StorePodiumRequest;
use App\Http\Requests\PodiumRequests\ShowPodiumRequest;
use App\Http\Requests\PodiumRequests\UpdatePodiumRequest;
use App\Http\Requests\PodiumRequests\DestroyPodiumRequest;
use Cloudinary\Cloudinary;

use App\Models\Podium;
use App\Models\OverallTeam;
use App\Models\Event;
use App\Models\IntramuralGame;
use App\Models\User;

class PodiumController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        // Initialize Cloudinary with the CLOUDINARY_URL from .env
        $this->cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
    }

    public function index(Request $request, string $intrams_id)
    {
        \Log::info('Incoming data: ', $request->all());
        $perPage = 12;
        $type = $request->query('type');
        $search = $request->query('search');
        $showSubEvents = $request->query('show_sub_events', false);
        $intrams = IntramuralGame::findOrFail($intrams_id);
        
        $query = Podium::with([
            'event',
            'gold',
            'silver',
            'bronze',
        ])->where('intrams_id', $intrams_id);
        
        // Filter by event type
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
        
        // Filter to include standalone events and parent events unless sub-events are requested
        if (!$showSubEvents) {
            $query->whereHas('event', function ($q) {
                $q->where(function($query) {
                    $query->whereNull('parent_id')
                        ->orWhereHas('parent', function($pq) {
                            $pq->where('has_independent_medaling', true);
                        });
                });
            });
        }
        
        $podiums = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        $data = $podiums->map(function ($podium) {
            $event = $podium->event;
            $isSubEvent = $event->parent_id !== null;
            $parentName = $isSubEvent ? $event->parent->name : null;
            
            return [
                'id' => $podium->id,
                'event_id' => $event->id,
                'event' => [
                    'name' => $event->category . ' ' . $event->name,
                    'type' => $event->type,
                    'is_umbrella' => $event->is_umbrella,
                    'has_independent_medaling' => $event->has_independent_medaling,
                    'is_sub_event' => $isSubEvent,
                    'parent_name' => $parentName
                ],
                'gold_team_logo' => $podium->gold?->team_logo_path ?? null,
                'gold_team_name' => $podium->gold?->name ?? 'N/A',
                'silver_team_logo' => $podium->silver?->team_logo_path ?? null,
                'silver_team_name' => $podium->silver?->name ?? 'N/A',
                'bronze_team_logo' => $podium->bronze?->team_logo_path ?? null,
                'bronze_team_name' => $podium->bronze?->name ?? 'N/A',
                'medals' => $event->gold, // Number of gold medals for this event
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
        $event = Event::find($validated['event_id']);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        // If it's an umbrella event with dependent medaling
        if ($event->is_umbrella && !$event->has_independent_medaling) {
            // Check if all sub-events have their podiums
            $subEventIds = $event->subEvents()->pluck('id');
            $completedPodiums = Podium::whereIn('event_id', $subEventIds)->count();
            
            if ($completedPodiums < count($subEventIds)) {
                return response()->json([
                    'message' => 'Cannot create podium for dependent medaling umbrella event until all sub-events have podiums',
                    'completed' => $completedPodiums,
                    'total' => count($subEventIds)
                ], 422);
            }
            
            // Calculate medals based on sub-event results
            $medalWinners = $event->calculateDependentMedals();
            if ($medalWinners) {
                $validated['gold_team_id'] = $medalWinners['gold_team_id'];
                $validated['silver_team_id'] = $medalWinners['silver_team_id'];
                $validated['bronze_team_id'] = $medalWinners['bronze_team_id'];
            }
        }
        
        $podium = Podium::create($validated);
        
        // Mark the event as completed
        $event->status = 'completed';
        $event->save();
        
        // If this is a sub-event with a parent that has dependent medaling,
        // check if all siblings are completed to auto-calculate parent podium
        if ($event->parent_id) {
            $parent = $event->parent;
            if ($parent && !$parent->has_independent_medaling) {
                $siblingIds = $parent->subEvents()->pluck('id');
                $completedSiblings = Event::whereIn('id', $siblingIds)
                    ->where('status', 'completed')
                    ->count();
                
                if ($completedSiblings == count($siblingIds)) {
                    // All sub-events completed, auto-create parent podium
                    $medalWinners = $parent->calculateDependentMedals();
                    if ($medalWinners) {
                        Podium::create([
                            'intrams_id' => $validated['intrams_id'],
                            'event_id' => $parent->id,
                            'gold_team_id' => $medalWinners['gold_team_id'],
                            'silver_team_id' => $medalWinners['silver_team_id'],
                            'bronze_team_id' => $medalWinners['bronze_team_id']
                        ]);
                        
                        $parent->status = 'completed';
                        $parent->save();
                    }
                }
            }
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
     * Generate a PDF of the podium results for an intramural using TCPDF
     * 
     * @param Request $request
     * @param string $intrams_id
     * @return \Illuminate\Http\Response
     */
    public function generatePodiumPDF(Request $request, string $intrams_id)
    {
        // Get intramural game
        $intrams = IntramuralGame::findOrFail($intrams_id);
        
        // Create new TCPDF document
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor('System Generated');
        $pdf->SetTitle('Podium Results - ' . $intrams->name);
        $pdf->SetSubject('Podium Results');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 15);
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Create variables to track layout
        $eventsPerPage = 2; // Two events per page (half page each)
        $eventCount = 0;
        
        // Get all events that should be displayed in the PDF
        $displayEvents = $this->getEventsForPDF($intrams_id);
        
        // Process each event
        foreach ($displayEvents as $eventData) {
            $event = $eventData['event'];
            $podium = $eventData['podium'];
            
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
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, $intrams->name . ' - Podium Results', 0, 1, 'C');
                $pdf->Ln(2);
            }
            
            // Add a horizontal line to separate results when adding the second event on a page
            if ($eventCount % $eventsPerPage === 1) {
                // Draw a separator line between the two results
                $pdf->SetY(145); // Position just before the second result starts
                $pdf->SetLineWidth(0.5);
                $pdf->Line(15, 145, 195, 145); // Line from left margin to right margin
                
                // Optional: Add a small gap after the line
                $pdf->Ln(2);
            }
            
            // Determine Y position based on event count
            $yStart = ($eventCount % $eventsPerPage === 0) ? 30 : 150;
            $pdf->SetY($yStart);
            
            // Add event header
            $pdf->SetFont('helvetica', 'B', 12);
            
            // Display the event name with sub-event indication if needed
            $displayName = $event->name;
            if (isset($eventData['parent']) && $eventData['parent']) {
                $displayName .= ' (' . $eventData['parent']->name . ')';
            }
            
            $pdf->Cell(0, 8, $displayName, 0, 1, 'C');
            
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->Cell(0, 6, 'Category: ' . $event->category, 0, 1, 'C');
            
            
            
            $pdf->Ln(10);
            
            // Define margin from left edge
            $leftMargin = 30;
            $pdf->SetX($leftMargin);
            
            // Set consistent color for all labels (black)
            $pdf->SetTextColor(0, 0, 0);
            
            // Gold (First Place)
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(30, 8, 'First:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $goldTeam ? $goldTeam->name : 'N/A', 0, 1, 'L');
            $pdf->SetX($leftMargin);
            
            // Silver (Second Place)
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(30, 8, 'Second:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $silverTeam ? $silverTeam->name : 'N/A', 0, 1, 'L');
            $pdf->SetX($leftMargin);
            
            // Bronze (Third Place)
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(30, 8, 'Third:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $bronzeTeam ? $bronzeTeam->name : 'N/A', 0, 1, 'L');
            
            // Add medal counts if available
            if ($event->gold > 0 || $event->silver > 0 || $event->bronze > 0) {
                $pdf->Ln(5);
                $pdf->SetFont('helvetica', 'I', 9);
                $pdf->Cell(0, 6, 'Medal Value: Gold(' . $event->gold . ') Silver(' . $event->silver . ') Bronze(' . $event->bronze . ')', 0, 1, 'C');
            }
            
            // Add tsecretary information at the bottom of each half-page
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetY($yStart + 95);
            $pdf->Cell(0, 5, 'Submitted by: ' . ($tsecretary ? $tsecretary->name : 'System'), 0, 1, 'R');
            $pdf->Cell(0, 5, 'Date: ' . now()->format('Y-m-d'), 0, 1, 'R');
            
            // Increment event counter
            $eventCount++;
        }
        
        // Handle case when no events have podiums
        if ($eventCount === 0) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, $intrams->name . ' - Podium Results', 0, 1, 'C');
            $pdf->Ln(20);
            $pdf->SetFont('helvetica', 'I', 12);
            $pdf->Cell(0, 10, 'No completed events with podium results found.', 0, 1, 'C');
        }
        
        // Get PDF content as string
        $pdfContent = $pdf->Output('', 'S');
        
        // Upload to Cloudinary (rest of the code remains the same)
        $filename = 'podium_results_' . $intrams_id . '.pdf';
        $folder = 'podium_pdfs';
        
        // Check if a previous version exists in Cloudinary and delete it
        $publicId = $folder . '/' . pathinfo($filename, PATHINFO_FILENAME);
        try {
            // Try to delete the existing PDF if it exists
            $this->cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'raw']);
            \Log::info('Removed existing podium PDF from Cloudinary: ' . $publicId);
        } catch (\Exception $e) {
            // Ignore error if the file doesn't exist
            \Log::info('No existing PDF to delete or delete failed: ' . $e->getMessage());
        }
        
        // Upload the new PDF
        $upload = $this->cloudinary->uploadApi()->upload(
            'data:application/pdf;base64,' . base64_encode($pdfContent),
            [
                'folder' => $folder,
                'public_id' => pathinfo($filename, PATHINFO_FILENAME),
                'resource_type' => 'raw'
            ]
        );
        
        \Log::info('Uploaded podium PDF to Cloudinary', [
            'public_id' => $upload['public_id'],
            'url' => $upload['secure_url']
        ]);
        
        // Redirect to the uploaded PDF
        return redirect($upload['secure_url']);
    }

    /**
     * Get the events to display in the PDF based on the event type
     * 
     * @param string $intrams_id
     * @return array
     */
    /**
     * Get the events to display in the PDF based on the event type
     * 
     * @param string $intrams_id
     * @return array
     */
    private function getEventsForPDF(string $intrams_id)
    {
        $displayEvents = [];
        
        // First, collect all parent_ids of dependent medaling umbrella events
        // We'll use this to exclude their sub-events
        $dependentUmbrellaIds = Event::where('intrams_id', $intrams_id)
                                ->where('is_umbrella', true)
                                ->where('has_independent_medaling', false)
                                ->pluck('id')
                                ->toArray();
        
        // Get all completed events for this intramural
        $allEvents = Event::where('intrams_id', $intrams_id)
                        ->where('status', 'completed')
                        ->get();
        
        foreach ($allEvents as $event) {
            // Skip sub-events of dependent medaling umbrella events
            if (in_array($event->parent_id, $dependentUmbrellaIds)) {
                continue;
            }
            
            $podium = Podium::where('event_id', $event->id)
                            ->where('intrams_id', $intrams_id)
                            ->first();
            
            if (!$podium) {
                continue; // Skip if no podium exists for this event
            }
            
            // Case 1: Standalone Event
            if (!$event->is_umbrella) {
                $displayEvents[] = [
                    'event' => $event,
                    'podium' => $podium,
                    'parent' => null
                ];
                continue;
            }
            
            // Case 2: Umbrella Event with Dependent Medaling
            if ($event->is_umbrella && !$event->has_independent_medaling) {
                $displayEvents[] = [
                    'event' => $event,
                    'podium' => $podium,
                    'parent' => null
                ];
                continue;
            }
            
            // Case 3: Umbrella Event with Independent Medaling
            // Skip the umbrella event itself and instead include all its sub-events
            if ($event->is_umbrella && $event->has_independent_medaling) {
                // Get all sub-events
                $subEvents = Event::where('parent_id', $event->id)
                                ->where('status', 'completed')
                                ->get();
                
                foreach ($subEvents as $subEvent) {
                    $subEventPodium = Podium::where('event_id', $subEvent->id)
                                        ->where('intrams_id', $intrams_id)
                                        ->first();
                    
                    if ($subEventPodium) {
                        $displayEvents[] = [
                            'event' => $subEvent,
                            'podium' => $subEventPodium,
                            'parent' => $event // Include reference to parent for display purposes
                        ];
                    }
                }
            }
        }
        
        return $displayEvents;
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
        $publicId = 'podium_pdfs/' . pathinfo($filename, PATHINFO_FILENAME);
        
        try {
            // Delete the file from Cloudinary
            $result = $this->cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'raw']);
            \Log::info('Deleted podium PDF from Cloudinary', ['result' => $result]);
            
            return response()->json([
                'message' => 'Podium PDF successfully deleted'
            ], 200);
        } catch (\Exception $e) {
            // If the file doesn't exist or another error occurs
            \Log::error('Error deleting podium PDF from Cloudinary: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'No podium PDF found for this intramural or error occurred'
            ], 404);
        }
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
        
        // Create new TCPDF document - Portrait (P), mm units, A4 format
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor('System Generated');
        $pdf->SetTitle($event->name . ' - Podium Results');
        $pdf->SetSubject('Podium Results');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 15);
        
        // Add page
        $pdf->AddPage();
        
        // Starting at top of page
        $pdf->SetY(30);
        
        // Add intrams and event titles - using helvetica font
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, $intrams->name . ' - Podium Results', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, $event->name, 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 6, 'Category: ' . $event->category, 0, 1, 'C');
        $pdf->Ln(5);
        
        // Define margin from left edge
        $leftMargin = 30;
        $pdf->SetX($leftMargin);
        
        // Set consistent color for all labels (black)
        $pdf->SetTextColor(0, 0, 0);
        
        // Gold (First Place)
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(30, 8, 'First:', 0, 0, 'L'); // Left-aligned
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 8, $goldTeam ? $goldTeam->name : 'N/A', 0, 1, 'L');
        $pdf->SetX($leftMargin);
        
        // Silver (Second Place)
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(30, 8, 'Second:', 0, 0, 'L'); // Left-aligned
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 8, $silverTeam ? $silverTeam->name : 'N/A', 0, 1, 'L');
        $pdf->SetX($leftMargin);
        
        // Bronze (Third Place)
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(30, 8, 'Third:', 0, 0, 'L'); // Left-aligned
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 8, $bronzeTeam ? $bronzeTeam->name : 'N/A', 0, 1, 'L');
        
        // Add medal counts if available
        if ($event->gold > 0 || $event->silver > 0 || $event->bronze > 0) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->Cell(0, 6, 'Medal Value: Gold(' . $event->gold . ') Silver(' . $event->silver . ') Bronze(' . $event->bronze . ')', 0, 1, 'C');
        }
        
        // Add tsecretary information at the bottom of the half-page
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetY(125); // Position at end of half page
        $pdf->Cell(0, 5, 'Submitted by: ' . ($tsecretary ? $tsecretary->name : 'System'), 0, 1, 'R');
        $pdf->Cell(0, 5, 'Date: ' . now()->format('Y-m-d'), 0, 1, 'R');
        
        // Get PDF content as string
        $pdfContent = $pdf->Output('', 'S');
        
        // Upload to Cloudinary
        $filename = 'event_' . $event_id . '.pdf';
        $folder = 'podium_pdfs';
        
        // Check if a previous version exists in Cloudinary and delete it
        $publicId = $folder . '/' . pathinfo($filename, PATHINFO_FILENAME);
        try {
            // Try to delete the existing PDF if it exists
            $this->cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'raw']);
            \Log::info('Removed existing event podium PDF from Cloudinary: ' . $publicId);
        } catch (\Exception $e) {
            // Ignore error if the file doesn't exist
            \Log::info('No existing event PDF to delete or delete failed: ' . $e->getMessage());
        }
        
        // Upload the new PDF
        $upload = $this->cloudinary->uploadApi()->upload(
            'data:application/pdf;base64,' . base64_encode($pdfContent),
            [
                'folder' => $folder,
                'public_id' => pathinfo($filename, PATHINFO_FILENAME),
                'resource_type' => 'raw'
            ]
        );
        
        \Log::info('Uploaded event podium PDF to Cloudinary', [
            'event_id' => $event_id,
            'public_id' => $upload['public_id'],
            'url' => $upload['secure_url']
        ]);
        
        // Redirect to the uploaded PDF
        return redirect($upload['secure_url']);
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
        
        // Define the standard public ID in Cloudinary
        $filename = 'event_' . $event_id . '.pdf';
        $publicId = 'podium_pdfs/' . pathinfo($filename, PATHINFO_FILENAME);
        
        try {
            // Delete the file from Cloudinary
            $result = $this->cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'raw']);
            \Log::info('Deleted podium PDF for event from Cloudinary', [
                'event_id' => $event_id,
                'result' => $result
            ]);
            
            return response()->json([
                'message' => 'Podium PDF for event "' . $event->name . '" successfully deleted'
            ], 200);
        } catch (\Exception $e) {
            // If the file doesn't exist or another error occurs
            \Log::error('Error deleting event podium PDF from Cloudinary: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'No podium PDF found for this event or error occurred'
            ], 404);
        }
    }
}