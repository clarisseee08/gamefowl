# GFMS Coding Conventions

Every module in this system follows these conventions. They exist so that five
feature verticals written in parallel read as one codebase rather than five.

**Stack, confirmed:** Laravel 12.66.0 · PHP 8.2.12 · Livewire **4.4** · Tailwind
**v4** (CSS-first, no `tailwind.config.js`) · PHPUnit 11.5 (**not Pest**) ·
PostgreSQL 17.6 on Supabase · Laravel Pint 1.30.

---

## 1. Where each kind of code lives

| Concern | Location | Notes |
|---|---|---|
| Eloquent models | `app/Models/` | `$fillable` always. **Never** `$guarded = []`. |
| Backed enums | `app/Enums/` | One file per enum. |
| Business logic | `app/Actions/<Domain>/` | Single-purpose invokable-style classes. |
| Validation | `app/Http/Requests/` | Form Requests only. |
| Authorization | `app/Policies/` | One policy per model. |
| Livewire components | `app/Livewire/<Domain>/` | **Class-based** - see §4. |
| Livewire views | `resources/views/livewire/<domain>/` | Mirrors the component namespace. |
| Blade layouts | `resources/views/layouts/` | `app.blade.php`, `guest.blade.php`. |
| Reusable Blade UI | `resources/views/components/` | Anonymous components. |
| PDF report templates | `resources/views/reports/pdf/` | **CSS 2.1 only** - see §8. |
| Tests | `tests/Feature/`, `tests/Unit/` | PHPUnit, extend `Tests\TestCase`. |

---

## 2. PHP style

- `declare(strict_types=1);` at the top of **every** PHP file in `app/`.
- Typed properties, typed parameters, typed return values. No untyped signatures.
- Run `vendor/bin/pint` before every commit. Pint is the arbiter of formatting -
  do not hand-format around it.
- Import classes with `use`; do not write fully-qualified names inline.
- Prefer early returns over nested conditionals.

### Models

```php
final class Example extends Model      // 'final' unless something must extend it
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['a', 'b'];

    /** Laravel 12 uses the casts() METHOD, not a $casts array. */
    protected function casts(): array
    {
        return ['status' => Status::class, 'happened_on' => 'date'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['a', 'b'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('example');
    }
}
```

**Rules:**
- Soft deletes on every domain model. Nothing is ever hard-deleted in a system
  whose selling point is an audit trail.
- `LogsActivity` on every domain model, with an explicit `logOnly()` allow-list.
  Never log password hashes or tokens.
- Derived values are **methods or accessors, never columns**. Age comes from
  `date_hatched`; fertility and hatch rates come from the egg counts. A stored
  derived value can contradict its own inputs.
- Filter scopes take a nullable argument and **return early when it is empty**,
  so a Livewire component can pass all its filters unconditionally:

```php
public function scopeStatus(Builder $query, ?string $status): void
{
    if ($status === null || $status === '') {
        return;
    }
    $query->where('status', $status);
}
```

- Postgres text search uses `ilike` (case-insensitive), not `like`.

---

## 3. Actions - where business logic goes

Anything that writes to more than one table, or encodes a domain rule, is an
Action. Controllers and Livewire components orchestrate; they do not contain
business logic.

```php
namespace App\Actions\Mortality;

final class RecordMortality
{
    public function handle(Broodcock $broodcock, array $data, User $actor): MortalityRecord
    {
        return DB::transaction(function () use ($broodcock, $data, $actor) {
            $record = $broodcock->mortalityRecord()->create([...$data, 'recorded_by' => $actor->id]);
            $broodcock->update(['status' => BroodcockStatus::Deceased]);

            return $record;
        });
    }
}
```

**Rules:**
- One public method, named `handle()`.
- **Every multi-table write is wrapped in `DB::transaction()`.** Non-negotiable.
- Actions receive already-validated data. They do not validate.
- Actions are resolved from the container by type-hint, not with `new`.

---

## 4. Livewire 4 - class-based components only

Livewire 4 defaults to single-file components stored at
`resources/views/components/⚡name.blade.php` (yes, with a literal emoji in the
filename). **We do not use that format.** `config/livewire.php` is set to
`make_command.type = 'class'` and `emoji = false`, giving the familiar layout:

```
app/Livewire/Broodcocks/Index.php
resources/views/livewire/broodcocks/index.blade.php
```

Reasons: PSR-4 autoloading keeps working, and Pint does not currently format the
PHP block inside single-file components.

```php
namespace App\Livewire\Broodcocks;

final class Index extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $status = '';

    public function updating($property): void
    {
        // Any filter change must return to page 1.
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    #[Computed]
    public function rows()
    {
        return Broodcock::query()
            ->with(['pen', 'primaryPhoto'])   // eager-load - never N+1
            ->search($this->search)
            ->status($this->status)
            ->latest()
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.broodcocks.index');
    }
}
```

**Livewire 4 specifics that differ from v3 tutorials:**
- Routes use `Route::livewire('/path', Component::class)`, not `Route::get()`.
- Component tags **must be closed**: `<livewire:foo />`.
- `wire:model` is deferred by default; use `wire:model.live` for live binding.
- v3's `wire:model.blur` is now `wire:model.live.blur`.
- `#[Computed]` properties are accessed as `$this->rows` in Blade.
- **Volt is dead.** Do not use it.

