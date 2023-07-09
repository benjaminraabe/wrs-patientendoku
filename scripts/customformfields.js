function selectButton(button, buttonGroupClass) {
  let groupWrapper = document.getElementsByClassName(buttonGroupClass)
  // Get all wrappers for this buttongroup
  for (let group of groupWrapper) {
    // Get all buttons in all wrappers
    let buttons = group.getElementsByTagName('button')
    for (var btn of buttons) {
      btn.classList.remove("active")
    }
  }
  button.classList.add("active")
}

function guidGenerator() {
  var S4 = function() {
    return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
  };
  return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
}


// Set-Up Buttons on page
let buttonWrappers = document.getElementsByClassName('buttonwrapper')
for (let wrapper of buttonWrappers) {
  let buttons = wrapper.getElementsByTagName('button')
  for (let button of buttons) {
    // Apply Styling
    button.classList.add("formbutton")

    // Apply inner text
    let buttonText = button.getAttribute("data-text")
    if (buttonText) {
      let textWrapper = document.createElement('div')
      textWrapper.classList.add('button-text')
      textNode = document.createTextNode(buttonText)
      textWrapper.appendChild(textNode)
      button.appendChild(textWrapper)
    }
  }
}

// Apply JS to Buttons of a common group
let buttongroups = document.getElementsByClassName('buttongroup')
for (let group of buttongroups) {
  let groupID = 'buttonGroup_' + guidGenerator()
  group.classList.add(groupID)
  let groupButtons = group.getElementsByTagName('button')
  for (let button of groupButtons) {
    button.addEventListener("click", () => {
      selectButton(button, groupID)
    })
  }
}

// Beschreibungen für alle Felder einfügen
let fields = Array.from(document.getElementsByClassName('field'))
let buttons = Array.from(document.getElementsByClassName('formbutton'))
fields = fields.concat(buttons)
for (var field of fields) {
  let captionText = field.getAttribute("data-caption")
  if (captionText) {
    let caption = document.createElement('div')
    caption.classList.add('field-caption')
    textNode = document.createTextNode(captionText)

    if (field.childNodes.length == 0) {
      let edit = document.createElement('input')
      edit.type = 'text'
      edit.value = field.getAttribute("data-value")
      edit.onclick = function () {this.select()}
      field.appendChild(edit)
    }

    caption.appendChild(textNode)
    field.insertBefore(caption, field.firstChild)
    // field.appendChild(caption)
  }

  // Helper: Focus the input field, even when not prrecisely clicking on it.
  // containedEdit = field.getElementsByTagName('input')
  // if (containedEdit.length == 1) {
  //   eElement = containedEdit[0]
  //   field.addEventListener("click", (ev) => {
  //     eElement.focus()
  //   })
  // }
}
