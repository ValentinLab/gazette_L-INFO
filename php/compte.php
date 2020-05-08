<?php
ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_gazette.php';

// Vérifier les droits de l'utilisateur
vpac_check_authentication(ALL_U);

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Compte');
vpac_get_nav();
vpac_get_header('Mon compte');

// Page
  // WIP ...

// Footer
vpac_get_footer();
ob_end_flush();
?>