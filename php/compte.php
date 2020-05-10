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

$status_datas = $status_passwd = $status_custom = array();
if(isset($_POST['btnCustom'])) {
  $status_custom = vpacl_form_processing_customization();
} else if(isset($_POST['btnDatas'])) {
  $status_datas = array();
} else if (isset($_POST['btnPassword'])) {
  $status_passwd = array();
}

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Compte');
vpac_get_nav();
vpac_get_header('Mon compte');

// Page
vpacl_print_datas();
vpacl_print_password();
vpacl_print_customization($status_custom);

// Footer
vpac_get_footer();
ob_end_flush();

// ----------------------------------------
// Fonctions
// ----------------------------------------

function vpacl_print_datas() {
  echo '<section>',
    '<h2>Informations personnelles</h2>',
    '<p>Vous pouvez modifier les informations suivantes.</p>',
    '<form action="compte.php" method="post">',
        '<table>';
          vpac_print_table_form_radio('Votre civilité', 'radSexe', array(1, 2), 1, array('Monsieur', 'Madame'), false);
          vpac_print_table_form_input('Votre nom', 'nom', '', true);
          vpac_print_table_form_input('Votre prénom', 'prenom', '', true);
          vpac_print_table_form_date('Votre date de naissance', 'naissance', 2020, 2020 - DIFF_ANNEE, 11, 06, 2000);
          vpac_print_table_form_input('Votre email', 'email', '', true);
          vpac_print_table_form_checkbox(array('cbSpam'), array(1), array(FALSE), array('J\'accepte de recevoir des tonnes de mails pourris'), array(FALSE));
          vpac_print_table_form_button(array('submit', 'reset'), array('Enregistrer', 'Réinitialiser'), array('btnDatas', ''));
        echo '</table>',
      '</form>',
  '</section>';
}

function vpacl_print_password() {
  echo '<section>',
    '<h2>Authentification</h2>',
    '<p>Vous pouvez modifier votre mot de passe ci-dessous.</p>',
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
      vpac_print_table_form_select('Thème du site', 'theme', array('Thème clair', 'Thème sombre'), $theme);
      vpac_print_table_form_button(array('submit'), array('Enregistrer'), array('btnCustom'));
      echo '</table>',
    '</form>',
  '</section>';
}

function vpacl_print_preview($theme) {
  echo '<div class="preview" id="prev-', $theme,'">',
    '<nav></nav>',
    '<header></header>',
    '<section></section>',
    '<section></section>',
  '</div>';
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
?>