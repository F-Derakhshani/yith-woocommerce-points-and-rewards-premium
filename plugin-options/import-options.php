<?php

//from 1.1.3
$import = array(

	'import' => array(

		'header'    => array(

			array(
				'name' => __( 'Import / Export points from csv file', 'yith-woocommerce-points-and-rewards' ),
				'type' => 'title'
			),

			array( 'type' => 'close' )
		),


		'import' => array(

			array( 'type' => 'open' ),

			array(
				'id'      => 'options_import_form',
				'name'    => __( 'Import points from CVS file', 'yith-woocommerce-points-and-rewards' ),
				'desc'    => '',
				'type'    => 'options-import-form',
			),

			array( 'type' => 'close' ),
		)
	)
);


return apply_filters( 'yith_ywpar_panel_import_options', $import );