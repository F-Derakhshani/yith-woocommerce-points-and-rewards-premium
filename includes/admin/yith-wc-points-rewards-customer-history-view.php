<?php

/**
 * YITH WooCommerce Points and Rewards Customer History List Table
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */


class YITH_WC_Points_Rewards_Customer_History_List_Table extends WP_List_Table {



    public function __construct( $args = array() ) {
        parent::__construct( array() );
    }

    function get_columns() {
        $columns = array(
            'id'           => __( 'ID', 'yith-woocommerce-points-and-rewards' ),
            'action'       => __( 'Action', 'yith-woocommerce-points-and-rewards' ),
            'order_id'     => __( 'Order Num.', 'yith-woocommerce-points-and-rewards' ),
            'amount'       => __( 'Amount', 'yith-woocommerce-points-and-rewards' ),
            'date_earning' => __( 'Date', 'yith-woocommerce-points-and-rewards' ),
        );
        return $columns;
    }

    function prepare_items() {
	    global $wpdb, $_wp_column_headers;
	    $screen                = get_current_screen();
	    $columns               = $this->get_columns();
	    $hidden                = array();
	    $sortable              = $this->get_sortable_columns();
	    $this->_column_headers = array( $columns, $hidden, $sortable );

	    $user_id = ! empty( $_GET["user_id"] ) ? $_GET["user_id"] : 0;

	    $orderby = ! empty( $_GET["orderby"] ) ? $_GET["orderby"] : 'date_earning';
	    $order   = ! empty( $_GET["order"] ) ? $_GET["order"] : 'DESC';

	    $order_string = 'ORDER BY ' . $orderby . ' ' . $order;

	    $table_name = $wpdb->prefix . 'yith_ywpar_points_log';

	    $query = "SELECT ywpar_points.* FROM $table_name as ywpar_points where user_id = $user_id $order_string";

	    $totalitems = $wpdb->query( $query );

	    $perpage = 25;
	    //Which page is this?
	    $paged = ! empty( $_GET["paged"] ) ? $_GET["paged"] : '';
	    //Page Number
	    if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
		    $paged = 1;
	    }
	    //How many pages do we have in total?
	    $totalpages = ceil( $totalitems / $perpage );
	    //adjust the query to take pagination into account
	    if ( ! empty( $paged ) && ! empty( $perpage ) ) {
		    $offset = ( $paged - 1 ) * $perpage;
		    $query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
	    }

	    /* -- Register the pagination -- */
	    $this->set_pagination_args( array(
		    "total_items" => $totalitems,
		    "total_pages" => $totalpages,
		    "per_page"    => $perpage,
	    ) );
	    //The pagination links are automatically built according to those parameters

	    $_wp_column_headers[ $screen->id ] = $columns;
	    $this->items                       = $wpdb->get_results( $query );


    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'id':
                return $item->id;
                break;
            case 'order_id':
               if( $item->order_id != 0 ){
                   return '<a href="' . admin_url( 'post.php?post=' . $item->order_id . '&action=edit' ) . '">' . sprintf( __( 'Order #%d', 'ywrac' ), $item->order_id ) . '</a>';
               }break;
            case 'action':
                return YITH_WC_Points_Rewards()->get_action_label( $item->action );
                break;
            default:
                return (isset( $item->$column_name ) ) ?  $item->$column_name : '';
        }
    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'id'       => array( 'ID', false ),
            'action'   => array( 'action', false ),
            'order_id' => array( 'order_id', false ),
            'amount'   => array( 'amount', false ),
            'date_earning'     => array( 'date_earning', false ),
        );
        return $sortable_columns;
    }

}
