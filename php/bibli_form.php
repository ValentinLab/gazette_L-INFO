<?php
// ----------------------------------------
// Gestion des formulaires
// ----------------------------------------

// ----- Affichage sous forme de tableau -----

/**
 * Afficher un input dans une ligne de tableau
 * 
 * @param string  $label       Label à afficher
 * @param string  $name        Nom de l'input
 * @param string  $value       Valeur de l'input
 * @param boolean $required    Champ obligatoire ou non
 * @param string  $type        Type d'input
 * @param string  $placeholder Placeholder de l'input
 */
function vpac_print_table_form_input($label, $name, $value, $required = false, $type = 'text', $placeholder = '') {
  $placeholder_val = (!empty($placeholder)) ? " placeholder=\"{$placeholder}\"" : '';
  $required_val = ($required) ?  ' required' : '';

  echo '<tr>',
         '<td><label for="', $name, '">', $label, ' :</label></td>',
         '<td><input type="', $type, '" name="', $name, '" id="', $name, '" value="', $value, '"', $placeholder_val, $required_val, '></td>',
       '</tr>';
}

/**
 * Affichage d'un champ texte invisible dans une ligne de tableau
 * 
 * @param string $name  Nom de l'input
 * @param mixed  $value Valeur de l'input
 */
function vpac_print_table_form_invisible_input($name, $value) {
  echo '<tr style="display: none">',
         '<td colspan="2">';
           vpac_print_invisible_input($name, $value);
         echo '</td>',
       '</tr>';
}

/**
 * Affichage d'un textarea dans une ligne de tableau
 * 
 * @param string $name Nom de l'input
 * @param int $rows Nombre de lignes du textarea
 * @param int $cols Nombre de lignes du  textarea
 */
function vpac_print_table_form_textarea($name, $rows = 10, $cols = 50, $required = false) {
  $required_val = ($required) ?  ' required'  : '';
  echo '<tr>',
          '<td colspan="2">',
            '<textarea name="', $name, '" rows="', $rows, '" cols="', $cols,'" ', $required_val, '></textarea>',
          '</td>',
        '</tr>';
}

/**
 * Afficher un select dans une ligne de tableau
 * 
 * @param string $label       Label à afficher
 * @param string $name        Nom du select
 * @param array  $values      Valeurs du select
 * @param mixed  $default_day Valeur sélectionnée par défaut
 */
function vpac_print_table_form_select($label, $name, $values, $default_value) {
  echo '<tr>',
    '<td>', $label, ' :</td>',
    '<td>', vpac_print_list($name, $values, $default_value), '</td>',
  '</tr>';
}

/**
 * Afficher un select avec des nombres dans une ligne de tableau
 * 
 * @param string $label       Label à afficher
 * @param string $name        Nom du select
 * @param int    $start       Valeur de départt
 * @param int    $end         Valeur de fin
 * @param int    $step        Pas d'incrémentation
 * @param int    $default_day Nombre sélectionné par défaut
 */
function vpac_print_table_form_select_number($label, $name, $start, $end, $step, $default_value) {
  echo '<tr>',
    '<td>', $label, ' :</td>',
    '<td>', vpac_print_list_number($name, $start, $end, $step, $default_value), '<td>',
  '</tr>';
}

/**
 * Afficher un choix de date dans un tableau
 * Trois select sont  affichés : jour / mois / année
 * 
 * @param string $label         Label à afficher
 * @param string $name          Nom des select (suffixés par _j/_m/_a)
 * @param int    $start_year    Année de départ à afficher
 * @param int    $end_year      Année de fin à afficher
 * @param int    $default_day   Jour sélectionné par défaut (le jour actuel pour 0)
 * @param int    $default_month Mois sélectionné par défaut (le mois actuel pour 0)
 * @param int    $default_year  Année sélectionnée par défaut (l'année actuelle pour 0)
 * @param int    $step          Pas d'incrément pour l'année
 */
function vpac_print_table_form_date($label, $name, $start_year, $end_year, $default_day = 0, $default_month = 0, $default_year  = 0, $step = -1) {
  echo  '<tr><td>', $label, ' :</td><td>';
  vpac_print_list_date($name, $start_year, $end_year, $default_day, $default_month, $default_year, $step);
  echo '</td></tr>';
}

