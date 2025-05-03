<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PodiumRequests\StorePodiumRequest;
use App\Http\Requests\PodiumRequests\ShowPodiumRequest;
use App\Http\Requests\PodiumRequests\UpdatePodiumRequest;
use App\Http\Requests\PodiumRequests\DestroyPodiumRequest;

use App\Models\Podium;


class PodiumController extends Controller
{
    //
    public function index(Request $request, string $intrams_id)
    {
        $perPage = 12;

        $type = $request->query('type');
        $search = $request->query('search');

        $query = Podium::where('intrams_id', $intrams_id);

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $podiums = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $podiums->items(),
            'meta' => [
                'current_page' => $podiums->currentPage(),
                'per_page' => $podiums->perPage(),
                'total' => $podiums->total(),
                'last_page' => $podiums->lastPage(),
            ]
        ], 200);

    }
    
    public function store(StorePodiumRequest $request) 
    {
        $validated = $request->validated();
        $podium = Podium::create($validated);
        
        return response()->json($podium, 201);
    }

    public function show(ShowPodiumRequest $request)
    {   
        $validated = $request->validated();
        $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();

        return response->json($podium, 200);
    }

    public function update(UpdatePodiumRequest $request)
    {
        $validated = $request->validated();
        $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();
        $podium->update($validated);
        return response()->json($podium, 200);
    }

    public function destroy(DestroyPodiumRequest $request)
    {
        $validated = $request->validated();
        $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();
        $podium->delete();

        return response()->json(204);
    }
}