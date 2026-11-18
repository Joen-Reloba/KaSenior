<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberRosterTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function memberData(): array
    {
        return ['first_name' => 'Test', 'last_name' => 'Member', 'gender' => 'Female', 'birth_date' => '1950-10-01', 'location' => '7C', 'status' => 'Active', 'registered_at' => '2020-01-01'];
    }

    public function test_defaults_and_custom_roster_details_are_saved_and_displayed(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/members/create')->assertOk()->assertSee('value="Baliok"', false)->assertSee('value="Talomo"', false)->assertSee('value="7C"', false);
        $this->post('/members', $this->memberData())->assertSessionHasNoErrors();
        $member = Member::firstOrFail();
        $this->assertSame('Baliok', $member->barangay);
        $this->assertSame('Talomo', $member->district);
        $details = ['name_extension' => 'III', 'barangay' => 'Other Barangay', 'district' => 'Other District', 'location' => 'Purok 12', 'senior_citizen_id' => '00-01234', 'id_issued_at' => '2024-01-15', 'mobile' => '09123456789'];
        $this->put('/members/'.$member->id, array_replace($this->memberData(), $details))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('members', array_diff_key($details, ['id_issued_at' => true]));
        $this->assertSame('2024-01-15', $member->fresh()->id_issued_at->toDateString());
        $this->get('/members/'.$member->id)->assertSee('Test Member III')->assertSee('Other Barangay')->assertSee('00-01234')->assertSee('January 15, 2024');
        $this->get('/members/'.$member->id.'/edit')->assertSee('value="Purok 12"', false);
        $this->put('/members/'.$member->id, array_replace($this->memberData(), ['id_issued_at' => '1940-01-01', 'barangay' => '']))->assertSessionHasErrors(['id_issued_at']);
        $this->put('/members/'.$member->id, array_replace($this->memberData(), ['id_issued_at' => '', 'senior_citizen_id' => '']))->assertSessionHasNoErrors();
        $this->assertNull($member->fresh()->id_issued_at);
    }

    public function test_recent_member_sort_does_not_change_roster_order(): void
    {
        $this->actingAs(User::factory()->create());
        $first = Member::create(array_replace($this->memberData(), ['last_name' => 'Aquino']));
        $second = Member::create(array_replace($this->memberData(), ['last_name' => 'Santos']));

        $this->get('/members')->assertOk()->assertSeeInOrder(['Test Aquino', 'Test Santos']);
        $this->get('/members?sort=recent')->assertOk()->assertSeeInOrder(['Test Santos', 'Test Aquino']);
        $this->get('/members/roster?sort=recent')->assertOk()
            ->assertViewHas('members', fn ($members) => $members->modelKeys() === [$first->id, $second->id]);
    }

    public function test_roster_filters_ages_columns_and_pdf_generation(): void
    {
        $this->actingAs(User::factory()->create());
        $member = Member::create($this->memberData() + ['senior_citizen_id' => 'TEST-001', 'name_extension' => 'III']);
        Member::create(array_replace($this->memberData(), ['first_name' => 'Excluded', 'location' => 'Other', 'status' => 'Deceased', 'deceased_at' => '2025-01-01']));
        $response = $this->get('/members/roster?as_of=2026-09-30');
        $response->assertOk()->assertViewHas('members', fn ($members) => $members->count() === 1 && $members->first()->id === $member->id);
        $html = view('members.roster-pdf', $response->original->getData())->render();
        $this->assertStringContainsString('SC ID #', $html);
        $this->assertStringContainsString('TEST-001', $html);
        $this->assertStringContainsString('>75</td>', $html);
        $this->assertStringNotContainsString('Excluded', $html);
        $this->get('/members/roster?location=Other&status=')->assertViewHas('members', fn ($members) => $members->count() === 1);
        $this->get('/members/roster?as_of=invalid')->assertSessionHasErrors('as_of');
        $this->get('/members/roster?download=1&location=Missing')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        for ($index = 1; $index <= 75; $index++) {
            Member::create(array_replace($this->memberData(), ['first_name' => 'Sample '.$index, 'middle_name' => 'Long Middle Name', 'senior_citizen_id' => 'TEST-'.$index]));
        }
        $pdf = $this->get('/members/roster?download=1&as_of=2026-09-30');
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame(77, Member::count());
        if (getenv('ROSTER_PREVIEW_PATH')) {
            file_put_contents(getenv('ROSTER_PREVIEW_PATH'), $pdf->getContent());
        }
    }

    public function test_cleared_defaults_are_restored_and_active_death_date_is_ignored(): void
    {
        $this->actingAs(User::factory()->create());
        $data = array_replace($this->memberData(), ['location' => '', 'barangay' => '   ', 'district' => '', 'deceased_at' => '2024-01-01']);
        $this->post('/members', $data)->assertSessionHasNoErrors();
        $member = Member::firstOrFail();
        $this->assertSame('7C', $member->location);
        $this->assertSame('Baliok', $member->barangay);
        $this->assertSame('Talomo', $member->district);
        $this->assertNull($member->deceased_at);
        $this->put('/members/'.$member->id, $data)->assertSessionHasNoErrors();
        $this->get('/members/create')->assertDontSee('name="address"', false)->assertDontSee('name="emergency_contact"', false)->assertSee('id="deceased-at" disabled', false);
        $this->get('/members')->assertOk()->assertSee('data-open-roster', false)->assertSee('id="member-roster-dialog"', false)->assertDontSee('>Roster PDF</a>', false)->assertDontSee('>Print List</a>', false);
    }

    public function test_roster_requires_authentication(): void
    {
        $this->get('/members/roster?download=1')->assertRedirect('/login');
    }
}
