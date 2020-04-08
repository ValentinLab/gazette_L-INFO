<?php
// ----------------------------------------
// Gestion des utilisateurs
// ----------------------------------------

/**
 * Détruire une session
 * 
 * @param string $redirection Page vers laquelle l'utilisateur sera redirigé
 */
function vpac_session_exit($redirection = '../index.php') {
  session_destroy();
  session_unset();

  $cookie_params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 86400, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);

  header("Location: $redirection");
  exit();
}

/**
 * Vérifier qu'un utilisateur est authentifié
 */
function vpac_check_authentication() {
  if(!isset($_SESSION['utPseudo'])) {
    vpac_session_exit();
  }
}
?>