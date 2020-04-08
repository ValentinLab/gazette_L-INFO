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
  'utPseudo'        => '',
  'utCivilite'      => '',
  'utNom'           => '',
  'utPrenom'        => '',
  'UtDateNaissance' => 0,
  'utEmail'         => '',
  'utPasse'         => '',
  'utMailsPourris'  => true,
  );
$errors = array();

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Inscription');
vpac_get_nav();
vpac_get_header('Inscription');

// Formulaire
if(isset($_POST['btnInscription'])) {
  vpacl_form_processing($values, $errors);
} else {
  vpacl_print_form($values, $errors);
}

// Footer
vpac_get_footer();

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Traitement du formulaire
 * 
 * @param array $values Tableau à remplir avec les valeurs du formulaire
 * @param array $errors Tableau à remplir avec les erreurs de saisie
 */
function vpacl_form_processing($values, $errors) {
  // Vérifier les clés présentes dans $_POST
  if(!vpac_parametres_controle(
    'post', 
    array('pseudo', 'nom', 'prenom', 'naissance_j', 'naissance_m', 'naissance_a', 'email', 'passe1', 'passe2', 'btnInscription'), 
    array('radSexe', 'cbCGU', 'cbSpam'))
  ) {
    vpac_session_exit();
  }

  // Vérification du pseudo
  $values['utPseudo'] = trim($_POST['pseudo']);
  if(!preg_match('/^[a-z0-9]{4,20}$/', $values['utPseudo'])) {
    $errors[] = 'Le pseudo doit contenir entre 4 et 20 caractères minuscules (sans accent) ou chiffres.';
  }

  // Vérification de la civilité
  if(!isset($_POST['radSexe'])) {
    $errors[] = 'Vous devez choisir une civilité.';
  } else if(!in_array($_POST['radSexe'], array('Mr', 'Mme'))) {
    vpac_session_exit();
  }
  switch($_POST['radSexe']) {
    case 'Mr':
      $values['utCivilite'] = 'h';
      break;
    case 'Mme';
    $values['utCivilite'] = 'f';
    break;
  }

  // Vérification du nom et du prénom
  $values['utNom'] = trim($_POST['nom']);
  vpacl_check_name($errors, $values['utNom'], 'prenom', 50);
  $values['utPrenom'] = trim($_POST['prenom']);
  vpacl_check_name($errors, $values['utPrenom'], 'nom', 50);

  // Vérification du jour/mois/année de naissance
  vpacl_check_select($_POST['naissance_j'], 1, 31);
  vpacl_check_select($_POST['naissance_m'], 1, 12);
  vpacl_check_select($_POST['naissance_a'], 1920, 2020);
  // Vérification de l'âge
  $current_date = getdate();
  $cdate = $current_date['year'] * 10000 + $current_date['mon'] * 100 + $current_date['mday'];
  $values['utDateNaissance'] = $_POST['naissance_a'] * 10000 + $_POST['naissance_m'] * 100 + $_POST['naissance_j'];
  if($cdate - 180000 < $values['utDateNaissance']) {
    $errors[] = 'Vous devez avoir au moins 18 ans pour vous inscrire.';
  }

  // Vérification de l'adresse mail
  $values['utEmail'] = trim($_POST['email']);
  $email_len = strlen($values['utEmail']);
  if($email_len == 0) {
    $errors[] = 'L\'adresse mail ne peut pas être vide.';
  } elseif($email_len > 255) {
    $errors[] = 'L\'adresse mail ne peut pas contenir plus de 255 caractères.';
  }
  // Vérification de la validité de l'adresse mail
  if(filter_var($values['utEmail'], FILTER_VALIDATE_EMAIL) === false) {
    $errors[] = 'L\'adresse mail n\'est pas valide.';
  }

  // Vérification du mot de passe 1
  $values['utPasse'] = $_POST['passe1'];
  $passe_len = strlen($values['utPasse']);
  if($passe_len == 0) {
    $errors[] = 'Le mot de passe ne peut pas être vide.';
  } elseif($passe_len > 255) {
    $errors[] = 'Le mot de passe ne peut pas contenir plus de 255 caractères.';
  }

  // Vérification du mot de passe 2
  if($values['utPasse'] != $_POST['passe2']) {
    $errors[] = 'Les mots de passe doivent être identiques.';
  }

  // Vérification des spams
  if(isset($_POST['cbSpam'])) {
    if($_POST['cbSpam'] != 1) {
      vpac_session_exit();
    }
    $values['utMailsPourris'] = true;
  } else {
    $values['utMailsPourris'] = false;
  }

  // Vérification des CGU
  if(!isset($_POST['cbCGU'])) {
    $errors[] = 'Vous devez accepter les CGU.';
  } else if($_POST['cbCGU'] != 1) {
    vpac_session_exit();
  }

  if(empty($errors)) {
    $bd = vpac_bd_connecter();

    $utPseudo = mysqli_real_escape_string($bd, $values['utPseudo']);
    $utEmail = mysqli_real_escape_string($bd, $values['utEmail']);
    $sql = "SELECT utPseudo, utEmail
          FROM utilisateur
          WHERE utPseudo='$utPseudo'
            OR utEmail='$utEmail'";

    $res = mysqli_query($bd, $sql) or vpac_bd_erreur($bd, $sql);

    if(mysqli_num_rows($res) > 0) {
      $data = mysqli_fetch_assoc($res);
      if($data['utPseudo'] == $values['utPseudo']) {
        $errors[] = 'Ce pseudo existe déjà.';
      } else {
        $errors[] = 'Un compte est déjà lié à cet email.';
      }
      mysqli_close($bd);
    }

    mysqli_free_result($res);
  }

  // Affichage des erreurs
  if(!empty($errors)) {
    vpacl_print_form($values, $errors);
    exit();
  }

  // Inscription d'un nouvel utilisateur
  $values['utPseudo'] = mysqli_real_escape_string($bd, $values['utPseudo']);
  $values['utNom'] = mysqli_real_escape_string($bd, $values['utNom']);
  $values['utPrenom'] = mysqli_real_escape_string($bd, $values['utPrenom']);
  $values['utEmail'] = mysqli_real_escape_string($bd, $values['utEmail']);
  $values['utPasse'] = password_hash($values['utPasse'], PASSWORD_DEFAULT);
  $sql = "INSERT INTO utilisateur
      VALUES ('{$values['utPseudo']}',
              '{$values['utNom']}',
              '{$values['utPrenom']}',
              '{$values['utEmail']}',
              '{$values['utPasse']}',
              {$values['utDateNaissance']},
              0,
              '{$values['utCivilite']}',
              {$values['utMailsPourris']}
            )";
  $res = mysqli_query($bd, $sql) or vpac_bd_erreur($bd, $sql);
  mysqli_close($bd);

  // Mémoriser dans la variable de session
  $_SESSION['utPseudo'] = $values['utPseudo'];
  $_SESSION['utStatut'] = 0;

  header('Location: ../index.php');
}

