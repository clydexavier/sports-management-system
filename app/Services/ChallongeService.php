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
}
