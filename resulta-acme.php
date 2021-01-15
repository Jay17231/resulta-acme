<?php
/**
 * Plugin Name: Resulta Acme Sports
 * Plugin URI: https://www.resulta.com/
 * Description: Show IDs on all post, page and taxonomy pages.
 * Version: 1.0.0
 * Author: Jay Gajjar
 * Author URI: https://www.resulta.com/
 * License: GPL2
 */

 // If this file is called directly, abort.
 if ( ! defined( 'WPINC' ) ) {
 	die;
 }

 // function to create menu page, and submenu pages.
 function resulta_modifymenu() {

 	// This is the main item for the menu
 	add_menu_page(
 		__( 'Resulta ACME'),
    __( 'Resulta ACME'),
    'manage_options',
    'acme_list',
    'acme_list',
    'dashicons-superhero'
 	);
 }
 add_action( 'admin_menu', 'resulta_modifymenu' );

// Callback function for menu page
function acme_list(){
  echo '<h1> Welcome to the ACME NFL Team Listing! </h1>';
  echo '<h4> Use the shortcode to display the team listings on the desired page. </h4>';
  echo '<hr>';
  echo '<h2>Generate your shortcode:</h2>';
  ?>

  <form method="post">
    <?php settings_fields( 'resulta_acme_options_group' ); ?>
    <label for="acme_conference" style="display:block; margin-bottom:10px"><strong>Choose Conference</strong></label>
    <select id="acme_conference" name="acme_conference" style="display:block; margin-bottom:20px">
      <option selected="selected">All</option>
      <?php
        $conf_list = get_conferences();
        foreach ($conf_list as $conf) {
          echo '<option value="'.$conf.'">'.$conf.'</option>';
        }
      ?>
    </select>
    <label for="acme_conference" style="display:block; margin-bottom:10px"><strong>Choose Division</strong></label>
    <select id="acme_division" name="acme_division" style="display:block; margin-bottom:20px">
      <option selected="selected">All</option>
      <?php
        $div_list = get_division();
        foreach ($div_list as $div) {
          echo '<option value="'.$div.'">'.$div.'</option>';
        }
      ?>
    </select>
    <?php  submit_button("Generate Shortcode"); ?>
  </form>
  <?php
  if(!empty($_POST)){
    switch (true) {
      case ($_POST['acme_division'] == "All" && $_POST['acme_conference'] == "All"):
        echo '<h4>Your choices:</h4>';
        echo 'Division: ' . $_POST['acme_division'] .'<br>';
        echo 'Conference: ' . $_POST['acme_conference'] . '<br>';
        echo '<br><input type="text" id="acme_team_data" name="acme_team_data" value="[acme_team_data]" readonly><br>';
        break;
      case ($_POST['acme_division'] != "All" && $_POST['acme_conference'] == "All"):
        $value = '[acme_team_data division="'.$_POST['acme_division'].'"]';
        echo '<h4>Your choices:</h4>';
        echo 'Division: ' . $_POST['acme_division'] .'<br>';
        echo 'Conference: ' . $_POST['acme_conference'] . '<br>';
        echo '<br><input size="250" type="text" id="acme_team_data" name="acme_team_data" value="'.htmlentities($value).'" readonly><br>';
        break;
      case ($_POST['acme_division'] == "All" && $_POST['acme_conference'] != "All"):
        $value = '[acme_team_data conference="'.$_POST['acme_conference'].'"]';
        echo '<h4>Your choices:</h4>';
        echo 'Division: ' . $_POST['acme_division'] .'<br>';
        echo 'Conference: ' . $_POST['acme_conference'] . '<br>';
        echo '<br><input size="100" type="text" id="acme_team_data" name="acme_team_data" value="'.htmlentities($value).'" readonly><br>';
        break;
      default:
        $value = '[acme_team_data conference="'.$_POST['acme_conference'].'" division="'.$_POST['acme_division'].'"]';
        echo '<h4>Your choices:</h4>';
        echo 'Division: ' . $_POST['acme_division'] .'<br>';
        echo 'Conference: ' . $_POST['acme_conference'] . '<br>';
        echo '<br><input size="100" type="text" id="acme_team_data" name="acme_team_data" value="'.htmlentities($value).'" readonly><br>';
        break;
    }
  }
}

