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
vpacl_print_actus();

// Footer
vpac_get_footer();
ob_end_flush();

/**
 * Afficher la page actus.php
 */
function vpacl_print_actus(){
    // Vérifier le paramètre id dans l'URL
    if(isset($_GET['page'])){
        $page = (int)vpac_decrypt_url($_GET['page']);
        if(!vpac_is_number($page) || $page <= 0) {
            vpac_print_error('Identifiant de page invalide.');
            return;
        }
    }else{
        $page=1;
    }

    $db = vpac_db_connect();
    $sql = "SELECT * FROM article 
            ORDER BY arDatePublication DESC";

    $res = mysqli_query($db, $sql) or vpac_bd_error($db, $sql);
    if(mysqli_num_rows($res) > 0) {
        $data=array();
        while($current_row=mysqli_fetch_assoc($res)){
            array_push($data,$current_row);
        }
        mysqli_close($db);
    }
    mysqli_free_result($res);

    $numberOfPages=(int)(count($data)/4)+1;
    if($page > $numberOfPages) {
        vpac_print_error('Identifiant de page invalide.');
        return;
    }


    vpacl_print_page_selector($numberOfPages);

    $data_by_month=vpacl_classer_articles_par_mois($data);
    vpacl_print_articles($data_by_month);
}

/**
 * Couper une date pour ne retenir que l'année et le mois
 *
 * @param string $date Date au format YYYYMMDDhhmm
 * @return string Date au format YYYYDD
 */
function vpacl_get_month_and_year($date){
    return substr($date,0,6);
}

/**
 * Obtenir un tableau contenant les articles correspondant au numéro de la page, classés par mois
 *
 * @param array $data Tableau contenant tous les articles de la page
 * @return array Tableau contenant les 4 articles de la page, indexé par mois
 */
function vpacl_classer_articles_par_mois($data){
    $page = (isset($_GET['page'])) ? vpac_decrypt_url($_GET['page']) : 1;;

    $return=array();
    for($i=($page-1)*4;$i<=($page-1)*4+3&&$i<count($data);++$i){
        if(!array_key_exists(vpacl_get_month_and_year($data[$i]['arDatePublication']),$return)){
            $return[vpacl_get_month_and_year($data[$i]['arDatePublication'])][0]=$data[$i];
        }else{
            array_push($return[vpacl_get_month_and_year($data[$i]['arDatePublication'])],$data[$i]);
        }
    }
    return $return;
}

/**
 * Transformer une date dans le format
 * Month Year
 * 
 * @param int $date Date à transformer
 */
function vpacl_month_and_year_to_string($date) {
    $month = (int)substr($date, -8, 2);
    $year = substr($date, 0, -8);
  
    $months = vpac_get_months();
  
    return vpac_mb_ucfirst($months[$month]) . ' ' . $year;
}

/**
 * Afficher le contenu principal de la page, 4 articles classés par mois
 *
 * @param array $data_by_month Tableau indexé par mois, contenant les 4 articles
 */
function vpacl_print_articles($data_by_month){
    foreach($data_by_month as &$value){
        echo'<section>',
            '<h2>',vpacl_month_and_year_to_string($value[0]['arDatePublication']),'</h2>';
            foreach($value as &$article) { 
                vpacl_print_article($article);
            }
        echo'</section>';
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

/**
 * Afficher la barre permettant de sélectionner la page souhaitée
 * 
 * @param int $numberOfPages Nombre de pages d'articles
 */
function vpacl_print_page_selector($numberOfPages) {
    $page = (isset($_GET['page'])) ? vpac_decrypt_url($_GET['page']) : 1;

    echo'<article id="page_selector">',
            '<p>Pages :</p>';
            // Précédent
            $disabled = ($page == 1) ? ' button_disabled' : '';
            echo'<a href="../php/actus.php?page=',vpac_encrypt_url($page - 1),'" class="button', $disabled,
              '">&#x25C1;</a>';;
            // Numéros
            for($i=1;$i<=$numberOfPages;$i++){
                if($i==$page){
                    echo'<a href="../php/actus.php?page=',vpac_encrypt_url($i),'" class="button button_selected">',$i,
                      '</a>';
                }else{
                    echo'<a href="../php/actus.php?page=',vpac_encrypt_url($i),'" class="button">',$i,'</a>';
                }
            }
            // Suivant
            $disabled = ($page >= $numberOfPages) ? ' button_disabled' : '';
            echo'<a href="../php/actus.php?page=',vpac_encrypt_url($page + 1),'" class="button', $disabled,
              '">&#x25C1;</a>';
        echo'</article>';
}
?>