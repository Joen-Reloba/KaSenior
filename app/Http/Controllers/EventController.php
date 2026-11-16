<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('events.index', ['events' => Event::with('contributions')->orderByDesc('event_date')->paginate(12)]);
    }

    public function create(): View
    {
        return view('events.form', ['event' => new Event(['event_date' => today()]), 'memberCount' => Member::where('status', 'Active')->count()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $event = DB::transaction(function () use ($data): Event {
            $event = Event::create($data);
            Member::where('status', 'Active')->orderBy('id')->chunkById(250, function ($members) use ($event): void {
                foreach ($members as $member) {
                    $event->contributions()->create(['member_id' => $member->id]);
                }
            });

            return $event;
        });

        return redirect()->route('events.show', $event)->with('success', 'Event created with its active member list.');
    }

    public function show(Request $request, Event $event): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:paid,unpaid']]);
        $event->load('contributions');
        $query = $event->contributions()->with('member');
        if ($request->filled('q')) {
            $query->whereHas('member', fn ($query) => $query->whereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", ['%'.$request->string('q').'%']));
        }
        if ($request->input('status') === 'paid') {
            $query->where('amount_cents', '>=', $event->amount_cents);
        }
        if ($request->input('status') === 'unpaid') {
            $query->where('amount_cents', '<', $event->amount_cents);
        }
        $query->orderBy('amount_cents')->orderBy('id');

        return view('events.show', [
            'event' => $event,
            'summary' => $event->summary(),
            'contributions' => $request->boolean('print') ? $query->get() : $query->paginate(10)->withQueryString(),
        ]);
    }

    public function edit(Event $event): View
    {
        return view('events.form', ['event' => $event, 'memberCount' => $event->contributions()->count()]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($event, $data): void {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($event->contributions()->where('amount_cents', '>', $data['amount_cents'])->exists()) {
                throw ValidationException::withMessages(['amount' => 'The required amount cannot be lower than an existing payment.']);
            }
            $event->update($data);
        });

        return redirect()->route('events.show', $event)->with('success', 'Event updated.');
    }

    public function payment(Event $event, Contribution $contribution): View
    {
        abort_unless($contribution->event_id === $event->id, 404);

        return view('events.payment', compact('event', 'contribution'));
    }

    public function savePayment(Request $request, Event $event, Contribution $contribution): RedirectResponse
    {
        abort_unless($contribution->event_id === $event->id, 404);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:1000000', 'decimal:0,2'],
            'paid_at' => ['nullable', 'required_unless:amount,0', 'date', 'before_or_equal:today'],
            'version' => ['required', 'integer', 'min:0'],
        ]);
        DB::transaction(function () use ($event, $contribution, $data): void {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $contribution = Contribution::whereKey($contribution->id)->lockForUpdate()->firstOrFail();
            if ($contribution->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['amount' => 'This payment was changed in another window. Reload this page before saving again.']);
            }
            $cents = (int) round((float) $data['amount'] * 100);
            if ($cents > $event->amount_cents) {
                throw ValidationException::withMessages(['amount' => 'The payment cannot exceed the required contribution.']);
            }
            $contribution->update(['amount_cents' => $cents, 'paid_at' => $cents > 0 ? $data['paid_at'] : null, 'version' => $contribution->version + 1]);
        });

        return redirect()->route('events.show', $event)->with('success', 'Payment saved.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000', 'decimal:0,2'],
        ]);
        $data['amount_cents'] = (int) round((float) $data['amount'] * 100);
        unset($data['amount']);

        return $data;
    }
}
