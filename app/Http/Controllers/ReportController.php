<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function dashboard(): View
    {
        $active = Member::where('status', 'Active')->get();
        $event = Event::with('contributions')->orderByDesc('event_date')->first();

        return view('dashboard', [
            'total' => Member::count(),
            'active' => $active->count(),
            'deceased' => Member::where('status', 'Deceased')->count(),
            'groups' => $this->groups($active),
            'event' => $event,
            'summary' => $event?->summary(),
            'recent' => Member::latest('id')->limit(3)->get(),
        ]);
    }

    public function census(Request $request): View
    {
        $request->validate([
            'age' => ['nullable', 'in:60,70,80,90'],
            'gender' => ['nullable', 'in:Female,Male'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);
        $query = Member::where('status', 'Active');
        foreach (['gender', 'location'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        $members = $query->get()->filter(function (Member $member) use ($request): bool {
            $minimum = $request->integer('age', 60);
            $maximum = $minimum === 90 || ! $request->filled('age') ? PHP_INT_MAX : $minimum + 9;

            return $member->age >= $minimum && $member->age <= $maximum;
        });

        return view('census', [
            'members' => $members,
            'groups' => $this->groups($members),
            'locations' => Member::distinct()->orderBy('location')->pluck('location'),
            'byLocation' => $members->groupBy('location')->map->count()->sortKeys(),
        ]);
    }

    private function groups($members): array
    {
        $groups = [];
        foreach ([60 => '60 – 69', 70 => '70 – 79', 80 => '80 – 89', 90 => '90 and above'] as $minimum => $label) {
            $rows = $members->filter(fn (Member $member) => $member->age >= $minimum && ($minimum === 90 || $member->age < $minimum + 10));
            $groups[$label] = ['total' => $rows->count(), 'female' => $rows->where('gender', 'Female')->count(), 'male' => $rows->where('gender', 'Male')->count()];
        }

        return $groups;
    }
}
