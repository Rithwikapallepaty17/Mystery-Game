CREATE DATABASE IF NOT EXISTS detective_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE detective_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  role ENUM('player','admin') NOT NULL DEFAULT 'player',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  victim VARCHAR(150) NOT NULL,
  location VARCHAR(150) NOT NULL,
  incident_time VARCHAR(60) NOT NULL,
  difficulty ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
  base_score INT NOT NULL DEFAULT 100,
  summary TEXT NOT NULL,
  solution TEXT NOT NULL,
  culprit_id INT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS suspects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  case_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  occupation VARCHAR(100) NOT NULL,
  statement TEXT NOT NULL,
  alibi TEXT NOT NULL,
  motive TEXT NOT NULL,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS evidence (
  id INT AUTO_INCREMENT PRIMARY KEY,
  case_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  found_at VARCHAR(150) NOT NULL,
  cost INT NOT NULL DEFAULT 0,          -- 0 = free clue, visible from the start
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS evidence_unlocked (
  user_id INT NOT NULL,
  evidence_id INT NOT NULL,
  unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, evidence_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (evidence_id) REFERENCES evidence(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS accusations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  case_id INT NOT NULL,
  suspect_id INT NOT NULL,
  reasoning TEXT NOT NULL,
  is_correct TINYINT(1) NOT NULL,
  clues_used INT NOT NULL,
  points_spent INT NOT NULL,
  score INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_one_accusation (user_id, case_id),   -- the accusation is final: one per player per case
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
  FOREIGN KEY (suspect_id) REFERENCES suspects(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------
-- Sample case so the game is playable right away
-- ---------------------------------------------------------------
INSERT INTO cases (id, title, victim, location, incident_time, difficulty, base_score, summary, solution, culprit_id, is_published) VALUES
(1, 'The Last Toast at Rosewood Manor',
 'Edmund Rosewood (62), retired antiques dealer',
 'Rosewood Manor, the study on the first floor',
 'Friday, 9:40 PM',
 'medium', 100,
 'During his anniversary dinner, Edmund Rosewood excused himself to his study for a quiet toast. Minutes later a scream rang through the manor. Edmund was found slumped over his desk beside a half-empty brandy glass. The study window was latched from the inside, and the only door opens onto a hallway. Four people had reasons to be at the manor tonight, and one of them is lying.',
 'Dr. Julian Marsh poisoned the brandy with digitoxin, a heart medication he obtained through fraudulent prescriptions. Edmund had discovered the fraud and planned to report it to the medical board. The gate log proves Marsh arrived at 8:55 PM, not 9:35 PM as he claimed, which gave him time to slip the drug into the brandy tray left unattended in the hallway. Clara has a continuous video alibi, Victor left the estate before Marsh arrived, and Mrs. Pritchard poured the brandy from a sealed bottle in front of the cook.',
 4, 1);

INSERT INTO suspects (id, case_id, name, occupation, statement, alibi, motive) VALUES
(1, 1, 'Clara Rosewood', 'Niece, art student',
 'I was in the attic studio painting from about half past eight. I heard the scream and ran down the stairs. I loved my uncle, even if he had just cut my allowance.',
 'Says she was alone in the attic studio from 8:30 PM until the scream. Nobody saw her there.',
 'Edmund had cut her allowance and threatened to stop paying her tuition.'),
(2, 1, 'Victor Hale', 'Business partner',
 'We argued about the sale of the Rosewood collection. I left at 8:45 sharp, drove home and did not come back. I only learned about the death when the police phoned me.',
 'Claims he left the manor before 9 PM and was at home when the police called.',
 'Edmund suspected Victor of undervaluing items to pocket the difference.'),
(3, 1, 'Mrs. Agnes Pritchard', 'Housekeeper',
 'I poured Mr. Rosewood his brandy from a fresh bottle at 8:58 and left the tray in the hallway outside his study, as I always do. After that I was in the kitchen with the cook all night.',
 'The cook says Mrs. Pritchard was in the kitchen from 9:00 PM onward.',
 'Edmund had told her he planned to sell the manor, ending thirty years of service.'),
(4, 1, 'Dr. Julian Marsh', 'Family physician',
 'I was at my clinic until 9:30, then drove over because Edmund asked me to join the toast. I arrived at 9:35, just as the scream rang out, and I pronounced him dead.',
 'Says he was working late at his clinic until 9:30 PM and reached the manor at 9:35 PM.',
 'Edmund had recently asked him odd questions about patient records and prescriptions.');

INSERT INTO evidence (id, case_id, title, description, found_at, cost) VALUES
(1, 1, 'Crime scene report',
 'Edmund Rosewood was found slumped over his desk at 9:40 PM. A half-empty brandy glass sat beside him. The study window was latched from the inside, and the door opens onto the main hallway.',
 'Study, first floor', 0),
(2, 1, 'Toxicology note',
 'Cause of death was an overdose of digitoxin, a heart medication, dissolved in the brandy. The dose was large and fast acting. Whoever did this had access to the drug and knew how to dose it.',
 'Police laboratory', 10),
(3, 1, 'Front gate camera log',
 '8:52 PM: the silver sedan of Victor Hale leaves the estate. 8:55 PM: a dark green car registered to Dr. Julian Marsh enters. No other vehicles are logged before the police arrive.',
 'Security booth at the front gate', 10),
(4, 1, 'Kitchen record',
 'The cook confirms Mrs. Pritchard poured the brandy at 8:58 PM from a freshly sealed bottle in front of her, then stayed in the kitchen. The tray was left unattended in the hallway for about twenty minutes until Edmund collected it at 9:20 PM.',
 'Kitchen', 10),
(5, 1, 'Livestream recording of Clara',
 'Clara streamed herself painting in the attic studio from 8:30 PM to 9:45 PM. The video is continuous and timestamped, and her easel is visible the whole time.',
 'Attic studio and online archive', 10),
(6, 1, 'Pharmacy records',
 'Digitoxin was dispensed three weeks ago on a prescription signed by Dr. Julian Marsh, for a patient who had died two months earlier.',
 'Village pharmacy', 15),
(7, 1, 'Final diary entry',
 'Found the pharmacy slips for Mrs. Dalton, who died in August. J. signed every one. Told him he has until Monday to explain, or I go to the medical board.',
 'Study desk drawer', 20);
