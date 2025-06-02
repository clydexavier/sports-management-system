<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\IntramuralRequests\StoreIntramuralGameRequest;
use App\Http\Requests\IntramuralRequests\UpdateIntramuralGameRequest;
use App\Models\IntramuralGame;
use App\Models\Podium;
use App\Models\Event;
use Cloudinary\Cloudinary;



class IntramuralGameController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        // Initialize Cloudinary with the CLOUDINARY_URL from .env
        $this->cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = 12;
        $status = $request->query('status');
        $search = $request->query('search');

        $query = IntramuralGame::query();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $games = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $games->items(),
            'meta' => [
                'current_page' => $games->currentPage(),
                'per_page' => $games->perPage(),
                'total' => $games->total(),
                'last_page' => $games->lastPage(),
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIntramuralGameRequest $request)
    {
        //
        $validated = $request->validated();
        $intramural = IntramuralGame::create($validated);

        return response()->json($intramural, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $game = IntramuralGame::findOrFail($id);
        return response()->json($game, 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIntramuralGameRequest $request)
    {
        //
        $validated = $request->validated();
        $game = IntramuralGame::findOrFail($validated['id']);
        $game->update($validated);

        return response()->json(['message' =>'Game updated successfully', 'game' => $game], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        try {
            $game = IntramuralGame::findOrFail($id);
            $game->delete();
            return response()->json(['message' => 'intramural game deleted successfully.'], 204);    
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'intramural game not found'], 404);
        }
        
    }

    public function overall_tally(Request $request, string $id)
    {
        // Get the type filter from the request, default to 'overall'
        $type = $request->query('type', 'overall');
        
        // Load intramural with teams and all podiums + related event and team data
        $intramural = IntramuralGame::with([
            'teams', 
            'podiums.event', 
            'podiums.gold', 
            'podiums.silver', 
            'podiums.bronze',
            'podiums.event.parent'
        ])->findOrFail($id);

        $tally = [];

        // Step 1: Initialize all teams with zero medals
        foreach ($intramural->teams as $team) {
            $tally[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_logo_path' => $team->team_logo_path ? $team->team_logo_path : null,
                'gold' => 0,
                'silver' => 0,
                'bronze' => 0,
            ];
        }

        // Step 2: Identify dependent umbrella events to avoid double-counting their sub-events
        $dependentUmbrellaIds = Event::where('intrams_id', $id)
                                ->where('is_umbrella', true)
                                ->where('has_independent_medaling', false)
                                ->pluck('id')
                                ->toArray();

        // Step 3: Loop through podiums and count medals based on event medal allocations
        foreach ($intramural->podiums as $podium) {
            $event = $podium->event;
            if (!$event) continue;
            
            // Skip this event if it doesn't match the type filter
            if ($type !== 'overall' && strtolower($event->type) !== strtolower($type)) {
                continue;
            }

            // Skip sub-events of dependent medaling umbrella events to avoid double counting
            if ($event->parent_id && in_array($event->parent_id, $dependentUmbrellaIds)) {
                continue;
            }

            // Also skip the umbrella event itself if it has independent medaling (we count the sub-events instead)
            if ($event->is_umbrella && $event->has_independent_medaling) {
                continue;
            }

            $goldCount = $event->gold ?? 0;
            $silverCount = $event->silver ?? 0;
            $bronzeCount = $event->bronze ?? 0;

            $addMedal = function ($team, $medal, $count = 1) use (&$tally) {
                if (!$team) return;

                if (!isset($tally[$team->id])) {
                    $tally[$team->id] = [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'team_logo_path' => $team->team_logo_path ? $team->team_logo_path : null,
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ];
                }

                $tally[$team->id][$medal] += $count;
            };

            $addMedal($podium->gold, 'gold', $goldCount);
            $addMedal($podium->silver, 'silver', $silverCount);
            $addMedal($podium->bronze, 'bronze', $bronzeCount);
        }

        // Filter out teams with no medals (only if a specific type is selected)
        if ($type !== 'overall') {
            $tally = array_filter($tally, function ($teamTally) {
                return $teamTally['gold'] > 0 || $teamTally['silver'] > 0 || $teamTally['bronze'] > 0;
            });
            
            // If no teams have medals for this category, we'll keep all teams with zero counts
            if (empty($tally)) {
                foreach ($intramural->teams as $team) {
                    $tally[$team->id] = [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'team_logo_path' => $team->team_logo_path ? $team->team_logo_path : null,
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ];
                }
            }
        }

        // Step 4: Sort the tally by gold, silver, then bronze
        $sorted = collect($tally)->sort(function ($a, $b) {
            return [$b['gold'], $b['silver'], $b['bronze']] <=> [$a['gold'], $a['silver'], $a['bronze']];
        })->values();

        return response()->json([
            'data' => $sorted,
            'intrams_name' => $intramural->name,
        ], 200);
    }

    public function events(Request $request, string $intrams_id)
    {
        // Get all events
        $events = Event::where('intrams_id', $intrams_id)->get();
        
        // Filter events based on our medal distribution logic
        $filteredEvents = $events->filter(function ($event) {
            // Skip sub-events of dependent medaling umbrella events
            if ($event->parent_id) {
                $parent = Event::find($event->parent_id);
                if ($parent && $parent->is_umbrella && !$parent->has_independent_medaling) {
                    return false;
                }
            }
            
            // Skip umbrella events with independent medaling (we show their sub-events instead)
            if ($event->is_umbrella && $event->has_independent_medaling) {
                return false;
            }
            
            return true;
        });
        
        // Format the events
        $formattedEvents = $filteredEvents->map(function ($event) {
            return [
                'id' => $event->id,
                'name' => $event->category . ' ' . $event->name,
                'is_umbrella' => $event->is_umbrella,
                'has_independent_medaling' => $event->has_independent_medaling
            ];
        });

        return response()->json($formattedEvents, 200);
    }
    /**
     * Generate a comprehensive PDF report of team medal breakdowns
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function generateTeamMedalBreakdownPDF(Request $request, string $id)
    {
        // Get the type filter from the request, default to 'overall'
        $type = $request->query('type', 'overall');
        
        // Get intramural game
        $intramural = IntramuralGame::findOrFail($id);
        
        // Use the same logic as teamMedalBreakdown to get data
        $teamBreakdowns = $this->getTeamMedalBreakdownData($id, $type);
        
        // Create new TCPDF document
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor('System Generated');
        $pdf->SetTitle('Team Medal Breakdown Report - ' . $intramural->name);
        $pdf->SetSubject('Team Medal Breakdown');
        
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
        
        // Add first page
        $pdf->AddPage();
        
        // Title page
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 15, 'TEAM MEDAL BREAKDOWN REPORT', 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $intramural->name, 0, 1, 'C');
        $pdf->Ln(3);
        
        $pdf->SetFont('helvetica', '', 12);
        $typeLabel = ucfirst($type) . ' Events';
        $pdf->Cell(0, 8, $typeLabel, 0, 1, 'C');
        $pdf->Ln(3);
        
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 6, 'Generated on: ' . now()->format('F j, Y \a\t g:i A'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Summary section
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'SUMMARY', 0, 1, 'L');
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', '', 10);
        $totalTeams = count($teamBreakdowns['data']);
        $teamsWithMedals = count(array_filter($teamBreakdowns['data'], function($team) {
            return $team['total_medals'] > 0;
        }));
        
        $pdf->Cell(50, 6, 'Total Teams:', 0, 0, 'L');
        $pdf->Cell(0, 6, $totalTeams, 0, 1, 'L');
        $pdf->Cell(50, 6, 'Teams with Medals:', 0, 0, 'L');
        $pdf->Cell(0, 6, $teamsWithMedals, 0, 1, 'L');
        $pdf->Cell(50, 6, 'Category Filter:', 0, 0, 'L');
        $pdf->Cell(0, 6, ucfirst($type), 0, 1, 'L');
        $pdf->Ln(8);
        
        // Process each team
        foreach ($teamBreakdowns['data'] as $index => $team) {
            // Add new page for each team (except the first one)
            if ($index > 0) {
                $pdf->AddPage();
            }
            
            // Team header
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetFillColor(240, 248, 240); // Light green background
            $pdf->Cell(0, 10, strtoupper($team['team_name']), 1, 1, 'C', true);
            $pdf->Ln(2);
            
            // Team medal summary
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 8, 'Medal Summary', 0, 1, 'L');
            
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(40, 6, 'Gold Medals:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(255, 215, 0); // Gold color
            $pdf->Cell(20, 6, $team['total_gold'], 0, 0, 'L');
            
            $pdf->SetTextColor(0, 0, 0); // Reset to black
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(40, 6, 'Silver Medals:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(192, 192, 192); // Silver color
            $pdf->Cell(20, 6, $team['total_silver'], 0, 0, 'L');
            
            $pdf->SetTextColor(0, 0, 0); // Reset to black
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(40, 6, 'Bronze Medals:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(205, 127, 50); // Bronze color
            $pdf->Cell(0, 6, $team['total_bronze'], 0, 1, 'L');
            
            $pdf->SetTextColor(0, 0, 0); // Reset to black
            $pdf->Ln(3);
            
            // Events participated
            if (!empty($team['events'])) {
                 // Sort events alphabetically by event name
                usort($team['events'], function($a, $b) {
                    return strcasecmp($a['event_name'], $b['event_name']);
                });
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 8, 'Events Participated (' . count($team['events']) . ' events)', 0, 1, 'L');
                $pdf->Ln(1);
                
                // Table header for events (removed Total column, adjusted widths)
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(230, 230, 230); // Light gray background
                $pdf->Cell(110, 7, 'Event Name', 1, 0, 'C', true);
                $pdf->Cell(25, 7, 'Gold', 1, 0, 'C', true);
                $pdf->Cell(25, 7, 'Silver', 1, 0, 'C', true);
                $pdf->Cell(20, 7, 'Bronze', 1, 1, 'C', true);
                
                // Event rows (removed total calculation and column)
                $pdf->SetFont('helvetica', '', 9);
                foreach ($team['events'] as $eventIndex => $event) {
                    // Alternate row colors
                    $fillColor = ($eventIndex % 2 == 0) ? [250, 250, 250] : [255, 255, 255];
                    $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
                    
                    $pdf->Cell(110, 6, $event['event_name'], 1, 0, 'L', true);
                    
                    // Gold medals with color
                    if ($event['gold_medals'] > 0) {
                        $pdf->SetTextColor(184, 134, 11); // Dark gold
                        $pdf->Cell(25, 6, $event['gold_medals'], 1, 0, 'C', true);
                        $pdf->SetTextColor(0, 0, 0);
                    } else {
                        $pdf->Cell(25, 6, '-', 1, 0, 'C', true);
                    }
                    
                    // Silver medals with color
                    if ($event['silver_medals'] > 0) {
                        $pdf->SetTextColor(107, 114, 128); // Dark silver
                        $pdf->Cell(25, 6, $event['silver_medals'], 1, 0, 'C', true);
                        $pdf->SetTextColor(0, 0, 0);
                    } else {
                        $pdf->Cell(25, 6, '-', 1, 0, 'C', true);
                    }
                    
                    // Bronze medals with color
                    if ($event['bronze_medals'] > 0) {
                        $pdf->SetTextColor(180, 83, 9); // Dark bronze
                        $pdf->Cell(20, 6, $event['bronze_medals'], 1, 1, 'C', true);
                        $pdf->SetTextColor(0, 0, 0);
                    } else {
                        $pdf->Cell(20, 6, '-', 1, 1, 'C', true);
                    }
                }
            } else {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 8, 'No medals won in any events', 0, 1, 'L');
            }
            
            $pdf->Ln(8);
        }
        
        // Add footer information on last page
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'This report was automatically generated by the Sports Management System', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Report includes: ' . $typeLabel . ' | Total Teams: ' . $totalTeams . ' | Teams with Medals: ' . $teamsWithMedals, 0, 1, 'C');
        
        // Get PDF content as string
        $pdfContent = $pdf->Output('', 'S');
        
        // Upload to Cloudinary
        $filename = 'team_medal_breakdown_' . $id . '_' . $type . '.pdf';
        $folder = 'medal_reports';
        
        // Check if a previous version exists in Cloudinary and delete it
        $publicId = $folder . '/' . pathinfo($filename, PATHINFO_FILENAME);
        try {
            // Try to delete the existing PDF if it exists
            $this->cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'raw']);
            \Log::info('Removed existing team medal breakdown PDF from Cloudinary: ' . $publicId);
        } catch (\Exception $e) {
            // Ignore error if the file doesn't exist
            \Log::info('No existing team medal breakdown PDF to delete or delete failed: ' . $e->getMessage());
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
        
        \Log::info('Uploaded team medal breakdown PDF to Cloudinary', [
            'public_id' => $upload['public_id'],
            'url' => $upload['secure_url']
        ]);
        
        // Redirect to the uploaded PDF
        return redirect($upload['secure_url']);
    }

    /**
     * Helper method to get team medal breakdown data
     * (This extracts the logic from your teamMedalBreakdown method)
     */
    private function getTeamMedalBreakdownData(string $id, string $type = 'overall')
    {
        // Load intramural with teams
        $intramural = IntramuralGame::with('teams')->findOrFail($id);

        // Get events to display: only standalone events and parent events
        $eventsToDisplay = Event::where('intrams_id', $id)
            ->where(function($query) {
                $query->whereNull('parent_id') // Standalone events or parent events
                    ->orWhere('is_umbrella', true); // Umbrella/parent events
            })
            ->when($type !== 'overall', function($query) use ($type) {
                $query->where('type', $type);
            })
            ->get();

        // Initialize breakdown structure for all teams with all events
        $teamBreakdowns = [];
        foreach ($intramural->teams as $team) {
            $teamBreakdowns[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_logo_path' => $team->team_logo_path,
                'events' => [],
                'total_gold' => 0,
                'total_silver' => 0,
                'total_bronze' => 0,
            ];

            // Initialize all events for this team with 0 medals
            foreach ($eventsToDisplay as $event) {
                $teamBreakdowns[$team->id]['events'][$event->id] = [
                    'event_id' => $event->id,
                    'event_name' => $event->category . ' ' . $event->name,
                    'event_type' => $event->type,
                    'is_umbrella' => $event->is_umbrella,
                    'medaling_type' => $event->is_umbrella ? ($event->has_independent_medaling ? 'independent' : 'dependent') : 'standalone',
                    'gold_medals' => 0,
                    'silver_medals' => 0,
                    'bronze_medals' => 0,
                ];

                // Add sub_events_count for umbrella events
                if ($event->is_umbrella && $event->has_independent_medaling) {
                    $subEventsCount = Event::where('parent_id', $event->id)->count();
                    $teamBreakdowns[$team->id]['events'][$event->id]['sub_events_count'] = $subEventsCount;
                }
            }
        }

        // Now populate the actual medal data
        foreach ($eventsToDisplay as $event) {
            // Skip umbrella events that have independent medaling 
            // because we'll aggregate their sub-events instead
            if ($event->is_umbrella && $event->has_independent_medaling) {
                $this->processIndependentUmbrellaEventUniform($event, $teamBreakdowns, $id);
            } else {
                // For standalone events and dependent umbrella events, 
                // use their direct podium data
                $this->processDirectEventPodiumUniform($event, $teamBreakdowns);
            }
        }

        // Convert events from associative array to indexed array
        $result = [];
        foreach ($teamBreakdowns as $teamId => $breakdown) {
            $breakdown['events'] = array_values($breakdown['events']);
            $breakdown['total_events_participated'] = count(array_filter($breakdown['events'], function($event) {
                return $event['gold_medals'] > 0 || $event['silver_medals'] > 0 || $event['bronze_medals'] > 0;
            }));
            $breakdown['total_medals'] = $breakdown['total_gold'] + $breakdown['total_silver'] + $breakdown['total_bronze'];
            $result[] = $breakdown;
        }

        // Sort teams by total medals (highest first)
        usort($result, function($a, $b) {
            $totalA = $a['total_gold'] * 3 + $a['total_silver'] * 2 + $a['total_bronze'];
            $totalB = $b['total_gold'] * 3 + $b['total_silver'] * 2 + $b['total_bronze'];
            
            if ($totalA === $totalB) {
                if ($a['total_gold'] === $b['total_gold']) {
                    if ($a['total_silver'] === $b['total_silver']) {
                        return $b['total_bronze'] - $a['total_bronze'];
                    }
                    return $b['total_silver'] - $a['total_silver'];
                }
                return $b['total_gold'] - $a['total_gold'];
            }
            return $totalB - $totalA;
        });

        return [
            'data' => $result,
            'intrams_name' => $intramural->name,
            'filter_type' => $type,
            'summary' => [
                'total_teams' => count($intramural->teams),
                'teams_with_medals' => count(array_filter($result, function($team) {
                    return $team['total_medals'] > 0;
                })),
                'total_events_displayed' => count($eventsToDisplay)
            ]
        ];
    }

    
    /**
     * Get detailed medal breakdown for each team in an intramural
     * Shows only parent events and standalone events
     * For parent events with independent medaling, aggregates medals from sub-events
     */
    public function teamMedalBreakdown(Request $request, string $id)
    {
        // Get the type filter from the request, default to 'overall'
        $type = $request->query('type', 'overall');
        
        // Load intramural with teams
        $intramural = IntramuralGame::with('teams')->findOrFail($id);

        // Initialize breakdown structure for all teams
        $teamBreakdowns = [];
        foreach ($intramural->teams as $team) {
            $teamBreakdowns[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_logo_path' => $team->team_logo_path,
                'events' => [],
                'total_gold' => 0,
                'total_silver' => 0,
                'total_bronze' => 0,
            ];
        }

        // Get events to display: only standalone events and parent events
        $eventsToDisplay = Event::where('intrams_id', $id)
            ->where(function($query) {
                $query->whereNull('parent_id') // Standalone events or parent events
                    ->orWhere('is_umbrella', true); // Umbrella/parent events
            })
            ->when($type !== 'overall', function($query) use ($type) {
                $query->where('type', $type);
            })
            ->get();

        foreach ($eventsToDisplay as $event) {
            // Skip umbrella events that have independent medaling 
            // because we'll aggregate their sub-events instead
            if ($event->is_umbrella && $event->has_independent_medaling) {
                $this->processIndependentUmbrellaEvent($event, $teamBreakdowns, $id);
            } else {
                // For standalone events and dependent umbrella events, 
                // use their direct podium data
                $this->processDirectEventPodium($event, $teamBreakdowns);
            }
        }

        // Convert events from associative array to indexed array and filter out teams with no medals
        $result = [];
        foreach ($teamBreakdowns as $teamId => $breakdown) {
            if (!empty($breakdown['events']) || $type === 'overall') {
                $breakdown['events'] = array_values($breakdown['events']);
                $breakdown['total_events_participated'] = count($breakdown['events']);
                $breakdown['total_medals'] = $breakdown['total_gold'] + $breakdown['total_silver'] + $breakdown['total_bronze'];
                $result[] = $breakdown;
            }
        }

        // Sort teams by total medals (highest first)
        usort($result, function($a, $b) {
            $totalA = $a['total_gold'] * 3 + $a['total_silver'] * 2 + $a['total_bronze'];
            $totalB = $b['total_gold'] * 3 + $b['total_silver'] * 2 + $b['total_bronze'];
            
            if ($totalA === $totalB) {
                if ($a['total_gold'] === $b['total_gold']) {
                    if ($a['total_silver'] === $b['total_silver']) {
                        return $b['total_bronze'] - $a['total_bronze'];
                    }
                    return $b['total_silver'] - $a['total_silver'];
                }
                return $b['total_gold'] - $a['total_gold'];
            }
            return $totalB - $totalA;
        });

        return response()->json([
            'data' => $result,
            'intrams_name' => $intramural->name,
            'filter_type' => $type,
            'summary' => [
                'total_teams' => count($intramural->teams),
                'teams_with_medals' => count(array_filter($result, function($team) {
                    return $team['total_medals'] > 0;
                })),
                'total_events_displayed' => count($eventsToDisplay)
            ]
        ], 200);
    }

    /**
     * Process independent umbrella events by aggregating medals from all sub-events
     */
    private function processIndependentUmbrellaEventUniform($parentEvent, &$teamBreakdowns, $intramsId)
    {
        // Get all sub-events of this parent
        $subEvents = Event::where('parent_id', $parentEvent->id)
            ->where('intrams_id', $intramsId)
            ->get();

        // Initialize aggregated medal counts for each team
        $aggregatedMedals = [];
        foreach (array_keys($teamBreakdowns) as $teamId) {
            $aggregatedMedals[$teamId] = [
                'gold' => 0,
                'silver' => 0,
                'bronze' => 0
            ];
        }

        // Aggregate medals from all sub-events
        foreach ($subEvents as $subEvent) {
            $podium = Podium::where('event_id', $subEvent->id)->first();
            if (!$podium) continue;

            $goldCount = $subEvent->gold ?? 0;
            $silverCount = $subEvent->silver ?? 0;
            $bronzeCount = $subEvent->bronze ?? 0;

            // Add medals to aggregated totals
            if ($podium->gold_team_id && isset($aggregatedMedals[$podium->gold_team_id])) {
                $aggregatedMedals[$podium->gold_team_id]['gold'] += $goldCount;
            }
            if ($podium->silver_team_id && isset($aggregatedMedals[$podium->silver_team_id])) {
                $aggregatedMedals[$podium->silver_team_id]['silver'] += $silverCount;
            }
            if ($podium->bronze_team_id && isset($aggregatedMedals[$podium->bronze_team_id])) {
                $aggregatedMedals[$podium->bronze_team_id]['bronze'] += $bronzeCount;
            }
        }

        // Update existing event entries with aggregated medals
        foreach ($aggregatedMedals as $teamId => $medals) {
            // Update the existing event entry (already initialized with 0 medals)
            $teamBreakdowns[$teamId]['events'][$parentEvent->id]['gold_medals'] = $medals['gold'];
            $teamBreakdowns[$teamId]['events'][$parentEvent->id]['silver_medals'] = $medals['silver'];
            $teamBreakdowns[$teamId]['events'][$parentEvent->id]['bronze_medals'] = $medals['bronze'];
            
            // Update team totals
            $teamBreakdowns[$teamId]['total_gold'] += $medals['gold'];
            $teamBreakdowns[$teamId]['total_silver'] += $medals['silver'];
            $teamBreakdowns[$teamId]['total_bronze'] += $medals['bronze'];
        }
    }

    /**
     * Process direct event podium (for standalone events and dependent umbrella events)
     * (Modified to update existing event entries instead of creating new ones)
     */
    private function processDirectEventPodiumUniform($event, &$teamBreakdowns)
    {
        $podium = Podium::where('event_id', $event->id)->first();
        if (!$podium) return; // Event entries already exist with 0 medals

        $goldCount = $event->gold ?? 0;
        $silverCount = $event->silver ?? 0;
        $bronzeCount = $event->bronze ?? 0;

        // Update gold medal winner
        if ($podium->gold_team_id && isset($teamBreakdowns[$podium->gold_team_id])) {
            $teamBreakdowns[$podium->gold_team_id]['events'][$event->id]['gold_medals'] = $goldCount;
            $teamBreakdowns[$podium->gold_team_id]['total_gold'] += $goldCount;
        }

        // Update silver medal winner
        if ($podium->silver_team_id && isset($teamBreakdowns[$podium->silver_team_id])) {
            $teamBreakdowns[$podium->silver_team_id]['events'][$event->id]['silver_medals'] = $silverCount;
            $teamBreakdowns[$podium->silver_team_id]['total_silver'] += $silverCount;
        }

        // Update bronze medal winner
        if ($podium->bronze_team_id && isset($teamBreakdowns[$podium->bronze_team_id])) {
            $teamBreakdowns[$podium->bronze_team_id]['events'][$event->id]['bronze_medals'] = $bronzeCount;
            $teamBreakdowns[$podium->bronze_team_id]['total_bronze'] += $bronzeCount;
        }
    }

}