<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChallongeService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('CHALLONGE_API_KEY'); // Ensure you set this in .env
        $this->baseUrl = "https://api.challonge.com/v1";
    }

    private function request($method, $endpoint, $params = [])
    {
        $params['api_key'] = $this->apiKey;
        $url = "{$this->baseUrl}/{$endpoint}.json";

        $response = Http::{$method}($url, $params);

        return $response->json();
    }

    public function getTournaments($params = [])
    {
        return $this->request('get', 'tournaments', $params);
    }

    public function createTournament($params)
    {
        return $this->request('post', 'tournaments', ['tournament' => $params]);
    }

    public function getTournament($event_id, $params = [])
    {
        return $this->request('get', "tournaments/{$event_id}", $params);
    }

    public function updateTournament($event_id, $params)
    {
        return $this->request('put', "tournaments/{$event_id}", ['tournament' => $params]);
    }

    public function deleteTournament($event_id)
    {
        return $this->request('delete', "tournaments/{$event_id}");
    }

    public function startTournament($event_id, $params = [])
    {
        return $this->request('post', "tournaments/{$event_id}/start", $params);
    }

    public function finalizeTournament($event_id)
    {
        return $this->request('post', "tournaments/{$event_id}/finalize");
    }

    public function resetTournament($event_id)
    {
        return $this->request('post', "tournaments/{$event_id}/reset");
    }
    public function getMatches($event_id, $params = [])
    {
        return $this->request('get', "tournaments/{$event_id}/matches", $params);
    }

    public function getTournamentParticipants($event_id, $params = [])
    {
        return $this->request('get', "tournaments/{$event_id}/participants");
    }

    public function addTournamentParticipants($event_id, $payload = [])
    {
        $url = "{$this->baseUrl}/tournaments/{$event_id}/participants/bulk_add.json";
        
        // Use JSON format as shown in the example
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post($url, $payload);

        // Safely handle the response
        if ($response->successful()) {
            return $response->json(); 
        }

        \Log::error('[ChallongeService] Failed to add participants.', [
            'status' => $response->status(),
            'body' => $response->body(),
            'sent_payload' => $payload
        ]);

        return [];
    }

    public function updateMatchScore($event_id, $match_id, $params)
    {
        return $this->request('put', "tournaments/{$event_id}/matches/{$match_id}", ['match' => $params]);
    }

    public function getParticipantStandings($event_id)
    {
        $params = [
            'include_matches' => true
        ];
        
        return $this->request('get', "tournaments/{$event_id}/participants", $params);
    }

}