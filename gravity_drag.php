<?php
/*
 * Plugin Name: Gravity Drag
 * Plugin URI: 
 * Description: Manually update GF entries by dragging and dropping 
 * Version: 0.1
 * Author: Rochelle Victor
 * Author URI: https://runawaypeacock.com
 * License: GPL3+
 */

if(!defined('ABSPATH')) exit;

//include css
function enqueue_custom_styles() {
    wp_enqueue_style('plugin-style', plugin_dir_url(__FILE__) . '/includes/css/gf_drag.css?version=1.26', array(), '1.0', 'all');
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');

//Enqueue js
function draggable_add_script() {
    wp_enqueue_script('draggable-script', plugins_url('/includes/js/draggable-script.js?version=2.38', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-autocomplete'));
    wp_localize_script( 'draggable-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}
add_action('wp_enqueue_scripts', 'draggable_add_script');

//shortcode to load drag and drop interface
add_shortcode( 'gf_drag', 'gfdrag' );
function gfdrag($atts = array()){
    $details = shortcode_atts( array(
        'form_id'=>'12',
        'field_id'=>'12',
        'max_col'=>4,
        'min_width'=>'250',
        'label_ids'=>'',
        'conditions'=>'1-is-Hawk'
    ), $atts );
    $form = GFAPI::get_form($details['form_id']);
    
    $field_exists = false;
    foreach ( $form['fields'] as $field ) {
        if($field->id == $details['field_id']){
            $field_exists = true; 
            if($field->type == 'checkbox'){
                $type='multiselect';
            }else{
                $type='select';
            }
            //get choice values
            if(count($field['choices']) == 0 || empty($field['choices']) ){
                 return '<h3 style="text-align:center;padding:20px;">The field id selected does not contain choices. Try using another field id.</h3>';
            }else{
                if($field->{'gppa-choices-enabled'} == "true"){
                    $hydrated_field = gp_populate_anything()->populate_field( $field, $form, "2" );
                    $choices = rgars( $hydrated_field, 'field/choices' ) ;
                }else{
                    $choices = $field->choices; 
                }
                $count_choices = count($choices);
            }
        }
    }
    if($field_exists == false){
        return '<h3 style="text-align:center;padding:20px;">This form or field id does not exist.</h3>';
    }
    $form_id=$details['form_id'];

    if(empty($details['conditions'])){
        $search_criteria = array(
            'status'        => 'active'
        );
    }else{
        $search_criteria = array(
            'status'        => 'active'
        );
        $conditions=explode(",",$details['conditions']);
        $b=0;
        foreach($conditions as $condition){
            $search_condition=explode("-",$condition);
            $search_criteria['field_filters'][$b]['key'] = $condition[0];
            $search_criteria['field_filters'][$b]['operator'] = $condition[1];
            $search_criteria['field_filters'][$b]['value'] = $condition[2];
            $b++;
        }
    }
    $paging = array( 'offset' => 0, 'page_size' => GFAPI::count_entries($form_id) );
    $total_count = 0;
    $sorting =array('key' => 'date_created', 'direction' => 'ASC');
    $entries = GFAPI::get_entries( $form_id, $search_criteria,$sorting, $paging, $total_count );
    $label_ids=explode(" ",$details['label_ids']);

    foreach($entries as $entry){

        for($c=0;$c<count($label_ids);$c++){
            $label[$c]=$entry[ str_replace(array("{", "}"), "", $label_ids[$c]) ];
        } 
        if(strlen(trim(implode(" ",$label))) == 0 || empty(implode(" ",$label))){
            $label=array($entry['id']);
        }
        if(empty($entry[ $details['field_id'] ]) ){
            $array[ 'unassigned' ][]='<a data-type="order" id="'.$entry['id'].'" href="">'.implode(" ",$label).'</a>';
            for($x=0;$x<$count_choices;$x++) {
                if($entry[ $details['field_id'].'.'.($x + 1)] == $choices[$x]['value'] ){
                        $check=true;
                        $array[ $choices[$x]['value'] ][]='<a data-type="order" id="'.$entry['id'].'" href="">'.implode(" ",$label).'</a><button class="revert"><span class="dashicons dashicons-no"></span></span></button>';
                }
            }
        }else{
            $check=false;
            for($x=0;$x<$count_choices;$x++) {
                if($entry[ $details['field_id'] ] == $choices[$x]['value'] ){
                        $check=true;
                        $array[ $choices[$x]['value'] ][]='<a data-type="order" id="'.$entry['id'].'" href="">'.implode(" ",$label).'</a><button class="revert"><span class="dashicons dashicons-no"></span></span></button>';
                }
            }
            if($check == false){
                $array[ 'unassigned' ][]='<a data-type="order" id="'.$entry['id'].'" href="">'.implode(" ",$label).'</a>';
            }
        }
       
    }
    $width = ((100/(int)$details['max_col']) - 2);
    //unassigned
        $drag_tables.='
        <div id="gf_drag_search">
            <div id="drag_search_container">
                <div class="sortx">
                    <input type="text" id="drag_search" placeholder="Search for names.." title="Type in a name">
                    <div class="row_count"></div>
                    <table class="tables_ui t_draggable '.$type.'" id="t_draggable">
                    <thead id="t_sortable_fixed_head">
                        <tr class="header">
                            <th>Unassigned</th>
                        </tr>
                    </thead>
                    <tbody id="" class="t_sortable">
                        <tr>
                            <td style="background-image: linear-gradient(45deg, #999 14.29%, #ffffff 14.29%, #ffffff 50%, #999 50%, #999 64.29%, #ffffff 64.29%, #ffffff 100%);background-size: 9.90px 9.90px;"></td>
                        </tr>
                        ';
                        foreach($array['unassigned'] as $unassigned){
                           $drag_tables.='
                           <tr class="ui-sortable-handle">
                                <td>'.$unassigned.'
                                </td>
                            </tr>';
                        }
                        
                    $drag_tables.='
                        
                    </tbody>';
                    if($type=='select'){
                        $drag_tables.='
                        <tfoot></tfoot>';   
                    }else{
                         $drag_tables.='
                        <tfoot style="display:none"></tfoot>';
                    }
                
                $drag_tables.='
                </table>
            
                <div class="middle">
                    <form class="dragform">
                        <input id="draggable_entry_id" name="df_entry" type="hidden">
                        <input id="count_unassigned" value="1" type="hidden">
                        <input id="draggable_group_name" name="df_group" type="hidden">
                        <input id="draggable_type_name" name="df_type" type="hidden">
                        <input id="draggable_field_id" name="df_field" type="hidden" value="'.$details['field_id'].'">
                        <p><input type="hidden" name="action" value="draggable_action" />
                    '.wp_nonce_field('draggable_action', '_df_nonce', true, false).'</p>
                        <p id="mess"></p>
                    </form>
                </div>
                <div id="df-msg"></div>
            </div>
        </div>';
    $drag_tables.='
    
        <section class="group_container"  > 
            <input class="searchy" placeholder="Search All" type="text" id="searchp" style="min-width:'.$details['min_width'].'px"/>
            <div class="row">
            <div id="gd-msg"></div>';
        $hex_color=array('#ea9999','#f9cb9c','#b6d7a8','#a2c4c9','#b4a7d6','#f4cb86','#97a288','#9bb3d9','#de976c','#dc5e5e','#c6e3de','#badca8','#f7f0a3','#f9c2a3','#f9a3ae','#ffcfc9','#ffc8aa','#ffe5a0','#d4edbc','#bfe1f6','#ea9999','#f9cb9c','#b6d7a8','#a2c4c9','#b4a7d6','#f4cb86','#97a288','#9bb3d9','#de976c','#dc5e5e','#c6e3de','#badca8','#f7f0a3','#f9c2a3','#f9a3ae','#ffcfc9','#ffc8aa','#ffe5a0','#d4edbc','#bfe1f6');
        for($i=0;$i<$count_choices;$i++) {
            //add random colors
            $min_rgb_value = 178;
            $red[$i] = mt_rand($min_rgb_value, 255);
            $green[$i] = mt_rand($min_rgb_value, 255);
            $blue[$i] = mt_rand($min_rgb_value, 255);
            //$hex_color[$i] = sprintf("#%02x%02x%02x", $red[$i], $green[$i], $blue[$i]);
            //$hex_color[$i] = '#c6dcf5';
            $color_index = $i % count($hex_color);
            $hex_color[$color_index];

            //get choice values
            if($choices[$i]['value'] == $detail){
                $select ="checked";
            }else{
                $select = "";
            }
            
            //buckets
            $drag_tables.='
            <div class="table_container" >
  
                <table class="groups tables_ui '.$type.'" id="t_draggable'.$i.'" >
                    <thead style="background:'.$hex_color[$i].';">
                        <tr>
                            <th class="edit group_name"><b>'.$choices[$i]['text'].'</b>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="'.$choices[$i]['value'].'" data-option="'.$i.'" class="t_sortable ui-sortable" >
                        <tr class="ui-sortable-handle">
                            <td style="color:black;text-align:center;font-weight:bold;background-image: linear-gradient(45deg, '.$hex_color[$i].' 14.29%, #ffffff 14.29%, #ffffff 50%, '.$hex_color[$i].' 50%, '.$hex_color[$i].' 64.29%, #ffffff 64.29%, #ffffff 100%);background-size: 9.90px 9.90px;">
                            Drop Here</td>
                        </tr>';
                        for($y=0;$y < count( $array[ $choices[$i]['value'] ] );$y++){
                            $drag_tables.='
                            <tr>
                                <td>'.$array[ $choices[$i]['value'] ][$y].'</td>
                            </tr>';
                        }

                $drag_tables.='    
                        
                    </tbody>
                </table>
            
            </div>';  
        }
        $drag_tables.='
        </div>
        </section>
    ';
    
    return $drag_tables;
}
add_action( 'wp_ajax_draggable_action', 'ajax_draggable_action_callback' );
add_action( 'wp_ajax_nopriv_draggable_action', 'ajax_draggable_action_callback' );

//add ajax to update form entry

function ajax_draggable_action_callback() {
    $error = '';
    $status = 'error';
    if (!wp_verify_nonce($_POST['_df_nonce'], $_POST['action'])) {
        $error = 'Verification error, try again.';
    }else {
        $error = "Success";
        $entry_id = htmlspecialchars($_POST['df_entry'], ENT_QUOTES);
        $group_id = htmlspecialchars($_POST['df_group'], ENT_QUOTES | ENT_HTML401);
        $group_field_id = htmlspecialchars($_POST['df_field'], ENT_QUOTES);

        if ( !empty($entry_id) ) {
            if(GFAPI::entry_exists( $entry_id )){
                $entry = GFAPI::get_entry( $entry_id );
                $form = GFAPI::get_form($entry['form_id']);
                foreach ( $form['fields'] as $field ) {
                    if($field->id == $group_field_id ){
                        if($field->{'gppa-choices-enabled'} == "true"){
                            $hydrated_field = gp_populate_anything()->populate_field( $field, $form, "2" );
                            $choices = rgars( $hydrated_field, 'field/choices' ) ;
                        }else{
                            $choices = $field->choices; 
                        } 
                        if($field->type == 'checkbox'){
                            if((int)$group_id < 0){
                                $entry[strval($group_field_id.'.'.(((int)$group_id * -1) + 1) )] = "" ;
                            }else{
                                $entry[strval($group_field_id.'.'.((int)$group_id + 1))] = $choices[$group_id]['value'] ;
                            }
                        }else{
                            $entry[$group_field_id] = $choices[$group_id]['value'];
                        }        
                    }
                }
                if(!empty($group_field_id)){
                    $result = GFAPI::update_entry( $entry );
                }
            }
            $status = 'success';
            $error = $sendmsg;
         
        }else {
            $error = 'Some errors occurred.';
        }
    }
    $resp = array('status' => $status, 'errmessage' => $error);
    header( "Content-Type: application/json" );
    echo json_encode($resp);
    die();
}
?>
