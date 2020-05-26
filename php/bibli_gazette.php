<?php
// ----------------------------------------
// Constantes
// ----------------------------------------

// Base de données
define('BD_SERVER','localhost');
define('BD_NAME','claudel_gazette');
define('BD_USER','claudel_u');
define('BD_PASS','claudel_p');

// URL
define('CIPHER', 'aes-128-gcm');
define('KEY', '++/lMMTtpH23xuuxS/+Jlw==');
define('TAG_LEN', 16);

// Droits des utilisateurs
define('ALL_U', 0b00);
define('WRITER_U', 0b01);
define('ADMINISTRATOR_U', 0b10);

// Thèmes
define('CUSTOM_LIGHT', 0);
define('CUSTOM_DARK', 1);

//Inscription
define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 20);
define('LMAX_PRENOM', 60);
define('LMAX_NOM', 50);
define('LMAX_EMAIL', 255);
define('DIFF_ANNEE', 100);

// Commentaires
define('LMAX_COMMENTAIRE', 256);

// ----------------------------------------
// Obtenir le header de la page HTML
// ----------------------------------------

/**
 * Afficher <head>
 * 
 * @param string $title Ttre de la page
 * @param string $path  Chemin des fichiers ('.' ou '..')
 */
function vpac_get_head($title, $path = '..') {
  $page_title = (!empty($title)) ? "$title | La gazette de L-INFO" : 'La gazette de L-INFO';
  $theme = (isset($_SESSION['user']) && $_SESSION['user']['theme'] == CUSTOM_DARK) ? ' id="dark"' : '';

  echo '<!doctype html>',
        '<html lang="fr">',
        '<head>',
            '<meta charset="UTF-8">',
            '<title>', $page_title, '</title>',
            '<link rel="stylesheet" type="text/css" href="', $path,'/styles/gazette.css">',
            '<script src="', $path, '/js/a-little-bit-of.js"></script>',
        '</head>',
        '<body', $theme, '>';
}

/**
 * Afficher <nav>
 * 
 * @param string $path Chemin des fichiers ('.' ou '..')
 */
function vpac_get_nav($path = '..') {
  echo '<nav>',
          '<ul>',
              '<li><a href="', $path, '/">Accueil</a></li>',
              '<li><a href="', $path, '/php/actus.php">Toute l\'actu</a></li>',
              '<li><a href="', $path, '/php/recherche.php">Recherche</a></li>',
              '<li><a href="', $path, '/php/redaction.php">La rédac\'</a></li>',
              '<li>';
              if(isset($_SESSION['user'])) {
                echo '<a href="#">', htmlentities($_SESSION['user']['pseudo']), '</a>',
                        '<ul>',
                          '<li><a href="', $path, '/php/compte.php">Mon profil</a></li>',
                          ($_SESSION['user']['writer']) ? 
                            "<li><a href=\"{$path}/php/nouveau.php\">Nouvel article</a></li>" : '',
                          ($_SESSION['user']['administrator']) ? 
                            "<li><a href=\"{$path}/php/administration.php\">Administration</a></li>" : '',
                          '<li><a href="', $path, '/php/deconnexion.php">Se déconnecter</a></li>',
                        '</ul>';
              } else {
                echo '<a href="', $path, '/php/connexion.php">Se connecter</a>';
              }
              echo '</li>',
          '</ul>',
      '</nav>';
}

/**
 * Afficher <header>
 * 
 * @param string $path Chemin des fichiers ('.' ou '..')
 */
function vpac_get_header($title, $path = '..') {
  echo '<header>',
          '<img src="', $path, '/images/titre.png" alt="La gazette de L-INFO" width="780" height="83">',
          '<h1>', $title, '</h1>',
        '</header><main>';
}

// ----------------------------------------
// Obtenir le footer de la page HTML
// ----------------------------------------

/**
 * Afficher le footer de la page html
 */
function vpac_get_footer() {
  echo '</main><footer>&copy; Licence Informatique - Janvier 2020 - Tous droits réservés</footer></body></html>';
}

// ----------------------------------------
// Gestion du contenu de la page
// ----------------------------------------

/**
 * Obtenir l'image d'un article
 * Si aucune image n'est présente, on obtient l'image none.jpg
 * 
 * @param int    $id   ID de l'article
 * @param string $path Chemin des fichiers ('.' ou '..')
 * @return string Chemin vers l'image
 */
function vpac_get_article_image($id, $path = '..') {
  $image = "{$path}/upload/{$id}.jpg";
  if(!file_exists($image)) {
    $image = "{$path}/images/none.jpg";
  }

  return $image;
}

/**
 * Affichage d'une section d'erreur
 * 
 * @param string $array Message d'erreur
 */
