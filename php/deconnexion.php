<?php
ob_start();
session_start();

require_once 'bibli_gazette.php';
require_once 'bibli_generale.php';

// Vérifier que l'utilisateur peut accéder à cette page
vpac_check_authentication();

// Supprimer la session et rediriger l'utilisateur
$referer = $_SERVER['HTTP_REFERER'];
if(empty($referer)) {
  $referer = '../index.php';
}
vpac_session_exit($referer);
?>