// Öffnet und Resettet den Dialog zum anlegen/modifizieren eines Benutzers.
function openDlg(role_id, newRole=false) {
  // Titel setzen
  document.getElementById('dlgTitle').textContent = (newRole) ? "Neue Benutzerrolle"
                                                              : "Benutzerrolle bearbeiten"

  // Reset Form
  let form = document.getElementById('permissionForm').reset()

  // Neue Rolle / Oder editieren
  document.getElementsByName('bNewPerm')[0].value = newRole

  // Wenn ein Benutzer bearbeitet wird, werden die entsprechenden Daten gesetzt
  if (!newRole) {
    // Datensatz finden
    let data = role_list.filter((role) => role["ROLE_ID"] == role_id)
                        .reduce((_, el) => el, null)
    // Rollenname und ID eintragen
    document.getElementsByName('iName')[0].value = data["NAME"]
    document.getElementsByName('iRoleID')[0].value = role_id

    // Beschreibung eintragen
    document.getElementsByName('iDescription')[0].value = data["DESCRIPTION"]

    // Benutzerrollen setzen
    let checkedPermissions = role_x_perm.filter((el) => el["ROLE_ID"] == data["ROLE_ID"])
                                 .map((el) => el["PERMISSION_ID"]+"")
    console.log(document.getElementsByName('bPerm[]'));
    console.log(checkedPermissions);
    Array.from(document.getElementsByName('bPerm[]'))
      .filter((cb) => checkedPermissions.includes(cb.value))
      .map((cb) => {cb.checked = true})
  }

  // Show Dialog
  Metro.dialog.open("#editDialog");
}
