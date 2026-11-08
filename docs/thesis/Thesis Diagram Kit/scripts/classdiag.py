"""Class diagram (Figure 10): the application's Eloquent models, their columns,
their domain methods and their associations, in the reference's box style."""
import sys

from svgkit import Svg

OUT = sys.argv[1]
LH, FS, HEAD = 20, 14.5, 34
BW = 300

CLASSES = {
    "User": (["bigint id", "string full_name", "string email", "timestamp email_verified_at", "string password",
              "enum role", "string contact_number", "string address", "string position", "string profile_photo_path",
              "string profile_photo_disk", "boolean is_active", "string remember_token", "timestamps timestamps",
              "timestamp deleted_at"],
             ["isOwner()", "isStaff()", "isCustomer()", "isInternal()", "profilePhotoUrl()", "hasProfilePhoto()"]),
    "Broodcock": (["bigint id", "string band_number", "string name", "string breed", "string bloodline", "enum class",
                   "enum sex", "date date_hatched", "date date_acquired", "decimal weight", "string color",
                   "string comb_type", "string leg_color", "text distinguishing_marks", "enum status",
                   "boolean is_external", "boolean for_sale", "bigint sire_id", "bigint dam_id", "bigint pen_id",
                   "text notes", "timestamps timestamps", "timestamp deleted_at"],
                  ["offspring()", "ageInMonths()", "ageLabel()", "isDeceased()", "isPubliclyVisible()", "displayName()"]),
    "BroodcockPhoto": (["bigint id", "bigint broodcock_id", "string path", "string disk", "string caption",
                        "boolean is_primary", "bigint uploaded_by", "timestamps timestamps"], ["url()"]),
    "Pen": (["bigint id", "string code", "string name", "string location", "int capacity", "text notes",
             "timestamps timestamps", "timestamp deleted_at"], ["occupancy()", "remainingCapacity()", "isFull()"]),
    "BreedingRecord": (["bigint id", "bigint sire_id", "bigint dam_id", "date mating_date", "int eggs_set",
                        "int eggs_fertile", "int eggs_hatched", "int offspring_count", "text notes",
                        "bigint recorded_by", "timestamps timestamps", "timestamp deleted_at"],
                       ["fertilityRate()", "hatchRate()", "overallHatchRate()", "hasUnregisteredOffspring()"]),
    "HealthRecord": (["bigint id", "bigint broodcock_id", "enum record_type", "string product_name", "string dosage",
                      "date checkup_date", "date next_due_date", "string condition", "text remarks",
                      "bigint recorded_by", "timestamps timestamps", "timestamp deleted_at"],
                     ["isOverdue()", "isDueSoon()", "scheduleState()", "daysUntilDue()"]),
    "PerformanceRecord": (["bigint id", "bigint broodcock_id", "date event_date", "enum event_type", "decimal weight",
                           "enum result", "int duration_seconds", "int rating", "text remarks", "bigint recorded_by",
                           "timestamps timestamps", "timestamp deleted_at"], ["durationLabel()"]),
    "MortalityRecord": (["bigint id", "bigint broodcock_id", "date date_of_death", "string cause_of_death",
                         "string disposal_method", "text remarks", "bigint recorded_by", "timestamps timestamps",
                         "timestamp deleted_at"], ["ageAtDeathInMonths()"]),
    "Report": (["bigint id", "string report_type", "json parameters", "string format", "int row_count",
                "bigint generated_by", "timestamp generated_at", "string file_path", "timestamps timestamps"],
               ["ofType()"]),
    "FarmSetting": (["bigint id", "string farm_name", "string address", "string phone", "string email",
                     "string hours", "text visitor_note", "timestamps timestamps"], ["current()"]),
}


def height(name):
    a, m = CLASSES[name]
    return HEAD + 8 + len(a) * LH + 10 + len(m) * LH + 10


POS = {}


def place(name, x, y):
    POS[name] = (x, y, BW, height(name))


C1, C2, C3, C4 = 40, 440, 900, 1360
place("Pen", C1, 70)
place("BroodcockPhoto", C1, 470)
place("Broodcock", C2, 70)
place("MortalityRecord", C2, height("Broodcock") + 180)
place("BreedingRecord", C3, 70)
place("HealthRecord", C3, 70 + height("BreedingRecord") + 60)
place("PerformanceRecord", C3, POS["HealthRecord"][1] + height("HealthRecord") + 60)
place("User", C4, 70)
place("Report", C4, 70 + height("User") + 90)
place("FarmSetting", C4, POS["Report"][1] + height("Report") + 60)

W = C4 + BW + 40
H = int(max(y + h for x, y, w, h in POS.values()) + 170)
svg = Svg(W, H)


def L(n):
    return POS[n][0]


def R(n):
    return POS[n][0] + POS[n][2]


def T(n):
    return POS[n][1]


def B(n):
    return POS[n][1] + POS[n][3]


def CX(n):
    return POS[n][0] + POS[n][2] / 2


def mult(x, y, t, anchor="middle"):
    svg.text(x, y, t, 15, anchor=anchor, weight="bold")


def name_label(x, y, t):
    svg.text(x, y, t, 15, style="italic")


def assoc(points, m1, m2, label, label_at=None, m1_off=(0, -8), m2_off=(0, -8)):
    svg.poly(points, 2)
    (x1, y1), (x2, y2) = points[0], points[-1]
    mult(x1 + m1_off[0], y1 + m1_off[1], m1)
    mult(x2 + m2_off[0], y2 + m2_off[1], m2)
    if label:
        lx, ly = label_at if label_at else ((x1 + x2) / 2, (y1 + y2) / 2 - 8)
        name_label(lx, ly, label)


