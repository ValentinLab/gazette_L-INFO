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
// Traitement du formulaire
// ----------------------------------------

$errors = array();
if(isset($_POST['btnCommentaire'])) {
  $errors = vpacl_form_processing();
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
    vpacl_print_error('Identifiant d\'article non fourni.');
    return;
  }
  if(!vpac_is_number($_GET['id']) || $_GET['id'] <= 0) {
    vpacl_print_error('Identifiant d\'article invalide.');
    return;
  }

  $id = (int)$_GET['id'];

  // Requête pour obtenir l'article et les commentaires
  $bd = vpac_bd_connecter();
  $sql = "SELECT * FROM ((article INNER JOIN utilisateur ON arAuteur = utPseudo) LEFT OUTER JOIN redacteur ON utPseudo = rePseudo) LEFT OUTER JOIN commentaire ON arID = coArticle WHERE arID = $id ORDER BY coDate DESC, coID DESC";
  $res = mysqli_query($bd, $sql) or vpac_bd_erreur($bd, $sql);

  // Vérifier le nombre de résultats
  if(mysqli_num_rows($res) == 0) {
    vpacl_print_error('Identifiant d\'article non reconnu.');
    mysqli_free_result($res);
    mysqli_close($bd);
    return;
  }

  // Afficher l'article et les commentaires
  vpacl_print_article_part($res);
  vpacl_print_comments($res, $errors);

  mysqli_free_result($res);
  mysqli_close($bd);
}

/**
 * Afficher un article
 * 
 * @param object $res Résultat d'une requête sql permettant d'obtenir l'article
 */
