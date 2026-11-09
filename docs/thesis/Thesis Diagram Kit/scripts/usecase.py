"""Use case diagrams (Figures 3-5) in the reference's layout: actor on the left,
main use cases down a column inside the system boundary, their included and
extending use cases fanned out to the right."""
import math
import sys

from svgkit import Svg, wrap

OUT = sys.argv[1]

UC_RX, UC_RY = 100, 28
SUB_RX, SUB_RY = 96, 23
STEP = 34          # vertical step between consecutive sub use cases
NEAR, FAR = 280, 490   # sub columns, measured from the main column
MIN_BLOCK = 66


def ellipse_label(svg, cx, cy, rx, ry, label, size):
    svg.ellipse(cx, cy, rx, ry)
    svg.lines_centered(cx, cy, wrap(label, size, rx * 1.7), size)


def edge_point(cx, cy, rx, ry, tx, ty):
    """Where the line from an ellipse's centre towards (tx, ty) leaves it."""
    dx, dy = tx - cx, ty - cy
    if dx == 0 and dy == 0:
        return cx, cy
    t = 1 / math.sqrt((dx / rx) ** 2 + (dy / ry) ** 2)
    return cx + dx * t, cy + dy * t


def diagram(name, actor, mains):
    """mains: [(label, [(kind, label), ...])], kind in {'include', 'extend'}.

    Sub use cases alternate between a near and a far column, each one a
    half-step lower than the last, so an arrow to the far column always
    passes between two near ellipses instead of through one."""
    blocks = [max(MIN_BLOCK, len(subs) * STEP + 34) for _, subs in mains]
    top, bx = 52, 200
    main_x = bx + 235
    W = int(main_x + FAR + SUB_RX + 50)
    H = int(top + sum(blocks) + 52)
    svg = Svg(W, H)
    svg.rect(bx, 24, W - bx - 24, H - 48, rx=10, sw=2.4)

    centres, y = [], top
    for (label, subs), h in zip(mains, blocks):
        centres.append((main_x, y + h / 2, label, subs))
        y += h

    actor_cy = H / 2
    svg.actor(92, actor_cy - 70, actor, 19)
    ax, ay = 92 + 36, actor_cy - 70 + 34 + 18

    for cx, cy, label, subs in centres:
        # To each case's leftmost point: every line then stays left of the
        # cases it passes, however far up or down its target is.
        svg.line(ax, ay, cx - UC_RX, cy, 1.7)
        n = len(subs)
        for j, (kind, sub) in enumerate(subs):
            sx = main_x + (NEAR if j % 2 == 0 else FAR)
            sy = cy + (j - (n - 1) / 2) * STEP
            if j % 2 == 0:
                # Near column: a straight line.
                path = [edge_point(cx, cy, UC_RX, UC_RY, sx, sy), edge_point(sx, sy, SUB_RX, SUB_RY, cx, cy)]
            else:
                # Far column: to the gap between two near cases, then
                # straight across it, so no near ellipse is crossed.
                gx = main_x + NEAR - SUB_RX - 26
                path = [edge_point(cx, cy, UC_RX, UC_RY, gx, sy), (gx, sy), (sx - SUB_RX, sy)]
            # «include» points base -> included; «extend» points extension -> base.
            if kind == "extend":
                path = list(reversed(path))
            svg.arrow(path, 1.4, dash="7,5", open_head=True, size=10)
        for j, (kind, sub) in enumerate(subs):
            sx = main_x + (NEAR if j % 2 == 0 else FAR)
            sy = cy + (j - (n - 1) / 2) * STEP
            ellipse_label(svg, sx, sy, SUB_RX, SUB_RY, sub, 14.5)
            # The stereotype sits just left of the case it labels.
            svg.text(sx - SUB_RX - 6, sy - 8, f"«{kind}»", 12, anchor="end", style="italic")
        ellipse_label(svg, cx, cy, UC_RX, UC_RY, label, 16)
    print(name, svg.save(f"{OUT}/{name}"))


I, E = "include", "extend"

diagram("uc-owner", "Farm Owner", [
    ("Login", [(E, "Reset Password")]),
    ("Manage Gamefowl", [(I, "View Gamefowl"), (E, "Add or Edit Gamefowl"), (E, "Delete Gamefowl"),
                         (E, "Manage Photos"), (E, "View Family Tree")]),
    ("Manage Breeding Records", [(I, "View Matings"), (E, "Record or Edit a Mating"),
                                 (E, "Register Hatched Offspring")]),
    ("Manage Health Records", [(I, "View Health Records"), (E, "Add or Edit Health Record"),
                               (E, "Delete Health Record"), (E, "View Vaccination Schedule")]),
    ("Manage Performance Records", [(I, "View Performance Records"), (E, "Add or Edit Performance Record"),
                                    (E, "Delete Performance Record")]),
    ("Manage Mortality Records", [(I, "View Mortality Records"), (E, "Record a Death"),
                                  (E, "Delete Mortality Record")]),
    ("Generate Reports", [(I, "Select Report and Period"), (E, "Download CSV"), (E, "Download PDF"),
                          (I, "View Report History")]),
    ("Manage Users", [(I, "View Users"), (E, "Add or Edit User"), (E, "Activate or Deactivate User")]),
    ("Manage Farm Profile", [(I, "View Farm Profile"), (E, "Edit Farm Details")]),
    ("Manage Own Profile", [(E, "Edit Profile Details"), (E, "Change Profile Photo"), (E, "Change Password")]),
    ("Logout", []),
])

diagram("uc-staff", "Farm Staff", [
    ("Login", [(E, "Reset Password")]),
    ("Manage Gamefowl", [(I, "View Gamefowl"), (E, "Add or Edit Gamefowl"), (E, "Manage Photos"),
                         (E, "View Family Tree")]),
    ("Manage Breeding Records", [(I, "View Matings"), (E, "Record or Edit a Mating"),
                                 (E, "Register Hatched Offspring")]),
    ("Manage Health Records", [(I, "View Health Records"), (E, "Add or Edit Health Record"),
                               (E, "View Vaccination Schedule")]),
    ("Manage Performance Records", [(I, "View Performance Records"), (E, "Add or Edit Performance Record")]),
    ("Manage Mortality Records", [(I, "View Mortality Records"), (E, "Record a Death")]),
    ("Generate Reports", [(I, "Select Report and Period"), (E, "Download CSV"), (E, "Download PDF")]),
    ("Manage Own Profile", [(E, "Edit Profile Details"), (E, "Change Profile Photo"), (E, "Change Password")]),
    ("Logout", []),
])

diagram("uc-visitor", "Public\nVisitor", [
    ("View Home Page", []),
    ("Browse Catalogue", [(E, "Search and Filter Birds"), (E, "Show Birds for Sale")]),
    ("View Bird Details", [(I, "View Photos"), (E, "View Health Records"), (E, "View Performance Records")]),
    ("View Family Tree", []),
    ("View About Page", [(E, "View Bloodlines")]),
    ("View Visit Page", [(I, "View Contact Details"), (E, "Call or Email the Farm"), (E, "Open Map")]),
])
