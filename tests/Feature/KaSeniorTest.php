<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaSeniorTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_event_payment_and_reports_work_together(): void
    {
        $this->actingAs(User::factory()->create());
        $data = ['first_name' => 'Maria', 'last_name' => 'Santos', 'gender' => 'Female', 'birth_date' => '1954-03-12', 'location' => 'Purok 3', 'status' => 'Active', 'registered_at' => today()->toDateString()];
        $this->post('/members', $data)->assertSessionHasNoErrors()->assertRedirect();
        $member = Member::firstOrFail();
        $this->get('/members?q=Maria')->assertOk()->assertSee('Maria Santos');
        $this->get('/members/'.$member->id)->assertOk();
        $this->get('/members/'.$member->id.'/edit')->assertOk();
        $this->get('/members/create')->assertOk();
        $this->get('/census?gender=Female')->assertOk()->assertSee('Purok 3');
        $this->post('/events', ['name' => 'Fiesta', 'event_date' => today()->toDateString(), 'amount' => '100.00'])->assertSessionHasNoErrors()->assertRedirect();
        $event = Event::firstOrFail();
        $payment = Contribution::firstOrFail();
        $this->get('/events')->assertOk();
        $this->get('/events/create')->assertOk();
        $this->get('/events/'.$event->id.'/edit')->assertOk();
        $this->get('/events/'.$event->id.'/payments/'.$payment->id)->assertOk();
        $this->put('/events/'.$event->id.'/payments/'.$payment->id, ['amount' => '25.50', 'paid_at' => today()->toDateString(), 'version' => 0])->assertSessionHasNoErrors();
        $this->assertSame(2550, $payment->fresh()->amount_cents);
        $this->assertSame(7450, $event->fresh()->summary()['outstanding']);
        $this->put('/events/'.$event->id.'/payments/'.$payment->id, ['amount' => '100', 'paid_at' => today()->toDateString(), 'version' => 0])->assertSessionHasErrors('amount');
        $this->put('/events/'.$event->id.'/payments/'.$payment->id, ['amount' => '100', 'paid_at' => today()->toDateString(), 'version' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, $event->fresh()->summary()['paid']);
        $this->get('/events/'.$event->id.'?print=1')->assertOk()->assertSee('Maria Santos');
        $this->get('/members?print=1')->assertOk();
        $this->put('/members/'.$member->id, array_merge($data, ['status' => 'Deceased']))->assertSessionHasNoErrors();
        $this->assertSame(1, $event->fresh()->summary()['count']);
        $this->get('/dashboard')->assertOk();
        $this->delete('/members/'.$member->id)->assertRedirect('/members');
        $this->assertSoftDeleted($member);
        foreach (['', 'Active', 'Deceased'] as $status) {
            $this->get('/members?status='.$status)->assertOk()->assertDontSee('Maria Santos');
        }
        Member::create(array_replace($data, ['first_name' => 'Ana']));
        $this->get('/members?status=Archived')->assertOk()->assertSee('Maria Santos')->assertDontSee('Ana Santos');
        $this->get('/members/'.$member->id)->assertOk()->assertSee('Archived')->assertDontSee('Archive Member')->assertDontSee('Edit Member');
        $this->get('/members/'.$member->id.'/edit')->assertNotFound();
        $this->get('/events/'.$event->id)->assertOk()->assertSee('Maria Santos');
    }

    public function test_guests_and_invalid_data_are_rejected(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->post('/members', ['birth_date' => today()->toDateString()])->assertSessionHasErrors(['first_name', 'birth_date']);
        $this->post('/events', ['name' => 'Invalid', 'amount' => '-5'])->assertSessionHasErrors(['amount', 'event_date']);
    }
}
