<?php

if ( !defined( 'ABSPATH' ) || !defined( 'YITH_YWPAR_VERSION' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements admin features of YITH WooCommerce Points and Rewards
 *
 * @class   YITH_WC_Points_Rewards_Admin
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */
if ( !class_exists( 'YITH_WC_Points_Rewards_Admin' ) ) {

    class YITH_WC_Points_Rewards_Admin {

        /**
         * Single instance of the class
         *
         * @var \YITH_WC_Points_Rewards_Admin
         */
        protected static $instance;

        /**
         * @var $_panel Panel Object
         */
        public $_panel;

        /**
         * @var $_premium string Premium tab template file name
         */
        protected $_premium = 'premium.php';

        /**
         * @var string Premium version landing link
         */
        protected $_premium_landing = 'http://yithemes.com/themes/plugins/yith-woocommerce-points-and-rewards/';

        /**
         * @var string Panel page
         */
        protected $_panel_page = 'yith_woocommerce_points_and_rewards';

        /**
         * @var string Doc Url
         */
        public $doc_url = 'https://yithemes.com/docs-plugins/yith-woocommerce-points-and-rewards/';

        /**
         * @var string name of plugin options
         */
        public $plugin_options = 'yit_ywpar_options';

        /**
         * @var Wp List Table
         */
        public $cpt_obj;

        /**
         * @var array extra_points options
         */
        public $extra_points_options;

        /**
         * Returns single instance of the class
         *
         * @return \YITH_WC_Points_Rewards_Admin
         * @since 1.0.0
         */
        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor
         *
         * Initialize plugin and registers actions and filters to be used
         *
         * @since  1.0.0
         * @author Emanuela Castorina
         */
        public function __construct() {

            $this->create_menu_items();


            //Register plugin to licence/update system
            add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
            add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );
            add_action( 'admin_init', array( $this, 'actions_from_settings_panel' ), 9);
            //Add action links
            add_filter( 'plugin_action_links_' . plugin_basename( YITH_YWPAR_DIR . '/' . basename( YITH_YWPAR_FILE ) ), array( $this, 'action_links' ) );
            add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );

            //Add extra options fields in setting panels
            add_action( 'yit_panel_options-conversion', array( $this, 'admin_options_conversion' ), 10, 2 );
            add_action( 'yit_panel_options-percentual-conversion', array( $this, 'admin_options_percentual_conversion' ), 10, 2 );
            add_action( 'yit_panel_options-extra-points', array( $this, 'admin_options_extra_points' ), 10, 2 );
            add_action( 'yit_panel_points-previous-order', array( $this, 'admin_options_points_previous_order' ), 10, 2 );
            add_action( 'yit_panel_options-import-form', array( $this, 'admin_options_import_form' ), 10, 2 );

            //Set a list of options for extrapoints
            $this->extra_points_options = include( YITH_YWPAR_DIR.'plugin-options/tab-options/extra-points-options.php');

            //Custom styles and javascripts
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 11);

            //Product Categories fields
            add_action( 'product_cat_add_form_fields', array( $this, 'product_cat_add_form_fields' ), 10 );
            add_action( 'product_cat_edit_form_fields', array( $this, 'edit_category_fields' ), 11 );
            add_action( 'created_term', array( $this, 'save_category_fields' ), 10, 3 );
            add_action( 'edit_term', array( $this, 'save_category_fields' ), 10, 3 );

            /* Ajax action for apply points previous order */
            add_action( 'wp_ajax_ywpar_apply_previous_order', array( $this, 'apply_previous_order' ) );
            add_action( 'wp_ajax_nopriv_ywpar_apply_previous_order', array( $this, 'apply_previous_order' ) );

            /* Ajax action for reset points */
            add_action( 'wp_ajax_ywpar_reset_points', array( $this, 'reset_points' ) );
            add_action( 'wp_ajax_nopriv_ywpar_reset_points', array( $this, 'reset_points' ) );

            /* Ajax action for apply points previous order */
            add_action( 'wp_ajax_ywpar_apply_wc_points_rewards', array( $this, 'apply_wc_points_rewards' ) );
            add_action( 'wp_ajax_nopriv_ywpar_apply_wc_points_rewards', array( $this, 'apply_wc_points_rewards' ) );

            /* Add widgets into the dashboard */
            add_action( 'wp_dashboard_setup', array( $this,'ywpar_points_widgets') );

            if(  ! apply_filters( 'ywpar_enable_product_meta', true ) ){
                return;
            }
            //Custom fields for single product
            add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_custom_fields_for_single_products' ) );
            add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_fields_for_single_products' ), 10, 2 );

            //Custom fields for variation
            add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_custom_fields_for_variation_products' ), 14, 3 );
            add_action( 'woocommerce_save_product_variation', array( $this, 'save_custom_fields_for_variation_products' ), 10 );




        }


	    /**
	     * Add csv to mime file type
	     *
	     * @since 1.1.3
	     *
	     * @param $mime_types
	     *
	     * @return mixed
	     */
        public function add_mime_types( $mime_types ) {
            $mime_types['csv'] = 'text/csv';
            $mime_types['txt'] = 'text/plain';
            return $mime_types;
        }

	    /**
	     * Set the settings tabs enabled to shop manager
	     *
	     * @access public
	     * @
	     *
	     * @param $panel_options
	     *
	     * @return array
	     * @since 1.1.3
	     */
        public function admin_panel_options_for_shop_manager( $panel_options ) {
            add_filter( 'option_page_capability_yit_' . $panel_options['parent'] . '_options', array($this,'change_capability') );
            $panel_options['capability'] = 'manage_woocommerce';
            return $panel_options;
        }

        /**
         * Enqueue styles and scripts
         *
         * @access public
         * @return void
         * @since 1.0.0
         */
        public function enqueue_styles_scripts() {
            wp_enqueue_script( 'jquery-ui-datepicker' );

            //load select2
            wp_enqueue_script( 'wc-enhanced-select' );
            wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
            wp_enqueue_style( 'select2', str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/' . 'css/select2.css' );

            //backend enqueue scripts/css
            wp_enqueue_style( 'yith_ywpar_backend', YITH_YWPAR_ASSETS_URL . '/css/backend.css', YITH_YWPAR_VERSION );
            wp_enqueue_script( 'yith_ywpar_admin', YITH_YWPAR_ASSETS_URL . '/js/ywpar-admin' . YITH_YWPAR_SUFFIX . '.js', array( 'jquery', 'jquery-ui-sortable' ), YITH_YWPAR_VERSION, true );
            wp_enqueue_script( 'jquery-blockui', YITH_YWPAR_ASSETS_URL . '/js/jquery.blockUI.min.js', array( 'jquery' ), false, true );

            wp_localize_script( 'yith_ywpar_admin', 'yith_ywpar_admin', array(
                'ajaxurl'                         => admin_url( 'admin-ajax.php' ),
                'apply_previous_order_none'       => wp_create_nonce( 'apply_previous_order' ),
                'apply_wc_points_rewards'         => wp_create_nonce( 'apply_wc_points_rewards' ),
                'reset_points'                    => wp_create_nonce( 'reset_points' ),
                'reset_points_confirm'            => __( 'Are you sure that want reset all points? This process is irreversible', 'yith-woocommerce-points-and-rewards' ),
                'import_points_import_file_empty' => __( 'The file import is empty', 'yith-woocommerce-points-and-rewards' ), //from 1.2.0
                'block_loader'                    => apply_filters( 'yith_ywpar_block_loader_admin', YITH_YWPAR_ASSETS_URL . '/images/block-loader.gif' ),
            ) );
        }

        /**
         * Create Menu Items
         *
         * Print admin menu items
         *
         * @since  1.0.0
         * @author Emanuela Castorina
         */
        private function create_menu_items() {
            // Add a panel under YITH Plugins tab
            add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
            add_action( 'yith_ywpar_customers', array( $this, 'customers_tab' ) );
        }

        /**
         * Add a panel under YITH Plugins tab
         *
         * @return   void
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @use      /Yit_Plugin_Panel class
         * @see      plugin-fw/lib/yit-plugin-panel.php
         */
        public function register_panel() {

            if ( !empty( $this->_panel ) ) {
                return;
            }

            $admin_tabs = apply_filters( 'ywpar_show_admin_tabs', array(
		            'general'      => __( 'Settings', 'yith-woocommerce-points-and-rewards' ),
		            'roles'        => __( 'Member Role', 'yith-woocommerce-points-and-rewards' ),
		            'customers'    => __( 'Customer points', 'yith-woocommerce-points-and-rewards' ),
		            'extra-points' => __( 'Extra Points', 'yith-woocommerce-points-and-rewards' ),
		            'labels'       => __( 'Labels', 'yith-woocommerce-points-and-rewards' ),
		            'expiration'   => __( 'Expiry Settings', 'yith-woocommerce-points-and-rewards' ),
		            'update'       => __( 'Update Email Settings', 'yith-woocommerce-points-and-rewards' ),
		            'messages'     => __( 'Messages', 'yith-woocommerce-points-and-rewards' ),
		            'import'       => __( 'Import', 'yith-woocommerce-points-and-rewards' ),
                )
            );



            $args = apply_filters( 'ywpar_admin_panel_options', array(
                'create_menu_page' => true,
                'parent_slug'      => '',
                'page_title'       => _x( 'Points and Rewards', 'Plugin name in admin page title', 'yith-woocommerce-points-and-rewards' ),
                'menu_title'       => _x( 'Points and Rewards', 'Plugin name in admin WP menu', 'yith-woocommerce-points-and-rewards' ),
                'capability'       => 'manage_options',
                'parent'           => 'ywpar',
                'parent_page'      => 'yit_plugin_panel',
                'page'             => $this->_panel_page,
                'admin-tabs'       => $admin_tabs,
                'options-path'     => YITH_YWPAR_DIR . '/plugin-options'
            ) );

	        //enable shop manager to change Customer points
	        if( YITH_WC_Points_Rewards()->get_option('enabled_shop_manager') == 'yes' ){
		        if( current_user_can('manage_woocommerce') && !current_user_can( 'manage_options' ) ){
			        add_filter( 'option_page_capability_yit_' . $args['parent'] . '_options', array($this,'change_capability') );
			        add_filter( 'yit_plugin_panel_menu_page_capability', array($this,'change_capability') );
			        $args['capability'] = 'manage_woocommerce';
			        $new_admin_tabs     = array( 'customers' => __( 'Customer points', 'yith-woocommerce-points-and-rewards' ) );
			        $args['admin-tabs'] = apply_filters( 'ywpar_show_tab_to_shop_manager', $new_admin_tabs, $admin_tabs );
		        }
	        }

            /* === Fixed: not updated theme  === */
            if ( !class_exists( 'YIT_Plugin_Panel' ) ) {
                require_once( YITH_YWPAR_DIR.'/plugin-fw/lib/yit-plugin-panel.php' );
            }

            $this->_panel = new YIT_Plugin_Panel( $args );

            $this->save_default_options();

        }

        /**
         * Modify the capability
         *
         * @param $capability
         *
         * @return string
         */
        function change_capability( $capability ) {
            return 'manage_woocommerce';
        }

        /**
         * Save default options when the plugin is installed
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function save_default_options() {

            $options                = maybe_unserialize( get_option( 'yit_ywpar_options', array() ) );
            $current_option_version = get_option( 'yit_ywpar_option_version', '0' );
            $forced                 = isset( $_GET['update_ywpar_options'] ) && $_GET['update_ywpar_options'] == 'forced';

            if ( version_compare( $current_option_version, YITH_YWPAR_VERSION, '>=' ) && ! $forced ) {
                return;
            }

            $new_option = array_merge( $this->_panel->get_default_options(), ( array ) $options );
            update_option( 'yit_ywpar_options', $new_option );
            update_option( 'yit_ywpar_option_version', YITH_YWPAR_VERSION );
        }

	    /**
	     * Template for admin section
	     *
	     * @since 1.0.0
	     * @access public
	     * @author Emanuela Castorina
	     *
	     * @param $option
	     * @param $db_value
	     */
        public function admin_options_conversion( $option, $db_value ) {
            include( YITH_YWPAR_TEMPLATE_PATH . '/panel/types/options-conversion.php' );
        }

	    /**
	     * Template for admin section
	     *
	     * @since 1.0.0
	     * @access public
	     * @author Emanuela Castorina
	     *
	     * @param $option
	     * @param $db_value
	     */
        public function admin_options_percentual_conversion( $option, $db_value ) {
            include( YITH_YWPAR_TEMPLATE_PATH . '/panel/types/options-percentual-conversion.php' );
        }

	    /**
	     * Template for extra points field
	     *
	     * @since 1.0.0
	     * @access public
	     * @author Emanuela Castorina
	     *
	     * @param $option
	     * @param $db_value
	     */
        public function admin_options_extra_points( $option, $db_value ) {
            include( YITH_YWPAR_TEMPLATE_PATH . '/panel/types/options-extra-points.php' );
        }

	    /**
	     * Template for apply previous orders
	     *
	     * @since 1.0.0
	     * @access public
	     * @author Emanuela Castorina
	     *
	     * @param $option
	     * @param $db_value
	     */
        public function admin_options_points_previous_order( $option, $db_value ) {
            include( YITH_YWPAR_TEMPLATE_PATH . '/panel/types/points-previous-order.php' );
        }

	    /**
	     * Template for import points
	     *
	     * @since 1.1.3
	     * @access public
	     * @author Emanuela Castorina
	     *
	     * @param $option
	     * @param $db_value
	     */
        public function admin_options_import_form( $option, $db_value ) {
            include( YITH_YWPAR_TEMPLATE_PATH . '/panel/types/options-import-form.php' );
        }

        /**
         * Customers Tab Template
         *
         * Load the customers tab template on admin page
         *
         * @return   void
         * @since    1.0.0
         * @author   Emanuela Castorina
         */
        public function customers_tab() {
            $points = 0;

            if ( isset( $_REQUEST['action'] ) ) {
                $user_id   = $_REQUEST['user_id'];
                $user_info = get_userdata( $user_id );
	            $points = get_user_meta( $user_id, '_ywpar_user_total_points', true );
                if ( $_REQUEST['action'] == 'save' && wp_verify_nonce( $_POST['ywpar_update_points'], 'update_points' ) ) {

                    $new_points     = $_REQUEST['user_points'] + $points;
                    update_user_meta( $user_id, '_ywpar_user_total_points', $new_points );

                    YITH_WC_Points_Rewards()->register_log( $user_id, 'admin_action', '', $_REQUEST['user_points'] );
                    YITH_WC_Points_Rewards_Earning()->extra_points( array( 'points' ), $user_id );
                }

                $link   = remove_query_arg( array( 'action', 'user_id' ) );

                $this->cpt_obj = new YITH_WC_Points_Rewards_Customer_History_List_Table();

            }
            else {
                $this->cpt_obj = new YITH_WC_Points_Rewards_Customers_List_Table();
            }

            $customers_tab = YITH_YWPAR_TEMPLATE_PATH . '/admin/customers-tab.php';
            if ( file_exists( $customers_tab ) ) {
                include_once( $customers_tab );
            }
        }

        /**
         * Add custom fields for single product
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function add_custom_fields_for_single_products() {
            global $thepostid;

            //Compatibility with Multivendor
            if( function_exists( 'yith_get_vendor') ){
                $vendor = yith_get_vendor( 'current', 'user' );
                if ( $vendor->is_valid() && $vendor->has_limited_access() ) {
                    return;
                }
            }

            echo '<div class="options_group">';
            woocommerce_wp_text_input( array(
                'id' => '_ywpar_point_earned',
                'wrapper_class' => 'show_if_simple',
                'label' => __( 'Points Earned', 'yith-woocommerce-points-and-rewards' ),
                'placeholder' => '',
                'desc_tip' => true,
                'description' =>  __('This field allows you to override global and category rules for point collection.
 Leave it blank to make global rules o category rules apply, assign a fixed number of points for this product  (0 for no points) or set a percent value to apply global or category rules according to percentage (200&#37; for double points).','yith-woocommerce-points-and-rewards')
            ) );


            $product = wc_get_product( $thepostid );

            $ywpar_point_earned_dates_from = ( $date = yit_get_prop( $product, '_ywpar_point_earned_dates_from', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';
            $ywpar_point_earned_dates_to   = ( $date = yit_get_prop( $product, '_ywpar_point_earned_dates_to', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';

            echo '<p class="form-field ywpar_point_earned_dates_fields show_if_simple">
                    <label for="_ywpar_point_earned_dates_from">' . __( 'Validity for extra point reward (optional)', 'yith-woocommerce-points-and-rewards' ) . '</label>
                    <input type="text" class="short" name="_ywpar_point_earned_dates_from" id="_ywpar_point_earned_dates_from" value="' . esc_attr( $ywpar_point_earned_dates_from ) . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" style="display: block;float: none; margin-bottom: 20px;" />
                    <input type="text" class="short" name="_ywpar_point_earned_dates_to" id="_ywpar_point_earned_dates_to" value="' . esc_attr( $ywpar_point_earned_dates_to ) . '" placeholder="' . _x( 'To&hellip;', 'placeholder', 'woocommerce' ) . '  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" style="display:block;"/>
                </p>';
            if( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'fixed' ){
                woocommerce_wp_text_input( array(
                    'id' => '_ywpar_max_point_discount',
                    'wrapper_class' => 'show_if_simple',
                    'label' => __( 'Maximum discount', 'yith-woocommerce-points-and-rewards' ),
                    'placeholder' => '',
                    'desc_tip' => true,
                    'description' => __('Maximum discount applicable to this product. You can add a constant value or a percentage value that edits the maximum quantity of points
                 that can be used to get a discount according to product price. This value overrides global and category rules.','yith-woocommerce-points-and-rewards')
                ) );

            }elseif(YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'percentage') {
                //from 1.1.2
                woocommerce_wp_text_input( array(
                    'id' => '_ywpar_redemption_percentage_discount',
                    'wrapper_class' => 'show_if_simple',
                    'label' => __( 'Reward percent discount (%)', 'yith-woocommerce-points-and-rewards' ),
                    'placeholder' => '',
                    'desc_tip' => true,
                    'description' => __('Discount applicable to this product. This option edits redeem percent discount that can be applied on product price and overrides global and category rules.','yith-woocommerce-points-and-rewards')
                ) );
            }

            echo '</div>';

        }

	    /**
	     * Save custom fields for single product
	     *
	     * @since   1.0.0
	     * @author  Emanuela Castorina
	     *
	     * @param $post_id
	     * @param $post
	     *
	     * @return void
	     */
        public function save_custom_fields_for_single_products( $post_id, $post ){

	        $args = array();
	        if ( isset( $_POST['_ywpar_point_earned'] ) ) {
		        $args['_ywpar_point_earned'] = $_POST['_ywpar_point_earned'];
	        }
	        if ( isset( $_POST['_ywpar_point_earned_dates_from'] ) ) {
		        $args['_ywpar_point_earned_dates_from'] = strtotime( $_POST['_ywpar_point_earned_dates_from'] );
	        }
	        if ( isset( $_POST['_ywpar_point_earned_dates_to'] ) ) {
		        $args['_ywpar_point_earned_dates_to'] = strtotime( $_POST['_ywpar_point_earned_dates_to'] );
	        }
	        if ( isset( $_POST['_ywpar_max_point_discount'] ) ) {
		        $args['_ywpar_max_point_discount'] = $_POST['_ywpar_max_point_discount'];
	        }
	        if ( isset( $_POST['_ywpar_redemption_percentage_discount'] ) ) {
		        $args['_ywpar_redemption_percentage_discount'] = $_POST['_ywpar_redemption_percentage_discount'];
	        }

            if( !empty($args)){
	            $product = wc_get_product( $post_id );
	            yit_save_prop( $product, $args, false, true );
            }

        }

	    /**
	     * Add custom fields for variation products
	     *
	     * @since   1.0.0
	     * @author  Emanuela Castorina
	     *
	     * @param $loop
	     * @param $variation_data
	     * @param $variations
	     *
	     * @return void
	     */
        public function add_custom_fields_for_variation_products( $loop, $variation_data, $variations ) {

	        $product                              = wc_get_product( $variations->ID );
	        $ywpar_point_earned                   = yit_get_prop( $product, '_ywpar_point_earned', true );
	        $ywpar_max_point_discount             = yit_get_prop( $product, '_ywpar_max_point_discount', true );
	        $ywpar_redemption_percentage_discount = yit_get_prop( $product, '_ywpar_redemption_percentage_discount', true );
	        $ywpar_point_earned_dates_from        = ( $date = yit_get_prop( $product, '_ywpar_point_earned_dates_from', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';
	        $ywpar_point_earned_dates_to          = ( $date = yit_get_prop( $product, '_ywpar_point_earned_dates_to', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';
            ?>
            <div class="ywpar_price_rewards">
                <p class="form-row form-row-first">
                    <label><?php _e( 'Points Earned:', 'yith-woocommerce-points-and-rewards' ); ?><a href="#" class="tips" data-tip="<?php _e( 'This field allows you to override global and category rules for point collection.
                Leave it blank to make global rules o category rules apply, assign a fixed number of points for this product
                (0 for no points) or set a percent value to apply global or category rules according to percentage (200&#37; for double points).', 'yith-woocommerce-points-and-rewards' ) ?>"> [?]</a></label>
                    <input type="text" size="5" name="variable_ywpar_point_earned[<?php echo $loop; ?>]" value="<?php echo esc_attr( $ywpar_point_earned ); ?>" />
                </p>
                <?php if( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'fixed' ): ?>
                <p class="form-row form-row-last">
                    <label><?php _e( 'Maximum discount:', 'yith-woocommerce-points-and-rewards' ); ?>
                        <a href="#" class="tips" data-tip="<?php _e( 'Maximum discount applicable to this variation. You can add a constant value or a percentage value that edits the maximum quantity of points
                 that can be used to get a discount according to product price. This value overrides global and category rules.', 'yith-woocommerce-points-and-rewards' ) ?>">[?]</a>
                    </label>
                    <input type="text" size="5" name="variable_ywpar_max_point_discount[<?php echo $loop; ?>]" value="<?php echo esc_attr( $ywpar_max_point_discount ); ?>" />
                </p>
                <?php
                //from 1.1.2
                elseif( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'percentage' ): ?>
                    <p class="form-row form-row-last">
                        <label><?php _e( 'Reward percent discount (%)', 'yith-woocommerce-points-and-rewards' ); ?>
                            <a href="#" class="tips" data-tip="<?php _e( 'Discount applicable to this variation.
                            This option edits the redeem percent discount that can be applied on product price. This value overrides global and category rules.', 'yith-woocommerce-points-and-rewards' ) ?>">[?]</a>
                        </label>
                        <input type="text" size="5" name="variable_ywpar_redemption_percentage_discount[<?php echo $loop; ?>]" value="<?php echo esc_attr( $ywpar_redemption_percentage_discount ); ?>" />
                    </p>
                <?php endif ?>
                <p class="form-row form-row-first ywpar_point_earned_dates_fields">
                    <label for="_ywpar_point_earned_dates_from"><?php _e( 'Validity of extra point rewards (optional) - From:', 'yith-woocommerce-points-and-rewards' ) ?></label>
                    <input type="text" class="short ywpar_point_earned_dates_from" name="variable_ywpar_point_earned_dates_from[<?php echo $loop; ?>]" id="_ywpar_point_earned_dates_from[<?php echo $loop; ?>]" value="<?php echo esc_attr( $ywpar_point_earned_dates_from ) ?>" placeholder="<?php echo _x( 'From&hellip;', 'placeholder', 'woocommerce' ) ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                </p>

                <p class="form-row form-row-last ywpar_point_earned_dates_fields">
                    <label for="_ywpar_point_earned_dates_to"><?php _e( 'To: ', 'yith-woocommerce-points-and-rewards' ) ?></label>
                    <input type="text" class="short ywpar_point_earned_dates_to" name="variable_ywpar_point_earned_dates_to[<?php echo $loop; ?>]" id="_ywpar_point_earned_dates_to[<?php echo $loop; ?>]" value="<?php echo esc_attr( $ywpar_point_earned_dates_to ) ?>" placeholder="<?php echo _x( 'To&hellip;', 'placeholder', 'woocommerce' ) ?>  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" style="display:block;" />
                </p>
            </div>
            <?php
        }

	    /**
	     * Save custom fields for variation products
	     *
	     * @since   1.0.0
	     * @author  Emanuela Castorina
	     *
	     * @param $variation_id
	     *
	     * @return mixed
	     */
	    public function save_custom_fields_for_variation_products( $variation_id ) {
		    if ( isset( $_POST['variable_post_id'] ) && ! empty( $_POST['variable_post_id'] ) ) {
			    $current_variation_index = array_search( $variation_id, $_POST['variable_post_id'] );
		    }

		    if ( $current_variation_index === false ) {
			    return false;
		    }

		    $args = array();


		    if ( isset( $_POST['variable_ywpar_point_earned'][ $current_variation_index ] ) ) {
			    $args['_ywpar_point_earned'] = $_POST['variable_ywpar_point_earned'][ $current_variation_index ];
		    }

		    if ( isset( $_POST['variable_ywpar_point_earned_dates_from'][ $current_variation_index ] ) ) {
			    $args['_ywpar_point_earned_dates_from'] = strtotime( $_POST['variable_ywpar_point_earned_dates_from'][ $current_variation_index ] );
		    }

		    if ( isset( $_POST['variable_ywpar_point_earned_dates_to'][ $current_variation_index ] ) ) {
			    $args['_ywpar_point_earned_dates_to'] = strtotime( $_POST['variable_ywpar_point_earned_dates_to'][ $current_variation_index ] );
		    }

		    if ( isset( $_POST['variable_ywpar_max_point_discount'][ $current_variation_index ] ) ) {
			    $args['_ywpar_max_point_discount'] = $_POST['variable_ywpar_max_point_discount'][ $current_variation_index ];
		    }

		    if ( isset( $_POST['variable_ywpar_redemption_percentage_discount'][ $current_variation_index ] ) ) {
			    $args['_ywpar_redemption_percentage_discount'] = $_POST['variable_ywpar_redemption_percentage_discount'][ $current_variation_index ];
		    }

		    if ( ! empty( $args ) ) {
			    $product = wc_get_product( $variation_id );
			    yit_save_prop( $product, $args , false, true );
		    }
	    }

        /**
         * Add additional fields in product_cat in add form
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  string The premium landing link
         */
        public function product_cat_add_form_fields() {
            ?>
            <div class="form-field">
                <label for="display_type"><?php _e( 'Points Earned', 'yith-woocommerce-points-and-rewards' ); ?></label>
                <input type="text" name="point_earned" id="point-earned" value="" size="40" />

                <p><?php _e( 'This field allows you to override global rules for point collection in all products belonging to this category.
                Leave it blank to make global rules apply, assign a fixed number of points for this category
                (0 for no points) or set a percent value to apply global rules according to percentage (200&#37; for double points).
                This value can, in turn, be overridden by rules specified for single product.', 'yith-woocommerce-points-and-rewards' ) ?></p>
            </div>

            <div class="form-field">
                <input type="text" name="point_earned_dates_from" id="ywpar-point-earned-dates-from" value="" size="10" placeholder="<?php echo _x( 'From&hellip;', 'placeholder', 'woocommerce' ) ?>  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                <input type="text" name="point_earned_dates_to" id="ywpar-point-earned-dates-to" value="" size="10" placeholder="<?php echo _x( 'To&hellip;', 'placeholder', 'woocommerce' ) ?>  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />

                <p style="clear: both" class="description"><?php _e( 'You can set a start and end date for validity of rules specified in the field above', 'yith-woocommerce-points-and-rewards' ) ?></p>

            </div>
            <?php if( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'fixed' ): ?>
            <div class="form-field">
                <label for="display_type"><?php _e( 'Maximum discount', 'yith-woocommerce-points-and-rewards' ); ?></label>
                <input type="text" name="max_point_discount" id="max-point-discount" value="" size="40" />

                <p><?php _e( 'Maximum discount applicable to products in this category. You can add a constant value or a percentage value that edits the maximum quantity of points
                 that can be used to get a cart discount according to product price. This value overrides global rules and can be overridden by
                 rules in single product.', 'yith-woocommerce-points-and-rewards' ) ?></p>
            </div>
                <?php
            //from 1.1.2
            elseif( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'percentage' ): ?>
                <div class="form-field">
                    <label for="display_type"><?php _e( 'Reward percent discount (%)', 'yith-woocommerce-points-and-rewards' ); ?></label>
                    <input type="text" name="redemption_percentage_discount" id="redemption-percentage-discount" value="" size="40" />

                    <p><?php _e( 'Discount applicable to products in this category. This option edits the redeem percent discount that can be applied on product price.
                    This value overrides global and category rules.', 'yith-woocommerce-points-and-rewards' ) ?></p>
                </div>
                <?php
            endif;

        }

	    /**
	     * Add additional fields in product_cat edit form
	     *
	     * @since   1.0.0
	     * @author  Emanuela Castorina
	     *
	     * @param $term
	     *
	     * @return void
	     */
        public function edit_category_fields( $term ){
	        $point_earned                   = get_term_meta( $term->term_id, 'point_earned', true );
	        $max_point_discount             = get_term_meta( $term->term_id, 'max_point_discount', true );
	        $redemption_percentage_discount = get_term_meta( $term->term_id, 'redemption_percentage_discount', true );
	        $point_earned_dates_from        = ( $date = get_term_meta( $term->term_id, 'point_earned_dates_from', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';
	        $point_earned_dates_to          = ( $date = get_term_meta( $term->term_id, 'point_earned_dates_to', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';

          ?>
            <tr class="form-field">
                <th scope="row" valign="top"><label><?php _e( 'Points Earned', 'yith-woocommerce-points-and-rewards' ); ?></label></th>
                <td>
                    <input type="text"  name="point_earned" id="point-earned" value="<?php echo $point_earned ?>" size="10"/>
                    <p class="description"><?php _e( 'This field allows you to override global rules for point collection in all products belonging to this category.
                Leave it blank to make global rules apply, assign a fixed number of points for this category
                (0 for no points) or set a percent value to apply global rules according to percentage (200&#37; for double points).
                This value can, in turn, be overridden by rules specified for single product.', 'yith-woocommerce-points-and-rewards' ) ?></p>
                </td>
            </tr>

            <tr class="form-field ywpar_point_earned_dates_fields">
                <th scope="row" valign="top"><label><?php _e( 'Validity for extra point reward (optional)', 'yith-woocommerce-points-and-rewards' ); ?></label></th>
                <td>
                    <input type="text"  name="point_earned_dates_from" id="ywpar-point-earned-dates-from" value="<?php echo $point_earned_dates_from ?>" size="10" placeholder="<?php echo _x( 'From&hellip;', 'placeholder', 'woocommerce' ) ?>  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"/>
                    <input type="text"  name="point_earned_dates_to" id="ywpar-point-earned-dates-to" value="<?php echo $point_earned_dates_to ?>" size="10" placeholder="<?php echo _x( 'To&hellip;', 'placeholder', 'woocommerce' ) ?>  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"/>
                    <p style="clear: both" class="description"><?php _e( 'You can set a start and end date for validity of rules specified in the field above', 'yith-woocommerce-points-and-rewards' ) ?></p>
                </td>
            </tr>
            <?php if( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'fixed' ): ?>
            <tr class="form-field">
                <th scope="row" valign="top"><label><?php _e( 'Maximum discount', 'yith-woocommerce-points-and-rewards' ); ?></label></th>
                <td>
                    <input type="text"  name="max_point_discount" id="max-point-discount" value="<?php echo $max_point_discount ?>" size="10"/>
                    <p  class="description"><?php _e( 'Maximum discount applicable to products in this category. You can add a constant value or a percent value that edits the maximum quantity of points
                 that can be used to get a cart discount. This value overrides global rules and can be overridden by
                 rules in single product.', 'yith-woocommerce-points-and-rewards' ) ?></p>
                </td>
            </tr>
                <?php
            //from 1.1.2
            elseif( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'percentage' ): ?>
                <tr class="form-field">
                    <th scope="row" valign="top"><label><?php _e( 'Reward percent discount (%)', 'yith-woocommerce-points-and-rewards' ); ?></label></th>
                    <td>
                        <input type="text"  name="redemption_percentage_discount" id="redemption-percentage-discount" value="<?php echo $redemption_percentage_discount ?>" size="10"/>
                        <p  class="description"><?php _e( 'Discount applicable to products in this category. This option edits the percent discount that can be applied to product price.
                    This value overrides global and category rules.', 'yith-woocommerce-points-and-rewards' ) ?></p>
                    </td>
                </tr>
<?php
endif;

        }

	    /**
	     * Save custom category fields
	     *
	     * @since   1.0.0
	     * @author  Emanuela Castorina
	     *
	     * @param $term_id
	     * @param string $tt_id
	     * @param string $taxonomy
	     *
	     * @return void
	     */
        public function save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
            if ( 'product_cat' !== $taxonomy ) {
                return;
            }

            if ( isset( $_POST['point_earned'] ) ) {
	            update_term_meta( $term_id, 'point_earned', $_POST['point_earned'] );
            }

            if ( isset( $_POST['max_point_discount'] ) ) {
	            update_term_meta( $term_id, 'max_point_discount', $_POST['max_point_discount'] );
            }

            if ( isset( $_POST['redemption_percentage_discount'] ) ) {
	            update_term_meta( $term_id, 'redemption_percentage_discount', $_POST['redemption_percentage_discount'] );
            }

            if ( isset( $_POST['point_earned_dates_from'] ) ) {
	            update_term_meta( $term_id, 'point_earned_dates_from', strtotime( wc_clean( $_POST['point_earned_dates_from'] ) ) );
            }

            if ( isset( $_POST['point_earned_dates_to'] ) ) {
	            update_term_meta( $term_id, 'point_earned_dates_to', strtotime( wc_clean( $_POST['point_earned_dates_to'] ) ) );
            }
        }

        /**
         * Apply Points to Previous Orders
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function apply_previous_order() {
            check_ajax_referer( 'apply_previous_order', 'security' );

            if ( isset( $_POST['from'] ) ) {
                $from = $_POST['from'];
            }

            $success_count  = 0;
            $offset         = 0;
            $posts_per_page = 300;

            do {
                $args = array(
                    'post_type'      => 'shop_order',
                    'fields'         => 'ids',
                    'offset'         => $offset,
                    'posts_per_page' => $posts_per_page,
                    'post_status'    => array( 'wc-processing', 'wc-completed' ),
                    'meta_query'     => array(
                        array(
                            'key'     => '_ywpar_points_earned',
                            'compare' => 'NOT EXISTS'
                        ),
                    )
                );

                if ( $from != '' ) {
                    $d                  = explode( '-', $from );
                    $args['date_query'] = array(
                        array(
                            'after'     => array(
                                'year'  => $d[0],
                                'month' => $d[1],
                                'day'   => $d[2],
                            ),
                            'inclusive' => true,
                        ),
                    );
                }

                $order_ids = get_posts( $args );

                if ( is_array( $order_ids ) ) {
                    foreach ( $order_ids as $order_id ) {
                        YITH_WC_Points_Rewards_Earning()->add_order_points( $order_id );
                        $success_count ++;
                    }
                }

                $offset += $posts_per_page;

            } while ( count( $order_ids ) == $posts_per_page );

            $response = sprintf( _n( '<strong>%d</strong> order has been updated', '<strong>%d</strong> orders have been updated', $success_count, 'yith-woocommerce-points-and-rewards' ), $success_count );

            wp_send_json( $response );
        }

        /**
         * Apply Points to Previous Orders
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function apply_wc_points_rewards() {

            check_ajax_referer( 'apply_wc_points_rewards', 'security' );

            $from = isset( $_POST['from'] ) ?  $_POST['from'] : '';

            $success_count = YITH_WC_Points_Rewards_Porting()->migrate_points( $from );

            $response = sprintf( _n( '<strong>%d</strong> point has been updated', '<strong>%d</strong> points have been updated', $success_count, 'yith-woocommerce-points-and-rewards' ), $success_count );

            wp_send_json( $response );
        }

        /**
         * Add a widgets to the dashboard.
         *
         * This function is hooked into the 'wp_dashboard_setup' action below.
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        function ywpar_points_widgets() {

            wp_add_dashboard_widget(
                'ywpar_points_hit_widget',
                __('Best Point Earners', 'yith-woocommerce-points-and-rewards'),
                array( $this, 'points_hit_widget')
            );

            wp_add_dashboard_widget(
                'ywpar_points_best_rewards_widget',
                __('Best Point Rewards', 'yith-woocommerce-points-and-rewards'),
                array( $this, 'best_rewards_widget')
            );
        }

        /**
         * Print the dashboard widget with the users with best points
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        function points_hit_widget() {

            $users = YITH_WC_Points_Rewards()->user_list_points( apply_filters( 'ywpar_points_hit_widget_items_number', 10 ) );

            if ( !empty( $users ) ) {
                echo '<table cellpadding="5" class="ywpar_points_hit_widget">';
                foreach ( $users as $user ) {
                    echo '<tr>';
                    echo '<td width="1">' . get_avatar( $user->ID, '32' ) . '</td>';
                    $points      = get_user_meta( $user->ID, '_ywpar_user_total_points', true );
                    $history_url = admin_url( 'admin.php?yit_plugin_panel&page=yith_woocommerce_points_and_rewards&tab=customers&action=update&user_id=' . $user->ID );

                    echo '<td>' . $user->display_name . '</td>';
                    echo '<td class="points">' . $points . '</td>';
                    echo '<td class="history"><a href="' . $history_url . '" class="button secondary-button"> ' . __( 'View History', 'yith-woocommerce-points-and-rewards' ) . '</a></td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            else {
                echo __( 'No users found', 'yith-woocommerce-points-and-rewards' );
            }

        }

        /**
         * Print the dashboard widget with the users with best discounts
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        function best_rewards_widget() {

            $users = YITH_WC_Points_Rewards()->user_list_discount( apply_filters( 'ywpar_best_rewards_widget_items_number', 10 ) );

            if ( !empty( $users ) ) {
                echo '<table cellpadding="5" class="ywpar_points_hit_widget">';
                foreach ( $users as $user ) {
                    echo '<tr>';
                    echo '<td width="1">' . get_avatar( $user->ID, '32' ) . '</td>';
                    $discount    = get_user_meta( $user->ID, '_ywpar_user_total_discount', true );
                    $history_url = admin_url( 'admin.php?yit_plugin_panel&page=yith_woocommerce_points_and_rewards&tab=customers&action=update&user_id=' . $user->ID );

                    echo '<td>' . $user->display_name . '</td>';
                    echo '<td class="points">' . wc_price( $discount ) . '</td>';
                    echo '<td class="history"><a href="' . $history_url . '" class="button secondary-button"> ' . __( 'View History', 'yith-woocommerce-points-and-rewards' ) . '</a></td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            else {
                echo __( 'No users found', 'yith-woocommerce-points-and-rewards' );
            }

        }

        /**
         * Action Links
         *
         * add the action links to plugin admin page
         *
         * @param $links | links plugin array
         *
         * @return   mixed Array
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @return mixed
         * @use      plugin_action_links_{$plugin_file_name}
         */
        public function action_links( $links ) {

            $links[] = '<a href="' . admin_url( "admin.php?page={$this->_panel_page}" ) . '">' . __( 'Settings', 'yith-woocommerce-points-and-rewards' ) . '</a>';
            if ( defined( 'YITH_YWPAR_FREE_INIT' ) ) {
                $links[] = '<a href="' . $this->get_premium_landing_uri() . '" target="_blank">' . __( 'Premium Version', 'yith-woocommerce-points-and-rewards' ) . '</a>';
            }

            return $links;
        }

        /**
         * plugin_row_meta
         *
         * add the action links to plugin admin page
         *
         * @param $plugin_meta
         * @param $plugin_file
         * @param $plugin_data
         * @param $status
         *
         * @return   Array
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @use      plugin_row_meta
         */
        public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

            if ( defined( 'YITH_YWPAR_INIT' ) && YITH_YWPAR_INIT == $plugin_file ) {
                $plugin_meta[] = '<a href="' . $this->doc_url . '" target="_blank">' . __( 'Plugin Documentation', 'yith-woocommerce-points-and-rewards' ) . '</a>';
            }
            return $plugin_meta;
        }

        /**
         * Get the premium landing uri
         *
         * @since   1.0.0
         * @author  Andrea Grillo <andrea.grillo@yithemes.com>
         * @return  string The premium landing link
         */
        public function get_premium_landing_uri(){
            return defined( 'YITH_REFER_ID' ) ? $this->_premium_landing . '?refer_id=' . YITH_REFER_ID : $this->_premium_landing.'?refer_id=1030585';
        }

        /**
         * Register plugins for activation tab
         *
         * @return void
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function register_plugin_for_activation() {
            if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
                require_once ( YITH_YWPAR_DIR . 'plugin-fw/licence/lib/yit-licence.php' );
                require_once ( YITH_YWPAR_DIR . 'plugin-fw/licence/lib/yit-plugin-licence.php' );
            }
            YIT_Plugin_Licence()->register( YITH_YWPAR_INIT, YITH_YWPAR_SECRET_KEY, YITH_YWPAR_SLUG );
        }

        /**
         * Register plugins for update tab
         *
         * @return void
         * @since  1.0.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function register_plugin_for_updates() {
            if( ! class_exists( 'YIT_Upgrade' ) ) {
                require_once YITH_YWPAR_DIR.'plugin-fw/lib/yit-upgrade.php';
            }
            YIT_Upgrade()->register( YITH_YWPAR_SLUG, YITH_YWPAR_INIT );
        }

	    /**
         * Reset points from administrator points
         *
         * @return void
         * @since 1.1.1
         * @author Emanuela Castorina
         */
        public function reset_points(  ) {

            check_ajax_referer( 'reset_points', 'security' );


            YITH_WC_Points_Rewards()->reset_points();

            //from 1.1.1
            $response =   __( 'Done!', 'yith-woocommerce-points-and-rewards' ) ;

            wp_send_json( $response );
        }

	    /**
	     * Import point from csv
         * @return void
         * @author Emanuela Castorina
	     */
	    public function actions_from_settings_panel(  ) {

            if( ! isset( $_REQUEST['option_page']) || ! isset( $_REQUEST['ywpar_safe_submit_field']) ){
                return;
            }

            switch( $_REQUEST['ywpar_safe_submit_field'] ){
                case 'import_points':
                    if (!isset($_FILES['file_import_csv']) || !is_uploaded_file($_FILES['file_import_csv']['tmp_name'])) {
                        return;
                    }

                    $uploaddir = wp_upload_dir();

                    $temp_name = $_FILES['file_import_csv']['tmp_name'];
                    $file_name = $_FILES['file_import_csv']['name'];

                    if ( ! move_uploaded_file( $temp_name, $uploaddir['basedir'] . $file_name ) ) {
                        return;
                    }

                    YITH_WC_Points_Rewards_Porting()->import_from_csv( $uploaddir['basedir'] . $file_name, $_REQUEST['delimiter'], $_REQUEST['csv_format'], $_REQUEST['csv_import_action']);

                    break;
                default:
            }
        }

    }
}

/**
 * Unique access to instance of YITH_WC_Points_Rewards_Admin class
 *
 * @return \YITH_WC_Points_Rewards_Admin
 */
function YITH_WC_Points_Rewards_Admin() {
    return YITH_WC_Points_Rewards_Admin::get_instance();
}
