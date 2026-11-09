// Design Specifications (use case, activity, class), Requirements Analysis,
// System Architecture and the ERD - in the manuscript's format.
const fs = require('fs');
const path = require('path');
const {
    Document, Packer, Paragraph, TextRun, ImageRun, AlignmentType, Table, TableRow, TableCell,
    WidthType, BorderStyle, PageOrientation, VerticalAlign,
} = require('docx');

const [, , FIG, OUT] = process.argv;
const FONT = 'Times New Roman';
const SIZE = 24;
const DOUBLE = 480;

const run = (text, o = {}) => new TextRun({ text, font: FONT, size: SIZE, ...o });

const body = (text) => new Paragraph({
    spacing: { line: DOUBLE, before: 0, after: 0 },
    indent: { firstLine: 720 },
    children: [run(text)],
});

const lead = (text) => new Paragraph({ // an intro line that must stay with its figure
    spacing: { line: DOUBLE, before: 0, after: 0 },
    indent: { firstLine: 720 },
    keepNext: true,
    keepLines: true,
    children: [run(text)],
});

const plain = (text, o = {}) => new Paragraph({
    spacing: { line: DOUBLE, before: 0, after: 0 },
    keepNext: o.keepNext ?? false,
    children: [run(text, o.run || {})],
});

const heading = (text) => new Paragraph({
    style: 'Heading2',
    keepNext: true,
    children: [run(text, { bold: true })],
});

const blank = () => new Paragraph({ spacing: { line: 240, before: 0, after: 0 }, children: [] });

function dims(file) {
    const b = fs.readFileSync(file);
    return [b.readUInt32BE(16), b.readUInt32BE(20)];
}

function figure(file, number, title, maxW, maxH) {
    const p = path.join(FIG, file);
    const [pw, ph] = dims(p);
    let w = maxW;
    let h = w * ph / pw;
    if (h > maxH) { h = maxH; w = h * pw / ph; }
    return [
        new Paragraph({
            alignment: AlignmentType.CENTER, keepNext: true, spacing: { before: 120, after: 120 },
            children: [new ImageRun({
                type: 'png', data: fs.readFileSync(p),
                transformation: { width: Math.round(w * 96), height: Math.round(h * 96) },
                altText: { title, description: title, name: title },
            })],
        }),
        new Paragraph({
            spacing: { line: DOUBLE, before: 0, after: 0 },
            children: [run(`Figure ${number}. `), run(title, { italics: true })],
        }),
    ];
}

const rule = { style: BorderStyle.SINGLE, size: 6, color: '7F7F7F' };
const none = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };

function table(roman, title, head, rows) {
    const W = [4680, 4680];
    const cell = (text, i, borders, bold = false) => new TableCell({
        width: { size: W[i], type: WidthType.DXA },
        borders,
        margins: { top: 60, bottom: 60, left: 100, right: 100 },
        verticalAlign: VerticalAlign.TOP,
        children: text.split('\n').map((t) => new Paragraph({
            spacing: { line: 360, before: 0, after: 0 },
            children: [run(t, { bold })],
        })),
    });
    const headRow = new TableRow({
        tableHeader: true,
        children: head.map((h, i) => cell(h, i, { top: rule, bottom: rule, left: none, right: none })),
    });
    const bodyRows = rows.map((r, idx) => new TableRow({
        children: r.map((t, i) => cell(t, i, {
            top: none, left: none, right: none,
            bottom: idx === rows.length - 1 ? rule : none,
        })),
    }));
    return [
        new Paragraph({ alignment: AlignmentType.CENTER, keepNext: true, spacing: { line: DOUBLE, before: 0, after: 0 }, children: [run(`Table ${roman}`)] }),
        new Paragraph({ alignment: AlignmentType.CENTER, keepNext: true, spacing: { line: 360, before: 0, after: 0 }, children: [run(title)] }),
        new Table({ width: { size: 9360, type: WidthType.DXA }, columnWidths: W, rows: [headRow, ...bodyRows] }),
        blank(),
    ];
}

const portrait = { size: { width: 12240, height: 15840 }, margin: { top: 1440, bottom: 1440, left: 1440, right: 1440 } };
const landscape = { size: { width: 12240, height: 15840, orientation: PageOrientation.LANDSCAPE }, margin: { top: 1440, bottom: 1440, left: 1440, right: 1440 } };

