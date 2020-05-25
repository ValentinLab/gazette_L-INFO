<?php
ob_start();
session_start();

require_once 'bibli_gazette.php';
require_once 'bibli_generale.php';


// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('L\'actu');
vpac_get_nav();
vpac_get_header('L\'actu');

// Formulaire
$errors = array();
vpacl_print_form($errors);
if(isset($_POST['btnRecherche'])) {
    $errors = vpacl_form_processing();
}

// Footer
vpac_get_footer();
ob_end_flush();

// ----------------------------------------
// Fonctions
// ----------------------------------------

function vpacl_print_form($errors) {
    echo '<section>',
        '<h2>Rechercher des articles</h2>',
        '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte</p>';

        vpac_print_form_errors($errors, 'Les erreurs suivantes ont été relevées lors de votre recherche :');

        $criteres='';
          if(isset($_POST['btnRecherche'])) {
            $criteres=vpac_protect_data($_POST['criteres']);
          }
        echo '<form action="recherche.php" method="post">',
        '<table>',
            '<input type="input" name="criteres" id="criteres"';  
            if(isset($_POST['criteres'])){
                echo ' value="',$_POST['criteres'],'"';
            }
            echo '>',
            '<input type="submit" name="btnRecherche">',
            '<input type="reset" name="btnReset">',
        '</table>',
        '</form>',
    '</section>';
}

function vpacl_form_processing() {
    if(!isset($_POST['criteres'])){
        vpac_session_exit();
    }
    $criteres=explode(" ",trim($_POST['criteres']));

    //Vérification de la validité des critères
    if(empty($criteres)){
        $errors[]="Erreur :  vous devez entrer au moins un critère";
    }

    foreach($criteres as $val){
        if(strlen($val)<3){
            $errors[]="Erreur : les critères doivent faire au moins 3 caractères";
        }
    }

    if(!empty($errors)){
        return $errors;
    }
    
    //Requête SQL
    $db = vpac_db_connect();

    $sql="SELECT * FROM article WHERE";
    foreach($criteres as $val){
        $sql.=" arTitre LIKE '%$val%' OR arResume LIKE '%$val%' AND";
    }
    $sql=substr($sql,0,-3);
    $sql.= " ORDER BY arDatePublication DESC";
    $res = mysqli_query($db, $sql) or vpac_db_error($db, $sql);
    if(mysqli_num_rows($res) > 0) {
        $data=array();
        while($current_row=mysqli_fetch_assoc($res)){
            array_push($data,$current_row);
        }
    }
    
    //Affichage des articles
    if(isset ($data)){
        foreach($data as $article){
            vpacl_print_article($article);
        }
    }
    
}

/**
 * Afficher un article
 * 
 * @param array $article Tableau contenant les informations de l'article en question
 */
function vpacl_print_article($article){
    $image = (file_exists("../upload/{$article['arID']}.jpg")) ? "<img src=\"../upload/{$article['arID']}.jpg\" alt=\"{$article['arTitre']}\">" : '';
    echo'<article class="resume">',
            $image,
            '<h3>',$article['arTitre'],'</h3>',
            '<p>',
            $article['arResume'],
            '</p>',
            '<footer><a href="../php/article.php?id=',vpac_encrypt_url($article['arID']),'">Lire l\'article</a></footer>',
        '</article>';
}

?>