function vpacl_print_article_part($res) {
  $data = mysqli_fetch_assoc($res);
  $data = vpac_protect_data($data);

  // Image
  $image = (file_exists("../upload/{$data['arID']}.jpg")) ? "<img src=\"../upload/{$data['arID']}.jpg\" alt=\"{$data['arTitre']}\">" : '';

  // Auteur
  $authorName = vpac_mb_ucfirst(mb_substr($data['utPrenom'], 0, 1, 'UTF-8')) . '. ' . vpac_mb_ucfirst($data['utNom']);
  $author = (isset($data['rePseudo']) && ($data['utStatut'] == 1 || $data['utStatut'] == 3) ? "<a href='../php/redaction.php#{$data['utPseudo']}'>$authorName</a>" : $authorName);

  // BBCode
  vpacl_parse_bbcode($data['arTexte']);
  vpacl_parse_bbcode_unicode($data['arTexte']);

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
  echo '<section><h2>Réactions</h2>';

  // Vérifier s'il y a des commentaires
  $data = mysqli_fetch_assoc($res);
  if(isset($data['coID'])) {
    mysqli_data_seek($res, 0);
    echo '<ul>';
    while($comment = mysqli_fetch_assoc($res)) {
      //Protéger et parser le texte
      $comment = vpac_protect_data($comment);
      vpacl_parse_bbcode_unicode($comment['coTexte']);

      //Vérifier si la personne connectée est l'auteur du message
      $my_comment_id = (isset($_SESSION['user']) && $_SESSION['user']['pseudo'] == $comment['coAuteur']) ? ' id="comment-mine"' : '';

      // Afficher le commentaire
      echo '<li', $my_comment_id, '>',
            '<p>Commentaire de <strong>', $comment['coAuteur'],'</strong>, ', vpacl_time_to_string($comment['coDate']), '</p>';
            if(!empty($my_comment_id)) {
              echo '<form action="" method="post">';
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
    // Affichage des erreurs
    vpac_print_form_errors($errors, '', true);

    // Affichage du formulaire
    echo '<form action="" method="post">',
          '<fieldset>',
            '<legend>Ajoutez un commentaire</legend>',
            '<table id="form-uncentered">';
              vpac_print_table_form_textarea('commentaire', 15, 70, true);
              vpac_print_table_form_button(array('submit'), array('Publier ce commentaire'), array('btnCommentaire'));
            echo '</table>',
          '</fieldset>',
        '</form>';
  }
}

/**
 * Affichage d'une section d'erreur
 * 
 * @param string $array Message d'erreur
 */
function vpacl_print_error($content) {
  echo '<section>',
         '<h2>Oups, il y a une erreur ...</h2>',
         '<p>La page que vous avez demandée a terminé son exécution avec le message d\'erreur suivant :',
         '<blockquote>', $content, '</blockquote>',
       '</section>';
}

/**
 * Transformation du BBCode en HTML
 * 
 * @param string $text Texte à transformer
 */
function vpacl_parse_bbcode(&$text) {
  // balise [p] -> <p>
  $text = preg_replace('/\[(\/?)p\]/', '<\1p>', $text);
  // balise [gras] -> <strong>
  $text = preg_replace('/\[(\/?)gras\]/', '<\1strong>', $text);
  // balise [it] -> <em>
  $text = preg_replace('/\[(\/?)it\]/', '<\1em>', $text);
  // balise [citation] -> <blockquote>
  $text = preg_replace('/\[(\/?)citation\]/', '<\1blockquote>', $text);
  // balise [liste] -> <ul>
  $text = preg_replace('/\[(\/?)liste\]/', '<\1ul>', $text);
  // balise [item] -> <li>
  $text = preg_replace('/\[(\/?)item\]/', '<\1li>', $text);
  // balise [a:url] -> <a>
  $text = preg_replace('/\[a:([^]]+)\]/', '<a href="\1">', $text);
  $text = preg_replace('/\[\/a\]/', '</a>', $text);

  // balise [br] -> <br>
  $text = preg_replace('/\[br\]/', '<br>', $text);
  // balise [youtube:w:h:url] -> <iframe width='w' height='h' src='url' allowfullscreen></iframe>
  $text = preg_replace('/\[youtube:([^:]+):([^:]+):([^(\]| )]+)\]/', '<iframe width="\1" height="\1" src="\3" allowfullscreen></iframe>', $text);
  // balise [youtube:w:h:url] -> <figure><iframe width="w" height="h" src="url" allowfullscreen></iframe><figcaption>f<figcaption></figure>
  $text = preg_replace('/\[youtube:([^:]+):([^:]+):([^ ]+) ([^]]+)\]/', '<figure><iframe width="\1" height="\2" src="\3" allowfullscreen></iframe><figcaption>\4<figcaption></figure>', $text);

  return $text;
}

/**
 * Transformation du BBCode en HTML, uniquement pour les éléments unicode
 * 
 * @param string $text Texte à transformer
 */
function vpacl_parse_bbcode_unicode(&$text) {
  // balise [#NNN] -> &#NNN ou [#xNNN] -> &#xNNN
  $text = preg_replace('/\[#([^]]+)\]/', '&#\1', $text);

  return $text;
}

/**
 * Traitement du formulaire
 * 
 * @return array Tableau à remplir avec les erreurs de saisie
 */
function vpacl_form_processing() {
  // Vérifier les clés présentes dans $_POST
  if(!vpac_parametres_controle('post', array('commentaire', 'btnCommentaire'))) {
    header('Location: ../index.php');
    exit();
  }

  // Vérification de l'ID
  if(!isset($_GET['id']) || !vpac_is_number($_GET['id']) || $_GET['id'] <= 0) {
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
  $bd = vpac_bd_connecter();
    $auteur = mysqli_real_escape_string($bd, $_SESSION['user']['pseudo']);
    $commentaire = mysqli_real_escape_string($bd, $commentaire);
    $date = date('YmdHi');
    $article = (int)$_GET['id'];
  $sql = "INSERT INTO commentaire (coAuteur, coTexte, coDate, coArticle)
          VALUES ('{$auteur}', '{$commentaire}', {$date}, {$article})";
  mysqli_query($bd, $sql) or vpac_bd_erreur($bd, $sql);
  mysqli_close($bd);

  return array();
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