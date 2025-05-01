<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;


use App\Http\Requests\ScheduleRequests\StoreScheduleRequest;
use App\Http\Requests\ScheduleRequests\ShowScheduleRequest;
use App\Http\Requests\ScheduleRequests\UpdateScheduleRequest;
use App\Http\Requests\ScheduleRequests\DestroySchedueRequest;



class ScheduleController extends Controller
{
    //
    public function store(StoreScheduleRequest $request)
    {
        $validated = $request->validated();

        $schedule = Schedule::create($validated);
        
        return response()->json($schedule, 201);
    }

    public function show(ShowScheduleRequest $request)
    {
        $validated = $request->validated();
        $schedule = Schedule::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id']);

        return response()->json($schedule, 200);
    }

    public function update(UpdateScheduleRequest $request) 
    {
        $validated = $request->validated();
        
        $schedule = Schedule::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id']);

        $schedule->update($validated);
        
        return response()->json($schedule, 200);

    }

    public function destroy (DestroyScheduleRequest $request)
    {
        $validated = $request->validated();

        $schedule = Schedule::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id']);

        $schedule->delete();
        return response()->json(200);
    }


}