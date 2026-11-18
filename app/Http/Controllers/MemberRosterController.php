<?php

namespace App\Http\Controllers;

use App\Models\PrintTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class MemberRosterController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        $request->validate([
            'as_of' => ['nullable', 'date_format:Y-m-d'],
            'association' => ['nullable', 'string', 'max:100'],
            'report_barangay' => ['nullable', 'string', 'max:100'],
            'report_district' => ['nullable', 'string', 'max:100'],
            'organized_at' => ['nullable', 'date_format:Y-m-d'],
            'submitted_by' => ['nullable', 'string', 'max:100'],
            'verified_by' => ['nullable', 'string', 'max:100'],
            'noted_by' => ['nullable', 'string', 'max:100'],
        ]);
        $request->merge(['print' => 1, 'status' => $request->input('status', 'Active')]);
        $data = app(MemberController::class)->index($request)->getData();
        $asOf = Carbon::parse($request->input('as_of') ?: today()->toDateString())->startOfDay();
        $data += [
            'asOf' => $asOf,
            'association' => $request->input('association', 'PUROK 7-C'),
            'reportBarangay' => $request->input('report_barangay', 'Baliok'),
            'reportDistrict' => $request->input('report_district', 'Talomo'),
            'organizedAt' => $request->input('organized_at', '2023-09-09'),
            'submittedBy' => $request->input('submitted_by'),
            'verifiedBy' => $request->input('verified_by'),
            'notedBy' => $request->input('noted_by'),
        ];
        if (! $request->boolean('download')) {
            return view('members.index', $data + ['openRoster' => true]);
        }

        return response(PrintTemplate::rosterPdf($data), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="member-roster.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
