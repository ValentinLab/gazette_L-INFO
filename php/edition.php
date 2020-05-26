<?php
ob_start();
session_start();

require_once 'bibli_gazette.php';
require_once 'bibli_generale.php';

$errors = array();
if(isset($_POST['btnValidation'])) {
  $errors = vpacl_form_processing();
} else if(isset($_POST['btnRemove'])) {
  vpacl_form_processing_rm();
}

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Edition d\'article');
vpac_get_nav();
vpac_get_header('Edition d\'article');

$db = vpac_db_connect();
$data=vpacl_get_article($db);

// Formulaire
vpacl_print_form($errors,$data);

// Footer
vpac_get_footer();
ob_end_flush();
// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
 * Récupération de l'article, en vérifiant que l'utilisateur est bien l'auteur
 * 
 * @param $db connexion à la base de données
 * 
 * @return array tableau contenant le titre, le résumé et le texte de l'article.
 */
function vpacl_get_article($db){
    if(!isset($_GET['arID'])){
        header('Location: ../index.php');
    }
    
    //Récupération de l'article
    $sql="SELECT * FROM article where arID=".vpac_decrypt_url($_GET['arID']);
    $res = mysqli_query($db, $sql) or vpac_db_error($db, $sql);
    $data=mysqli_fetch_assoc($res);
    
    // Vérifier le nombre de résultats
    if(mysqli_num_rows($res) == 0) {
        vpac_print_error('Identifiant d\'article non reconnu.');
        mysqli_free_result($res);
        mysqli_close($db);
        return;
    }

    // Vérification que l'utilisateur est bien l'auteur de l'article
    if($_SESSION['user']['pseudo']!=$data['arAuteur']){
        vpac_print_error('Vous n\'êtes pas l\'auteur de cet article.');
        mysqli_free_result($res);
        mysqli_close($db);
        return;
    }
    return $data;
}

/**
 * Affichage du formulaire
 * 
 * @param array $errors Erreur du traitement
 * @param array $data   Données pour le formulaire
 */
function vpacl_print_form($errors,$data){
    $titre=$data['arTitre'];
    $resume=$data['arResume'];
    $texte=$data['arTexte'];
    echo '<section>',
            '<h2>Formulaire d\'édition</h2>',   
            '<p>Modifiez votre article ci-dessous.</p>',
            vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de l\'édition de l\'article :');
            echo'<form action="edition.php?arID=',urlencode($_GET['arID']),'" method="post" enctype="multipart/form-data">',
                '<table>';
                    vpac_print_table_form_input('Titre de l\'article', 'titre', vpac_protect_data($titre), true);
                    vpac_print_table_form_textarea('Résumé','resume',5, 60, true,vpac_protect_data($resume));
                    echo '<tr><td></td><td>';
                      vpac_print_bbcode_dialog();
                    echo '</td></tr>';
                    vpac_print_table_form_textarea('Texte de l\'article','texte',20,60, true,vpac_protect_data($texte));
                    vpac_print_table_form_image(
                      'image',
                      '../images/none.jpg',
                      "../upload/{$data['arID']}.jpg",
                      'image d\'illustration',
                      250,
                      187
                    );
                    echo '<tr><td colspan="2">';
                      vpac_print_input_btn('submit', 'Enregistrer', 'btnValidation');
                      vpacl_print_remove_dialog();
                    echo '</td></tr>',
                '</table>',
            '</form>',
        '</section>';
    vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de l\'édition de l\'article :');
}

/**
 * Traitement du formulaire pour l'édition
 * 
 * @return array Traitement du formulaire
 */
function vpacl_form_processing(){
    // Vérifier les clés présentes dans $_POST
    if(!vpac_parametres_controle('post',array('titre', 'resume', 'texte','btnValidation'))) {
    vpac_session_exit();
    }

    // Valeurs à récuperer dans le formulaire
    $titre = $resume = $texte = '';

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

    // Vérification du texte
    $texte=$_POST['texte'];
    $texte_len = mb_strlen($texte, 'UTF-8');
    if($texte_len == 0) {
    $errors[] = 'Le texte ne peut pas être vide.';
    }

    if(!empty($errors)) {
    return;
    }
    
    //Mise à jour de la bd
    $bd = vpac_db_connect();
  
    $titre = mysqli_real_escape_string($bd, $titre);
    $resume = mysqli_real_escape_string($bd,$resume);
    $texte = mysqli_real_escape_string($bd,$texte);
    vpac_string_to_bbcode($texte);
    $dateModif = mysqli_real_escape_string($bd,vpac_date_array_to_int(getdate()));

    $sql="UPDATE article
    SET arTitre='{$titre}',arResume='{$resume}',arTexte='{$texte}',arDateModification='{$dateModif}'
    WHERE arID=".vpac_decrypt_url($_GET['arID']);
    mysqli_query($bd, $sql) or vpac_db_error($bd, $sql);
    mysqli_close($bd);
    header('Location: ./article.php?id='.urlencode($_GET['arID']));

    //Mise à jour du fichier upload
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
            $place = realpath('..').'\\upload\\'.vpac_decrypt_url($_GET['arID']).'.'.pathinfo($f['name'])['extension'];
            unlink($place);
            if (!@move_uploaded_file($f['tmp_name'], $place)) {
                $errors[] = 'Erreur interne de transfert';
            }
            if(!empty($errors)) {
                return;
            }
        }
    exit();
}

/**
 * Traitement du formulaire pour la suppression
 * 
 * @return array Traitement du formulaire
 */
function vpacl_form_processing_rm() {
  // Vérifier les clés présentes dans $_POST
  if(!vpac_parametres_controle('post',array('titre', 'resume', 'texte', 'btnRemove'))) {
    vpac_session_exit();
  }

  // Vérifier l'ID de l'article
  $article_id = vpac_decrypt_url($_GET['arID']);
  if($article_id === FALSE) {
    vpac_session_exit();
  }

  // Supprimer l'article
  $db = vpac_db_connect();
    $current_user = mysqli_real_escape_string($db, $_SESSION['user']['pseudo']);
  $sql = "DELETE FROM article
          WHERE arID = '$article_id' AND arAuteur = '$current_user'";
  var_dump($sql);
  mysqli_query($db, $sql) or vpac_db_error($db, $sql);

  // Redirection
  header('Location: ../index.php');
}

/**
 * Affichage de la boîte de dialogue pour la suppression de l'article
 */
function vpacl_print_remove_dialog() {
  // Bouton d'affichage
  echo '<input type="checkbox" class="dialog_btn" id="dialog_rm"><label for="dialog_rm">Supprimer cet article</label>';

  // Boîte de dialogue
  echo '<div class="dialog">',
    '<header>',
      '<h2><span>Zone de danger</span></h2>',
      '<label for="dialog_rm">&#x2715;</label>',
    '</header>',
    '<div>',
      '<h3>Souhaitez-vous supprimer cet article ?</h3>',
      '<p>Vous ne pourrez pas restaurer l\'article après l\'avoir supprimé.</p>';
      vpac_print_input_btn('submit', 'Supprimer', 'btnRemove');
      echo '<label for="dialog_rm">Annuler</label>',
    '</div>',
  '</div>';
}
?>