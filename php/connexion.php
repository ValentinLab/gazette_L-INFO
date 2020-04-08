<?php
ob_start();
session_start();

require_once 'bibli_gazette.php';
require_once 'bibli_generale.php';

// Vérifier l'authentification
if(isset($_SESSION['utPseudo'])) {
  header('Location: ../index.php');
  exit();
}

// ----------------------------------------
// Valeur du formulaire
// ----------------------------------------

$values = array(
  'utPseudo' => ''
  );
$errors = array();

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vp_get_head('Connexion');
vp_get_nav();
vp_get_header('Connexion');

// Formulaire
if(isset($_POST['btnConnexion'])) {
  vpl_form_processing($values, $errors);
} else {
  vpl_print_form($values, $errors);
}

// Footer
vp_get_footer();

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Traitement du formulaire
 * 
 * @param array $values Tableau à remplir avec les valeurs du formulaire
 * @param array $errors Tableau à remplir avec les erreurs de saisie
 */
function vpl_form_processing($values, $errors) {
  // Vérifier les clés présentes dans $_POST
  if(!vp_parametres_controle('post', array('pseudo', 'passe', 'btnConnexion'))) {
    header('Location: ../index.php');
    exit();
  }

  // Vérification du pseudo
  if(empty($_POST['pseudo'])) {
    $errors[] = "Vous devez saisir votre pseudo";
  }
  $values['utPseudo'] = $_POST['pseudo'];

  // Vérification du mot de passe
  if(empty($_POST['passe'])) {
    $errors[] = "Vous devez saisir votre mot de passe";
  }

  if(empty($errors)) {
    $bd = vp_bd_connecter();
    $utPseudo = mysqli_real_escape_string($bd, $_POST['pseudo']);
    $sql = "SELECT utPseudo, utStatut
            FROM utilisateur
            WHERE utPseudo='{$utPseudo}'";

    $res = mysqli_query($bd, $sql) or vp_bd_erreur($bd, $sql);
    $data = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    mysqli_close($bd);

    $hash = password_hash($_POST['passe'], PASSWORD_DEFAULT);
    if($data == NULL || !password_verify($_POST['passe'], $hash)) {
      $errors[] = "Échec d'authentification. Utilisateur inconnu ou mot de passe incorrect.";
    }
  }

  // Affichage du formulaire
  if(!empty($errors)) {
    vpl_print_form($values, $errors);
    exit();
  }

  // Mémoriser dans la variable de session
  $_SESSION['utPseudo'] = $values['utPseudo'];
  $_SESSION['utStatut'] = $data['utStatut'];

  header('Location: ../index.php');
}

/**
 * Affichage du formulaire
 * 
 * @param array $values Tableau avec les valeurs du formulaire
 * @param array $errors Tableau avec les erreurs de saisie
 */
function vpl_print_form($values, $errors) {
  echo '<section>',
    '<h2>Formulaire de connexion</h2>',
      '<p>Pour vous identifier, remplissez le formulaire ci-dessous.</p>';

      // Affichage des erreurs
      vp_print_form_errors($errors);

      echo '<form action="connexion.php" method="post">',
        '<table>';
          vp_print_table_form_input('Pseudo', 'pseudo', htmlentities($values['utPseudo']), true);
          vp_print_table_form_input('Mot de passe', 'passe', '', true, 'password');
          vp_print_table_form_button(array('submit', 'reset'), array('Se connecter', 'Annuler'), array('btnConnexion', ''));
        echo '</table>',
      '</form>',
      '<p>Pas encore inscrit ? N\'attendez pas, <a href="inscription.php">inscrivez-vous</a> !</p>',
    '</section>';
}
?>