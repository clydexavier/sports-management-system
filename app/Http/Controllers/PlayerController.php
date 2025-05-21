<?php

namespace App\Http\Controllers;

use App\Models\IntramuralGame;
use App\Models\Player;
use App\Models\Event;

use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Http\Requests\PlayerRequests\StorePlayerRequest;
use App\Http\Requests\PlayerRequests\UpdatePlayerRequest;
use App\Http\Requests\PlayerRequests\ShowPlayerRequest;
use App\Http\Requests\PlayerRequests\DestroyPlayerRequest;
use Cloudinary\Cloudinary;

class PlayerController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        // Initialize Cloudinary with the CLOUDINARY_URL from .env
        $this->cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
    }
    
    public function index(Request $request, string $intrams_id, string $event_id)
    {
        \Log::info('Incoming data: ', $request->all());
        $perPage = 12;
        
        $search = $request->query('search');
        $team_id = $request->query('activeTab');
        
        // Get the current event
        $event = Event::findOrFail($event_id);
        
        // Initialize the query based on event hierarchy
        if ($event->parent_id) {
            // This is a subevent, find all sibling subevents (including this one)
            $parentId = $event->parent_id;
            
            // Get all event IDs that share the same parent
            $siblingEventIds = Event::where('parent_id', $parentId)
                ->pluck('id')
                ->toArray();
            
            \Log::info('Subevent detected, fetching players from all subevents under parent: ' . $parentId);
            \Log::info('Sibling event IDs: ', $siblingEventIds);
            
            // Query players from all sibling events
            $query = Player::whereIn('event_id', $siblingEventIds)
                ->where('intrams_id', $intrams_id);
        } 
        else if (Event::where('parent_id', $event_id)->exists()) {
            // This is a parent event, get players from all its subevents
            $childEventIds = Event::where('parent_id', $event_id)
                ->pluck('id')
                ->toArray();
                
            \Log::info('Parent event detected, fetching players from all subevents: ', $childEventIds);
            
            // Query players from all child events
            $query = Player::whereIn('event_id', $childEventIds)
                ->where('intrams_id', $intrams_id);
        }
        else {
            // Regular event (not parent or child), get players directly assigned to it
            $query = Player::where('event_id', $event_id)
                ->where('intrams_id', $intrams_id);
        }

        // Apply filters
        if ($team_id && $team_id !== 'All') {
            $query->where('team_id', $team_id);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $players = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $playersData = $players->items();
        
        // Process player data - no need to modify URLs since Cloudinary returns full URLs
        
        return response()->json([
            'data' => $playersData,
            'meta' => [
                'current_page' => $players->currentPage(),
                'per_page' => $players->perPage(),
                'total' => $players->total(),
                'last_page' => $players->lastPage(),
            ]
        ], 200);
    }

    public function store(StorePlayerRequest $request)
    {
        \Log::info('Incoming data:', $request->all());

        $validated = $request->validated();

        if ($request->hasFile('picture')) {
            // Upload file to Cloudinary
            $uploadedFile = $request->file('picture');
            $result = $this->cloudinary->uploadApi()->upload(
                $uploadedFile->getRealPath(),
                ['folder' => 'player_pictures']
            );

            // Store the Cloudinary URL
            $validated['picture'] = $result['secure_url'];
            
            // Store the public_id for deletion later
            $validated['picture_public_id'] = $result['public_id'];
        }

        // Set initial document statuses
        $validated['medical_certificate_status'] = 'pending';
        $validated['parents_consent_status'] = 'pending';
        $validated['cor_status'] = 'pending';
        
        // Set initial approval status
        $validated['approval_status'] = 'pending';
        $validated['is_varsity'] = false;

        $event = Event::find($validated['event_id']);
        if ($event) {
            $eventType = $event->category;
            $eventName = $event->name;
            $validated['sport'] = $eventType . ' ' . $eventName;
        } else {
            $validated['sport'] = null;
        }

        $player = Player::create($validated);

        return response()->json($player, 201);
    }

    public function update(UpdatePlayerRequest $request)
    {
        \Log::info('Incoming data:', $request->all());

        $validated = $request->validated();
        \Log::info('Validated data:', $validated);

        $player = Player::where('id', $validated['id'])->firstOrFail();

        $remove_picture = $request->input('remove_picture', false);

        if ($request->has('id_number')) {
            if ($player->id_number !== $validated['id_number']) {
                unset($validated['id_number']);
            }
        }

        // Handle picture removal if requested
        if ($remove_picture && $player->picture) {
            // Delete from Cloudinary if public_id exists
            if ($player->picture_public_id) {
                $this->cloudinary->uploadApi()->destroy($player->picture_public_id);
            }
            
            // Set both fields to null
            $validated['picture'] = null;
            $validated['picture_public_id'] = null;
            
            \Log::info('Removing picture for player: ' . $player->id);
        }
        // Handle new picture upload
        elseif ($request->hasFile('picture')) {
            // Delete old image if exists
            if ($player->picture_public_id) {
                $this->cloudinary->uploadApi()->destroy($player->picture_public_id);
            }
            
            // Upload new image
            $uploadedFile = $request->file('picture');
            $result = $this->cloudinary->uploadApi()->upload(
                $uploadedFile->getRealPath(),
                ['folder' => 'player_pictures']
            );

            // Store the Cloudinary URL and public_id
            $validated['picture'] = $result['secure_url'];
            $validated['picture_public_id'] = $result['public_id'];
        }

        $player->update($validated);

        return response()->json([
            'message' => 'Player updated successfully',
            'player' => $player
        ], 200);
    }
    
    // Method to update document status individually with automatic approval handling
    public function updateDocumentStatus(Request $request, string $intrams_id, string $event_id, string $player_id)
    {
        $player = Player::findOrFail($player_id);
        
        $validated = $request->validate([
            'document_type' => 'required|in:medical_certificate,parents_consent,cor',
            'status' => 'required|in:valid,invalid,pending',
        ]);
        
        $statusField = $validated['document_type'] . '_status';
        $player->$statusField = $validated['status'];
        
        // If player is currently rejected, keep that status unless manually cleared
        if ($player->approval_status !== 'rejected') {
            // Update approval status based on document statuses
            $this->updatePlayerApprovalStatus($player);
        }
        
        $player->save();
        
        return response()->json([
            'message' => 'Document status updated successfully',
            'player' => $player
        ], 200);
    }
    
    // Method to manually reject a player
    public function rejectPlayer(Request $request, string $intrams_id, string $event_id, string $player_id)
    {
        $player = Player::findOrFail($player_id);
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:3',
        ]);
        
        $player->approval_status = 'rejected';
        $player->rejection_reason = $validated['rejection_reason'];
        $player->save();
        
        return response()->json([
            'message' => 'Player successfully rejected',
            'player' => $player
        ], 200);
    }
    
    // Method to clear rejection status (sets back to pending or approved based on documents)
    public function clearRejection(Request $request, string $intrams_id, string $event_id, string $player_id)
    {
        $player = Player::findOrFail($player_id);
        
        // Update approval status based on document statuses
        $this->updatePlayerApprovalStatus($player);
        
        // Clear rejection reason
        $player->rejection_reason = null;
        $player->save();
        
        return response()->json([
            'message' => 'Rejection status cleared successfully',
            'player' => $player
        ], 200);
    }
    
    // Helper method to automatically determine approval status based on document statuses
    private function updatePlayerApprovalStatus(Player $player)
    {
        // If all documents are valid, automatically approve
        if ($player->medical_certificate_status === 'valid' && 
            $player->parents_consent_status === 'valid' && 
            $player->cor_status === 'valid') {
            $player->approval_status = 'approved';
        } 
        // Otherwise, set to pending
        else {
            $player->approval_status = 'pending';
        }
        
        return $player;
    }

    public function destroy(DestroyPlayerRequest $request)
    {
        \Log::info('Incoming data:', $request->all());

        $validated = $request->validated();
        $player = Player::where('id', $validated['id'])->firstOrFail();

        // Delete picture from Cloudinary if it exists
        if ($player->picture_public_id) {
            $this->cloudinary->uploadApi()->destroy($player->picture_public_id);
        }

        $player->delete();

        return response()->json(['message' => 'Player deleted successfully'], 200);
    }
}