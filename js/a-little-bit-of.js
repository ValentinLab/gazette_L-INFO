// Attendre que le DOM soit prêt
document.addEventListener("DOMContentLoaded", isReady);

/**
 * Fonctions à réaliser lorsque le DOM est prêt
 */
function isReady() {
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