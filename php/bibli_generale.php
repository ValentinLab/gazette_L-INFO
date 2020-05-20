<?php
require_once 'bibli_bd.php';
require_once 'bibli_form.php';
require_once 'bibli_user.php';

// ----------------------------------------
// Gestion des URLs
// ----------------------------------------

/**
 * Crypter une donnée pour la transmettre dans une URL
 * 
 * @param mixed $value Valeur à crypter
 * @return string Valeur cryptée
 */
function vpac_encrypt_url($value) {
  // Vecteur d'initialisation
  $iv_len = openssl_cipher_iv_length(CIPHER);
  $iv = openssl_random_pseudo_bytes($iv_len);

  // Crypter la valeur
  $url_data = openssl_encrypt($value, CIPHER, base64_decode(KEY), OPENSSL_RAW_DATA, $iv, $tag);

  // Ajouter l'iv et la signature
  $url_data = $iv . $tag . $url_data;
  $url_data = base64_encode($url_data);

  return urlencode($url_data);
}

/**
 * Décrypter une donnée transmise dans une URL
 * 
 * @param string $url_data Valeur cryptée
 * @return mixed Valeur d'origine
 */
function vpac_decrypt_url($url_data) {
  $url_data = base64_decode($url_data);
  $iv_len = openssl_cipher_iv_length(CIPHER);

  // Vérifier la taille de la  chaîne
  if(strlen($url_data) <= $iv_len + TAG_LEN) {
    return false;
  }

  // Vi, tag et donnée
  $iv = substr($url_data, 0, $iv_len);
  $tag = substr($url_data, $iv_len, TAG_LEN);
  $url_data = substr($url_data, $iv_len + TAG_LEN);

  return openssl_decrypt($url_data, CIPHER, base64_decode(KEY), OPENSSL_RAW_DATA, $iv, $tag);
}

// ----------------------------------------
// Vérification de données
// ----------------------------------------

/**
 * Protéger les chaînes de caractères
 * 
 * @param mixed $datas Valeur à proteger
 */
function vpac_protect_data($data) {
  if(is_array($data)) {
    foreach($data as &$val) {
      $val = vpac_protect_data($val);
    }
    unset($val);
    return $data;
  }
  if(is_string($data)) {
    return htmlentities($data, ENT_QUOTES, 'UTF-8');
  }
  return $data;
}

/**
 * Contrôle des clés présentes dans les tableaux $_GET ou $_POST
 * Cette fonction renvoie false en présence d'une suspicion de piratage 
 * et true quand il n'y a pas de problème détecté.
 * 
 * @param  string $tab_global 'post' ou 'get'
 * @param  array  $cles_obligatoires tableau contenant les clés qui doivent obligatoirement être présentes
 * @param  array  $cles_facultatives tableau contenant les clés facultatives
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
 * Vérifier si une variable contient uniquement un nombre
 * 
 * @param string $nb Valeur à vérifier
 * @return bool true si la variable ne contient qu'un nombre
 */
function vpac_is_number($nb) {
  return is_numeric($nb) && $nb == (int)$nb;
}

// ----------------------------------------
// Transformation de texte
// ----------------------------------------

/**
 * Transformation du BBCode en HTML
 * 
 * @param string $text Texte à transformer
 */
function vpac_parse_bbcode(&$text) {
  // balises [p], [gras], [it], [citation], [liste], [item], [br] et \n
  $markups_general = array('/\[(\/)?p\]/',
                          '/\[(\/)?it\]/',
                          '/\[(\/)?gras\]/',
                          '/\[(\/)?citation\]/',
                          '/\[(\/)?liste\]/',
                          '/\[(\/)?item\]/',
                          '/\[br\]/',
                          '/\\n|\r/'
                          );
  $replace_general = array('<\1p>',
                           '<\1em>',
                           '<\1strong>',
                           '<\1blockquote>',
                           '<\1ul>',
                           '<\1li>',
                           ''
                          );
  $text = preg_replace($markups_general, $replace_general, $text);

  // balises [a:url]
  $text = preg_replace_callback('/\[a:([^]]+)\](.*?)\[\/a\]/', 'vpac_parse_url', $text);

  // balises [youtube:w:h:url], [youtube:w:h:url legende]
  $yt = array(
          '/\[youtube:([0-9]+):([0-9]+):([a-zA-Z:\/.0-9&?\-_]+)\]/',
          '/\[youtube:([0-9]+):([0-9]+):([a-zA-Z:\/.0-9&?\-_]+) ([^]]+)\]/'
        );
  $text = preg_replace_callback($yt, 'vpac_parse_youtube', $text);
}

