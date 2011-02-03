(function($){
	var timerID;
	
	// make the columns sortable
	$('div.WidgetDashboardColumn').sortable({
		connectWith: 	'div.WidgetDashboardColumn',
	}).bind('sortupdate', function(event, ui){
		// serialize the widgets in this column and stick them in the right hidden field
		var a =	$(this).sortable('toArray');
		for (var i = 0; i < a.length; i++) a[i] = a[i].replace('widget_', '');
		var f = $(this).closest('form').find('input[name='+this.id+']');
		f.val(a.join(','));

		// set a timer to save the data shortly, if the timer hasn't already
		// been set - this event could get fired on more than one column and
		// this allows us to only save the data once
		if (!timerID){
			timerID = setTimeout(function(f){
				// clear the timer
				timerID = null;

				// now just quietly submit the form via ajax
				f.ajaxSubmit();
			}, 1000, $(this).closest('form'));
		}
	});
	

})(jQuery);