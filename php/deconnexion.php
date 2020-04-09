<?php
session_start();

require_once 'bibli_generale.php';

// Supprimer la session et rediriger l'utilisateur
$referer = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '../index.php';
vpac_session_exit($referer);
?>