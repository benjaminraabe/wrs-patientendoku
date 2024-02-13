// Öffnet und Resettet den Dialog zum anlegen/modifizieren eines Benutzers.
function openDlg(username, data, edit=true) {
  document.getElementById('dlgTitle').textContent = (edit) ? "[" + username + "] bearbeiten" : "Neuer Benutzer"

  // Form-Setup
  if (edit) {
    document.getElementById('formUsername').value = username
    document.getElementById('formUsername').disabled = true
    document.getElementById('formPassword').value = ''
    document.getElementById('formActive').checked = (data.active == 1)
    document.getElementById('formLateEdit').checked = (data.late_edit == 1)
    document.getElementById('formPatSearch').checked = (data.pat_search == 1)
    document.getElementById('formBackdate').checked = (data.backdate_protocol == 1)

    let roleSelect = Metro.getPlugin(document.getElementById('formRoleSelect'), 'select')
    roleSelect.val([data.role])

    let uhsSelect = Metro.getPlugin(document.getElementById('formUhstSelect'), 'select')
    uhsSelect.val([data.uhs])

    document.getElementById('formBtnEdit').style.display = 'inline-flex'
    document.getElementById('formBtnNew').style.display = 'none'
  } else {
    document.getElementById('formUsername').value = ''
    document.getElementById('formUsername').disabled = false
    document.getElementById('formPassword').value = ''
    document.getElementById('formActive').checked = true
    document.getElementById('formLateEdit').checked = false
    document.getElementById('formPatSearch').checked = false
    document.getElementById('formBackdate').checked = false

    Metro.getPlugin(document.getElementById('formRoleSelect'), 'select').reset(true)
    Metro.getPlugin(document.getElementById('formUhstSelect'), 'select').reset(true)

    document.getElementById('formBtnNew').style.display = 'inline-flex'
    document.getElementById('formBtnEdit').style.display = 'none'
  }

  Metro.dialog.open("#editDialog");
}


// Versucht die Daten für einen neuen oder modifizierten Benutzer an den Server zu senden.
function updateUserData(edit=true) {
  let data = {}

  // Differenzierer zwischen dem anlegen von neuen Usern und dem Verändern eines bestehenden Users
  if (edit) {
    data["MODIFY_USER"] = "1"
  } else {
    // Check UN and Password
    data["NEW_USER"] = "1"
    if ((document.getElementById('formUsername').value == '')
        || (document.getElementById('formPassword').value == '') ) {
          alert("Benutzername und Passwort dürfen nicht leer sein.")
          return
        }
  }

  // Das neue Passwort wird nur mitgeschickt (ergo verändert), wenn es angegeben wird
  if (document.getElementById('formUsername').value != '') {
    data["PASSWORD"] = document.getElementById('formPassword').value
  }

  data["USERNAME"] = document.getElementById('formUsername').value
  data["USER_ROLE"] = Metro.getPlugin(document.getElementById('formRoleSelect'), 'select').val()
  data["UHST_ID"] = Metro.getPlugin(document.getElementById('formUhstSelect'), 'select').val()
  data["ACTIVE"] = (document.getElementById('formActive').checked) ? 1 : 0
  data["CAN_LATE_EDIT"] = (document.getElementById('formLateEdit').checked) ? 1 : 0
  data["CAN_SEARCH_PATIENTS"] = (document.getElementById('formPatSearch').checked) ? 1 : 0
  data["CAN_BACKDATE_PROTOCOL"] = (document.getElementById('formBackdate').checked) ? 1 : 0

  fetch("../backend/process_admin_page.php", {
    method: "POST",
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  }).then(res => {
    return res.text()
  }).then(txt => {
    // Check for Server-Side-Errors
    if (txt.includes("Exception")) {
      Metro.toast.create("Bei Speichern ist ein Fehler aufgetreten. Existiert der Benutzername bereits?", null, null, "alert", {showTop: true});
      console.warn(txt);
    } else if (txt.includes("Error")) {
      Metro.toast.create(txt, null, null, "alert", {showTop: true});
    } else {
      Metro.toast.create("Daten gespeichert!", () => {window.location.reload();}, 500, "success", {showTop: true});
    }
  })
  .catch(err => {
    Metro.toast.create("Bei Speichern ist ein Fehler aufgetreten. (FETCH)", null, null, "alert", {showTop: true});
    console.warn(err);
  })
}


// Versucht die aktualisierte Nachricht für den Monitor an den Server zu senden.
function updateMonitor() {
  let data = {
    "MONITOR_NACHRICHT" : document.getElementById('formMonitorNachricht').value
  }

  fetch("../backend/process_admin_page.php", {
    method: "POST",
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  }).then(res => {
    return res.text()
  }).then(txt => {
    // Check for Server-Side-Errors
    if (txt.includes("Error")) {
      Metro.toast.create("Bei Speichern ist ein Fehler aufgetreten. (SERVER)", null, null, "alert", {showTop: true});
      console.warn(txt);
    } else {
      Metro.toast.create("Daten gespeichert!", () => {window.location.reload();}, 500, "success", {showTop: true});
    }
  })
  .catch(err => {
    Metro.toast.create("Bei Speichern ist ein Fehler aufgetreten. (FETCH)", null, null, "alert", {showTop: true});
    console.warn(err);
  })
}