const S = []; // sections
let cur = [];
const flush = (page) => { S.push({ properties: { page }, children: cur }); cur = []; };
const add = (...xs) => xs.flat().forEach((x) => cur.push(x));

// =============================================================== Design Specifications
add(heading('Design Specifications'));
add(body('To illustrate the development process and the functionalities of the Digital Broodcock Farm Record Management System, the following design specifications were outlined.'));

add(heading('Use Case Diagram'));
add(body('Within the Unified Modeling Language (UML), use case diagrams serve as a visual tool for capturing system behavior. They depict the interactions between actors, the users of the system or other systems, and the system under development, showing which functions each actor uses to achieve a goal. By capturing functional requirements and defining the boundaries of the system, use case diagrams support clear communication among stakeholders and provide a guide for both development and testing (Nishadha, 2022). The system has three actors: the farm owner, who administers the system; the farm staff, who keep the farm’s records; and the public visitors, who need no account and view the farm’s public pages.'));

add(lead('Figure 3 shows the use case diagram for the farm owner. It is composed of 11 use cases: Login, Manage Gamefowl, Manage Breeding Records, Manage Health Records, Manage Performance Records, Manage Mortality Records, Generate Reports, Manage Users, Manage Farm Profile, Manage Own Profile, and Logout.'));
add(figure('uc-owner.png', 3, 'System Use Case Diagram for the Farm Owner', 5.5, 7.6));
add(body('The diagram in Figure 3 illustrates how the farm owner interacts with the Digital Broodcock Farm Record Management System. The owner enters the system through the Login use case, which is extended by Reset Password for an owner who has forgotten the password; a reset link is sent to the email address of the account. After signing in, the owner has access to every module of the system.'));
add(body('Each of the five record modules, Manage Gamefowl, Manage Breeding Records, Manage Health Records, Manage Performance Records and Manage Mortality Records, includes viewing its records and is extended by the operations that change them. Manage Gamefowl is extended by Add or Edit Gamefowl, Delete Gamefowl, Manage Photos and View Family Tree. Manage Breeding Records is extended by Record or Edit a Mating and by Register Hatched Offspring, which creates a record for each chick hatched from a mating. Manage Health Records is extended by Add or Edit Health Record, Delete Health Record and View Vaccination Schedule, which lists the vaccinations and dewormings that are due or overdue. Manage Performance Records and Manage Mortality Records are extended by adding or recording a record and by deleting one. Deleting records is reserved for the owner.'));
add(body('Generate Reports includes selecting a report and its period and viewing the history of the reports generated, and is extended by downloading the report as a CSV file or a PDF document. Manage Users, which only the owner can use, includes viewing the accounts and is extended by Add or Edit User and by Activate or Deactivate User; the owner creates every account, as the system has no self-registration. Manage Farm Profile is extended by Edit Farm Details, which updates the farm’s name, address and contact details shown on the public website. Manage Own Profile is extended by editing the profile details, changing the profile photo and changing the password. The Logout use case ends the owner’s session.'));

add(lead('Figure 4 shows the use case diagram for the farm staff, who are recorded in the system as record keepers. It is composed of 9 use cases: Login, Manage Gamefowl, Manage Breeding Records, Manage Health Records, Manage Performance Records, Manage Mortality Records, Generate Reports, Manage Own Profile, and Logout.'));
add(figure('uc-staff.png', 4, 'System Use Case Diagram for the Farm Staff', 6.0, 7.4));
add(body('The diagram in Figure 4 illustrates the interactions between the farm staff and the system. Like the owner, a staff member signs in through the Login use case, which is extended by Reset Password. The staff keep the day-to-day records of the farm: they can view, add and edit gamefowl, manage their photos and view their family trees; view, record and edit matings and register hatched offspring; view, add and edit health records and view the vaccination schedule; view, add and edit performance records; and view mortality records and record a death.'));
add(body('The staff may also generate reports as CSV files or PDF documents and manage their own profile. Compared with the owner, the staff role cannot delete records, manage users or edit the farm profile; these use cases are absent from Figure 4 because the system refuses them for staff, not merely because they are hidden from view. The session ends with Logout.'));

