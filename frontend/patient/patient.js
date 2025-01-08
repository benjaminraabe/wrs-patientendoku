function update_pat_data(patient_exit=false,
                         patient_transport_requested=false,
                         patient_hand_over=false,
                         patient_time_edit=false) {
  displayLoadingScreen(true);

  let pat_id = (new URLSearchParams(window.location.search)).get("id")
                || (new URLSearchParams(window.location.search)).get("nachtrPatID")

  // Sichtungs- und Abteilungsdaten
  let sichtungskategorie = "UNKNOWN";
  try {
    sichtungskategorie = document.getElementsByClassName('js-sichtk active')[0].dataset.text.toUpperCase();
  } catch (e) {console.log("Sichtungskategorie konnte nicht ausgelesen werden.", e);}

  let bereich = null;
  try {
    bereich = document.getElementsByClassName('js-bereich active')[0].dataset.value;
  } catch (e) {console.log("Bereich konnte nicht ausgelesen werden.");}

  // Personendaten
  let vorname = document.getElementById('form-Vname').children[1].value;
  let name = document.getElementById('form-Name').children[1].value;
  let geburtsdatum = document.getElementById('form-Geburtsdatum').children[1].value;
  let geschlecht = document.getElementById('form-geschlecht-select').value || null;

  // Adresse
  let land = document.getElementById('form-Land').children[1].value;
  let ort = document.getElementById('form-Ort').children[1].value;
  let strasse = document.getElementById('form-Str').children[1].value;
  let hausnummer = document.getElementById('form-Hnr').children[1].value;

  // Sonstige
  let pzc = document.getElementById('pzc-input').value;
  let bemerkung = document.getElementById('form-Bemerkung').getElementsByTagName('textarea')[0].value;
  let transportKat = document.getElementById('form-transport-kategorie').value;
  let transportZiel = document.getElementById('form-transport-zielkh').value;
  let transportCallsign = document.getElementById('form-transport-rufname').value;

  let verknuepfungen = document.getElementById('eProtokollVerkn').value;
  let isInfekt = document.getElementById('cbInfekt').checked;
  let isUeberschwer = document.getElementById('cbGewicht').checked;

  // Art des Patienteneingangs wird abgefragt
  let eArt = 0;
  try {
    eArt = document.getElementsByClassName('js-eingangsart active')[0].dataset.value;
  } catch (e) {console.log("Eingangsart konnte nicht ausgelesen werden.");}

  // Eventuelle Veränderungen an Ein- und Ausgangszeit werden abgefragt
  let newDateIn = document.getElementById('frmEinDatum').value;
  let newTimeIn = document.getElementById('frmEinZeit').value;
  let newDateOut = document.getElementById('frmAusDatum').value;
  let newTimeOut = document.getElementById('frmAusZeit').value;



  // Construct the request-data
  let data = {
    "patient" : {
      "PATIENTEN_ID" : pat_id,
      "SICHTUNGSKATEGORIE" : sichtungskategorie,
      "BEREICH_ID" : bereich,
      "PZC" : pzc,
      "VORNAME" : vorname,
      "NAME" : name,
      "LAND" : land,
      "ORT" : ort,
      "STRASSE" : strasse,
      "HAUSNUMMER" : hausnummer,
      "BEMERKUNG" : bemerkung,
      "GESCHLECHT" : geschlecht,
      "DOB" : geburtsdatum,
      "INFEKT" : (isInfekt) ? 1 : 0,
      "UEBERSCHWER" : (isUeberschwer) ? 1 : 0,
      "EINGANGSART" : eArt,
      "VERKNUEPFUNG" : verknuepfungen
    },
    "exit" : patient_exit
  }

  if (patient_transport_requested) {
    data["TRANSPORTKATEGORIE"] = transportKat
  }
  if (patient_hand_over) {
    data["TRANSPORT_RUFNAME"] = transportCallsign
    data["TRANSPORT_ZIELKLINIK"] = transportZiel
  }
  if (patient_time_edit) {
    data["EINGANG_TIMESTAMP"] = newDateIn + ' ' + newTimeIn
    data["AUSGANG_TIMESTAMP"] = newDateOut + ' ' + newTimeOut
  }

  // console.log(data);
  // return;

  fetch("../backend/updatepatient.php", {
    method: "POST",
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  }).then(res => {
    return res.text()
  }).then(txt => {
    // Check for Server-Side-Errors
    if (txt.includes("Error") || txt.includes("Exception")) {
      Metro.toast.create("Bei Speichern ist ein Fehler aufgetreten. (SERVER)", null, null, "alert", {showTop: true});
      console.warn(txt);
      displayLoadingScreen(false);
    } else {
      // console.log(txt);
      // return
      Metro.toast.create("Daten gespeichert! (" + current_time_string() + " Uhr)", () => {history.back();}, 1000, "success", {showTop: true});
    }
  })
  .catch(err => {
    Metro.toast.create("Bei Speichern ist ein Fehler aufgetreten. (FETCH)", null, null, "alert", {showTop: true});
    console.log(err);
    displayLoadingScreen(false);
  })
}

