<?php
// ----------------------------------------
// Gestion des utilisateurs
// ----------------------------------------

/**
 * Détruire une session
 * 
 * @param string $redirection Page vers laquelle l'utilisateur sera redirigé
 */
function vp_session_exit($redirection = '../index.php') {
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
function vp_check_authentication() {
  if(!isset($_SESSION['utPseudo'])) {
    vp_session_exit();
  }
}
?>