function vpac_print_error($content) {
  echo '<section>',
         '<h2>Oups, il y a une erreur ...</h2>',
         '<p>La page que vous avez demandée a terminé son exécution avec le message d\'erreur suivant :',
         '<blockquote>', $content, '</blockquote>',
       '</section>';
}

function vpac_print_bbcode_dialog($all = TRUE) {
  // Bouton d'affichage
  echo '<input type="checkbox" class="dialog_btn" id="dialog_bbcode"><label for="dialog_bbcode">Comment utiliser le BBCode ?</label>';

  // Boîte de dialogue
  echo '<div class="dialog">',
    '<header>',
      '<h2><span>BBCode</span> : cheatsheet</h2>',
      '<label for="dialog_bbcode">&#x2715;</label>',
    '</header>',
    '<main>';
      if($all == TRUE) {
        echo '<h3>Mise en forme du texte</h3>',
        '<ul>',
          '<li><span>[p]contenu[/p]</span> : paragraphe</li>',
          '<li><span>[gras]contenu[/gras]</span> : contenu en gras</li>',
          '<li><span>[it]contenu[/it]</span> : contenu en italique</li>',
          '<li><span>[citation]contenu[/citation]</span> : citation</li>',
          '<li><span>[liste]contenu[/liste]</span> : liste</li>',
          '<li><span>[item]contenu[/item]</span> : item dans une liste</li>',
          '<li><span>[a:url]contenu[/a]</span> : lien pointant vers <span>url</span></li>',
          '<li><span>[br]</span> : saut de ligne</li>',
          '<li><span>[youtube:w:h:url]</span> : vidéo youtube de taille <span>w</span> et <span>h</span></li>',
          '<li><span>[youtube:w:h:url legende]</span> : vidéo youtube avec légende</li>',
        '</ul>';
      }
      echo '<h3>Ajout de codes unicode</h3>',
        '<ul>',
          '<li><span>[#NNN]</span> : code unicode décimal</li>',
          '<li><span>[#xNNN]</span> : code unicode héxadécimal</li>',
        '</ul>',
    '</main>',
  '</div>';
}

/**
 * Couper une date pour ne retenir que l'année et le mois
 *
 * @param string $date Date au format YYYYMMDDhhmm
 * @return string Date au format YYYYDD
 */
function vpac_get_month_and_year($date){
    return substr($date,0,6);
}

/**
 * Obtenir un tableau contenant les articles correspondant au numéro de la page, classés par mois
 *
 * @param array $data Tableau contenant tous les articles de la page
 * @return array Tableau contenant les 4 articles de la page, indexé par mois
 */
function vpac_classer_articles_par_mois($data){
    //$page = (isset($_GET['page'])) ? vpac_decrypt_url($_GET['page']) : 1;;

    $return=array();
    for($i=0;$i<count($data);++$i){
        if(!array_key_exists(vpac_get_month_and_year($data[$i]['arDatePublication']),$return)){
            $return[vpac_get_month_and_year($data[$i]['arDatePublication'])][0]=$data[$i];
        }else{
            array_push($return[vpac_get_month_and_year($data[$i]['arDatePublication'])],$data[$i]);
        }
    }
    return $return;
}

/**
 * Transformer une date dans le format
 * Month Year
 * 
 * @param int $date Date à transformer
 */
function vpac_month_and_year_to_string($date) {
    $month = (int)substr($date, -8, 2);
    $year = substr($date, 0, -8);
  
    $months = vpac_get_months();
  
    return mb_strtolower($months[$month], 'UTF-8') . ' ' . $year;
}

/**
 * Afficher le contenu principal de la page, 4 articles classés par mois
 *
 * @param array $data_by_month Tableau indexé par mois, contenant les 4 articles
 */
function vpac_print_articles($data_by_month){
    foreach($data_by_month as &$value){
        echo'<section>',
            '<h2>',vpac_month_and_year_to_string($value[0]['arDatePublication']),'</h2>';
            foreach($value as &$article) { 
                vpac_print_article($article);
            }
        echo'</section>';
    } 
}

/**
 * Afficher un article
 * 
 * @param array $article Tableau contenant les informations de l'article en question
 */
function vpac_print_article($article){
    $image = (file_exists("../upload/{$article['arID']}.jpg")) ? "<img src=\"../upload/{$article['arID']}.jpg\" alt=\"{$article['arTitre']}\">" : "<img src=\"../images/none.jpg\" alt=\"{$article['arTitre']}\">";
    echo'<article class="resume">',
            $image,
            '<h3>',$article['arTitre'],'</h3>',
            '<p>',
            $article['arResume'],
            '</p>',
            '<footer><a href="../php/article.php?id=',vpac_encrypt_url($article['arID']),'">Lire l\'article</a></footer>',
        '</article>';
}
?>