"""Activity diagrams (Figures 6-9) in the reference's style: a start dot,
rounded actions, YES/NO decisions, a fork bar into one column per module, and
the end bullseye after Logout."""
import sys

from svgkit import Svg, wrap

OUT = sys.argv[1]
BOX_W, BOX_H = 176, 58
SUB_W, SUB_H = 150, 50
COL_W = 190
FS = 15


def action(svg, cx, cy, label, w=BOX_W, h=BOX_H, size=FS):
    svg.rect(cx - w / 2, cy - h / 2, w, h, rx=12, sw=2.2)
    svg.lines_centered(cx, cy, wrap(label, size, w - 16), size)


def decision(svg, cx, cy, label, w=150, h=96):
    svg.diamond(cx, cy, w, h)
    svg.lines_centered(cx, cy, wrap(label, 14, w * 0.55), 14)


def tag(svg, x, y, t, anchor="middle"):
    svg.text(x, y, t, 14, anchor=anchor, weight="bold")


def modules(svg, x0, bar_y, cols, end_after_last=True):
    """The fork bar and one column per module; returns the bottom y."""
    xs = [x0 + i * COL_W for i in range(len(cols))]
    svg.bar(xs[0] - SUB_W / 2 + 10, xs[-1] + SUB_W / 2 - 10, bar_y)
    bottom = bar_y
    for x, (title, subs) in zip(xs, cols):
        hy = bar_y + 70
        svg.arrow([(x, bar_y + 5), (x, hy - BOX_H / 2)], 2, size=11)
        action(svg, x, hy, title, w=SUB_W + 14, h=BOX_H, size=15)
        rail = x - (SUB_W + 14) / 2 + 14
        y = hy + BOX_H / 2 + 26 + SUB_H / 2
        last = hy
        for sub in subs:
            sx = x + 14
            action(svg, sx, y, sub, w=SUB_W - 6, h=SUB_H, size=13.5)
            svg.arrow([(rail, y), (sx - (SUB_W - 6) / 2, y)], 1.8, size=9)
            last = y
            y += SUB_H + 18
        if subs:
            svg.line(rail, hy + BOX_H / 2, rail, last, 1.8)
        bottom = max(bottom, last + SUB_H / 2)
    if end_after_last:
        x = xs[-1]
        top = bar_y + 70 + BOX_H / 2
        svg.arrow([(x, top), (x, top + 50)], 2, size=11)
        svg.end(x, top + 64)
        bottom = max(bottom, top + 80)
    return bottom


