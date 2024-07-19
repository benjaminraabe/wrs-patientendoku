// Öffnet und Resettet den Dialog zum anlegen/modifizieren eines Benutzers.
function openDlg(username, newUser=false) {
  // Titel setzen
  document.getElementById('dlgTitle').textContent = (newUser) ? "Neuer Benutzer"
                                                              : "[" + username + "] bearbeiten"

  // Reset Form
  let form = document.getElementById('userForm').reset()

  // NewUser / EditUser
  document.getElementsByName('bNewUser')[0].value = newUser

  // Wenn ein Benutzer bearbeitet wird, werden die entsprechenden Daten gesetzt
  if (!newUser) {
    // Username eintragen
    document.getElementsByName('iUsername')[0].value = username

    // Benutzerrollen setzen
    let checkedRoles = user_roles.filter((role) => role["USERNAME"] == username)
                                 .map((el) => el["NAME"])
    Array.from(document.getElementsByName('bUserrole[]'))
      .filter((cb) => checkedRoles.includes(cb.dataset.caption))
      .map((cb) => {cb.checked = true})

    // UHS-Einschränkung setzen
    let uhs = user_list.filter((role) => role["USERNAME"] == username)
                       .reduce((_, el) => el["UHST_ID"], null)
    document.getElementsByName('sUHS')[0].value = uhs ?? ""

    // Aktiv toggeln
    let active = user_list.filter((role) => role["USERNAME"] == username)
                          .reduce((_, el) => el["ACTIVE"], 1)
    document.getElementsByName('bActive')[0].checked = (active === 1)
  }

  // Show Dialog
  document.getElementsByName('iUsername')[0].disabled = (!newUser);
  Metro.dialog.open("#editDialog");
}

function validateAndSend() {
  let newUser = document.getElementsByName('bNewUser')[0].value == "true"

  if (newUser) {
    if (document.getElementsByName('iUsername')[0].value == ''
        || document.getElementsByName('iPassword')[0].value == ''){
          alert("Username und Passwort müssen angegeben werden.")
          return;
        }
  }
  // Damit deaktivierte Checkboxen/Inputs beim Post trotzdem gesendet werden, müssen sie vorher aktiviert werden.
  Array.from(document.getElementById('userForm').getElementsByTagName('input')).map((el) => el.disabled = false)
  document.getElementsByName('iUsername')[0].disabled = false;

  document.getElementById('userForm').submit()
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
