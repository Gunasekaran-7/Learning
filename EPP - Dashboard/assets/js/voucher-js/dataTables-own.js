jQuery(document).ready(function(){
        
	jQuery('.reassign-voucher').on('click', function(){
		//jQuery('#reassign_modal').fadeIn();
	});

	jQuery('.close_btn, #close_button').on('click', function(){
		jQuery('#reassign_modal').fadeOut();
	});


	jQuery(window).on('click', function(event){
		if (jQuery(event.target).is('#reassign_modal')) {
			jQuery('#reassign_modal').fadeOut();
		}
	});

	jQuery('#voucher-assign').DataTable({
		"order": [],   
		"paging": true,           
		"searching": true,        
		"info": true              
	});

	 
    
	 
});

jQuery(document).ready(function() {
	var table = jQuery('#voucher-assign').DataTable();

	 

	jQuery('#voucher-assign tbody').on('click', '.assign-voucher', function (e) {
		var row = jQuery(this).closest('tr');  
		var email = jQuery(row).find('.voucher-email').val();
		var vouchervalue = jQuery(row).find('.voucher-td').val();  
		var id = jQuery(this).attr('data-voucher');
		vouchervalue = jQuery(row).find('.voucher-td').text();  
			 
		 
		var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

		if (!emailPattern.test(email)) {
			jQuery(row).find('.voucher-email').val('Your email is not valid');
			return false;  
		}


		jQuery.ajax({
			url: site_data.ajaxUrl,
			type: 'POST',
			data: {
				action: 'voucher_list_ajax',
				id: id, 
				email: email, 
				vouchervalue: vouchervalue,
			},
			success: function (response) {
				 
				jQuery(row).find('.voucher-email').prop('readonly',true);   

                jQuery(row).find('.assign-voucher').text('Reassign');

				jQuery(row).find('.assign-voucher').removeClass('assign-voucher').addClass('reassign-voucher');

				

				jQuery('#success_message_out').text("Voucher assigned successfully!").fadeIn().css('opacity', '1');

				setTimeout(function () {
					jQuery('#success_message_out').fadeOut().css('opacity', '0');
				}, 3000);

				// setTimeout(function () {
				// 	location.reload();  
				// }, 3000);
			 
			},
			error: function (xhr, status, error) {
				console.error("AJAX Error:", error, xhr.responseText);
			}
		});
	});

 

	// jQuery('#voucher-assign tbody').on('click', '.reassign-voucher', function () {
	// 	var row = jQuery(this).closest('tr');  
	// 	var email = jQuery(row).find('.voucher-email').val();
	// 	var id = jQuery(this).attr('data-voucher');

	// 	jQuery('#reassign_modal').fadeIn();
		
	// 		var rn_email = jQuery('#email_reassing').val(email);
			 
		 
	// 	jQuery.ajax({
	// 		url: site_data.ajaxUrl,
	// 		type: 'POST',
	// 		data: {
	// 			action: 'trigger_password_reset',
	// 			id: id, 
	// 			email: email,
	// 			rn_email:rn_email,
	// 		},
	// 		success: function (response) {
	// 			console.log(response);
	// 		},
	// 		error: function (xhr, status, error) {
	// 			console.error("AJAX Error:", error, xhr.responseText);
	// 		}
	// 	});
	// });



// jQuery(document).ready(function () {
//     var voucherId = "";
//     var emailValue = "";

//     jQuery('#voucher-assign tbody').on('click', '.reassign-voucher', function () {
//         var row = jQuery(this).closest('tr');
//         emailValue = jQuery(row).find('.voucher-email').val();
//         voucherId = jQuery(this).attr('data-voucher');

		 

		 

//         jQuery('#email_reassing').val(emailValue);
//         jQuery('#email_reassing').data('row', row);   

//         jQuery('#reassign_modal').fadeIn();
//     });

//     jQuery('#confirm_button').on('click', function () {
//         var rn_email = jQuery('#email_reassing').val();
//         var row = jQuery('#email_reassing').data('row');  

// 		var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
// 		if (!emailPattern.test(rn_email)) {
			
           
// 			jQuery(row).find('#email_reassing').val('not valid');
// 			return false;  
// 		}

//         jQuery.ajax({
//             url: site_data.ajaxUrl,
//             type: 'POST',
//             data: {
//                 action: 'trigger_password_reset',
//                 id: voucherId,
//                 email: emailValue,
//                 rn_email: rn_email
//             },
//             success: function (response) {
//                 console.log(response);
//                 jQuery('#reassign_modal').fadeOut();

//                 jQuery(row).find('.voucher-email').val(rn_email);   
//             },
//             error: function (xhr, status, error) {
//                 console.error("AJAX Error:", error, xhr.responseText);
//             }
//         });
//     });
// });


 

jQuery(document).ready(function () {
    var voucherId = "";
    var emailValue = "";
	var vouchervalue ="";

    jQuery('#voucher-assign tbody').on('click', '.reassign-voucher', function () {

        var row = jQuery(this).closest('tr');  
        emailValue = jQuery(row).find('.voucher-email').val();  
		vouchervalue = jQuery(row).find('.voucher-td').text();  
        voucherId = jQuery(this).attr('data-voucher'); 

		  
        jQuery('#email_reassing').val(emailValue);  
        jQuery('#email_reassing').data('row', row);  

        jQuery('#reassign_modal').fadeIn();  

    });

    jQuery('#confirm_button').on('click', function () {

        var rn_email = jQuery('#email_reassing').val();  

        var row = jQuery('#email_reassing').data('row');

		 
		rn_vouchervalue = jQuery(row).find('.voucher-td').text();  
		 

        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(rn_email)) {
            jQuery('#email_reassing').val('Your email is not valid');  
            return false;  
        }

        jQuery.ajax({
            url: site_data.ajaxUrl,
            type: 'POST',
            data: {
                action: 'voucher_list_ajax',
                id: voucherId,
                email: emailValue,
                rn_email: rn_email,
				rn_vouchervalue: rn_vouchervalue,
            },
            success: function (response) {
			 
				jQuery('#success_message').text("Voucher Re-assigned successfully!").fadeIn().css('opacity', '1');


				setTimeout(function () {
					jQuery('#success_message').fadeOut().css('opacity', '0');
				}, 3000);

                jQuery('#reassign_modal').fadeOut();
                jQuery(row).find('.voucher-email').val(rn_email);

            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", error, xhr.responseText);
            }
        });
    });
});
 


 

});


 

jQuery(document).ready(function () {
    jQuery('#voucher_orders').DataTable({
        scrollX: true,
		responsive: true,
        pagingType: 'simple_numbers',
        paging: true,
        language: {
            paginate: {
                previous: 'Previous',
                next: 'Next'
            }
        },
        autoWidth: false,  
		
    });
});
