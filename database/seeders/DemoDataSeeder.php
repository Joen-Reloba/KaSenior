<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $firstNames = ['Jose', 'Maria', 'Pedro', 'Luz', 'Antonio', 'Carmen', 'Ramon', 'Rosa', 'Manuel', 'Elena'];
            $lastNames = ['Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza'];
            $members = [];

            for ($index = 0; $index < 50; $index++) {
                $members[] = Member::firstOrCreate([
                    'address' => 'Demo member '.($index + 1).' — sample address',
                ], [
                    'first_name' => $firstNames[$index % 10],
                    'last_name' => $lastNames[intdiv($index, 10)],
                    'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                    'birth_date' => today()->subYears(60 + $index % 36)->subDays($index * 3),
                    'location' => 'Purok '.(1 + $index % 7),
                    'status' => $index >= 45 ? 'Deceased' : 'Active',
                    'registered_at' => today()->subMonths(12 + $index % 24),
                    'deceased_at' => $index >= 45 ? today()->subDays($index) : null,
                    'emergency_contact' => 'Sample contact — demo data',
                ]);
            }

            $eventNames = ['Monthly Assembly', 'Wellness Day', 'Community Lunch', 'Anniversary Celebration', 'Holiday Gathering'];
            foreach ($eventNames as $eventIndex => $name) {
                $event = Event::firstOrCreate(['name' => '[Demo] '.$name], [
                    'event_date' => today()->subDays(120 - $eventIndex * 35),
                    'description' => 'Sample event for exploring reports and payment tracking.',
                    'amount_cents' => [5000, 10000, 15000, 20000, 25000][$eventIndex],
                ]);

                foreach ($members as $memberIndex => $member) {
                    if ($member->status !== 'Active') {
                        continue;
                    }

                    $paid = ($memberIndex + $eventIndex) % 3 !== 0;
                    $event->contributions()->firstOrCreate(['member_id' => $member->id], [
                        'amount_cents' => $paid ? $event->amount_cents : 0,
                        'paid_at' => $paid ? today()->subDays($memberIndex % 20) : null,
                    ]);
                }
            }
        });
    }
}
