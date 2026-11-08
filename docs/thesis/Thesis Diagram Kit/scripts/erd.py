"""Entity relationship diagram (Figure 12): every application table, its keys
and columns, and crow's-foot relationships whose ends follow the real
nullability of each foreign key."""
import math
import sys

from svgkit import Svg

OUT = sys.argv[1]
LH, FS, HEAD = 20, 14, 34
BW = 330

T_ = {
    "users": [("PK", "id", "bigint"), ("", "full_name", "varchar"), ("UK", "email", "varchar"),
              ("", "email_verified_at", "timestamp"), ("", "password", "varchar"), ("", "role", "enum"),
              ("", "contact_number", "varchar(40)"), ("", "address", "varchar"), ("", "position", "varchar(120)"),
              ("", "profile_photo_path", "varchar"), ("", "profile_photo_disk", "varchar(40)"),
              ("", "is_active", "boolean"), ("", "remember_token", "varchar"), ("", "created_at", "timestamp"),
              ("", "updated_at", "timestamp"), ("", "deleted_at", "timestamp")],
    "broodcocks": [("PK", "id", "bigint"), ("UK", "band_number", "varchar(64)"), ("", "name", "varchar"),
                   ("", "breed", "varchar(120)"), ("", "bloodline", "varchar(120)"), ("", "class", "enum"),
                   ("", "sex", "enum"), ("", "date_hatched", "date"), ("", "date_acquired", "date"),
                   ("", "weight", "decimal(5,2)"), ("", "color", "varchar(80)"), ("", "comb_type", "varchar(80)"),
                   ("", "leg_color", "varchar(80)"), ("", "distinguishing_marks", "text"), ("", "status", "enum"),
                   ("", "is_external", "boolean"), ("", "for_sale", "boolean"), ("FK", "sire_id", "bigint"),
                   ("FK", "dam_id", "bigint"), ("FK", "pen_id", "bigint"), ("", "notes", "text"),
                   ("", "created_at", "timestamp"), ("", "updated_at", "timestamp"), ("", "deleted_at", "timestamp")],
    "broodcock_photos": [("PK", "id", "bigint"), ("FK", "broodcock_id", "bigint"), ("", "path", "varchar"),
                         ("", "disk", "varchar(40)"), ("", "caption", "varchar"), ("", "is_primary", "boolean"),
                         ("FK", "uploaded_by", "bigint"), ("", "created_at", "timestamp"), ("", "updated_at", "timestamp")],
    "pens": [("PK", "id", "bigint"), ("UK", "code", "varchar(32)"), ("", "name", "varchar"),
             ("", "location", "varchar"), ("", "capacity", "int"), ("", "notes", "text"),
             ("", "created_at", "timestamp"), ("", "updated_at", "timestamp"), ("", "deleted_at", "timestamp")],
    "breeding_records": [("PK", "id", "bigint"), ("FK", "sire_id", "bigint"), ("FK", "dam_id", "bigint"),
                         ("", "mating_date", "date"), ("", "eggs_set", "int"), ("", "eggs_fertile", "int"),
                         ("", "eggs_hatched", "int"), ("", "offspring_count", "int"), ("", "notes", "text"),
                         ("FK", "recorded_by", "bigint"), ("", "created_at", "timestamp"),
                         ("", "updated_at", "timestamp"), ("", "deleted_at", "timestamp")],
    "health_records": [("PK", "id", "bigint"), ("FK", "broodcock_id", "bigint"), ("", "record_type", "enum"),
                       ("", "product_name", "varchar"), ("", "dosage", "varchar(120)"), ("", "checkup_date", "date"),
                       ("", "next_due_date", "date"), ("", "condition", "varchar"), ("", "remarks", "text"),
                       ("FK", "recorded_by", "bigint"), ("", "created_at", "timestamp"),
                       ("", "updated_at", "timestamp"), ("", "deleted_at", "timestamp")],
    "performance_records": [("PK", "id", "bigint"), ("FK", "broodcock_id", "bigint"), ("", "event_date", "date"),
                            ("", "event_type", "enum"), ("", "weight", "decimal(5,2)"), ("", "result", "enum"),
                            ("", "duration_seconds", "int"), ("", "rating", "smallint"), ("", "remarks", "text"),
                            ("FK", "recorded_by", "bigint"), ("", "created_at", "timestamp"),
                            ("", "updated_at", "timestamp"), ("", "deleted_at", "timestamp")],
    "mortality_records": [("PK", "id", "bigint"), ("FK, UK", "broodcock_id", "bigint"),
                          ("", "date_of_death", "date"), ("", "cause_of_death", "varchar"),
                          ("", "disposal_method", "varchar(120)"), ("", "remarks", "text"),
                          ("FK", "recorded_by", "bigint"), ("", "created_at", "timestamp"),
                          ("", "updated_at", "timestamp"), ("", "deleted_at", "timestamp")],
    "reports": [("PK", "id", "bigint"), ("", "report_type", "varchar(80)"), ("", "parameters", "jsonb"),
                ("", "format", "varchar(10)"), ("", "row_count", "int"), ("FK", "generated_by", "bigint"),
                ("", "generated_at", "timestamp"), ("", "file_path", "varchar"), ("", "created_at", "timestamp"),
                ("", "updated_at", "timestamp")],
    "farm_settings": [("PK", "id", "bigint"), ("", "farm_name", "varchar(120)"), ("", "address", "varchar(255)"),
                      ("", "phone", "varchar(60)"), ("", "email", "varchar(190)"), ("", "hours", "varchar(120)"),
                      ("", "visitor_note", "text"), ("", "created_at", "timestamp"), ("", "updated_at", "timestamp")],
}


