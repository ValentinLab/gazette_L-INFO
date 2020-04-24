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
    if(isset($_POST['btnPublication'])) {
      $errors = vpacl_form_processing();
    }


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
  
        // Valeurs du formulaire*/
        $titre=$resume=$contenu='';
        if(isset($_POST['btnPublication'])) {
          $titre=vpac_protect_data($_POST['titre']);
          $resume=vpac_protect_data($_POST['resume']);
          $contenu=vpac_protect_data($_POST['contenu']);
        }
  
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
  
  /**
   * Traitement du formulaire
   * 
   * @return array Tableau à remplir avec les erreurs de saisie
   */
  function vpacl_form_processing() {
    // Vérifier les clés présentes dans $_POST
    if(!vpac_parametres_controle('post',array('titre', 'resume', 'contenu','btnPublication'))) {
      var_dump($_POST);
      //vpac_session_exit();
    }

    // Valeurs à récuperer dans le formulaire
    $titre = $resume = $contenu = '';

    // Vérification du titre
    $titre=$_POST['titre'];
    $titre_len = mb_strlen($titre, 'UTF-8');
    if($titre_len == 0) {
      $errors[] = 'Le titre ne peut pas être vide.';
    } elseif($titre_len > 150) {
      $errors[] = "Le titre ne peut pas contenir plus de 255 caractères. Actuellement $titre_len";
    }
    // Vérification du résumé
    $resume=$_POST['resume'];
    $resume_len = mb_strlen($resume, 'UTF-8');
    if($resume_len == 0) {
      $errors[] = 'Le résumé ne peut pas être vide.';
    }

    // Vérification du contenu
    $contenu=$_POST['contenu'];
    $contenu_len = mb_strlen($contenu, 'UTF-8');
    if($contenu_len == 0) {
      $errors[] = 'Le contenu ne peut pas être vide.';
    }
    
    if(!empty($errors)) {
      exit();
    }
   
    //Publication de l'article
    $bd = vpac_bd_connecter();
    
    $titre = mysqli_real_escape_string($bd, $titre);
    $resume = mysqli_real_escape_string($bd, $resume);
    $contenu = mysqli_real_escape_string($bd, $contenu);
  
    $date=getDate();
    $datePublication=$date['year'];
    
    //mise au bon format du mois, jour, heure et minutes
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
    var_dump($datePublication);

    $auteur=mysqli_real_escape_string($bd, $_SESSION['user']['pseudo']);
    $sql = "INSERT INTO article (arTitre,arResume,arTexte,arDatePublication,arDateModification,arAuteur)
          VALUES ('{$titre}', '{$resume}', '{$contenu}', '{$datePublication}', NULL, '{$auteur}')";
    mysqli_query($bd, $sql) or vpac_bd_erreur($bd, $sql);
    mysqli_close($bd);
    header('Location: ../index.php');
  }
?>