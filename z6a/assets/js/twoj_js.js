function myLoadHeader()
{
	$(document).ready(function()
	{
		$('#myHeader').load("../layout/header.php");
	});
}

// Walidacja Bootstrap dla formularzy .needs-validation (delegacja - działa dla wszystkich stron z layoutu)
document.addEventListener('submit', function (e) {
	const form = e.target;
	if (form.classList && form.classList.contains('needs-validation')) {
		if (!form.checkValidity()) {
			e.preventDefault();
			e.stopPropagation();
		}
		form.classList.add('was-validated');
	}
}, true);

function formatBytes(b)
{
	const u = ['B', 'KB', 'MB', 'GB'];
	let i = 0;
	while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
	return (i ? b.toFixed(1) : b) + ' ' + u[i];
}

// 7: formularz dodawania utworu - kontrola rozmiaru/typu, podpowiedź tytułu i wykonawcy z nazwy
// pliku "Wykonawca - Tytuł.mp3", pasek postępu
function initSongUploadForm(limitBytes)
{
	const input = document.getElementById('fileToUpload');
	const title = document.getElementById('title');
	const musician = document.getElementById('musician');
	input.addEventListener('change', function () {
		input.setCustomValidity('');
		const f = input.files[0];
		if (!f) {
			return;
		}
		if (!/\.mp3$/i.test(f.name)) {
			input.setCustomValidity('Dozwolone są tylko pliki MP3.');
		} else if (limitBytes && f.size > limitBytes) {
			input.setCustomValidity('Plik ' + formatBytes(f.size) + ' przekracza limit serwera ' + formatBytes(limitBytes) + '.');
		}
		input.reportValidity();
		const base = f.name.replace(/\.[^.]+$/, '').replace(/_/g, ' ').trim();
		const parts = base.split(/\s+-\s+/);
		if (!musician.value && parts.length > 1) {
			musician.value = parts.shift().trim();
		}
		if (!title.value) {
			title.value = parts.join(' - ').trim();
		}
	});
	input.form.addEventListener('submit', function () {
		if (input.form.checkValidity()) {
			document.getElementById('uploadProgress').classList.remove('d-none');
		}
	});
}

// 12, 13c: odtwarzacz playlisty. Jeden element <audio>; po zdarzeniu "ended" ładowany i odtwarzany
// jest następny utwór - przeglądarka pozwala na to bez kolejnego kliknięcia, bo ten sam element
// został już uruchomiony gestem użytkownika.
function initPlaylistPlayer(tracks)
{
	if (!tracks.length) {
		return;
	}
	const audio = document.getElementById('plAudio');
	const rows = Array.from(document.querySelectorAll('#plTracks .track'));
	const toggleBtn = document.getElementById('plToggle');
	const repeat = document.getElementById('plRepeat');
	const shuffle = document.getElementById('plShuffle');
	const pageTitle = document.title;
	let order = tracks.map(function (_, i) { return i; }); // kolejność odtwarzania (indeksy utworów)
	let pos = 0;                                              // pozycja w "order"
	let errors = 0;                                           // kolejne błędy (np. brak pliku) - ochrona przed pętlą

	function current() { return order[pos]; }

	function shuffled(first)
	{
		const rest = tracks.map(function (_, i) { return i; }).filter(function (i) { return i !== first; });
		for (let i = rest.length - 1; i > 0; i--) {
			const j = Math.floor(Math.random() * (i + 1));
			[rest[i], rest[j]] = [rest[j], rest[i]];
		}
		return [first].concat(rest);
	}

	function load(newPos, autoplay)
	{
		pos = newPos;
		const t = tracks[current()];
		audio.src = t.src;
		document.getElementById('plTitle').textContent = t.title;
		document.getElementById('plMusician').textContent = t.musician;
		document.getElementById('plPosition').textContent = '(' + (pos + 1) + '/' + tracks.length + ')';
		document.getElementById('plLyrics').textContent = t.lyrics.trim() || 'Brak tekstu dla tego utworu.';
		document.title = '▶ ' + t.title + ' - ' + t.musician + ' | ' + pageTitle;
		rows.forEach(function (r, i) {
			const active = i === current();
			r.classList.toggle('active', active);
			r.querySelector('.track-icon').className = 'bi fs-5 track-icon ' + (active ? 'bi-volume-up-fill text-success' : 'bi-play-circle text-success');
		});
		if ('mediaSession' in navigator) {
			navigator.mediaSession.metadata = new MediaMetadata({ title: t.title, artist: t.musician, album: pageTitle });
		}
		if (autoplay) {
			audio.play().catch(function () { /* autoodtwarzanie zablokowane - użytkownik kliknie Odtwórz */ });
		}
	}

	function next(fromEnded)
	{
		if (pos < order.length - 1) {
			load(pos + 1, true);
		} else if (repeat.checked || !fromEnded) {
			if (shuffle.checked) {
				order = shuffled(order[Math.floor(Math.random() * order.length)]);
			}
			load(0, fromEnded ? true : !audio.paused);
		}
		// koniec playlisty bez zapętlenia - zatrzymanie po ostatnim utworze
	}

	function prev()
	{
		if (audio.currentTime > 3 || pos === 0) {
			audio.currentTime = 0;     // jak w Spotify: najpierw powrót na początek utworu
		} else {
			load(pos - 1, !audio.paused);
		}
	}

	// Klucz zadania: automatyczne przejście do następnego utworu
	audio.addEventListener('ended', function () { next(true); });
	audio.addEventListener('playing', function () { errors = 0; });
	audio.addEventListener('error', function () {
		if (++errors < tracks.length) {
			setTimeout(function () { next(true); }, 500); // pomiń uszkodzony/usunięty plik
		}
	});
	audio.addEventListener('play', function () {
		toggleBtn.innerHTML = '<i class="bi bi-pause-fill"></i> Pauza';
		document.getElementById('plDisc').classList.add('spin');
	});
	audio.addEventListener('pause', function () {
		toggleBtn.innerHTML = '<i class="bi bi-play-fill"></i> Odtwórz';
		document.getElementById('plDisc').classList.remove('spin');
	});

	toggleBtn.addEventListener('click', function () {
		if (audio.paused) {
			audio.play();
		} else {
			audio.pause();
		}
	});
	document.getElementById('plNext').addEventListener('click', function () { next(false); });
	document.getElementById('plPrev').addEventListener('click', prev);
	shuffle.addEventListener('change', function () {
		const playing = current();
		order = shuffle.checked ? shuffled(playing) : tracks.map(function (_, i) { return i; });
		pos = order.indexOf(playing);
		document.getElementById('plPosition').textContent = '(' + (pos + 1) + '/' + tracks.length + ')';
	});

	// Kliknięcie utworu na liście odtwarza go (z pominięciem przycisków i formularzy w wierszu)
	rows.forEach(function (row) {
		row.addEventListener('click', function (e) {
			if (e.target.closest('form, a, button, select')) {
				return;
			}
			const idx = parseInt(row.dataset.index, 10);
			if (idx === current() && !audio.paused) {
				return;
			}
			if (shuffle.checked) {
				order = shuffled(idx); // losowa kolejność zaczynająca się od klikniętego utworu
			}
			load(order.indexOf(idx), true);
		});
	});

	// Klawisze multimedialne / sterowanie z poziomu systemu (Media Session API)
	if ('mediaSession' in navigator) {
		navigator.mediaSession.setActionHandler('nexttrack', function () { next(false); });
		navigator.mediaSession.setActionHandler('previoustrack', prev);
	}

	load(0, false);
}
