<?php
ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_gazette.php';

// Vérifier les droits de l'utilisateur
vpac_check_authentication(ADMINISTRATOR_U);

// ----------------------------------------
// Traitement du formulaire
// ----------------------------------------

$db = null;
$status = array();
if(isset($_POST['btnChangeRights'])) {
  $status = vpacl_form_processing($db);
}

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Admin');
vpac_get_nav();
vpac_get_header('Administration');

// Administration
vpacl_print_user_datas($db, $status);
vpacl_print_users($db);

// Footer
vpac_get_footer();
ob_end_flush();

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Afficher le tableau contenant tous les utilisateurs
 */
function vpacl_print_users(&$db) {
  // Requête SQL
  if($db == null) {
    $db = vpac_db_connect();
  }
  $sql = "SELECT utPseudo, utNom, utPrenom, utStatut, count(DISTINCT coID) AS NbCo, count(DISTINCT arID) as NbAr
          FROM (utilisateur LEFT OUTER JOIN commentaire ON utPseudo = coAuteur)
                LEFT OUTER JOIN article ON utPseudo = arAuteur
          GROUP BY utPseudo";
  $res = mysqli_query($db, $sql) or vpac_bd_error($db, $sql);
  mysqli_close($db);

  // Affichage du tableau
  echo '<section>',
    '<h2>Table des utilisateurs</h2>',
    '<table id="data_table">',
      '<thead>',
        '<tr>',
          '<th>Pseudo</th>',
          '<th>Nom</th>',
          '<th>Droits</th>',
          '<th>Commentaires</th>',
          '<th>Articles</th>',
          '<th>Commentaires/Articles</th>',
          '<th>Actions</th>',
        '</tr>',
      '</thead>',
      '<tbody>';
        while($data = mysqli_fetch_assoc($res)) {
          vpacl_print_user_tr($data);
        }
      echo '</tbody>',
    '</table>',
  '</section>';

  mysqli_free_result($res);
}

/**
 * Afficher une section avec l'ensemble des informations sur l'utilisateur
 */
function vpacl_print_user_datas(&$db, $status) {
  if(!isset($_GET['user'])) {
    return;
  }
  $current_user = vpac_decrypt_url($_GET['user']);
  if($current_user === FALSE) {
    vpac_session_exit();
  }

  // Requête sql
  if($db == null) {
    $db = vpac_db_connect();
  }
  $user_e = mysqli_real_escape_string($db, $current_user);
  $sql = "SELECT utNom, utPrenom, utEmail, utCivilite, utDateNaissance, utMailsPourris, utStatut, arID, arTitre
          FROM utilisateur LEFT OUTER JOIN article ON utPseudo = arAuteur
          WHERE utPseudo = '$user_e'
          ORDER BY arID";
  $res = mysqli_query($db, $sql) or vpac_bd_error($db, $sql);

  // Données
  $data = mysqli_fetch_assoc($res);
  $name = vpac_protect_data(vpac_mb_ucfirst($data['utPrenom']) . ' ' . vpac_mb_ucfirst($data['utNom']));
  $email = vpac_protect_data($data['utEmail']);
  $gender = ($data['utCivilite'] == 'h') ? 'homme' : 'femme';
  $birthdate = substr($data['utDateNaissance'], 6) . '/' . substr($data['utDateNaissance'], 4, 2) . '/' . 
    substr($data['utDateNaissance'], 0, 4);
  $spam = ($data['utMailsPourris'] == 1) ? 'oui' : 'non';

  // Affichage
  echo '<section>',
    '<h2>Utilisateur <em>', $current_user,'</em></h2>';

    // Affichage du status de traitement
    vpac_print_form_status($status, '', true);

    echo '<h3>Informations personnelles</h3>',
    '<p><strong>Nom</strong> : ', $name, '</p>',
    '<p><strong>Email</strong> : ', $email, '</p>',
    '<p><strong>Civilité</strong> : ', $gender, '</p>',
    '<p><strong>Date de naissance</strong> : ', $birthdate, '</p>',
    '<p><strong>Spam</strong> : ', $spam,'</p>',

    '<h3>Modification des droits</h3>',
    '<form action="administration.php?user=', urlencode($_GET['user']), '", method="post" id="admin_rights">';
      $rights = array('aucun droit', 'rédacteur', 'administrateur', 'rédacteur et administrateur');
      echo '<label><strong>Droits</strong> : ', vpac_print_list('rights', $rights, $rights[$data['utStatut']]), 
        '</label>';
      vpac_print_input_btn('submit', 'Modifier les droits', 'btnChangeRights');
    echo '</form>',

    '<h3>Articles de l\'utilisateur</h3>';
    // Affichage des Articles
    if(empty($data['arTitre'])) {
      echo '<p>L\'utilisateur n\'a écrit aucun article.</p>';
    } else {
      echo '<input type="checkbox" id="user_articles"><label for="user_articles">Cliquez ici pour</label>',
      '<ul>';
        do{
          echo '<li><a href="article.php?id=', vpac_encrypt_url($data['arID']), '" >', $data['arTitre'], '</a></li>';
        } while($data = mysqli_fetch_assoc($res));
      echo '</ul>';
    }
    echo '</section>';
}

/**
 * Afficher un utilisateur dans une ligne de tableau
 */
function vpacl_print_user_tr($data) {
  // Données
  $print_datas = array(
    'pseudo' => vpac_protect_data($data['utPseudo']),
    'name' => vpac_protect_data(vpac_mb_ucfirst($data['utPrenom']) . ' ' . vpac_mb_ucfirst($data['utNom'])),
    'rights' => vpac_rights_to_string($data['utStatut']),
    'comments' => $data['NbCo'],
    'articles' => $data['NbAr'],
    'average' => 0
  );

  // Affichage
  echo '<tr>';
    foreach($print_datas as $user_d) {
      echo '<td>', $user_d, '</td>';
    }
    echo '<td><a href="administration.php?user=', vpac_encrypt_url($print_datas['pseudo']),'">Modifier</a></td>';
  echo '</tr>';
}

/**
 * Traitement du formulaire de changement de droits d'un utilisateur
 */
function vpacl_form_processing(&$db) {
  // Vérification de $_GET
  if(!isset($_GET['user'])) {
    header('Location: ../index.php');
    exit;
  }
  $current_user = vpac_decrypt_url($_GET['user']);
  if($current_user === FALSE) {
    vpac_session_exit();
  }

  // Vérification des clés
  if(!vpac_parametres_controle('post', array('rights', 'btnChangeRights'))) {
    vpac_session_exit();
  }

  // Vérification des droits
  if($_POST['rights'] < 0 || $_POST['rights'] > 3 && vpac_is_number($_POST['rights'])) {
    header('Location: ../index.php');
    exit;
  }

  // Modification des droits de l'utilisateur
  $db = vpac_db_connect();
    $current_user = mysqli_real_escape_string($db, $current_user);
    $new_rights = (int)$_POST['rights'];
  $sql = "UPDATE Utilisateur
          SET utStatut={$new_rights}
          WHERE utPseudo='{$current_user}'";
  mysqli_query($db, $sql) or vpac_bd_error($db, $sql);

  $status['stdout'] = 'Les droits de l\'utilisateur ont été modifiés.';
  return $status;
}
?>
