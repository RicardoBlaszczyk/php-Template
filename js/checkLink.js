$(function () {
    $('.checkBtn').click(function () {
        let id = $(this).data('id');
        // Prüfung auf undefined oder leeren String
        if (typeof id === 'undefined' || id === '') {
            id = $(this).data('link-id');
        }
        let user_id = $(this).data('user');
        // Prüfung auf undefined oder leeren String
        if (typeof user_id === 'undefined' || user_id === '') {
            user_id = $(this).data('user-id');
        }
        let user = ''; // wichtige Änderung: vorab deklarieren
        if (user_id && $('#' + user_id).length > 0) {
            user = $('#' + user_id).val();
        } else {
            user = user_id || '';
        }
        let pass_id = $(this).data('pass');
        // Prüfung auf undefined oder leeren String
        if (typeof pass_id === 'undefined' || pass_id === '') {
            pass_id = $(this).data('pass-id');
        }
        let pass = ''; // wichtige Änderung: vorab deklarieren
        if (pass_id && $('#' + pass_id).length > 0) {
            pass = $('#' + pass_id).val();
        } else {
            pass = pass_id || '';
        }
        // URL kommt aus dem Checkbox-Input mit id="url-<id>"
        const $input = $('#url-' + id);
        const url = ($input.length > 0) ? $input.val().trim() : '';
        const $btn = $(this);

        if (!url) {
            alert('Bitte gib eine URL ein.');
            return;
        }

        $btn.prop('disabled', true).text('Prüfe...');

        $.ajax({
            url: 'ajax/checkEndpoint.php',
            type: 'POST',
            data: {
                url: url,
                auth_user: user,
                auth_pass: pass
            },
            dataType: 'json',
            success: function (res) {
                let text;
                let headerClass = 'bg-warning text-white';  // Standard: warning

                if (res.success) {
                    if (res.status === 200) {
                        headerClass = 'bg-success text-white';  // Nur bei 200: success
                    }
                    text =
                        "✅ Verbindung erfolgreich\r\n" +
                        "🌐 HTTP-Status: " + res.status + "\r\n" +
                        "⏱️ Antwortzeit: " + res.time + "s\r\n" +
                        "📍 URL: " + res.url + "\r\n" +
                        "🔒 SSL-Version: " + res.ssl_version + "\r\n" +
                        "🔑 Cipher: " + res.ssl_cipher + "\r\n";
                } else {
                    headerClass = 'bg-danger text-white';
                    text = "❌ Fehler: " + res.error;
                }

                // Toast erstellen
                const toastId = 'toast-' + Date.now();
                const toastHtml = `
                    <div id="${toastId}" class="toast show shadow-sm border-0" role="alert" aria-live="assertive" aria-atomic="true">
                      <div class="toast-header ${headerClass}">
                        <strong class="me-auto">Ergebnis #${id}</strong>
                        <small>${new Date().toLocaleTimeString()}</small>
                        <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast"></button>
                      </div>
                      <div class="toast-body"><pre>${text}</pre></div>
                    </div>`;

                $('#toastContainer').prepend(toastHtml);

                const toastEl = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastEl, {delay: 15000});
                toast.show();

                // Nach Ablauf automatisch entfernen
                toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            },
            error: function () {
                alert('Fehler bei der Verbindung zum Server. ' + url);
            },
            complete: function () {
                // $btn.prop('disabled', false).text('Prüfen #' + id);
                $btn.prop('disabled', false).html('<span class="fa fa-search"></span>');
            }
        });
    });
});