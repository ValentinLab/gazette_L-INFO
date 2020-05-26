<?php
ob_start();
session_start();

require_once 'bibli_gazette.php';
require_once 'bibli_generale.php';

// Vérifier l'authentification
if(isset($_SESSION['user'])) {
  header('Location: ../index.php');
  exit();
}

// ----------------------------------------
// Traitement du formulaire
// ----------------------------------------

$errors = array();
if(isset($_POST['btnConnexion'])) {
  $errors = vpacl_form_processing();
}

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Connexion');
vpac_get_nav();
vpac_get_header('Connexion');

// Formulaire
vpacl_print_form($errors);

// Footer
vpac_get_footer();
ob_end_flush();

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Affichage du formulaire
 * 
 * @param array $errors Tableau avec les erreurs de saisie
 */
function vpacl_print_form($errors) {
  echo '<section>',
    '<h2>Formulaire de connexion</h2>',
      '<p>Pour vous identifier, remplissez le formulaire ci-dessous.</p>';

      // Affichage des erreurs
      vpac_print_form_errors($errors);

      // Valeur du formulaire
      $pseudo = (isset($_POST['btnConnexion'])) ? $_POST['pseudo'] : '';
      $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';

      echo '<form action="connexion.php" method="post">',
        '<table>';
          vpac_print_table_form_input('Pseudo', 'pseudo', vpac_protect_data($pseudo), true);
          vpac_print_table_form_input('Mot de passe', 'passe', '', true, 'password');
          vpac_print_table_form_invisible_input('referer', vpac_encrypt_url($referer));
          vpac_print_table_form_button(array('submit', 'reset'), array('Se connecter', 'Annuler'), array('btnConnexion', ''));
        echo '</table>',
      '</form>',
      '<p>Pas encore inscrit ? N\'attendez pas, <a href="inscription.php">inscrivez-vous</a> !</p>',
    '</section>';
}

/**
 * Traitement du formulaire
 * 
 * @return array Résultat du traitement du formulaire
 */
function vpacl_form_processing() {
  // Vérifier les clés présentes dans $_POST
  if(!vpac_parametres_controle('post', array('pseudo', 'passe', 'referer', 'btnConnexion'))) {
    header('Location: ../index.php');
    exit();
  }

  // Vérification du pseudo
  $pseudo = trim($_POST['pseudo']);
  if(empty($_POST['pseudo'])) {
    $errors[] = "Vous devez saisir votre pseudo";
  }

  // Vérification du mot de passe
  if(empty($_POST['passe'])) {
    $errors[] = "Vous devez saisir votre mot de passe";
  }

  // Vérificattion de referer
  $referer = vpac_decrypt_url(urldecode($_POST['referer']));
  if($referer === FALSE) {
    header('Location: ../index.php');
    exit;
  }

  if(!empty($errors)) {
    return $errors;
  }

  // Requête à la bd
  $db = vpac_db_connect();
  $pseudo_e = mysqli_real_escape_string($db, $pseudo);
  $sql = "SELECT utPseudo, utStatut
          FROM utilisateur
          WHERE utPseudo='{$pseudo_e}'";
  $res = mysqli_query($db, $sql) or vpac_db_error($db, $sql);
  $data = mysqli_fetch_assoc($res);
  mysqli_free_result($res);
  mysqli_close($db);

  $hash = password_hash($_POST['passe'], PASSWORD_DEFAULT);
  if($data == NULL || !password_verify($_POST['passe'], $hash)) {
    $errors[] = "Échec d'authentification. Utilisateur inconnu ou mot de passe incorrect.";
  }

  if(!empty($errors)) {
    return $errors;
  }

  // Obtenir le thème
  if(isset($_COOKIE["theme_user_{$pseudo}"])) {
    $theme = $_COOKIE["theme_user_{$pseudo}"];
  } else {
    $theme = CUSTOM_LIGHT;
  }

  // Mémoriser dans la variable de session
  vpac_connect_user($pseudo, $data['utStatut'], $theme);

  header("Location: {$referer}");
}
?>