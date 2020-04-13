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
// Page
// ----------------------------------------

// Header
vpac_get_head('L\'actu');
vpac_get_nav();
vpac_get_header('L\'actu');

// Article et commentaires
vpacl_print_article();

// Footer
vpac_get_footer();

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Afficher un article et ses commentaires
 */
function vpacl_print_article() {
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
  vpacl_print_comments($res);

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
  vpac_protect_array($data);

  // Image
  $image = (file_exists("../upload/{$data['arID']}.jpg")) ? "<img src=\"../upload/{$data['arID']}.jpg\" alt=\"{$data['arTitre']}\">" : '';

  // Auteur
  $authorName = vpac_mb_ucfirst(mb_substr($data['utPrenom'], 0, 1, 'UTF-8')) . '. ' . vpac_mb_ucfirst($data['utNom']);
  $author = (isset($data['rePseudo']) && ($data['utStatut'] == 1 || $data['utStatut'] == 3) ? "<a href='../php/redaction.php#{$data['utPseudo']}'>$authorName</a>" : $authorName);

  // BBCode
  $data['arTexte'] = vpacl_parse_bbcode($data['arTexte']);

  // Affichage
  echo '<article>',
          '<h3>', $data['arTitre'],'</h3>',
            $image,
            $data['arTexte'],
            '<footer>Par ', $author, '. Publié le ', vpac_time_to_string($data['arDatePublication']);
  if(isset($data['arDateModification'])) {
    echo ', modifié le ', vpac_time_to_string($data['arDateModification']);
  }
   echo '</footer></article>';
}

/**
 * Afficher les commentaires d'un article
 * 
 * @param object $res Résultat d'une requête sql permettant d'obtenir les commentaires
 */
function vpacl_print_comments($res) {
  echo '<section><h2>Réactions</h2>';

  // Vérifier s'il y a des commentaires
  $data = mysqli_fetch_assoc($res);
  if(isset($data['coID'])) {
    mysqli_data_seek($res, 0);
    echo '<ul>';
    while($comment = mysqli_fetch_assoc($res)) {
      vpac_protect_array($comment);
      $comment['coTexte'] = vpacl_parse_bbcode($comment['coTexte']);

      echo '<li>',
            '<p>Commentaire de <strong>', $comment['coAuteur'],'</strong>, ', vpac_time_to_string($comment['coDate']),'</p>',
            '<blockquote>', $comment['coTexte'],'</blockquote>',
          '</li>';
    }
    echo '</ul>';
  } else {
    echo '<p>Il n\'y a pas de commentaires à cet article. </p>';
  }

  echo '<p><a href="../php/connexion.php">Connectez-vous</a> ou <a href="./inscription.php">inscrivez-vous</a> pour pouvoir commenter cet article !</p></section>';
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
 * Transformation d'une date dans le format
 * dd MMM YYYY à HHhMM
 * 
 * @param int $time Heure à transformer
 */
function vpac_time_to_string($date) {
  $min = substr($date, -2);
  $hour = (int)substr($date, -4, 2);
  $day = (int)substr($date, -6, 2);
  $month = (int)substr($date, -8, 2);
  $year = substr($date, 0, -8);

  $months = vpac_get_months();

  return $day . ' ' . mb_strtolower($months[$month], 'UTF-8') . ' ' . $year . ' à ' . $hour . 'h' . $min;
}
?>