def login_flow(svg, cx, y_login, with_request=False):
    """Enter credentials -> valid? (forgot-password loop) -> active? -> dashboard.
    Returns the y of the fork bar."""
    action(svg, cx, y_login, "Enter Login Credentials")
    vy = y_login + 125
    svg.arrow([(cx, y_login + BOX_H / 2), (cx, vy - 48)], 2, size=11)
    decision(svg, cx, vy, "Valid credentials?")

    # NO -> Forgot password? on the left.
    fx = cx - 270
    svg.arrow([(cx - 75, vy), (fx + 75, vy)], 2, size=11)
    tag(svg, cx - 100, vy - 10, "NO")
    decision(svg, fx, vy, "Forgot password?")
    # Forgot? NO -> back to the credentials.
    svg.arrow([(fx, vy - 48), (fx, y_login), (cx - BOX_W / 2, y_login)], 2, size=11)
    tag(svg, fx + 22, vy - 62, "NO", anchor="start")
    # Forgot? YES -> reset by email, then back to the credentials.
    ry1, ry2 = vy + 125, vy + 215
    svg.arrow([(fx, vy + 48), (fx, ry1 - BOX_H / 2)], 2, size=11)
    tag(svg, fx + 22, vy + 72, "YES", anchor="start")
    action(svg, fx, ry1, "Request a Reset Link by Email")
    svg.arrow([(fx, ry1 + BOX_H / 2), (fx, ry2 - BOX_H / 2)], 2, size=11)
    action(svg, fx, ry2, "Set a New Password")
    lx = fx - BOX_W / 2 - 40
    # Joins the NO path, whose arrow already leads back to the credentials.
    svg.poly([(fx - BOX_W / 2, ry2), (lx, ry2), (lx, y_login), (fx, y_login)], 2)

    # YES -> Account active?
    ay = vy + 165
    svg.arrow([(cx, vy + 48), (cx, ay - 48)], 2, size=11)
    tag(svg, cx + 14, vy + 74, "YES", anchor="start")
    decision(svg, cx, ay, "Account active?")
    # Inactive -> refused.
    dx = cx + 270
    svg.arrow([(cx + 75, ay), (dx - BOX_W / 2, ay)], 2, size=11)
    tag(svg, cx + 100, ay - 10, "NO")
    action(svg, dx, ay, "Show 'Account Deactivated' Message")
    svg.arrow([(dx, ay + BOX_H / 2), (dx, ay + BOX_H / 2 + 44)], 2, size=11)
    svg.end(dx, ay + BOX_H / 2 + 58)
    # Active -> dashboard.
    dy = ay + 140
    svg.arrow([(cx, ay + 48), (cx, dy - BOX_H / 2)], 2, size=11)
    tag(svg, cx + 14, ay + 74, "YES", anchor="start")
    action(svg, cx, dy, "View Dashboard")
    bar_y = dy + 90
    svg.arrow([(cx, dy + BOX_H / 2), (cx, bar_y - 6)], 2, size=11)
    return bar_y


def build(name, cols, top, h_extra=0):
    W = int(80 + len(cols) * COL_W)
    max_subs = max(len(s) for _, s in cols)
    svg = Svg(W, 2000)
    cx = W / 2
    bar_y = top(svg, cx)
    bottom = modules(svg, 40 + COL_W / 2, bar_y, cols)
    H = int(bottom + 50 + h_extra)
    svg.h = H
    svg.parts[0] = f'<rect x="0" y="0" width="{W}" height="{H}" fill="#fff"/>'
    svg.parts[1] = f'<rect x="2" y="2" width="{W - 4}" height="{H - 4}" fill="none" stroke="#000" stroke-width="2.5"/>'
    print(name, svg.save(f"{OUT}/{name}"))


# ------------------------------------------------------------------ Figure 6
def access(svg, cx):
    svg.start(cx, 50)
    steps = ["Open a Web Browser", "Enter the System's Web Address", "Load the Farm's Home Page"]
    y = 50
    for s in steps:
        ny = y + 110 if y == 50 else y + 105
        svg.arrow([(cx, y + (13 if y == 50 else BOX_H / 2)), (cx, ny - BOX_H / 2)], 2, size=11)
        action(svg, cx, ny, s, w=230)
        y = ny
    svg.arrow([(cx, y + BOX_H / 2), (cx, y + BOX_H / 2 + 44)], 2, size=11)
    svg.end(cx, y + BOX_H / 2 + 58)


svg = Svg(520, 470)
access(svg, 260)
print("act-access", svg.save(f"{OUT}/act-access"))

# ------------------------------------------------------------------ Figure 7
OWNER = [
    ("Manage Gamefowl", ["View Gamefowl", "Add Gamefowl", "Edit Gamefowl", "Delete Gamefowl", "Manage Photos", "View Family Tree"]),
    ("Manage Breeding Records", ["View Matings", "Record a Mating", "Edit a Mating", "Register Hatched Offspring"]),
    ("Manage Health Records", ["View Health Records", "Add Health Record", "Edit Health Record", "Delete Health Record", "View Vaccination Schedule"]),
    ("Manage Performance Records", ["View Performance Records", "Add Performance Record", "Edit Performance Record", "Delete Performance Record"]),
    ("Manage Mortality Records", ["View Mortality Records", "Record a Death", "Delete Mortality Record"]),
    ("Generate Reports", ["Select Report and Period", "Download CSV", "Download PDF", "View Report History"]),
    ("Manage Users", ["View Users", "Add User", "Edit User", "Activate or Deactivate User"]),
    ("Manage Farm Profile", ["View Farm Profile", "Edit Farm Details"]),
    ("Manage Own Profile", ["Edit Profile Details", "Change Profile Photo", "Change Password"]),
    ("Logout", []),
]