/**
 * Afficher plusieurs checkboxs dans un tableau
 * 
 * @param array   $names    Tableau contenant les noms des checkboxs
 * @param array   $values   Tableau contenant les valeurs des checkboxs
 * @param array   $checked  Tableau contenant des booléans pour indiquer si la checkbox est cochée
 * @param array   $labels   Tableau contenant les labels des checkboxs
 * @param boolean $required Champ obligatoire ou non
 */
function  vpac_print_table_form_checkbox($names, $values, $checked, $labels, $required) {
  $radio_numbers = count($names);

  echo '<tr><td colspan="', $radio_numbers, '">';
    vpac_print_checkbox($radio_numbers, $names, $values, $checked, $labels, $required);
  echo '</td></tr>';
}

/**
 * Affihcer plusieurs boutons radio dans un tableau
 * 
 * @param string  $main_    Label principal
 * @param string  $name     Nom des boutons radio
 * @param array   $values   Tableau contenant les valeurs des boutons radio
 * @param mixed   $default  Valeur par défaut
 * @param array   $labels   Tableau contenant les labels des boutons radio
 * @param boolean $required Champ obligatoire ou non
 */
function vpac_print_table_form_radio($main_label, $name, $values, $default, $labels, $required) {
  echo '<tr>',
          '<td>', $main_label, '</td>',
          '<td>';
            vpac_print_radio($name, $values, $default, $labels, $required);
  echo '</td></tr>';
}

/**
 * Afficher des boutons dans un tableau
 * 
 * @param array $types  Tableau contenant les types des boutons
 * @param array $values Tableau contenant les valeurs des boutons
 * @param array $names  Tableau contenant le nom des boutons
 */
function vpac_print_table_form_button($types, $values, $names) {
  echo '<tr><td colspan="2">';
    for($i = 0, $btn_number = count($types); $i < $btn_number; ++$i) {
      vpac_print_input_btn($types[$i], $values[$i], $names[$i]);
    }
  echo '</td></tr>';
}

// ----- Affichage des élements -----

/**
 * Afficher un select
 * 
 * @param string $name        Nom du select
 * @param array  $values      Valeurs du select
 * @param mixed  $default_day Valeur sélectionnée par défaut
 */
function vpac_print_list($name, $values, $default_value) {
  echo '<select name="', $name, '">';
    foreach($values as $key => $val) {
      $selected = ($default_value == $val) ? ' selected' : '';
      echo '<option value="', $key, '"', $selected, '>', $val, '</option>';
    }
  echo '</select>';
}

/**
 * Afficher un select avec des nombres
 * 
 * @param string $name        Nom du select
 * @param int    $start       Valeur de départt
 * @param int    $end         Valeur de fin
 * @param int    $step        Pas d'incrémentation
 * @param int    $default_day Nombre sélectionné par défaut
 */
function vpac_print_list_number($name, $start, $end, $step, $default_value) { 
  $arr = range($start, $end, $step);
  vpac_print_list($name, array_combine($arr, $arr), $default_value);
}

/**
 * Afficher un select avec des mois
 * 
 * @param string $name        Nom du select
 * @param string $default_day Mois sélectionné par défaut
 */
function vpac_print_list_months($name, $default_value) {
  $arr = vpac_get_months();
  vpac_print_list($name, $arr, $arr[$default_value]);
}

/**
 * Afficher trois selects avec des jours / mois / années
 * 
 * @param string $name          Nom des select (suffixés par _j/_m/_a)
 * @param int    $start_year    Année de départ à afficher
 * @param int    $end_year      Année de fin à afficher
 * @param int    $default_day   Jour sélectionné par défaut (le jour actuel pour 0)
 * @param int    $default_month Mois sélectionné par défaut (le mois actuel pour 0)
 * @param int    $default_year  Année sélectionnée par défaut (l'année actuelle pour 0)
 * @param int    $step          Pas d'incrément pour l'année
 */
