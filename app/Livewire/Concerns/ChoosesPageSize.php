<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Rows per page for a paginated list, chosen in the table footer
 * (<x-ui.table-pagination>). Every list in the console offers the same four
 * sizes, so the footer reads the same on every screen.
 *
 * The component must also use WithPagination, and paginate with
 * `->paginate($this->pageSize())`.
 */
trait ChoosesPageSize
{
    /** @var list<int> */
    public const PAGE_SIZES = [10, 25, 50, 100];

    #[Url(as: 'per', except: 10)]
    public int $perPage = 10;

    /** The chosen page size, or the default when the query string asks for any other. */
    public function pageSize(): int
    {
        return in_array($this->perPage, self::PAGE_SIZES, true) ? $this->perPage : self::PAGE_SIZES[0];
    }

    /** A new page size starts every list on the component from page one. */
    public function updatedPerPage(): void
    {
        foreach (array_keys($this->paginators) as $pageName) {
            $this->setPage(1, $pageName);
        }
    }
}