**Every list is paginated. Every relation shown in a list is eager-loaded.**
Supabase is a network hop away, so one N+1 loop is far more expensive here than
against a local database.

---

## 5. Validation - Form Requests

```php
final class StoreBroodcockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Broodcock::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'band_number' => ['nullable', 'string', 'max:64', Rule::unique('broodcocks')],
            'sex' => ['required', Rule::enum(Sex::class)],
        ];
    }

    public function attributes(): array
    {
        return ['band_number' => 'band number'];   // plain words in error messages
    }
}
```

Livewire components that do not go through a Form Request must still use
`$this->validate()` with the same rules - **extract shared rules to a static
method on the Form Request** rather than duplicating them.

Error messages are read by farm staff with low technical literacy: write
"Please enter the band number" rather than "The band_number field is required."

---

## 6. Authorization - Policies, always server-side

- One policy per model in `app/Policies/`. Laravel 12 auto-discovers them.
- **Every** action is checked: `viewAny`, `view`, `create`, `update`, `delete`,
  `restore`.
- Hiding a button in Blade is *not* authorization. The policy is the gate; the
  hidden button is only a courtesy.
- The role matrix:

| | owner | staff | customer |
|---|---|---|---|
| View broodcocks | ✅ | ✅ | ✅ (public fields only) |
| Create / update records | ✅ | ✅ | ❌ |
| Delete anything | ✅ | ❌ | ❌ |
| Manage users | ✅ | ❌ | ❌ |
| Internal remarks, cost data | ✅ | ✅ | ❌ |

Customers must never see internal remarks, user accounts, or cost data - filter
those at the query/view layer, not just visually.

---

## 7. Blade and UI

Users are Filipino farm staff with low technical literacy. That drives real
constraints, not just preferences:

- **Plain labels.** "Band Number", never "UID". "Date Hatched", never "DOH".
- **Large touch targets** - minimum `py-2.5 px-4` on buttons and links.
- **Inline validation** in readable language, next to the field.
- **Confirmation dialogs** on every destructive action, naming the record.
- **Empty states that say what to do next**, never a blank table. e.g.
  "No broodcocks yet. Click 'Add Broodcock' to record your first bird."
- Every table needs a mobile story - stack to cards below `sm:`.

Tailwind v4: theme customisation goes in `resources/css/app.css` inside
`@theme { }`. There is no `tailwind.config.js` and no PostCSS config.

Status and grade colours come from the enum's `badgeClasses()` method so they are
defined exactly once.

---

## 8. PDF report templates - CSS 2.1 ONLY

dompdf is a CSS 2.1 engine. It **does not support flexbox or CSS grid**, and it
cannot parse Tailwind v4's output (custom properties, `oklch()`, `@property`).

**Report templates must not reference the app's Tailwind bundle at all.**

- Lay out with `<table>`, `colspan`/`rowspan`, and floats.
- Hand-write a `<style>` block of CSS 2.1 in `resources/views/reports/pdf/_layout.blade.php`.
- Sizes in `pt`/`mm`/`%` - not `rem`, not `vh`.
- `<thead>` repeats across page breaks; use it.
- A table row cannot split across pages - keep rows short.
- `position: fixed` repeats on every page: use it for running headers/footers.

CSV export uses a streamed response with `fputcsv` - no package needed.

---

## 9. Testing

PHPUnit 11, extending `Tests\TestCase`. **Not Pest** - it cannot install on
PHP 8.2.

```php
final class BroodcockAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_delete_a_broodcock(): void
    {
        $staff = User::factory()->staff()->create();
        $bird  = Broodcock::factory()->create();

        $this->actingAs($staff)
            ->delete(route('broodcocks.destroy', $bird))
            ->assertForbidden();

        $this->assertNotSoftDeleted($bird);
    }
}
```

**Rules:**
- Test method names are full sentences: `test_staff_cannot_delete_a_broodcock`.
- Authorization coverage is the priority: **one test per role per protected
  route**, asserting both the HTTP status *and* that the data did not change.
- Livewire components are tested with `Livewire::test(Component::class)`.
  Do **not** use `make:livewire --test` - it emits Pest.
- Tests run against SQLite in memory (see `phpunit.xml`), so avoid
  Postgres-only SQL in application code paths that tests exercise.

---

## 10. Naming

| Thing | Convention | Example |
|---|---|---|
| Table | plural snake_case | `breeding_records` |
| Column | snake_case | `date_hatched` |
| FK column | `<singular>_id` | `broodcock_id` |
| Model | singular PascalCase | `BreedingRecord` |
| Enum | singular PascalCase | `BroodcockStatus` |
| Enum case | PascalCase, snake_case value | `ClassA = 'class_a'` |
| Action | verb phrase | `RecordMortality` |
| Form Request | `<Verb><Model>Request` | `StoreBroodcockRequest` |
| Policy | `<Model>Policy` | `BroodcockPolicy` |
| Route name | dot notation | `broodcocks.index` |
| Livewire component | `App\Livewire\<Plural>\<Action>` | `App\Livewire\Broodcocks\Index` |

Commits follow Conventional Commits: `feat(health): add vaccination schedule view`.
