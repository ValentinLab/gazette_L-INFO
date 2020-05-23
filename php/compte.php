<?php
ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_gazette.php';

// Vérifier les droits de l'utilisateur
vpac_check_authentication(ALL_U);

// ----------------------------------------
// Traitement des formulaires
// ----------------------------------------

$status_datas = $status_passwd = $status_custom = $status_writer = $status_pic = array();
if(isset($_POST['btnCustom'])) {
  $status_custom = vpacl_form_processing_customization();
} else if(isset($_POST['btnDatas'])) {
  $status_datas = vpacl_form_processing_datas();
} else if(isset($_POST['btnPassword'])) {
  $status_passwd = vpacl_form_processing_password();
} else if(isset($_POST['btnWriter'])) {
  $status_writer = vpacl_form_processing_writer();
} else if(isset($_POST['btnPic'])) {
  $status_pic = vpacl_form_processing_writer_pic();
}

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Compte');
vpac_get_nav();
vpac_get_header('Mon compte');

// Page
$datas = vpacl_get_user_datas();
vpacl_print_datas($datas, $status_datas);
vpacl_print_password($status_passwd);
vpacl_print_customization($status_custom);
if($_SESSION['user']['writer']) {
  vpacl_print_writer($datas, $status_writer);
  vpacl_print_writer_pic($status_pic);
}

// Footer
vpac_get_footer();
ob_end_flush();

// ----------------------------------------
// Fonctions
// ----------------------------------------

// ----- Récupération des données

function vpacl_get_user_datas() {
  $db = vpac_db_connect();
  $current_user = mysqli_real_escape_string($db, $_SESSION['user']['pseudo']);
  $sql = "SELECT utCivilite, utNom, utPrenom, utDateNaissance, utEmail, utMailsPourris, reBio, reCategorie, reFonction
          FROM utilisateur LEFT OUTER JOIN redacteur ON utPseudo = rePseudo
          WHERE utPseudo='$current_user'";
  $res = mysqli_query($db, $sql) or vpac_db_error($db, $sql);

  $datas = mysqli_fetch_assoc($res);

  mysqli_free_result($res);
  mysqli_close($db);

  return $datas;
}

// ----- Affichage des sections

function vpacl_print_datas($user_datas, $status) {
  echo '<section>',
    '<h2>Informations personnelles</h2>';
    vpac_print_form_status($status, 'Les erreurs suivantes ont été relevées');
    echo '<p>Vous pouvez modifier les informations suivantes.</p>',
    '<form action="compte.php" method="post">',
        '<table>';

          $civilite = ($user_datas['utCivilite'] == 'h') ? 1 : 2;
          vpac_print_table_form_radio('Votre civilité', 'radSexe', array(1, 2), $civilite, array('Monsieur', 'Madame'),
            false);

          vpac_print_table_form_input('Votre nom', 'nom', vpac_protect_data($user_datas['utNom']), TRUE);
          vpac_print_table_form_input('Votre prénom', 'prenom', vpac_protect_data($user_datas['utPrenom']), TRUE);

          $current_year = date('Y');
          $birth = array(
            'year' => (int)substr($user_datas['utDateNaissance'], 0, 4),
            'month' => (int)substr($user_datas['utDateNaissance'], 4, 2),
            'day' => (int)substr($user_datas['utDateNaissance'], 6)
          );
          vpac_print_table_form_date('Votre date de naissance', 'naissance', $current_year, $current_year - DIFF_ANNEE,
            $birth['day'], $birth['month'], $birth['year']);

          vpac_print_table_form_input('Votre email', 'email', vpac_protect_data($user_datas['utEmail']), TRUE);

          vpac_print_table_form_checkbox(array('cbSpam'), array(1), array((bool)$user_datas['utMailsPourris']),
            array('J\'accepte de recevoir des tonnes de mails pourris'), array(FALSE));

          vpac_print_table_form_button(array('submit', 'reset'), array('Enregistrer', 'Réinitialiser'),
            array('btnDatas', ''));
        echo '</table>',
      '</form>',
  '</section>';
}

function vpacl_print_password($status) {
  echo '<section>',
    '<h2>Authentification</h2>';
    vpac_print_form_status($status);
    echo '<p>Vous pouvez modifier votre mot de passe ci-dessous.</p>',
    '<form action="compte.php" method="post">',
      '<table>';
        vpac_print_table_form_input('Choisissez un mot de passe', 'passe1', '', true, 'password');
        vpac_print_table_form_input('Répétez le mot de passe', 'passe2', '', true, 'password');
        vpac_print_table_form_button(array('submit'), array('Enregistrer'), array('btnPassword'));
      echo '</table>',
    '</form>',
  '</section>';
}

