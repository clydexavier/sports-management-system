<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Event;
use App\Models\IntramuralGame;
use Illuminate\Support\Facades\Storage;


use App\Services\ChallongeService;




use App\Http\Requests\ScheduleRequests\StoreScheduleRequest;
use App\Http\Requests\ScheduleRequests\ShowScheduleRequest;
use App\Http\Requests\ScheduleRequests\UpdateScheduleRequest;
use App\Http\Requests\ScheduleRequests\DestroyScheduleRequest;



class ScheduleController extends Controller
{
    protected $challonge;

    public function __construct(ChallongeService $challonge)
    {
        $this->challonge = $challonge;
    }
    //
    public function index(Request $request, $intrams_id, $event_id)
    {
        // First, get the event to obtain the Challonge event ID
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->firstOrFail();
        
        // Get all local schedules
        $localSchedules = Schedule::where('intrams_id', $intrams_id)
            ->where('event_id', $event_id)
            ->get()
            ->keyBy('match_id'); // Index by match_id for easy lookup
        
        // Fetch latest data from Challonge
        $challongeMatches = $this->challonge->getMatches($event->challonge_event_id);
        
        // Create a map of match_id to suggested_play_order
        $playOrderMap = [];
        foreach ($challongeMatches as $challongeMatchData) {
            $match = $challongeMatchData['match'] ?? $challongeMatchData;
            $matchId = $match['id'];
            // Store the suggested_play_order for this match
            if (isset($match['suggested_play_order'])) {
                $playOrderMap[$matchId] = (int) $match['suggested_play_order'];
            }
        }
        
        // Fetch participants for mapping IDs to names
        $participants = $this->challonge->getTournamentParticipants($event->challonge_event_id);
        $participantMap = collect($participants)->mapWithKeys(function ($item) {
            $participant = $item['participant'] ?? $item;
            return [$participant['id'] => $participant['name']];
        });
        
        // Process each match from Challonge
        foreach ($challongeMatches as $challongeMatchData) {
            $match = $challongeMatchData['match'] ?? $challongeMatchData;
            $matchId = $match['id'];
            
            // Parse scores if present
            $scoresCsv = $match['scores_csv'] ?? null;
            $winner_id = $match['winner_id'] ?? null;
            $is_completed = !empty($winner_id);
            
            // Calculate simple scores if scores_csv is present
            $score_team1 = null;
            $score_team2 = null;
            
            if ($scoresCsv) {
                // For simple scoring, just count the overall score
                // For set-based scoring, count sets won by each team
                $totalTeam1 = 0;
                $totalTeam2 = 0;
                
                $sets = explode(',', $scoresCsv);
                foreach ($sets as $set) {
                    $setScores = explode('-', $set);
                    if (count($setScores) === 2) {
                        if ((int)$setScores[0] > (int)$setScores[1]) {
                            $totalTeam1++;
                        } else if ((int)$setScores[1] > (int)$setScores[0]) {
                            $totalTeam2++;
                        }
                    }
                }
                
                $score_team1 = $totalTeam1;
                $score_team2 = $totalTeam2;
            }
            
            // Get participant names and IDs, ensuring we don't have null values
            $team1_id = $match['player1_id'] ?? 0; // Use 0 as default instead of null
            $team2_id = $match['player2_id'] ?? 0; // Use 0 as default instead of null
            
            // Determine player1 name with W/L notation if needed
            $team1_name = $participantMap[$team1_id] ?? null;
            if (!$team1_name && isset($match['player1_prereq_match_id'])) {
                $prereqOrder = $playOrderMap[$match['player1_prereq_match_id']] ?? null;
                if ($prereqOrder) {
                    $prefix = isset($match['player1_is_prereq_match_loser']) && $match['player1_is_prereq_match_loser'] ? 'L' : 'W';
                    $team1_name = "{$prefix}{$prereqOrder}";
                } else {
                    $team1_name = 'TBD';
                }
            } else if (!$team1_name) {
                $team1_name = 'TBD';
            }
            
            // Determine player2 name with W/L notation if needed
            $team2_name = $participantMap[$team2_id] ?? null;
            if (!$team2_name && isset($match['player2_prereq_match_id'])) {
                $prereqOrder = $playOrderMap[$match['player2_prereq_match_id']] ?? null;
                if ($prereqOrder) {
                    $prefix = isset($match['player2_is_prereq_match_loser']) && $match['player2_is_prereq_match_loser'] ? 'L' : 'W';
                    $team2_name = "{$prefix}{$prereqOrder}";
                } else {
                    $team2_name = 'TBD';
                }
            } else if (!$team2_name) {
                $team2_name = 'TBD';
            }
            
            // Update or create local record
            if (isset($localSchedules[$matchId])) {
                // Update existing schedule
                $localSchedules[$matchId]->update([
                    'team_1' => $match['player1_id'] ?? 0,
                    'team_2' => $match['player2_id'] ?? 0,
                    'suggested_play_order' => $match['suggested_play_order'],
                    'team1_name' => $team1_name,
                    'team2_name' => $team2_name,
                    'scores_csv' => $scoresCsv,
                    'score_team1' => $score_team1,
                    'score_team2' => $score_team2,
                    'winner_id' => $winner_id,
                    'is_completed' => $is_completed,
                ]);
            } else {
                // Create new schedule if not exists
                Schedule::create([
                    'match_id' => $matchId,
                    'challonge_event_id' => $event->challonge_event_id,
                    'event_id' => $event_id,
                    'suggested_play_order' => $match['suggested_play_order'],
                    'intrams_id' => $intrams_id,
                    'team_1' => $match['player1_id'] ?? 0,
                    'team_2' => $match['player2_id'] ?? 0,
                    'team1_name' => $team1_name,
                    'team2_name' => $team2_name,
                    'scores_csv' => $scoresCsv,
                    'score_team1' => $score_team1,
                    'score_team2' => $score_team2,
                    'winner_id' => $winner_id,
                    'is_completed' => $is_completed,
                    'date' => null,
                    'time' => null,
                    'venue' => null,
                ]);
            }
        }
        
        // Get updated schedules
        $updatedSchedules = Schedule::where('intrams_id', $intrams_id)
            ->where('event_id', $event_id)
            ->get();
        
        // Sort the schedules based on the play order map
        if (!empty($playOrderMap)) {
            $updatedSchedules = $updatedSchedules->sort(function ($a, $b) use ($playOrderMap) {
                $orderA = $playOrderMap[$a->match_id] ?? PHP_INT_MAX; // Default to max value if not found
                $orderB = $playOrderMap[$b->match_id] ?? PHP_INT_MAX;
                return $orderA <=> $orderB; // PHP 7+ spaceship operator for comparison
            })->values(); // Reindex the array after sorting
        }
        
        return response()->json($updatedSchedules, 200);
    }
    public function store(StoreScheduleRequest $request)
    {
        $validated = $request->validated();

        $schedule = Schedule::create($validated);
        
        return response()->json($schedule, 201);
    }

