<?php
ob_start();
session_start();

require_once 'bibli_gazette.php';
require_once 'bibli_generale.php';

$errors = array();
if(isset($_POST['btnValidation'])||isset($_POST['btnValidationImage'])) {
  $errors = vpacl_form_processing();
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
     * 
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
                        vpac_print_table_form_textarea('Résumé','resume',5, 80, true,vpac_protect_data($resume));
                        vpac_print_table_form_textarea('texte de l\'article','texte',40,80, true,vpac_protect_data($texte));
                        vpac_print_input_image('Vous pouvez changer l\'image','image');
                        $image = (file_exists("../upload/{$data['arID']}.jpg")) ? "<img id=\"edition_image\" src=\"../upload/{$data['arID']}.jpg\">" : '';
                        echo "<td>$image</td>";
                        vpac_print_table_form_button(array('submit', 'reset'), array('Valider', 'Réinitialiser'), array('btnValidation', ''));
                    echo '</table>',
                '</form>',
            '</section>';
        vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de l\'édition de l\'article :');
    }

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

?>