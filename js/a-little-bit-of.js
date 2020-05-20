// Attendre que le DOM soit prêt
document.addEventListener("DOMContentLoaded", start);

function start() {
  toStatusBox();
}

/**
 * Positionner l'utilisateur au niveau des messages d'erreurs et de succès
 */
function toStatusBox() {
  var statusBox = document.getElementsByClassName('statusBox');
  if(statusBox.length > 0) {
    if(statusBox[0].getBoundingClientRect().y > window.innerHeight) {
      window.scroll(0, statusBox[0].getBoundingClientRect().y + 40)
    }
  }
}