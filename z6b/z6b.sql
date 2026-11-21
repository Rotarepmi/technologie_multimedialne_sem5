-- Zadanie 6b - myNetflix (modyfikacja mySpotify z z6a)
-- Import przez phpMyAdmin do bazy m1127_z6b (lub uruchom w kliencie mysql).
-- Tabele users, goscieportalu i break_ins pochodzą z poprzednich zadań (templatka z3 + rozszerzenia z5).
-- Tabela user (idu, login, password) z instrukcji = users (id, username, password) z poprzednich zadań (pkt 6).
-- Collation utf8_polish_ci z instrukcji: w MySQL 8 "utf8" to przestarzały alias utf8mb3,
-- dlatego użyto jego następcy utf8mb4_polish_ci (te same reguły sortowania, pełny zakres Unicode).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,                 -- idu
  username VARCHAR(20) NOT NULL UNIQUE,              -- login = nazwa katalogu films/<login>
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL,
  failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0,
  blocked_until DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wizyty gości portalu (z3) + udane i błędne próby logowania
CREATE TABLE IF NOT EXISTS goscieportalu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ipaddress VARCHAR(45) NOT NULL,
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  event ENUM('visit', 'login_ok', 'login_fail') NOT NULL DEFAULT 'visit',
  username VARCHAR(20) NULL,
  browser TEXT NULL,
  screen_res VARCHAR(20) NULL,
  window_res VARCHAR(20) NULL,
  colors TINYINT UNSIGNED NULL,
  cookies BOOLEAN NULL,
  java BOOLEAN NULL,
  language VARCHAR(35) NULL,
  INDEX idx_ip (ipaddress),
  INDEX idx_user_event (username, event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS break_ins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ipaddress VARCHAR(45) NOT NULL,
  username VARCHAR(20) NOT NULL,
  INDEX idx_user (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9: gatunki video (dawna tabela musictype (idmt, name) z mySpotify)
CREATE TABLE IF NOT EXISTS filmtype (
  idft INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) COLLATE utf8mb4_polish_ci NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 10: pliki video (dawna tabela song (ids, title, musician, datetime, idu, filename, lyrics, idmt));
-- plik leży w films/<login właściciela>/<filename>
CREATE TABLE IF NOT EXISTS film (
  idf INT AUTO_INCREMENT PRIMARY KEY,
  title TEXT COLLATE utf8mb4_polish_ci NOT NULL,
  director TEXT COLLATE utf8mb4_polish_ci NOT NULL,      -- imię i nazwisko reżysera
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idu INT NOT NULL,                                      -- kto umieścił plik video w portalu
  filename TEXT COLLATE utf8mb4_polish_ci NOT NULL,
  subtitles TEXT COLLATE utf8mb4_polish_ci NULL,         -- opcjonalne napisy (np. w formacie SRT/WebVTT)
  idft INT NOT NULL,
  CONSTRAINT fk_film_user FOREIGN KEY (idu) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_film_filmtype FOREIGN KEY (idft) REFERENCES filmtype (idft)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 13: nazwy playlist; domyślnie prywatna (public = FALSE)
CREATE TABLE IF NOT EXISTS playlistname (
  idpl INT AUTO_INCREMENT PRIMARY KEY,
  idu INT NOT NULL,
  name TEXT COLLATE utf8mb4_polish_ci NOT NULL,
  public BOOLEAN NOT NULL DEFAULT FALSE,
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_playlist_user FOREIGN KEY (idu) REFERENCES users (id) ON DELETE CASCADE,
  INDEX idx_public (public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 14: filmy należące do playlist (kolejność odtwarzania = kolejność dodania, idpldb).
-- Kolumna ids z mySpotify przemianowana na idf (pkt 14c instrukcji).
CREATE TABLE IF NOT EXISTS playlistdatabase (
  idpldb INT AUTO_INCREMENT PRIMARY KEY,
  idpl INT NOT NULL,
  idf INT NOT NULL,
  CONSTRAINT fk_pldb_playlist FOREIGN KEY (idpl) REFERENCES playlistname (idpl) ON DELETE CASCADE,
  CONSTRAINT fk_pldb_film FOREIGN KEY (idf) REFERENCES film (idf) ON DELETE CASCADE,
  UNIQUE KEY uq_playlist_film (idpl, idf)                -- ten sam film raz na playliście
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9b: gatunki video z instrukcji, 9c: dodatkowe gatunki
INSERT IGNORE INTO filmtype (idft, name) VALUES
  (1, 'dokument'), (2, 'reportaż'), (3, 'publicystyka'), (4, 'film akcji'), (5, 'sci-fi'), (6, 'horror'),
  (7, 'familijny'), (8, 'przyrodniczy'), (9, 'koncert'), (10, 'animowany'), (11, 'inny'),
  (12, 'komedia'), (13, 'dramat'), (14, 'thriller'), (15, 'kryminał'), (16, 'teledysk'), (17, 'poradnik');

INSERT IGNORE INTO users (username, password) VALUES ('admin', 'admin');
