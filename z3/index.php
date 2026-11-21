<?php
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once 'auth.php';
require_once 'layout.php';

page_start('Strona powitalna');
?>
<p>Zadanie 3 - geolokalizacja i logowanie wizyt gości portalu. Każde wejście na portal (także na stronę logowania)
	jest rejestrowane w tabeli <code>goscieportalu</code>.</p>
<ul>
	<li><a href="goscie.php">Lista wizyt</a> - IP, data, geolokalizacja z linkiem do Google Maps, przeglądarka, rozdzielczość ekranu i okna, kolory, ciasteczka, Java, język.</li>
	<li><a href="goscie_ip.php">Wizyty wg IP</a> - unikalne adresy IP z liczbą wejść, posortowane malejąco.</li>
	<li>Menu <em>Serwer</em> oraz <em>DNS i geolokalizacja</em> - skrypty testowe z instrukcji (punkty 4-11).</li>
</ul>
<?php
page_end();
