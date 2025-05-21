<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\OverallTeam;
use App\Models\Player;
use App\Models\Gallery;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Http;

class TeamGalleryController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        // Initialize Cloudinary with the CLOUDINARY_URL from .env
        $this->cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
    }

    public function index(Request $request, string $intrams_id, string $event_id)
    {
        // First verify that the event exists and belongs to the specified intrams
        $event = Event::where('id', $event_id)
                    ->where('intrams_id', $intrams_id)
                    ->first();
        
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }
        
        // Determine which event IDs to look for based on event hierarchy
        $eventIds = [$event_id]; // Start with current event
        
        if ($event->parent_id) {
            // This is a subevent - find all sibling events with same parent
            $siblingEventIds = Event::where('parent_id', $event->parent_id)
                ->pluck('id')
                ->toArray();
            
            // Add the parent ID as well to include any galleries directly attached to parent
            $eventIds = array_merge($siblingEventIds, [$event->parent_id]);
            
            \Log::info('Subevent detected - fetching galleries from all related events', [
                'current_event' => $event_id,
                'parent_event' => $event->parent_id,
                'all_event_ids' => $eventIds
            ]);
        } 
        else if (Event::where('parent_id', $event_id)->exists()) {
            // This is a parent event - find all its subevents
            $childEventIds = Event::where('parent_id', $event_id)
                ->pluck('id')
                ->toArray();
            
            $eventIds = array_merge($eventIds, $childEventIds);
            
            \Log::info('Parent event detected - fetching galleries from parent and all subevents', [
                'parent_event' => $event_id,
                'all_event_ids' => $eventIds
            ]);
        }
        
        // Get all galleries for these events, with team information
        $galleries = Gallery::whereIn('event_id', $eventIds)
                            ->with(['team', 'event'])
                            ->get();
        
        // Group galleries by team with full file URLs
        $galleryByTeam = $galleries->groupBy('team_id')
                                ->map(function($teamGalleries) use ($event) {
                                    $team = $teamGalleries->first()->team;
                                    return [
                                        'team_id' => $team->id,
                                        'team_name' => $team->name,
                                        'team_logo_url' => $team->team_logo_path, // Cloudinary URLs are already absolute
                                        'galleries' => $teamGalleries->map(function($gallery) use ($team, $event) {
                                            // Generate a clean display name for downloads
                                            $eventName = $gallery->event ? $gallery->event->name : 'Event';
                                            $teamName = $team->name;
                                            $displayName = str_replace(' ', '_', "Team_Gallery_{$teamName}_{$eventName}.docx");
                                            
                                            // Ensure we're using secure URL
                                            $secureUrl = $gallery->file_path;
                                            
                                            // Create a proper download URL with query parameters
                                            $downloadUrl = $secureUrl;
                                            // Add fl_attachment as a query parameter
                                            if (strpos($downloadUrl, '?') === false) {
                                                $downloadUrl .= '?fl_attachment=true';
                                            } else {
                                                $downloadUrl .= '&fl_attachment=true';
                                            }
                                            // Add an explicit Content-Disposition header via Cloudinary parameters
                                            $downloadUrl .= '&response-content-disposition=attachment;filename=' . urlencode($displayName);
                                            
                                            return [
                                                'id' => $gallery->id,
                                                'file_url' => $secureUrl, // Cloudinary secure URL for viewing
                                                'download_url' => $downloadUrl, // URL optimized for downloading
                                                'secure_url' => $secureUrl, // Explicitly include secure_url
                                                'public_id' => $gallery->cloudinary_public_id, // Include public_id for reference
                                                'display_name' => $displayName, // A clean name for download
                                                'created_at' => $gallery->created_at
                                            ];
                                        })
                                    ];
                                })
                                ->values();
        
        return response()->json([
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'galleries_by_team' => $galleryByTeam
        ]);
    }

    /**
     * Get Form 2 data for a specific team and event
     */
    public function getForm2Data(Request $request, string $intrams_id, string $event_id)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:overall_teams,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $teamId = $request->input('team_id');
    
        // Get team details
        $team = OverallTeam::findOrFail($teamId);
    
        // Get event details using the route parameter
        $event = Event::where('id', $event_id)
                      ->where('intrams_id', $intrams_id)
                      ->firstOrFail();
        
        // Get parent event name if this is a subevent
        $eventName = $event->name;
        $parentEvent = null;
        
        if ($event->parent_id) {
            // This is a subevent, fetch the parent event
            $parentEvent = Event::find($event->parent_id);
            if ($parentEvent) {
                // Use parent event name instead
                $eventName = $parentEvent->name;
            }
        }
    
        // Determine related event IDs (current event and sibling subevents)
        $eventIds = [$event_id];
        
        if ($event->parent_id) {
            // This is a subevent, get all siblings
            $siblingEventIds = Event::where('parent_id', $event->parent_id)
                ->pluck('id')
                ->toArray();
            $eventIds = $siblingEventIds;
        } 
        else if (Event::where('parent_id', $event_id)->exists()) {
            // This is a parent event, get all children
            $childEventIds = Event::where('parent_id', $event_id)
                ->pluck('id')
                ->toArray();
            $eventIds = array_merge($eventIds, $childEventIds);
        }
    
        // Get players for this team across all related events
        $players = Player::where('team_id', $teamId)
            ->whereIn('event_id', $eventIds)
            ->where('approval_status', 'approved')
            ->get();
    
        // Get coaches across all related events
        $coach = Player::where('team_id', $teamId)
            ->whereIn('event_id', $eventIds)
            ->where('role', 'coach')
            ->first();
    
        $assistantCoach = Player::where('team_id', $teamId)
            ->whereIn('event_id', $eventIds)
            ->where('role', 'assistant_coach')
            ->first();
    
        $generalManager = Player::where('team_id', $teamId)
            ->whereIn('event_id', $eventIds)
            ->where('role', 'general_manager')
            ->first();
    
        // Prepare response data
        $formData = [
            'event_name' => $eventName, // Using parent event name if this is a subevent
            'event_date' => '2024-10-22 to 2024-10-25',
            'screening_date' => Carbon::now()->format('Y-m-d'),
            'team_name' => $team->name,
            'team_category' => $event->category ?? '',
            'team_logo' => $team->team_logo_path ?? null,
            'players' => $players->map(function ($player) {
                return [
                    'name' => $player->name,
                    'date_of_birth' => $player->birthdate,
                    'course_year' => $player->course_year,
                    'contact_no' => $player->contact,
                    'picture' => $player->picture ?? null,
                    'is_varsity' => $player->is_varsity,
                ];
            }),
            'coach' => $coach ? [
                'name' => $coach->name,
                'date_of_birth' => $coach->birthdate,
                'course_year' => $coach->course_year,
                'contact_no' => $coach->contact,
                'picture' => $coach->picture ?? null,
            ] : null,
            'assistant_coach' => $assistantCoach ? [
                'name' => $assistantCoach->name,
                'date_of_birth' => $assistantCoach->birthdate,
                'course_year' => $assistantCoach->course_year,
                'contact_no' => $assistantCoach->contact,
                'picture' => $assistantCoach->picture ?? null,
            ] : null,
            'general_manager' => $generalManager ? [
                'name' => $generalManager->name,
                'date_of_birth' => $generalManager->birthdate,
                'course_year' => $generalManager->course_year,
                'contact_no' => $generalManager->contact,
                'picture' => $generalManager->picture ?? null,
            ] : null,
        ];
    
        return response()->json($formData);
    }
    
    /**
     * Generate a gallery for Form 2
     */
    public function generateGallery(Request $request, string $intrams_id, string $event_id)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:overall_teams,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $teamId = $request->input('team_id');

        // Verify event exists and belongs to intrams
        $event = Event::where('id', $event_id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();

        // Determine which event ID to associate the gallery with
        // If this is a subevent, associate with parent instead
        $galleryEventId = $event_id;
        if ($event->parent_id) {
            $galleryEventId = $event->parent_id;
            \Log::info('Subevent detected - associating gallery with parent event', [
                'subevent_id' => $event_id,
                'parent_id' => $galleryEventId
            ]);
        }

        // Fetch data using the existing method
        $formData = $this->getForm2Data($request, $intrams_id, $event_id);
        $data = $formData->getData(); // Get the data from the JsonResponse

        // Get team for outputPath
        $team = OverallTeam::findOrFail($teamId);

        // Count the number of players
        $playerCount = isset($data->players) && is_array($data->players) ? count($data->players) : 0;
        
        // Choose the appropriate template based on player count and staff presence
        if ($playerCount <= 12 && !isset($data->coach) && !isset($data->assistant_coach) && !isset($data->general_manager)) {
            // Use single page template
            $templatePath = public_path('Form_2_Gallery_Template_Single_Page.docx');
        } else {
            // Use the regular two-page template
            $templatePath = public_path('Form_2_Gallery_Template_With_Placeholders.docx');
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // Replace basic placeholders
        $templateProcessor->setValue('event.name', $data->event_name);
        $templateProcessor->setValue('event.category', $data->team_category);
        $templateProcessor->setValue('team.name', $data->team_name);

        // Define maximum players for each template
        $maxPlayers = ($templatePath == public_path('Form_2_Gallery_Template_Single_Page.docx')) ? 12 : 21;
        
        // Helper function to download image from Cloudinary URL
        $downloadImage = function($imageUrl) {
            if (empty($imageUrl)) return null;
            
            try {
                $response = Http::get($imageUrl);
                
                if ($response->successful()) {
                    $tempDir = sys_get_temp_dir();
                    $tempFile = $tempDir . '/' . uniqid() . '.jpg';
                    file_put_contents($tempFile, $response->body());
                    return $tempFile;
                }
            } catch (\Exception $e) {
                \Log::error('Error downloading image: ' . $e->getMessage());
            }
            
            return null;
        };

        // Process players based on the selected template
        if (isset($data->players) && is_array($data->players)) {
            // Fill in player data for as many players as we have (up to template max)
            for ($i = 0; $i < min($playerCount, $maxPlayers); $i++) {
                $playerNumber = $i + 1;
                $templateProcessor->setValue("player{$playerNumber}.name", $data->players[$i]->name ?? '');
                $templateProcessor->setValue("player{$playerNumber}.birthdate", $data->players[$i]->date_of_birth ?? '');
                $templateProcessor->setValue("player{$playerNumber}.course", $data->players[$i]->course_year ?? '');
                $templateProcessor->setValue("player{$playerNumber}.contact", $data->players[$i]->contact_no ?? '');
                
                // Handle player pictures from Cloudinary URL
                if (isset($data->players[$i]->picture) && !empty($data->players[$i]->picture)) {
                    $tempPicturePath = $downloadImage($data->players[$i]->picture);
                    
                    if ($tempPicturePath) {
                        $templateProcessor->setImageValue("player{$playerNumber}.picture", [
                            'path' => $tempPicturePath,
                            'width' => 140,
                            'height' => 140,
                            'ratio' => false
                        ]);
                        
                        // Register cleanup for temp file
                        register_shutdown_function(function() use ($tempPicturePath) {
                            if (file_exists($tempPicturePath)) {
                                unlink($tempPicturePath);
                            }
                        });
                    } else {
                        $templateProcessor->setValue("player{$playerNumber}.picture", '');
                    }
                } else {
                    $templateProcessor->setValue("player{$playerNumber}.picture", '');
                }
            }
            
            // Clear unused player placeholders
            for ($i = $playerCount + 1; $i <= $maxPlayers; $i++) {
                $templateProcessor->setValue("player{$i}.name", '');
                $templateProcessor->setValue("player{$i}.birthdate", '');
                $templateProcessor->setValue("player{$i}.course", '');
                $templateProcessor->setValue("player{$i}.contact", '');
                $templateProcessor->setValue("player{$i}.picture", '');
            }
        }

        // Only process staff members if using the full template
        if ($templatePath == public_path('Form_2_Gallery_Template_With_Placeholders.docx')) {
            // Handle coach (player22)
            if (isset($data->coach)) {
                $templateProcessor->setValue("player22.name", $data->coach->name ?? '');
                $templateProcessor->setValue("player22.birthdate", $data->coach->date_of_birth ?? '');
                $templateProcessor->setValue("player22.course", $data->coach->course_year ?? '');
                $templateProcessor->setValue("player22.contact", $data->coach->contact_no ?? '');
                
                // Handle coach picture from Cloudinary URL
                if (isset($data->coach->picture) && !empty($data->coach->picture)) {
                    $tempPicturePath = $downloadImage($data->coach->picture);
                    
                    if ($tempPicturePath) {
                        $templateProcessor->setImageValue("player22.picture", [
                            'path' => $tempPicturePath,
                            'width' => 100,
                            'height' => 120,
                            'ratio' => false
                        ]);
                        
                        // Register cleanup for temp file
                        register_shutdown_function(function() use ($tempPicturePath) {
                            if (file_exists($tempPicturePath)) {
                                unlink($tempPicturePath);
                            }
                        });
                    } else {
                        $templateProcessor->setValue("player22.picture", '');
                    }
                } else {
                    $templateProcessor->setValue("player22.picture", '');
                }
            } else {
                // Clear coach placeholders
                $templateProcessor->setValue("player22.name", '');
                $templateProcessor->setValue("player22.birthdate", '');
                $templateProcessor->setValue("player22.course", '');
                $templateProcessor->setValue("player22.contact", '');
                $templateProcessor->setValue("player22.picture", '');
            }

            // Handle assistant coach (player23)
            if (isset($data->assistant_coach)) {
                $templateProcessor->setValue("player23.name", $data->assistant_coach->name ?? '');
                $templateProcessor->setValue("player23.birthdate", $data->assistant_coach->date_of_birth ?? '');
                $templateProcessor->setValue("player23.course", $data->assistant_coach->course_year ?? '');
                $templateProcessor->setValue("player23.contact", $data->assistant_coach->contact_no ?? '');
                
                // Handle assistant coach picture from Cloudinary URL
                if (isset($data->assistant_coach->picture) && !empty($data->assistant_coach->picture)) {
                    $tempPicturePath = $downloadImage($data->assistant_coach->picture);
                    
                    if ($tempPicturePath) {
                        $templateProcessor->setImageValue("player23.picture", [
                            'path' => $tempPicturePath,
                            'width' => 100,
                            'height' => 120,
                            'ratio' => false
                        ]);
                        
                        // Register cleanup for temp file
                        register_shutdown_function(function() use ($tempPicturePath) {
                            if (file_exists($tempPicturePath)) {
                                unlink($tempPicturePath);
                            }
                        });
                    } else {
                        $templateProcessor->setValue("player23.picture", '');
                    }
                } else {
                    $templateProcessor->setValue("player23.picture", '');
                }
            } else {
                // Clear assistant coach placeholders
                $templateProcessor->setValue("player23.name", '');
                $templateProcessor->setValue("player23.birthdate", '');
                $templateProcessor->setValue("player23.course", '');
                $templateProcessor->setValue("player23.contact", '');
                $templateProcessor->setValue("player23.picture", '');
            }

            // Handle general manager (player24)
            if (isset($data->general_manager)) {
                $templateProcessor->setValue("player24.name", $data->general_manager->name ?? '');
                $templateProcessor->setValue("player24.birthdate", $data->general_manager->date_of_birth ?? '');
                $templateProcessor->setValue("player24.course", $data->general_manager->course_year ?? '');
                $templateProcessor->setValue("player24.contact", $data->general_manager->contact_no ?? '');
                
                // Handle general manager picture from Cloudinary URL
                if (isset($data->general_manager->picture) && !empty($data->general_manager->picture)) {
                    $tempPicturePath = $downloadImage($data->general_manager->picture);
                    
                    if ($tempPicturePath) {
                        $templateProcessor->setImageValue("player24.picture", [
                            'path' => $tempPicturePath,
                            'width' => 100,
                            'height' => 120,
                            'ratio' => false
                        ]);
                        
                        // Register cleanup for temp file
                        register_shutdown_function(function() use ($tempPicturePath) {
                            if (file_exists($tempPicturePath)) {
                                unlink($tempPicturePath);
                            }
                        });
                    } else {
                        $templateProcessor->setValue("player24.picture", '');
                    }
                } else {
                    $templateProcessor->setValue("player24.picture", '');
                }
            } else {
                // Clear general manager placeholders
                $templateProcessor->setValue("player24.name", '');
                $templateProcessor->setValue("player24.birthdate", '');
                $templateProcessor->setValue("player24.course", '');
                $templateProcessor->setValue("player24.contact", '');
                $templateProcessor->setValue("player24.picture", '');
            }
        }

        // Generate unique filename with clear pattern
        $publicId = "gallery_" . $teamId . "_" . $event_id . "_" . uniqid();
        $filename = $publicId . ".docx";
        
        // Save document to temp file first
        $tempFilePath = sys_get_temp_dir() . '/' . $filename;
        $templateProcessor->saveAs($tempFilePath);
        
        // Read the file content
        $docxContent = file_get_contents($tempFilePath);
        
        // Upload to Cloudinary
        $result = $this->cloudinary->uploadApi()->upload(
            "data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;base64," . base64_encode($docxContent),
            [
                'folder' => 'galleries',
                'public_id' => $publicId,
                'resource_type' => 'raw'
            ]
        );
        
        // Clean up temp file
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
        
        $cloudinaryUrl = $result['secure_url'];
        $cloudinaryPublicId = 'galleries/' . $publicId;
        
        // Create Gallery record in the database
        $gallery = Gallery::create([
            'event_id' => $galleryEventId,
            'team_id' => $teamId,
            'file_path' => $cloudinaryUrl,
            'cloudinary_public_id' => $cloudinaryPublicId
        ]);
        
        // Return a JSON response so the frontend can continue without navigation
        return response()->json([
            'message' => 'Gallery generated successfully',
            'gallery' => [
                'id' => $gallery->id,
                'file_url' => $cloudinaryUrl,
                'file_path' => $cloudinaryUrl,
                'created_at' => $gallery->created_at,
                'team_id' => $teamId
            ]
        ]);
    }

    /**
     * Submit players for an event
     */
    public function submitPlayers(Request $request, string $intrams_id, string $event_id)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:overall_teams,id',
            'players' => 'required|array',
            'players.*.name' => 'required|string',
            'players.*.id_number' => 'required|string',
            'players.*.birthdate' => 'required|date',
            'players.*.course_year' => 'required|string',
            'players.*.contact' => 'required|string',
            'players.*.is_varsity' => 'boolean',
            'players.*.picture' => 'sometimes|image|max:2048',
            'players.*.medical_certificate' => 'sometimes|file|max:5120',
            'players.*.parents_consent' => 'sometimes|file|max:5120',
            'players.*.cor' => 'sometimes|file|max:5120',
            'players.*.role' => 'sometimes|string|in:player,coach,assistant_coach,general_manager',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify event exists and belongs to intrams
        $event = Event::where('id', $event_id)
                     ->where('intrams_id', $intrams_id)
                     ->firstOrFail();

        $teamId = $request->input('team_id');
        $playersData = $request->input('players');

        $savedPlayers = [];

        foreach ($playersData as $index => $playerData) {
            // Handle file uploads with Cloudinary
            $picturePath = null;
            $picturePublicId = null;
            $medicalCertPath = null;
            $medicalCertPublicId = null;
            $parentsConsentPath = null;
            $parentsConsentPublicId = null;
            $corPath = null;
            $corPublicId = null;

            if ($request->hasFile('players.' . $index . '.picture')) {
                $uploadedFile = $request->file('players.' . $index . '.picture');
                $publicId = 'player_picture_' . $teamId . '_' . uniqid();
                $result = $this->cloudinary->uploadApi()->upload(
                    $uploadedFile->getRealPath(),
                    [
                        'folder' => 'player_pictures',
                        'public_id' => $publicId
                    ]
                );
                $picturePath = $result['secure_url'];
                $picturePublicId = $result['public_id'];
            }

            if ($request->hasFile('players.' . $index . '.medical_certificate')) {
                $uploadedFile = $request->file('players.' . $index . '.medical_certificate');
                $publicId = 'med_cert_' . $teamId . '_' . uniqid();
                $result = $this->cloudinary->uploadApi()->upload(
                    $uploadedFile->getRealPath(),
                    [
                        'folder' => 'medical_certificates',
                        'public_id' => $publicId
                    ]
                );
                $medicalCertPath = $result['secure_url'];
                $medicalCertPublicId = $result['public_id'];
            }

            if ($request->hasFile('players.' . $index . '.parents_consent')) {
                $uploadedFile = $request->file('players.' . $index . '.parents_consent');
                $publicId = 'consent_' . $teamId . '_' . uniqid();
                $result = $this->cloudinary->uploadApi()->upload(
                    $uploadedFile->getRealPath(),
                    [
                        'folder' => 'parents_consents',
                        'public_id' => $publicId
                    ]
                );
                $parentsConsentPath = $result['secure_url'];
                $parentsConsentPublicId = $result['public_id'];
            }

            if ($request->hasFile('players.' . $index . '.cor')) {
                $uploadedFile = $request->file('players.' . $index . '.cor');
                $publicId = 'cor_' . $teamId . '_' . uniqid();
                $result = $this->cloudinary->uploadApi()->upload(
                    $uploadedFile->getRealPath(),
                    [
                        'folder' => 'cors',
                        'public_id' => $publicId
                    ]
                );
                $corPath = $result['secure_url'];
                $corPublicId = $result['public_id'];
            }

            // Create or update player
            $player = Player::updateOrCreate(
                [
                    'id_number' => $playerData['id_number'],
                    'event_id' => $event_id,
                    'team_id' => $teamId
                ],
                [
                    'name' => $playerData['name'],
                    'birthdate' => $playerData['birthdate'],
                    'course_year' => $playerData['course_year'],
                    'contact' => $playerData['contact'],
                    'is_varsity' => $playerData['is_varsity'] ?? false,
                    'intrams_id' => $intrams_id,
                    'role' => $playerData['role'] ?? 'player',
                    'approved' => false,
                ]
            );

            // Update file paths if files were uploaded
            $updateData = [];

            if ($picturePath) {
                $updateData['picture'] = $picturePath;
                // You should add cloudinary_picture_id field to your Player model if needed
            }

            if ($medicalCertPath) {
                $updateData['medical_certificate'] = $medicalCertPath;
                // You should add cloudinary_medical_certificate_id field to your Player model if needed
            }

            if ($parentsConsentPath) {
                $updateData['parents_consent'] = $parentsConsentPath;
                // You should add cloudinary_parents_consent_id field to your Player model if needed
            }

            if ($corPath) {
                $updateData['cor'] = $corPath;
                // You should add cloudinary_cor_id field to your Player model if needed
            }

            if (!empty($updateData)) {
                $player->update($updateData);
            }

            $savedPlayers[] = $player;
        }

        return response()->json([
            'message' => 'Players submitted successfully',
            'players' => $savedPlayers
        ], 201);
    }

        /**
 * Download gallery file with proper headers
 */
