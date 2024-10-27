jQuery(document).ready(function() {
	jQuery("input#_currency_xlm").on("change", function() {
		var this_val = jQuery(this).val();
		jQuery("input#_regular_price").val(this_val);
	});
	
	jQuery('[name^="_variation_currency_xlm"]').each(function() {
		var this_val = jQuery(this).val();
		alert(this_val);
	});
	
	jQuery("a.artg_schedule_price_show").click(function() {
		var thisdata_id = jQuery(this).data('id');
		
		jQuery("p."+thisdata_id).removeClass('hidden');
		jQuery(this).addClass("hidden");
		
		return false;
	});
	jQuery("a.artg_schedule_price_cancel").click(function() {
		var thisdata_id = jQuery(this).data('id');
		
		jQuery("p."+thisdata_id+" input").val('');
		jQuery("p."+thisdata_id).addClass('hidden');
		jQuery(this).addClass("hidden");
		jQuery("a.artg_schedule_price_show[data-id="+thisdata_id+"]").removeClass('hidden');
		
		return false;
	});
	
	jQuery(".artgdatepicker").datepicker({ 
		dateFormat: 'yy-mm-dd',
		minDate: 0,
	});
});