add(lead('Figure 5 shows the use case diagram for public visitors. It is composed of 6 use cases: View Home Page, Browse Catalogue, View Bird Details, View Family Tree, View About Page, and View Visit Page.'));
add(figure('uc-visitor.png', 5, 'System Use Case Diagram for Public Visitors', 6.0, 7.4));
add(body('The diagram in Figure 5 illustrates how the public side of the system is used. Unlike the owner and the staff, a public visitor does not need an account: the home page, the catalogue, each bird’s page and its family tree, the About page and the Visit page are open to anyone. Browse Catalogue is extended by Search and Filter Birds, which narrows the list by name, band number, bloodline, sex or class, and by Show Birds for Sale. View Bird Details includes View Photos and is extended by View Health Records and View Performance Records, the latter for broodcocks only. View About Page is extended by View Bloodlines, and View Visit Page includes the farm’s contact details and is extended by Call or Email the Farm and Open Map.'));
add(body('Only the birds currently on the farm appear in the catalogue, and only their pages can be opened by a visitor; birds that were sold, have died or belong to another farm are not shown. No account is needed for any of these use cases: the public side of the system has no sign-in, so prospective buyers browse as visitors, and the internal notes and records of the farm remain hidden from them.'));

// =============================================================== Activity
add(heading('Activity Diagram'));
add(body('An activity diagram is a behavioral UML diagram that describes the dynamic aspects of a system. It is an advanced form of a flowchart that models the flow from one activity to another, describing how activities are coordinated to provide a service at different levels of abstraction (visual-paradigm.com, n.d.).'));
add(lead('Figure 6 shows the activity diagram for accessing the system. Because the system is web based, nothing has to be installed on the user’s device.'));
add(figure('act-access.png', 6, 'System Activity Diagram for Accessing the System', 3.0, 4.0));
add(body('The diagram in Figure 6 shows the steps a user takes before using the Digital Broodcock Farm Record Management System. The user opens a web browser on a computer or a mobile phone and enters the system’s web address, and the farm’s home page is loaded. From there, a visitor can browse the public pages, and a member of the farm can sign in.'));
add(plain(' ', { keepNext: true }));
flush(portrait);

add(lead('Figure 7 shows the activity diagram for the farm owner.'));
add(figure('act-owner.png', 7, 'System Activity Diagram for the Farm Owner', 9.0, 5.6));
flush(landscape);

add(body('The diagram in Figure 7 shows the workflow of the farm owner. The process begins with entering the login credentials, which the system validates. If the credentials are invalid, the owner is asked whether the password was forgotten. If not, the owner re-enters the credentials; if so, the owner requests a reset link by email, sets a new password and returns to sign in. Valid credentials are followed by a second check, whether the account is active: a deactivated account is refused with a message, and the process ends. To protect the accounts, failed sign-in attempts are also limited to five per minute.'));
add(body('An active owner is taken to the dashboard, which leads to ten modules. Manage Gamefowl lets the owner view, add, edit and delete gamefowl, manage their photos and view their family trees. Manage Breeding Records covers viewing, recording and editing matings and registering hatched offspring. Manage Health Records covers viewing, adding, editing and deleting health records and viewing the vaccination schedule. Manage Performance Records covers viewing, adding, editing and deleting performance records, and Manage Mortality Records covers viewing and deleting mortality records and recording a death, which also marks the bird as deceased. Generate Reports lets the owner select a report and its period, download it as a CSV file or a PDF document, and view the report history. Manage Users covers viewing, adding and editing accounts and activating or deactivating them; Manage Farm Profile covers viewing and editing the farm’s details; and Manage Own Profile covers editing the profile details, changing the profile photo and changing the password. The workflow ends with Logout, which ends the owner’s session.'));
add(plain(' ', { keepNext: true }));
flush(portrait);

add(lead('Figure 8 shows the activity diagram for the farm staff.'));
add(figure('act-staff.png', 8, 'System Activity Diagram for the Farm Staff', 9.0, 5.6));
flush(landscape);

add(body('The diagram in Figure 8 shows the workflow of a staff member. The process begins with a check of whether the staff member has an account. Because the system has no self-registration, a staff member without one requests an account from the farm owner and receives login credentials before continuing. The staff member then enters the credentials, which pass through the same validation, password reset and active-account checks as the owner’s.'));
add(body('Upon signing in, the staff member reaches the dashboard and eight modules. The staff can view, add and edit gamefowl, manage photos and view family trees; view, record and edit matings and register hatched offspring; view, add and edit health records and view the vaccination schedule; view, add and edit performance records; view mortality records and record a death; generate reports as CSV files or PDF documents; and manage their own profile. Deleting records, managing users and editing the farm profile do not appear, as they are reserved for the owner. The workflow ends with Logout.'));
add(lead('Figure 9 shows the activity diagram for public visitors.'));
add(figure('act-visitor.png', 9, 'System Activity Diagram for Public Visitors', 6.0, 6.5));
add(body('The diagram in Figure 9 shows the workflow on the public side of the system. The visitor opens the farm’s website and views the home page, which leads to four activities. Browsing the catalogue allows the visitor to search and filter the birds, open a bird’s page, view its photographs and details, view its health and performance records, and view its family tree. The About page describes the farm and its bloodlines, and the Visit page shows the farm’s contact details, from which the visitor can call or email the farm or open its location on a map. No sign-in is involved at any point, and the workflow ends when the visitor leaves the website.'));

