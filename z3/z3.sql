-- Zadanie 3 - geolokalizacja, logowanie wizyt gości portalu
-- Import przez phpMyAdmin do bazy m1127_z3 (lub uruchom w kliencie mysql).

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(20) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL
);

-- 12: IP gościa + data/godzina wejścia (pobierana w momencie tworzenia rekordu)
-- 15: przeglądarka (surowy User-Agent, filtrowany dopiero przy wyświetlaniu)
-- 16: dane zebrane w JS po stronie przeglądarki gościa
CREATE TABLE IF NOT EXISTS goscieportalu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ipaddress VARCHAR(45) NOT NULL,
  datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  browser TEXT NULL,
  screen_res VARCHAR(20) NULL,
  window_res VARCHAR(20) NULL,
  colors TINYINT UNSIGNED NULL,
  cookies BOOLEAN NULL,
  java BOOLEAN NULL,
  language VARCHAR(35) NULL,
  INDEX idx_ip (ipaddress)
);

INSERT IGNORE INTO users (username, password) VALUES ('admin', 'admin');
