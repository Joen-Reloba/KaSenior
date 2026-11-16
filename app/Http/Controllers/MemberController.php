<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in(['Active', 'Deceased'])], 'location' => ['nullable', 'string', 'max:255']]);
        $query = Member::query();
        if ($request->filled('q')) {
            $query->whereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", ['%'.$request->string('q').'%']);
        }
        foreach (['status', 'location'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        $query->orderBy('last_name')->orderBy('first_name')->orderBy('id');
        $members = $request->boolean('print') ? $query->get() : $query->paginate(10)->withQueryString();

        return view('members.index', [
            'members' => $members,
            'locations' => Member::distinct()->orderBy('location')->pluck('location'),
            'active' => Member::where('status', 'Active')->count(),
            'deceased' => Member::where('status', 'Deceased')->count(),
        ]);
    }

    public function create(): View
    {
        return view('members.form', ['member' => new Member(['status' => 'Active', 'registered_at' => today()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $member = Member::create($this->validated($request));

        return redirect()->route('members.show', $member)->with('success', 'Member added.');
    }

    public function show(Member $member): View
    {
        return view('members.show', compact('member'));
    }

    public function edit(Member $member): View
    {
        return view('members.form', compact('member'));
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $member->update($this->validated($request));

        return redirect()->route('members.show', $member)->with('success', 'Member updated.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()->route('members.index')->with('success', 'Member removed from the member list. Event records are retained.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Female', 'Male'])],
            'birth_date' => ['required', 'date', 'before_or_equal:'.today()->subYears(60)->toDateString()],
            'location' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Deceased'])],
            'registered_at' => ['required', 'date', 'after_or_equal:birth_date', 'before_or_equal:today'],
        ]);
    }
}
