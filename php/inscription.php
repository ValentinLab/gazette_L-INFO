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
if(isset($_POST['btnInscription'])) {
  $errors = vpacl_form_processing();
}

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Inscription');
vpac_get_nav();
vpac_get_header('Inscription');

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
    '<h2>Formulaire d\'inscription</h2>',
      '<p>Pour vous inscrire, remplissez le formulaire ci-dessous.</p>';

      vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de votre inscription :');

      // Année actuelle
      $current_year = date('Y');

      // Valeurs du formulaire
      $pseudo = $nom = $prenom = $email = '';
      $naissance_j = $naissance_m = 1;
      $naissance_a = $current_year;
      $civilite = 0;
      $mails_pourris = true;
      if(isset($_POST['btnInscription'])) {
        $pseudo = vpac_protect_data($_POST['pseudo']);
        $nom = vpac_protect_data($_POST['nom']);
        $prenom = vpac_protect_data($_POST['prenom']);
        $email = vpac_protect_data($_POST['email']);
        $naissance_j = (int)$_POST['naissance_j'];
        $naissance_m = (int)$_POST['naissance_m'];
        $naissance_a = (int)$_POST['naissance_a'];
        $civilite = (isset($_POST['radSexe'])) ? $_POST['radSexe'] : 0;
        $mails_pourris = isset($_POST['cbSpam']);
      }

      echo '<form action="inscription.php" method="post">',
        '<table>';
          vpac_print_table_form_input('Choisissez un pseudo', 'pseudo', vpac_protect_data($pseudo), true, 'text', LMIN_PSEUDO . ' caractères minimum');
          vpac_print_table_form_radio('Votre civilité', 'radSexe', array(1, 2), $civilite, array('Monsieur', 'Madame'), true);
          vpac_print_table_form_input('Votre nom', 'nom', vpac_protect_data($nom), true);
          vpac_print_table_form_input('Votre prénom', 'prenom', vpac_protect_data($prenom), true);
          vpac_print_table_form_date('Votre date de naissance', 'naissance', $current_year, $current_year - DIFF_ANNEE, $naissance_j, $naissance_m, $naissance_a);
          vpac_print_table_form_input('Votre email', 'email', vpac_protect_data($email), true);
          vpac_print_table_form_input('Choisissez un mot de passe', 'passe1', '', true, 'password');
          vpac_print_table_form_input('Répétez le  mot de passe', 'passe2', '', true, 'password');
          vpac_print_table_form_checkbox(array('cbCGU', 'cbSpam'), array(1, 1), array(0, $mails_pourris), array('J\'ai lu et j\'accepte les conditions générales d\'utilisation', 'J\'accepte de recevoir des tonnes de mails pourris'), array(true, false));
          vpac_print_table_form_button(array('submit', 'reset'), array('S\'inscrire', 'Réinitialiser'), array('btnInscription', ''));
        echo '</table>',
      '</form>',
    '</section>';
}

/**
 * Traitement du formulaire
 * 
 * @return array Tableau à remplir avec les erreurs de saisie
 */
