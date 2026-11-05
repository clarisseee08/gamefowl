{{-- A non-interactive heading for a group of rows. Deliberately NOT a menuitem:
     giving it a role would put a stop in the keyboard order that does nothing.
     d-menu-title draws its text at 40% ink, under the 7:1 console floor, so the
     colour is muted-foreground instead. --}}
<li role="none" class="d-menu-title px-2.5 py-1.5 text-[13px] font-medium text-muted-foreground">{{ $slot }}</li>
