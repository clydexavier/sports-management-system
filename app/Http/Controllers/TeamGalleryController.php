<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\View;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use App\Models\Event;
use App\Models\OverallTeam;
use App\Models\Player;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;


class TeamGalleryController extends Controller
{
    public function generateGalleryDocx(Request $request, $intrams_id, $event_id, $team_id)
{
    $event = Event::findOrFail($event_id);
    $team = OverallTeam::findOrFail($team_id);
    $players = Player::where('event_id', $event_id)
                    ->where('team_id', $team_id)
                    ->get();

    // Map athletes
    $athletes = $players->take(9)->map(function ($player) {
        return [
            'name' => $player->name,
            'dob' => optional($player->dob)->format('F j, Y') ?? 'N/A',
            'course' => $player->course_year ?? 'N/A',
            'contact' => $player->contact ?? 'N/A',
        ];
    })->toArray();

    // Dummy coaching staff (you can replace this with actual coach fetching logic)
    $coach = [
        'name' => 'Coach Name',
        'dob' => 'January 1, 1980',
        'course' => 'BS Physical Education',
        'contact' => '09123456789'
    ];

    $assistantCoach = [
        'name' => 'Assistant Coach',
        'dob' => 'February 2, 1985',
        'course' => 'BS Sports Science',
        'contact' => '09876543210'
    ];

    $athleticManager = 'GENERAL ATHLETIC MANAGER';
    $screeningDate = now()->format('F j, Y');

    $teamLogo = $team->logo_path ? public_path('storage/' . $team->logo_path) : null;
    $teamLogo = $teamLogo ? asset('storage/' . $team->logo_path) : null;

    $html = View::make('gallery_template', [
        'event' => $event->name,
        'team' => $team->name,
        'category' => $event->category,
        'screeningDate' => $screeningDate,
        'teamLogo' => $teamLogo,
        'athletes' => $athletes,
        'coach' => $coach,
        'assistantCoach' => $assistantCoach,
        'athleticManager' => $athleticManager
    ])->render();

    $phpWord = new PhpWord();
    $section = $phpWord->addSection([
        'marginTop' => 600,
        'marginBottom' => 600,
        'marginLeft' => 800,
        'marginRight' => 800,
    ]);

    Html::addHtml($section, $html, false, false);

    $fileName = Str::slug($team->name . '-' . $event->name) . '_gallery_' . now()->format('Ymd_His') . '.docx';
    $outputPath = storage_path("app/public/generated/$fileName");

    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($outputPath);

    return response()->download($outputPath)->deleteFileAfterSend(true);
}

}