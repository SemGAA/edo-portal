import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const fileInput = document.getElementById('dropzone-file');
const fileNameElement = document.getElementById('file-name');
const uploadIconElement = document.getElementById('upload-icon');

if (fileInput && fileNameElement && uploadIconElement) {
  fileInput.addEventListener('change', (event) => {
    const file = event.target.files?.[0];

    if (!file) {
      return;
    }

    fileNameElement.innerHTML = `<span class="font-semibold text-emerald-600">${file.name}</span>`;
    uploadIconElement.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-3 text-emerald-600">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>`;
  });
}





