<x-layouts::catalog title="Privacy notice">
    @php $farm = config('gfms.farm'); @endphp

    {{--
        A REAL PAGE, NOT A LINK TO A PDF, and not boilerplate either.

        Every statement below is about what THIS application actually does,
        checked against the code rather than copied from a template. If the
        system changes, this page is wrong and has to change with it - and
        LegalPagesTest fails when the claims it can check stop being true.
    --}}
    <x-long-form eyebrow="Privacy" title="Privacy notice"
                 lead="What {{ $farm['name'] }} collects through this website, and what it does not."
                 :contents="[
                     'browsing' => 'Browsing this site',
                     'contact' => 'If you get in touch',
                     'accounts' => 'Accounts',
                     'birds' => 'The birds',
                     'storage' => 'Where the data is kept',
                     'asking' => 'Asking about your information',
                 ]">
        <h2 id="browsing">Browsing this site</h2>
        <p>
            You can look at every public page — the front page, the catalogue, a bird's own
            page and its family tree — without an account and without giving us anything.
            There is no sign-up, no newsletter box and no contact form on this site. Nothing you
            do while browsing is recorded against you as a person.
        </p>
        <p>
            The site sets one cookie, which keeps your browsing session working and carries the
            token that protects forms from cross-site misuse. It is not used to track you, and
            there is no advertising network, analytics product or social media pixel on any page.
            The map on the visiting page is a link to Google Maps rather than an embedded map,
            so Google is not contacted unless you choose to open it.
        </p>

        <h2 id="contact">If you get in touch</h2>
        <p>
            Visits are arranged by telephone or email, not through this site. When you ring or
            write, the farm keeps whatever you tell it for as long as it takes to arrange the
            visit and no longer. None of it is stored in this system.
        </p>

        <h2 id="accounts">Accounts</h2>
        <p>
            Accounts exist only for the farm's own staff, and only the owner can create one —
            you cannot register yourself. An account holds a name, an email address, an optional
            contact number and address, an optional photograph, and a password that is stored
            hashed, which means nobody at the farm can read it. Staff can change their own details
            at any time from their profile.
        </p>

        <h2 id="birds">The birds</h2>
        <p>
            The records this site shows are about animals, not people. A bird's page carries its
            parentage, its photographs, its health record and its performance history. The farm's
            internal notes, its breeding and mortality records and its reports are not public and
            are never shown to a visitor.
        </p>

        <h2 id="storage">Where the data is kept</h2>
        <p>
            The records and photographs are held on hosted infrastructure outside the
            Philippines, reached over an encrypted connection. Photographs are kept in private
            storage and served only through this site, which checks on every single request
            whether the viewer is allowed to see that image.
        </p>

        <h2 id="asking">Asking about your information</h2>
        <p>
            If you have an account here, or you think the farm holds something about you, write to
            us and we will tell you what there is and remove it if you ask.
            @if ($farm['email'])
                The address is <a href="mailto:{{ $farm['email'] }}">{{ $farm['email'] }}</a>.
            @endif
            @if ($farm['phone'])
                You can also ring the farm on <span class="datum">{{ $farm['phone'] }}</span>.
            @endif
        </p>
    </x-long-form>
</x-layouts::catalog>
