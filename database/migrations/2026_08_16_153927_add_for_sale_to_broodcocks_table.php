<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a bird is offered for sale.
 *
 * Added because the farm's own records track it — every bird they supplied
 * carries a "For Sale / Not For Sale" line. It had nowhere to go in the schema,
 * and dropping a field the client actively maintains would have quietly lost
 * part of their data model.
 *
 * Defaults to FALSE. On a breeding farm the safe default is "not for sale":
 * a bird wrongly listed as available is a customer conversation the farm did
 * not intend, whereas a bird wrongly withheld is merely invisible until someone
 * ticks the box.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broodcocks', function (Blueprint $table): void {
            $table->boolean('for_sale')->default(false)->after('status');

            // The catalogue is the customer-facing surface, so "show me what is
            // available" is the query this column exists to serve.
            $table->index(['for_sale', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('broodcocks', function (Blueprint $table): void {
            $table->dropIndex(['for_sale', 'status']);
            $table->dropColumn('for_sale');
        });
    }
};
