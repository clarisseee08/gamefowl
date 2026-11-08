# Thesis Diagram Kit

These are the scripts that produced the GFMS Design Specifications chapter (Figures 3–12, Tables IV–VII). They are **templates**: the layout code is reusable, but the content (actors, use cases, classes, tables, paragraph text) belongs to GFMS and must be replaced with the new system's facts.

To use the kit, open Claude Code in the new system's project folder and paste `PROMPT.md`, with its three `<…>` values filled in.

| Script | Makes | What to change |
|---|---|---|
| `scripts/svgkit.py` | Shared SVG helpers: shapes, arrows, text wrapping, PNG export through PyMuPDF | nothing |
| `scripts/usecase.py` | Use case diagrams (Figs 3–5) | the `diagram(...)` calls at the bottom: actor names and `(include/extend, label)` lists |
| `scripts/activity.py` | Activity diagrams (Figs 6–9) | the module lists (`OWNER`, `STAFF`, `VISITOR`) and the top flows (`*_top`) |
| `scripts/classdiag.py` | Class diagram (Fig 10) | `CLASSES`, the `place(...)` positions and the `assoc(...)` routes |
| `scripts/erd.py` | Crow's-foot ERD (Fig 12) | `T_` (tables and columns), the `place(...)` positions and the `rel(...)` routes |
| `scripts/architecture.py` | System architecture (Fig 11) | the labels and which devices and servers are drawn |
| `scripts/design.js` | The .docx: Times 12, double-spaced, figures, captions, tables, landscape pages | all paragraph text and table rows |

## Running the scripts (Windows)

```
python -m venv venv && venv\Scripts\pip install pymupdf
cd scripts
..\venv\Scripts\python usecase.py out     (and the same for activity.py, classdiag.py, erd.py)
..\venv\Scripts\python architecture.py out
npm init -y && npm install docx
node design.js <folder with the PNGs> "<output>.docx"
```

`out` must already exist. Every script writes its PNG there (`architecture.py` writes `out/architecture.png`), and `design.js` reads them all from that folder.

Always look at each PNG, and at the Word-exported PDF of the .docx, before using them.
