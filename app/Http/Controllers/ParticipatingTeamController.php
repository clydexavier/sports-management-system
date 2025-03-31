<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ParticipatingTeamRequests\StorePTRequest;
use App\Http\Requests\ParticipatingTeamRequests\ShowPTRequest;
use App\Http\Requests\ParticipatingTeamRequests\UpdatePTRequest;
use App\Http\Requests\ParticipatingTeamRequests\DeletePTRequest;

class ParticipatingTeamController extends Controller
{
    //
    public function index()
    {

    }

    public function store(StorePTRequest $request)
    {
        $validated = $request->validated();

    }

    public function show(ShowPTRequest $request)
    {
        $validated = $request->validated();
    }

    public function update(UpdatePTRequest $request)
    {
        $validated = $request->validated();
    }

    public function destroy(DeletePTRequest $request)
    {
        $validated = $request->validated();
    }
}
