<?php
ob_start();
session_start();

require_once 'bibli_gazette.php';
require_once 'bibli_generale.php';

    $errors = array();
    if(isset($_POST['btnPublication'])||isset($_POST['btnValidationImage'])) {
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

// ----------------------------------------
// Fonctions
// ----------------------------------------

    /**
 * Affichage du formulaire (titre, résumé et contenu si ce n'est pas déjà fait, sinon choix de l'image)
 * 
 * @param array $errors Tableau avec les erreurs de saisie
 */
    function vpacl_print_form($errors) {
      //premier formulaire (titre, résumé et contenu)
        echo '<section>',
        '<h2>Formulaire de rédaction</h2>',
        '<p>Rédiger votre article ci-dessous.</p>';
  
        vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de la rédaction de l\'article :');
  
        // Valeurs du formulaire
        $titre=$resume=$contenu='';
        if(isset($_POST['btnPublication'])) {
          $titre=vpac_protect_data($_POST['titre']);
          $resume=vpac_protect_data($_POST['resume']);
          $contenu=vpac_protect_data($_POST['contenu']);
        }
        echo '<form action="nouveau.php" method="post" enctype="multipart/form-data">',
          '<table>';
            vpac_print_table_form_input('Titre de l\'article', 'titre', vpac_protect_data($titre), true);
            vpac_print_table_form_textarea('Résumé','resume',5, 60, true);
            echo '<tr><td></td><td>';
            vpac_print_bbcode_dialog();
            echo '</td></tr>';
            vpac_print_table_form_textarea('Contenu de l\'article','contenu',20,60, true);
            vpac_print_table_form_image(
              'image',
              '../images/none.jpg',
              '',
              'image d\'illustration',
              250,
              187
            );
            vpac_print_table_form_button(array('submit'), array('Publier'), array('btnPublication'));
          echo '</table>',
        '</form>',
      '</section>';
    }
  
  /**
   * Traitement du formulaire (titre, résumé et contenu si ce n'est pas déjà fait, sinon choix de l'image)
   * 
   * @return array $errors Tableau à remplir avec les erreurs de saisie
   */
  function vpacl_form_processing() {
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
      return;
    }
  
    //Publication de l'article
    $bd = vpac_db_connect();
    
    $titre = mysqli_real_escape_string($bd, $titre);
    $resume = mysqli_real_escape_string($bd,$resume);
    $contenu = mysqli_real_escape_string($bd,$contenu);
    
    vpac_string_to_bbcode($contenu);
    
    $date=getdate();
    $datePublication=vpac_date_array_to_int($date);  

    $auteur=mysqli_real_escape_string($bd, $_SESSION['user']['pseudo']);
    $sql = "INSERT INTO article (arTitre,arResume,arTexte,arDatePublication,arDateModification,arAuteur)
          VALUES ('{$titre}', '{$resume}', '{$contenu}', '{$datePublication}', NULL, '{$auteur}')";
    mysqli_query($bd, $sql) or vpac_db_error($bd, $sql);
    $insert_id=mysqli_insert_id($bd);
    
    mysqli_close($bd);
    if($_FILES['image']['name']!=''){
      $errors=array();
      //vérification des erreurs
      $f = $_FILES['image'];
      if($f['type']!='image/jpeg'){
        $errors[] = 'le fichier doit être de type jpg';
      }
      switch ($f['error']) {
      case 1:
      case 2:
        $errors[] = $f['name'].' est trop gros.';
        break;
      case 3:
        $errors[] = 'Erreur de transfert de '.$f['name'];
        break;
      case 4:
        $errors[] = $f['name'].' introuvable.';
      }
      if(!empty($errors)) {
        return;
      }
      if (! @is_uploaded_file($f['tmp_name'])) {
        $errors[]='Erreur interne de transfert';
      }
      $place = realpath('..').'\\upload\\'.$insert_id.'.'.pathinfo($f['name'])['extension'];
      if (!@move_uploaded_file($f['tmp_name'], $place)) {
        $errors[] = 'Erreur interne de transfert';
      }
      if(!empty($errors)) {
        return;
      }
    }
    header('Location: ./actus.php?');
    exit();
  }

?>