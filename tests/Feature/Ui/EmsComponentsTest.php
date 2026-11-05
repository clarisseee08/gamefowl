<?php

declare(strict_types=1);

namespace Tests\Feature\Ui;

use App\Enums\BroodcockStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The EMS wrappers in resources/views/components/ui - what each one renders,
 * pinned on markup and behaviour rather than on class names, which are free
 * to be retuned.
 */
final class EmsComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_button_is_a_button_and_a_button_with_an_href_is_a_link(): void
    {
        $this->blade('<x-ui.button>Save</x-ui.button>')
            ->assertSee('<button type="button"', escape: false)
            ->assertSee('Save');

        $this->blade('<x-ui.button type="submit">Save</x-ui.button>')
            ->assertSee('<button type="submit"', escape: false);

        $this->blade('<x-ui.button href="/health">View records</x-ui.button>')
            ->assertSee('<a href="/health"', escape: false)
            ->assertDontSee('<button', escape: false);
    }

    public function test_a_busy_button_is_disabled_and_says_so(): void
    {
        $this->blade('<x-ui.button :loading="true">Saving...</x-ui.button>')
            ->assertSee('disabled="disabled"', escape: false)
            ->assertSee('aria-busy="true"', escape: false);

        $this->blade('<x-ui.button>Save</x-ui.button>')
            ->assertDontSee('aria-busy', escape: false)
            ->assertDontSee('disabled="disabled"', escape: false);
    }

    public function test_an_icon_draws_its_glyph_and_is_hidden_unless_it_is_named(): void
    {
        $this->blade('<x-ui.icon name="plus" class="size-4" />')
            ->assertSee('<path d="M5 12h14"/>', escape: false)
            ->assertSee('aria-hidden="true"', escape: false);

        $this->blade('<x-ui.icon name="trash-2" label="Delete" />')
            ->assertSee('role="img"', escape: false)
            ->assertSee('aria-label="Delete"', escape: false);
    }

    public function test_an_input_is_labelled_and_described_by_its_help(): void
    {
        $this->blade('<x-ui.input wire:model="band_number" label="Band number" help="Leave empty if not banded yet." required />')
            ->assertSee('<label for="band_number"', escape: false)
            ->assertSee('id="band_number"', escape: false)
            ->assertSee('wire:model="band_number"', escape: false)
            ->assertSee('aria-describedby="band_number-help"', escape: false)
            ->assertSee('aria-required="true"', escape: false)
            ->assertSee('Leave empty if not banded yet.');
    }

    public function test_an_input_shows_its_validation_error_in_place_of_its_help(): void
    {
        $this->withViewErrors(['weight' => 'Weight cannot be a negative number.']);

        $this->blade('<x-ui.input wire:model="weight" label="Weight" help="In kilograms." />')
            ->assertSee('Weight cannot be a negative number.')
            ->assertSee('aria-invalid="true"', escape: false)
            ->assertSee('aria-describedby="weight-error"', escape: false)
            ->assertSee('role="alert"', escape: false)
            ->assertDontSee('In kilograms.');
    }

    public function test_a_field_reads_its_error_under_a_key_that_differs_from_its_id(): void
    {
        $this->withViewErrors(['sire_id' => 'Choose the sire.']);

        $this->blade('<x-ui.input id="sire_choice" error="sire_id" label="Sire" />')
            ->assertSee('Choose the sire.')
            ->assertSee('aria-describedby="sire_choice-error"', escape: false);
    }

    public function test_a_native_select_lists_its_placeholder_then_its_options(): void
    {
        $this->blade('<x-ui.native-select wire:model="sex" label="Sex" placeholder="Choose..." :options="[\'male\' => \'Cock\', \'female\' => \'Hen\']" />')
            ->assertSeeInOrder(['<option value="">Choose...</option>', '<option value="male">Cock</option>', '<option value="female">Hen</option>'], escape: false);
    }

    public function test_a_textarea_keeps_its_content(): void
    {
        $this->blade('<x-ui.textarea wire:model="remarks" label="Remarks">Calm, good feather.</x-ui.textarea>')
            ->assertSee('Calm, good feather.')
            ->assertSee('<label for="remarks"', escape: false);
    }

    public function test_a_partly_selected_checkbox_reads_as_mixed(): void
    {
        $this->blade('<x-ui.checkbox :indeterminate="true" aria-label="Select all" />')
            ->assertSee('data-indeterminate="true"', escape: false)
            ->assertSee('aria-checked="mixed"', escape: false);

        $this->blade('<x-ui.checkbox label="For sale" />')
            ->assertSee('For sale')
            ->assertDontSee('aria-checked', escape: false);
    }

    public function test_a_switch_is_a_switch(): void
    {
        $this->blade('<x-ui.switch label="Show in the catalogue" />')
            ->assertSee('role="switch"', escape: false)
            ->assertSee('Show in the catalogue');
    }

    public function test_a_kpi_tile_colours_its_change_by_whether_a_rise_is_good(): void
    {
        $this->blade('<x-ui.kpi-tile label="Fertility" value="75%" :delta="4.2" delta-unit=" pts" />')
            ->assertSeeInOrder(['75%', 'Up', '4.2 pts'])
            ->assertSee('bg-success-bg', escape: false);

        $this->blade('<x-ui.kpi-tile label="Deaths" value="3" :delta="2" :higher-is-better="false" />')
            ->assertSeeInOrder(['Up', '2'])
            ->assertSee('bg-destructive-bg', escape: false);

        $this->blade('<x-ui.kpi-tile label="Hatch rate" value="81%" :delta="0" />')
            ->assertSee('no change');
    }

    public function test_a_kpi_tile_without_a_prior_period_shows_no_change_chip(): void
    {
        $this->blade('<x-ui.kpi-tile label="Matings" value="12" />')
            ->assertDontSee('no change')
            ->assertDontSee('Up')
            ->assertDontSee('Down');
    }

    public function test_a_kpi_tile_with_no_figure_says_so(): void
    {
        $this->blade('<x-ui.kpi-tile label="Pedigree gaps" />')
            ->assertSee('No data')
            ->assertDontSee('&mdash;', escape: false);
    }

    public function test_a_loading_kpi_tile_is_busy_and_names_what_is_loading(): void
    {
        $this->blade('<x-ui.kpi-tile label="Fertility" :loading="true" />')
            ->assertSee('aria-busy="true"', escape: false)
            ->assertSee('Loading Fertility');
    }

    public function test_a_kpi_tile_with_a_drill_down_is_a_link_that_names_its_action(): void
    {
        $this->blade('<x-ui.kpi-tile label="Overdue" value="4" href="/health/schedule" action="Show the overdue vaccinations" />')
            ->assertSee('href="/health/schedule"', escape: false)
            ->assertSee('title="Show the overdue vaccinations"', escape: false);
    }

    public function test_a_summary_card_reads_a_signed_footer_as_a_trend(): void
    {
        $this->blade('<x-ui.summary-card title="Birds" value="26" footer="+3 since last month" />')
            ->assertSeeInOrder(['+3', 'since last month'])
            ->assertSee('M7 7h10v10', escape: false);

        $this->blade('<x-ui.summary-card title="Deaths" value="3" footer="-1 against last year" />')
            ->assertSee('m7 7 10 10', escape: false);

        $this->blade('<x-ui.summary-card title="Due" value="7" footer="In the next 30 days" />')
            ->assertSee('In the next 30 days')
            ->assertDontSee('M7 7h10v10', escape: false)
            ->assertDontSee('m7 7 10 10', escape: false);
    }

    public function test_a_flat_footer_of_zero_is_not_a_trend(): void
    {
        $this->blade('<x-ui.summary-card title="Birds" value="26" footer="+0 since last month" />')
            ->assertDontSee('M7 7h10v10', escape: false);
    }

    public function test_a_card_with_an_href_is_one_link(): void
    {
        $this->blade('<x-ui.card href="/settings"><x-ui.card.header><x-ui.card.title>Farm profile</x-ui.card.title></x-ui.card.header></x-ui.card>')
            ->assertSee('<a href="/settings"', escape: false)
            ->assertSee('<h2', escape: false)
            ->assertSee('Farm profile');
    }

    public function test_a_progress_bar_clamps_its_value(): void
    {
        $this->blade('<x-ui.progress :value="140" label="Over" />')
            ->assertSee('value="100"', escape: false)
            ->assertSee('aria-label="Over"', escape: false);
    }

    public function test_a_status_badge_takes_an_enums_badge_class_as_its_tone(): void
    {
        $this->blade('<x-ui.status-badge :tone="$tone">Deceased</x-ui.status-badge>', ['tone' => BroodcockStatus::Deceased->badgeClasses()])
            ->assertSee('Deceased')
            ->assertSee('text-destructive', escape: false);
    }

    public function test_a_count_over_ninety_nine_reads_ninety_nine_plus(): void
    {
        $this->blade('<x-ui.count :value="140" />')->assertSee('99+');
        $this->blade('<x-ui.count :value="7" />')->assertSee('7')->assertDontSee('99+');
    }

    public function test_an_avatar_without_a_photo_shows_the_initials(): void
    {
        $this->blade('<x-ui.avatar name="Salvador Santos Guadalupe" />')->assertSee('SG');
        $this->blade('<x-ui.avatar name="Owner" />')->assertSee('O');
    }

    public function test_tabs_are_a_tablist_with_one_selected_tab_in_the_tab_order(): void
    {
        $segments = [
            ['key' => 'overview', 'label' => 'Overview'],
            ['key' => 'health', 'label' => 'Health', 'count' => 140],
        ];

        $this->blade('<x-ui.tabs :segments="$segments" active="health" model="tab" label="Bird record sections" />', ['segments' => $segments])
            ->assertSee('role="tablist"', escape: false)
            ->assertSee('aria-label="Bird record sections"', escape: false)
            ->assertSee('id="tabs-tab-health" aria-controls="tabs-tab-panel"', escape: false)
            ->assertSeeInOrder(['id="tabs-tab-overview"', 'aria-selected="false" tabindex="-1"', 'id="tabs-tab-health"', 'aria-selected="true" tabindex="0"'], escape: false)
            ->assertSee("wire:click=\"\$set('tab', 'overview')\"", escape: false)
            ->assertSee('99+');
    }

    public function test_a_dropdown_menu_announces_itself_and_its_items(): void
    {
        $this->blade(<<<'BLADE'
            <x-ui.dropdown-menu label="Actions for Tandang">
                <x-slot:trigger aria-label="Actions for Tandang">More</x-slot:trigger>
                <x-ui.dropdown-menu.item icon="pencil">Edit</x-ui.dropdown-menu.item>
                <x-ui.dropdown-menu.separator />
                <x-ui.dropdown-menu.item icon="trash-2" :destructive="true">Delete</x-ui.dropdown-menu.item>
                <x-ui.dropdown-menu.item type="radio" :checked="true">Light</x-ui.dropdown-menu.item>
            </x-ui.dropdown-menu>
            BLADE)
            ->assertSee('aria-haspopup="menu"', escape: false)
            ->assertSee('role="menu"', escape: false)
            ->assertSee('aria-label="Actions for Tandang"', escape: false)
            ->assertSeeInOrder(['role="menuitem"', 'Edit', 'role="separator"', 'role="menuitem"', 'Delete'], escape: false)
            ->assertSee('role="menuitemradio"', escape: false)
            ->assertSee('aria-checked="true"', escape: false);
    }

    public function test_a_tooltip_is_a_tooltip(): void
    {
        $this->blade('<x-ui.tooltip text="Edit this record"><button aria-label="Edit this record">E</button></x-ui.tooltip>')
            ->assertSee('role="tooltip"', escape: false)
            ->assertSee('Edit this record');
    }

    public function test_a_form_dialog_is_a_labelled_native_dialog_that_submits_to_livewire(): void
    {
        $this->blade('<x-ui.form-dialog title="Edit health record" model="showForm" submit="save" submit-label="Save">Fields</x-ui.form-dialog>')
            ->assertSee('<dialog', escape: false)
            ->assertSee('wire:ignore.self', escape: false)
            ->assertSee('aria-labelledby="dialog-showForm-title"', escape: false)
            ->assertSee('<h2 id="dialog-showForm-title"', escape: false)
            ->assertSee("emsDialog({ model: 'showForm'", escape: false)
            ->assertSee('wire:submit="save"', escape: false)
            ->assertSeeInOrder(['Edit health record', 'Fields', 'Cancel', 'Save', 'Saving...']);
    }

    public function test_a_sheet_is_a_native_dialog_from_the_side(): void
    {
        $this->blade('<x-ui.sheet title="Edit gamefowl" model="showSheet" submit="save">Fields</x-ui.sheet>')
            ->assertSee('data-side="right"', escape: false)
            ->assertSee('aria-labelledby="sheet-showSheet-title"', escape: false)
            ->assertSee('wire:submit="save"', escape: false)
            ->assertSeeInOrder(['Edit gamefowl', 'Fields', 'Cancel', 'Save']);
    }

    public function test_the_confirm_dialog_is_an_alert_dialog_opened_by_an_event(): void
    {
        $this->blade('<x-ui.confirm-dialog />')
            ->assertSee('role="alertdialog"', escape: false)
            ->assertSee('x-on:ems-confirm.window="ask($event.detail)"', escape: false);
    }

    public function test_the_toaster_carries_a_redirects_flash_into_the_page(): void
    {
        session()->flash('success', 'Health record saved.');

        $this->blade('<x-ui.toaster />')
            ->assertSee('data-flash="success"', escape: false)
            ->assertSee('Health record saved.')
            ->assertSee('aria-live="polite"', escape: false);
    }

    public function test_the_page_header_opens_on_a_small_title_and_its_figure(): void
    {
        $this->blade('<x-ui.page-header title="Gamefowl" figure="30" figure-label="birds on record"><x-slot:actions><a href="/broodcocks/create">Add gamefowl</a></x-slot:actions></x-ui.page-header>')
            ->assertSeeInOrder(['<h1', 'Gamefowl', '30', 'birds on record', 'Add gamefowl'], escape: false);
    }

    public function test_the_old_anchor_block_renders_the_ems_header_without_its_eyebrow(): void
    {
        $this->blade('<x-anchor-block eyebrow="Manage" title="Health Records" figure="12" figure-label="records on file" />')
            ->assertSee('Health Records')
            ->assertSeeInOrder(['12', 'records on file'])
            ->assertDontSee('Manage');
    }

    public function test_a_row_action_is_a_named_icon_with_a_tooltip(): void
    {
        $this->blade('<x-ui.row-action icon="pencil" label="Edit Tandang" href="/broodcocks/1/edit" />')
            ->assertSee('<a href="/broodcocks/1/edit" aria-label="Edit Tandang"', escape: false)
            ->assertSee('role="tooltip"', escape: false);

        $this->blade('<x-ui.row-action icon="trash-2" label="Delete Tandang" :destructive="true" wire:click="confirmDelete(1)" />')
            ->assertSee('<button type="button" aria-label="Delete Tandang"', escape: false)
            ->assertSee('wire:click="confirmDelete(1)"', escape: false);
    }

    public function test_the_design_page_shows_the_ems_components_to_staff(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('design'))
            ->assertOk()
            ->assertSee('EMS components')
            ->assertSee('KPI tile and summary card')
            ->assertSee('Weight cannot be a negative number.');
    }
}
