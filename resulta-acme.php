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
  echo '<h3> Use the shortcode to display the team listings on the desired page. </h3>';
  echo '<input type="text" id="acme_team_data" name="acme_team_data" value="[acme_team_data]" readonly><br>';
  ?>

  <form method="post">
    <?php settings_fields( 'myplugin_options_group' ); ?>
    <select id="acme_conference" name="acme_conference">
      <option selected="selected">All</option>
      <?php
        $conf_list = get_conferences();
        foreach ($conf_list as $conf) {
          echo '<option value="'.$conf.'">'.$conf.'</option>';
        }
      ?>
    </select>
    <select id="acme_division" name="acme_division">
      <option selected="selected">All</option>
      <?php
        $div_list = get_division();
        foreach ($div_list as $div) {
          echo '<option value="'.$div.'">'.$div.'</option>';
        }
      ?>
    </select>
    <?php  submit_button(); ?>
  </form>
  <?php
  echo $_POST["acme_division"];
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

function resulta_acme_shortcode($atts){

  $team_data = fetch_team_data();
  // var_dump($team_data);
  if(!empty( $team_data )) {
    $team_table = '<table style="width:100%">';
    // Declare the header column
    $team_table .= '<tr>';
    foreach( $team_data->results->columns as $column ) {
      $team_table .= '<th>';
        $team_table .= $column;
      $team_table .= '</th>';
    }
    $team_table .= '</tr>';
    // Populate the rest of the data
    foreach( $team_data->results->data->team as $team ) {
      $team_table .= '<tr>';
      foreach($team as $key => $value){
        if(empty($atts)){
          $team_table .= '<td>';
          $team_table .= $value;
          $team_table .= '</td>';
        }else if(!empty($atts['division']) && $team->division == $atts['division']){
          $team_table .= '<td>';
          $team_table .= $value;
          $team_table .= '</td>';
        }else{
          echo "Incorrect Shortcode - Please Try Again with a correct shortcode";
          return false;
        }
      }
      $team_table .= '</tr>';
    }

    $team_table .= '</table>';
  }
  return $team_table;
}
add_shortcode( 'acme_team_data', 'resulta_acme_shortcode' );
