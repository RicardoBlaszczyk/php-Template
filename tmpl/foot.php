<footer class="footer mt-auto py-2 fixed-bottom">
    <div class="container-fluid position-relative">
        <div class="text-center">
            <span class="text-muted">&copy; <?php echo date('Y') ?> Kaitech IT Systems GmbH - &#9742;&nbsp;<a
                        class="link-dark" href="tel:004595251129210">+49
                            5251/12921-0</a> - &#9993;&nbsp;<a href="mailto:info@kaitech.de" class="link-dark">info@kaitech.de</a></span>
        </div>
        <!-- Theme-Umschalter rechts -->
        <button id="floatingToggle"
                class="btn btn-xs btn-secondary theme-toggle position-absolute end-0 top-50 me-2 translate-middle-y"
                title="Theme wechseln">🌓
        </button>
        <!-- btnTheme unsichtbar (für Funktionalität behalten) -->
        <button id="btnTheme" class="btn btn-outline-secondary d-none">Dark</button>
    </div>
</footer>
<?php
if (isset($GLOBALS['sql_timings']) && is_array($GLOBALS['sql_timings'])) {
    $totalTime = 0;
    $count     = count($GLOBALS['sql_timings']);
    echo '<div style="padding: 10px; background: #f8f9fa; border-top: 1px solid #dee2e6; font-size: 12px; color: #666;">';
    echo '<strong>SQL Performance Log (' . $count . ' Queries)</strong><br>';
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<thead><tr style="text-align: left;"><th>Time (ms)</th><th>Engine</th><th>Query</th></tr></thead>';
    echo '<tbody>';
    foreach ($GLOBALS['sql_timings'] as $timing) {
        $totalTime += $timing['time'];
        $color     = $timing['time'] > 50 ? 'color: red;' : ($timing['time'] > 20 ? 'color: orange;' : 'color: green;');
        echo '<tr>';
        echo '<td style="' . $color . '">' . $timing['time'] . ' ms</td>';
        echo '<td>' . htmlspecialchars($timing['engine']) . '</td>';
        echo '<td style="font-family: monospace;">' . htmlspecialchars(substr($timing['sql'], 0, 200)) . (strlen($timing['sql']) > 200 ? '...' : '') . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '<tfoot><tr><td><strong>' . $totalTime . ' ms</strong></td><td colspan="2">Total Time</td></tr></tfoot>';
    echo '</table>';
    echo '</div>';

    // Array leeren nach der Ausgabe
    $GLOBALS['sql_timings'] = [];
}
?>
<!-- Toast-Container -->
<div id="toastContainer"></div>

<!-- Include all compiled plugins (below), or include individual files as needed -->
<!-- Latest compiled and minified JavaScript -->
<script src="js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js" crossorigin="anonymous"
        async></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<!-- select -->
<script src="js/select2.full.min.js"></script>
<!-- all pages -->
<script src="js/allPages.js?rnd=<?php echo time(); ?>" crossorigin="anonymous"></script>
<?php
/**
 * Anzeige Error & Success
 */
if (!empty($has_errors)) { ?>
    <script type="text/javascript">
        const toastError = new bootstrap.Toast(document.getElementById('errorToast'))
        toastError.show()
    </script>
<?php }
if (!empty($has_messages)) { ?>
    <script type="text/javascript">
        const toastMessage = new bootstrap.Toast(document.getElementById('messageToast'))
        toastMessage.show()
    </script>
<?php } ?>

<script type="text/javascript">
    $(document).ready(function () {
        /**
         *
         */
        $('#sel_documenttype_identifier, .sel_document_name').select2({
            placeholder: 'Dokumententyp suchen...',
            tags: <?php echo areConstantsEmpty(['PARASHIFT_DOCTYPE_USE', 'GENESYS_DOCTYPE_USE']) ? 'true' : 'false'; ?>,
            allowClear: false,
            ajax: {
                url: 'ajax/searchDocumentTypes.php',
                dataType: 'json',
                delay: 250, // Wartezeit zwischen Tippen & Anfrage
                data: function (params) {
                    return {q: params.term || ''}; // Suchbegriff, leeren String mitschicken
                },
                processResults: function (data) {
                    return data; // API liefert bereits { results: [...] }
                },
                cache: true
            },
            minimumInputLength: 0,   // 👈 wichtig
            createTag: function (params) {
                const term = $.trim(params.term || '');
                if (term === '') {
                    return null;
                }
                return {
                    id: 'DXXX',
                    text: term,
                    newTag: true
                };
            },
            insertTag: function (data, tag) {
                // Tag nur hinzufügen, wenn noch nicht vorhanden (case-insensitive)
                const exists = data.some(function (item) {
                    return (item.text || '').toLowerCase() === (tag.text || '').toLowerCase();
                });
                if (!exists) {
                    data.push(tag);
                }
            }
        });

        // Auswahl ins Hidden-Feld schreiben
        $('#sel_documenttype_identifier').on('select2:select', function (e) {
            $('#documenttype_identifier').val(e.params.data.id);
            $('#create_document').removeClass('disabled');
        });

        // Auswahl ins Hidden-Feld schreiben - mit Event-Delegation für dynamisch hinzugefügte Elemente
        $(document).on('select2:select', '.sel_document_name', function (e) {
            const selectEl = $(this); // das <select> Element
            const documentValue = selectEl.data('document'); // data-document vom select selbst

            const tempInput = $('input[name="temp_document_name[' + documentValue + ']"]');
            const selectedText = e.params.data.text;

            if (tempInput.length) {
                tempInput.val(selectedText);

                // Button zum "Übernehmen" aktivieren
                $('button[name="document_name[' + documentValue + ']"]').removeClass('disabled');
            } else {
                console.log('Temp-Input nicht gefunden: input[name="temp_document_name[' + documentValue + ']"]');
            }
        });

        // 2) Bei Button-Klick: temp_document_name -> document_name kopieren
        $(document).on('click', 'button[name^="document_name["]', function (e) {
            e.preventDefault();

            const btn = $(this);
            const nameAttr = btn.attr('name'); // z.B. "document_name[3]"
            const match = nameAttr.match(/^document_name\[(.+)]$/);

            if (!match) return;

            const documentValue = match[1];

            const tempInput = $('input[name="temp_document_name[' + documentValue + ']"]');
            const finalInput = $('input[name="document_name[' + documentValue + ']"]');

            if (tempInput.length && finalInput.length) {
                finalInput.val(tempInput.val());
                // optional: Button wieder deaktivieren
                // btn.addClass('disabled');
            } else {
                console.log('Input nicht gefunden für documentValue=' + documentValue);
            }
        });

        /**
         * Name des Stapels suchen
         */
        $('#sel_custom_fields_batch_name').select2({
            placeholder: 'Stapelname auswählen oder angeben...',
            tags: true,
            allowClear: false,
            dropdownCssClass: 'big-dropdown-class',
            containerCssClass: 'big-selection-class',
            ajax: {
                url: 'ajax/searchBatchCategories.php',
                dataType: 'json',
                delay: 250, // Wartezeit zwischen Tippen & Anfrage
                data: function (params) {
                    return {q: params.term || ''}; // Suchbegriff
                },
                processResults: function (data) {
                    return data; // API liefert bereits { results: [...] }
                },
                cache: true
            },
            minimumInputLength: 0   // 👈 wichtig
        });

        // Vorgabewert hinzufügen und auswählen
        let defaultOption = new Option("Stapel-Upload allgemein", "batchUpload", true, true);
        $('#sel_custom_fields_batch_name').append(defaultOption).trigger('change');

        // Auswahl auch ins Hidden-Feld schreiben
        $('#custom_fields_batch_name').val("batchUpload");

        // Auswahl ins Hidden-Feld schreiben
        $('#sel_custom_fields_batch_name').on('select2:select', function (e) {
            $('#custom_fields_batch_name').val(e.params.data.id);
        });

    });
</script>