<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CensusPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_census_default_pdf_uses_filtered_members_and_chapter_details(): void
    {
        $this->get('/census/pdf')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        foreach ([['Sample', 'Female', 'Active', '7C'], ['Excluded', 'Male', 'Deceased', '7C'], ['Elsewhere', 'Male', 'Active', 'Other']] as [$name, $gender, $status, $location]) {
            Member::create(['first_name' => $name, 'last_name' => 'Test', 'gender' => $gender, 'status' => $status, 'location' => $location, 'birth_date' => today()->subYears(85), 'registered_at' => today()]);
        }
        $this->get('/census')->assertOk()->assertSee(route('census.pdf'), false)->assertSee('Chapter data sheet PDF');
        $request = Request::create('/census', 'GET', ['location' => '7C']);
        $data = app(ReportController::class)->census($request)->getData();
        $this->assertSame(['Sample'], $data['members']->pluck('first_name')->all());
        $html = view('census-pdf', $data + ['asOf' => today(), 'association' => 'Test Chapter', 'details' => ['pensioners' => 0, 'waitlisted' => 1]])->render();
        $this->assertStringContainsString('CHAPTER DATA SHEET', $html);
        $this->assertStringContainsString('85 YO', $html);
        $response = $this->get('/census/pdf?location=7C&chapter=Test+Chapter&president=Sample+President&submitted_by=Sample+Signatory&pensioners=0&waitlisted=1');
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi;
        $source = \setasign\Fpdi\PdfParser\StreamReader::createByString($response->getContent());
        $this->assertSame(1, $pdf->setSourceFile($source));
        $this->get('/census/pdf?location=Missing')->assertOk();
        $this->get('/census/pdf?pensioners=-1&waitlisted=abc')->assertSessionHasErrors(['pensioners', 'waitlisted']);
        $this->get('/census/pdf?age=invalid')->assertSessionHasErrors('age');
        $this->assertDatabaseCount('members', 3);
        if (getenv('CENSUS_PREVIEW_PATH')) {
            file_put_contents(getenv('CENSUS_PREVIEW_PATH'), $response->getContent());
        }
    }
}