// =============================================================== Class
add(heading('Class Diagram'));
add(body('In a study by Al-Fedaghi (2017), class diagrams were described as visual blueprints that provide a high-level view of a system by outlining its core elements, the classes, and the connections between them. Rectangles containing a class’s name, its attributes and its methods represent the individual classes, while the edges between them represent relationships such as associations, dependencies and inheritance (Al-Fedaghi, 2019).'));
add(plain(' ', { keepNext: true }));
flush(portrait);

add(lead('Figure 10 illustrates the class structure of the system. This class diagram shows the attributes, methods and relationships of the objects within the application.'));
add(figure('class.png', 10, 'System Class Diagram', 9.0, 5.3));
flush(landscape);

add(body('The diagram in Figure 10 provides a structural view of the Digital Broodcock Farm Record Management System, illustrating its main classes and their attributes, methods and relationships. It encompasses ten classes: User, Broodcock, BroodcockPhoto, Pen, BreedingRecord, HealthRecord, PerformanceRecord, MortalityRecord, Report and FarmSetting.'));
const cls = [
    ['User', 'The User class represents every account in the system: the farm owner and the farm staff. Public visitors have no accounts. It contains attributes such as id, full_name, email, password, role, contact_number, address, position, the path and disk of the profile photo, is_active and timestamps. The role attribute determines what the user may do, and is_active allows the owner to deactivate an account without deleting it. The methods isOwner(), isStaff() and isInternal() check the user’s role, while profilePhotoUrl() and hasProfilePhoto() handle the profile photograph.'],
    ['Broodcock', 'The Broodcock class is the center of the system and represents every bird, male or female. Its attributes include band_number, name, breed, bloodline, class, sex, date_hatched, date_acquired, weight, color, comb_type, leg_color, distinguishing_marks, status, is_external, for_sale and notes. The sire_id and dam_id attributes refer to two other broodcocks, which is how the pedigree of every bird is recorded, and pen_id refers to the pen where the bird is kept. The methods offspring(), ageInMonths(), ageLabel(), isDeceased(), isPubliclyVisible() and displayName() support the bird’s page, the catalogue and the family tree.'],
    ['BroodcockPhoto', 'The BroodcockPhoto class stores the photographs of each bird. It includes broodcock_id, the path and disk of the stored file, a caption, is_primary, which marks the photo shown in lists, and uploaded_by. The url() method returns the address used to display the photograph.'],
    ['Pen', 'The Pen class represents the pens where the birds are kept. It includes a code, name, location, capacity and notes. The methods occupancy(), remainingCapacity() and isFull() report how many birds a pen holds.'],
    ['BreedingRecord', 'The BreedingRecord class records each mating. Its attributes are the sire_id and dam_id of the pair, mating_date, the counts eggs_set, eggs_fertile, eggs_hatched and offspring_count, notes and recorded_by. The methods fertilityRate(), hatchRate(), overallHatchRate() and hasUnregisteredOffspring() compute the results of the mating and show whether hatched chicks still have to be registered.'],
    ['HealthRecord', 'The HealthRecord class records vaccinations, medications, check-ups, treatments and dewormings. It includes broodcock_id, record_type, product_name, dosage, checkup_date, next_due_date, condition, remarks and recorded_by. The methods isOverdue(), isDueSoon(), scheduleState() and daysUntilDue() drive the vaccination schedule and the reminders on the dashboard.'],
    ['PerformanceRecord', 'The PerformanceRecord class records the sparring, conditioning, weigh-ins and derbies of the broodcocks. Its attributes include broodcock_id, event_date, event_type, weight, result, duration_seconds, rating, remarks and recorded_by. The durationLabel() method formats the duration of a bout for display.'],
    ['MortalityRecord', 'The MortalityRecord class records the death of a bird. It includes broodcock_id, date_of_death, cause_of_death, disposal_method, remarks and recorded_by. The ageAtDeathInMonths() method computes the age of the bird when it died.'],
    ['Report', 'The Report class keeps a log of every report generated. It includes report_type, the parameters used to filter the report, its format, row_count, generated_by and generated_at. The ofType() method selects the reports of one kind.'],
    ['FarmSetting', 'The FarmSetting class holds the profile of the farm: farm_name, address, phone, email, the visiting hours and a note for visitors. The current() method returns the single settings record of the farm, which is shown on the public website.'],
];
cls.forEach(([name, text]) => { add(plain(name, { keepNext: true })); add(body(text)); });
add(body('The relationships between these classes show the structure of the system. A Pen houses many Broodcocks, while each Broodcock is kept in at most one pen (0..1 to many). A Broodcock has many BroodcockPhotos, HealthRecords and PerformanceRecords (1 to many) and at most one MortalityRecord (1 to 0..1). A Broodcock can be the sire or the dam of many other broodcocks, and appears as the sire or the dam in many BreedingRecords. A User records many breeding, health, performance and mortality records, uploads many photos and generates many reports (1 to many). The FarmSetting class stands alone, as it describes the farm rather than any record.'));

