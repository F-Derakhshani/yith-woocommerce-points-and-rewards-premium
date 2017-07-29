<?php

/**
 * YITH WooCommerce Points and Rewards Customers List Table
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */


class YITH_WC_Points_Rewards_Customers_List_Table extends WP_List_Table {



    public function __construct( $args = array() ) {
        parent::__construct( array() );
    }

    function get_columns() {
        $columns = array(
            'user_id'   => __( 'ID', 'yith-woocommerce-points-and-rewards' ),
            'user_info' => __( 'User', 'yith-woocommerce-points-and-rewards' ),
            'points'    => __( 'Subtotal', 'yith-woocommerce-points-and-rewards' ),
            'action'    => __( 'Action', 'yith-woocommerce-points-and-rewards' ),
        );
        return $columns;
    }

    function prepare_items() {

        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $users_per_page = 25;

        $paged = ( isset( $_GET['paged'] ) ) ? $_GET['paged'] : '';

        if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
            $paged = 1;
        }

        $args = array(
            'number' => $users_per_page,
            'offset' => ( $paged-1 ) * $users_per_page,
        );

        if ( $this->is_site_users )
            $args['blog_id'] = $this->site_id;

        if ( isset( $_REQUEST['orderby'] ) ){
            if(  $_REQUEST['orderby'] == 'meta_value_num' ){
                $args['meta_key'] = '_ywpar_user_total_points';
            }
            $args['orderby'] = $_REQUEST['orderby'];
        }

        if ( isset( $_REQUEST['order'] ) ){
            $args['order'] = $_REQUEST['order'];
        }

        $args = $this->add_filter_args( $args );

        $wp_user_search = new WP_User_Query( $args );

        $this->items = $wp_user_search->get_results();
        $this->set_pagination_args( array(
            'total_items' => $wp_user_search->get_total(),
            'per_page' => $users_per_page,
        ) );

    }

    function column_default( $item, $column_name ) {

        switch( $column_name ) {
            case 'user_id':
                return $item->ID;
                break;
            case 'user_info':
                $email = '<a href="mailto:'.$item->user_email.'">'.$item->user_email.'</a>';
                return $item->display_name.'<br>'.$email;
                break;
            case 'points':
                $points = get_user_meta( $item->ID, '_ywpar_user_total_points', true);
                return $points;
                break;
            default:
                return ''; //Show the whole array for troubleshooting purposes
        }

    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'user_id'   => array( 'ID', false ),
            'user_info' => array( 'display_name', false ),
            'points'    => array( 'meta_value_num', false ),
        );
        return $sortable_columns;
    }



    function column_action( $item ) {
        $arg = remove_query_arg( array('paged','orderby','order'));
        $button = '<a href="' . add_query_arg( array( 'action'  => 'update',
                                                      'user_id' => $item->ID
            ), $arg ) . '" class="ywpar_update_points button action">' . __( 'View History', 'yith-woocommerce-points-and-rewards' ) . '</a>';

        return $button;
    }

    /**
     * Adds in any query arguments based on the current filters
     *
     * @since 1.0
     * @param array $args associative array of WP_Query arguments used to query and populate the list table
     * @return array associative array of WP_Query arguments used to query and populate the list table
     */
    private function add_filter_args( $args ) {
        // filter by customer
        if ( isset( $_POST['_customer_user'] ) && $_POST['_customer_user'] > 0 ) {
            $args['include'] = array( $_POST['_customer_user'] );
        }

        return $args;
    }

    /**
     * Extra controls to be displayed between bulk actions and pagination, which
     * includes our Filters: Customers, Products, Availability Dates
     *
     * @see WP_List_Table::extra_tablenav();
     * @since 1.0
     * @param string $which the placement, one of 'top' or 'bottom'
     */
    public function extra_tablenav( $which ) {
        if ( 'top' == $which ) {
	        // Customers, products
	       ;

	        echo '<div class="alignleft actions">';
	        if ( version_compare( WC()->version, '2.7', '<' ) ) {
		        $user_string = '';
		        $customer_id = '';
		        $user = '';
		        if ( ! empty( $_POST['_customer_user'] ) ) {
			        $customer_id = absint( $_POST['_customer_user'] );
			        $user        = get_user_by( 'id', $customer_id );
			        $user_string = esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email );
		        }

		        ?>
				<input type="hidden" class="wc-customer-search" id="customer_user" name="_customer_user" data-placeholder="<?php _e( 'Show All Customers', 'yith-woocommerce-points-and-rewards' ); ?>" data-selected="<?php echo esc_attr( $user_string ); ?>" value="<?php echo $customer_id; ?>" data-allow_clear="true" style="width:200px" />
		        <?php
		        submit_button( __( 'Filter' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );

	        }else{
		        $user_string = '';
		        $user_id = 0;
		        $sel = '';
		        if ( ! empty( $_REQUEST['_customer_user'] ) ) {
					$user_id     = absint( $_REQUEST['_customer_user'] );
					$user        = get_user_by( 'id', $user_id );
					/* translators: 1: user display name 2: user ID 3: user email */
					$user_string = sprintf(
						esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
						$user->display_name,
						absint( $user->ID ),
						$user->user_email
					);
					$sel[$user_id] = $user_string;
		        }


		        yit_add_select2_fields(
			         array(
				        'type'              => 'hidden',
				        'class'             => 'wc-customer-search',
				        'id'                => 'customer_user',
				        'name'              => '_customer_user',
				        'data-placeholder'  => __( 'Show All Customers', 'yith-woocommerce-points-and-rewards' ),
				        'data-allow_clear'  => false,
				        'data-selected'     => $sel,
				        'data-multiple'     => false,
				        'data-action'       => '',
				        'value'             => $user_id,
				        'style'             => 'width:200px',
				        'custom-attributes' => array()
			        )
				);
		        submit_button( __( 'Filter' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );
			}
	        echo '</div>';
        }
    }


}
