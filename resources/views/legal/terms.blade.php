<x-layouts::catalog title="Terms of use">
    @php $farm = config('gfms.farm'); @endphp

    {{--
        Deliberately short, and deliberately specific to what this site is.

        A farm's shop window does not need a thousand words of software
        licensing. It needs to say what the listings mean, what they do not
        mean, and who is responsible for what. Everything a generic template
        would add here would be a promise this farm has not made.
    --}}
    <article class="max-w-[68ch] py-6">
        <h1 class="text-[34px] font-semibold leading-[1.1] text-foreground sm:text-[42px]">
            Terms of use
        </h1>
        <p class="mt-4 text-[17px] leading-relaxed text-muted-foreground">
            What the listings on this site mean, and what they do not.
        </p>

        <h2 class="mt-12 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            This site is a shop window
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            It shows the birds {{ $farm['name'] }} currently holds, with their
            records. Nothing here is an offer to sell, nothing can be reserved
            or bought through this site, and no price is quoted anywhere on it.
            A bird marked <strong>For sale</strong> is one the farm is willing to
            discuss — the discussion happens by telephone or in person.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            The records are the farm's own
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            Parentage, health and performance are entered by the farm from its
            own books. They are kept carefully and we believe them to be
            accurate, but they are a record of what was written down, not a
            guarantee of how a bird will perform. Where something was never
            recorded the page says so rather than guessing — that is deliberate,
            and "Not recorded" means exactly what it says.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            Come and look
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            A photograph only shows so much. Visitors are welcome by
            arrangement, and we would rather you saw a bird in person before
            deciding anything.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            Photographs and text
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            The photographs of birds on this site belong to the farm. Please do
            not republish them as a record of stock you hold. Background images
            used for decoration are licensed stock and are credited in the
            site's own files.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            Accounts
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            Accounts are for the farm's staff. There is no public registration,
            and the owner creates and removes every account. If you hold one,
            keep your password to yourself and tell the owner if you think
            somebody else has it.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            Questions
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            Ask the farm directly — the contact details are on the front page,
            and the <a href="{{ route('legal.privacy') }}" wire:navigate class="text-primary hover:underline">privacy
            notice</a> says what happens to anything you send us.
        </p>
    </article>
</x-layouts::catalog>
