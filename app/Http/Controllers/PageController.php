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
        // The newest High alert (same 7 days as the bell): Tugak names it and
        // drops the jokes, like newestHighAlert in the app.
        $alert = rescue(fn () => Alert::where('severity', 'high')->where('created_at', '>', now()->subDays(7))->latest('created_at')->first(), null, false);

        return view('home', [
            'w' => $w,
            'reports' => $reports,
            'alert' => $alert,
        ]);
    }

    /** Flood reports + evacuation centers on a map (FR-G2). */
    public function map()
    {
        $reports = rescue(fn () => FloodReport::active()->with('user')->get(), collect(), false);
        $centers = rescue(fn () => EvacuationCenter::orderBy('name')->get(), collect(), false);

        return view('map', [
            // Deepest first, reports without a depth last (the app's _zones).
            'reports' => $reports->sortByDesc(fn ($r) => $r->depth_cm ?? -1)->map(fn ($r) => [
                'id' => $r->report_id,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'type' => ucfirst($r->incident_type),
                'barangay' => $r->barangay,
                'depth' => $r->depth_cm,
                'level' => $r->depth_cm === null ? null : ucfirst(AgosData::depthLabel($r->depth_cm)),
                'risk' => $r->risk(),
                'photo' => $r->photo_url,
                'note' => $r->note,
                'by' => $r->user?->full_name,
                'updated' => AgosData::timeAgo($r->created_at),
                'taken' => $r->photo_taken_at?->format('D, M j, g:i A'),
                'takenAgo' => $r->photo_taken_at ? AgosData::timeAgo($r->photo_taken_at) : null,
                'leftHours' => max(0, (int) now()->diffInHours($r->expires_at)),
            ])->values(),
            'centers' => $centers->map(fn ($c) => [
                'name' => $c->name,
                'icon' => AgosData::centerIcon($c->name),
                'distance' => ($d = AgosData::distanceKm(request()->attributes->get('me')?->barangay, $c->latitude, $c->longitude)) === null ? '' : number_format($d, 1).' km',
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
            // 911 is the SOS button, so the list is the local numbers.
            'hotlines' => array_values(array_filter(AgosData::HOTLINES, fn ($h) => $h[1] !== '911')),
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

    /** markAlertsRead in the app: alert_reads for users, the session for guests. */
    public function alertsRead(Request $request)
    {
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));
        $me = $request->attributes->get('me');
        if ($me && $ids) {
            \Illuminate\Support\Facades\DB::connection('supabase')->table('alert_reads')->upsert(
                array_map(fn ($id) => ['user_id' => $me->user_id, 'alert_id' => $id], $ids), ['user_id', 'alert_id'], [],
            );
        } elseif ($ids) {
            $request->session()->put('read_alerts', array_values(array_unique([...$request->session()->get('read_alerts', []), ...$ids])));
        }

        return response()->noContent();
    }
}
