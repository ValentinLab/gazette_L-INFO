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
 * Affichage du formulaire (titre, résumé et contenu si ce n'est pas déjà fait, sinon choix de l'image)
 * 
 * @param array $errors Tableau avec les erreurs de saisie
 */
    function vpacl_print_form($errors) {
      //premier formulaire (titre, résumé et contenu)
      if(!isset($_GET['id'])){
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
      }//deuxième formulaire : upload de l'image
      else{
        echo '<section>',
          '<h2>Choisissez maintenant une image pour votre article</h2>';
          vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de l\'upload de la photo de l\'article :');
        echo '<form action="nouveau.php?id=',$_GET['id'],'" method="post" enctype="multipart/form-data">',
            '<table>';
              vpac_print_input_image('Sélectionnez une image (PNG/JPEG) : ','image');
              vpac_print_table_form_button(array('submit', 'reset'), array('Valider', 'Réinitialiser'), array('btnPublication', ''));
            echo '</table>',
          '</form>',
        '</section>';
      }
    }
  
  /**
   * Traitement du formulaire (titre, résumé et contenu si ce n'est pas déjà fait, sinon choix de l'image)
   * 
   * @return array Tableau à remplir avec les erreurs de saisie
   */
  function vpacl_form_processing() {
    //premier formulaire (titre, résumé et contenu)
    if(!isset($_GET['id'])){
      // Vérifier les clés présentes dans $_POST
      if(!vpac_parametres_controle('post',array('titre', 'resume', 'contenu','btnPublication'))) {
        vpac_session_exit();
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
    
      $datePublication=date_array_to_int($date);  

      $auteur=mysqli_real_escape_string($bd, $_SESSION['user']['pseudo']);
      $sql = "INSERT INTO article (arTitre,arResume,arTexte,arDatePublication,arDateModification,arAuteur)
            VALUES ('{$titre}', '{$resume}', '{$contenu}', '{$datePublication}', NULL, '{$auteur}')";
      mysqli_query($bd, $sql) or vpac_bd_erreur($bd, $sql);
      $insert_id=mysqli_insert_id($bd);
      mysqli_close($bd);
      header('Location: ./nouveau?id='.$insert_id);
    }//deuxième formulaire : upload de l'image
    else{
      if(!vpac_parametres_controle('post',array('image','btnPublication'))) {
        vpac_session_exit();
      }

      //Vérification de la validité fichier sélectionné

      //Création du fichier dans le répértoire upload
    }
  }

  /**
   * renvoie la date à laquelle l'article est posté dans le bon format
   * 
   * @return int date au format AAAAMMJJhhmm pour l'insérer dans la bdd
   */
  function date_array_to_int(){
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