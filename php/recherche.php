<?php
ob_start();
session_start();

require_once 'bibli_gazette.php';
require_once 'bibli_generale.php';


// ----------------------------------------
// Traitement du formulaire
// ----------------------------------------

$errors = array();
if(isset($_POST['btnRecherche'])) {
    $result = vpacl_form_processing();
    //$result est le tableau des erreurs
    if(isset($result[0])){
        $errors = $result;
    }else{//$result est le tableau des articles, classés par mois
        $data = $result;
    }
}

// ----------------------------------------
// Page
// ----------------------------------------

// Header
vpac_get_head('Rechercher');
vpac_get_nav();
vpac_get_header('Rechercher un article');
// Formulaire

vpacl_print_form($errors);
if(isset($data)){
    vpac_print_articles($data);
}

// Footer
vpac_get_footer();
ob_end_flush();

// ----------------------------------------
// Fonctions
// ----------------------------------------

function vpacl_print_form($errors) {
    echo '<section>',
        '<h2>Rechercher des articles</h2>';

        vpac_print_form_errors($errors);

        echo '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte</p>';
        $criteres='';
        if(isset($_POST['btnRecherche'])) {
        $criteres=vpac_protect_data($_POST['criteres']);
        }
        echo '<form action="recherche.php" method="post">',
        '<input type="text" name="criteres" id="criteres"';  
        if(isset($_POST['criteres'])){
            echo ' value="',$_POST['criteres'],'"';
        }
        echo '>';
        vpac_print_input_btn('submit', 'Rechercher', 'btnRecherche');
        echo '</form>',
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
        $sql.=" (arTitre LIKE '%$val%' OR arResume LIKE '%$val%') AND";
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
    if(isset($data)){ 
        $data_by_month=vpac_classer_articles_par_mois($data);
        return $data_by_month;
    }else{
        $errors[]='Aucun article ne correspond à votre recherche';
        return $errors;
    }
}

?>