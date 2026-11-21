-- Zadanie 5 - MyCloud (dysk sieciowy)
-- Import przez phpMyAdmin do bazy m1127_z5 (lub uruchom w kliencie mysql).
-- Tabele users i goscieportalu pochodzą z zadania 3 (templatka), rozszerzone o kolumny potrzebne w z5.

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(20) NOT NULL UNIQUE,           -- 6c: login = nazwa katalogu macierzystego
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL,
  failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 6b-iv: kolejne błędne logowania
  blocked_until DATETIME NULL                     -- 6b-iv: blokada konta na 1 minutę
);

-- Wizyty gości portalu (z3) + 6b: udane i błędne próby logowania (data/godzina, IP, login)
CREATE TABLE IF NOT EXISTS goscieportalu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ipaddress VARCHAR(45) NOT NULL,
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  event ENUM('visit', 'login_ok', 'login_fail') NOT NULL DEFAULT 'visit',
  username VARCHAR(20) NULL,                      -- login podany przy próbie logowania
  browser TEXT NULL,
  screen_res VARCHAR(20) NULL,
  window_res VARCHAR(20) NULL,
  colors TINYINT UNSIGNED NULL,
  cookies BOOLEAN NULL,
  java BOOLEAN NULL,
  language VARCHAR(35) NULL,
  INDEX idx_ip (ipaddress),
  INDEX idx_user_event (username, event)
);

-- 6b-iv: trzykrotne błędne logowanie (data/godzina + IP), dostępne dla administratora
CREATE TABLE IF NOT EXISTS break_ins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ipaddress VARCHAR(45) NOT NULL,
  username VARCHAR(20) NOT NULL,
  INDEX idx_user (username)
);

INSERT IGNORE INTO users (username, password) VALUES ('admin', 'admin');
