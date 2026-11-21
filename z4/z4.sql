-- Zadanie 4 - monitorowanie usług w sieciach IP
-- Import przez phpMyAdmin do bazy m1127_z4 (lub uruchom w kliencie mysql).

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(20) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL,
  worker BOOLEAN NOT NULL DEFAULT FALSE           -- 15e: pracownik odpowiedzialny za monitorowanie
);

CREATE TABLE IF NOT EXISTS domeny (
  id INT AUTO_INCREMENT PRIMARY KEY,
  host TEXT NOT NULL,
  port INT NOT NULL,
  user_id INT NOT NULL,                           -- 14a: właściciel (klucz obcy -> users.id)
  errors INT NOT NULL DEFAULT 0,                  -- 15a: kolejne nieudane próby
  failure DATETIME NULL,                          -- 15b: początek bieżącej awarii
  total_errors INT NOT NULL DEFAULT 0,            -- 15c: łączna liczba nieudanych prób
  CONSTRAINT fk_domeny_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 14g: konto administratora + przykładowi pracownicy (15e)
INSERT IGNORE INTO users (username, password, worker) VALUES
  ('admin', 'admin', FALSE),
  ('kowalski', 'kowalski', TRUE),
  ('nowak', 'nowak', TRUE);

-- 5 i 10: przykładowe hosty (wp.ppl nie istnieje - test wykrywania awarii)
INSERT INTO domeny (host, port, user_id)
SELECT h.host, h.port, u.id
FROM (
  SELECT 'fb.com' AS host, 80 AS port, 'admin' AS owner
  UNION ALL SELECT 'youtube.com', 443, 'admin'
  UNION ALL SELECT 'wp.pl', 80, 'kowalski'
  UNION ALL SELECT 'wp.ppl', 80, 'kowalski'
  UNION ALL SELECT 'onet.pl', 443, 'nowak'
) h
JOIN users u ON u.username = h.owner
WHERE NOT EXISTS (SELECT 1 FROM domeny);
