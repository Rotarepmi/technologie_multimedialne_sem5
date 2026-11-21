function myLoadHeader()
{
	$(document).ready(function()
	{
		$('#myHeader').load("header.php");
	});
}

// Co intervalMs pobiera świeżą wersję strony i podmienia blok #monitor.
// Dokument nie jest przeładowywany, więc raz odblokowany dźwięk alarmu działa dalej
// (przy <meta http-equiv="refresh"> przeglądarka blokowałaby autoodtwarzanie po każdym przeładowaniu).
function monitorStart(intervalMs)
{
	playAlarmIfNeeded();
	const tick = async () => {
		try {
			const res = await fetch(location.href, { cache: 'no-store' });
			if (new URL(res.url).pathname !== location.pathname) { // np. sesja wygasła -> login.php
				location.href = res.url;
				return;
			}
			const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
			const fresh = doc.getElementById('monitor');
			const box = document.getElementById('monitor');
			if (fresh && box) {
				box.replaceWith(fresh);
				playAlarmIfNeeded();
			}
		} catch (e) {
			console.warn('Nie udało się odświeżyć monitoringu', e);
		}
		setTimeout(tick, intervalMs); // następne sprawdzenie dopiero po zakończeniu bieżącego
	};
	setTimeout(tick, intervalMs);
}

let alarmAudio = null;

function playAlarmIfNeeded()
{
	const box = document.getElementById('monitor');
	if (!box || box.dataset.alarm !== '1') {
		return;
	}
	const src = box.dataset.sound;
	if (!alarmAudio || !alarmAudio.src.endsWith(src)) {
		alarmAudio = new Audio(src);
	}
	alarmAudio.currentTime = 0;
	alarmAudio.play()
		.then(() => toggleUnlock(false))
		.catch(() => toggleUnlock(true)); // brak interakcji użytkownika = autoplay zablokowany
}

function unlockAlarm()
{
	if (alarmAudio) {
		alarmAudio.play().then(() => toggleUnlock(false)).catch(() => {});
	}
}

function toggleUnlock(show)
{
	const el = document.getElementById('sound-unlock');
	if (el) {
		el.classList.toggle('d-none', !show);
	}
}
