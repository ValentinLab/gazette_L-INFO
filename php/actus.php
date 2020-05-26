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

// ----------------------------------------
// Fonctions
// ----------------------------------------

/**
* Afficher les articles
*/
function vpacl_print_actus() {
  // Vérifier le paramètre id dans l'URL
  if(isset($_GET['page'])){
    $page = (int)vpac_decrypt_url($_GET['page']);
    if(!vpac_is_number($page) || $page <= 0) {
      vpac_print_error('Identifiant de page invalide.');
      return;
    }
  } else {
    $page=1;
  }
  
  $db = vpac_db_connect();
  //On récupère les 3 articles qui seront affichés
  $offset = ($page-1)*4;
  $sql = "SELECT * FROM article 
  ORDER BY arDatePublication DESC
  LIMIT 4 OFFSET {$offset}";
  
  $res = mysqli_query($db, $sql) or vpac_db_error($db, $sql);
  if(mysqli_num_rows($res) > 0) {
    $data=array();
    while($current_row=mysqli_fetch_assoc($res)){
      array_push($data,$current_row);
    }
  }
  mysqli_free_result($res);
  
  //numberOfPages prend le nombre total de pages d'articles
  if(isset($_GET['nbPages'])){
    $numberOfPages=vpac_decrypt_url($_GET['nbPages']);
  } else {
    $sql_numberOfArticles="SELECT COUNT(*) FROM article";
    $numberOfArticles = mysqli_query($db, $sql_numberOfArticles) or vpac_db_error($db, $sql_numberOfArticles);
    $numberOfPages=mysqli_fetch_assoc($numberOfArticles)['COUNT(*)']/4+1;
  }
  
  mysqli_close($db);
  
  if($page > $numberOfPages) {
    vpac_print_error('Identifiant de page invalide.');
    return;
  }
  
  vpacl_print_page_selector($numberOfPages);
  $data_by_month=vpac_classer_articles_par_mois($data);
  vpac_print_articles($data_by_month);
}

/**
* Afficher la barre permettant de sélectionner la page souhaitée
* 
* @param int $numberOfPages Nombre de pages d'articles
*/
function vpacl_print_page_selector($numberOfPages) {
  $page = (isset($_GET['page'])) ? vpac_decrypt_url($_GET['page']) : 1;
  
  echo'<div id="page_selector">',
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
  $disabled = ($page == (int)$numberOfPages) ? ' button_disabled' : '';
  echo'<a href="../php/actus.php?page=',vpac_encrypt_url($page + 1),'" class="button', $disabled,
  '">&#x25C1;</a>';
  echo'</div>';
}
?>