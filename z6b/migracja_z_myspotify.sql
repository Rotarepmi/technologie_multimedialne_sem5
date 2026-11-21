-- Zadanie 6b - myNetflix: punkty 5, 9 i 10 instrukcji.
-- Przekształca strukturę bazy mySpotify (wyeksportowaną z m1127_z6 BEZ danych i zaimportowaną
-- do nowej bazy m1127_z6b, pkt 1-2) w strukturę myNetflix. Uruchom w phpMyAdminie (zakładka SQL)
-- na bazie m1127_z6b. Wynik jest taki sam jak z6b.sql (gotowa struktura do importu od zera).
SET NAMES utf8mb4;

-- 9a: musictype (idmt, name) -> filmtype (idft, name)
ALTER TABLE song DROP FOREIGN KEY fk_song_musictype;
RENAME TABLE musictype TO filmtype;
ALTER TABLE filmtype RENAME COLUMN idmt TO idft;
DELETE FROM filmtype;                     -- gatunki muzyczne nie są potrzebne
ALTER TABLE filmtype AUTO_INCREMENT = 1;

-- 10a: song (ids, title, musician, datetime, idu, filename, lyrics, idmt)
--   -> film (idf, title, director, datetime, idu, filename, subtitles, idft)
ALTER TABLE playlistdatabase DROP FOREIGN KEY fk_pldb_song;
ALTER TABLE song DROP FOREIGN KEY fk_song_user;
RENAME TABLE song TO film;
ALTER TABLE film
  RENAME COLUMN ids TO idf,
  RENAME COLUMN musician TO director,
  RENAME COLUMN lyrics TO subtitles,
  RENAME COLUMN idmt TO idft,
  RENAME INDEX fk_song_user TO fk_film_user,
  RENAME INDEX fk_song_musictype TO fk_film_filmtype,
  ADD CONSTRAINT fk_film_user FOREIGN KEY (idu) REFERENCES users (id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_film_filmtype FOREIGN KEY (idft) REFERENCES filmtype (idft);

-- 14c: playlistdatabase (idpldb, idpl, ids) -> (idpldb, idpl, idf)
ALTER TABLE playlistdatabase
  DROP INDEX uq_playlist_song,
  RENAME COLUMN ids TO idf,
  RENAME INDEX fk_pldb_song TO fk_pldb_film,
  ADD UNIQUE KEY uq_playlist_film (idpl, idf),
  ADD CONSTRAINT fk_pldb_film FOREIGN KEY (idf) REFERENCES film (idf) ON DELETE CASCADE;

-- 9b-c: gatunki video
INSERT INTO filmtype (idft, name) VALUES
  (1, 'dokument'), (2, 'reportaż'), (3, 'publicystyka'), (4, 'film akcji'), (5, 'sci-fi'), (6, 'horror'),
  (7, 'familijny'), (8, 'przyrodniczy'), (9, 'koncert'), (10, 'animowany'), (11, 'inny'),
  (12, 'komedia'), (13, 'dramat'), (14, 'thriller'), (15, 'kryminał'), (16, 'teledysk'), (17, 'poradnik');

-- 6a: tabela users pochodzi z mySpotify; konto administratora (eksport był bez danych)
INSERT IGNORE INTO users (username, password) VALUES ('admin', 'admin');
