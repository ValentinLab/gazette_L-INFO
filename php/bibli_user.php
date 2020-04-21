<?php
// ----------------------------------------
// Gestion des utilisateurs
// ----------------------------------------

/**
 * Créer la session d'un utilisateur
 * 
 * @param string $pseudo Pseudo de l'utilisateur
 * @param int    $statut Statut de l'utilisateur dans l'intervalle [0, 3]
 */
function vpac_connect_user($pseudo, $statut) {
  $writer = ($statut == 1 || $statut == 3);
  $administrator = ($statut == 2 || $statut == 3);

  $_SESSION['user'] = array('pseudo' => $pseudo, 'writer' => $writer, 'administrator' => $administrator);
}

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
 * 
 * @param int $min_rights Droit que l'utilisateur doit posséder
 *                        ALL_U :                      pour tous les utilisateurs
 *                        WRITER_U :                   pour les rédacteurs uniquement
 *                        ADMINISTRATOR_U :            pour les administrateurs uniquement
 *                        WRITER_U | ADMINISTRATOR_U : pour les rédacteurs et administrateurs
 */
function vpac_check_authentication($rights = ALL_U) {
  // Vérifier que l'utilisateur est conecté
  if(!isset($_SESSION['user'])) {
    vpac_session_exit();
  }

  // Vérifier si tous les utilisateurs peuvent accéder à la page
  if($rights == ALL_U) {
    return;
  }

  // Autrement, vérifier que l'utilisateur à le bon niveau de droit
  $current_rights = 0b01*$_SESSION['user']['writer'] + 0b10*$_SESSION['user']['administrator'];
  if($current_rights & $rights == 0) {
    vpac_session_exit();
  }
}

/**
 * Transformer les droits de l'utilisateur en ue chaîne de caractères
 * 
 * @param int $user_rights Droits de l'utilisateur
 *                         Si NULL, les droits de l'utilisateur courant
*                          sont utilisés
 * @return string Chaîne représentant les droits
 */
function vpac_rights_to_string($user_rights = NULL) {
  // Si aucun statut n'est spécifié
  if(is_null($user_rights)) {
    // Vérifier que l'utilisateur est connecté
    if(!isset($_SESSION['user'])) {
      return '';
    }

    // Calculer les droits
    $user_rights = 0b01*$_SESSION['user']['writer'] + 0b10*$_SESSION['user']['administrator'];
  }

  // Transformation des droits en string
  $rights_str = '';
  switch($user_rights) {
    case 0:
      $rights_str = 'utilisateur';
    case 1:
      $rights_str = 'rédacteur';
      break;
    case 2:
      $rights_str = 'administrateur';
      break;
    case 3:
      $rights_str = 'admin/rédacteur';
      break;
  }

  return $rights_str;
}
?>