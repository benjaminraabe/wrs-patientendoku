// Globales Code-Reader-Objekt zur Nutzung in anderen Funktionen.
var codeReader = null
var selectedDeviceId = null

// Aktiviert / Deaktiviert den Senden-Knopf, je nachdem ob etwas im Edit-Feld steht.
function toggleFormSend(el) {
  if (el.value.length > 0) {
    document.getElementById('codescan-submit').disabled = false
  } else {
    document.getElementById('codescan-submit').disabled = true
  }
}

// Startet die Kameraauswertung für den Barcode-Reader
function startBarcodeReader() {
  document.getElementById('barcodeScanResult').value = ''
  codeReader.decodeOnceFromVideoDevice(selectedDeviceId, 'video').then((result) => {
      console.log("Raw barcode result:", result)
      document.getElementById('barcodeScanResult').value = result.text
      successfulRead()
  }).catch((err) => {

    if (!err.includes("ended before any code")) {
      console.error(err)
      document.getElementById('barcodeScanError').textContent = err
    }
  })
  console.log(`Started decode from camera with id ${selectedDeviceId}`)
}

// Resettet und stoppt die Kameraauswertung
function resetBarcodeReader() {
  codeReader.reset()
  document.getElementById('barcodeScanError').textContent = ''
}

function successfulRead() {
  document.getElementById('codescan-form').submit()
}

// Nachdem die Seite vollständig geladen wurde, werden die Eingabegeräte initialisiert.
window.addEventListener('load', function () {
  codeReader = new ZXing.BrowserBarcodeReader()
  console.log('ZXing code reader initialized')

  codeReader.getVideoInputDevices()
    .then((videoInputDevices) => {
        const sourceSelect = document.getElementById('sourceSelect')
        let backCameraFound = false
        selectedDeviceId = null

        // Wenn bereits eine Kamera ausgewählt wurde, wird versucht diese erneut zu verwenden
        if (!(localStorage.getItem("preferredCameraId") === null)) {
          selectedDeviceId = localStorage.getItem("preferredCameraId")
        } else {
          console.log("Keine bevorzugte Kamera gefunden. Versuche Rück-Kamera zu finden...");
          // Ist dies nicht der Fall, wird versucht eine Kamera mit "back" im Namen zu verwenden, um möglichst
          //    die Rück-Kamera zu verwenden
          videoInputDevices.forEach((camera) => {
            if (camera.label.toLowerCase().includes("back")) {
              selectedDeviceId = camera.deviceId
              localStorage.setItem("preferredCameraId", selectedDeviceId)
              backCameraFound = true
            }
          });
          // Ist beides nicht möglich, wird die erste Kamera verwendet.
          if (!backCameraFound) {
            console.log("Keine Rück-Kamera gefunden. Fallback auf erste gelistete Kamera");
            selectedDeviceId = videoInputDevices[0].deviceId
          }
        }


        // Wenn mehr als ein Video-Eingabegerät vorhanden ist, wird eine Auswahlbox angezeigt
        if (videoInputDevices.length > 1) {
            videoInputDevices.forEach((element) => {
                const sourceOption = document.createElement('option')
                sourceOption.text = element.label
                sourceOption.value = element.deviceId
                if (element.deviceId == selectedDeviceId) {
                  sourceOption.selected = true
                }
                sourceSelect.appendChild(sourceOption)
            })

            sourceSelect.onchange = () => {
                selectedDeviceId = sourceSelect.value;
                // Kamera wird als neuer Favorit gespeichert
                localStorage.setItem("preferredCameraId", sourceSelect.value)
                resetBarcodeReader()
                startBarcodeReader()
            }

            document.getElementById('sourceSelectPanel').style.display = 'block'
        }

        // Auslesen wird bei erfolgreicher initialisierung automatisch gestartet.
        startBarcodeReader()
    })
    .catch((err) => {
        document.getElementById('barcodeScanError').textContent = ''
    })
})
