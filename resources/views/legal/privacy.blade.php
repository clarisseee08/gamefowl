<x-layouts::catalog title="Privacy notice">
    @php $farm = config('gfms.farm'); @endphp

    {{--
        A REAL PAGE, NOT A LINK TO A PDF, and not boilerplate either.

        Everything below is a statement about what THIS application actually
        does, checked against the code rather than copied from a template. If
        the system changes, this page is wrong and has to change with it -
        which is the only way a privacy notice is worth having.

        Set at a reading measure. This is the one kind of page in the whole
        application that is prose first, so it takes a column rather than the
        full 1440 the rest of the public site uses.
    --}}
    <article class="max-w-[68ch] py-6">
        <h1 class="text-[34px] font-semibold leading-[1.1] text-foreground sm:text-[42px]">
            Privacy notice
        </h1>
        <p class="mt-4 text-[17px] leading-relaxed text-muted-foreground">
            What {{ $farm['name'] }} collects through this website, and what it does not.
        </p>

        <h2 class="mt-12 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            Browsing this site
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            You can look at every public page — the front page, the catalogue, a
            bird's own page and its family tree — without an account and without
            giving us anything. There is no sign-up, no newsletter box and no
            contact form on this site. Nothing you do while browsing is recorded
            against you as a person.
        </p>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            The site sets one cookie, which keeps your browsing session working
            and carries the token that protects forms from cross-site misuse. It
            is not used to track you, and there is no advertising network,
            analytics product or social media pixel on any page.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            If you get in touch
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            Visits are arranged by telephone or email, not through this site.
            When you ring or write, the farm keeps whatever you tell it for as
            long as it takes to arrange the visit and no longer. None of it is
            stored in this system.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            Accounts
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            Accounts exist only for the farm's own staff, and only the owner can
            create one — you cannot register yourself. An account holds a name,
            an email address, an optional contact number and address, an
            optional photograph, and a password that is stored hashed, which
            means nobody at the farm can read it. Staff can change their own
            details at any time from their profile.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            The birds
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            The records this site shows are about animals, not people. A bird's
            page carries its parentage, its photographs, its health record and
            its performance history. The farm's internal notes, its breeding and
            mortality records and its reports are not public and are never shown
            to a visitor.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            Where the data is kept
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            The records and photographs are held on hosted infrastructure
            outside the Philippines, reached over an encrypted connection.
            Photographs are kept in private storage and served only through this
            site, which checks on every single request whether the viewer is
            allowed to see that image.
        </p>

        <h2 class="mt-10 border-t border-border pt-7 text-[21px] font-semibold text-foreground">
            Asking us about your information
        </h2>
        <p class="mt-3 text-[17px] leading-relaxed text-foreground">
            If you have an account here, or you think the farm holds something
            about you, write to us and we will tell you what there is and remove
            it if you ask.
        </p>

        @if ($farm['email'] || $farm['phone'])
            <dl class="mt-7 grid gap-x-10 gap-y-5 border-t border-border pt-7 sm:grid-cols-2">
                @if ($farm['email'])
                    <div>
                        <dt class="text-[13px] font-semibold text-muted-foreground">Email</dt>
                        <dd class="mt-2 break-words text-[17px]">
                            <a href="mailto:{{ $farm['email'] }}" class="text-primary hover:underline">{{ $farm['email'] }}</a>
                        </dd>
                    </div>
                @endif
                @if ($farm['phone'])
                    <div>
                        <dt class="text-[13px] font-semibold text-muted-foreground">Phone</dt>
                        <dd class="datum mt-2 text-[17px] text-foreground">{{ $farm['phone'] }}</dd>
                    </div>
                @endif
            </dl>
        @endif
    </article>
</x-layouts::catalog>
