-- Zadanie 6a - mySpotify
-- Import przez phpMyAdmin do bazy m1127_z6 (lub uruchom w kliencie mysql).
-- Tabele users, goscieportalu i break_ins pochodzą z poprzednich zadań (templatka z3 + rozszerzenia z5).
-- Tabela user (idu, login, password) z instrukcji = users (id, username, password) z poprzednich zadań (pkt 2).
-- Collation utf8_polish_ci z instrukcji: w MySQL 8 "utf8" to przestarzały alias utf8mb3,
-- dlatego użyto jego następcy utf8mb4_polish_ci (te same reguły sortowania, pełny zakres Unicode).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,                 -- idu
  username VARCHAR(20) NOT NULL UNIQUE,              -- login = nazwa katalogu songs/<login>
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

-- 5: gatunki muzyczne
CREATE TABLE IF NOT EXISTS musictype (
  idmt INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) COLLATE utf8mb4_polish_ci NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 6: utwory audio; plik leży w songs/<login właściciela>/<filename>
CREATE TABLE IF NOT EXISTS song (
  ids INT AUTO_INCREMENT PRIMARY KEY,
  title TEXT COLLATE utf8mb4_polish_ci NOT NULL,
  musician TEXT COLLATE utf8mb4_polish_ci NOT NULL,
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idu INT NOT NULL,                                  -- kto umieścił utwór w portalu
  filename TEXT COLLATE utf8mb4_polish_ci NOT NULL,
  lyrics TEXT COLLATE utf8mb4_polish_ci NULL,        -- opcjonalny tekst piosenki
  idmt INT NOT NULL,
  CONSTRAINT fk_song_user FOREIGN KEY (idu) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_song_musictype FOREIGN KEY (idmt) REFERENCES musictype (idmt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 9: nazwy playlist; domyślnie prywatna (public = FALSE)
CREATE TABLE IF NOT EXISTS playlistname (
  idpl INT AUTO_INCREMENT PRIMARY KEY,
  idu INT NOT NULL,
  name TEXT COLLATE utf8mb4_polish_ci NOT NULL,
  public BOOLEAN NOT NULL DEFAULT FALSE,
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_playlist_user FOREIGN KEY (idu) REFERENCES users (id) ON DELETE CASCADE,
  INDEX idx_public (public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 10: utwory należące do playlist (kolejność odtwarzania = kolejność dodania, idpldb)
CREATE TABLE IF NOT EXISTS playlistdatabase (
  idpldb INT AUTO_INCREMENT PRIMARY KEY,
  idpl INT NOT NULL,
  ids INT NOT NULL,
  CONSTRAINT fk_pldb_playlist FOREIGN KEY (idpl) REFERENCES playlistname (idpl) ON DELETE CASCADE,
  CONSTRAINT fk_pldb_song FOREIGN KEY (ids) REFERENCES song (ids) ON DELETE CASCADE,
  UNIQUE KEY uq_playlist_song (idpl, ids)            -- ten sam utwór raz na playliście
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO musictype (idmt, name) VALUES
  (1, 'pop'), (2, 'rock'), (3, 'hip-hop'), (4, 'electronic dance'), (5, 'R&B'), (6, 'latin'),
  (7, 'country'), (8, 'metal'), (9, 'jazz'), (10, 'classic'), (11, 'inny'),
  -- 5b: dodatkowe gatunki
  (12, 'blues'), (13, 'reggae'), (14, 'folk'), (15, 'disco polo'), (16, 'soundtrack'), (17, 'podcast');

INSERT IGNORE INTO users (username, password) VALUES ('admin', 'admin');
