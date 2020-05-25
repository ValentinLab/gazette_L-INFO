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
    window.scroll(0, statusBox[0].getBoundingClientRect().y - 110)
  }
}

/**
 * Afficher un aperçu de l'image qui sera téléchargé
 * 
 * @param {*} event Événement
 */
function preview_upload(event) {
  var reader = new FileReader();
  reader.onload = function() {
    var output = document.getElementById('upload_pic_row').getElementsByTagName('td')[0].getElementsByTagName('img')[0];
    output.src = reader.result;
  }

  if(event.target.files[0].type == "image/jpeg") {
    reader.readAsDataURL(event.target.files[0]);
  }
}