    public function show(ShowScheduleRequest $request)
    {
        $validated = $request->validated();
        $schedule = Schedule::where('id', $validated['id'])->where('event_id', $validated['event_id'])->firstOrFail();

        return response()->json($schedule, 200);
    }

    public function update(UpdateScheduleRequest $request) 
    {
        $validated = $request->validated();
        
        $schedule = Schedule::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();

        $schedule->update($validated);
        
        return response()->json($schedule, 200);

    }

    public function destroy (DestroyScheduleRequest $request)
    {
        $validated = $request->validated();

        $schedule = Schedule::where('id', $validated['id'])->where('event_id', $validated['event_id'])->firstOrFail();


        $schedule->delete();
        return response()->json(200);
    }

    /**
     * Count unique teams in the schedules
     * 
     * @param Collection $schedules
     * @return int
     */
    private function getUniqueTeamCount($schedules)
    {
        $teams = collect();
        
        foreach ($schedules as $schedule) {
            if ($schedule->team_1 && $schedule->team_1 > 0) {
                $teams->push($schedule->team_1);
            }
            if ($schedule->team_2 && $schedule->team_2 > 0) {
                $teams->push($schedule->team_2);
            }
        }
        
        return $teams->unique()->count();
    }

    /**
     * Generate a PDF schedule for an event in A4 Landscape format
     * 
     * @param Request $request
     * @param string $intrams_id
     * @param string $event_id
     * @return \Illuminate\Http\Response
     */
    public function generateSchedulePDF(Request $request, string $intrams_id, string $event_id)
{
    // Get the event details
    $event = Event::where('id', $event_id)
                ->where('intrams_id', $intrams_id)
                ->firstOrFail();
                
    // Get intramural game
    $intrams = IntramuralGame::findOrFail($intrams_id);
    
    // Get all schedules for this event, ordered by suggested_play_order
    $schedules = Schedule::where('event_id', $event_id)
                        ->where('intrams_id', $intrams_id)
                        ->orderBy('suggested_play_order')
                        ->get();
    
    if ($schedules->isEmpty()) {
        return response()->json([
            'message' => 'No schedules found for this event'
        ], 404);
    }

    // Create new TCPDF document - A4 Landscape
    $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(config('app.name'));
    $pdf->SetAuthor('System Generated');
    $pdf->SetTitle($event->name . ' - Schedule');
    $pdf->SetSubject('Match Schedule');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont('courier');
    
    // Set margins
    $pdf->SetMargins(20, 20, 20);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(true, 20);
    
    // Add page
    $pdf->AddPage();
    
    // Define colors (RGB values)
    $headerBgColor = [157, 190, 75]; // Light green for header
    $tableBgColor = [226, 239, 181]; // Lighter green for table
    $timeColColor = [157, 190, 75]; // Same as header for time column
    $vsColor = [255, 0, 0]; // Red for "vs" text
    
    // Set up initial page
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $intrams->name, 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $event->name . ' - Schedule', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Using a more balanced width that won't stretch too wide on A4 landscape
    // Total width of 240mm (instead of the full ~297mm)
    $totalWidth = 240;
    
    // Define balanced column widths based on your example image
    $timeWidth = 30;       // TIME column
    $gameWidth = 30;       // GAME# column
    $teamWidth = 80;       // Each team column (approximately)
    $vsWidth = 20;         // VS column
    $venueWidth = 30;      // VENUE column
    
    // Count the number of teams
    $teamCount = $this->getUniqueTeamCount($schedules);
    
    // Convert RGB arrays to hex strings for TCPDF
    $headerBgColorHex = sprintf('%02X%02X%02X', $headerBgColor[0], $headerBgColor[1], $headerBgColor[2]);
    $tableBgColorHex = sprintf('%02X%02X%02X', $tableBgColor[0], $tableBgColor[1], $tableBgColor[2]);
    $timeColColorHex = sprintf('%02X%02X%02X', $timeColColor[0], $timeColColor[1], $timeColColor[2]);
    $vsColorHex = sprintf('%02X%02X%02X', $vsColor[0], $vsColor[1], $vsColor[2]);
    
    // Add Teams & Event header
    $pdf->SetFillColor($headerBgColor[0], $headerBgColor[1], $headerBgColor[2]);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($timeWidth, 10, $teamCount . ' Teams', 1, 0, 'C', true);
    $pdf->Cell($gameWidth + $teamWidth * 2 + $vsWidth, 10, $event->name, 1, 0, 'C', true);
    $pdf->Cell($venueWidth, 10, '', 1, 1, 'C', true); // Empty cell for VENUE header
    
    // Table Header
    $pdf->SetFillColor($timeColColor[0], $timeColColor[1], $timeColColor[2]);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($timeWidth, 8, 'TIME', 1, 0, 'C', true);
    
    $pdf->SetFillColor($headerBgColor[0], $headerBgColor[1], $headerBgColor[2]);
    $pdf->Cell($gameWidth, 8, 'GAME#', 1, 0, 'C', true);
    
    // We're not using a single cell for team matchups anymore
    // Instead, we create individual columns for team1, vs, team2
    $pdf->Cell($teamWidth * 2 + $vsWidth, 8, '', 1, 0, 'C', true);
    
    $pdf->Cell($venueWidth, 8, 'VENUE', 1, 1, 'C', true);
    
    // Initialize table rows color
    $pdf->SetFillColor($tableBgColor[0], $tableBgColor[1], $tableBgColor[2]);
    
    // Populate schedule rows - using consistent row height
    $rowHeight = 8;
    
    foreach ($schedules as $index => $schedule) {
        // Handle time column
        $pdf->SetFillColor($timeColColor[0], $timeColColor[1], $timeColColor[2]);
        $timeString = '';
        if ($schedule->time) {
            $timeString = date('h:i A', strtotime($schedule->time));
        }
        $pdf->Cell($timeWidth, $rowHeight, $timeString, 1, 0, 'C', true);
        
        // Reset fill color for the rest of the row
        $pdf->SetFillColor($tableBgColor[0], $tableBgColor[1], $tableBgColor[2]);
        
        // Game number (based on suggested_play_order)
        $gameNumber = 'G' . ($schedule->suggested_play_order ?? ($index + 1));
        $pdf->Cell($gameWidth, $rowHeight, $gameNumber, 1, 0, 'C', true);
        
        // Team 1
        $pdf->Cell($teamWidth, $rowHeight, $schedule->team1_name, 1, 0, 'L', true);
        
        // VS text in red
        $pdf->SetTextColor($vsColor[0], $vsColor[1], $vsColor[2]); // Red
        $pdf->Cell($vsWidth, $rowHeight, 'vs', 1, 0, 'C', true);
        $pdf->SetTextColor(0, 0, 0); // Back to black
        
        // Team 2
        $pdf->Cell($teamWidth, $rowHeight, $schedule->team2_name, 1, 0, 'L', true);
        
        // Venue
        $pdf->Cell($venueWidth, $rowHeight, $schedule->venue ?? '', 1, 1, 'C', true);
    }
    
    // Add a note about the format at the bottom of the page
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
    
    // Use a simpler naming convention for the PDF file
    $filename = 'schedule_' . $event_id . '.pdf';
    $storagePath = 'schedule_pdfs/' . $filename;
    
    // Log for debugging
    \Log::info('Generating schedule PDF', [
        'event_id' => $event_id,
        'filename' => $filename,
        'storage_path' => $storagePath
    ]);
    
    // Check if a previous PDF exists for this event and delete it
    if (Storage::disk('public')->exists($storagePath)) {
        Storage::disk('public')->delete($storagePath);
        \Log::info('Replaced existing schedule PDF', [
            'event_id' => $event_id,
            'path' => $storagePath
        ]);
    }
    
    // Ensure directory exists
    Storage::makeDirectory('public/schedule_pdfs');
    
    // Save PDF to storage - note TCPDF's output method is different
    Storage::put('public/' . $storagePath, $pdf->Output('', 'S'));
    
    // Return file for download
    return response()->download(
        storage_path('app/public/' . $storagePath),
        $filename,
        ['Content-Type' => 'application/pdf']
    );
}