function vpacl_print_customization($status) {
  // Obtenir le thème choisie par l'utilisateur
  $theme = ($_SESSION['user']['theme'] == CUSTOM_LIGHT) ? 'Thème claire' : 'Thème sombre';

  // Affichage du formulaire
  echo '<section>',
    '<h2>Personnalisation du style</h2>';
    vpac_print_form_status($status);
    echo '<p>Vous pouvez modifier l\'apparence du  site internet.</p>',
    '<figure>',
      vpacl_print_preview('light');
      vpacl_print_preview('dark');
    echo '</figure>',
    '<form action="compte.php" method="post">',
      '<table>';
      vpac_print_table_form_select('Thème du site', 'theme', array('Thème clair', 'Thème sombre'), $theme, array());
      vpac_print_table_form_button(array('submit'), array('Enregistrer'), array('btnCustom'));
      echo '</table>',
    '</form>',
  '</section>';
}

function vpacl_print_writer($writer_datas, $status) {
  $db = vpac_db_connect();
  $sql = "SELECT catLibelle
          FROM categorie";
  $res = mysqli_query($db, $sql) or vpac_db_error($db, $sql);
  $categories = array();
  $categories[] = '-- choisir une catégorie --';
  while($data = mysqli_fetch_assoc($res)) {
    $categories[] = $data['catLibelle'];
  }
  mysqli_free_result($res);
  mysqli_close($db);

  // Informations
  $bio = vpac_protect_data($writer_datas['reBio']);
  $categorie = empty($writer_datas['reCategorie']) ? $categories[0] : $categories[$writer_datas['reCategorie']];
  $fonction = vpac_protect_data($writer_datas['reFonction']);
  $is_registrated = vpac_encrypt_url((int)!empty($bio));

  echo '<section>',
    '<h2>Informations de rédacteur</h2>';
    vpac_print_form_status($status, 'Les erreurs suivantes ont été relevées');
    echo '<p>Vous pouvez modifier vos informations affichées sur la page de <a href="redaction.php">la rédaction</a>.</p>',
    '<form action="compte.php" method="post">',
      '<table>';
        vpac_print_table_form_textarea('Biographie', 'bio', 10, 50, TRUE, $bio);
        vpac_print_table_form_select('Catégorie', 'categorie', $categories, $categorie);
        vpac_print_table_form_input('Fonction', 'fonction', $fonction, FALSE);
        vpac_print_table_form_invisible_input('registrated', $is_registrated);
        vpac_print_table_form_button(array('submit', 'reset'), array('Enregistrer', 'Réinitialiser'),
            array('btnWriter', ''));
      echo '</table>',
    '</form>',
  '</section>';
}

function vpacl_print_writer_pic($status) {
  $imagePath = file_exists("../upload/{$_SESSION['user']['pseudo']}.jpg") ? "../upload/{$_SESSION['user']['pseudo']}.jpg" : '../images/anonyme.jpg';

  echo '<section>',
    '<h2>Photo de profile</h2>';
    vpac_print_form_status($status);
    echo '<p>Vous pouvez modifier votre photo de rédacteur.</p>',
    '<form action="compte.php" method="post" enctype="multipart/form-data">',
      '<table>';
        vpac_print_table_form_image(
          'pidRedacteur',
          '../images/anonyme.jpg',
          "../upload/{$_SESSION['user']['pseudo']}.jpg",
          "photo de profile de " . htmlentities($_SESSION['user']['pseudo']),
          150,
          200
        );
        vpac_print_table_form_button(array('submit'), array('Enregistrer'), array('btnPic'));
      echo '</table>',
    '</form>',
  '</section>';
}

// ----- Affichage autre

function vpacl_print_preview($theme) {
  echo '<div class="preview" id="prev-', $theme,'">',
    '<nav></nav>',
    '<header></header>',
    '<section></section>',
    '<section></section>',
  '</div>';
}

// ----- Traitement des formulaires