public function downloadGallery(string $intrams_id, string $event_id, string $id)
{
    // Find the gallery
    $gallery = Gallery::findOrFail($id);
    
    // Create a temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
    
    // Get document content from Cloudinary and save to temp file
    $response = Http::get($gallery->file_path);
    
    if (!$response->successful()) {
        return response()->json(['error' => 'Unable to download file'], 500);
    }
    
    // Write binary content to temp file
    file_put_contents($tempFile, $response->body());
    
    // Create a meaningful filename
    $event = $gallery->event ? $gallery->event->name : 'Event';
    $team = $gallery->team ? $gallery->team->name : 'Team';
    $filename = str_replace(' ', '_', "Team_Gallery_{$team}_{$event}.docx");
    
    // Return the file for download and delete it after sending
    return response()->download($tempFile, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ])->deleteFileAfterSend(true);
}
    /**
     * Delete a gallery item
     */
    public function destroy(Request $request, string $intrams_id, string $event_id, string $id) 
    {
        // Verify event exists and belongs to intrams
        $event = Event::where('id', $event_id)
                     ->where('intrams_id', $intrams_id)
                     ->firstOrFail();
        
        // Determine related event IDs to allow deleting galleries from parent/siblings
        $eventIds = [$event_id];
        
        if ($event->parent_id) {
            // This is a subevent - include parent and siblings
            $siblingEventIds = Event::where('parent_id', $event->parent_id)
                ->pluck('id')
                ->toArray();
            $eventIds = array_merge($siblingEventIds, [$event->parent_id]);
        } 
        else if (Event::where('parent_id', $event_id)->exists()) {
            // This is a parent event - include all children
            $childEventIds = Event::where('parent_id', $event_id)
                ->pluck('id')
                ->toArray();
            $eventIds = array_merge($eventIds, $childEventIds);
        }
        
        // Validate the gallery belongs to any of the related events
        $gallery = Gallery::where('id', $id)
            ->whereIn('event_id', $eventIds)
            ->firstOrFail();

        // Delete from Cloudinary 
        if ($gallery->cloudinary_public_id) {
            try {
                // Use the stored cloudinary_public_id for more reliable deletion
                $this->cloudinary->uploadApi()->destroy($gallery->cloudinary_public_id, ['resource_type' => 'raw']);
                \Log::info('Deleted file from Cloudinary', ['public_id' => $gallery->cloudinary_public_id]);
            } catch (\Exception $e) {
                \Log::error('Error deleting file from Cloudinary: ' . $e->getMessage(), [
                    'public_id' => $gallery->cloudinary_public_id
                ]);
                // Continue with deletion from database even if Cloudinary deletion fails
            }
        } 
        // Fallback to URL extraction if needed (for older records)
        else if ($gallery->file_path) {
            try {
                // Extract the public ID from the URL
                // URL format: https://res.cloudinary.com/cloud_name/raw/upload/v123456789/galleries/gallery_id.docx
                $pattern = '/\/v\d+\/(.+)\.[^\.]+$/';
                if (preg_match($pattern, $gallery->file_path, $matches)) {
                    $publicId = $matches[1]; // folder/filename without extension
                    $this->cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'raw']);
                    \Log::info('Deleted file from Cloudinary using URL extraction:', ['public_id' => $publicId]);
                } else {
                    \Log::warning('Could not extract public_id from URL: ' . $gallery->file_path);
                }
            } catch (\Exception $e) {
                \Log::error('Error deleting file from Cloudinary: ' . $e->getMessage());
            }
        }

        // Delete the gallery record from the database
        $gallery->delete();
    
        return response()->json([
            'message' => 'Gallery deleted successfully'
        ], 200);
    }
}