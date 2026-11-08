# Prompt: Design Specifications chapter (UML + ERD + Requirements + Architecture)

Copy everything below the line into Claude Code, opened in the **project folder of the system** you are documenting. Fill in the three `<…>` values first.

---

I need the Design Specifications part of my capstone thesis for **<SYSTEM TITLE, e.g. "Digital Broodcock Farm Record Management System">**, made as a Word document (.docx) that matches my earlier approved paper exactly in order, numbering and formatting.

**Inputs**
- Reference paper (the format to copy): `<FULL PATH TO "(Lague et al., 2025) Online Personal Grooming with a Subscription Based-Based Salon Services App.pdf">`. A copy ships in the kit's `reference` folder.
- The system's source code: this project folder.
- A `Thesis Diagram Kit` folder with drawing scripts from a previous run: `<PATH TO "Thesis Diagram Kit">`. Read `README.md` in it. Reuse and adapt the scripts as templates; their content is for a different system.

**Step 1 — Study the reference.** Render the pages and look at them; don't rely on the extracted text alone. In the PDF, page number = printed page + 15:
- Use Case Diagrams: Figures 3–5, one per user type (PDF pp. 48–54).
- Activity Diagrams: Figures 6–9 (pp. 54–60). Figure 6 is app installation; for a web system, make it "System Activity Diagram for Accessing the System" (open a browser → enter the address → load the home page).
- Class Diagram: Figure 10 (pp. 60–63), followed by one short paragraph per class and a relationships paragraph.
- Requirements Analysis: Tables IV–VII (pp. 63–66). Minimum hardware and software, each for development and for deployment, each table followed by a description.
- System Architecture: Figure 11 (pp. 66–67).
- Database: Figure 12 (p. 73). In the reference this is only a phpMyAdmin screenshot; draw a proper crow's-foot Entity Relationship Diagram instead.

Keep the reference's figure and table numbers. Mirror its wording pattern:
- "Figure N shows the …"
- the figure;
- the caption "Figure N. *Title*", with the title in italics;
- "The diagram in Figure N illustrates …" paragraphs.

You may reuse the reference's intro citations: Nishadha (2022) for use cases, visual-paradigm.com (n.d.) for activity diagrams, Al-Fedaghi for class diagrams.

**Step 2 — Gather facts from the code before drawing anything.** Use a subagent if the codebase is large. Every actor, use case, activity, class, attribute, method, table and relationship must come from the code; invent nothing. Collect:
- **Roles and permissions:** user roles, what each role can do per module (from policies, middleware, guards and routes), what guests can see, and which actions are restricted to one role.
- **Authentication:** self-registration or not, password reset, account deactivation, rate limits.
- **Key workflows:** what each important action changes in the data.
- **Schema:** every table and column with type, nullability, PK/FK/unique, and soft deletes. Use the final state of the migrations.
- **Models:** each model's relationships, domain methods and enums.
- **Versions:** of the stack (composer.json, package.json, Dockerfile or deployment config).

**Step 3 — Draw the diagrams** in the reference's style:
- **General style:** black on white, Times New Roman, a thin frame around each figure. Draw as SVG and render to high-resolution PNG at about 220 dpi.
- **Use case diagrams:**
  - The actor (stick figure) sits left, outside a rounded system boundary. The main use cases form one column.
  - Sub use cases fan out to the right in two staggered columns. Arrows to the far column bend through the gap between near ovals, so no line crosses an oval.
  - Dashed open arrows: «include» points from base to included; «extend» points from extension to base.
  - Actor lines end at each main oval's leftmost point.
  - If a diagram gets crowded (more than about 30 ovals), merge paired actions such as "Add or Edit X". Keep any action that only one role has as its own oval.
- **Activity diagrams:**
  - Use a start dot, rounded action boxes, diamond decisions with YES/NO labels, a thick fork bar into one column per module (each column listing its actions), and an end bullseye under Logout.
  - The sign-in flow must follow the real auth rules: credential check, forgot-password loop, deactivated-account check, and "request an account from the admin" if there is no self-registration.
- **Class diagram:**
  - Use three-part boxes (name / `+type attribute` / `+method()`), right-angled association lines with multiplicities (1, *, 0..1) and association names.
  - Route lines so none cross: a line leaving a box higher up takes a routing channel further out.
- **ERD:**
  - In each table box: the table name in a grey header, then PK/FK/UK markers, column names and italic types.
  - Use crow's-foot ends that match real nullability: a nullable FK is zero-or-one; a required FK is exactly one; a unique FK gives a one-to-zero-or-one relationship.
- **Architecture:** the users' devices (laptop, phone, desktop) connect through an Internet cloud to the web server, which connects to the database and any file storage. Name the real hosting.
- **Check every rendered PNG by looking at it.** Fix any overlapping labels, crossing lines or text running outside boxes before going on.

**Step 4 — Build the .docx** with the `docx` npm library:
- **Page:** US Letter, 1-inch margins.
- **Text:** Times New Roman 12 pt, double spacing, 0.5-inch first-line indent, bold section headings.
- **Lead-in paragraphs:** set `keepNext` and `keepLines` so they stay with their figure.
- **Wide figures** (wide activity diagrams, the class diagram, the ERD) go on landscape pages; size them to fit the page.
- **Tables:**
  - Above each table, "Table IV" centred, then the title centred.
  - Draw only horizontal rules (top, below the header, bottom); no vertical lines.
  - Use two columns: Components | Minimum Requirements.
- **Order:** Design Specifications intro → Use Case → Activity → Class → Requirements Analysis → System Architecture → Entity Relationship Diagram.
- **Before finishing:** export the .docx to PDF through Microsoft Word (COM automation on Windows), render every page, and look at them all.

**Step 5 — Deliver:**
- the .docx in my Downloads folder;
- a folder of the figure PNGs named "Figure N - …".

Tell me which values you assumed rather than found in the code. The development-machine hardware in Table IV is always an assumption: ask me, or propose values and flag them. Also flag anything the code permits but the UI doesn't offer.

**Tool notes for Windows:**
- `pdftoppm` and LibreOffice may be missing. Make a Python venv in a scratch folder and `pip install pymupdf`; it renders PDF pages and SVGs.
- `npm install docx` in a scratch folder, not in the project.
- Write Python and JS to files rather than inline heredocs, because quotes break in Git Bash.
- Don't add dependencies to the project.
