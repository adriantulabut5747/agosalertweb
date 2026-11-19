<?php

namespace App\Http\Controllers;

use App\Models\AgosModel;
use App\Support\AgosData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * "Request rescue" (FR-G8, FR-R5). Goes through the same database
 * function as the Flutter app (create_rescue_request in
 * supabase/07_rescue_requests.sql): 3 per number per 10 minutes.
 * Nobody monitors these yet: the form says so in red.
 */
class RescueController extends Controller
{
    public function store(Request $request)
    {
        $me = $request->attributes->get('me');
        $request->merge(['contact_phone' => preg_replace('/[\s\-().]/', '', (string) $request->input('contact_phone'))]);
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'regex:/^\+?[0-9]{7,14}$/'],
            'barangay' => ['required', Rule::in(AgosData::BARANGAYS)],
            'people_count' => ['required', 'integer', 'between:1,99'],
            'special_needs' => ['array'],
            'special_needs.*' => [Rule::in(array_keys(AgosData::RESCUE_NEEDS))],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'contact_phone.regex' => 'Type a phone number the admins can call, like 0917 123 4567.',
        ]);

        try {
            $id = AgosModel::asUser($me?->user_id, fn ($db) => $db->selectOne(
                'select public.create_rescue_request(?, ?, ?, null, null, ?, ?::text[], ?) as id',
                [
                    $data['contact_name'],
                    $data['contact_phone'],
                    $data['barangay'],
                    $data['people_count'],
                    AgosModel::toPgArray($data['special_needs'] ?? []),
                    $data['note'] ?? '',
                ],
            )->id);
        } catch (Throwable $e) {
            $msg = str_contains($e->getMessage(), 'too_many_requests')
                ? 'This number already sent 3 requests in 10 minutes. Call 911 or a hotline now.'
                : 'Couldn\'t send it. Call 911 or a hotline now.';

            return back()->withInput()->with('error', $msg);
        }
        $request->session()->put('rescue_request_id', $id);

        return redirect()->route('help')->with('ok', "Request #{$id} saved. This is a demo: no one is watching these requests yet. If you're in danger, call 911 now.");
    }
}
