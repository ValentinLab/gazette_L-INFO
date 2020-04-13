<?php
require_once 'bibli_bd.php';
require_once 'bibli_form.php';
require_once 'bibli_user.php';

// ----------------------------------------
// Général
// ----------------------------------------

/**
 * Vérifier si une variable contient uniquement un nombre
 * 
 * @param string $nb Valeur à vérifier
 * @return bool true si la variable ne contient qu'un nombre
 */
function vpac_is_number($nb) {
  return is_numeric($nb) && $nb == (int)$nb;
}

/**
 * Protéger les chaînes de caractères d'un tableau
 * 
 * @param array $datas Tableau à protéger
 */
function vpac_protect_array(&$datas) {
  foreach($datas as &$data) {
    if(isset($data)) {
      $data = htmlentities($data);
    }
  }
  unset($data);
}

/**
 * Contrôle des clés présentes dans les tableaux $_GET ou $_POST
 * Cette fonction renvoie false en présence d'une suspicion de piratage 
 * et true quand il n'y a pas de problème détecté.
 * 
 * @param string $tab_global 'post' ou 'get'
 * @param array  $cles_obligatoires tableau contenant les clés qui doivent obligatoirement être présentes
 * @param array  $cles_facultatives tableau contenant les clés facultatives
 * @global array $_GET
 * @global array $_POST
 * @return bool  true si les paramètres sont corrects, false sinon
 */
function vpac_parametres_controle($tab_global, $cles_obligatoires, $cles_facultatives = array()){
  $x = strtolower($tab_global) == 'post' ? $_POST : $_GET;

  $x = array_keys($x);
  // $cles_obligatoires doit être inclus dans $x
  if(count(array_diff($cles_obligatoires, $x)) > 0) {
    return false;
  }
  // $x doit être inclus dans $cles_obligatoires Union $cles_facultatives
  if(count(array_diff($x, array_merge($cles_obligatoires,$cles_facultatives))) > 0) {
    return false;
  }

  return true;
}

/**
 * Obtenir un tableau contenant le nom de tous les mois
 * 
 * @return array Nom de tous les mois
 */
function vpac_get_months() {
  $months = array(1 => 'janvier');
  array_push($months, 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aôut', 'septembre', 'octobre', 'novembre', 'décembre');
  return $months;
}

/**
 * Mettre la première lettre d'une châine de caractères en majuscule
 * et les suivantes en minuscule
 * 
 * @param string $str Chaîne de caractères
 * @return string La chaîne modifiée
 */
function vpac_mb_ucfirst($str) {
  $str = mb_strtolower($str, 'UTF-8');
  $start = mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8');

  return $start . mb_substr($str, 1, mb_strlen($str), 'UTF-8');
}

/**
 * Transformation du BBCode en HTML
 * 
 * @param string $text Texte à transformer
 * @return string BBCode transformé en HTML
 */
function vpacl_parse_bbcode($text) {
  // balise [p] -> <p>
  $text = preg_replace('/\[p\]/', '<p>', $text);
  $text = preg_replace('/\[\/p\]/', '</p>', $text);
  // balise [gras] -> <strong>
  $text = preg_replace('/\[gras\]/', '<strong>', $text);
  $text = preg_replace('/\[\/gras\]/', '</strong>', $text);
  // balise [it] -> <em>
  $text = preg_replace('/\[it\]/', '<em>', $text);
  $text = preg_replace('/\[\/it\]/', '</em>', $text);
  // balise [citation] -> <blockquote>
  $text = preg_replace('/\[citation\]/', '<blockquote>', $text);
  $text = preg_replace('/\[\/citation\]/', '</blockquote>', $text);
  // balise [liste] -> <ul>
  $text = preg_replace('/\[liste\]/', '<ul>', $text);
  $text = preg_replace('/\[\/liste\]/', '</ul>', $text);
  // balise [item] -> <li>
  $text = preg_replace('/\[item\]/', '<li>', $text);
  $text = preg_replace('/\[\/item\]/', '</li>', $text);
  // balise [a:url] -> <a>
  $text = preg_replace('/\[a:([^]]+)\]/', '<a href="\1">', $text);
  $text = preg_replace('/\[\/a\]/', '</a>', $text);

  // balise [br] -> <br>
  $text = preg_replace('/\[br\]/', '<br>', $text);
  // balise [youtube:w:h:url] -> <iframe width='w' height='h' src='url' allowfullscreen></iframe>
  $text = preg_replace('/\[youtube:([^:]+):([^:]+):([^(\]| )]+)\]/', '<iframe width="\1" height="\1" src="\3" allowfullscreen></iframe>', $text);
  // balise [youtube:w:h:url] -> <figure><iframe width="w" height="h" src="url" allowfullscreen></iframe><figcaption>f<figcaption></figure>
  $text = preg_replace('/\[youtube:([^:]+):([^:]+):([^ ]+) ([^]]+)\]/', '<figure><iframe width="\1" height="\2" src="\3" allowfullscreen></iframe><figcaption>\4<figcaption></figure>', $text);

  // balise [#NNN] -> &#NNN ou [#xNNN] -> &#xNNN
  $text = preg_replace('/\[#([^]]+)\]/', '&#\1', $text);

  return $text;
}

?>