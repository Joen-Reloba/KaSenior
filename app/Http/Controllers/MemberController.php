<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['Active', 'Deceased', 'Archived'])],
            'location' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['alphabetical', 'recent'])],
        ]);
        $archived = $request->input('status') === 'Archived';
        $query = $archived ? Member::onlyTrashed() : Member::query();
        if ($request->filled('q')) {
            $query->whereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", ['%'.$request->string('q').'%']);
        }
        if (! $archived && $request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('location')) {
            $query->where('location', $request->input('location'));
        }
        $printing = $request->boolean('print');
        if (! $printing && $request->input('sort') === 'recent') {
            $query->latest()->orderByDesc('id');
        } else {
            $query->orderBy('last_name')->orderBy('first_name')->orderBy('id');
        }
        $members = $printing ? $query->get() : $query->paginate(10)->withQueryString();

        return view('members.index', [
            'members' => $members,
            'locations' => Member::withTrashed()->distinct()->orderBy('location')->pluck('location'),
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
        $member->update($this->validated($request, $member));

        return redirect()->route('members.show', $member)->with('success', 'Member updated.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()->route('members.index')->with('success', 'Member archived. Select Archived in the status filter to view this member.');
    }

    private function validated(Request $request, ?Member $member = null): array
    {
        foreach (['location' => '7C', 'barangay' => 'Baliok', 'district' => 'Talomo'] as $field => $default) {
            if ($request->exists($field) && ! $request->filled($field)) {
                $request->merge([$field => $default]);
            }
        }
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_extension' => ['nullable', 'string', 'max:40'],
            'barangay' => ['sometimes', 'required', 'string', 'max:255'],
            'district' => ['sometimes', 'required', 'string', 'max:255'],
            'senior_citizen_id' => ['nullable', 'string', 'max:100'],
            'id_issued_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:birth_date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['Female', 'Male'])],
            'birth_date' => ['required', 'date', 'before_or_equal:'.today()->subYears(60)->toDateString()],
            'location' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(['Active', 'Deceased'])],
            'deceased_at' => ['exclude_unless:status,Deceased', 'nullable', 'date_format:Y-m-d', 'after_or_equal:birth_date', 'before_or_equal:today'],
            'registered_at' => ['required', 'date', 'after_or_equal:birth_date', 'before_or_equal:today'],
        ]);
        $data['deceased_at'] = $data['status'] === 'Deceased'
            ? ($data['deceased_at'] ?? $member?->deceased_at ?? today())
            : null;

        return $data;

    }
}