# ---------------------------------------------------------------- the classes
for name, (attrs, methods) in CLASSES.items():
    x, y, w, h = POS[name]
    svg.rect(x, y, w, h, sw=2.2)
    svg.text(x + w / 2, y + 23, name, 17, weight="bold")
    svg.line(x, y + HEAD, x + w, y + HEAD, 2)
    ty = y + HEAD + 8 + LH - 5
    for a in attrs:
        svg.text(x + 10, ty, "+" + a, FS, anchor="start")
        ty += LH
    my = y + HEAD + 8 + len(attrs) * LH + 5
    svg.line(x, my, x + w, my, 2)
    ty = my + LH
    for m in methods:
        svg.text(x + 10, ty, "+" + m, FS, anchor="start")
        ty += LH

# ---------------------------------------------------------------- associations
# Pen houses birds.
y = T("Pen") + 150
assoc([(R("Pen"), y), (L("Broodcock"), y)], "0..1", "*", "houses", m1_off=(16, -8), m2_off=(-14, -8))
# A bird has photographs.
y = T("BroodcockPhoto") + 110
assoc([(L("Broodcock"), y), (R("BroodcockPhoto"), y)], "1", "*", "has", m1_off=(-12, -8), m2_off=(14, -8))
# Sire and dam: the self-association.
sx = R("Broodcock")
assoc([(sx, T("Broodcock") + 40), (sx + 46, T("Broodcock") + 40), (sx + 46, T("Broodcock") - 30),
       (CX("Broodcock"), T("Broodcock") - 30), (CX("Broodcock"), T("Broodcock"))],
      "0..1", "*", "sire / dam of", label_at=(sx - 40, T("Broodcock") - 38), m1_off=(14, -8), m2_off=(-16, -8))
# The two parents of a mating.
for dy, role in ((120, "sire in"), (190, "dam in")):
    y = T("Broodcock") + dy
    assoc([(R("Broodcock"), y), (L("BreedingRecord"), y)], "1", "*", role, m1_off=(12, -8), m2_off=(-12, -8))
# Health records.
y = T("HealthRecord") + 90
assoc([(R("Broodcock"), y), (L("HealthRecord"), y)], "1", "*", "has", m1_off=(12, -8), m2_off=(-12, -8))
# Performance records, round the health box.
gx = (R("Broodcock") + L("HealthRecord")) / 2
y0 = B("Broodcock") - 60
y1 = T("PerformanceRecord") + 90
assoc([(R("Broodcock"), y0), (gx, y0), (gx, y1), (L("PerformanceRecord"), y1)], "1", "*", "has",
      label_at=(gx - 24, (y0 + y1) / 2), m1_off=(12, -8), m2_off=(-12, -8))
# At most one death record.
assoc([(CX("Broodcock"), B("Broodcock")), (CX("Broodcock"), T("MortalityRecord"))], "1", "0..1", "has",
      label_at=(CX("Broodcock") + 30, (B("Broodcock") + T("MortalityRecord")) / 2 + 5), m1_off=(18, 20),
      m2_off=(26, -8))

# The user records everything and generates reports.
# Lines leaving User higher up take channels further out, so none cross.
ch = [L("User") - 66, L("User") - 50, L("User") - 34, L("User") - 18]
y = T("BreedingRecord") + 110
assoc([(L("User"), y), (R("BreedingRecord"), y)], "1", "*", "records", m1_off=(-12, -8), m2_off=(12, -8))
y = T("HealthRecord") + 150
yu = T("User") + 300
assoc([(L("User"), yu), (ch[0], yu), (ch[0], y), (R("HealthRecord"), y)], "1", "*", "records",
      label_at=(R("HealthRecord") + 75, y - 8), m1_off=(-12, -8), m2_off=(12, -8))
y = T("PerformanceRecord") + 150
yu = T("User") + 360
assoc([(L("User"), yu), (ch[1], yu), (ch[1], y), (R("PerformanceRecord"), y)], "1", "*", "records",
      label_at=(R("PerformanceRecord") + 75, y - 8), m1_off=(-12, -8), m2_off=(12, -8))
# Mortality and photos sit on the far side: under everything.
yb1 = H - 110
yb2 = H - 60
yu = T("User") + 420
assoc([(L("User"), yu), (ch[2], yu), (ch[2], yb1), (CX("MortalityRecord") + 60, yb1),
       (CX("MortalityRecord") + 60, B("MortalityRecord"))], "1", "*", "records",
      label_at=(CX("MortalityRecord") + 200, yb1 - 8), m1_off=(-12, -8), m2_off=(18, 22))
yu = T("User") + 470
assoc([(L("User"), yu), (ch[3], yu), (ch[3], yb2), (CX("BroodcockPhoto"), yb2),
       (CX("BroodcockPhoto"), B("BroodcockPhoto"))], "1", "*", "uploads",
      label_at=(CX("BroodcockPhoto") + 260, yb2 - 8), m1_off=(-12, -8), m2_off=(18, 22))
# Reports.
assoc([(CX("User"), B("User")), (CX("User"), T("Report"))], "1", "*", "generates",
      label_at=(CX("User") + 52, (B("User") + T("Report")) / 2 + 5), m1_off=(14, 20), m2_off=(14, -8))

print("class", svg.save(f"{OUT}/class"))
