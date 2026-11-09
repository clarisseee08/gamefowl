"""Draws the GFMS system architecture figure (SVG -> PNG), in the style of the reference Figure 11."""
import math
import sys

import pymupdf

W, H = 1400, 920
out = []


def add(s):
    out.append(s)


FONT = 'font-family="Times New Roman, Times, serif" font-size="30" fill="#000"'


def label(x, y, lines, anchor="middle"):
    for i, t in enumerate(lines):
        add(f'<text x="{x}" y="{y + i * 34}" text-anchor="{anchor}" {FONT}>{t}</text>')


def arrow(x1, y1, x2, y2, w=5, head=22):
    dx, dy = x2 - x1, y2 - y1
    length = math.hypot(dx, dy)
    ux, uy = dx / length, dy / length

    def tip(px, py, dirx, diry):
        bx, by = px - dirx * head, py - diry * head
        nx, ny = -diry, dirx
        return (f'<polygon points="{px},{py} {bx + nx * head * 0.45},{by + ny * head * 0.45} '
                f'{bx - nx * head * 0.45},{by - ny * head * 0.45}" fill="#000"/>')

    add(f'<line x1="{x1 + ux * head * 0.8}" y1="{y1 + uy * head * 0.8}" x2="{x2 - ux * head * 0.8}" '
        f'y2="{y2 - uy * head * 0.8}" stroke="#000" stroke-width="{w}"/>')
    add(tip(x2, y2, ux, uy))
    add(tip(x1, y1, -ux, -uy))