// =============================================================== Requirements
add(heading('Requirements Analysis'));
add(body('The minimum hardware and software requirements used in the development and the deployment of the system are presented below.'));
add(lead('Table 4 shows the minimum hardware requirements of the system for development.'));
add(table('IV', 'The Minimum Hardware Requirements of the System for Development', ['Hardware Components', 'Minimum Requirements'], [
    ['Processor', '64-bit dual-core processor, 2.0 GHz or faster'],
    ['Memory (RAM)', '8 GB of RAM or higher'],
    ['Storage', '256 GB solid-state drive, or larger'],
]));
add(body('Table 4 describes the minimum hardware specifications for developing the system. A 64-bit dual-core processor of at least 2.0 GHz and 8 gigabytes (GB) of RAM (Random-Access Memory) are required to run the code editor, the PHP and Node.js tools and a local PostgreSQL database at the same time. A solid-state drive (SSD) of at least 256 GB, or larger, is also required.'));
add(table('V', 'The Minimum Software Requirements of the System for Development', ['Software Components', 'Minimum Requirements'], [
    ['Operating System', 'Microsoft Windows 10 or 11'],
    ['Development Environment', 'Visual Studio Code'],
    ['Database', 'PostgreSQL'],
    ['Programming Language', 'PHP 8.2 (Laravel 12, Livewire 4)'],
    ['Scripting Language', 'JavaScript (Alpine.js)'],
    ['Styling', 'Tailwind CSS 4, daisyUI 5'],
    ['Build and Version Control Tools', 'Composer 2, Node.js 20 and npm, Git'],
]));
add(body('Table 5 shows the minimum software specifications for developing the system. The operating system used in development is Microsoft Windows 10 or 11, and the development environment is Visual Studio Code. The database is PostgreSQL. The programming language is PHP 8.2 with the Laravel 12 framework and Livewire 4, and the scripting language is JavaScript through Alpine.js. The interface is styled with Tailwind CSS 4 and daisyUI 5, and the project is built with Composer 2 and Node.js 20 with npm, with Git for version control.'));
add(table('VI', 'The Minimum Hardware Requirements of the System for Deployment', ['Hardware Components', 'Minimum Requirements'], [
    ['Web Server\n    CPU\n    RAM', ' \n0.1 shared virtual CPU (Render free instance)\n512 MB RAM'],
    ['Database Server\n    Storage', ' \n500 MB PostgreSQL storage (Supabase free plan)'],
    ['Client Device', 'Any computer, tablet or smartphone with a current web browser and an internet connection'],
]));
add(body('The minimum hardware requirements for deploying the system are detailed in Table 6. The web server runs on a Render free instance with a shared 0.1 virtual CPU (Central Processing Unit) and 512 megabytes (MB) of RAM, the configuration the system is tuned for. The database server is a PostgreSQL database on the Supabase free plan, with up to 500 MB of storage. On the side of the users, the system requires only a computer, tablet or smartphone with a current web browser and an internet connection; no particular device or operating system is needed, since nothing is installed.'));
add(table('VII', 'The Minimum Software Requirements of the System for Deployment', ['Software Components', 'Minimum Requirements'], [
    ['Web Browser', 'Google Chrome, Microsoft Edge, Mozilla Firefox or Safari (current version)'],
    ['Web Server Software', 'nginx and PHP 8.2-FPM in a Docker container (Alpine Linux)'],
    ['Database', 'PostgreSQL (Supabase)'],
    ['File Storage', 'Supabase Storage'],
    ['Email', 'SMTP mail service'],
]));
add(body('Table 7 outlines the minimum software needed to run and access the deployed system. The users need only a current web browser, such as Google Chrome, Microsoft Edge, Mozilla Firefox or Safari. On the server, the application runs in a Docker container on Alpine Linux, with nginx passing requests to PHP 8.2-FPM. The records are stored in a PostgreSQL database on Supabase and the photographs of the birds in Supabase Storage, while an SMTP mail service sends the password reset links.'));

