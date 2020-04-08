<?php
// ----------------------------------------
// Base de données
// ----------------------------------------

/** 
 *  Ouverture de la connexion à la base de données
 *  En cas d'erreur de connexion le script est arrêté.
 *
 *  @return object Connecteur à la base de données
 */
function vpac_bd_connecter() {
  $conn = mysqli_connect(BD_SERVER, BD_USER, BD_PASS, BD_NAME);
  if ($conn !== FALSE) {
      //mysqli_set_charset() définit le jeu de caractères par défaut à utiliser lors de l'envoi
      //de données depuis et vers le serveur de base de données.
      mysqli_set_charset($conn, 'utf8') 
      or vpac_bd_erreur_exit('<h4>Erreur lors du chargement du jeu de caractères utf8</h4>');
      return $conn;     // ===> Sortie connexion OK
  }
  // Erreur de connexion
  // Collecte des informations facilitant le debugage
  $msg = '<h4>Erreur de connexion base MySQL</h4>'
          .'<div style="margin: 20px auto; width: 350px;">'
          .'BD_SERVER : '. BD_SERVER
          .'<br>BD_USER : '. BD_USER
          .'<br>BD_PASS : '. BD_PASS
          .'<br>BD_NAME : '. BD_NAME
          .'<p>Erreur MySQL numéro : '.mysqli_connect_errno()
          //appel de htmlentities() pour que les éventuels accents s'affiche correctement
          .'<br>'.htmlentities(mysqli_connect_error(), ENT_QUOTES, 'ISO-8859-1')  
          .'</div>';
  vpac_bd_erreur_exit($msg);
}

/**
 * Arrêt du script si erreur base de données 
 *
 * Affichage d'un message d'erreur, puis arrêt du script
 * Fonction appelée quand une erreur 'base de données' se produit :
 *  - lors de la phase de connexion au serveur MySQL
 *  - ou indirectement lorsque l'envoi d'une requête échoue
 *
 * @param string $msg Message d'erreur à afficher
 */
function vpac_bd_erreur_exit($msg) {
  ob_end_clean();	// Suppression de tout ce qui a pu être déja généré

  echo    '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">',
          '<title>Erreur base de données</title>',
          '<style>',
              'table{border-collapse: collapse;}td{border: 1px solid black;padding: 4px 10px;}',
          '</style>',
          '</head><body>',
          $msg,
          '</body></html>';
  exit(1);  // ==> ARRET DU SCRIPT
}

/**
 * Gestion d'une erreur de requête à la base de données.
 *
 * A appeler impérativement quand un appel de mysqli_query() échoue 
 * Appelle la fonction xx_bd_erreurExit() qui affiche un message d'erreur puis termine le script
 *
 * @param object $bd  Connecteur sur la bd ouverte
 * @param string $sql Requête SQL provoquant l'erreur
 */
function vpac_bd_erreur($bd, $sql) {
  $errNum = mysqli_errno($bd);
  $errTxt = mysqli_error($bd);

  // Collecte des informations facilitant le debugage
  $msg =  '<h4>Erreur de requête</h4>'
          ."<pre><b>Erreur mysql :</b> $errNum"
          ."<br> $errTxt"
          ."<br><br><b>Requête :</b><br> $sql"
          .'<br><br><b>Pile des appels de fonction</b></pre>';

  // Récupération de la pile des appels de fonction
  $msg .= '<table>'
          .'<tr><td>Fonction</td><td>Appelée ligne</td>'
          .'<td>Fichier</td></tr>';

  $appels = debug_backtrace();
  for ($i = 0, $iMax = count($appels); $i < $iMax; $i++) {
      $msg .= '<tr style="text-align: center;"><td>'
              .$appels[$i]['function'].'</td><td>'
              .$appels[$i]['line'].'</td><td>'
              .$appels[$i]['file'].'</td></tr>';
  }

  $msg .= '</table>';

  vpac_bd_erreur_exit($msg);	// ==> ARRET DU SCRIPT
}

/**
 * Obtenir l'ensemble des résultats d'une requête SQL
 * 
 * @param string $sql Requête SQL
 * @param object $bd  Connecteur sur la bd ouverte
 */
function vpac_get_array_from_sql($sql, $bd) {
  $res = mysqli_query($bd,$sql) or vpac_bd_erreur($bd,$sql);
  $all_results = array();
  while($datas_res = mysqli_fetch_assoc($res)) {
    $all_results[] = $datas_res;
  }
  mysqli_free_result($res);
  
  return $all_results;
}
?>