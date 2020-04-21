<?php
ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_gazette.php';

// ----------------------------------------
// Vérification de l'URL
// ----------------------------------------

if(!vpac_parametres_controle('get', array(), array('id'))) {
  header('Location: ../index.php');
  exit;
}

// ----------------------------------------
// Traitement des formulaires
// ----------------------------------------

$errors = array();
if(isset($_POST['btnAjouterCommentaire'])) {
  $errors = vpacl_form_processing_add();
} else if(isset($_POST['btnSupprimerCommentaire'])) {
  $errors = vpacl_form_processing_remove();
}

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('L\'actu');
vpac_get_nav();
vpac_get_header('L\'actu');

// Article et commentaires
vpacl_print_article($errors);

// Footer
vpac_get_footer();
ob_end_flush();

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Afficher un article et ses commentaires
 */
function vpacl_print_article($errors) {
  // Vérifier le paramètre id dans l'URL
  if(!isset($_GET['id'])) {
    vpac_print_error('Identifiant d\'article non fourni.');
    return;
  }
  $id = (int)vpac_decrypt_url($_GET['id']);
  if(!vpac_is_number($id) || $id <= 0) {
    vpac_print_error('Identifiant d\'article invalide.');
    return;
  }

  // Requête pour obtenir l'article et les commentaires
  $db = vpac_db_connect();
  $sql = "SELECT * FROM ((article INNER JOIN utilisateur ON arAuteur = utPseudo) LEFT OUTER JOIN redacteur ON utPseudo = rePseudo) LEFT OUTER JOIN commentaire ON arID = coArticle WHERE arID = $id ORDER BY coDate DESC, coID DESC";
  $res = mysqli_query($db, $sql) or vpac_bd_error($db, $sql);

  // Vérifier le nombre de résultats
  if(mysqli_num_rows($res) == 0) {
    vpac_print_error('Identifiant d\'article non reconnu.');
    mysqli_free_result($res);
    mysqli_close($db);
    return;
  }

  // Affichage de l'édition
  vpacl_print_edit($res);
  // Afficher l'article et les commentaires
  vpacl_print_article_part($res);
  vpacl_print_comments($res, $errors);

  mysqli_free_result($res);
  mysqli_close($db);
}

/**
 * Afficher un bandeau proposant l'édition de l'article
 * 
 * @param object $res Résultat d'une requête sql permettant d'obtenir l'article
 */
function vpacl_print_edit($res) {
  // Vérifier que l'utilisateur est connectés
  if(!isset($_SESSION['user'])) {
    return;
  }

  // Récupérer les résultats depuis la base de données
  $data = mysqli_fetch_assoc($res);
  if($data == null) {
    return;
  }

  // Vérifier que l'utilisateur connecté est l'auteur de l'article
  if($data['utPseudo'] != $_SESSION['user']['pseudo'] || !$_SESSION['user']['writer']) {
    return;
  }

  echo '<section id="banner">',
    '<p>Vous êtes l\'auteur de cet article, <a href="edition.php">cliquez ici pour le modifier ou le supprimer</a></p>',
  '</section>';
}

/**
 * Afficher un article
 * 
 * @param object $res Résultat d'une requête sql permettant d'obtenir l'article
 */
function vpacl_print_article_part($res) {
  mysqli_data_seek($res, 0);
  $data = mysqli_fetch_assoc($res);
  $data = vpac_protect_data($data);

  // Image
  $image = (file_exists("../upload/{$data['arID']}.jpg")) ? "<img src=\"../upload/{$data['arID']}.jpg\" alt=\"{$data['arTitre']}\">" : '';

  // Auteur
  $authorName = vpac_mb_ucfirst(mb_substr($data['utPrenom'], 0, 1, 'UTF-8')) . '. ' . vpac_mb_ucfirst($data['utNom']);
  $author = (isset($data['rePseudo']) && ($data['utStatut'] == 1 || $data['utStatut'] == 3) ? "<a href='../php/redaction.php#{$data['utPseudo']}'>$authorName</a>" : $authorName);

  // BBCode
  vpac_parse_bbcode($data['arTexte']);
  vpac_parse_bbcode_unicode($data['arTexte']);

  // Affichage
  echo '<article>',
          '<h3>', $data['arTitre'],'</h3>',
            $image,
            $data['arTexte'],
            '<footer>Par ', $author, '. Publié le ', vpacl_time_to_string($data['arDatePublication']);
  if(isset($data['arDateModification'])) {
    echo ', modifié le ', vpacl_time_to_string($data['arDateModification']);
  }
   echo '</footer></article>';
}

/**
 * Afficher les commentaires d'un article
 * 
 * @param object $res Résultat d'une requête sql permettant d'obtenir les commentaires
 */
