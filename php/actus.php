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
//var_dump($data);
//var_dump(vpac_classer_articles_par_mois($data));

$numberOfPages=(int)(count($data)/4)+1;
vpac_print_page_selector($numberOfPages);

$data_by_month=vpac_classer_articles_par_mois($data);
vpac_print_articles($data_by_month);

// Footer
vpac_get_footer();

/**
 * coupe une date pour ne retenir que l'année et le mois
 * 
 * @param date date au format YYYYMMDDhhmm
 * @return return date au format YYYYDD
 */
function vpac_get_month_and_year($date){
    return substr($date,0,6);
}

/**
 * renvoie un tableau contenant les articles correspondant au numéro de la page, classés par mois
 * @param array $data Tableau contenant tous les articles de la page
 * @return array $return Tableau contenant les 4 articles de la page, indexé par mois
 */
function vpac_classer_articles_par_mois(array $data){
    $return=array();
    for($i=($_GET['page']-1)*4;$i<=($_GET['page']-1)*4+3&&$i<count($data);++$i){
        if(!array_key_exists(vpac_get_month_and_year($data[$i]['arDatePublication']),$return)){
            $return[vpac_get_month_and_year($data[$i]['arDatePublication'])][0]=$data[$i];
        }else{
            array_push($return[vpac_get_month_and_year($data[$i]['arDatePublication'])],$data[$i]);
        }
    }
    return $return;
}

/**
 * Transformation d'une date dans le format
 * Month Year
 * 
 * @param int $date date à transformer
 */
function vpac_month_and_year_to_string($date) {
    
    $month = (int)substr($date, -8, 2);
    $year = substr($date, 0, -8);
  
    $months = vpac_get_months();
  
    return mb_strtolower($months[$month], 'UTF-8') . ' ' . $year;
  }

/**
 * Affiche le contenu principal de la page, 4 articles classés par mois
 * @param array $data_by_month Tableau indexé par mois, contenant les 4 articles
 */
function vpac_print_articles(array $data_by_month){
    foreach($data_by_month as &$value){
        echo'<section>',
            '<h2>',vpac_month_and_year_to_string($value[0]['arDatePublication']),'</h2>';
            foreach($value as &$article) { 
                vpac_print_article($article);
            }
        echo'</section>';
    } 
}

/**
 * affiche un article
 * @param $article Tableau contenant les informations de l'article en question
 */
function vpac_print_article(array $article){
    $image = (file_exists("../upload/{$article['arID']}.jpg")) ? "<img src=\"../upload/{$article['arID']}.jpg\" alt=\"{$article['arTitre']}\">" : '';
    $article['arResume'] = vpacl_parse_bbcode1($article['arResume']);
    $article['arTitre'] = vpacl_parse_bbcode1($article['arTitre']);
    echo'<article class="resume">',
            $image,
            '<h3>',$article['arTitre'],'</h3>',
            '<p>',
            $article['arResume'],
            '</p>',
            '<footer><a href="../php/article.php?id=',$article['arID'],'">Lire l\'article</a></footer>',
        '</article>';
}

/**
 * affiche la barre permettant de sélectionner la page souhaitée
 * @param $numberOfPages nombre de pages d'articles
 */
function vpac_print_page_selector($numberOfPages){
    echo'<article class="page_selector">',
            'Pages :';
            for($i=1;$i<=$numberOfPages;$i++){
                if($i==$_GET['page']){
                    echo'<a href="../php/actus.php?page=',$i,'"><h5>',$i,'</h5></a>';
                }else{
                    echo'<a href="../php/actus.php?page=',$i,'"><h4>',$i,'</h4></a>';
                }
            }
        echo'</article>';
}

?>