// Helper-Function returns a String-Representation of the current time in hh:mm 24-h format
function current_time_string() {
  let dt = new Date()
  let hrs = (dt.getHours() < 10) ? '0'+dt.getHours() : dt.getHours()
  let mins = (dt.getMinutes() < 10) ? '0'+dt.getMinutes() : dt.getMinutes()
  return hrs + ':' + mins
}


function selectPZC(pzc, description) {
  document.getElementById('pzc-input').value = pzc;
  document.getElementById('pzc-beschreibung').textContent = description;
  Metro.dialog.close('#pzcDialog');
}


// Open the PZC-Search Dialog and set it up (Clear search, show all entries, focus searchfield)
function openPzcSuche() {
  let suchfeld = document.getElementById('i-pzcsuche');
  suchfeld.value = '';
  pzcSuche(suchfeld);
  Metro.dialog.open('#pzcDialog');
  suchfeld.focus();
}


function pzcSuche(searchinput) {
  let searchword = searchinput.value;

  let rows = document.getElementsByClassName('pzctable')[0].getElementsByClassName('row')
  for (let row of rows) {
    if (row.textContent.toUpperCase().includes(searchword.toUpperCase())) {
      row.style.display = 'flex';
    } else {
      row.style.display = 'none';
    }
  }
}


// Prüft ob die Einträge für die Entlassung vollständig sind.
function checkExitData() {
  let missingEL = []

  // PZC fehlt
  if (document.getElementById('pzc-input').value == "") {
    missingEL.push("PZC");
  }
  // Name fehlt / unvollständig
  if (document.getElementById('form-Vname').children[1].value == "") {missingEL.push("Vorname");}
  if (document.getElementById('form-Name').children[1].value == "") {missingEL.push("Name");}
  // Adresse fehlt / ist unvollständig. Land wird nicht erzwungen
  if (document.getElementById('form-Ort').children[1].value == "") {missingEL.push("Wohnort");}
  if (document.getElementById('form-Str').children[1].value == "") {missingEL.push("Straße");}
  if (document.getElementById('form-Hnr').children[1].value == "") {missingEL.push("Hausnummer");}

  return missingEL;
}

function openExitPage() {
  let missingEL = checkExitData();

  if (missingEL.length == 0) {
    document.getElementById('wrapperExitCheckSuccess').style.display = 'Block';
    document.getElementById('wrapperExitCheckFail').style.display = 'None';
  } else {
    document.getElementById('wrapperExitCheckSuccess').style.display = 'None';
    document.getElementById('wrapperExitCheckFail').style.display = 'Block';

    let missingHTML = '<div class="row"><div class="cell-2"><span class="mif-cross icon fg-red"></span></div><div class="cell"><b>###ELEMENT###</b> des Patienten fehlt.</div></div>'
    let newHTML = ''
    missingEL.forEach((item, i) => {
      newHTML += missingHTML.replace('###ELEMENT###', item)
    });

    document.getElementById('ExitCheckFailGrid').innerHTML = newHTML;
  }
}


function run_pat_transport_req(with_exit=false) {
  // Mit der Flag wird der Patient an ein Rettungsmittel übergeben
  if (with_exit) {
    update_pat_data(false, false, true);
  } else {
    update_pat_data(false, true);
  }
}


function displayLoadingScreen(state) {
  if (state) {
    document.getElementById('patform-loader').style.display = "block";
  } else {
    document.getElementById('patform-loader').style.display = "none";
  }
}


function openTimeEditDlg() {
  Metro.dialog.open('#dlgTimeEdit');
}


// Darstellungsfunktion für die Tab-Navigation
function openTab(selector, caller) {
  const tabs = document.getElementsByClassName("tab-patient")
  const target = document.querySelector(selector)

  document.querySelector(".tab-button.active").classList.remove("active")
  caller.classList.add("active")

  for (const el of tabs) {
    el.style.display = "none"
  }
  target.style.display = "block"
}
