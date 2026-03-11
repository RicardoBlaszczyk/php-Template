/**
 * Globaler Speicher und Funktionen für Tag-Inputs
 */
const tagData = {};

window.removeTag = function (containerId, index) {
    if (tagData[containerId]) {
        tagData[containerId].splice(index, 1);
        updateTags(containerId);
    }
};

function updateTags(containerId) {
    const tagContainer = document.getElementById(containerId);
    if (!tagContainer) return;

    const textareaId = tagContainer.getAttribute("data-textarea");
    const hiddenTextarea = document.getElementById(textareaId);

    tagContainer.innerHTML = '';

    if (tagData[containerId]) {
        tagData[containerId].forEach((tag, index) => {
            const shortenedTag = tag.length > 30 ? tag.substring(0, 30) + "..." : tag;

            const tagBadge = document.createElement('span');
            tagBadge.classList.add('badge', 'bg-primary', 'me-1', 'mb-1', 'p-2');
            tagBadge.style.cursor = 'default';
            tagBadge.style.display = 'inline-flex';
            tagBadge.style.alignItems = 'center';

            tagBadge.innerHTML = `
                        <span title="${tag}" class="me-2">${shortenedTag}</span>
                        <button type="button" class="btn-close btn-close-white" style="font-size: 0.6em;" aria-label="Close" onclick="removeTag('${containerId}', ${index});"></button>
                    `;

            tagContainer.appendChild(tagBadge);
        });

        if (hiddenTextarea) {
            hiddenTextarea.value = tagData[containerId].join(',');
        }
    }
}

function setupTagInput(inputId, containerId, textareaId) {
    const inputField = document.getElementById(inputId);
    const tagContainer = document.getElementById(containerId);
    const hiddenTextarea = document.getElementById(textareaId);

    if (!inputField || !tagContainer || !hiddenTextarea) return;

    tagContainer.setAttribute("data-textarea", textareaId);

    if (!tagData[containerId]) {
        tagData[containerId] = [];
    }

    // Bestehende Werte beim Laden wiederherstellen
    if (hiddenTextarea.value.trim() !== '') {
        const initialTags = hiddenTextarea.value.split(',').map(t => t.trim()).filter(t => t !== '');
        tagData[containerId] = initialTags;
        updateTags(containerId);
    }

    inputField.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            event.stopPropagation(); // Verhindert, dass andere Listener reagieren

            const newTag = this.value.trim();

            if (newTag !== '') {
                if (!tagData[containerId].includes(newTag)) {
                    tagData[containerId].push(newTag);
                    updateTags(containerId);
                }
                this.value = '';
            }
            return false; // Zusätzliche Sicherheit
        }
    });
}

// ... existing code ...

/**
 * Sichtbarkeits-Manager (Universal)
 */