/**
 * Affichage du formulaire
 * 
 * @param array $values Tableau avec les valeurs du formulaire
 * @param array $errors Tableau avec les erreurs de saisie
 */
function vpacl_print_form($values, $errors) {
  echo '<section>',
    '<h2>Formulaire d\'inscription</h2>',
      '<p>Pour vous inscrire, remplissez le formulaire ci-dessous.</p>';

      vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de votre inscription :');

      // Date de naissance
      $naissance_j = $naissance_m = 1;
      $naissance_a = 2020;
      if($values['utDateNaissance'] != 0) {
        $naissance_j = (int)substr($values['utDateNaissance'], 6);
        $naissance_m = (int)substr($values['utDateNaissance'], 4, 2);
        $naissance_a = (int)substr($values['utDateNaissance'], 0, 4);
      }
      // Civilité
      $civilite = array(false, false);
      if(!empty($values['utCivilite'])) {
        if($values['utCivilite'] == 'h') {
          $civilite[0] = true;
        } else {
          $civilite[1] = true;
        }
      }

      echo '<form action="inscription.php" method="post">',
        '<table>';
          vpac_print_table_form_input('Choisissez un pseudo', 'pseudo', htmlentities($values['utPseudo']), true, 'text', '4 caractères minimum');
          vpac_print_table_form_radio('Votre civilité', 'radSexe', array('Mr', 'Mme'), $civilite, array('Monsieur', 'Madame'), true);
          vpac_print_table_form_input('Votre nom', 'nom', htmlentities($values['utNom']), true);
          vpac_print_table_form_input('Votre prénom', 'prenom', htmlentities($values['utPrenom']), true);
          vpac_print_table_form_date('Votre date de naissance', 'naissance', 2020, 1920, $naissance_j, $naissance_m, $naissance_a);
          vpac_print_table_form_input('Votre email', 'email', htmlentities($values['utEmail']), true);
          vpac_print_table_form_input('Choisissez un mot de passe', 'passe1', '', true, 'password');
          vpac_print_table_form_input('Répétez le  mot de passe', 'passe2', '', true, 'password');
          vpac_print_table_form_checkbox(array('cbCGU', 'cbSpam'), array(1, 1), array(0, $values['utMailsPourris']), array('J\'ai lu et j\'accepte les conditions générales d\'utilisation', 'J\'accepte de recevoir des tonnes de mails pourris'), array(true, false));
          vpac_print_table_form_button(array('submit', 'reset'), array('S\'inscrire', 'Réinitialiser'), array('btnInscription', ''));
        echo '</table>',
      '</form>',
    '</section>';
}

/**
 * Vérifier la validité d'une châine de type nom/prénom
 * 
 * @param array  $errors     Tableau contenant toutes les erreurs
 * @param string $value      Valeur à vérifier
 * @param string $field_name Nom du champ
 * @param int    $length     Longueur maximum du champ
 */
function vpacl_check_name(&$errors, $value, $field_name, $length) {
  if(empty($value)) {
    $errors[] = "Le $field_name ne peut pas être vide.";
  } else if(!preg_match("/^[a-zéèêëàâäùçôö\-]{1,50}$/i", $value)) {
    $errors[] = "Le $field_name ne peut pas contenir plus de $length caractères.";
  }
}

/**
 * Vérifier la validité d'un champ de type select
 * 
 * @param int    $value  Valeur à vérifier
 * @param int    $min    Valeur minimum possible
 * @param int    $max    Valeur maximum possible
 */
function vpacl_check_select($value, $min, $max) {
  if($value < $min || $value > $max) {
    header('Location: ../index.php');
    exit();
  }
}
?>