/**
 * Transformation du BBCode en unicode
 * 
 * @param string $text Texte à transformer
 */
function vpac_parse_bbcode_unicode(&$text) {
  // balise [#NNN] -> &#NNN ou [#xNNN] -> &#xNNN
  $text = preg_replace('/\[#([^]]+)\]/', '&#\1;', $text);
}

/**
 * Transformer le BBCode [a:url] si l'URL est valide
 * 
 * @param array $x Tableau transmit par preg_replace_callback
 * @return string BBCode transformé en chaîne de caractères
 */
function vpac_parse_url($x) {
  // Mail
  if(preg_match('/^mailto:([^?]+)/', $x[1], $res) == 1) {
    if(filter_var($res[1], FILTER_VALIDATE_EMAIL)) {
      return "<a href=\"{$x[1]}\">{$x[2]}</a>";
    }
    return $x[0];
  }

  // External link
  if(preg_match('/^https?/', $x[1]) == 1) {
    if(filter_var($x[1], FILTER_VALIDATE_URL)) {
      return "<a href=\"{$x[1]}\" target=\"_blank\">{$x[2]}</a>";
    }
    return $x[0];
  }

  // Anchor
  if(preg_match('/^#[a-zA-Z0-9\-_]/', $x[1]) == 1) {
    return "<a href=\"{$x[1]}\">{$x[2]}</a>";
  }

  // Internal link
  if(file_exists($x[1])) {
    return "<a href=\"{$x[1]}\">{$x[2]}</a>";
  }

 return $x[0];
}

/**
 * Transformer le BBCode [youtube:...] si l'URL est valide
 * 
 * @param array $x Tableau transmit par preg_replace_callback
 * @return string BBCode transformé en chaîne de caractères
 */
function vpac_parse_youtube($x) {
  // Vérification de l'URL
  if(filter_var($x[3], FILTER_VALIDATE_URL) === FALSE) {
    return $x[0];
  }

  // Transformation du BBCode
  if(count($x) == 5) {
    return "<figure><iframe width=\"{$x[1]}\" height=\"{$x[2]}\" src=\"{$x[3]}\" allowfullscreen></iframe><figcaption>{$x[4]}</figcaption></figure>";
  }
  return "<figure><iframe width=\"{$x[1]}\" height=\"{$x[2]}\" src=\"{$x[3]}\" allowfullscreen></iframe></figure>";
}

/**
   * Ajoute la balise [p] à une string si elle n'est pas présente
   * 
   * @param string $string chaîne à transformer
   */
  function vpac_string_to_bbcode(&$string){
    //ajout de la balise paragraphe
    if(strcmp(substr($string,0,3),'[p]')!=0){
      $string='[p]'.$string.'[/p]';
    }
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

// ----------------------------------------
// Autre
// ----------------------------------------

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
   * renvoie la date à laquelle l'article est posté dans le bon format
   * 
   * @return int date au format AAAAMMJJhhmm pour l'insérer dans la bdd
   */
  function vpac_date_array_to_int(){
    $date=getDate();
    $datePublication=$date['year'];
    if($date['mon']<10){
      $datePublication.='0'.$date['mon'];
    }else{
      $datePublication.=$date['mon'];
    }
    if($date['mday']<10){
      $datePublication.='0'.$date['mday'];
    }else{
      $datePublication.=$date['mday'];
    }
    if($date['hours']<10){
      $datePublication.='0'.$date['hours'];
    }else{
      $datePublication.=$date['hours'];
    }
    if($date['minutes']<10){
      $datePublication.='0'.$date['minutes'];
    }else{
      $datePublication.=$date['minutes'];
    }
    return $datePublication;
  }
?>