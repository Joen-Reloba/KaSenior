<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Member;
use App\Models\PrintTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tests\TestCase;
use ZipArchive;

class PrintTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_template_is_saved_selected_filled_and_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $word = new PhpWord;
        $section = $word->addSection();
        $section->addText('${report_title}');
        $table = $section->addTable();
        $table->addRow();
        $table->addCell()->addText('${member_name}');
        $table->addCell()->addText('${location}');
        $table->addCell()->addText('${amount_paid}');
        $path = tempnam(sys_get_temp_dir(), 'template-test-');
        try {
            \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($path);
            $this->upload($path, 'docx');
            $template = PrintTemplate::firstOrFail();
            $this->get('/members')->assertOk()->assertSee('Test template');
            $this->member('Maria & Ana', 'Purok 1');
            $this->member('Excluded', 'Purok 2');
            $response = $this->get('/print-templates/'.$template->id.'/report?report=members&location=Purok%201');
            $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            file_put_contents($path, $response->getContent());
            $zip = new ZipArchive;
            $zip->open($path);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertStringContainsString('Maria &amp; Ana', $xml);
            $this->assertStringNotContainsString('Excluded', $xml);
            $this->assertStringNotContainsString('${', $xml);
            $this->get('/census')->assertOk()->assertDontSee('Test template');
            $this->get('/print-templates/'.$template->id.'/report?report=census')->assertSessionHasErrors('template');
            $this->get('/print-templates/'.$template->id.'/report?report=members&location=Missing')->assertOk();
            $template->update(['category' => 'census']);
            $this->get('/census')->assertOk()->assertSee('Test template');
            $this->get('/members')->assertOk()->assertDontSee('Test template');
            $this->get('/print-templates/'.$template->id.'/report?report=census')->assertOk();
            $template->update(['category' => 'events']);
            $event = Event::create(['name' => 'Collection', 'event_date' => today(), 'amount_cents' => 10000]);
            $event->contributions()->create(['member_id' => Member::firstOrFail()->id, 'amount_cents' => 2500, 'paid_at' => today()]);
            $this->get('/events/'.$event->id)->assertOk()->assertSee('Test template');
            $eventResponse = $this->get('/print-templates/'.$template->id.'/report?report=events&event='.$event->id.'&status=unpaid');
            $eventResponse->assertOk();
            file_put_contents($path, $eventResponse->getContent());
            $zip->open($path);
            $this->assertStringContainsString('25.00', $zip->getFromName('word/document.xml'));
            $zip->close();
            $this->get('/print-templates/'.$template->id.'/download')->assertOk();
            $this->delete('/print-templates/'.$template->id)->assertRedirect('/print-templates');
            $this->assertDatabaseCount('print_templates', 0);
        } finally {
            @unlink($path);
        }
    }

    public function test_excel_repeats_rows_and_treats_member_values_as_text(): void
    {
        $this->actingAs(User::factory()->create());
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A1', '${report_title}');
        $sheet->setCellValue('A2', '${member_name}');
        $sheet->setCellValue('B2', '${location}');
        $sheet->setCellValue('A3', 'Footer');
        $path = tempnam(sys_get_temp_dir(), 'template-test-');
        try {
            (new Xlsx($book))->save($path);
            $this->upload($path, 'xlsx');
            $this->member('=1+1', 'Purok 1');
            $this->member('Second', 'Purok 2');
            $template = PrintTemplate::firstOrFail();
            $response = $this->get('/print-templates/'.$template->id.'/report?report=members');
            $response->assertOk();
            file_put_contents($path, $response->getContent());
            $result = IOFactory::createReader('Xlsx')->load($path);
            $this->assertSame('=1+1 Test', $result->getActiveSheet()->getCell('A2')->getValue());
            $this->assertSame('s', $result->getActiveSheet()->getCell('A2')->getDataType());
            $this->assertSame('Second Test', $result->getActiveSheet()->getCell('A3')->getValue());
            $this->assertSame('Footer', $result->getActiveSheet()->getCell('A4')->getValue());
            $result->disconnectWorksheets();
            $empty = $this->get('/print-templates/'.$template->id.'/report?report=members&location=Missing');
            $empty->assertOk();
            file_put_contents($path, $empty->getContent());
            $result = IOFactory::createReader('Xlsx')->load($path);
            $this->assertSame('Footer', $result->getActiveSheet()->getCell('A2')->getValue());
            $result->disconnectWorksheets();
        } finally {
            $book->disconnectWorksheets();
            @unlink($path);
        }
    }

    public function test_pdf_template_generates_multiple_pages_and_supports_layout_changes(): void
    {
        $this->actingAs(User::factory()->create());
        $pdf = new \TCPDF;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(15, 15, 'Group Letterhead');
        $path = tempnam(sys_get_temp_dir(), 'template-test-');
        try {
            $pdf->Output($path, 'F');
            $this->upload($path, 'pdf');
            $template = PrintTemplate::firstOrFail();
            for ($index = 0; $index < 50; $index++) {
                $this->member('Member '.$index, 'Purok 1');
            }
            $response = $this->get('/print-templates/'.$template->id.'/report?report=members');
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            file_put_contents($path, $response->getContent());
            $reader = new Fpdi;
            $this->assertGreaterThan(1, $reader->setSourceFile($path));
            $this->put('/print-templates/'.$template->id, ['name' => 'Renamed', 'category' => 'events', 'pdf_top' => 50, 'pdf_bottom' => 20, 'pdf_margin' => 15])->assertSessionHasNoErrors();
            $this->assertSame('Renamed', $template->fresh()->name);
            $this->assertSame('events', $template->fresh()->category);
        } finally {
            @unlink($path);
        }
    }

    public function test_invalid_uploads_and_guests_are_rejected(): void
    {
        $this->get('/print-templates')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->post('/print-templates', ['name' => 'Image', 'category' => 'members', 'file' => UploadedFile::fake()->create('image.png', 5, 'image/png'), 'pdf_top' => 45, 'pdf_bottom' => 20, 'pdf_margin' => 15])->assertSessionHasErrors('file');
        $this->post('/print-templates', ['name' => 'Broken', 'category' => 'members', 'file' => UploadedFile::fake()->createWithContent('broken.docx', 'not a document'), 'pdf_top' => 45, 'pdf_bottom' => 20, 'pdf_margin' => 15])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('print_templates', 0);
    }

    public function test_multipage_roster_reference_upload_and_reports_do_not_import_members(): void
    {
        $this->actingAs(User::factory()->create());
        $pdf = new \TCPDF;
        $pdf->AddPage();
        $pdf->Write(10, 'Original reference member - must not be imported');
        $pdf->AddPage();
        $pdf->Write(10, 'Second reference page');
        $source = getenv('ROSTER_REFERENCE_PATH')
            ? file_get_contents(getenv('ROSTER_REFERENCE_PATH'))
            : $pdf->Output('reference.pdf', 'S');
        $this->post('/print-templates', [
            'name' => 'Quarterly roster reference', 'category' => 'members',
            'file' => UploadedFile::fake()->createWithContent('roster.pdf', $source),
            'pdf_layout' => 'roster', 'pdf_top' => 45, 'pdf_bottom' => 20, 'pdf_margin' => 15,
        ])->assertSessionHasNoErrors()->assertRedirect('/print-templates');
        $template = PrintTemplate::firstOrFail();
        $this->assertTrue($template->usesRosterLayout());
        $this->assertDatabaseCount('members', 0);
        $this->get('/print-templates')->assertOk()->assertSee('Roster reference');
        $preview = $this->get('/print-templates/'.$template->id.'/preview');
        $preview->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertDatabaseCount('members', 0);
        $this->get('/print-templates/'.$template->id.'/download')->assertContent($source);
        $this->member('Saved Member', '7C');
        $report = $this->get('/print-templates/'.$template->id.'/report?report=members&as_of=2026-09-30&association=Test+Association');
        $report->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertDatabaseCount('members', 1);
        $this->get('/print-templates/'.$template->id.'/report?report=members&as_of=invalid')->assertSessionHasErrors('as_of');
        $this->put('/print-templates/'.$template->id, ['name' => 'Invalid category', 'category' => 'events', 'pdf_top' => 45, 'pdf_bottom' => 20, 'pdf_margin' => 15])->assertSessionHasErrors('pdf_layout');
        if (getenv('ROSTER_REFERENCE_PREVIEW')) {
            file_put_contents(getenv('ROSTER_REFERENCE_PREVIEW'), $report->getContent());
        }
    }

    private function upload(string $path, string $extension): void
    {
        $this->post('/print-templates', [
            'name' => 'Test template',
            'category' => 'members',
            'file' => UploadedFile::fake()->createWithContent('template.'.$extension, file_get_contents($path)),
            'pdf_top' => 45,
            'pdf_bottom' => 20,
            'pdf_margin' => 15,
        ])->assertSessionHasNoErrors()->assertRedirect('/print-templates');
    }

    private function member(string $firstName, string $location): Member
    {
        return Member::create(['first_name' => $firstName, 'last_name' => 'Test', 'birth_date' => '1950-01-01', 'gender' => 'Female', 'location' => $location, 'status' => 'Active', 'registered_at' => '2020-01-01']);
    }
}