function vpacl_form_processing_datas() {
  // Vérification des clés présentes dans $_POST
  if(
    !vpac_parametres_controle('post',
      array('nom', 'prenom', 'naissance_j', 'naissance_m', 'naissance_a', 'email', 'btnDatas'),
      array('radSexe', 'cbSpam')
    )
  ) {
    vpac_session_exit();
  }

  // Erreurs de traitement
  $errors = array();

  // Vérification de la civilité
  if(!vpac_is_number($_POST['radSexe'])) {
    vpac_session_exit();
  }
  vpac_check_between($_POST['radSexe'], 1, 2);
  if($_POST['radSexe'] == 1) {
    $civilite = 'h';
  } else {
    $civilite = 'f';
  }

  // Vérification du nom et du prénom
  $nom = trim($_POST['nom']);
  vpac_check_name($errors, $nom, 'prenom', LMAX_PRENOM);
  $prenom = trim($_POST['prenom']);
  vpac_check_name($errors, $prenom, 'nom', LMAX_NOM);

  // Vérification du jour/mois/année de naissance
  $current_year = date('Y');
  $day = (int)$_POST['naissance_j'];
  $month = (int)$_POST['naissance_m'];
  $year = (int)$_POST['naissance_a'];
  vpac_check_between($day, 1, 31);
  vpac_check_between($month, 1, 12);
  vpac_check_between($year, $current_year - DIFF_ANNEE, $current_year);
  // Vérification de l'âge
  if(!checkdate($month, $day, $year)) {
    $errors[] = 'La date de naissance n\'est pas valide.';
  } elseif(mktime(0, 0, 0, $month, $day, $year+18) > time()) {
    $errors[] = 'Vous devez avoir au moins 18 ans pour vous inscrire.';
  }
  // Format de la date
  $month = ($month < 10) ? "0{$month}" : $month;
  $day = ($day < 10) ? "0{$day}" : $day;
  $naissance = "{$year}{$month}{$day}";

  // Vérification de l'adresse mail
  $email = trim($_POST['email']);
  $email_len = mb_strlen($email, 'UTF-8');
  if($email_len == 0) {
    $errors[] = 'L\'adresse mail ne peut pas être vide.';
  } elseif($email_len > LMAX_EMAIL) {
    $errors[] = 'L\'adresse mail ne peut pas contenir plus de 255 caractères.';
  }
  // Vérification de la validité de l'adresse
  if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    $errors[] = 'L\'adresse mail n\'est pas valide.';
  }

  // Vérification des spams
  if(isset($_POST['cbSpam'])) {
    if($_POST['cbSpam'] != 1) {
      vpac_session_exit();
    } else {
      $mails_pourris = 1;
    }
  } else {
    $mails_pourris = 0;
  }

  if(!empty($errors)) {
    return array('stderr' => $errors);
  }

  // Modification des données de l'utilisateur
  $db = vpac_db_connect();
  $user = mysqli_real_escape_string($db, $_SESSION['user']['pseudo']);
  $nom = mysqli_real_escape_string($db, $nom);
  $prenom = mysqli_real_escape_string($db, $prenom);
  $email = mysqli_real_escape_string($db, $email);
  $sql = "UPDATE utilisateur
          SET utNom='{$nom}', utPrenom='{$prenom}', utEmail='{$email}', utDateNaissance={$naissance},
            utCivilite='{$civilite}', utMailsPourris={$mails_pourris}
          WHERE utPseudo='{$user}'";
  mysqli_query($db, $sql) or vpac_db_error($db, $sql);

  return array('stdout' => 'Vos données ont été modifiées');
}

function vpacl_form_processing_password() {
  // Vérification des clés présentes dans $_POST
  if(!vpac_parametres_controle('post', array('passe1', 'passe2', 'btnPassword'))) {
    vpac_session_exit();
  }

  // Erreurs de traitement
  $errors = array();

  // Vérification du mot de passe 1
  $passe = $_POST['passe1'];
  $passe_len = mb_strlen($passe, 'UTF-8');
  if($passe_len == 0) {
    $errors[] = 'Le mot de passe ne peut pas être vide.';
  } elseif($passe_len > 255) {
    $errors[] = "Le mot de passe ne peut pas contenir plus de 255 caractères. Actuellement $passe_len";
  }

  // Vérification du mot de passe 2
  if($passe !== $_POST['passe2']) {
    $errors[] = 'Les mots de passe doivent être identiques.';
  }

  if(!empty($errors)) {
    return array('stderr' => $errors);
  }

  // Requête SQL
  $db = vpac_db_connect();
  $user = mysqli_real_escape_string($db, $_SESSION['user']['pseudo']);
  $passe = password_hash($passe, PASSWORD_DEFAULT);
  $sql = "UPDATE utilisateur
          SET utPasse='$passe'
          WHERE utPseudo='$user'";
  mysqli_query($db, $sql) or vpac_db_error($db, $sql);

  return array('stdout' => 'Le mot de passe a été changé avec succès');
}

