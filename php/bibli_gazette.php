<?php
// ----------------------------------------
// Constantes
// ----------------------------------------

// Base de données
define('BD_SERVER','localhost');
define('BD_NAME','gazette_bd');
define('BD_USER','perignon_u');
define('BD_PASS','perignon_p');

// URL
define('CIPHER', 'aes-128-gcm');
define('KEY', '++/lMMTtpH23xuuxS/+Jlw==');
define('TAG_LEN', 16);

//Inscription
define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 20);
define('LMAX_PRENOM', 60);
define('LMAX_NOM', 50);
define('LMAX_EMAIL', 255);
define('DIFF_ANNEE', 100);

// Commentaire
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

  echo '<!doctype html>',
        '<html lang="fr">',
        '<head>',
            '<meta charset="UTF-8">',
            '<title>', $page_title, '</title>',
            '<link rel="stylesheet" type="text/css" href="', $path,'/styles/gazette.css">',
        '</head>',
        '<body>';
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
                          ($_SESSION['user']['redacteur']) ? "<li><a href=\"{$path}/php/nouveau.php\">Nouvel article</a></li>" : '',
                          ($_SESSION['user']['administrateur']) ? "<li><a href=\"{$path}/php/admin.php\">Administration</a></li>" : '',
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
  echo '</main><footer>&copy; Licence Informatique - Janvier 2020 - Tous droits réservés</footer>';
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
?>