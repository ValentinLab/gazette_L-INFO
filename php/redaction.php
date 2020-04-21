<?php
ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_gazette.php';

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('La rédac\'');
vpac_get_nav();
vpac_get_header('Rédaction');

// Membres de la rédaction
vpacl_print_first_section();
vpacl_print_people_sections();
vpacl_print_last_section();

// Footer
vpac_get_footer();

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Afficher la première section de la page
 */
function vpacl_print_first_section() {
  echo '<section>',
    '<h2>Le mot de la rédaction</h2>',
    '<p>Passionnés par le journalisme d\'investigation depuis notre plus jeune âge, nous avons créé en 2019 ce site pour répondre à un réel besoin : celui de fournir une information fiable et précise sur la vie de la <abbr title="Licence Informatique">L-INFO</abbr> de l\'<a href="http://www.univ-fcomte.fr" target="_blank">Université de Franche-Comté</a>.</p>',
      '<p>Découvrez les hommes et les femmes qui composent l\'équipe de choc de la Gazette de L-INFO.</p>',
  '</section>';
}

/**
 * Afficher les sections avec les membres de la rédaction
 */
function vpacl_print_people_sections() {
  // Obtenir les membres de la rédaction
  $categories_data = vpacl_extract_categories();

  // Afficher les différentes catégories
  foreach($categories_data as $cat) {
    vpacl_print_cat($cat);
  }
}

/**
 * Afficher la dernière section de la page
 */
function vpacl_print_last_section() {
  echo '<section>',
    '<h2>La Gazette de L-INFO recrute !</h2>',
    '<p>Si vous souhaitez vous aussi faire partie de notre team, rien de plus simple. Envoyez-nous un mail grâce au lien dans le menu de navigation, et rejoignez l\'équipe.</p>',
  '</section>';
}

/**
 * Afficher une catégorie
 * 
 * @param array $cat Tableau contenant tous les éléments de la catégorie
 */
function vpacl_print_cat($cat) {
  $title = (count($cat) == 1) ? "Notre {$cat[0]['catLibelle']}" : 'Nos ' . vpacl_to_plural($cat[0]['catLibelle']);

  echo '<section>',
    '<h2>', $title, '</h2>';
    foreach($cat as $person) {
      vpacl_print_person($person);
    }
  echo '</section>';
}

/**
 * Afficher les informations sur une personne
 * 
 * @param array $person Tableau contenant les données d'une personne
 */
function vpacl_print_person($person) {
  // Valeurs
  $image = file_exists("../upload/{$person['rePseudo']}.jpg") ? "../upload/{$person['rePseudo']}.jpg" : '../images/anonyme.jpg';
  $person = vpac_protect_data($person);
  $author = vpac_mb_ucfirst($person['utPrenom']) . ' ' . vpac_mb_ucfirst($person['utNom']);
  vpac_parse_bbcode($person['reBio']);
  vpac_parse_bbcode_unicode($person['reBio']);
  $function = (!empty($person['reFonction'])) ? "<h4>{$person['reFonction']}</h4>": '';

  // Affichage
  echo '<article class="redacteur" id="', $person['rePseudo'], '">',
    '<img src="', vpac_protect_data($image), '" width="150" height="200" alt="', $author, '">',
    '<h3>', $author, '</h3>',
    $function,
    $person['reBio'],
  '</article>';
}

/**
 * Extraire les différentes catégories de la base de données
 * 
 * @return array Tableau avec les différentes catégories
 */
function vpacl_extract_categories() {
  //Requête SQL
  $db = vpac_db_connect();
  $sql = 'SELECT rePseudo, utNom, utPrenom, reBio, reCategorie, reFonction, catLibelle
          FROM redacteur, categorie, utilisateur
          WHERE reCategorie = catID
            AND rePseudo = utPseudo
            AND reBio IS NOT NULL
            AND (utStatut = 1 OR utStatut = 3)
          ORDER BY reCategorie, utPseudo';
  $res = mysqli_query($db, $sql) or vpac_bd_error($db, $sql);
  mysqli_close($db);

  $results = array();
  while($data = mysqli_fetch_assoc($res)) {
    $results[$data['reCategorie']][] = $data;
  }
  mysqli_free_result($res);

  return $results;
}

/**
 * Mettre une catégorie au pluriel
 * 
 * @param string Chaîne au singulier
 * @param string Chaîne au pluriel
 */
function vpacl_to_plural($str) {
  $strs = explode(' ', $str);
  $res = '';
  foreach($strs as $elmt) {
    $res .= (next($strs)) ? "{$elmt}s " : "{$elmt}s";
  }

  return $res;
}
?>