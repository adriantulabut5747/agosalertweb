<?php

namespace App\Http\Controllers;

use App\Models\AgosModel;
use App\Models\Alert;
use App\Models\AppUser;
use App\Models\EvacuationCenter;
use App\Models\FlaggedNumber;
use App\Models\FloodReport;
use App\Models\RescueRequest;
use App\Support\AgosData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

/**
 * The admin dashboard (FR-A1..A8), same powers as the Flutter app's
 * Admin page. Every route here sits behind the 'admin' middleware.
 * Changes run asUser(admin) so the database stamps who did them.
 */
class AdminController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'rescue');

        return view('admin.index', [
            'tab' => $tab,
            'requests' => $tab === 'rescue' ? RescueRequest::latest('created_at')->take(200)->get() : collect(),
            'flagged' => $tab === 'rescue' ? FlaggedNumber::pluck('phone_number')->all() : [],
            // Live ones first, then the trash (every row left is one or the other).
            'reports' => $tab === 'reports' ? FloodReport::with('user')->latest('created_at')->get()
                ->sortBy(fn ($r) => $r->inTrash())->values() : collect(),
            'alerts' => $tab === 'alerts' ? Alert::latest('created_at')->get() : collect(),
            'centers' => $tab === 'centers' ? EvacuationCenter::orderBy('name')->get() : collect(),
            'users' => $tab === 'users' ? AppUser::latest('created_at')->get() : collect(),
            'statuses' => AgosData::RESCUE_STATUSES,
            'needs' => AgosData::RESCUE_NEEDS,
            'barangays' => AgosData::BARANGAYS,
        ]);
    }

    private function me(Request $request): AppUser
    {
        return $request->attributes->get('me');
    }

    // ---------------------------------------------------------- rescue
    public function rescueStatus(Request $request, RescueRequest $rescue)
    {
        $status = $request->validate(['status' => ['required', Rule::in(array_keys(AgosData::RESCUE_STATUSES))]])['status'];
        AgosModel::asUser($this->me($request)->user_id, fn () => $rescue->update(['status' => $status]));

        return back()->with('ok', "Request #{$rescue->request_id}: ".AgosData::RESCUE_STATUSES[$status]);
    }

    /** Flag false + put the number on the prank list (FR-A5). */
    public function rescueFlag(Request $request, RescueRequest $rescue)
    {
        $reason = $request->input('reason') ?: 'Potential Spam/Prank Call';
        AgosModel::asUser($this->me($request)->user_id, function () use ($rescue, $reason) {
            FlaggedNumber::updateOrCreate(
                ['phone_number' => $rescue->contact_phone],
                ['request_id' => $rescue->request_id, 'reason' => $reason],
            );
            $rescue->update(['status' => 'flagged_false']);
        });

        return back()->with('ok', "{$rescue->contact_phone} flagged.");
    }

    // --------------------------------------------------------- reports
    public function reportStatus(Request $request, FloodReport $report)
    {
        $status = $request->validate(['status' => ['required', Rule::in(['unverified', 'verified', 'removed'])]])['status'];
        $report->update(['status' => $status, 'reviewed_by' => $this->me($request)->user_id]);

        return back()->with('ok', 'Report '.$status.'.');
    }

    /** Admins' delete goes to the trash too; only an admin can restore it. */
    public function reportDelete(Request $request, FloodReport $report)
    {
        AgosModel::asUser($this->me($request)->user_id, fn ($db) => $db->select('select public.trash_report(?)', [$report->report_id]));

        return back()->with(['ok' => 'Moved to the trash.', 'undo' => $report->report_id]);
    }

    // ---------------------------------------------------------- alerts
    public function alertStore(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string'],
            'category' => ['required', Rule::in(['flood', 'weather', 'road', 'shelter'])],
            'severity' => ['required', Rule::in(['normal', 'moderate', 'high'])],
            'area' => ['required', 'string', 'max:100'],
            'actions' => ['nullable', 'string'],
        ]);
        $steps = array_values(array_filter(array_map('trim', explode("\n", $data['actions'] ?? ''))));
        // As the admin: the trigger sets posted_by to them.
        AgosModel::asUser($this->me($request)->user_id, fn () => Alert::create([
            ...collect($data)->except('actions')->all(),
            'actions' => AgosModel::toPgArray($steps),
        ]));

        return back()->with('ok', 'Alert posted. It shows under the bell in the app too.');
    }

    public function alertDelete(Alert $alert)
    {
        $alert->delete();

        return back()->with('ok', 'Alert deleted.');
    }

    // --------------------------------------------------------- centers
    public function centerStatus(Request $request, EvacuationCenter $center)
    {
        $status = $request->validate(['status' => ['required', Rule::in(['open', 'standby', 'closed'])]])['status'];
        AgosModel::asUser($this->me($request)->user_id, fn () => $center->update(['status' => $status]));

        return back()->with('ok', "{$center->name}: {$status}.");
    }

    public function centerStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'barangay' => ['required', Rule::in(AgosData::BARANGAYS)],
            'latitude' => ['required', 'numeric', 'between:15.130,15.300'],
            'longitude' => ['required', 'numeric', 'between:120.490,120.670'],
            'status' => ['required', Rule::in(['open', 'standby', 'closed'])],
        ]);
        AgosModel::asUser($this->me($request)->user_id, fn () => EvacuationCenter::create($data));

        return back()->with('ok', "{$data['name']} added.");
    }

    public function centerDelete(EvacuationCenter $center)
    {
        $center->delete();

        return back()->with('ok', "{$center->name} deleted.");
    }

    // ----------------------------------------------------------- users
    /** Suspend / reactivate (FR-A7), never yourself. */
    public function userStatus(Request $request, AppUser $user)
    {
        abort_if($user->user_id === $this->me($request)->user_id, 403, 'You can\'t suspend yourself.');
        $user->update(['account_status' => $user->account_status === 'active' ? 'suspended' : 'active']);

        return back()->with('ok', "{$user->full_name} is now {$user->account_status}.");
    }
}
