<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A request from the public to come and see the birds.
 *
 * THIS IS THE FIRST TABLE A STRANGER CAN WRITE TO. Every other write in this
 * system sits behind auth and a Policy; this one is reachable by anyone who
 * opens the farm's front page. The protections that follow from that - a rate
 * limit and a honeypot - live in Appointments\RequestForm, because they are
 * about the request rather than the record.
 *
 * DO NOT RUN THIS ON PRODUCTION AS PART OF A DEPLOY. Development and
 * production are the same Supabase project and RUN_MIGRATIONS is false on
 * Render for exactly that reason. Ship it, then set RUN_MIGRATIONS=true,
 * deploy, confirm, and set it back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->string('name', 120);

            /*
             * The reply channel, and the reason it is required while email is
             * not. MAIL_* is unset, so the farm cannot write to an address
             * even if it has one. It rings the number instead.
             */
            $table->string('contact_number', 40);

            /*
             * Nullable on purpose. Requiring an address the system cannot send
             * to would collect it under the implication that it will be used.
             */
            $table->string('email', 190)->nullable();

            $table->date('preferred_date');

            /*
             * Morning or afternoon rather than a time. A farm visit is not
             * booked to the minute, and a time field invites a precision the
             * arrangement does not have - the owner confirms the actual hour
             * on the phone.
             */
            $table->enum('preferred_time', ['morning', 'afternoon']);

            $table->unsignedSmallInteger('party_size')->default(1);

            $table->text('message')->nullable();

            /*
             * Which bird prompted this, when the visitor asked from a bird's
             * page. nullOnDelete, never cascade: someone is still expecting a
             * phone call, and removing a bird from the records must not
             * quietly cancel a visit.
             */
            $table->foreignId('broodcock_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('status', AppointmentStatus::values())
                ->default(AppointmentStatus::Pending->value);

            /* Who acted on it and when - the audit shape used elsewhere here. */
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();

            /* The queue opens on pending, ordered by when people want to come. */
            $table->index('status');
            $table->index('preferred_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
