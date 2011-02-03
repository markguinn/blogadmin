Behaviour.register({
	'#select_all_posts' : {
		'onclick' : function(){
			// this only really allows for a single blogtablefield at one time, but i'm ok with that for now
			jQuery('.AuthorTableField .markingcheckbox .checkbox').attr('checked', this.checked ? 'checked' : '');
		}
	},
	
	'a.editauthorlink' : {
		'onclick' : function(){
			statusMessage("loading...");
			$('Form_EditForm').loadURLFromServer(this.href);
			return false;
		}
	},
	
	'a.showauthorlink' : {
		'onclick' : function(){
			this.target = '_blank';
		}
	}

});
