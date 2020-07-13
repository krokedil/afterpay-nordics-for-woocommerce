jQuery(function ($) {
	var se_address_fetched 		= WC_AfterPay.se_address_fetched;
	var customer_info_fetched 	= false;
	var customer_first_name   	= '';
	var customer_last_name    	= '';
	var customer_address_1    	= '';
	var customer_address_2    	= '';
	var customer_postcode     	= '';
	var customer_city         	= '';
	var display_get_address_no = WC_AfterPay.display_get_address_no;
	var always_display_get_address = WC_AfterPay.always_display_get_address;

	function mask_form_field(field) {
		if (field != null) {
			var field_split = field.split(' ');
			var field_masked = new Array();

			$.each(field_split, function (i, val) {
				if (isNaN(val)) {
					field_masked.push(val.charAt(0) + Array(val.length).join('*'));
				} else {
					field_masked.push('**' + val.substr(val.length - 3));
				}
			});

			return field_masked.join(' ');
		}
	}

	function maybe_show_pre_checkout_form(do_focus) {
		//console.log(do_focus);
		console.log('maybe_show_pre_checkout_form');
		var selected_payment_method = $('input[name="payment_method"]:checked').val();
		var selected_customer_country = $("#billing_country").val();
		
		// Don't show the get address field if payment method isn't AfterPay and settings is set to only display it for AP.
		if (selected_payment_method.indexOf('afterpay') < 0 && 'no' === always_display_get_address ) {
			jQuery('#afterpay-pre-check-customer').fadeOut();
			return;
		}

		// Don't show the get address field if the country is NO and the the feature is disable in settings.
		if ( 'NO' === selected_customer_country && 'no' === display_get_address_no ) {
			return;
		}
				
		if ($("#payment_method_afterpay_invoice").length > 0) {
	        jQuery('#afterpay-pre-check-customer').fadeIn();
	        check_separate_shipping_address(do_focus);
	
			// Only display the Get Address button if Sweden is the selected country
			
			if (selected_customer_country == 'SE') {
				jQuery('.afterpay-pre-check-se').fadeIn();
				jQuery('.afterpay-pre-check-no').fadeOut();
				//jQuery( '#billing_email_field' ).fadeOut();
				jQuery( '.personal-number-norway' ).hide();
				//jQuery('.afterpay-get-address-button').fadeIn();
			} else if ( selected_customer_country == 'DE' ) {
				jQuery('.afterpay-pre-check-se').fadeOut();
				jQuery('.afterpay-pre-check-no').fadeOut();
				jQuery( '.personal-number-norway' ).hide();
			} else if ( selected_customer_country == 'DK' ) {
				jQuery('.afterpay-pre-check-se').fadeOut();
				jQuery('.afterpay-pre-check-no').fadeOut();
			} else {
				jQuery( '.afterpay-pre-check-no' ).fadeIn();
				jQuery( '.afterpay-pre-check-se' ).fadeOut();
				//jQuery( '#billing_email_field' ).fadeIn();
				jQuery( '.personal-number-norway' ).show();
				//jQuery('.afterpay-get-address-button').fadeOut();
			}
			
			// Hide/show customer lookup button
			if ( $( '#afterpay-customer-category-company' ).is(":checked") ) {
				jQuery( '.afterpay-customer-lookup-button' ).hide();
				jQuery('li.payment_method_afterpay_part_payment').fadeOut();
			} else {
				jQuery( '.afterpay-customer-lookup-button' ).show();
				jQuery('li.payment_method_afterpay_part_payment').fadeIn();
			}
		} else {
			jQuery('#afterpay-pre-check-customer').fadeOut();
		}
	}

	function check_separate_shipping_address(do_focus){
        var selected_payment_method = $('input[name="payment_method"]:checked').val();
        if (selected_payment_method.indexOf('afterpay') >= 0) {
            // Check customer type
            if ( $( '#afterpay-customer-category-company' ).is(":checked") ) {
                // Check if option is checked in admin for separate shipping address for companies
                if ( $('#separate_shipping_companies').val() === 'yes' ){
                    // Show separate shipping address for AfterPay if customer is company
                    //$('#ship-to-different-address').show();
                } else{
                    // Do not allow separate shipping address for AfterPay if the option is not checked
                    $('div.shipping_address').hide();
                    $('#ship-to-different-address').hide();
                }
            } else {
                // Do not allow separate shipping address for AfterPay if customer is not company
                $('div.shipping_address').hide();
                $('#ship-to-different-address').hide();
            }
            // Show pno
            $('#afterpay-pre-check-customer').slideDown(250);
            if ('yes' == do_focus) {
                $('#afterpay-pre-check-customer-number').focus();
            }
            //$( '#billing_email_field' ).hide();
        } else {
            // Hide pno
            //$('#afterpay-pre-check-customer').slideUp(250);
            // Show ship to different address checkbox
            $( '#ship-to-different-address' ).show();
            //$( '#billing_email_field' ).show();
        }
	}

	function populate_afterpay_fields() {
		var selected_customer_category = $('input[name="afterpay_customer_category"]:checked').val();
		if( 'SE' == $("#billing_country").val() ) {
			if ( 'Person' == selected_customer_category ) {
				console.log(customer_first_name);
				$('#billing_first_name').val(customer_first_name).prop('readonly', true);
				$('#billing_last_name').val(customer_last_name).prop('readonly', true);
	
				$('#shipping_first_name').val(customer_first_name).prop('readonly', true);
				$('#shipping_last_name').val(customer_last_name).prop('readonly', true);
	
			} else {
				$('#billing_company').val(customer_last_name).prop('readonly', true);
				$('#shipping_company').val(customer_last_name).prop('readonly', true);
			}
			$('#billing_address_1').val(customer_address_1).prop('readonly', true);
			$('#billing_postcode').val(customer_postcode).prop('readonly', true);
			$('#billing_city').val(customer_city).prop('readonly', true);

			$('#shipping_address_1').val(customer_address_1).prop('readonly', true);
			$('#shipping_postcode').val(customer_postcode).prop('readonly', true);
			$('#shipping_city').val(customer_city).prop('readonly', true);

		} else {
			if ( 'Person' == selected_customer_category ) {
				console.log(customer_first_name);
				$('#billing_first_name').val(customer_first_name).prop('readonly', false);
				$('#billing_last_name').val(customer_last_name).prop('readonly', false);
	
				$('#shipping_first_name').val(customer_first_name).prop('readonly', false);
				$('#shipping_last_name').val(customer_last_name).prop('readonly', false);
	
			} else {
				$('#billing_email').val($('#afterpay-customer-email').val());
				$('#billing_company').val(customer_last_name).prop('readonly', false);
	
				$('#shipping_company').val(customer_last_name);
			}
			$('#billing_address_1').val(customer_address_1).prop('readonly', false);
			$('#billing_postcode').val(customer_postcode).prop('readonly', false);
			$('#billing_city').val(customer_city).prop('readonly', false);
			
			$('#shipping_address_1').val(customer_address_1).prop('readonly', false);
			$('#shipping_postcode').val(customer_postcode).prop('readonly', false);
			$('#shipping_city').val(customer_city).prop('readonly', false);
		}
		
	}

	function wipe_afterpay_fields() {
		$('#billing_first_name').val('').prop('readonly', false);
		$('#billing_last_name').val('').prop('readonly', false);
		$('#billing_company').val('').prop('readonly', false);
		$('#billing_address_1').val('').prop('readonly', false);
		//$('#billing_address_2').val('').prop('readonly', false);
		$('#billing_postcode').val('').prop('readonly', false);
		$('#billing_city').val('').prop('readonly', false);

		$('#shipping_first_name').val('').prop('readonly', false);
		$('#shipping_last_name').val('').prop('readonly', false);
		$('#shipping_company').val('').prop('readonly', false);
		$('#shipping_address_1').val('').prop('readonly', false);
		//$('#shipping_address_2').val('').prop('readonly', false);
		$('#shipping_postcode').val('').prop('readonly', false);
		$('#shipping_city').val('').prop('readonly', false);
	}

	function maybe_readonly_afterpay_fields() {
		// Maybe make customer fields readonly (if customer is from Sweden, AfterPay is the selected payment method and customer address if fetched)
		var selected_payment_method = $('input[name="payment_method"]:checked').val();
		var customer_billing_country = $("#billing_country").val();
		if( 'yes' == se_address_fetched && 'SE' ==  customer_billing_country && selected_payment_method.indexOf('afterpay') >= 0 ) {
			$('#billing_first_name').prop('readonly', true);
			$('#billing_last_name').prop('readonly', true);
			$('#billing_address_1').prop('readonly', true);
			$('#billing_postcode').prop('readonly', true);
			$('#billing_city').prop('readonly', true);

			$('#shipping_first_name').prop('readonly', true);
			$('#shipping_last_name').prop('readonly', true);
			$('#shipping_address_1').prop('readonly', true);
			$('#shipping_postcode').prop('readonly', true);
			$('#shipping_city').prop('readonly', true);
		}

		// Remove readonly if AfterPay isn't the selected payment method
		if( 'yes' == se_address_fetched && 'SE' ==  customer_billing_country && selected_payment_method.indexOf('afterpay') < 0 ) {
			$('#billing_first_name').prop('readonly', false);
			$('#billing_last_name').prop('readonly', false);
			$('#billing_address_1').prop('readonly', false);
			$('#billing_postcode').prop('readonly', false);
			$('#billing_city').prop('readonly', false);

			$('#shipping_first_name').prop('readonly', false);
			$('#shipping_last_name').prop('readonly', false);
			$('#shipping_address_1').prop('readonly', false);
			$('#shipping_postcode').prop('readonly', false);
			$('#shipping_city').prop('readonly', false);
		}
		
	}

	$(document).on('init_checkout', function (event) {
		var do_focus = 'yes';
		maybe_show_pre_checkout_form(do_focus);
	});
	$(document).on('updated_checkout', function (event) {
		var do_focus = 'no';
		maybe_show_pre_checkout_form(do_focus);
	});
	
	$(document).on('change', 'input[name="payment_method"]', function (event) {
		var do_focus = 'yes';
		//maybe_show_pre_checkout_form(do_focus);
		
		$('body').trigger('update_checkout');

		maybe_readonly_afterpay_fields();
		

		/*
		var selected = $('input[name="payment_method"]:checked').val();
		if (selected.indexOf('afterpay') < 0) {
			$('#afterpay-pre-check-customer-response').remove();
			wipe_afterpay_fields();
		} else {
			// If switching to AfterPay and customer info is fetched, use that to populate the fields
			if (customer_info_fetched) {
				populate_afterpay_fields();
			}
		}
		*/
	});

	// Fire PreCheckCustomer when the button is clicked
	$(document).on('click', '.afterpay-get-address-button', function (event) {
		// Prevent the form from actually submitting
		event.preventDefault();

		trigger_ajax_pre_check_customer();
	});

	// Fire check_separate_shipping_address on radio button press
    $(document).on('click', '#afterpay-pre-check-customer', function () {
		check_separate_shipping_address('no');
    });
    
    // Hide/show customer lookup button when customer category radio button is changed
    $(document).on('change', 'input[name="afterpay_customer_category"]', function (event) {
		if ( $( '#afterpay-customer-category-company' ).is(":checked") ) {
			jQuery( '.afterpay-customer-lookup-button' ).hide();
			jQuery('li.payment_method_afterpay_part_payment').fadeOut();
		} else {
			jQuery( '.afterpay-customer-lookup-button' ).show();
			jQuery('li.payment_method_afterpay_part_payment').fadeIn();
		}
	});
	
	// Display of info about selected part payment method
	//$(document).on('change', 'input[type=radio][name=afterpay_installment_plan]', function () {
	//$("input[name=afterpay_installment_plan]:radio").on('change', function () {
	//jQuery('input:radio[name=afterpay_installment_plan]:checked').change(function () {
	$(document).on('change', 'input[name="afterpay_installment_plan"]', function (event) {
		//console.log('hej');
		var selectedOption =  $('input[name="afterpay_installment_plan"]:checked').val();
		//var test = $('input[name=afterpay_installment_plan]:checked').val();
		console.log( selectedOption );
		

		$('.afterpay-ppp-details').hide().removeClass('visible-ppp');
		$('div.afterpay-ppp-details[data-campaign=' + selectedOption + ']').show().addClass('visible-ppp');
	});
				
	// Fire PreCheckCustomer on update_checkout
	// $(document).on('update_checkout', function(event) {
		// trigger_ajax_pre_check_customer();
	// });

	function trigger_ajax_pre_check_customer() {
		// Remove success note, in case it's already there
		$('#afterpay-pre-check-customer-response').remove();

		var selected_payment_method = $('input[name="payment_method"]:checked').val();
		var selected_customer_category = $('input[name="afterpay_customer_category"]:checked').val();
		var entered_personal_number = $('#afterpay-pre-check-customer .afterpay-pre-check-customer-number').val();
		var entered_email = $('#afterpay-customer-email').val();
		var selected_billing_country = $("#billing_country").val();
		$('.afterpay-pre-check-customer-number').val(entered_personal_number);

		if ('' != entered_personal_number) { // Check if the field is empty

			$('.afterpay-get-address-button').addClass('disabled');

			$.ajax(
				WC_AfterPay.ajaxurl,
				{
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'afterpay_pre_check_customer',
						personal_number: entered_personal_number,
						email: entered_email,
						payment_method: selected_payment_method,
						customer_category: selected_customer_category,
						billing_country: selected_billing_country,
						nonce: WC_AfterPay.afterpay_pre_check_customer_nonce
					},
					success: function (response) {
						if (response.success) { // wp_send_json_success
							console.log(response.data);

							$('body').trigger('update_checkout');

							customer_data = response.data.response;

							customer_info_fetched = true;
							customer_first_name   = customer_data.first_name;
							customer_last_name    = customer_data.last_name;
							customer_address_1    = customer_data.address_1;
							customer_postcode     = customer_data.postcode;
							customer_city         = customer_data.city;

							populate_afterpay_fields();

                            $('.afterpay-get-address-button').removeClass('disabled');
							$('#afterpay-pre-check-customer').append('<div id="afterpay-pre-check-customer-response" class="woocommerce-message">' + response.data.message + '</div>');

						} else { // wp_send_json_error
							console.log('ERROR:');
							console.log(response.data);

							$('body').trigger('update_checkout');

							$('#afterpay-pre-check-customer').append('<div id="afterpay-pre-check-customer-response" class="woocommerce-error">' + response.data.message + '</div>');
						}
					},
					error: function (response) {
						console.log('AJAX error');
						console.log(response);
					}
				}
			);
		} else { // If the field is empty show notification

		}
	}
	
	// Norway
	// Fire PreCheckCustomer when the button is clicked
	$(document).on('click', '.afterpay-customer-lookup-button', function (event) {
		
		// Prevent the form from actually submitting
		event.preventDefault();

		trigger_ajax_customer_lookup();
	});
	
	function trigger_ajax_customer_lookup() {
		// Remove success note, in case it's already there
		$('#afterpay-pre-check-customer-response').remove();

		var selected_payment_method = $('input[name="payment_method"]:checked').val();
		var selected_customer_category = $('input[name="afterpay_customer_category"]:checked').val();
		var entered_personal_number = $('#afterpay-pre-check-customer-number').val();
		
		var entered_mobile_number = $('#afterpay-pre-check-mobile-number').val();
		var selected_billing_country = $("#billing_country").val();
		//$('.afterpay-pre-check-customer-number').val(entered_personal_number);
		
		// Check if the field is empty
		if ('' == entered_mobile_number && '' == entered_personal_number ) { 
			// If the field is empty show notification
		} else { 
			// Make a request
			console.log('entered_personal_number ' + entered_personal_number);
			$('.afterpay-customer-lookup-button').addClass('disabled spinner');
			$.ajax(
				WC_AfterPay.ajaxurl,
				{
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'afterpay_customer_lookup',
						mobile_number: entered_mobile_number,
						personal_number: entered_personal_number,
						payment_method: selected_payment_method,
						customer_category: selected_customer_category,
						billing_country: selected_billing_country,
						nonce: WC_AfterPay.afterpay_pre_check_customer_nonce
					},
					success: function (response) {
						if (response.success) { // wp_send_json_success
							console.log(response.data);

							$('body').trigger('update_checkout');

							customer_data = response.data.response;

							customer_info_fetched = true;
							customer_first_name   = customer_data.first_name;
							customer_last_name    = customer_data.last_name;
							customer_address_1    = customer_data.address_1;
							customer_postcode     = customer_data.postcode;
							customer_city         = customer_data.city;

							populate_afterpay_fields();

                            $('.afterpay-get-address-button').removeClass('disabled');
							$('#afterpay-pre-check-customer').append('<div id="afterpay-pre-check-customer-response" class="woocommerce-message">' + response.data.message + '</div>');

						} else { // wp_send_json_error
							console.log('ERROR:');
							console.log(response.data);

							$('body').trigger('update_checkout');

							$('#afterpay-pre-check-customer').append('<div id="afterpay-pre-check-customer-response" class="woocommerce-error">' + response.data.message + '</div>');
						}
						$('.afterpay-customer-lookup-button').removeClass('disabled spinner');
					},
					error: function (response) {
						console.log('AJAX error');
						console.log(response);
					}
				}
			);
		}
	}

	$(document).ready(function(){
		$('input[name=afterpay-pre-check-mobile-number]').keyup(function(){
			$('input[name=billing_phone]').val($(this).val());
		});

		// Maybe make customer fields readonly
		maybe_readonly_afterpay_fields();
	});

	// Fixed Address for German customers.
	var AfterpayFixedAddress = {
        handleHashChange : function(event){
			
			var currentHash = location.hash;
			var splittedHash = currentHash.split("=");
            if(splittedHash[0] === "#afterpay"){
				console.log('AfterPay hashchange');
				$response = JSON.parse( atob( splittedHash[1] ) );
				$('form[name="checkout"]').removeClass( 'processing' ).unblock();
				console.log($response);
				$('#billing_first_name').val($response.first_name);
				$('#billing_last_name').val($response.last_name);

				
				$('#billing_address_2').val($response.address2);
				$('#billing_postcode').val($response.postcode);
				$('#billing_city').val($response.city);
				$('#shipping_first_name').val($response.first_name);
				$('#shipping_last_name').val($response.last_name);
				$('#shipping_address_1').val($response.address1);
				$('#shipping_postcode').val($response.postcode);
				$('#shipping_city').val($response.city);

				// Street number logic.
				if( WC_AfterPay.street_number_field ) {
					$('#billing_address_1').val($response.address1);
					$('#' + WC_AfterPay.street_number_field).val($response.street_number);
				} else {
					$('#billing_address_1').val($response.address1 + ' ' + $response.street_number);
				}
				// Maybe remove old message.
				if ($('#afterpay-address-changed-message').length) {
					$('#afterpay-address-changed-message').remove();
				}
				// Add notice telling customer that the address have been changed.
				$('form.checkout').prepend( '<div id="afterpay-address-changed-message" class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"><ul class="woocommerce-error" role="alert"><li>' +  $response.message + '</li></ul></div>' );
				var etop = $('form.checkout').offset().top -100;
				console.log(etop);
				$('html, body').animate({
					scrollTop: etop
					}, 1000);
            }
        }
	};
	// Hash change listener for German Fixed address change.
	window.addEventListener("hashchange",AfterpayFixedAddress.handleHashChange);

});