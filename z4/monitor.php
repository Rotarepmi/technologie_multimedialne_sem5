<?php
declare(strict_types=1);

// 15b.ii: czas serwera hostingowego może być w innej strefie - ustawiamy czas polski
date_default_timezone_set('Europe/Warsaw');

const REFRESH_SECONDS = 10;   // co ile sekund strona ponawia sprawdzanie
const CONNECT_TIMEOUT = 3;    // limit fsockopen(); 30 s z instrukcji blokowałoby stronę przy kilku awariach
const PULSE_AFTER = 20;       // awaria > 20 s: grubsza, pulsująca ikona
const SOUND_AFTER = 40;       // awaria > 40 s: alarm dźwiękowy

const SOUNDS = [
  1 => ['file' => 'media/sounds/alarm1.mp3', 'label' => 'Brzęczyk (beep-beep)'],
  2 => ['file' => 'media/sounds/alarm2.mp3', 'label' => 'Syrena'],
  3 => ['file' => 'media/sounds/alarm3.mp3', 'label' => 'Niski buzzer'],
];

// Rodzaj usługi/urządzenia rozpoznawany po numerze portu
const SERVICE_TYPES = [
  'www'     => ['label' => 'Serwer WWW',        'ports' => [80, 443, 8000, 8080]],
  'db'      => ['label' => 'Serwer baz danych', 'ports' => [1433, 1521, 3306, 5432, 6379, 27017]],
  'mail'    => ['label' => 'Serwer poczty',     'ports' => [25, 110, 143, 465, 587, 993, 995]],
  'ap'      => ['label' => 'Access point',      'ports' => [8443, 8880, 10001]],
  'router'  => ['label' => 'Router',            'ports' => [53, 179, 2000, 8291]],
  'switch'  => ['label' => 'Switch zarządzalny', 'ports' => [23, 161, 162]],
  'printer' => ['label' => 'Drukarka sieciowa', 'ports' => [515, 631, 9100]],
  'server'  => ['label' => 'Inna usługa',       'ports' => []],
];

// Ikony w jednym stylu: kontur 24x24, kolor i grubość linii dziedziczone z CSS
const ICON_PATHS = [
  'www'     => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20a15 15 0 0 1 0-20"/>',
  'db'      => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>',
  'mail'    => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
  'ap'      => '<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><path d="M12 20h.01"/>',
  'router'  => '<rect x="2" y="14" width="20" height="8" rx="2"/><path d="M6 18h.01M10 18h.01M15 10v4"/><path d="M17.84 7.17a4 4 0 0 0-5.66 0"/><path d="M20.66 4.34a8 8 0 0 0-11.31 0"/>',
  'switch'  => '<rect x="2" y="7" width="20" height="10" rx="2"/><path d="M6 11v2M9 11v2M12 11v2M15 11v2M18 11v2"/>',
  'printer' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
  'server'  => '<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><path d="M6 6h.01M6 18h.01"/>',
];

function service_type(int $port): string
{
  foreach (SERVICE_TYPES as $type => $def) {
    if (in_array($port, $def['ports'], true)) {
      return $type;
    }
  }
  return 'server';
}

/** @param string $state ok | fail | fail-long */
function service_icon(string $type, string $state, string $title = ''): string
{
  $paths = ICON_PATHS[$type] ?? ICON_PATHS['server'];
  // Awaria = ta sama ikona na czerwono i przekreślona (biała "otoczka" pod kreską dla czytelności)
  $slash = $state === 'ok' ? '' : '<path d="M3 3l18 18" class="svc-slash-halo"/><path d="M3 3l18 18"/>';
  $titleTag = $title !== '' ? '<title>' . htmlspecialchars($title) . '</title>' : '';
  return '<svg class="svc-icon svc-' . $state . '" viewBox="0 0 24 24" role="img" aria-label="' . htmlspecialchars($title) . '">'
    . $titleTag . $paths . $slash . '</svg>';
}

function format_hms(int $seconds): string
{
  return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
}

/**
 * Sprawdza usługę (fsockopen) i zapisuje wynik w tabeli domeny (15a-c).
 * Zwraca wiersz uzupełniony o: ok, stan, down_for, state.
 */
