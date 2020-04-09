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

      echo '<form action="connexion.php" method="post">',
        '<table>';
          vpac_print_table_form_input('Pseudo', 'pseudo', vpac_protect_data($pseudo), true);
          vpac_print_table_form_input('Mot de passe', 'passe', '', true, 'password');
          vpac_print_table_form_invicible_input('referer', $_SERVER['HTTP_REFERER']);
          vpac_print_table_form_button(array('submit', 'reset'), array('Se connecter', 'Annuler'), array('btnConnexion', ''));
        echo '</table>',
      '</form>',
      '<p>Pas encore inscrit ? N\'attendez pas, <a href="inscription.php">inscrivez-vous</a> !</p>',
    '</section>';
}

/**
 * Traitement du formulaire
 * 
 * @return array Tableau à remplir avec les erreurs de saisie
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
  $referer = '../index.php';
  if(!empty($_POST['referer'])) {
    if(!filter_var($_POST['referer'], FILTER_VALIDATE_URL)) {
      header('Location: ../index.php');
      exit();
    }
    $referer = $_POST['referer'];
  }

  if(!empty($errors)) {
    return $errors;
  }

  // Requête à la bd
  $bd = vpac_bd_connecter();
  $pseudo_e = mysqli_real_escape_string($bd, $pseudo);
  $sql = "SELECT utPseudo, utStatut
          FROM utilisateur
          WHERE utPseudo='{$pseudo_e}'";
  $res = mysqli_query($bd, $sql) or vpac_bd_erreur($bd, $sql);
  $data = mysqli_fetch_assoc($res);
  mysqli_free_result($res);
  mysqli_close($bd);

  $hash = password_hash($_POST['passe'], PASSWORD_DEFAULT);
  if($data == NULL || !password_verify($_POST['passe'], $hash)) {
    $errors[] = "Échec d'authentification. Utilisateur inconnu ou mot de passe incorrect.";
  }

  if(!empty($errors)) {
    return $errors;
  }

  // Mémoriser dans la variable de session
  $redacteur = ($data['utStatut'] == 1 || $data['utStatut'] == 3);
  $administrateur = ($data['utStatut'] == 2 || $data['utStatut'] == 3);
  $_SESSION['user'] = array('pseudo' => $pseudo, 'redacteur' => $redacteur, 'administrateur' => $administrateur);

  header("Location: {$referer}");
}
?>