def phone(cx, cy):
    w, h = 88, 150
    x, y = cx - w / 2, cy - h / 2
    add(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="14" fill="#000"/>')
    add(f'<rect x="{x + 8}" y="{y + 22}" width="{w - 16}" height="{h - 56}" rx="3" fill="#fff"/>')
    add(f'<rect x="{cx - 14}" y="{y + 9}" width="28" height="5" rx="2.5" fill="#fff"/>')
    add(f'<circle cx="{cx}" cy="{y + h - 17}" r="8" fill="#fff"/>')


def laptop(cx, cy):
    sw, sh = 190, 120
    x, y = cx - sw / 2, cy - sh / 2 - 14
    add(f'<rect x="{x}" y="{y}" width="{sw}" height="{sh}" rx="10" fill="#000"/>')
    add(f'<rect x="{x + 12}" y="{y + 12}" width="{sw - 24}" height="{sh - 24}" rx="3" fill="#fff"/>')
    by = y + sh + 4
    add(f'<path d="M {x - 28} {by} L {x + sw + 28} {by} L {x + sw + 12} {by + 22} L {x - 12} {by + 22} Z" fill="#000"/>')
    add(f'<rect x="{cx - 26}" y="{by + 3}" width="52" height="6" rx="3" fill="#fff"/>')


def desktop(cx, cy):
    sw, sh = 200, 130
    x, y = cx - sw / 2, cy - sh / 2 - 20
    add(f'<rect x="{x}" y="{y}" width="{sw}" height="{sh}" rx="10" fill="#000"/>')
    add(f'<rect x="{x + 12}" y="{y + 12}" width="{sw - 24}" height="{sh - 34}" rx="3" fill="#fff"/>')
    add(f'<circle cx="{cx}" cy="{y + sh - 11}" r="5" fill="#fff"/>')
    add(f'<path d="M {cx - 22} {y + sh} L {cx + 22} {y + sh} L {cx + 30} {y + sh + 34} L {cx - 30} {y + sh + 34} Z" fill="#000"/>')
    add(f'<rect x="{cx - 62}" y="{y + sh + 32}" width="124" height="12" rx="6" fill="#000"/>')


def server(cx, cy):
    w, h = 104, 168
    x, y = cx - w / 2, cy - h / 2
    add(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="8" fill="#000"/>')
    for i in range(3):
        yy = y + 18 + i * 40
        add(f'<rect x="{x + 14}" y="{yy}" width="{w - 28}" height="24" rx="3" fill="#fff"/>')
        add(f'<circle cx="{x + w - 28}" cy="{yy + 12}" r="5" fill="#000"/>')
        add(f'<rect x="{x + 22}" y="{yy + 9}" width="34" height="6" rx="3" fill="#000"/>')
    add(f'<circle cx="{cx}" cy="{y + h - 16}" r="6" fill="#fff"/>')


def database(cx, cy):
    rx, ry, seg, sw = 62, 18, 46, 7
    top = cy - 75
    for i in range(3):
        y0 = top + i * seg
        add(f'<path d="M {cx - rx} {y0} L {cx - rx} {y0 + seg} A {rx} {ry} 0 0 0 {cx + rx} {y0 + seg} L {cx + rx} {y0}" '
            f'fill="#fff" stroke="#000" stroke-width="{sw}"/>')
    add(f'<ellipse cx="{cx}" cy="{top}" rx="{rx}" ry="{ry}" fill="#fff" stroke="#000" stroke-width="{sw}"/>')
    for i in range(1, 3):
        y0 = top + i * seg
        add(f'<path d="M {cx - rx} {y0} A {rx} {ry} 0 0 0 {cx + rx} {y0}" fill="none" stroke="#000" stroke-width="{sw}"/>')


def storage(cx, cy):
    """A folder holding a photograph."""
    w, h = 150, 110
    x, y = cx - w / 2, cy - h / 2
    add(f'<path d="M {x} {y + 12} L {x} {y + h} L {x + w} {y + h} L {x + w} {y + 22} L {x + 70} {y + 22} '
        f'L {x + 58} {y} L {x + 10} {y} Q {x} {y} {x} {y + 12} Z" fill="#000"/>')
    add(f'<rect x="{x + 22}" y="{y + 38}" width="{w - 44}" height="{h - 54}" rx="3" fill="#fff"/>')
    add(f'<path d="M {x + 30} {y + h - 22} L {x + 62} {y + 62} L {x + 82} {y + 82} L {x + 96} {y + 70} '
        f'L {x + w - 30} {y + h - 22} Z" fill="#000"/>')
    add(f'<circle cx="{x + w - 42}" cy="{y + 54}" r="8" fill="#000"/>')


def cloud(cx, cy):
    sw = 6
    d = (f"M {cx - 92} {cy + 46} A 40 40 0 0 1 {cx - 78} {cy - 22} A 52 52 0 0 1 {cx - 2} {cy - 58} "
         f"A 46 46 0 0 1 {cx + 76} {cy - 20} A 36 36 0 0 1 {cx + 92} {cy + 46} Z")
    add(f'<path d="{d}" fill="#fff" stroke="#000" stroke-width="{sw}" stroke-linejoin="round"/>')
    gy, r = cy + 34, 46
    add(f'<circle cx="{cx}" cy="{gy}" r="{r}" fill="#fff" stroke="#000" stroke-width="{sw}"/>')
    add(f'<ellipse cx="{cx}" cy="{gy}" rx="{r * 0.45}" ry="{r}" fill="none" stroke="#000" stroke-width="4"/>')
    add(f'<line x1="{cx}" y1="{gy - r}" x2="{cx}" y2="{gy + r}" stroke="#000" stroke-width="4"/>')
    add(f'<line x1="{cx - r}" y1="{gy}" x2="{cx + r}" y2="{gy}" stroke="#000" stroke-width="4"/>')
    for dy in (-r * 0.5, r * 0.5):
        hw = math.sqrt(r * r - dy * dy)
        add(f'<line x1="{cx - hw}" y1="{gy + dy}" x2="{cx + hw}" y2="{gy + dy}" stroke="#000" stroke-width="4"/>')


add(f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">')
add(f'<rect x="0" y="0" width="{W}" height="{H}" fill="#fff"/>')
add(f'<rect x="3" y="3" width="{W - 6}" height="{H - 6}" fill="none" stroke="#000" stroke-width="3"/>')

C = (640, 420)
owner, staff, visitor = (215, 170), (215, 650), (640, 735)
web, db, files = (1060, 175), (1000, 650), (1240, 650)

laptop(*owner)
label(owner[0], owner[1] + 110, ["Farm Owner"])
phone(*staff)
label(staff[0], staff[1] + 112, ["Farm Staff"])
desktop(*visitor)
label(visitor[0], visitor[1] + 132, ["Public Visitor"])
cloud(*C)
label(C[0], C[1] - 76, ["Internet"])
server(*web)
label(web[0] - 82, web[1] - 20, ["Web Server", "(Laravel on Render)"], anchor="end")
database(*db)
label(db[0], db[1] + 120, ["Database Server", "(PostgreSQL)"])
storage(*files)
label(files[0], files[1] + 120, ["Photo Storage"])

arrow(owner[0] + 120, owner[1] + 42, C[0] - 90, C[1] - 18)
arrow(staff[0] + 60, staff[1] - 46, C[0] - 92, C[1] + 70)
arrow(visitor[0], visitor[1] - 108, C[0], C[1] + 96)
arrow(C[0] + 94, C[1] - 30, web[0] - 66, web[1] + 66)
arrow(web[0] - 12, web[1] + 98, db[0] - 4, db[1] - 104)
arrow(web[0] + 40, web[1] + 98, files[0] - 24, files[1] - 70)
add("</svg>")

target = sys.argv[1]
with open(target + "/architecture.svg", "w", encoding="utf-8") as fh:
    fh.write("\n".join(out))

doc = pymupdf.open(target + "/architecture.svg")
pix = doc[0].get_pixmap(dpi=200)
pix.save(target + "/architecture.png")
print(pix.width, pix.height)
