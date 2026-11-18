<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\PrintTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrintTemplateController extends Controller
{
    public function index(): View
    {
        return view('templates', ['templates' => PrintTemplate::select('id', 'name', 'category', 'format', 'pdf_layout', 'pdf_top', 'pdf_bottom', 'pdf_margin', 'created_at')->orderBy('category')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::in(array_keys(PrintTemplate::CATEGORIES))],
            'file' => ['required', 'file', 'max:2048', 'mimes:docx,xlsx,pdf', 'extensions:docx,xlsx,pdf'],
            'pdf_layout' => ['sometimes', 'required', Rule::in(['letterhead', 'roster'])],
            'pdf_top' => ['required', 'integer', 'between:5,200'],
            'pdf_bottom' => ['required', 'integer', 'between:5,100'],
            'pdf_margin' => ['required', 'integer', 'between:5,50'],
        ]);
        $template = new PrintTemplate(collect($data)->except('file')->all());
        $template->format = strtolower($request->file('file')->getClientOriginalExtension());
        $template->content = base64_encode($request->file('file')->getContent());
        $this->generate($template, $this->sampleHeading($template), [$this->sampleRow()]);
        $template->save();

        return redirect()->route('templates.index')->with('success', 'Template saved. It is now available in Print options.');
    }

    public function update(Request $request, PrintTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::in(array_keys(PrintTemplate::CATEGORIES))],
            'pdf_layout' => ['sometimes', 'required', Rule::in(['letterhead', 'roster'])],
            'pdf_top' => ['required', 'integer', 'between:5,200'],
            'pdf_bottom' => ['required', 'integer', 'between:5,100'],
            'pdf_margin' => ['required', 'integer', 'between:5,50'],
        ]);
        $template->fill($data);
        $this->generate($template, $this->sampleHeading($template), [$this->sampleRow()]);
        $template->save();

        return back()->with('success', 'Template settings saved.');
    }

    public function destroy(PrintTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted.');
    }

    public function download(PrintTemplate $template): Response
    {
        return $this->documentResponse($template, base64_decode($template->content, true));
    }

    public function preview(PrintTemplate $template): Response
    {
        return $this->documentResponse($template, $this->generate($template, $this->sampleHeading($template), [$this->sampleRow()]));
    }

    public function report(Request $request, PrintTemplate $template): Response
    {
        $request->validate(['report' => ['required', 'in:members,events,census'], 'event' => ['required_if:report,events', 'nullable', 'integer']]);
        if ($template->category !== $request->input('report')) {
            throw ValidationException::withMessages(['template' => 'Choose a template matching this report category.']);
        }
        if ($template->usesRosterLayout()) {
            $request->merge(['download' => 1]);

            return app(MemberRosterController::class)($request);
        }
        $request->merge(['print' => 1]);
        $event = null;
        $title = 'Members';
        if ($request->input('report') === 'events') {
            $event = Event::findOrFail($request->integer('event'));
            $data = app(EventController::class)->show($request, $event)->getData();
            $records = $data['contributions'];
            $title = $event->name.' — Collection Report';
        } elseif ($request->input('report') === 'census') {
            $data = app(ReportController::class)->census($request)->getData();
            $records = $data['members'];
            $title = 'Census Report';
        } else {
            $data = app(MemberController::class)->index($request)->getData();
            $records = $data['members'];
        }
        $rows = [];
        foreach ($records as $record) {
            $member = $event ? $record->member : $record;
            $rows[] = [
                'number' => (string) (count($rows) + 1),
                'member_name' => $member->full_name,
                'first_name' => $member->first_name,
                'middle_name' => $member->middle_name ?? '',
                'last_name' => $member->last_name,
                'name_extension' => $member->name_extension ?? '',
                'purok' => $member->location,
                'barangay' => $member->barangay,
                'district' => $member->district,
                'senior_citizen_id' => $member->senior_citizen_id ?? '',
                'id_issued_at' => $member->id_issued_at?->format('Y-m-d') ?? '',
                'mobile' => $member->mobile ?? '',
                'age' => (string) $member->age,
                'gender' => $member->gender,
                'location' => $member->location,
                'status' => $member->status,
                'birth_date' => $member->birth_date->format('Y-m-d'),
                'amount_paid' => $event ? number_format($record->amount_cents / 100, 2) : '',
                'balance' => $event ? number_format(max(0, $event->amount_cents - $record->amount_cents) / 100, 2) : '',
                'paid_at' => $event ? ($record->paid_at?->format('Y-m-d') ?? '') : '',
            ];
        }
        $heading = [
            'report_title' => $title,
            'report_date' => today()->format('Y-m-d'),
            'event_name' => $event?->name ?? '',
            'event_date' => $event?->event_date->format('Y-m-d') ?? '',
            'total_members' => (string) count($rows),
        ];

        return $this->documentResponse($template, $this->generate($template, $heading, $rows));
    }

    /** @return array<string, string> */
    private function sampleHeading(PrintTemplate $template): array
    {
        return ['report_title' => 'Sample '.(PrintTemplate::CATEGORIES[$template->category] ?? '').' Report', 'report_date' => today()->format('Y-m-d'), 'event_name' => $template->category === 'events' ? 'Sample Event' : '', 'event_date' => $template->category === 'events' ? today()->format('Y-m-d') : '', 'total_members' => '1'];
    }

    /** @return array<string, string> */
    private function sampleRow(): array
    {
        return ['number' => '1', 'member_name' => 'Sample Member', 'first_name' => 'Sample', 'middle_name' => '', 'last_name' => 'Member', 'name_extension' => '', 'purok' => '7C', 'barangay' => 'Baliok', 'district' => 'Talomo', 'senior_citizen_id' => 'SAMPLE-001', 'id_issued_at' => '2026-01-01', 'mobile' => '', 'age' => '70', 'gender' => 'Female', 'location' => 'Purok 1', 'status' => 'Active', 'birth_date' => '1956-01-01', 'amount_paid' => '100.00', 'balance' => '0.00', 'paid_at' => today()->format('Y-m-d')];
    }

    /** @param array<string, string> $heading
     * @param  list<array<string, string>>  $rows
     */
    private function generate(PrintTemplate $template, array $heading, array $rows): string
    {
        try {
            return $template->generate($heading, $rows);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'This template could not be processed. Use an unencrypted DOCX, XLSX, or PDF. For a completed member roster, choose Roster reference. PDF files with unsupported compression must be re-exported as PDF 1.4.']);
        }
    }

    private function documentResponse(PrintTemplate $template, string $content): Response
    {
        $mime = match ($template->format) {
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
        };
        $disposition = $template->format === 'pdf' ? 'inline' : 'attachment';

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.(Str::slug($template->name) ?: 'report').'.'.$template->format.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