function vpacl_print_comments($res, $errors) {
  echo '<section id="commentaires"><h2>Réactions</h2>';

  // Affichage des erreurs
  vpac_print_form_errors($errors, '', true);

  // Vérifier s'il y a des commentaires
  $data = mysqli_fetch_assoc($res);
  if(isset($data['coID'])) {
    mysqli_data_seek($res, 0);
    echo '<ul>';
    while($comment = mysqli_fetch_assoc($res)) {
      //Protéger et parser le texte
      $comment = vpac_protect_data($comment);
      vpac_parse_bbcode_unicode($comment['coTexte']);

      //Vérifier si la personne connectée est l'auteur du message
      $my_comment_id = (isset($_SESSION['user']) && $_SESSION['user']['pseudo'] == $comment['coAuteur']) ? ' id="comment-mine"' : '';

      // Afficher le commentaire
      echo '<li', $my_comment_id, '>',
            '<p>Commentaire de <strong>', $comment['coAuteur'],'</strong>, ', vpacl_time_to_string($comment['coDate']), '</p>';
            if(!empty($my_comment_id)) {
              echo '<form action="" method="post">';
                vpac_print_invisible_input('commentaire_id', $comment['coID']);
                vpac_print_input_btn('submit', 'Supprimer le commentaire', 'btnSupprimerCommentaire');
              echo '</form>';
            }
            echo '<blockquote>', $comment['coTexte'],'</blockquote>',
          '</li>';
    }
    echo '</ul>';
  } else {
    echo '<p>Il n\'y a pas de commentaires à cet article. </p>';
  }

  if(!isset($_SESSION['user'])) {
    // Connexion ou inscription
    echo '<p><a href="../php/connexion.php">Connectez-vous</a> ou <a href="./inscription.php">inscrivez-vous</a> pour pouvoir commenter cet article !</p></section>';
  } else {
    // Affichage du formulaire
    echo '<form action="" method="post">',
          '<fieldset>',
            '<legend>Ajoutez un commentaire</legend>',
            '<table id="form_uncentered">';
              vpac_print_table_form_textarea('commentaire', 15, 70, true);
              vpac_print_table_form_button(array('submit'), array('Publier ce commentaire'), array('btnAjouterCommentaire'));
            echo '</table>',
          '</fieldset>',
        '</form>';
  }
}


/**
 * Traitement du formulaire pour l'ajout d'un commentaire
 * 
 * @return array Tableau contenant les erreurs de saisie
 */
function vpacl_form_processing_add() {
  // Vérification des clés présentes dans $_POST
  if(!vpac_parametres_controle('post', array('commentaire', 'btnAjouterCommentaire'))) {
    header('Location: ../index.php');
    exit();
  }

  // Vérification de l'id de l'article
  if(!isset($_GET['id'])) {
    header('Location: ../index.php');
    exit();
  }
  $article = (int)vpac_decrypt_url($_GET['id']);
  if(!vpac_is_number($article) || $article <= 0) {
    header('Location: ../index.php');
    exit();
  }

  // Vérification du commentaire
  $commentaire = trim($_POST['commentaire']);
  $commentaire_len = mb_strlen($commentaire, 'UTF-8');
  if($commentaire_len == 0) {
    $errors[] = 'Le commentaire ne peut pas être vide.';
  } else if($commentaire_len > LMAX_COMMENTAIRE) {
    $errors[] = 'Le commentaire ne doit pas faire plus de ' . LMAX_COMMENTAIRE . ' caractères.';
  }

  if(!empty($errors)) {
    return $errors;
  }

  // Requête SQL
  $db = vpac_db_connect();
    $auteur = mysqli_real_escape_string($db, $_SESSION['user']['pseudo']);
    $commentaire = mysqli_real_escape_string($db, $commentaire);
    $date = date('YmdHi');
  $sql = "INSERT INTO commentaire (coAuteur, coTexte, coDate, coArticle)
          VALUES ('{$auteur}', '{$commentaire}', {$date}, {$article})";
  mysqli_query($db, $sql) or vpac_bd_error($db, $sql);
  mysqli_close($db);

  $article = vpac_encrypt_url($article);
  header("Location: article.php?id={$article}#commentaires");
  exit();
}

/**
 * Traitement du formulaire pour la suppression d'un commentaire
 * 
 * @return array Tableau contenant les erreurs de saisie
 */
function vpacl_form_processing_remove() {
  // Vérifier les clés présentes dans $_POST
  if(!vpac_parametres_controle('post', array('commentaire_id', 'btnSupprimerCommentaire'))) {
    header('Location: ../index.php');
    exit();
  }

  // Vérification de l'id de l'article
  if(!isset($_GET['id'])) {
    header('Location: ../index.php');
    exit();
  }
  $article = (int)vpac_decrypt_url($_GET['id']);
  if(!vpac_is_number($article) || $article <= 0) {
    header('Location: ../index.php');
    exit();
  }

  // Vérification de l'id du commentaire
  $id = $_POST['commentaire_id'];
  if(!vpac_is_number($id) || $id <= 0) {
    header('Location: ../index.php');
    exit();
  }

  // Requête SQL
  $db = vpac_db_connect();
    $auteur = mysqli_real_escape_string($db, $_SESSION['user']['pseudo']);
  $sql = "DELETE FROM commentaire
          WHERE coAuteur = '{$auteur}'
            AND coID = $id";
  mysqli_query($db, $sql) or vpac_bd_error($db, $sql);
  mysqli_close($db);

  $article = vpac_encrypt_url($article);
  header("Location: article.php?id={$article}#commentaires");
  exit();
}

/**
 * Transformation d'une date dans le format
 * dd MMM YYYY à HHhMM
 * 
 * @param int $time Heure à transformer
 */
function vpacl_time_to_string($date) {
  $min = substr($date, -2);
  $hour = (int)substr($date, -4, 2);
  $day = (int)substr($date, -6, 2);
  $month = (int)substr($date, -8, 2);
  $year = substr($date, 0, -8);

  $months = vpac_get_months();

  return $day . ' ' . mb_strtolower($months[$month], 'UTF-8') . ' ' . $year . ' à ' . $hour . 'h' . $min;
}
?>