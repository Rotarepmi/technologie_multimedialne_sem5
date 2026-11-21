function myLoadHeader()
{
	$(document).ready(function()
	{
		$('#myHeader').load("header.php");
	});
}

// 6k-iv: kliknięcie miniatury otwiera obrazek w pełnym rozmiarze w oknie modalnym (bez pobierania pliku)
function initImagePreview()
{
	const modalEl = document.getElementById('previewModal');
	if (!modalEl) {
		return;
	}
	const modal = new bootstrap.Modal(modalEl);
	const img = document.getElementById('previewImg');
	document.querySelectorAll('[data-preview]').forEach(function (a) {
		a.addEventListener('click', function (e) {
			e.preventDefault();
			img.src = a.dataset.preview;
			img.alt = a.dataset.title;
			document.getElementById('previewTitle').textContent = a.dataset.title;
			modal.show();
		});
	});
	modalEl.addEventListener('hidden.bs.modal', function () {
		img.src = '';
	});
}

// Formularz uploadu: lista wybranych plików z rozmiarami, ostrzeżenie o przekroczeniu limitu, pasek postępu
function initUploadForm(limitBytes)
{
	const input = document.getElementById('fileToUpload');
	const list = document.getElementById('selectedFiles');
	const fmt = function (b) {
		const u = ['B', 'KB', 'MB', 'GB'];
		let i = 0;
		while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
		return (i ? b.toFixed(1) : b) + ' ' + u[i];
	};
	input.addEventListener('change', function () {
		let total = 0;
		list.innerHTML = '';
		Array.from(input.files).forEach(function (f) {
			total += f.size;
			const div = document.createElement('div');
			div.textContent = f.name + ' (' + fmt(f.size) + ')';
			list.appendChild(div);
		});
		if (limitBytes && total > limitBytes) {
			const warn = document.createElement('div');
			warn.className = 'text-danger fw-semibold';
			warn.textContent = 'Łączny rozmiar ' + fmt(total) + ' przekracza limit serwera ' + fmt(limitBytes) + '.';
			list.appendChild(warn);
		}
	});
	input.form.addEventListener('submit', function () {
		document.getElementById('uploadProgress').classList.remove('d-none');
	});
}