    /**
     * Delete a schedule PDF for an event
     * 
     * @param Request $request
     * @param string $intrams_id
     * @param string $event_id
     * @return \Illuminate\Http\Response
     */
    public function deleteSchedulePDF(Request $request, string $intrams_id, string $event_id)
    {
        // Verify event exists
        $event = Event::where('id', $event_id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();
        
        // Use the same simple filename pattern as in the generate method
        $filename = 'schedule_' . $event_id . '.pdf';
        $storagePath = 'schedule_pdfs/' . $filename;
        
        // Log for debugging
        \Log::debug('Attempting to delete schedule PDF', [
            'event_id' => $event_id,
            'storage_path' => $storagePath,
            'exists' => Storage::disk('public')->exists($storagePath)
        ]);
        
        // Check if PDF exists
        if (!Storage::disk('public')->exists($storagePath)) {
            return response()->json([
                'message' => 'No schedule PDF found for this event'
            ], 404);
        }
        
        // Delete the file
        Storage::disk('public')->delete($storagePath);
        \Log::info('Deleted schedule PDF for event', [
            'event_id' => $event_id,
            'event_name' => $event->name
        ]);
        
        // Return success response
        return response()->json([
            'message' => 'Schedule PDF for event "' . $event->name . '" successfully deleted'
        ], 200);
    }

}