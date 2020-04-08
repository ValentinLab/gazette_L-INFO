<?php
ob_start();
session_start();

require_once 'php/bibli_generale.php';
require_once 'php/bibli_gazette.php';

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('', '.');
vpac_get_nav('.');
vpac_get_header('Le site de désinformation n°1 des étudiants en Licence Info', '.');

// Trois sections d'articles
$articles = vpacl_extract_articles();
vpacl_print_articles_section('À la Une', $articles[0]);
vpacl_print_articles_section('L\'info brûlante', $articles[1]);
vpacl_print_articles_section('Les incontournables', $articles[2]);

// Horoscope
vpacl_print_horoscope();

// Footer
vpac_get_footer();

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Obtenir l'ensemble des articles à afficher et les trier selon la zone d'affichage
 * 
 * - zone 1 : les derniers articles publiés
 * - zone 2 : les articles les plus commentés
 * - zone 3 : des articles choisi au hasard
 * 
 * @return array Articles des trois zones
 */
function vpacl_extract_articles() {
  // Connexion à la bd et requête
  $bd = vpac_bd_connecter();
  $sql = '(SELECT arID, arTitre, 1 AS type
          FROM article
          ORDER BY arDatePublication DESC
          LIMIT 0, 3)
          UNION
          (SELECT arID, arTitre, 2 AS type
          FROM article
          LEFT OUTER JOIN commentaire ON coArticle = arID
          GROUP BY arID
          ORDER BY COUNT(coArticle) DESC, rand()
          LIMIT 0, 3)
          UNION
          (SELECT arID, arTitre, 3 AS type
          FROM article
          ORDER BY rand()
          LIMIT 0, 9)';
  $res = mysqli_query($bd,$sql) or vpac_bd_erreur($bd,$sql);

  // Extraction des résultats
  $result = array();
  $arIDs = array();
  $ar_number = 0;
  while($data = mysqli_fetch_assoc($res)) {
    switch($data['type']) {
      case 1:
        $result[0][] = $data;
        $arIDs[] = $data['arID'];
        break;
      case 2:
        $result[1][] = $data;
        $arIDs[] = $data['arID'];
        break;
      case 3:
        if(!in_array($data['arID'], $arIDs) && $ar_number++ < 3) {
          $result[2][] = $data;
        }
        break;
    }
  }

  mysqli_free_result($res);
  mysqli_close($bd);

  return $result;
}

/**
 * Afficher une section avec plusieurs articles
 * 
 * @param string $title    Titre de la section
 * @param array  $articles Articles à afficher
 */
function vpacl_print_articles_section($title, $articles) {
  echo '<section class="centre">',
          '<h2>',$title, '</h2>';
            foreach($articles as $article) {
              vpacl_print_article($article);
            }
  echo '</section>';
}

/**
 * Afficher un article : sa vignette et son titre
 * 
 * @param array $article Informations de l'article
 */
function vpacl_print_article($article) {
  vpac_protect_array($article);
  echo '<a href="./php/article.php?id=', $article['arID'], '">',
          '<img src="', vpac_get_article_image($article['arID'], '.'), '" alt="', $article['arTitre'] ,'"><br>',
          $article['arTitre'],
       '</a>';
}

/**
 * Afficher l'horoscope
 */
function vpacl_print_horoscope() {
  echo '<section>',
          '<h2>Horoscope de la semaine</h2>',
          '<p>Vous l\'attendiez tous, voici l\'horoscope du semestre pair de l\'année 2019-2020. Sans surprise, il n\'est pas terrible...</p>',
          '<table id="horoscope">',
            '<tr>',
              '<td>Signe</td>',
              '<td>Date</td>',
              '<td>Votre horoscope</td>',
            '</tr>',
            '<tr>',
              '<td>&#9800; Bélier</td>',
              '<td>du 21 mars<br>au 19 avril</td>',
              '<td rowspan="4">',
                '<p>Après des vacances bien méritées, l\'année reprend sur les chapeaux de roues. Tous les signes sont concernés. </p>',
                '<p>Jupiter s\'aligne avec Saturne, péremptoirement à Venus, et nous promet un semestre qui ne sera pas de tout repos. Février sera le mois le plus tranquille puisqu\'il ne comporte que 29 jours.</p>',
                '<p>Les fins de mois seront douloureuses pour les natifs du 2e décan au moment où tomberont les tant-attendus résultats du module d\'<em>Algorithmique et Structures de Données</em> du semestre 3.</p>',
              '</td>',
            '</tr>',
            '<tr>',
              '<td>&#9801; Taureau</td>',
              '<td>du 20 avril<br>au 20 mai</td>',
            '</tr>',
            '<tr>',
              '<td>...</td>',
              '<td>...</td>',
            '</tr>',
            '<tr>',
              '<td>&#9811; Poisson</td>',
              '<td>du 20 février<br>au 20 mars</td>',
            '</tr>',
          '</table>',
          '<p>Malgré cela, notre équipe d\'astrologues de choc vous souhaite à tous un bon semestre, et bon courage pour le module de <em>Système et Programmation Système</em>.</p>',
        '</section>';
}
?>