function vpac_print_list_date($name, $start_year, $end_year, $default_day = 0, $default_month = 0, $default_year  = 0, $step = -1) {
  if($default_day == 0 || $default_month == 0 || $default_year == 0) {
    $current_date = getdate();
    if($default_day == 0) {
      $default_day = $current_date['mday'];
    }
    if($default_month == 0) {
      $default_month = $current_date['mon'];
    }
    if($default_year == 0) {
      $default_year = $current_date['year'];
    }
  }

  vpac_print_list_number("{$name}_j", 1, 31, 1, $default_day);
  vpac_print_list_months("{$name}_m", $default_month);
  vpac_print_list_number("{$name}_a", $start_year, $end_year, -1, $default_year);
}

/**
 * Afficher plusieurs checkboxs
 * 
 * @param int   $checkbox_numbers Nombre de checkboxs à afficher
 * @param array $names            Tableau contenant les noms des checkboxs
 * @param array $values           Tableau contenant les valeurs des checkboxs
 * @param array $checked          Tableau contenant des booléans pour indiquer si la checkbox est cochée
 * @param array $labels           Tableau contenant les labels des checkboxs
 * @param array $required         Tableau indiquant si une checkbox est obligatoire ou non
 */
function vpac_print_checkbox($radio_numbers, $names, $values, $checked, $labels, $required) {
  $check_val = '';
  for($i = 0; $i < $radio_numbers; ++$i) {
    $check_val = ($checked[$i]) ? ' checked' : '';
    $required_val = ($required[$i]) ? ' required' : '';
    echo '<input type="checkbox" name="', $names[$i], '" id="', $names[$i], '" value="', $values[$i], '"', $check_val, $required_val, '><label for="', $names[$i], '">', $labels[$i], '</label>';
  }
}

/**
 * Affihcer plusieurs boutons radio
 * 
 * @param string  $name     Nom des boutons radio
 * @param array   $values   Tableau contenant les valeurs des boutons radio
 * @param mixed   $default  Valeur par défaut
 * @param array   $labels   Tableau contenant les labels des boutons radio
 * @param boolean $required Champ obligatoire ou non
 */
function vpac_print_radio($name, $values, $default, $labels, $required) {
  $checkbox_numbers = count($values);
  $check_val = '';
  $required_val = ($required) ? ' required' : '';
  for($i = 0; $i < $checkbox_numbers; ++$i) {
    $check_val = ($values[$i] == $default) ? ' checked' : '';
    echo '<input type="radio" name="', $name, '" id="', $name, $i, '" value="', $values[$i], '" ',$check_val, $required_val, '><label for="', $name, $i, '"s>', $labels[$i], '</label> ';
  }
}

/**
 * Affichage d'un champ texte invisible dans une ligne de tableau
 * 
 * @param string $name  Nom de l'input
 * @param mixed  $value Valeur de l'input
 */
function vpac_print_invisible_input($name, $value) {
  echo '<input type="hidden" name="', $name, '" value="', $value, '" required></td>';
}

/**
 * Afficher des boutons
 * 
 * @param string $type      Type du bouton
 * @param mixed  $value     Valeur du boutons
 * @param string $name      Nom du bouton
 */
function vpac_print_input_btn($type, $value, $name) {
  $name = (!empty($name)) ? " name=\"$name\"" : '';
  echo '<input type="', $type, '" value="',  $value, '"', $name, '>';
}

/**
 * Afficher les erreurs d'un forrmulaire
 * 
 * @param array  $errors    Tableau contenant les erreurs du formulaire
 * @param string $text      Message à afficher avant les erreurs
 * @param bool   $full_size Le message aura une taille de 100% et non 705px
 */
function vpac_print_form_errors($errors, $text = '', $full_size = false) {
  $text = (!empty($text)) ? "<p>$text</p>" : '';
  $errors_id = ($full_size) ? 'errors-full-size' : 'errors';

  if(!empty($errors)) {
    echo '<div id="', $errors_id, '" class="statusBox">', $text;
    if(count($errors) > 1) {
      echo '<ul>';
        foreach($errors as $err) {
          echo '<li>', $err, '</li>';
        }
      echo '</ul>';
    } else {
      echo '<p>',$errors[0], '</p>';
    }
    echo '</div>';
  }
}
?>