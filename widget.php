<?php

/* 
 * WPC_Informer_Widget class
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class FT_Informer_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array('classname' => 'ft_informer_wiget', 'description' => __( 'Displays fleetly informer', FT_TEXTDOMAIN) );
		parent::__construct('ft_informer', __('Fleetly informer widget', FT_TEXTDOMAIN), $widget_ops);
	}

	public function widget( $args, $instance ) {
		
                echo $args['before_widget'];

                informers_client();
                
                echo $args['after_widget'];
		
	}
 
} 