// 12, 16: rejestracja wizyty gościa portalu. Dane, których serwer nie zna (ekran, okno,
// kolory, ciasteczka, Java, język), zbiera przeglądarka i wysyła do log_visit.php.
// IP, User-Agent i datę/godzinę ustala serwer. Jedna wizyta = jedna sesja PHP.
(function () {
	function send() {
		var data = new FormData();
		data.append('screen_res', screen.width + 'x' + screen.height);
		data.append('window_res', window.innerWidth + 'x' + window.innerHeight);
		data.append('colors', screen.colorDepth);
		data.append('cookies', navigator.cookieEnabled ? 1 : 0);
		// navigator.javaEnabled() we współczesnych przeglądarkach zawsze zwraca false (brak wtyczki NPAPI)
		data.append('java', typeof navigator.javaEnabled === 'function' && navigator.javaEnabled() ? 1 : 0);
		data.append('language', navigator.language || navigator.userLanguage || '');
		fetch('../visits/log_visit.php', { method: 'POST', body: data, credentials: 'same-origin' })
			.catch(function () {});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', send);
	} else {
		send();
	}
})();
