<?php

namespace App\Http\Controllers;

use App\Models\AgosModel;
use App\Models\FloodReport;
use App\Models\ReportVote;
use App\Support\AgosData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Flood reports: the feed (FR-G3), posting one (FR-R3), voting (FR-R4),
 * deleting your own. Same table and rules as the Flutter app
 * (supabase/02_flood_reports.sql, 03_report_votes.sql).
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->attributes->get('me');
        try {
            $q = FloodReport::active()->with('user')->withSum('votes as score', 'vote')
                ->withCount([
                    'votes as up_votes' => fn ($v) => $v->where('vote', 1),
                    'votes as down_votes' => fn ($v) => $v->where('vote', -1),
                ]);
            if ($b = $request->query('barangay')) {
                $q->where('barangay', $b);
            }
            if ($t = $request->query('type')) {
                $q->where('incident_type', $t);
            }
            if ($s = trim((string) $request->query('q'))) {
                $q->where(fn ($w) => $w->where('note', 'ilike', "%{$s}%")
                    ->orWhere('barangay', 'ilike', "%{$s}%")
                    ->orWhere('incident_type', 'ilike', "%{$s}%"));
            }
            $reports = $q->latest('created_at')->get();
            $myVotes = $me ? ReportVote::where('user_id', $me->user_id)
                ->whereIn('report_id', $reports->pluck('report_id'))->pluck('vote', 'report_id') : collect();
            $failed = false;
        } catch (Throwable) {
            [$reports, $myVotes, $failed] = [collect(), collect(), true];
        }

        return view('reports.index', [
            'reports' => $reports,
            'myVotes' => $myVotes,
            'failed' => $failed,
            'barangays' => AgosData::BARANGAYS,
            'types' => AgosData::INCIDENT_TYPES,
        ]);
    }

    public function create()
    {
        return view('reports.create', [
            'barangays' => AgosData::BARANGAYS,
            'types' => AgosData::INCIDENT_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $me = $request->attributes->get('me');
        $data = $request->validate([
            'incident_type' => ['required', Rule::in(AgosData::INCIDENT_TYPES)],
            'severity' => ['required', Rule::in(['minor', 'moderate', 'high'])],
            'depth_cm' => ['nullable', 'integer', 'between:0,300'],
            'barangay' => ['required', Rule::in(AgosData::BARANGAYS)],
            // Same box as the app: the spot must be in Mabalacat.
            'latitude' => ['required', 'numeric', 'between:15.130,15.300'],
            'longitude' => ['required', 'numeric', 'between:120.490,120.670'],
            'note' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ], [
            'latitude.required' => 'Tap the map to set where the flooding is.',
            'latitude.between' => 'The spot must be inside Mabalacat.',
            'longitude.between' => 'The spot must be inside Mabalacat.',
        ]);

        // Photo -> Supabase Storage, into the user's own folder, with
        // their login token (the bucket's rules check the folder name).
        $photoUrl = null;
        if ($file = $request->file('photo')) {
            $path = $me->user_id.'/'.now()->valueOf().'.'.$file->extension();
            $base = config('services.supabase.url').'/storage/v1/object';
            $res = Http::withToken((string) $request->session()->get('supabase_token'))
                ->withHeaders(['apikey' => config('services.supabase.key')])
                ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
                ->post("$base/report-photos/$path");
            if (! $res->successful()) {
                return back()->withInput()->with('error', 'The photo didn\'t upload. Log out and in again, or post without a photo.');
            }
            $photoUrl = "$base/public/report-photos/$path";
        }

        try {
            // As the user: the database trigger sets user_id, status and
            // expires_at (14 days after the photo) itself.
            AgosModel::asUser($me->user_id, fn () => FloodReport::create([
                ...collect($data)->except('photo')->all(),
                'photo_url' => $photoUrl,
                'photo_taken_at' => now(), // shortcut: no EXIF date here; posting time
            ]));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Couldn\'t post it. Try again.');
        }

        return redirect()->route('reports.index')->with('ok', 'Posted. Thanks for watching your street.');
    }

    /** Thumbs up / down (FR-R4). Same vote again = take it back. */
    public function vote(Request $request, FloodReport $report)
    {
        $me = $request->attributes->get('me');
        $vote = $request->validate(['vote' => ['required', Rule::in([1, -1])]])['vote'];
        abort_if($report->user_id === $me->user_id, 403, 'You can\'t vote on your own report.');

        $existing = ReportVote::where('report_id', $report->report_id)->where('user_id', $me->user_id);
        if ((int) optional($existing->first())->vote === (int) $vote) {
            $existing->delete();
        } else {
            $existing->delete();
            ReportVote::create(['report_id' => $report->report_id, 'user_id' => $me->user_id, 'vote' => $vote]);
        }

        return back();
    }

    /** Your own report only. */
    public function destroy(Request $request, FloodReport $report)
    {
        $me = $request->attributes->get('me');
        abort_unless($report->user_id === $me->user_id || $me->isAdmin(), 403);
        $report->delete();
        if ($report->photo_url && str_contains($report->photo_url, '/report-photos/')) {
            Http::withToken((string) $request->session()->get('supabase_token'))
                ->withHeaders(['apikey' => config('services.supabase.key')])
                ->delete(config('services.supabase.url').'/storage/v1/object/report-photos/'
                    .explode('/report-photos/', $report->photo_url)[1]);
        }

        return back()->with('ok', 'Report deleted.');
    }
}
