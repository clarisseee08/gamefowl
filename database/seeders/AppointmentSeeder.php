<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Six visit requests, one in every state the queue can show.
 *
 * The visit queue was the only screen in the application with no seeder behind
 * it, so a fresh install opened it to an empty table. That is a poor way to
 * meet a feature - and it hid a real bug for a while, because the actions
 * column cannot be wrong on a screen that never renders a row.
 *
 * WRITTEN OUT RATHER THAN FAKED, for the same reason the other seeders are:
 * these are read by a person deciding whether the screen works, so a request
 * that says "Interested in the Sweater cocks, driving from Cabanatuan" tells
 * them more than six rows of Lorem. Names and numbers are invented; the places
 * are real towns in Nueva Ecija, which is where this farm is.
 *
 * ONE OF EACH STATE ON PURPOSE. Pending is what the farm works through,
 * Confirmed carries "They came", Declined can be reinstated, and Completed is
 * the one that offers nothing at all - which is exactly the row that was being
 * offered Confirm and Decline before the states were written out properly.
 */
final class AppointmentSeeder extends Seeder
{
    /**
     * @var list<array{
     *     name: string, contact: string, email: ?string, days: int, time: string,
     *     party: int, message: ?string, status: AppointmentStatus, bird: ?string
     * }>
     */
    private const REQUESTS = [
        [
            'name' => 'Marites Santos',
            'contact' => '0917 555 1234',
            'email' => 'marites.santos@example.com',
            'days' => 9,
            'time' => 'morning',
            'party' => 3,
            'message' => 'Interested in the Sweater cocks. Driving over from Cabanatuan.',
            'status' => AppointmentStatus::Pending,
            'bird' => null,
        ],
        [
            'name' => 'Rodel Bautista',
            'contact' => '0918 442 0071',
            'email' => null,
            'days' => 3,
            'time' => 'afternoon',
            'party' => 1,
            'message' => 'Would like to see the Kelso line before the December derby.',
            'status' => AppointmentStatus::Pending,
            'bird' => null,
        ],
        [
            'name' => 'Ernesto Villanueva',
            'contact' => '0999 218 7745',
            'email' => 'e.villanueva@example.net',
            'days' => 16,
            'time' => 'morning',
            'party' => 2,
            'message' => 'Bringing my son. He is starting his own pen in Talavera.',
            'status' => AppointmentStatus::Confirmed,
            'bird' => null,
        ],
        [
            'name' => 'Divina Ocampo',
            'contact' => '0927 330 9812',
            'email' => null,
            'days' => 5,
            'time' => 'afternoon',
            'party' => 4,
            'message' => null,
            'status' => AppointmentStatus::Confirmed,
            'bird' => null,
        ],
        [
            'name' => 'Jun Mercado',
            'contact' => '0915 876 2240',
            'email' => 'jun.mercado@example.org',
            'days' => 2,
            'time' => 'morning',
            'party' => 8,
            'message' => 'Eight of us from the Guimba association.',
            'status' => AppointmentStatus::Declined,
            'bird' => null,
        ],
        [
            'name' => 'Aurelio Ramos',
            'contact' => '0906 771 5583',
            'email' => null,
            'days' => 21,
            'time' => 'morning',
            'party' => 2,
            'message' => 'Came last season. Wants to see how the young stock turned out.',
            'status' => AppointmentStatus::Completed,
            'bird' => null,
        ],
    ];

    public function run(): void
    {
        /*
         * The first bird on the farm stands in for "asked about a specific
         * bird", so the About column has something to render besides "The farm
         * generally". Nullable throughout: this seeder must not depend on
         * BroodcockSeeder having run.
         */
        $bird = Broodcock::query()->farmStock()->orderBy('id')->first();

        // handled_by is the farm's own record of who decided, so a handled
        // request needs somebody to have decided it.
        $decider = User::query()->where('role', 'owner')->orderBy('id')->first();

        foreach (self::REQUESTS as $index => $request) {
            $handled = $request['status'] !== AppointmentStatus::Pending;

            $appointment = Appointment::query()->make([
                'name' => $request['name'],
                'contact_number' => $request['contact'],
                'email' => $request['email'],
                'preferred_date' => now()->addDays($request['days'])->toDateString(),
                'preferred_time' => $request['time'],
                'party_size' => $request['party'],
                'message' => $request['message'],
                // Every other row asks about the farm generally, which is the
                // commoner case and the one with its own empty-ish state.
                'broodcock_id' => $index === 0 ? $bird?->id : null,
            ]);

            /*
             * Assigned rather than passed to make(): status, handled_by and
             * handled_at are outside $fillable precisely so that nothing a
             * visitor submits can reach them. The seeder respects the same
             * boundary the Livewire component does.
             */
            $appointment->status = $request['status'];
            $appointment->handled_by = $handled ? $decider?->id : null;
            $appointment->handled_at = $handled ? now()->subDays(2) : null;

            $appointment->save();
        }
    }
}