def owner_top(svg, cx):
    svg.start(cx, 40)
    svg.arrow([(cx, 53), (cx, 130 - BOX_H / 2)], 2, size=11)
    return login_flow(svg, cx, 130)


build("act-owner", OWNER, owner_top)

# ------------------------------------------------------------------ Figure 8
STAFF = [
    ("Manage Gamefowl", ["View Gamefowl", "Add Gamefowl", "Edit Gamefowl", "Manage Photos", "View Family Tree"]),
    ("Manage Breeding Records", ["View Matings", "Record a Mating", "Edit a Mating", "Register Hatched Offspring"]),
    ("Manage Health Records", ["View Health Records", "Add Health Record", "Edit Health Record", "View Vaccination Schedule"]),
    ("Manage Performance Records", ["View Performance Records", "Add Performance Record", "Edit Performance Record"]),
    ("Manage Mortality Records", ["View Mortality Records", "Record a Death"]),
    ("Generate Reports", ["Select Report and Period", "Download CSV", "Download PDF"]),
    ("Manage Own Profile", ["Edit Profile Details", "Change Profile Photo", "Change Password"]),
    ("Logout", []),
]


def staff_top(svg, cx):
    svg.start(cx, 40)
    hy = 140
    svg.arrow([(cx, 53), (cx, hy - 48)], 2, size=11)
    decision(svg, cx, hy, "Has an account?")
    ly = hy + 125
    # NO -> the owner creates the account.
    rx = cx + 270
    svg.arrow([(cx + 75, hy), (rx - BOX_W / 2, hy)], 2, size=11)
    tag(svg, cx + 100, hy - 10, "NO")
    action(svg, rx, hy, "Request an Account from the Farm Owner")
    svg.arrow([(rx, hy + BOX_H / 2), (rx, ly - BOX_H / 2)], 2, size=11)
    action(svg, rx, ly, "Receive Login Credentials")
    svg.arrow([(rx - BOX_W / 2, ly), (cx + BOX_W / 2, ly)], 2, size=11)
    # YES -> sign in.
    svg.arrow([(cx, hy + 48), (cx, ly - BOX_H / 2)], 2, size=11)
    tag(svg, cx + 14, hy + 74, "YES", anchor="start")
    return login_flow(svg, cx, ly)


build("act-staff", STAFF, staff_top)

# ------------------------------------------------------------------ Figure 9
VISITOR = [
    ("Browse the Catalogue", ["Search and Filter Birds", "Open a Bird's Page", "View Photos and Details", "View Health and Performance", "View Family Tree"]),
    ("View the About Page", ["Read About the Farm", "View Bloodlines"]),
    ("View the Visit Page", ["View Contact Details", "Call or Email the Farm", "Open the Map"]),
    # No sign-in: the public side of the system has no accounts.
    ("Leave the Website", []),
]


def visitor_top(svg, cx):
    svg.start(cx, 40)
    y1, y2 = 130, 230
    svg.arrow([(cx, 53), (cx, y1 - BOX_H / 2)], 2, size=11)
    action(svg, cx, y1, "Open the Farm's Website")
    svg.arrow([(cx, y1 + BOX_H / 2), (cx, y2 - BOX_H / 2)], 2, size=11)
    action(svg, cx, y2, "View the Home Page")
    bar_y = y2 + 90
    svg.arrow([(cx, y2 + BOX_H / 2), (cx, bar_y - 6)], 2, size=11)
    return bar_y


build("act-visitor", VISITOR, visitor_top)
