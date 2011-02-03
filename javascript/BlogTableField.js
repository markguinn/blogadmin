Behaviour.register({
	'#select_all_posts' : {
		'onclick' : function(){
			// this only really allows for a single blogtablefield at one time, but i'm ok with that for now
			jQuery('.BlogTableField .markingcheckbox .checkbox').attr('checked', this.checked ? 'checked' : '');
		}
	},
	
	'a.editbloglink' : {
		'onclick' : function(){
			statusMessage("loading...");
			$('Form_EditForm').loadURLFromServer(this.href);
			return false;
		}
	},
	
	'a.showbloglink' : {
		'onclick' : function(){
			this.target = '_blank';
		}
	},
	
	'a.commentsLink' : {
		'onclick' : function(){
			_CUR_TABS[''] = 'Root_Comments';
			statusMessage("loading...");
			$('Form_EditForm').loadURLFromServer(this.href);
			return false;
		}
	}
});
