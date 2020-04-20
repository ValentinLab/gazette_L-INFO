<?php
    ob_start();
    session_start();

    require_once 'bibli_gazette.php';
    require_once 'bibli_generale.php';

    // Vérifier l'authentification
    if(!isset($_SESSION['user'])||$_SESSION['user']['redacteur']==false) {
        header('Location: ../index.php');
        exit();
    }

    // ----------------------------------------
    // Traitement du formulaire
    // ----------------------------------------

    $errors = array();
    /*if(isset($_POST['btnInscription'])) {
    $errors = vpacl_form_processing();
    }*/


    // ----------------------------------------
    // Page
    // ----------------------------------------

    // Header
    vpac_get_head('Nouvel Article');
    vpac_get_nav();
    vpac_get_header('Nouvel Article');

    // Formulaire
    vpacl_print_form($errors);

    // Footer
    vpac_get_footer();
    ob_end_flush();

    /**
 * Affichage du formulaire
 * 
 * @param array $errors Tableau avec les erreurs de saisie
 */
    function vpacl_print_form($errors) {
        echo '<section>',
        '<h2>Formulaire de rédaction</h2>',
        '<p>Rédiger votre article ci-dessous.</p>';
  
        vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de la rédaction de l\'article :');
  
        /*// Année actuelle
        $current_year = date('Y');
  
        // Valeurs du formulaire*/
        $titre=$resume=$contenu='';/*
        if(isset($_POST['btnInscription'])) {
          $pseudo = vpac_protect_data($_POST['pseudo']);
          $nom = vpac_protect_data($_POST['nom']);
          $prenom = vpac_protect_data($_POST['prenom']);
          $email = vpac_protect_data($_POST['email']);
          $naissance_j = (int)$_POST['naissance_j'];
          $naissance_m = (int)$_POST['naissance_m'];
          $naissance_a = (int)$_POST['naissance_a'];
          $civilite = (isset($_POST['radSexe'])) ? $_POST['radSexe'] : 0;
          $mails_pourris = isset($_POST['cbSpam']);
        }*/
  
        echo '<form action="nouveau.php" method="post">',
          '<table>';
            vpac_print_table_form_input('Titre de l\'article', 'titre', vpac_protect_data($titre), true);
            vpac_print_table_form_textarea('Résumé','resume',5, 80, true);
            vpac_print_table_form_textarea('Contenu de l\'article','contenu',40,80, true);
            vpac_print_table_form_button(array('submit', 'reset'), array('Publier', 'Réinitialiser'), array('btnPublication', ''));
          echo '</table>',
        '</form>',
      '</section>';
    }
?>