// =============================================================== Architecture
add(heading('System Architecture'));
add(body('The system architecture serves as a blueprint for the physical deployment of the system. It illustrates how the users, the web server, the database server and the photo storage of the implemented system are connected to one another.'));
add(lead('Figure 11 shows the system architecture of the Digital Broodcock Farm Record Management System.'));
add(figure('architecture.png', 11, 'System Architecture', 6.0, 4.5));
add(body('The system architecture of the Digital Broodcock Farm Record Management System is web based, so no application has to be installed on any device. The farm owner, the farm staff, and public visitors open the system through a web browser on a laptop, a desktop computer or a mobile phone, and every request travels over the Internet to a single web server. The web server, a Laravel application hosted on Render, acts as the hub of the system: it receives each request, checks the user’s role before anything is shown or saved, and returns the page. The farm owner manages user accounts, farm settings and reports; the farm staff record broodcocks, matings, health treatments, performance and mortality, mostly from their phones while working on the farm; and public visitors, who need no account, may view only the public catalogue, each bird’s page and its family tree. Behind the web server, the database server (PostgreSQL) holds every farm record, and a separate photo storage holds the photographs of the birds. Neither is reachable by users directly: all reading and writing passes through the web server, which keeps the records secure and consistent while allowing the farm to be managed from any location with an Internet connection.'));

// =============================================================== ERD
add(heading('Entity Relationship Diagram'));
add(body('An entity relationship diagram (ERD) shows the tables of a database, the columns of each table and the relationships between them. It serves as the blueprint of how the data of the system is stored.'));
add(plain(' ', { keepNext: true }));
flush(portrait);

add(lead('Figure 12 shows the entity relationship diagram of the Digital Broodcock Farm Record Management System.'));
add(figure('erd.png', 12, 'Entity Relationship Diagram', 9.0, 5.4));
flush(landscape);

add(body('The diagram in Figure 12 shows the ten tables of the PostgreSQL database of the system: users, broodcocks, broodcock_photos, pens, breeding_records, health_records, performance_records, mortality_records, reports and farm_settings. Each table has a primary key (PK), id. Foreign keys (FK) link a table to another, and unique keys (UK) prevent duplicate values, such as two birds with the same band number or two accounts with the same email address. Every table records when each row was created and updated, and the record tables keep a deleted_at column, so that a deleted record is hidden rather than erased.'));
add(body('The relationships are drawn in crow’s foot notation. The broodcocks table is at the center: a bird is kept in zero or one pen, has zero or many photographs, health records and performance records, and has zero or one mortality record, since a bird can die only once. The broodcocks table also refers to itself through sire_id and dam_id, so that every bird is linked to its parents and its family tree can be followed back through the records of the farm. Each breeding record requires exactly one sire and one dam from the broodcocks table. The users table is linked to the record tables through recorded_by, to the photographs through uploaded_by and to the reports through generated_by; these links are optional, so that the records remain if an account is ever removed. The farm_settings table stands alone and holds the profile of the farm.'));
flush(portrait);

const doc = new Document({
    creator: 'Digital Broodcock Farm Record Management System',
    title: 'Design Specifications',
    styles: {
        default: { document: { run: { font: FONT, size: SIZE } } },
        paragraphStyles: [{
            id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
            run: { font: FONT, size: SIZE, bold: true, color: '000000' },
            paragraph: { spacing: { line: DOUBLE, before: 0, after: 0 }, keepNext: true, outlineLevel: 1 },
        }],
    },
    sections: S,
});

Packer.toBuffer(doc).then((buf) => { fs.writeFileSync(OUT, buf); console.log('written', buf.length); });
