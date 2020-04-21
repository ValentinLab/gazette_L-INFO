<?php
ob_start();
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_gazette.php';

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Admin');
vpac_get_nav();
vpac_get_header('Administration');

// Footer
vpac_get_footer();
ob_end_flush();
?>