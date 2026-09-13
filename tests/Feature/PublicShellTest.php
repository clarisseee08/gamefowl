<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The chrome every public page carries.
 *
 * The application had no footer at all until this - the catalogue rendered
 * under a 56px bar and simply stopped. A farm's public page with no address
 * and no phone number is a strange thing to hand a customer.
 *
 * THE CONTRAST TO HOLD IN MIND is that the system does not actually know any
 * of this. config('gfms.farm.address') has always defaulted to an empty
 * string, and there was no phone, email or set of visiting hours anywhere. So
 * the footer reads from config and, crucially, must render nothing at all for
 * a value the farm has not supplied - not a blank row, not a dash, not a
 * label with nothing after it.
 */
final class PublicShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_page_carries_the_farm_name(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee(config('gfms.farm.name'));
    }

    public function test_contact_details_appear_when_the_farm_has_supplied_them(): void
    {
        config([
            'gfms.farm.phone' => '0917 123 4567',
            'gfms.farm.email' => 'visit@ssguad.test',
            'gfms.farm.address' => 'Purok 3, San Isidro, Nueva Ecija',
            'gfms.farm.hours' => 'Monday to Saturday, 7am to 4pm',
        ]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('0917 123 4567')
            ->assertSee('visit@ssguad.test')
            ->assertSee('Purok 3, San Isidro, Nueva Ecija')
            ->assertSee('Monday to Saturday, 7am to 4pm');
    }

    /**
     * The half of the rule that is easy to get wrong.
     *
     * An unconfigured farm must not render "Phone" followed by nothing.
     */
    public function test_a_contact_row_the_farm_has_not_supplied_is_omitted_entirely(): void
    {
        config([
            'gfms.farm.phone' => '',
            'gfms.farm.email' => '',
            'gfms.farm.address' => '',
            'gfms.farm.hours' => '',
        ]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('Phone')
            ->assertDontSee('Visiting hours');
    }

    public function test_the_footer_carries_a_copyright_line(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee((string) now()->year);
    }

    public function test_the_header_offers_the_way_to_the_stock_and_to_visiting(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Our stock')
            ->assertSee('Visit us');
    }
}