def height(n):
    return HEAD + 8 + len(T_[n]) * LH + 8


POS = {}


def place(n, x, y):
    POS[n] = (x, y, BW, height(n))


C1, C2, C3, C4 = 40, 470, 960, 1450
place("pens", C1, 80)
place("broodcock_photos", C1, 470)
place("broodcocks", C2, 80)
place("mortality_records", C2, 80 + height("broodcocks") + 130)
place("breeding_records", C3, 80)
place("health_records", C3, 80 + height("breeding_records") + 60)
place("performance_records", C3, POS["health_records"][1] + height("health_records") + 60)
place("users", C4, 80)
place("reports", C4, 80 + height("users") + 90)
place("farm_settings", C4, POS["reports"][1] + height("reports") + 60)

W = C4 + BW + 40
H = int(max(y + h for x, y, w, h in POS.values()) + 160)
svg = Svg(W, H)


def L(n): return POS[n][0]
def R(n): return POS[n][0] + POS[n][2]
def T(n): return POS[n][1]
def B(n): return POS[n][1] + POS[n][3]
def CX(n): return POS[n][0] + POS[n][2] / 2


def row_y(n, col):
    """The centre line of a column's row, so a line can meet its foreign key."""
    i = [c[1] for c in T_[n]].index(col)
    return T(n) + HEAD + 8 + i * LH + LH / 2


for n, cols in T_.items():
    x, y, w, h = POS[n]
    svg.rect(x, y, w, h, sw=2.2)
    svg.add(f'<rect x="{x}" y="{y}" width="{w}" height="{HEAD}" fill="#e9e9e9" stroke="#000" stroke-width="2.2"/>')
    svg.text(x + w / 2, y + 23, n, 17, weight="bold")
    ty = y + HEAD + 8 + LH - 5
    for key, name, typ in cols:
        if key:
            svg.text(x + 8, ty, key, 12.5, anchor="start", weight="bold")
        svg.text(x + 64, ty, name, FS, anchor="start", weight="bold" if key.startswith("PK") else "normal")
        svg.text(x + w - 10, ty, typ, FS - 1, anchor="end", style="italic")
        ty += LH
    svg.line(x + 56, y + HEAD, x + 56, y + h, 1.2)


# ------------------------------------------------------------ crow's feet
def end_mark(px, py, dx, dy, kind):
    """A relationship end at (px, py); (dx, dy) points away from the entity.
    kind: 'one' (||), 'zero_one' (o|), 'many' (o<, zero or many)."""
    nx, ny = -dy, dx
    if kind == "many":
        fx, fy = px + dx * 20, py + dy * 20
        for s in (-11, 0, 11):
            svg.line(fx, fy, px + nx * s, py + ny * s, 2)
        cx, cy = px + dx * 30, py + dy * 30
        svg.add(f'<circle cx="{cx:.1f}" cy="{cy:.1f}" r="7" fill="#fff" stroke="#000" stroke-width="2"/>')
    else:
        for d in ((10, 16) if kind == "one" else (10,)):
            bx, by = px + dx * d, py + dy * d
            svg.line(bx + nx * 10, by + ny * 10, bx - nx * 10, by - ny * 10, 2)
        if kind == "zero_one":
            cx, cy = px + dx * 24, py + dy * 24
            svg.add(f'<circle cx="{cx:.1f}" cy="{cy:.1f}" r="7" fill="#fff" stroke="#000" stroke-width="2"/>')