function vpacl_form_processing() {
  // Vérifier les clés présentes dans $_POST
  if(!vpac_parametres_controle(
    'post', 
    array('pseudo', 'nom', 'prenom', 'naissance_j', 'naissance_m', 'naissance_a', 'email', 'passe1', 'passe2', 'btnInscription'), 
    array('radSexe', 'cbCGU', 'cbSpam'))
  ){
    vpac_session_exit();
  }

  // Valeurs à récuperer dans le formulaire
  $pseudo = $civilite = $nom = $prenom = $email = $passe = '';
  $naissance = '';
  $mails_pourris = true;

  // Vérification du pseudo
  $pseudo = trim($_POST['pseudo']);
  if(!preg_match('/^[a-z0-9]{' . LMIN_PSEUDO . ',' . LMAX_PSEUDO . '}$/', $pseudo)) {
    $errors[] = 'Le pseudo doit contenir entre 4 et 20 caractères minuscules (sans accent) ou chiffres.';
  }

  // Vérification de la civilité
  if(!isset($_POST['radSexe'])) {
    $errors[] = 'Vous devez choisir une civilité.';
  } else if(!vpac_is_number($_POST['radSexe'])) {
    vpac_session_exit();
  }
  vpacl_check_between($_POST['radSexe'], 1, 2);
  switch($_POST['radSexe']) {
    case 1:
      $civilite = 'h';
      break;
    case 2;
      $civilite = 'f';
      break;
  }

  // Vérification du nom et du prénom
  $nom = trim($_POST['nom']);
  vpacl_check_name($errors, $nom, 'prenom', LMAX_PRENOM);
  $prenom = trim($_POST['prenom']);
  vpacl_check_name($errors, $prenom, 'nom', LMAX_NOM);

  // Vérification du jour/mois/année de naissance
  $current_year = date('Y');
  $day = (int)$_POST['naissance_j'];
  $month = (int)$_POST['naissance_m'];
  $year = (int)$_POST['naissance_a'];
  vpacl_check_between($day, 1, 31);
  vpacl_check_between($month, 1, 12);
  vpacl_check_between($year, $current_year - DIFF_ANNEE, $current_year);
  // Vérification de l'âge
  if(!checkdate($month, $day, $year)) {
    $errors[] = 'La date de naissance n\'est pas valide.';
  } elseif(mktime(0, 0, 0, $month, $day, $year+18) > time()) {
    $errors[] = 'Vous devez avoir au moins 18 ans pour vous inscrire.';
  }
  $naissance = "{$year}{$month}{$day}";

  // Vérification de l'adresse mail
  $email = trim($_POST['email']);
  $email_len = mb_strlen($email, 'UTF-8');
  if($email_len == 0) {
    $errors[] = 'L\'adresse mail ne peut pas être vide.';
  } elseif($email_len > LMAX_EMAIL) {
    $errors[] = 'L\'adresse mail ne peut pas contenir plus de 255 caractères.';
  }
  // Vérification de la validité de l'adresse mail
  if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    $errors[] = 'L\'adresse mail n\'est pas valide.';
  }

  // Vérification du mot de passe 1
  $passe = $_POST['passe1'];
  $passe_len = mb_strlen($passe, 'UTF-8');
  if($passe_len == 0) {
    $errors[] = 'Le mot de passe ne peut pas être vide.';
  } elseif($passe_len > 255) {
    $errors[] = 'Le mot de passe ne peut pas contenir plus de 255 caractères.';
  }

  // Vérification du mot de passe 2
  if($passe !== $_POST['passe2']) {
    $errors[] = 'Les mots de passe doivent être identiques.';
  }

  // Vérification des spams
  if(isset($_POST['cbSpam'])) {
    if($_POST['cbSpam'] != 1) {
      vpac_session_exit();
    }
  } else {
    $mails_pourris = false;
  }

  // Vérification des CGU
  if(!isset($_POST['cbCGU'])) {
    $errors[] = 'Vous devez accepter les CGU.';
  } else if($_POST['cbCGU'] != 1) {
    vpac_session_exit();
  }

  if(!empty($errors)) {
    return $errors;
  }

  // Requête à la bd
  $db = vpac_db_connect();
  $pseudo_e = mysqli_real_escape_string($db, $pseudo);
  $email = mysqli_real_escape_string($db, $email);
  $sql = "SELECT utPseudo, utEmail
          FROM utilisateur
          WHERE utPseudo='{$pseudo}'
             OR utEmail='{$email}'";
  $res = mysqli_query($db, $sql) or vpac_bd_error($db, $sql);
  if(mysqli_num_rows($res) > 0) {
    $data = mysqli_fetch_assoc($res);
    if($data['utPseudo'] == $pseudo) {
      $errors[] = 'Ce pseudo existe déjà.';
    } else {
      $errors[] = 'Un compte est déjà lié à cet email.';
    }
    mysqli_close($db);
  }
  mysqli_free_result($res);

  if(!empty($errors)) {
    exit();
  }

  // Inscription d'un nouvel utilisateur
  $nom = mysqli_real_escape_string($db, $nom);
  $prenom = mysqli_real_escape_string($db, $prenom);
  $passe = password_hash($passe, PASSWORD_DEFAULT);
  $sql = "INSERT INTO utilisateur
          VALUES ('{$pseudo_e}', '{$nom}', '{$prenom}', '{$email}', '{$passe}', {$naissance}, 0, '{$civilite}', {$mails_pourris})";
  mysqli_query($db, $sql) or vpac_bd_error($db, $sql);
  mysqli_close($db);

  // Mémoriser dans la variable de session
  vpac_connect_user($pseudo, 0);

  header('Location: ../index.php');
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
 * Vérifier la validité d'un champ numérique
 * 
 * @param int    $value  Valeur à vérifier
 * @param int    $min    Valeur minimum possible
 * @param int    $max    Valeur maximum possible
 */
function vpacl_check_between($value, $min, $max) {
  if($value < $min || $value > $max) {
    vpac_session_exit();
  }
}
?>