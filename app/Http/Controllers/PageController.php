<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\EvacuationCenter;
use App\Models\FloodReport;
use App\Models\RescueRequest;
use App\Services\Weather;
use App\Support\AgosData;
use Illuminate\Http\Request;
use Throwable;

/**
 * The read-only pages everyone can open (FR-G1..G6): Weather (home),
 * Map, Help, Alerts. Each page is its own address: no swiping between
 * them like the Flutter app, this is a website.
 */
class PageController extends Controller
{
    /** Weather + flood outlook + what's on the map now (FR-G1, FR-G3). */
    public function home(Weather $weather)
    {
        $w = rescue(fn () => $weather->now(), null, false);
        $reports = rescue(fn () => FloodReport::active()->latest('created_at')->get(), collect(), false);
        $alert = rescue(fn () => Alert::where('severity', 'high')->latest('created_at')->first(), null, false);

        return view('home', [
            'w' => $w,
            'outlook' => $w ? Weather::outlook($w) : null,
            'reports' => $reports,
            'alert' => $alert,
        ]);
    }

    /** Flood reports + evacuation centers on a map (FR-G2). */
    public function map()
    {
        $reports = rescue(fn () => FloodReport::active()->get(), collect(), false);
        $centers = rescue(fn () => EvacuationCenter::orderBy('name')->get(), collect(), false);

        return view('map', [
            'reports' => $reports->map(fn ($r) => [
                'id' => $r->report_id,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'type' => ucfirst($r->incident_type),
                'barangay' => $r->barangay,
                'depth' => $r->depth_cm,
                'risk' => $r->risk(),
                'photo' => $r->photo_url,
                'note' => $r->note,
            ])->values(),
            'centers' => $centers->map(fn ($c) => [
                'name' => $c->name,
                'lat' => $c->latitude,
                'lng' => $c->longitude,
                'status' => $c->status,
                'barangay' => $c->barangay,
            ])->values(),
            'sample' => $centers->contains('is_sample', true),
        ]);
    }

    /** Hotlines, evacuation centers, the rescue request form (FR-G5, G6, G8, G9). */
    public function help(Request $request)
    {
        $centers = rescue(fn () => EvacuationCenter::orderByRaw("status = 'open' desc")->orderBy('name')->get(), collect(), false);
        // FR-G9: the request this browser sent last (its id is in the session).
        $mine = null;
        if ($id = $request->session()->get('rescue_request_id')) {
            $mine = rescue(fn () => RescueRequest::find($id), null, false);
        }

        return view('help', [
            'hotlines' => AgosData::HOTLINES,
            'centers' => $centers,
            'sample' => $centers->contains('is_sample', true),
            'barangays' => AgosData::BARANGAYS,
            'needs' => AgosData::RESCUE_NEEDS,
            'mine' => $mine,
        ]);
    }

    public function alerts()
    {
        try {
            $alerts = Alert::latest('created_at')->get();
            $failed = false;
        } catch (Throwable) {
            $alerts = collect();
            $failed = true;
        }

        return view('alerts', ['alerts' => $alerts, 'failed' => $failed]);
    }
}
