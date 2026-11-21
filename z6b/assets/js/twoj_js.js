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

// 8, 11: formularz dodawania filmu - kontrola rozmiaru/typu, podgląd wybranego pliku, podpowiedź tytułu
// i reżysera z nazwy pliku "Reżyser - Tytuł.mp4", wczytanie napisów z pliku .srt/.vtt, pasek postępu
function initFilmUploadForm(limitBytes)
{
	const input = document.getElementById('fileToUpload');
	const title = document.getElementById('title');
	const director = document.getElementById('director');
	const preview = document.getElementById('uploadPreview');
	const subtitlesFile = document.getElementById('subtitlesFile');
	const subtitles = document.getElementById('subtitles');
	let previewUrl = null;

	input.addEventListener('change', function () {
		input.setCustomValidity('');
		if (previewUrl) {
			URL.revokeObjectURL(previewUrl);
			previewUrl = null;
		}
		preview.classList.add('d-none');
		const f = input.files[0];
		if (!f) {
			return;
		}
		if (!/\.mp4$/i.test(f.name)) {
			input.setCustomValidity('Dozwolone są tylko pliki MP4.');
		} else if (limitBytes && f.size > limitBytes) {
			input.setCustomValidity('Plik ' + formatBytes(f.size) + ' przekracza limit serwera ' + formatBytes(limitBytes) + '.');
		} else {
			previewUrl = URL.createObjectURL(f); // podgląd lokalnego pliku przed wysłaniem
			preview.src = previewUrl;
			preview.classList.remove('d-none');
		}
		input.reportValidity();
		const base = f.name.replace(/\.[^.]+$/, '').replace(/_/g, ' ').trim();
		const parts = base.split(/\s+-\s+/);
		if (!director.value && parts.length > 1) {
			director.value = parts.shift().trim();
		}
		if (!title.value) {
			title.value = parts.join(' - ').trim();
		}
	});

	subtitlesFile.addEventListener('change', function () {
		const f = subtitlesFile.files[0];
		if (!f) {
			return;
		}
		if (f.size > 65535) {
			alert('Plik z napisami jest za duży (maks. 64 KB).');
			subtitlesFile.value = '';
			return;
		}
		f.text().then(function (text) { subtitles.value = text.replace(/^﻿/, ''); });
	});

	input.form.addEventListener('submit', function () {
		subtitlesFile.disabled = true; // plik napisów nie jest wysyłany - jego treść jest już w polu tekstowym
		if (input.form.checkValidity()) {
			document.getElementById('uploadProgress').classList.remove('d-none');
		}
	});
}

// 16, 17c: odtwarzacz playlisty. Jeden element <video>; po zdarzeniu "ended" ładowany i odtwarzany
// jest następny film - przeglądarka pozwala na to bez kolejnego kliknięcia, bo ten sam element
// został już uruchomiony gestem użytkownika (działa to także w trybie pełnoekranowym).
function initPlaylistPlayer(items)
{
	if (!items.length) {
		return;
	}
	const video = document.getElementById('plVideo');
	const rows = Array.from(document.querySelectorAll('#plTracks .track'));
	const toggleBtn = document.getElementById('plToggle');
	const repeat = document.getElementById('plRepeat');
	const shuffle = document.getElementById('plShuffle');
	const pageTitle = document.title;
	let order = items.map(function (_, i) { return i; }); // kolejność odtwarzania (indeksy filmów)
	let pos = 0;                                             // pozycja w "order"
	let errors = 0;                                          // kolejne błędy (np. brak pliku) - ochrona przed pętlą

	function current() { return order[pos]; }

	function shuffled(first)
	{
		const rest = items.map(function (_, i) { return i; }).filter(function (i) { return i !== first; });
		for (let i = rest.length - 1; i > 0; i--) {
			const j = Math.floor(Math.random() * (i + 1));
			[rest[i], rest[j]] = [rest[j], rest[i]];
		}
		return [first].concat(rest);
	}

	function showPosition()
	{
		document.getElementById('plPosition').textContent = '(' + (pos + 1) + '/' + items.length + ')';
	}

	function load(newPos, autoplay)
	{
		pos = newPos;
		const t = items[current()];
		// Napisy z kodami czasowymi: <track> wymieniany razem ze źródłem filmu
		video.querySelectorAll('track').forEach(function (tr) { tr.remove(); });
		if (t.track) {
			const tr = document.createElement('track');
			tr.kind = 'subtitles';
			tr.srclang = 'pl';
			tr.label = 'Napisy';
			tr.src = t.track;
			tr.default = true;
			video.appendChild(tr);
		}
		video.src = t.src;
		document.getElementById('plTitle').textContent = t.title;
		document.getElementById('plDirector').textContent = 'reż. ' + t.director;
		document.getElementById('plSubtitles').textContent = t.subtitles.trim() || 'Brak napisów dla tego filmu.';
		showPosition();
		document.title = '▶ ' + t.title + ' | ' + pageTitle;
		rows.forEach(function (r, i) {
			const active = i === current();
			r.classList.toggle('active', active);
			r.querySelector('.track-icon').className = 'bi fs-5 track-icon text-danger ' + (active ? 'bi-film' : 'bi-play-circle');
		});
		if ('mediaSession' in navigator) {
			navigator.mediaSession.metadata = new MediaMetadata({ title: t.title, artist: t.director, album: pageTitle });
		}
		if (autoplay) {
			video.play().catch(function () { /* autoodtwarzanie zablokowane - użytkownik kliknie Odtwórz */ });
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
			load(0, fromEnded ? true : !video.paused);
		}
		// koniec playlisty bez zapętlenia - zatrzymanie po ostatnim filmie
	}

	function prev()
	{
		if (video.currentTime > 3 || pos === 0) {
			video.currentTime = 0;     // najpierw powrót na początek filmu
		} else {
			load(pos - 1, !video.paused);
		}
	}

	// Klucz zadania (17c): automatyczne przejście do następnego filmu
	video.addEventListener('ended', function () { next(true); });
	video.addEventListener('playing', function () { errors = 0; });
	video.addEventListener('error', function () {
		if (++errors < items.length) {
			setTimeout(function () { next(true); }, 500); // pomiń uszkodzony/usunięty plik
		}
	});
	video.addEventListener('play', function () {
		toggleBtn.innerHTML = '<i class="bi bi-pause-fill"></i> Pauza';
	});
	video.addEventListener('pause', function () {
		toggleBtn.innerHTML = '<i class="bi bi-play-fill"></i> Odtwórz';
	});

	toggleBtn.addEventListener('click', function () {
		if (video.paused) {
			video.play();
		} else {
			video.pause();
		}
	});
	document.getElementById('plNext').addEventListener('click', function () { next(false); });
	document.getElementById('plPrev').addEventListener('click', prev);
	shuffle.addEventListener('change', function () {
		const playing = current();
		order = shuffle.checked ? shuffled(playing) : items.map(function (_, i) { return i; });
		pos = order.indexOf(playing);
		showPosition();
	});

	// Kliknięcie filmu na liście odtwarza go (z pominięciem przycisków i formularzy w wierszu)
	rows.forEach(function (row) {
		row.addEventListener('click', function (e) {
			if (e.target.closest('form, a, button, select')) {
				return;
			}
			const idx = parseInt(row.dataset.index, 10);
			if (idx === current() && !video.paused) {
				return;
			}
			if (shuffle.checked) {
				order = shuffled(idx); // losowa kolejność zaczynająca się od klikniętego filmu
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
