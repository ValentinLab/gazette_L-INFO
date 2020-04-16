<?php
require_once 'bibli_bd.php';
require_once 'bibli_form.php';
require_once 'bibli_user.php';

// ----------------------------------------
// Général
// ----------------------------------------

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

function vpac_decrypt_url($url_data) {
  $url_data = base64_decode($url_data);

  // Vi, tag et donnée
  $iv_len = openssl_cipher_iv_length(CIPHER);
  $iv = substr($url_data, 0, $iv_len);
  $tag = substr($url_data, $iv_len, TAG_LEN);
  $url_data = substr($url_data, $iv_len + TAG_LEN);

  return openssl_decrypt($url_data, CIPHER, base64_decode(KEY), OPENSSL_RAW_DATA, $iv, $tag);
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
?>