-- ============================================================
--  College Club Finder  -  MySQL / MariaDB schema + seed data
--  Import via phpMyAdmin or:  mysql -u root < database.sql
-- ============================================================


SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications, event_registrations, events, membership_requests,
  club_members, student_interests, clubs, club_categories, student_profiles, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- users (students, coordinators, admins) ----------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','coordinator','admin') NOT NULL DEFAULT 'student',
  status        ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE student_profiles (
  user_id        INT UNSIGNED PRIMARY KEY,
  roll_number    VARCHAR(40)  NULL,
  department     VARCHAR(100) NULL,
  year_of_study  TINYINT UNSIGNED NULL,
  phone          VARCHAR(20)  NULL,
  skills         VARCHAR(500) NULL,          -- comma separated
  career_goal    VARCHAR(200) NULL,
  bio            TEXT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE club_categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80) NOT NULL UNIQUE,
  slug        VARCHAR(100) NOT NULL UNIQUE,
  icon        VARCHAR(50) NOT NULL DEFAULT 'fa-users',   -- Font Awesome class
  color       VARCHAR(7)  NOT NULL DEFAULT '#6C3CE9',
  description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE clubs (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id         INT UNSIGNED NOT NULL,
  coordinator_user_id INT UNSIGNED NULL,
  name                VARCHAR(120) NOT NULL,
  slug                VARCHAR(140) NOT NULL UNIQUE,
  tagline             VARCHAR(160) NULL,
  description         TEXT NOT NULL,
  logo                VARCHAR(255) NULL,
  faculty_coordinator VARCHAR(100) NULL,
  student_coordinator VARCHAR(100) NULL,
  activities          TEXT NULL,              -- one per line
  requirements        TEXT NULL,
  contact_email       VARCHAR(150) NULL,
  contact_phone       VARCHAR(20)  NULL,
  meeting_info        VARCHAR(200) NULL,
  department          VARCHAR(100) NULL,      -- NULL = open to all departments
  skills              VARCHAR(300) NULL,      -- comma separated skill tags
  coordinator_can_approve TINYINT(1) NOT NULL DEFAULT 1,
  status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES club_categories(id) ON DELETE RESTRICT,
  FOREIGN KEY (coordinator_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_clubs_category (category_id),
  INDEX idx_clubs_status (status),
  INDEX idx_clubs_created (created_at),
  FULLTEXT INDEX ft_clubs (name, description)
) ENGINE=InnoDB;

CREATE TABLE student_interests (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_interest (user_id, category_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES club_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE club_members (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id   INT UNSIGNED NOT NULL,
  user_id   INT UNSIGNED NOT NULL,
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_member (club_id, user_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE membership_requests (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id     INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  message     VARCHAR(500) NULL,
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_request (club_id, user_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_req_status (status)
) ENGINE=InnoDB;

CREATE TABLE events (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(150) NOT NULL,
  description TEXT NULL,
  venue       VARCHAR(150) NULL,
  event_date  DATETIME NOT NULL,
  capacity    INT UNSIGNED NULL,              -- NULL = unlimited
  created_by  INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_events_date (event_date)
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_event_reg (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  title      VARCHAR(150) NOT NULL,
  message    VARCHAR(300) NULL,
  link       VARCHAR(255) NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user (user_id, is_read)
) ENGINE=InnoDB;

-- ============================================================
--  SEED DATA
--  Admin        admin@college.edu     / Admin@123
--  Coordinators coord1..4@college.edu / Coord@123
--  Students     student1..6@college.edu / Student@123
-- ============================================================
INSERT INTO users (id,name,email,password_hash,role) VALUES
(1,'Site Administrator','admin@college.edu','$2y$10$wlHDCDi4UUE2SNlNMik2HuCgKKZtdz5Z/D7mVPLbckdEMJGJs503y','admin'),
(2,'Riya Sharma','coord1@college.edu','$2y$10$dDn4OIJ4cp25YRiuOXmYNOCLvQYdkX6Oi9elmT1M1zez5S.IzkriW','coordinator'),
(3,'Arjun Mehta','coord2@college.edu','$2y$10$dDn4OIJ4cp25YRiuOXmYNOCLvQYdkX6Oi9elmT1M1zez5S.IzkriW','coordinator'),
(4,'Sneha Iyer','coord3@college.edu','$2y$10$dDn4OIJ4cp25YRiuOXmYNOCLvQYdkX6Oi9elmT1M1zez5S.IzkriW','coordinator'),
(5,'Karan Verma','coord4@college.edu','$2y$10$dDn4OIJ4cp25YRiuOXmYNOCLvQYdkX6Oi9elmT1M1zez5S.IzkriW','coordinator'),
(6,'Aarav Patel','student1@college.edu','$2y$10$MisjGDQB0cP0vMpBnPcm2ukbxagmpxV9lFfBEvyBKN4PqN51t0jfG','student'),
(7,'Diya Nair','student2@college.edu','$2y$10$MisjGDQB0cP0vMpBnPcm2ukbxagmpxV9lFfBEvyBKN4PqN51t0jfG','student'),
(8,'Rohan Gupta','student3@college.edu','$2y$10$MisjGDQB0cP0vMpBnPcm2ukbxagmpxV9lFfBEvyBKN4PqN51t0jfG','student'),
(9,'Meera Joshi','student4@college.edu','$2y$10$MisjGDQB0cP0vMpBnPcm2ukbxagmpxV9lFfBEvyBKN4PqN51t0jfG','student'),
(10,'Vikram Singh','student5@college.edu','$2y$10$MisjGDQB0cP0vMpBnPcm2ukbxagmpxV9lFfBEvyBKN4PqN51t0jfG','student'),
(11,'Ananya Reddy','student6@college.edu','$2y$10$MisjGDQB0cP0vMpBnPcm2ukbxagmpxV9lFfBEvyBKN4PqN51t0jfG','student');

INSERT INTO student_profiles (user_id,roll_number,department,year_of_study,phone,skills,career_goal,bio) VALUES
(6,'CS21001','Computer Science',3,'9876500001','Python,Web Development,Problem Solving','Software Engineer','Loves building things on the web.'),
(7,'EC21014','Electronics',2,'9876500002','Arduino,Embedded Systems,Photography','Robotics Engineer','Tinkerer and amateur photographer.'),
(8,'ME21007','Mechanical',4,'9876500003','Leadership,Public Speaking','Entrepreneur','Wants to launch a startup.'),
(9,'CS21033','Computer Science',1,'9876500004','Machine Learning,Python','Data Scientist','Curious about AI.'),
(10,'BA21010','Management',2,'9876500005','Guitar,Singing','Musician','Plays guitar every evening.'),
(11,'EN21022','English',3,'9876500006','Writing,Poetry,Public Speaking','Author','Writes short stories.');

INSERT INTO club_categories (id,name,slug,icon,color,description) VALUES
(1,'Technical','technical','fa-microchip','#6C3CE9','Build, tinker and ship real technology.'),
(2,'Coding','coding','fa-code','#0EA5A4','Competitive programming, hackathons and open source.'),
(3,'Robotics','robotics','fa-robot','#F97316','Design and program autonomous machines.'),
(4,'AI & Machine Learning','ai-machine-learning','fa-brain','#E11D74','Data, models and intelligent systems.'),
(5,'Sports','sports','fa-futbol','#16A34A','Compete, train and stay active.'),
(6,'Cultural','cultural','fa-masks-theater','#DC2626','Dance, drama and celebrating traditions.'),
(7,'Photography','photography','fa-camera-retro','#7C3AED','Capture campus life through the lens.'),
(8,'Music','music','fa-music','#DB2777','Bands, choirs and open mics.'),
(9,'Entrepreneurship','entrepreneurship','fa-rocket','#EA580C','Pitch ideas and build startups.'),
(10,'Social Service','social-service','fa-hand-holding-heart','#0D9488','Give back to the community.'),
(11,'Literary','literary','fa-feather-pointed','#B45309','Writing, debates and book discussions.');

INSERT INTO clubs (id,category_id,coordinator_user_id,name,slug,tagline,description,faculty_coordinator,student_coordinator,activities,requirements,contact_email,contact_phone,meeting_info,department,skills,coordinator_can_approve,created_at) VALUES
(1,2,2,'CodeCraft','codecraft','Write code. Break limits.','CodeCraft is the campus home for competitive programmers and open-source builders. We run weekly contests, DSA study circles and the annual 24-hour hackathon.','Dr. Meenakshi Rao','Riya Sharma','Weekly coding contests\nDSA study circles\nAnnual 24-hour hackathon\nOpen-source Fridays','Basic knowledge of any programming language. Willingness to attend weekly sessions.','codecraft@college.edu','9000000001','Wednesdays 5 PM, Lab 3','Computer Science','Python,C++,Problem Solving,Web Development',1,DATE_SUB(NOW(),INTERVAL 40 DAY)),
(2,4,3,'NeuralNest','neuralnest','Teaching machines to think.','NeuralNest explores machine learning, deep learning and data science through reading groups, Kaggle sprints and applied projects with faculty mentors.','Dr. Suresh Kumar','Arjun Mehta','Kaggle sprints\nPaper reading group\nML workshops\nIndustry guest talks','Comfortable with Python and basic statistics.','neuralnest@college.edu','9000000002','Fridays 4 PM, AI Lab','Computer Science','Machine Learning,Python,Statistics,Data Analysis',1,DATE_SUB(NOW(),INTERVAL 25 DAY)),
(3,3,4,'RoboForge','roboforge','From circuits to creatures.','RoboForge designs line-followers, robotic arms and drones, and represents the college at national robotics contests.','Prof. Anil Deshmukh','Sneha Iyer','Robot build sessions\nNational contests\nDrone workshop\nArduino bootcamp','Interest in electronics or mechanics. No prior experience required.','roboforge@college.edu','9000000003','Tuesdays & Thursdays 5 PM, Workshop B','Electronics','Arduino,Embedded Systems,CAD,Electronics',1,DATE_SUB(NOW(),INTERVAL 30 DAY)),
(4,7,5,'Shutterbugs','shutterbugs','See the campus differently.','Shutterbugs is a community of photographers and visual storytellers. We host photo walks, editing workshops and an annual exhibition.','Prof. Lakshmi Menon','Karan Verma','Weekend photo walks\nEditing workshops\nAnnual exhibition\nPortrait sessions','Any camera, even a phone, will do.','shutterbugs@college.edu','9000000004','Saturdays 8 AM, Main Gate','','Photography,Editing,Creativity',1,DATE_SUB(NOW(),INTERVAL 20 DAY)),
(5,8,NULL,'Harmony','harmony','Where campus finds its rhythm.','Harmony brings together vocalists, instrumentalists and producers. Join jam sessions, the monthly open mic and the Fest Night showcase.','Prof. Neha Kapoor','Vikram Singh','Jam sessions\nMonthly open mic\nFest Night showcase\nSongwriting circle','Audition at the start of each semester.','harmony@college.edu','9000000005','Mondays 6 PM, Auditorium Annex','','Singing,Guitar,Music Production',1,DATE_SUB(NOW(),INTERVAL 15 DAY)),
(6,9,NULL,'StartUp Garage','startup-garage','Ideas worth building.','StartUp Garage mentors student founders from idea to pitch. Expect founder talks, pitch nights, and a seed-funding competition.','Dr. Rajiv Malhotra','Rohan Gupta','Founder talks\nPitch nights\nSeed-funding contest\nMarket research clinics','Bring an idea or the curiosity to help build one.','startupgarage@college.edu','9000000006','Thursdays 3 PM, Incubation Centre','Management','Leadership,Public Speaking,Marketing,Business Planning',1,DATE_SUB(NOW(),INTERVAL 10 DAY)),
(7,5,NULL,'Velocity Sports Club','velocity-sports','Train hard. Play harder.','Velocity organises inter-department tournaments and coaching for cricket, football, basketball and athletics.','Mr. Rahul Bhatt','Aarav Patel','Inter-department leagues\nMorning fitness camp\nCoaching clinics\nAnnual sports day','Medical fitness certificate.','velocity@college.edu','9000000007','Daily 6 AM, Sports Ground','','Teamwork,Fitness,Leadership',1,DATE_SUB(NOW(),INTERVAL 8 DAY)),
(8,6,NULL,'Rangmanch','rangmanch','Stage. Story. Soul.','Rangmanch is the drama and dance society, staging street plays, folk-dance showcases and the annual cultural fest.','Prof. Sunita Rao','Diya Nair','Street plays\nFolk dance workshops\nCultural fest\nScriptwriting lab','Open to everyone.','rangmanch@college.edu','9000000008','Wednesdays 5 PM, Open Air Theatre','','Acting,Dance,Creativity,Public Speaking',1,DATE_SUB(NOW(),INTERVAL 6 DAY)),
(9,10,NULL,'Seva Circle','seva-circle','Small acts. Big change.','Seva Circle runs blood donation drives, teaching programs for local schools and clean-campus campaigns.','Dr. Pooja Nambiar','Ananya Reddy','Blood donation drives\nVillage teaching visits\nClean-campus campaigns\nFundraisers','Commitment of at least 2 hours a week.','seva@college.edu','9000000009','Saturdays 10 AM, Student Centre','','Empathy,Teamwork,Leadership',1,DATE_SUB(NOW(),INTERVAL 4 DAY)),
(10,11,NULL,'Inkwell Society','inkwell','Words that move.','Inkwell hosts poetry slams, debate nights, creative writing workshops and publishes the annual college magazine.','Prof. Arvind Joshi','Meera Joshi','Poetry slams\nDebate nights\nWriting workshops\nCollege magazine','Love for words.','inkwell@college.edu','9000000010','Tuesdays 4 PM, Library Hall','English','Writing,Poetry,Public Speaking,Editing',1,DATE_SUB(NOW(),INTERVAL 3 DAY)),
(11,1,NULL,'MakerSpace Guild','makerspace-guild','Build it. Break it. Fix it.','A hands-on club for 3D printing, IoT and hardware prototyping with open access to our maker lab.','Prof. Anil Deshmukh','Aarav Patel','3D printing clinics\nIoT builds\nHardware hack nights\nMaker fair','Safety orientation is mandatory.','maker@college.edu','9000000011','Fridays 2 PM, Maker Lab','Mechanical','CAD,3D Printing,Electronics,Problem Solving',1,DATE_SUB(NOW(),INTERVAL 2 DAY)),
(12,2,NULL,'WebWeavers','webweavers','Design the web.','WebWeavers teaches modern front-end and full-stack development and builds sites for campus organisations.','Dr. Meenakshi Rao','Diya Nair','Frontend bootcamp\nClient projects\nUI/UX critiques\nDemo days','HTML/CSS basics help but are not required.','webweavers@college.edu','9000000012','Mondays 4 PM, Lab 1','Computer Science','Web Development,JavaScript,UI/UX,PHP',1,DATE_SUB(NOW(),INTERVAL 1 DAY));

INSERT INTO student_interests (user_id,category_id) VALUES (6,1),(6,2),(6,4),(7,3),(7,7),(8,9),(9,4),(9,2),(10,8),(11,11),(11,6);

INSERT INTO club_members (club_id,user_id,joined_at) VALUES
(1,6,DATE_SUB(NOW(),INTERVAL 30 DAY)),(1,9,DATE_SUB(NOW(),INTERVAL 12 DAY)),(1,2,DATE_SUB(NOW(),INTERVAL 38 DAY)),
(2,9,DATE_SUB(NOW(),INTERVAL 10 DAY)),(2,6,DATE_SUB(NOW(),INTERVAL 9 DAY)),
(3,7,DATE_SUB(NOW(),INTERVAL 20 DAY)),(4,7,DATE_SUB(NOW(),INTERVAL 14 DAY)),
(5,10,DATE_SUB(NOW(),INTERVAL 11 DAY)),(6,8,DATE_SUB(NOW(),INTERVAL 7 DAY)),
(10,11,DATE_SUB(NOW(),INTERVAL 2 DAY)),(12,6,DATE_SUB(NOW(),INTERVAL 1 DAY));

INSERT INTO membership_requests (club_id,user_id,message,status,reviewed_by,reviewed_at,created_at) VALUES
(1,6,'Excited to compete!','approved',1,DATE_SUB(NOW(),INTERVAL 30 DAY),DATE_SUB(NOW(),INTERVAL 31 DAY)),
(2,9,'I want to learn ML.','approved',1,DATE_SUB(NOW(),INTERVAL 10 DAY),DATE_SUB(NOW(),INTERVAL 11 DAY)),
(1,7,'Would love to learn algorithms.','pending',NULL,NULL,DATE_SUB(NOW(),INTERVAL 1 DAY)),
(1,8,'Interested in hackathons.','pending',NULL,NULL,DATE_SUB(NOW(),INTERVAL 2 DAY)),
(3,6,'Robots look amazing!','rejected',1,DATE_SUB(NOW(),INTERVAL 5 DAY),DATE_SUB(NOW(),INTERVAL 6 DAY)),
(5,11,'I sing and write songs.','pending',NULL,NULL,DATE_SUB(NOW(),INTERVAL 1 DAY));

INSERT INTO events (club_id,title,description,venue,event_date,capacity,created_by) VALUES
(1,'Hack the Campus 24h','A 24-hour hackathon to solve real campus problems. Teams of 3-4, prizes for the top three.','Innovation Hall',DATE_ADD(NOW(),INTERVAL 7 DAY),120,2),
(1,'Intro to Competitive Programming','Beginner-friendly workshop on contests, Codeforces and common patterns.','Lab 3',DATE_ADD(NOW(),INTERVAL 3 DAY),60,2),
(2,'Kaggle Sprint: Titanic to Transformers','A guided sprint from classic ML to modern models.','AI Lab',DATE_ADD(NOW(),INTERVAL 10 DAY),40,3),
(3,'Line-Follower Build-Off','Build and race your line-follower robot. Kits provided.','Workshop B',DATE_ADD(NOW(),INTERVAL 5 DAY),30,4),
(4,'Golden Hour Photo Walk','Sunset photo walk around the lake. Bring any camera.','Main Gate',DATE_ADD(NOW(),INTERVAL 4 DAY),NULL,5),
(5,'Open Mic Night','Sing, play, recite - the stage is yours.','Auditorium Annex',DATE_ADD(NOW(),INTERVAL 9 DAY),80,1),
(6,'Pitch Night Season 3','Pitch your startup idea to alumni founders and investors.','Incubation Centre',DATE_ADD(NOW(),INTERVAL 14 DAY),100,1),
(9,'Blood Donation Drive','In partnership with the city blood bank.','Student Centre',DATE_ADD(NOW(),INTERVAL 6 DAY),NULL,1),
(10,'Poetry Slam','An evening of spoken word and open-floor poetry.','Library Hall',DATE_ADD(NOW(),INTERVAL 12 DAY),70,1),
(12,'Frontend Bootcamp Day 1','HTML, CSS and layout fundamentals in one day.','Lab 1',DATE_ADD(NOW(),INTERVAL 8 DAY),50,1),
(7,'Inter-Department Football Cup','Quarter-finals of the annual football league.','Sports Ground',DATE_ADD(NOW(),INTERVAL 15 DAY),NULL,1),
(1,'Algorithms Night (past)','Past event kept for history.','Lab 3',DATE_SUB(NOW(),INTERVAL 10 DAY),50,2);

INSERT INTO event_registrations (event_id,user_id) VALUES (1,6),(1,9),(2,6),(4,7),(5,7),(6,10);

INSERT INTO notifications (user_id,title,message,link) VALUES
(6,'Welcome to College Club Finder','Pick your interests to get personalised club suggestions.','/student/profile.php'),
(6,'Request rejected','Your request to join RoboForge was not approved this time.','/student/dashboard.php'),
(2,'New membership request','Diya Nair wants to join CodeCraft.','/coordinator/requests.php');