function vpacl_form_processing_customization() {
  // Vérifier les clés de $_POST
  if(!vpac_parametres_controle('post', array('theme', 'btnCustom'))) {
    vpac_session_exit();
  }

  // Vérifier le thème
  if(!vpac_is_number($_POST['theme']) || $_POST['theme'] < CUSTOM_LIGHT || $_POST['theme'] > CUSTOM_DARK) {
    vpac_session_exit();
  }

  // Modification du thème
  $_SESSION['user']['theme'] = (int)$_POST['theme'];
  setcookie("theme_user_{$_SESSION['user']['pseudo']}", $_POST['theme'], time()+60*60*24*30, "/");

  $status['stdout'] = 'Changement de thème effectué';
  return $status;
}

function vpacl_form_processing_writer() {
  // Vérifier les clés de $_POST
  if(!vpac_parametres_controle('post', array('bio', 'categorie', 'fonction', 'registrated', 'btnWriter'))) {
    vpac_session_exit();
  }

  // Erreurs de traitement
  $errors = array();

  // Vérifier la biographie
  $bio = trim($_POST['bio']);
  if(empty($bio)) {
    $errors[] = 'Votre biographie ne peut pas être vide';
  }
  if(!preg_match('/\A\[p\]/', $bio)) {
    $bio = "[p]{$bio}";
  }
  if(!preg_match('/\[\/p\]\Z/', $bio)) {
    $bio = "{$bio}[/p]";
  }

  // Vérifier la catégorie
  if(!vpac_is_number($_POST['categorie'])) {
    vpac_session_exit();
  }
  $categorie = (int)$_POST['categorie'];
  if($categorie == 0) {
    $errors[] = 'Vous devez choisir une catégorie';
  } else {
    vpac_check_between($categorie, 1, 3);
  }

  // Vérifier la fonction
  $fonction = trim($_POST['fonction']);
  if(!empty($fonction)) {
    if(!preg_match('/[a-zA-Zéèêëôöîïà\- ]/', $fonction)) {
      $errors[] = 'La fonction ne doit contenir que des caractères alphabétiques.';
    }
    if(strlen($fonction) > 100) {
      // strlen plutôt que mb_strlen car les caractères accentués sont stockés sur plus d'un octet
      $errors[] = 'La chaîne ne doit pas dépasser 100 caractères.';
    }
  }

  // Vérifier si l'utilisateur est déjà présent dans la db
  $is_registrated = vpac_decrypt_url(urldecode($_POST['registrated']));
  if($is_registrated === FALSE) {
    vpac_session_exit();
  }

  // Afficher les erreurs
  if(!empty($errors)) {
    return array('stderr' => $errors);
  }

  // Requête de modification
  $db = vpac_db_connect();
  $user_e = mysqli_real_escape_string($db, $_SESSION['user']['pseudo']);
  $bio_e = mysqli_real_escape_string($db, $bio);
  $fonction_e = mysqli_real_escape_string($db, $fonction);
  if($is_registrated) {
    $sql = "UPDATE redacteur
            SET reBio = '$bio_e', reCategorie = $categorie, reFonction = '$fonction_e'
            WHERE rePseudo = '$user_e'";
  } else {
    $sql = "INSERT INTO redacteur
            VALUES ('$user_e', '$bio_e', $categorie, '$fonction_e')";
  }
  mysqli_query($db, $sql) or vpac_db_error($db, $sql);
  mysqli_close($db);

  return array('stdout' => 'It\'s okay.');
}

function vpacl_form_processing_writer_pic() {
  // Erreurs du formulaire
  $errors = array();

  $file = $_FILES['picRedacteur'];

  // Vérifier s'il y a des erreurs
  switch($file['error']) {
    case 1:
    case 2:
      $errors[] = 'Le ficher "' . $file['name'] . '" est trop volumineux';
      break;
    case 3:
      $errors[] = 'Erreur de transfert du fichier "' . $file['name'] . '"';
      break;
    case 4:
      $errors[] = 'Le fichier "' . $file['name'] . '" est introuvable';
      break;
  }

  // Vérifier le type de l'image
  if($file['type'] != 'image/jpeg') {
    $errors[] = 'L\'image doit être de type jpeg.';
  }

  if(!empty($errors)) {
    return array('stderr' => $errors);
  }

  // Placer le fichier
  if(!@is_uploaded_file($file['tmp_name'])) {
    $errors[] = 'Erreur interne de transfert 1';
    return array('stderr' => $errors);
  }

  $path = realpath('..') . '/upload/' . $_SESSION['user']['pseudo'] . '.' . pathinfo($file['name'])['extension'];
  if(!@move_uploaded_file($file['tmp_name'], $path)) {
    $errors[] = 'Erreur interne de transfert 2';
    return array('stderr' => $errors);
  }

  return array('stdout' => 'Votre image de profile a été téléchargé avec succès');
}
?>