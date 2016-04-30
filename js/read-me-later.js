jQuery(document).ready( function(){	
		
	jQuery('#content').on('click', 'a.rml_bttn', function(e) { 
		e.preventDefault();
		
		var rml_post_id = jQuery(this).data( 'id' );
		
		jQuery.ajax({
			url : rml_obj.ajax_url,
			type : 'post',
			data : {
				action : 'read_me_later',
				security : rml_obj.check_nonce,
				post_id : rml_post_id
			},
			success : function( response ) {
				jQuery('.rml_contents').html(response);
			}
		});
		
		jQuery(this).hide();
	});	
	
});