function initVisibilityManager(config) {
    const container = document.getElementById(config.containerId);
    const hiddenInput = document.getElementById(config.jsonInputId);
    const addBtn = document.getElementById(config.addBtnId);

    if (!container || !hiddenInput) return;

    let rowData = [];

    // 1. Daten laden
    try {
        const rawVal = hiddenInput.value.trim();
        if (rawVal !== '' && rawVal !== '[]') {
            rowData = JSON.parse(rawVal);
        }
    } catch (e) {
        console.error("Fehler beim Parsen der Sichtbarkeits-JSON", e);
        rowData = [];
    }

    // 2. Helper: Daten speichern
    function updateHiddenField() {
        const rows = container.querySelectorAll('.visibility-row');
        const newData = [];
        rows.forEach(row => {
            const parentSel = row.querySelector('.select-parent');
            const childField = row.querySelector('.select-child');
            if (parentSel && childField) {
                newData.push({
                    parent: parentSel.value,
                    child: childField.value
                });
            }
        });
        hiddenInput.value = JSON.stringify(newData);
    }

    // 3. Helper: Selects bauen
    function createSelect(options, selectedValue, className) {
        const select = document.createElement('select');
        select.classList.add('form-select', className);

        if (Array.isArray(options)) {
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.val;
                option.textContent = opt.text;
                if (String(opt.val) === String(selectedValue)) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
        select.addEventListener('change', updateHiddenField);
        return select;
    }

    // 4. Zeile rendern
    function renderRow(val1, val2) {
        const rowDiv = document.createElement('div');
        rowDiv.classList.add('row', 'mb-2', 'visibility-row');

        // Feld 1 (Immer Select)
        const col1 = document.createElement('div');
        col1.classList.add('col-sm-5');
        col1.appendChild(createSelect(config.options1 || [], val1, 'select-parent'));

        // Feld 2 (Select oder Input)
        const col2 = document.createElement('div');
        col2.classList.add('col-sm-6');

        if (config.useInput) {
            const input = document.createElement('input');
            input.type = 'text';
            input.classList.add('form-control', 'select-child');
            input.value = val2 || '';
            input.placeholder = config.inputPlaceholder || '';
            input.addEventListener('input', updateHiddenField);
            col2.appendChild(input);
        } else {
            col2.appendChild(createSelect(config.options2 || [], val2, 'select-child'));
        }

        // Löschen Button
        const colAction = document.createElement('div');
        colAction.classList.add('col-sm-1', 'd-flex', 'align-items-center');
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.classList.add('btn', 'btn-outline-danger', 'btn-sm');
        delBtn.innerHTML = '<i class="fa fa-trash"></i>';
        delBtn.onclick = function () {
            rowDiv.remove();
            updateHiddenField();
        };
        colAction.appendChild(delBtn);

        rowDiv.appendChild(col1);
        rowDiv.appendChild(col2);
        rowDiv.appendChild(colAction);

        container.appendChild(rowDiv);
    }

    // 5. Initiale Darstellung
    container.innerHTML = '';
    if (rowData.length > 0) {
        rowData.forEach(item => renderRow(item.parent, item.child));
    }

    // 6. Button Event
    if (addBtn) {
        const newAddBtn = addBtn.cloneNode(true);
        addBtn.parentNode.replaceChild(newAddBtn, addBtn);
        newAddBtn.addEventListener('click', function () {
            renderRow('', config.useInput ? '' : '*');
            updateHiddenField();
        });
    }
}

// Haupt-Initialisierung
document.addEventListener("DOMContentLoaded", function () {

    // 1. Manager für Benutzergruppen -> Filialen
    initVisibilityManager({
        containerId: 'visibilityRowsContainer',
        jsonInputId: 'user_group_visibility_json',
        addBtnId: 'addVisibilityRowBtn',
        options1: window.availableUserGroups, // ✅ Wer? (Benutzergruppe)
        options2: window.availableBranches,   // ✅ Was? (Filiale)
        useInput: false
    });

    // 2. Manager für Zweideutige Belege (Dokumenttyp -> Freitext)
    initVisibilityManager({
        containerId: 'docVisibilityRowsContainer',
        jsonInputId: 'ambiguous_doc_parashift_json',
        addBtnId: 'addDocVisibilityRowBtn',
        options1: window.availableDocTypeNames, // ✅ Was? (Dokumenttyp)
        useInput: true,
        inputPlaceholder: 'Zuweisung eingeben...'
    });

    // INITIALISIERUNG DER TAG-INPUTS
    setupTagInput('gen4akt_columns_input', 'gen4akt_columns_tags', 'gen4akt_columns_textarea');

});

// Create User Button Logic (Bereinigt um doppelte EventListener)
const createUserBtn = document.getElementById('createUserBtn');
document.getElementById('createUserBtn').addEventListener('click', function () {
    let username = document.getElementById('newUser_user').value.trim();
    let password = document.getElementById('newUser_pass').value.trim();

    if (username === "") {
        alert("Bitte einen Benutzernamen eingeben!");
        return;
    }

    if (password === "") {
        alert("Bitte einen Passwort eingeben!");
        return;
    }

    fetch('ajax/checkUser.php?username=' + encodeURIComponent(username))
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                alert("Benutzername ist bereits vergeben!");
            } else {
                let form = document.getElementById('configForm');
                let url = form.getAttribute('action') || window.location.href;
                url += (url.includes('?') ? '&' : '?') + 'action=update';
                form.setAttribute('action', url);
                form.submit();
            }
        })
        .catch(err => {
            console.error("Fehler beim Prüfen:", err);
            alert("Fehler bei der Prüfung des Benutzernamens.");
        });
});