def rel(points, kind1, kind2, label=None, label_at=None):
    svg.poly(points, 2)
    for (p, q), kind in (((points[0], points[1]), kind1), ((points[-1], points[-2]), kind2)):
        L_ = math.hypot(q[0] - p[0], q[1] - p[1]) or 1
        end_mark(p[0], p[1], (q[0] - p[0]) / L_, (q[1] - p[1]) / L_, kind)
    if label:
        lx, ly = label_at if label_at else ((points[0][0] + points[-1][0]) / 2, (points[0][1] + points[-1][1]) / 2 - 9)
        svg.text(lx, ly, label, 14.5, style="italic")


# pens 0..1 -- broodcocks many (pen_id is nullable).
y = row_y("pens", "id")
rel([(R("pens"), y), (L("broodcocks"), y)], "zero_one", "many", "houses")
# broodcocks 1 -- photos many.
y = row_y("broodcock_photos", "broodcock_id")
rel([(L("broodcocks"), y), (R("broodcock_photos"), y)], "one", "many", "has")
# broodcocks sire/dam self-reference: two loops on top of the table, each from
# the parent side (0..1) to the child side (zero or many).
sx = R("broodcocks")
top = T("broodcocks")
for (x1, x2), lab in (((CX("broodcocks") - 150, CX("broodcocks") - 50), "sire of"),
                      ((CX("broodcocks") + 50, CX("broodcocks") + 150), "dam of")):
    rel([(x1, top), (x1, top - 46), (x2, top - 46), (x2, top)], "zero_one", "many", lab,
        label_at=((x1 + x2) / 2, top - 54))
# breeding_records: both parents required.
for col, lab, off in (("sire_id", "sire in", 0), ("dam_id", "dam in", 0)):
    y = row_y("breeding_records", col)
    rel([(sx, y), (L("breeding_records"), y)], "one", "many", lab,
        label_at=((sx + L("breeding_records")) / 2 - 10, y - 7))
# health and performance records.
y = row_y("health_records", "broodcock_id")
rel([(sx, y), (L("health_records"), y)], "one", "many", "has", label_at=((sx + L("health_records")) / 2 + 30, y - 9))
gx = (sx + L("health_records")) / 2 - 20
y0 = B("broodcocks") - 40
y1 = row_y("performance_records", "broodcock_id")
rel([(sx, y0), (gx, y0), (gx, y1), (L("performance_records"), y1)], "one", "many", "has",
    label_at=(gx - 26, (y0 + y1) / 2))
# mortality: at most one per bird (broodcock_id is unique).
rel([(CX("broodcocks"), B("broodcocks")), (CX("broodcocks"), T("mortality_records"))], "one", "zero_one", "has",
    label_at=(CX("broodcocks") + 32, (B("broodcocks") + T("mortality_records")) / 2 + 5))

# users: recorded_by / uploaded_by / generated_by are all nullable -> 0..1.
ux = L("users")
ch = [ux - 70, ux - 54, ux - 38, ux - 22]
y = row_y("breeding_records", "recorded_by")
rel([(ux, y), (R("breeding_records"), y)], "zero_one", "many", "records")
yu = row_y("users", "is_active")
y = row_y("health_records", "recorded_by")
rel([(ux, yu), (ch[0], yu), (ch[0], y), (R("health_records"), y)], "zero_one", "many", "records",
    label_at=(R("health_records") + 50, y - 9))
yu = row_y("users", "remember_token")
y = row_y("performance_records", "recorded_by")
rel([(ux, yu), (ch[1], yu), (ch[1], y), (R("performance_records"), y)], "zero_one", "many", "records",
    label_at=(R("performance_records") + 50, y - 9))
yb1, yb2 = H - 100, H - 55
yu = row_y("users", "updated_at")
rel([(ux, yu), (ch[2], yu), (ch[2], yb1), (CX("mortality_records") + 70, yb1), (CX("mortality_records") + 70, B("mortality_records"))],
    "zero_one", "many", "records", label_at=(CX("mortality_records") + 240, yb1 - 9))
yu = row_y("users", "deleted_at")
rel([(ux, yu), (ch[3], yu), (ch[3], yb2), (CX("broodcock_photos"), yb2), (CX("broodcock_photos"), B("broodcock_photos"))],
    "zero_one", "many", "uploads", label_at=(CX("broodcock_photos") + 280, yb2 - 9))
rel([(CX("users"), B("users")), (CX("users"), T("reports"))], "zero_one", "many", "generates",
    label_at=(CX("users") + 56, (B("users") + T("reports")) / 2 + 5))

print("erd", svg.save(f"{OUT}/erd"))