function check_service(mysqli $link, array $row): array
{
  $fp = @fsockopen($row['host'], (int)$row['port'], $errno, $errstr, CONNECT_TIMEOUT);
  $id = (int)$row['id'];

  if ($fp) {
    fclose($fp);
    // host odpowiada: zerujemy licznik kolejnych błędów i czas awarii, total_errors zostaje
    $stmt = mysqli_prepare($link, 'UPDATE domeny SET errors = 0, failure = NULL WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $row['errors'] = 0;
    $row['failure'] = null;
    $row['ok'] = true;
    $row['stan'] = 'OK';
  } else {
    // pierwszy błąd w serii ustala czas awarii, kolejne go nie nadpisują
    $failure = $row['failure'] ?? date('Y-m-d H:i:s');
    $stmt = mysqli_prepare($link, 'UPDATE domeny SET errors = errors + 1, total_errors = total_errors + 1, failure = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $failure, $id);
    $row['errors'] = (int)$row['errors'] + 1;
    $row['total_errors'] = (int)$row['total_errors'] + 1;
    $row['failure'] = $failure;
    $row['ok'] = false;
    $row['stan'] = trim("$errno $errstr") ?: 'Brak połączenia';
  }
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  $row['down_for'] = $row['failure'] ? max(0, time() - strtotime($row['failure'])) : 0;
  $row['state'] = $row['ok'] ? 'ok' : ($row['down_for'] > PULSE_AFTER ? 'fail-long' : 'fail');
  return $row;
}

/** Zwraca komunikat błędu albo null, gdy host i port są poprawne. */
function validate_service(string $host, string $port): ?string
{
  if ($host === '' || strlen($host) > 253) {
    return 'Podaj nazwę domenową lub adres IP hosta.';
  }
  if (!filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
    return 'Niepoprawna nazwa hosta lub adres IP.';
  }
  if (filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
    return 'Port musi być liczbą z zakresu 1-65535.';
  }
  return null;
}

/**
 * Sprawdza wszystkie przekazane usługi i wyświetla tabelę monitoringu.
 * Blok #monitor jest co REFRESH_SECONDS podmieniany przez twoj_js.js (monitorStart).
 */
function render_monitor(mysqli $link, array $rows, bool $showOwner): void
{
  $checked = array_map(fn(array $r) => check_service($link, $r), $rows);
  $failing = count(array_filter($checked, fn($r) => !$r['ok']));
  $alarm = (bool)array_filter($checked, fn($r) => !$r['ok'] && $r['down_for'] > SOUND_AFTER);
  $soundOn = !empty($_SESSION['sound_on']);
  $sound = SOUNDS[$_SESSION['sound'] ?? 1] ?? SOUNDS[1];
?>
  <div id="monitor"
       data-alarm="<?= $alarm && $soundOn ? '1' : '0' ?>"
       data-sound="<?= htmlspecialchars($sound['file']) ?>">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
      <a href="dodaj1.php" class="btn btn-primary btn-sm">+ Dodaj usługę</a>
      <a href="kasuj1.php" class="btn btn-outline-danger btn-sm">Usuń usługę</a>
      <?php if (is_admin()): ?><a href="staff.php" class="btn btn-outline-secondary btn-sm">Przypisz pracownikowi</a><?php endif; ?>
      <span class="ms-auto small text-muted">
        <span class="badge bg-success"><?= count($checked) - $failing ?> OK</span>
        <span class="badge bg-danger"><?= $failing ?> awarii</span>
        · sprawdzono <?= date('H:i:s') ?>, odświeżanie co <?= REFRESH_SECONDS ?> s
      </span>
    </div>

    <?php if ($alarm && !$soundOn): ?>
      <div class="alert alert-warning py-2">Awaria trwa ponad <?= SOUND_AFTER ?> s, ale alarm dźwiękowy jest wyłączony (<a href="ustawienia.php">ustawienia</a>).</div>
    <?php endif; ?>
    <div id="sound-unlock" class="alert alert-danger py-2 d-none">
      Przeglądarka zablokowała alarm dźwiękowy.
      <button type="button" class="btn btn-danger btn-sm ms-2" onclick="unlockAlarm()">Włącz dźwięk</button>
    </div>

    <?php if (!$checked): ?>
      <div class="alert alert-info">Nie monitorujesz jeszcze żadnych usług. <a href="dodaj1.php">Dodaj pierwszą.</a></div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle bg-white shadow-sm">
          <thead class="table-light">
            <tr>
              <th>id</th><th></th><th>Host</th><th>Port</th><th>Stan</th>
              <th title="Kolejne nieudane próby">Błędy</th><th>Awaria od</th><th title="Łączny czas niedostępności">Łączny czas awarii</th>
              <?php if ($showOwner): ?><th>Odpowiedzialny</th><?php endif; ?>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($checked as $r):
            $type = service_type((int)$r['port']);
            $label = SERVICE_TYPES[$type]['label'] . ($r['ok'] ? ' - działa' : ' - awaria ' . format_hms($r['down_for']));
          ?>
            <tr class="<?= $r['ok'] ? '' : 'table-danger' ?>">
              <td><?= (int)$r['id'] ?></td>
              <td><?= service_icon($type, $r['state'], $label) ?></td>
              <td class="text-break"><?= htmlspecialchars($r['host']) ?></td>
              <td><?= (int)$r['port'] ?></td>
              <td class="small <?= $r['ok'] ? 'text-success fw-semibold' : 'text-danger' ?>"><?= htmlspecialchars($r['stan']) ?></td>
              <td><?= (int)$r['errors'] ?></td>
              <td class="text-nowrap"><?= $r['failure'] ? htmlspecialchars($r['failure']) : '-' ?></td>
              <td><?= format_hms((int)$r['total_errors'] * REFRESH_SECONDS) ?></td>
              <?php if ($showOwner): ?><td><?= htmlspecialchars($r['username']) ?></td><?php endif; ?>
              <td class="text-end">
                <a class="btn btn-outline-danger btn-sm py-0" href="kasuj1.php?<?= http_build_query(['host' => $r['host'], 'port' => $r['port']]) ?>">Usuń</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <details class="small text-muted mb-5">
    <summary>Legenda ikon</summary>
    <div class="d-flex flex-wrap gap-4 mt-2">
      <?php foreach (SERVICE_TYPES as $type => $def): ?>
        <div class="text-center">
          <?= service_icon($type, 'ok', $def['label'] . ' - działa') ?>
          <?= service_icon($type, 'fail', $def['label'] . ' - awaria do ' . PULSE_AFTER . ' s') ?>
          <?= service_icon($type, 'fail-long', $def['label'] . ' - awaria powyżej ' . PULSE_AFTER . ' s') ?>
          <div><?= htmlspecialchars($def['label']) ?><?= $def['ports'] ? ' (' . implode(', ', $def['ports']) . ')' : '' ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="mt-2 mb-0">Zielona: działa. Czerwona, przekreślona: awaria do <?= PULSE_AFTER ?> s. Gruba i pulsująca: awaria powyżej <?= PULSE_AFTER ?> s. Powyżej <?= SOUND_AFTER ?> s odtwarzany jest alarm dźwiękowy.</p>
  </details>
  <script>monitorStart(<?= REFRESH_SECONDS * 1000 ?>);</script>
<?php
}
