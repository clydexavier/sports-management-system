<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;


use App\Http\Requests\ScheduleRequests\StoreScheduleRequest;
use App\Http\Requests\ScheduleRequests\ShowScheduleRequest;
use App\Http\Requests\ScheduleRequests\UpdateScheduleRequest;
use App\Http\Requests\ScheduleRequests\DestroyScheduleRequest;



class ScheduleController extends Controller
{
    //
    public function index(Request $request, $intrams_id, $event_id) 
    {
        $scheds = Schedule::where('intrams_id', $intrams_id)->where('event_id', $event_id)->get();
        return response()->json($scheds, 200);
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


}