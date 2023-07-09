// Leert alle Felder
function suche_reset() {
  document.getElementById('iID').value = ''
  document.getElementById('iName').value = ''
  document.getElementById('iDOB').value = ''
  document.getElementById('iEingang').value = ''
  document.getElementById('iAusgang').value = ''
  suche()
}

// Blendet Zeilen aus, die nicht den Eingaben aus den Suchfeldern entsprechen
function suche() {
  let s_id = document.getElementById('iID').value
  let s_name = document.getElementById('iName').value
  let s_dob = document.getElementById('iDOB').value
  let s_eingang = document.getElementById('iEingang').value
  let s_ausgang = document.getElementById('iAusgang').value

  // Datums-Matching ist komplizierter.
  //    Pattern-Matching wird betrieben, damit die Datums-Felder nur "Auslösen", wenn sie vollständig ausgefüllt sind.
  //    Danach muss das Datum so formatiert wird, dass es mit dem Datum aus der DB übereinstimmen kann
  let date_options = {day: '2-digit', month: '2-digit', year: '2-digit'}
  if (s_dob.match(/[1-9][0-9]{3}.*/g)) {s_dob = (new Date(s_dob)).toLocaleDateString("de-DE", date_options)}
  else {s_dob = ''}
  if (s_eingang.match(/[1-9][0-9]{3}.*/g)) {s_eingang = (new Date(s_eingang)).toLocaleDateString("de-DE", date_options)}
  else {s_eingang = ''}
  if (s_ausgang.match(/[1-9][0-9]{3}.*/g)) {s_ausgang = (new Date(s_ausgang)).toLocaleDateString("de-DE", date_options)}
  else {s_ausgang = ''}

  let rows = document.getElementsByClassName('data-row')
  for (let row of rows) {
    let cells = row.getElementsByClassName('cell-2')
    let show = false
    // Catchall: Wenn kein Filter gesetzt ist, ist alles sichtbar
    if (s_id == ''
        && s_name == ''
        && s_dob == ''
        && s_eingang == ''
        && s_ausgang == '') {
      show = true
    }
    // Cases: Es wird nach den eingegebenen Daten gefiltert
    if ((s_id == '' || cells[0].textContent.includes(s_id))
        && (s_name == '' || cells[1].textContent.toLowerCase().includes(s_name.toLowerCase()))
        && (s_dob == '' || cells[2].textContent.includes(s_dob))
        && (s_eingang == '' || cells[3].textContent.includes(s_eingang))
        && (s_ausgang == '' || cells[4].textContent.includes(s_ausgang))){show = true}

    // Je nach Ergebnis wird die Zeile angezeigt oder nicht
    if (show) {row.style.display = 'flex'} else {row.style.display = 'none'}
  }
}
