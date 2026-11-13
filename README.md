# Case Files: Detective Mystery Game (PHP + MySQL + jQuery AJAX)

Players read a murder case, question suspects, buy extra clues (each costs points), and make ONE final accusation.
A correct accusation scores `base score - points spent on clues` (never below 10). A wrong one scores 0.

## Setup (XAMPP)
1. Copy this folder to `C:\xampp\htdocs\detective` (the folder name must match `BASE_URL` in `config/db.php`).
2. Start Apache and MySQL in XAMPP.
3. phpMyAdmin -> Import -> choose `sql/schema.sql` (it also loads a playable sample case).
4. Open `http://localhost/detective/setup.php` once (creates the admin), then DELETE `setup.php`.
5. Open `http://localhost/detective/`
   - Admin: `admin` / `admin123`
   - Players: use "Join as Detective".

## Lab task mapping
| Feature | Files | Lab task |
|---|---|---|
| Registration form with JS validation (errors in red next to the field) | register.php, assets/validate.js | 2, 7b |
| Login, sessions, inactivity timeout, last-case cookie | login.php, includes/auth.php, case.php, dashboard.php | 7c, 6a, 6b |
| Suspect dropdown loads statement and alibi without reload | ajax/suspect.php, assets/case.js | 12 |
| Evidence board table with paid clue reveal (AJAX) | case.php, ajax/reveal_evidence.php | 12 |
| Notebook checklist (add, tick, delete) | assets/case.js | 4 |
| Final accusation form with validation | accuse.php, assets/validate.js | 2 |
| Score breakdown and leaderboard (bill-style tables) | result.php, leaderboard.php | 5 |
| Admin creates, updates and deletes cases, suspects and evidence | admin/*.php | 8 |
| Case file as XML with DTD and XSL | export_case.php, xml/case.dtd, xml/case.xsl | 9 |

## Task 9 demo (static files)
Open `xml/sample_case.xml` in a browser (use Firefox or Chrome via `http://localhost/detective/xml/sample_case.xml`).
It links `case.dtd` and `case.xsl` from the same folder and renders as styled tables.
The live export `export_case.php?id=1` (available to a player after accusing, always to the admin) generates the same structure from the database.
Add `&download=1` to save it as a file.

## Notes
- The culprit is never sent to the browser until the player has made their accusation.
- Passwords use `password_hash()`, and all queries use prepared statements.
- The notebook is stored in the browser (localStorage), so it is per device.
