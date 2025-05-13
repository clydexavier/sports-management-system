<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequests\StoreUserRequest;
use App\Http\Requests\UserRequests\UpdateUserRequest;
use App\Http\Requests\UserRequests\UpdateRoleRequest;
use App\Http\Requests\UserRequests\DeleteUserRequest;


use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = 8;

        // Optional query-string filters
        $role   = $request->query('role');      // e.g. ?role=admin|GAM|tsecretary|secretariat
        $search = $request->query('search');    // e.g. ?search=jane

        $query = User::query();

        /* role filter --------------------------------------------------------- */
        if ($role && $role !== 'all') {
            $query->where('role', $role);
        }

        /* search filter (name or email) --------------------------------------- */
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                ->orWhere('email','like', "%{$search}%");
            });
        }

        /* build paginated result --------------------------------------------- */
        $users = $query
        ->select('id', 'name', 'email', 'role', 'avatar_url', 'google_id') // include google_id
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    

       /* return only the requested fields ----------------------------------- */
        return response()->json([
            'data' => collect($users->items())->map(function ($user) {
                return [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'role'       => $user->role,
                    'avatar_url' => $user->google_id ? $user->avatar_url : null,
                ];
            }),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'last_page'    => $users->lastPage(),
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);
        return response(new UserResource($user),201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {   
        $user = $request->user(); // Same as Auth::user()
       // Check if the user role is 'user' and log them out if it is
       if($user->role === 'user'){
            Auth::logout();
            
            // For Laravel Sanctum, we should revoke any existing tokens
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            
            return response()->json([
                'message' => 'Please wait for the administrator to assign your appropriate role. You will be notified once your account is approved.'
            ], 403);
        }
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        if(isset($data['password'])){
            $data['password'] = bcrypt($data['password']);
        }
        $user->update($data);
        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteUserRequest $request)
    {
        $validated = $request->validated();
        $user = User::findOrFail($validated['id']);
        $user->delete();
        return response('',204);
    }

    public function update_role(UpdateRoleRequest $request, $id)
    {
        $validatedData = $request->validated();
        $user = User::findOrFail($id);
        $user->role = $validatedData['role'];

        if ($validatedData['role'] === 'GAM') {
            if (!isset($validatedData['intrams_id']) || !isset($validatedData['team_id'])) {
                return response()->json([
                    'message' => 'Intramural and team assignments are required for GAM role',
                    'errors' => [
                        'intrams_id' => ['Intramural assignment is required'],
                        'team_id' => ['Team assignment is required']
                    ]
                ], 422);
            }

            $user->intrams_id = $validatedData['intrams_id'];
            $user->team_id = $validatedData['team_id'];
            $user->event_id = null;

        } elseif ($validatedData['role'] === 'tsecretary') {
            if (!isset($validatedData['intrams_id']) || !isset($validatedData['event_id'])) {
                return response()->json([
                    'message' => 'Intramural and event assignments are required for Tournament Secretary role',
                    'errors' => [
                        'intrams_id' => ['Intramural assignment is required'],
                        'event_id' => ['Event assignment is required']
                    ]
                ], 422);
            }

            $user->intrams_id = $validatedData['intrams_id'];
            $user->event_id = $validatedData['event_id'];
            $user->team_id = null;

        } else {
            $user->intrams_id = null;
            $user->team_id = null;
            $user->event_id = null;
        }

        $user->save();
        $user->load(['team:id,name', 'intramural:id,name', 'event:id,name']);

        $responseData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'team_id' => $user->team_id,
            'team_name' => $user->team?->name,
            'intrams_id' => $user->intrams_id,
            'intrams_name' => $user->intramural?->name,
            'event_id' => $user->event_id,
            'event_name' => $user->event?->name,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        return response()->json([
            'message' => 'User role updated successfully',
            'data' => $responseData
        ], 200);
    }

}