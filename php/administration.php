<?php
ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_gazette.php';

// Vérifier les droits de l'utilisateur
vpac_check_authentication(ADMINISTRATOR_U);

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Admin');
vpac_get_nav();
vpac_get_header('Administration');

// Administration
vpacl_print_user_datas();
vpacl_print_users();

// Footer
vpac_get_footer();
ob_end_flush();

// ----------------------------------------
// Fonctions
// ----------------------------------------

function vpacl_print_users() {
  // Requête SQL
  $db = vpac_db_connect();
  $sql = "SELECT utPseudo, utNom, utPrenom, utStatut, count(coID) AS NbCo, count(arID) as NbAr
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

function vpacl_print_user_datas() {
  if(!isset($_GET['user'])) {
    return;
  }

  // Affichage
  echo '<section>',
    '<h2>Utilisateur <em>freddd</em></h2>',

    '<h3>Informations personnelles</h3>',
    '<p><strong>Nom</strong> : Frédéric Dadeau</p>',
    '<p><strong>Email</strong> : blabla@email.com</p>',
    '<p><strong>Civilité</strong> : homme</p>',
    '<p><strong>Date de naissance</strong> : 11/11/11</p>',
    '<p><strong>Spam</strong> : oui</p>',

    '<h3>Modification des droits</h3>',
    '<form action="administration.php?user=', $_GET['user'], '", method="post" id="admin_rights">';
      $rights = array('aucun droit', 'rédacteur', 'administrateur', 'rédacteur et administrateur');
      echo '<label><strong>Droits</strong> : ', vpac_print_list('right', $rights, $rights[0]), '</label>';
      vpac_print_input_btn('submit', 'Modifier les droits', 'btnChangeRights');
    echo '</form>',

    '<h3>Articles de l\'utilisateur</h3>',
    '<input type="checkbox" id="user_articles"><label for="user_articles">Cliquez ici pour</label>',
    '<ul>',
      '<li><a href="../index.php">Un mouchard dans un corrigé de Langages du Web</a></li>',
      '<li><a href="../index.php">Un mouchard dans un corrigé de Langages du Web</a></li>',
    '</ul>',
  '</section>';
}
?>