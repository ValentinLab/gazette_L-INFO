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

// Articles
$bd = vpac_bd_connecter();
$sql = "SELECT * FROM article 
        ORDER BY arDatePublication DESC";

$res = mysqli_query($bd, $sql) or vpac_bd_erreur($bd, $sql);
if(mysqli_num_rows($res) > 0) {
    $data=array();
    $current_row;
    while($current_row=mysqli_fetch_assoc($res)){
        array_push($data,$current_row);
    }
    mysqli_close($bd);
}
mysqli_free_result($res);
var_dump($data);

$numberOfPages=(int)(count($data)/4)+1;
//var_dump(count($data));
vpac_print_articles($data);


// Footer
vpac_get_footer();

/**
 * coupe une date pour ne retenir que l'année et le mois
 * 
 * @param date date au format YYYYMMDDhhmm
 * @return return date au format YYYYDD
 */
function vpac_get_month_and_year($date){
    return substr($data[0]['arDatePublication'],0,6);
}
/**
 * renvoie un tableau contenant les articles correspondant au numéro de la page, classés par moi
 * @param array $data Tableau contenant tous les articles
 */
function vpac_classer_articles_par_mois(array $data){
    $return=array();
    for($i=($_GET['page']-1)*4;$i<=($_GET['page']-1)*4+3&&$i<count($data);++$i){
        //
    }
}

/**
 * Affiche tous les articles correspondant au numéro de la page
 * 
 * @param array $data Tableau contenant tous les articles
 */
/*function vpac_print_articles(array $data){
    for($i=($_GET['page']-1)*4;$i<=($_GET['page']-1)*4+3&&$i<count($data);++$i){
        vpac_print_article($data, $i);
    }
}*/

/**
 * Affiche un article
 * @param array $data Tableau contenant tous les articles
 * @param $index indice de l'article dans $data
 */
function vpac_print_article(array $data, $index){

}
?>