/**
 * admin.js
 *
 * @author Your Inspiration Themes
 * @package YITH Infinite Scrolling Premium
 * @version 1.0.0
 */

jQuery(document).ready( function($) {
    "use strict";

    var wrapper         = $( document ).find( '#yit_ywpar_options_extra_points-container' ),
        container       = wrapper.find( '.ywpar-section' ),
        head            = container.find( '.section-head' ),
        remove          = head.find( '.ywpar-remove'),
        active          = head.find( '.ywpar-active'),
        eventType       = container.find( '.yith-ywpar-eventype-select'),
        block_loader    = ( typeof yith_ywpar_admin !== 'undefined' ) ? yith_ywpar_admin.block_loader : false,
        error_msg       = ( typeof yith_ywpar_admin !== 'undefined' ) ? yith_ywpar_admin.error_msg : false,
        del_msg         = ( typeof yith_ywpar_admin !== 'undefined' ) ? yith_ywpar_admin.del_msg : false,

        input_section   = wrapper.find( '#yith-ywpar-add-section' ),
        add_section     = wrapper.find( '#yith-ywpar-add-section-button'),


        /****
         * Remove function
         */
        remove_func = function( remove ) {

            remove.on('click', function (e) {
                e.stopPropagation();

                var t           = $(this),
                    section     = t.data('section'),
                    container   = t.parents('.ywpar-section' ),
                    confirm     = window.confirm( del_msg );

                if ( confirm == true ) {

                    if (block_loader) {
                        container.block({
                            message   : null,
                            overlayCSS: {
                                background: '#fff url(' + block_loader + ') no-repeat center',
                                opacity   : 0.5,
                                cursor    : 'none'
                            }
                        });
                    }

                    $.post(yith_ywpar_admin.ajaxurl, {
                        action : 'yith_dynamic_pricing_section_remove',
                        section: section
                    }, function (resp) {
                        container.remove();
                    })
                }

            })
        },

        /****
         * Active function
         */
        active_func = function( active ) {

            active.on('click', function (e) {

                e.stopPropagation();

                var t           = $(this),
                    section     = t.data('section'),

                    active_field = t.parents('.section-head').find('.active-hidden-field');

                if ( t.hasClass( 'activated' ) ) {
                    t.removeClass('activated');
                    active_field.val( 'no' );
                }else{
                    t.addClass('activated');
                    active_field.val( 'yes' );
                }

            })
        },

        /****
         * Deps function option
         */
        deps_func = function( eventType ) {
            eventType.each( function(){
                var t           = $(this),
                    field       = t.data('field'),
                    selected    = t.find( 'option:selected' );

                hide_show_func( t, selected.val(), field );

                t.on( 'change', function(){
                    field       = t.data('field'),
                        selected = t.find( 'option:selected' );
                    hide_show_func( t, selected.val(), field);
                })
            });
        },

        hide_show_func = function( t, val, field ) {
            var  opt        = t.parents('.ywpar-select-wrapper').find( 'tr.deps-'+ field);

            opt.each(function(){
                var types = $(this).data('type').split(';');
                if( $.inArray( val ,types) !== -1 ){
                    $(this).show();
                }else{
                    $(this).hide();
                    if( typeof $(this).data('rel') !== 'undefined' ){
                        var item_class = 'deps-' + $(this).data('rel');
                        $(this).parents('.ywpar-section').find('.'+item_class).hide();
                    }


                }
            });
        },
        product_search = function( element ){
            // Category Ajax Search
            element.ajaxChosen({
                method: 	'GET',
                url: 		yith_ywpar_admin.ajaxurl,
                dataType: 	'json',
                afterTypeDelay: 100,
                data:		{
                    action:    'woocommerce_json_search_products',
                    security:  yith_ywpar_admin.search_products_nonce
                }
            }, function (data) {
                var terms = {};

                $.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
        },

        category_search = function( element ){

            // Category Ajax Search
            element.ajaxChosen({
                method: 	'GET',
                url: 		yith_ywpar_admin.ajaxurl,
                dataType: 	'json',
                afterTypeDelay: 100,
                data:		{
                    action:    'ywpar_category_search',
                    security:  yith_ywpar_admin.search_categories_nonce
                }
            }, function (data) {
                var terms = {};

                $.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
        },

        customer_search = function( element ){

            // Category Ajax Search
            element.ajaxChosen({
                method: 	'GET',
                url: 		yith_ywpar_admin.ajaxurl,
                dataType: 	'json',
                afterTypeDelay: 100,
                data:		{
                    action:    'ywpar_customer_search',
                    security:  yith_ywpar_admin.search_customers_nonce
                }
            }, function (data) {
                var terms = {};

                $.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
        };

    add_section.on( 'click', function(e) {
        e.preventDefault();

        var t       = $(this),
            id      = t.data( 'section_id'),
            name    = t.data( 'section_name' ),
            action  = t.data( 'action'),
            title   = input_section.val();

        if( title == '' ) {
            if( error_msg ) {
                t.siblings( '.ywpar-error-input-section' ).html( error_msg );
            }
        }
        else {
            $.post( yith_ywpar_admin.ajaxurl, { action: action, section: title, id: id, name: name}, function( resp ) {
                // empty input
                input_section.val('');


                // remove error msg if any
                $( '.ywpar-error-input-section' ).remove();

                wrapper.append( resp );

                var container       = wrapper.find( '.ywpar-section').last(),
                    head            = $(container).find( '.section-head' ),
                    eventType       = container.find( '.yith-ywpar-eventype-select'),
                    active          = container.find( '.ywpar-active'),
                    remove          = container.find( '.ywpar-remove');


                // re-init
                container.find( 'select').chosen({
                    width: '100%',
                    disable_search: true
                });

                // open_func( head );
                deps_func( eventType );
                remove_func( remove );
                active_func( active );
                container.find('.ajax_chosen_select_categories').each( function(){
                    category_search( $(this) );
                });
                container.find('.ajax_chosen_select_products').each( function(){
                    product_search( $(this) );
                });
                container.find('.ajax_chosen_select_customers').each( function(){
                    customer_search( $(this) );
                });
                container.find('.datepicker').datepicker({
                    dateFormat : 'yy-mm-dd'
                });

            })
        }
    });





    /****
     * Add a row pricing rules
     ****/
    $(document).on('click', '.ywpar-add-row', function() {
        var $t = $(this),
            table = $t.closest('table'),
            current_row = $t.closest('tr'),
            current_index = parseInt(current_row.data('index')),
            clone = current_row.clone(),
            rows = table.find('tr'),
            max_index = 1;

        rows.each(function(){
            var index = $(this).data('index');
            if( index > max_index ){
                max_index = index;
            }
        });

        var new_index = max_index + 1;
        clone.attr( 'data-index', new_index );
        var fields = clone.find("[name*='extra_points']");

        fields.each(function(){
            var $t = $(this),
                name = $t.attr('name'),
                id =  $t.attr('id'),

                new_name = name.replace('[extra_points]['+current_index+']', '[extra_points]['+new_index+']'),
                new_id = id.replace('[extra_points]['+current_index+']', '[extra_points]['+new_index+']');

            $t.attr('name', new_name);
            $t.attr('id', new_id);
            $t.val('');

        });

        clone.find('.remove-row').removeClass('hide-remove');
        clone.find('.chosen-container').remove();

        clone.find( 'select').chosen({
            width: '100%',
            disable_search: false
        });

        table.append(clone);

        var eventType       = clone.find( '.yith-ywpar-eventype-select'),
            container = clone.parents( '.ywpar-section' );

        container.find('.ajax_chosen_select_categories').each(function () {
            category_search($(this));
        });

        container.find('.ajax_chosen_select_products').each(function () {
            product_search($(this));
        });

        container.find('.ajax_chosen_select_customers').each(function () {
            customer_search($(this));
        });

        deps_func(eventType);
    });

    /****
     * remove a row pricing rules
     ****/
    $(document).on('click', '#yit_ywpar_options_extra_points-container .remove-row', function() {
        var $t = $(this),
            current_row = $t.closest('tr');

        current_row.remove();
    });


    // init
    //  open_func(head);
    remove_func(remove);
    active_func(active);
    deps_func(eventType);

    container.find('.ajax_chosen_select_categories').each(function () {
        category_search($(this));
    });

    container.find('.ajax_chosen_select_products').each(function () {
        product_search($(this));
    });

    container.find('.ajax_chosen_select_customers').each(function () {
        customer_search($(this));
    });

    container.find('select').chosen({
        width         : '100%',
        disable_search: false
    });

    container.find('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd'
    });


    // DATE PICKER FIELDS
    $( '.ywpar_point_earned_dates_fields' ).each( function() {
        var dates = $( this ).find( 'input' ).datepicker({
            defaultDate: '',
            dateFormat: 'yy-mm-dd',
            numberOfMonths: 1,
            showButtonPanel: true,
            onSelect: function( selectedDate ) {
                var option   = '';
                var instance = $( this ).data( 'datepicker' );
                var date     = $.datepicker.parseDate( instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings );
                dates.not( this ).datepicker( 'option', option, date );
            }
        });
    });

    //datepicker
    $('.plugin-option .yit_options .panel-datepicker').each( function() {
        $(this).datepicker({
            dateFormat : 'yy-mm-dd'
        });
    });

    $('#yit_ywpar_options_apply_points_previous_order_btn').on('click', function(e) {
        e.preventDefault();
        var from = $(this).prev().val(),
            container   = $('#yit_ywpar_options_apply_points_previous_order-container .option');

        container.find('.response').remove();

        if (block_loader) {
            container.block({
                message   : null,
                overlayCSS: {
                    background: 'transparent',
                    opacity   : 0.5,
                    cursor    : 'none'
                }
            });
        }

        $.ajax({
            type    : 'POST',
            url     : yith_ywpar_admin.ajaxurl,
            dataType: 'json',
            data    : 'action=ywpar_apply_previous_order&from=' + from + '&security=' + yith_ywpar_admin.apply_previous_order_none,
            success : function (response) {
                container.unblock();
                container.append('<span class="response">'+response+'</span>');
            }
        });
    });

    $('.ywrac_reset_points').on('click', function(e) {
        e.preventDefault();

        var conf = confirm( yith_ywpar_admin.reset_points_confirm );

        if( ! conf ){
            return false;
        }

        var container   = $('#yit_ywpar_options_reset_points-container .option');

        container.find('.response').remove();

        if (block_loader) {
            container.block({
                message   : null,
                overlayCSS: {
                    background: 'transparent',
                    opacity   : 0.5,
                    cursor    : 'none'
                }
            });
        }

        $.ajax({
            type    : 'POST',
            url     : yith_ywpar_admin.ajaxurl,
            dataType: 'json',
            data    : 'action=ywpar_reset_points&security=' + yith_ywpar_admin.reset_points,
            success : function (response) {
                container.unblock();
                container.append('<span class="response">'+response+'</span>');
            }
        });

    });


    $('#yit_ywpar_options_apply_points_from_wc_points_rewards_btn').on('click', function(e) {
        e.preventDefault();
        var from = $(this).prev().val(),
            container   = $('#yit_ywpar_options_apply_points_from_wc_points_rewards-container .option');

        container.find('.response').remove();

        if (block_loader) {
            container.block({
                message   : null,
                overlayCSS: {
                    background: 'transparent',
                    opacity   : 0.5,
                    cursor    : 'none'
                }
            });
        }

        $.ajax({
            type    : 'POST',
            url     : yith_ywpar_admin.ajaxurl,
            dataType: 'json',
            data    : 'action=ywpar_apply_wc_points_rewards&from=' + from + '&security=' + yith_ywpar_admin.apply_wc_points_rewards,
            success : function (response) {
                container.unblock();
                container.append('<span class="response">'+response+'</span>');
            }
        });
    });

    var $rewards_method = $('#yit_ywpar_options_conversion_rate_method'),
        toggle_fields = function( type ){
            if( type == 'fixed'){
                $(document).find('input[data-hide="percentage_method"]').closest('tr').hide();
                $(document).find('.percentual_method').closest('tr').hide();

                $(document).find('input[data-hide="fixed_method"]').closest('tr').show();
                $(document).find('.fixed_method').closest('tr').show();

            }else if( type == 'none' ){
                $(document).find('input[data-hide="percentage_method"]').closest('tr').hide();
                $(document).find('.percentual_method').closest('tr').hide();
                $(document).find('input[data-hide="fixed_method"]').closest('tr').hide();
                $(document).find('.fixed_method').closest('tr').hide();
            }else if( type == 'percentage' ){
                $(document).find('input[data-hide="percentage_method"]').closest('tr').show();
                $(document).find('.percentual_method').closest('tr').show();
                $(document).find('input[data-hide="fixed_method"]').closest('tr').hide();
                $(document).find('.fixed_method').closest('tr').hide();
            }
        };

    toggle_fields($rewards_method.val());

    $rewards_method.on('change', function(){
        var type = $(this).val();
        toggle_fields(type);
    });

    if( $('#ywpar_import_points').length ){
        $('#ywpar_import_points').closest('form').attr('enctype',"multipart/form-data");
    }
    //Import points functions
    $('#ywpar_import_points').on('click', function(e){
        e.preventDefault();
        $('.ywpar_safe_submit_field').val('import_points');

        $(this).closest('form').submit();
    });

});
