jQuery(document).ready(function($){

	jQuery(document).on('click','.voucher-filter',function(){
					
    jQuery('.voucher_table').html('<div style="width:100%;height:200px;text-align:center;"><img  class="loading-gif" src="/wp-content/uploads/2025/03/loading-gif.gif"></div>');
    var conceptName = jQuery('#study').find(":selected").val();

  
    jQuery.ajax({
        url: my_ajax_object.admin_ajax_url,
        type: 'POST',
        data: {
          'action':'voucher_filter_ajax',
          'select_data':conceptName,
        },
        dataType: 'html',
        success: function(data) {
        jQuery('.voucher_table').html(data);
        if (jQuery.fn.DataTable.isDataTable('#tblData')) {
          jQuery('#tblData').DataTable().destroy();
      }

      // Reinitialize DataTable
      jQuery('#tblData').DataTable({
          colReorder: true,
          scrollX: true,
          searching: true,
          pagingType: 'simple_numbers',
          language: { paginate: { previous: 'Previous', next: 'Next' } },
          dom: 'Blfrtip',
          buttons: [
              { extend: 'csvHtml5', text: 'CSV' },
              'colvis'
          ]
      });
         

        },error: function(request, errorThrown){
          console.log(errorThrown);
        }
    });
  });

if (my_ajax_object.is_logged_in && my_ajax_object.is_my_account_page) {
    setTimeout(runAjax, 2000); 
}


function runAjax() {
    jQuery.ajax({
        url: my_ajax_object.admin_ajax_url, 
        type: 'POST',
        data: {
            action: 'canvas_data_ajax_action', 
        },
        success: function(response) {
            console.log('AJAX request completed.');
            console.log('Success message:', response.data.success);
        },
       
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          console.error('Status:', status);
          console.error('XHR:', xhr.responseText);
        }
    });
}




//   jQuery('#billing_first_name').on('keydown', function(e){
//     console.log('testing');
//     // var firstName = jQuery(this).val();
//     // console.log('jhsakdjhs');
//     // var name_regex = /^[a-zA-Z]+$/;
//     // if (!name_regex.test(firstName)) {
//     //     e.preventDefault();
//     // }
//     if (
//     (e.keyCode >= 65 && e.keyCode <= 90) || 
//     (e.keyCode >= 97 && e.keyCode <= 122) || 
//     (e.keyCode >= 48 && e.keyCode <= 57) || 
//     e.keyCode == 32 || 
//     e.keyCode == 9 || 
//     e.keyCode == 8 
//     ) {
//     return; 
//     }
//     else{
//         e.preventDefault();
//     }
    
// });
// jQuery('#firstname-95d56e5c-e8cc-4d2c-b57a-b97c3db5c9de').on('keydown',function(e){
//     console.log('testing');
//     if (
//     (e.keyCode >= 65 && e.keyCode <= 90) || 
//     (e.keyCode >= 97 && e.keyCode <= 122) || 
//     (e.keyCode >= 48 && e.keyCode <= 57) || 
//     e.keyCode == 32 || 
//     e.keyCode == 9 || 
//     e.keyCode == 8 
//     ) {
//     return; 
//     }
//     else{
//         e.preventDefault();
//     }
// });
// jQuery('#lastname-95d56e5c-e8cc-4d2c-b57a-b97c3db5c9de').on('keydown',function(e){
//     console.log('testing');
//     if (
//     (e.keyCode >= 65 && e.keyCode <= 90) || 
//     (e.keyCode >= 97 && e.keyCode <= 122) || 
//     (e.keyCode >= 48 && e.keyCode <= 57) || 
//     e.keyCode == 32 || 
//     e.keyCode == 9 || 
//     e.keyCode == 8 
//     ) {
//     return; 
//     }
//     else{
//         e.preventDefault();
//     }
// });
// function validateInput(e) {
//   var key = String.fromCharCode(e.keyCode);
//   var regex = /^[a-zA-Z0-9\s\t\b]+$/;
  
//   if (regex.test(key) || e.keyCode == 8) {
//       return; 
//   } else {
//       e.preventDefault();
//   }
// }

// jQ('#billing_first_name').on('keydown', validateInput);
// $('#firstname-95d56e5c-e8cc-4d2c-b57a-b97c3db5c9de').on('keydown', validateInput);
// $('#lastname-95d56e5c-e8cc-4d2c-b57a-b97c3db5c9de').on('keydown', validateInput);
});






