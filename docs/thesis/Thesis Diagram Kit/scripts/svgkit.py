"""A tiny SVG toolkit for thesis-style UML figures (black on white, Times)."""
import math

import pymupdf

SERIF = "Times New Roman, Times, serif"


def esc(t):
    return str(t).replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace("«", "&#171;").replace("»", "&#187;")


def text_width(t, size):
    """Rough width of Times text: good enough to size boxes and wrap lines."""
    narrow = sum(1 for c in t if c in "iljtfr.,:;'()| ")
    wide = sum(1 for c in t if c in "MWmw@")
    caps = sum(1 for c in t if c.isupper())
    return size * (0.47 * len(t) - 0.18 * narrow + 0.25 * wide + 0.12 * caps)


def wrap(t, size, max_w):
    words, lines, cur = str(t).split(), [], ""
    for w in words:
        trial = (cur + " " + w).strip()
        if text_width(trial, size) <= max_w or not cur:
            cur = trial
        else:
            lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


class Svg:
    def __init__(self, w, h, border=True):
        self.w, self.h, self.parts = w, h, []
        self.add(f'<rect x="0" y="0" width="{w}" height="{h}" fill="#fff"/>')
        if border:
            self.add(f'<rect x="2" y="2" width="{w - 4}" height="{h - 4}" fill="none" stroke="#000" stroke-width="2.5"/>')

    def add(self, s):
        self.parts.append(s)

    # ---------------------------------------------------------- text
    def text(self, x, y, t, size=20, anchor="middle", weight="normal", style="normal", fill="#000"):
        self.add(f'<text x="{x:.1f}" y="{y:.1f}" text-anchor="{anchor}" font-family="{SERIF}" font-size="{size}" '
                 f'font-weight="{weight}" font-style="{style}" fill="{fill}">{esc(t)}</text>')

    def lines_centered(self, cx, cy, lines, size=20, weight="normal", style="normal", gap=1.18):
        lh = size * gap
        top = cy - (len(lines) - 1) * lh / 2 + size * 0.34
        for i, ln in enumerate(lines):
            self.text(cx, top + i * lh, ln, size, "middle", weight, style)

    # ---------------------------------------------------------- lines
    def line(self, x1, y1, x2, y2, w=2.2, dash=None):
        d = f' stroke-dasharray="{dash}"' if dash else ""
        self.add(f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" stroke="#000" stroke-width="{w}"{d}/>')

    def poly(self, pts, w=2.2, dash=None):
        d = f' stroke-dasharray="{dash}"' if dash else ""
        p = " ".join(f"{x:.1f},{y:.1f}" for x, y in pts)
        self.add(f'<polyline points="{p}" fill="none" stroke="#000" stroke-width="{w}"{d} stroke-linejoin="round"/>')

    def head(self, x, y, ux, uy, size=13, filled=True):
        bx, by = x - ux * size, y - uy * size
        nx, ny = -uy, ux
        pts = f"{x:.1f},{y:.1f} {bx + nx * size * 0.5:.1f},{by + ny * size * 0.5:.1f} {bx - nx * size * 0.5:.1f},{by - ny * size * 0.5:.1f}"
        if filled:
            self.add(f'<polygon points="{pts}" fill="#000"/>')
        else:
            self.add(f'<polyline points="{bx + nx * size * 0.5:.1f},{by + ny * size * 0.5:.1f} {x:.1f},{y:.1f} '
                     f'{bx - nx * size * 0.5:.1f},{by - ny * size * 0.5:.1f}" fill="none" stroke="#000" stroke-width="2"/>')

    def arrow(self, pts, w=2.2, dash=None, open_head=False, size=13):
        """A polyline ending in an arrowhead at its last point."""
        (x1, y1), (x2, y2) = pts[-2], pts[-1]
        L = math.hypot(x2 - x1, y2 - y1) or 1
        ux, uy = (x2 - x1) / L, (y2 - y1) / L
        trimmed = pts[:-1] + [(x2 - ux * (0 if open_head else size * 0.7), y2 - uy * (0 if open_head else size * 0.7))]
        self.poly(trimmed, w, dash)
        self.head(x2, y2, ux, uy, size, filled=not open_head)

    # ---------------------------------------------------------- shapes
    def rect(self, x, y, w, h, rx=0, sw=2.2, fill="#fff"):
        self.add(f'<rect x="{x:.1f}" y="{y:.1f}" width="{w:.1f}" height="{h:.1f}" rx="{rx}" fill="{fill}" stroke="#000" stroke-width="{sw}"/>')

    def ellipse(self, cx, cy, rx, ry, sw=2.2):
        self.add(f'<ellipse cx="{cx:.1f}" cy="{cy:.1f}" rx="{rx:.1f}" ry="{ry:.1f}" fill="#fff" stroke="#000" stroke-width="{sw}"/>')

    def diamond(self, cx, cy, w, h, sw=2.2):
        self.add(f'<polygon points="{cx},{cy - h / 2} {cx + w / 2},{cy} {cx},{cy + h / 2} {cx - w / 2},{cy}" fill="#fff" stroke="#000" stroke-width="{sw}"/>')

    def actor(self, cx, top, label, size=20):
        r = 17
        self.add(f'<circle cx="{cx}" cy="{top + r}" r="{r}" fill="#fff" stroke="#000" stroke-width="2.4"/>')
        self.line(cx, top + 2 * r, cx, top + 2 * r + 52, 2.4)
        self.line(cx - 34, top + 2 * r + 18, cx + 34, top + 2 * r + 18, 2.4)
        self.line(cx, top + 2 * r + 52, cx - 28, top + 2 * r + 96, 2.4)
        self.line(cx, top + 2 * r + 52, cx + 28, top + 2 * r + 96, 2.4)
        for i, ln in enumerate(label.split("\n")):
            self.text(cx, top + 2 * r + 124 + i * size * 1.15, ln, size, weight="bold")

    def start(self, cx, cy, r=13):
        self.add(f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="#000"/>')

    def end(self, cx, cy, r=14):
        self.add(f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="#fff" stroke="#000" stroke-width="2.5"/>')
        self.add(f'<circle cx="{cx}" cy="{cy}" r="{r - 6}" fill="#000"/>')

    def bar(self, x1, x2, y, h=9):
        self.add(f'<rect x="{x1}" y="{y - h / 2}" width="{x2 - x1}" height="{h}" fill="#000"/>')

    def connector(self, cx, cy, label, r=15):
        self.add(f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="#fff" stroke="#000" stroke-width="2"/>')
        self.text(cx, cy + 6, label, 16, weight="bold")

    # ---------------------------------------------------------- output
    def save(self, base, dpi=220):
        with open(base + ".svg", "w", encoding="utf-8") as fh:
            fh.write(f'<svg xmlns="http://www.w3.org/2000/svg" width="{self.w}" height="{self.h}" viewBox="0 0 {self.w} {self.h}">\n'
                     + "\n".join(self.parts) + "\n</svg>")
        doc = pymupdf.open(base + ".svg")
        pix = doc[0].get_pixmap(dpi=dpi)
        pix.save(base + ".png")
        return pix.width, pix.height