$(document).ready(function () {
    /**
     * @description
     * Realisiert einen interaktiven Verzeichnis-Browser (Picker) für Konfigurationspfade.
     *
     * FUNKTIONSWEISE:
     * 1. Trigger: Klick auf Elemente mit `.open-dir-picker` (z.B. Lupe-Icon).
     *    Dabei wird über `data-target-input` bestimmt, welches Feld (z.B. #doc_path) befüllt werden soll.
     *
     * 2. Laden (AJAX): Die Funktion `loadDirs(path)` ruft via `ajax/browseDirs.php` die Unterverzeichnisse
     *    des aktuellen Pfads ab und rendert sie im Modal-Container.
     *
     * 3. Navigation: Klicks auf Verzeichnisse innerhalb des Modals (`.browse-dir`) triggern `loadDirs`
     *    erneut mit dem neuen Unterpfad, was ein tiefes Browsen im Dateisystem ermöglicht.
     *
     * 4. Bestätigung & Validierung:
     *    - Der gewählte Pfad wird in das ursprüngliche Input-Feld geschrieben.
     *    - Spezial-Logik für `#doc_path`: Wenn sich der Exportpfad gegenüber dem Initialwert (`initialDocPath`)
     *      geändert hat, wird automatisch ein Warn-Modal (`#pathWarningModal`) eingeblendet, um auf
     *      potenzielle Auswirkungen (z.B. Dateiverschiebung) hinzuweisen.
     */
    let selectedPath = '';
    let currentTargetInput = ''; // Speichert, ob doc_path oder backup_path gewählt wird
    let initialDocPath = $('#export_path').val(); // Speichere den ursprünglichen Pfad beim Laden

    // Funktion zum Laden der Verzeichnisse
    function loadDirs(path) {
        $('#dir-browser-container').html('<div class="text-center"><i class="glyphicon glyphicon-refresh"></i> Lade...</div>');
        $.get('ajax/browseDirs.php', {dir: path}, function (data) {
            $('#dir-browser-container').html(data);
            let newPath = $('#current-browsed-path').val();
            $('#selected-path-display').text(newPath);
            selectedPath = newPath;
        });
    }

    $('.open-dir-picker').click(function () {
        currentTargetInput = $(this).data('target-input');
        let initialPath = $(currentTargetInput).val();
        loadDirs(initialPath);
        $('#dirPickerModal').modal('show');
    });

    $(document).on('click', '.browse-dir', function (e) {
        e.preventDefault();
        loadDirs($(this).data('path'));
    });

    $('#confirm-dir').click(function () {
        let oldPath = $(currentTargetInput).val();
        $(currentTargetInput).val(selectedPath);
        $('#dirPickerModal').modal('hide');

        // Spezielle Warnung nur beim Document Path und wenn er sich geändert hat
        if (currentTargetInput === '#doc_path' && selectedPath !== initialDocPath) {
            // Kurze Verzögerung, damit das erste Modal sauber schließt
            setTimeout(function () {
                $('#pathWarningModal').modal('show');
            }, 500);
        }
    });

    /**
     * @function initUserTagPickers
     * @description
     * Initialisiert eine interaktive Tag-Verwaltung für kommagetrennte Benutzerlisten.
     *
     * Funktionsweise:
     * 1. Ein Select2-Picker dient als Such- und Auswahlwerkzeug für vorhandene Benutzer.
     * 2. Nach Auswahl eines Nutzers wird dieser in eine versteckte Textarea geschrieben (Datenhaltung).
     * 3. Das Skript wandelt den Textinhalt der Textarea automatisch in visuelle Bootstrap-Badges (Pills) um.
     * 4. Badges können per Klick auf das 'X' entfernt werden, was die Textarea und die Ansicht synchronisiert.
     *
     * @param {Object[]} tagConfigs - Array von Konfigurationsobjekten (picker, container, target).
     */
    const initUserTagPickers = () => {
        const tagConfigs = [
            {
                picker: '.parashift_users_picker',
                container: '#parashift_tags_container',
                target: '#parashift_learning_users'
            },
            {
                picker: '.select2-user-picker',
                container: '#special_users_tags_container',
                target: '#special_users_textarea'
            }
        ];

        tagConfigs.forEach(conf => {
            const $picker = $(conf.picker);
            const $container = $(conf.container);
            const $target = $(conf.target);

            // Nur ausführen, wenn die Elemente im aktuellen DOM existieren
            if (!$picker.length || !$container.length || !$target.length) return;

            // Funktion zum Zeichnen der Tags aus der blinden Textarea
            const renderTags = () => {
                $container.empty();
                const rawVal = $target.val() || "";
                const users = rawVal.split(',').map(s => s.trim()).filter(s => s !== "");

                users.forEach(user => {
                    $container.append(`
                        <span class="badge bg-primary d-flex align-items-center gap-2 p-2" style="font-size: 0.85rem;">
                            ${user}
                            <i class="fa fa-times remove-user-tag" data-user="${user}" style="cursor:pointer; opacity:0.7"></i>
                        </span>
                    `);
                });
            };

            // Initiales Rendern
            renderTags();

            // Select2 Initialisierung
            $picker.select2({
                placeholder: 'Nutzer wählen...',
                allowClear: true,
                dropdownParent: $picker.closest('.modal'),
                width: '100%'
            }).on('select2:select', function (e) {
                const val = e.params.data.id;
                let currentVal = $target.val() || "";
                let users = currentVal.split(',').map(s => s.trim()).filter(s => s !== "");

                if (!users.includes(val)) {
                    users.push(val);
                    $target.val(users.join(',')).trigger('change');
                    renderTags();
                }
                // Picker sofort leeren
                setTimeout(() => $picker.val(null).trigger('change'), 50);
            });

            // Event-Delegation für das Entfernen (funktioniert auch für dynamisch hinzugefügte Badges)
            $container.off('click', '.remove-user-tag').on('click', '.remove-user-tag', function (e) {
                e.preventDefault();
                const userToRemove = $(this).data('user');
                let currentVal = $target.val() || "";
                let users = currentVal.split(',').map(s => s.trim()).filter(u => u !== userToRemove);
                $target.val(users.join(',')).trigger('change');
                renderTags();
            });
        });
    };
    // Ausführen beim Laden
    initUserTagPickers();
});