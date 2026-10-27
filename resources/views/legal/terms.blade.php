<x-layouts::catalog title="Terms of use">
    @php $farm = config('gfms.farm'); @endphp

    {{--
        Deliberately short, and deliberately specific to what this site is.

        A farm's shop window does not need a thousand words of software
        licensing. It needs to say what the listings mean, what they do not
        mean, and who is responsible for what. Anything a generic template
        would add here would be a promise this farm has not made.
    --}}
    <x-long-form eyebrow="Terms" title="Terms of use"
                 lead="What the listings on this site mean, and what they do not."
                 :contents="[
                     'window' => 'This site is a shop window',
                     'records' => 'The records are the farm\'s own',
                     'visit' => 'Come and look',
                     'photographs' => 'Photographs and text',
                     'accounts' => 'Accounts',
                     'questions' => 'Questions',
                 ]">
        <h2 id="window">This site is a shop window</h2>
        <p>
            It shows the birds {{ $farm['name'] }} currently holds, with their records. Nothing
            here is an offer to sell, nothing can be reserved or bought through this site, and no
            price is quoted anywhere on it. A bird marked <strong>For sale</strong> is one the farm
            is willing to discuss — the discussion happens by telephone or in person.
        </p>

        <h2 id="records">The records are the farm's own</h2>
        <p>
            Parentage, health and performance are entered by the farm from its own books. They are
            kept carefully and we believe them to be accurate, but they are a record of what was
            written down, not a guarantee of how a bird will perform. Where something was never
            recorded the page says so rather than guessing — that is deliberate, and "Not recorded"
            means exactly what it says.
        </p>

        <h2 id="visit">Come and look</h2>
        <p>
            A photograph only shows so much. Visitors are welcome by arrangement, and we would
            rather you saw a bird in person before deciding anything.
            <a href="{{ route('public.visit') }}" wire:navigate>How to arrange a visit</a>.
        </p>

        <h2 id="photographs">Photographs and text</h2>
        <p>
            The photographs of birds on this site belong to the farm. Please do not republish them
            as a record of stock you hold. Background images used for atmosphere are licensed stock
            and are credited in the site's own files.
        </p>

        <h2 id="accounts">Accounts</h2>
        <p>
            Accounts are for the farm's staff. There is no public registration, and the owner
            creates and removes every account. If you hold one, keep your password to yourself and
            tell the owner if you think somebody else has it.
        </p>

        <h2 id="questions">Questions</h2>
        <p>
            Ask the farm directly — the contact details are on the
            <a href="{{ route('public.visit') }}" wire:navigate>visiting page</a>, and the
            <a href="{{ route('legal.privacy') }}" wire:navigate>privacy notice</a> says what
            happens to anything you send us.
        </p>
    </x-long-form>
</x-layouts::catalog>