function fetch_team_data(){
  $request = wp_remote_get('http://delivery.chalk247.com/team_list/NFL.JSON?api_key=74db8efa2a6db279393b433d97c2bc843f8e32b0');

  if( is_wp_error( $request ) ) {
  	return false;
  }
  $body = wp_remote_retrieve_body($request);
  $team_data = json_decode($body);
  return $team_data;
}

// Get the conferences
function get_conferences(){
  $team_data = fetch_team_data();
  $conferences = array();
  foreach( $team_data->results->data->team as $team ) {
    if(! in_array($team->conference, $conferences)){
        array_push($conferences, $team->conference);
    }
  }
  return $conferences;
}

// Get the conferences
function get_division(){
  $team_data = fetch_team_data();
  $divisions = array();
  foreach( $team_data->results->data->team as $team ) {
    if(! in_array($team->division, $divisions)){
        array_push($divisions, $team->division);
    }
  }
  return $divisions;
}

// Method to create the shortcode, and assign the table data to it
function resulta_acme_shortcode($atts){

  $conf_arr = get_conferences();
  $div_arr = get_division();
  if (!empty($atts['division']) && (!in_array($atts['division'], $div_arr) || str_replace(" ", "", $atts['division']) == "")){
    return "Incorrect <b>Division</b> Attribute in Resulta Acme Shortcode";
  }
  if (!empty($atts['conference']) && (!in_array($atts['conference'], $conf_arr) || str_replace(" ", "", $atts['conference']) == "")){
    return "Incorrect <b>Conference</b> Attribute in Resulta Acme Shortcode";
  }

  $team_data = fetch_team_data();
  if(!empty( $team_data )) {
    $team_table = '<table style="width:100%">';

    // Declare the header column
    $team_table .= '<thead style="background:#ff6b00; color:#fff">';
    $team_table .= '<tr>';
    foreach( $team_data->results->columns as $column ) {
      $team_table .= '<th>';
        $team_table .= $column;
      $team_table .= '</th>';
    }
    $team_table .= '</tr>';
    $team_table .= '</thead>';

    // Populate the rest of the data based on the shortcode parameters
    $check_val = $team_table;
    foreach( $team_data->results->data->team as $team ) {
      if(empty($atts)){
        $team_table .= '<tr>';
        foreach($team as $key => $value){
          $team_table .= '<td>';
          $team_table .= $value;
          $team_table .= '</td>';
        }
        $team_table .= '</tr>';
      }else if((!empty($atts['division']) && $team->division == $atts['division']) && empty($atts['conference'])){
        $team_table .= '<tr>';
        foreach($team as $key => $value){
          $team_table .= '<td>';
          $team_table .= $value;
          $team_table .= '</td>';
        }
        $team_table .= '</tr>';
      }
      else if((!empty($atts['conference']) && $team->conference == $atts['conference']) && empty($atts['division'])){
        $team_table .= '<tr>';
        foreach($team as $key => $value){
          $team_table .= '<td>';
          $team_table .= $value;
          $team_table .= '</td>';
        }
        $team_table .= '</tr>';
      }
      else if((!empty($atts['division']) && !empty($atts['conference'])) && $team->conference == $atts['conference'] && $team->division == $atts['division']){
        $team_table .= '<tr>';
        foreach($team as $key => $value){
          $team_table .= '<td>';
          $team_table .= $value;
          $team_table .= '</td>';
        }
        $team_table .= '</tr>';
      }
    }

    $team_table .= '</table>';
  }
  return $team_table;
}
add_shortcode( 'acme_team_data', 'resulta_acme_shortcode' );
