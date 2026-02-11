function setQuestion(text) {
  document.getElementById('question').value = text;
}

const chatForm = document.getElementById('chatForm');
const chatLog = document.getElementById('chatLog');
const historyList = document.getElementById('historyList');
const loadingBar = document.getElementById('loadingBar');

chatForm.addEventListener('submit', function (e) {
  e.preventDefault();

  const question = document.getElementById('question').value.trim();
  if (!question) return;

  if ($.fn.DataTable.isDataTable('#resultTable')) {
    $('#resultTable').DataTable().destroy();
  }
  chatLog.innerHTML = "";

  chatLog.innerHTML = `<div class="message message-user"><strong>Du:</strong> ${question}</div>`;
  document.getElementById('question').value = "";
  loadingBar.style.display = 'block';

  fetch('chatbot.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ question })
  })
  .then(response => response.json())
  .then(data => {
    loadingBar.style.display = 'none';

    chatLog.innerHTML += `<div class="message message-bot"><strong>Antwort:</strong> ${data.answer}</div>`;

    const item = document.createElement('li');
    item.className = 'list-group-item';
    item.textContent = question;
    historyList.prepend(item);

    initDataTables();
  })
  .catch(err => {
    loadingBar.style.display = 'none';
    chatLog.innerHTML = `<div class="message message-bot"><strong>Fehler:</strong> ${err}</div>`;
  });
});

function initDataTables() {
  const table = document.querySelector('#resultTable');
  if (table && !$.fn.DataTable.isDataTable(table)) {
    $(table).DataTable({
      dom: 'Bfrtip',
      buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/de-DE.json'
      }
    });
  }
}
