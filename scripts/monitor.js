// Aktualisiert die Uhrzeit im Header
function page_header_updateTime(){
  let dt = new Date()
  let hrs = (dt.getHours() < 10) ? '0'+dt.getHours() : dt.getHours()
  let mins = (dt.getMinutes() < 10) ? '0'+dt.getMinutes() : dt.getMinutes()
  document.getElementById('page-timedisplay').textContent = hrs + ':' + mins
}

// Eine klassische 2D-Matrix-Rotation für beliebige Punkte
//    Hier wird um den Mittelpunkt (250|250) rotiert.
function pointRotate(x, y, theta) {
  x = x - 250
  y = y - 250

  let nx = x*Math.cos(theta) - y*Math.sin(theta)
  let ny = x*Math.sin(theta) + y*Math.cos(theta)

  nx = nx + 250
  ny = ny + 250

  return [nx, ny]
}

// Wendet die Rotations-Funktion auf die Punkte der Tachonadel an
function rotateArrow(degrees) {
  let theta = degrees * (Math.PI / 180)
  let points = [
    [249.821, 52],
    [255.737, 241.855],
    [243.905, 241.855],
    [249.821, 52]
  ]

  let ps = points.map(p => pointRotate(p[0], p[1], theta)).map(p => [p[0].toFixed(3), p[1].toFixed(3)])
  return `M ${ps[0][0]} ${ps[0][1]} L ${ps[1][0]} ${ps[1][1]} L ${ps[2][0]} ${ps[2][1]} L ${ps[3][0]} ${ps[3][1]} Z`
}

// Die Bereiche des Tachos umfassen unterschiedlich viele Patienten.
//    Für korrekte Anzeige muss der Winkel der Tachonadel für jeden Bereich einzeln berechnet werden.
function arrowFromPatNr(patnr) {
  let theta = 492
  if (patnr <= 10) {
    // Grau:  226 - 295 Deg
    theta = 226 + patnr * ((295-226) / 10)
  } else if (patnr <= 35) {
    // Grün:  299 - 421 Deg
    theta = 299 + (patnr - 10) * ((421-299) / 25)
  } else if (patnr <= 40) {
    // Gelb:  425 - 475 Deg
    theta = 425 + (patnr - 35) * ((475-425) / 5)
  } else if (patnr <= 50) {
    // Rot:   479 - 492 Deg
    theta = 479 + (patnr - 40) * ((492-479) / 10)
  }
  return rotateArrow(theta % 360)
}
