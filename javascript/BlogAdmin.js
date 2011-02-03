Behaviour.register({
	'#Form_EditForm' : {
		getPageFromServer : function(id) {
			statusMessage("loading...");
			this.loadURLFromServer('admin/blog/' + id);
			$('sitetree').setCurrentByIdx(id);
		},
		
		changeDetection_fieldsToIgnore : {
			'ctf[start]' : true,
			'ctf[ID]' : true,
			'ctf[filter][CategoryID]' : true,
			'ctf[filter][ParentID]' : true,
			'ctf[filter][Status]' : true,
			'ctf[filter][Search]' : true,
			'ctf[filter][Author]' : true,
			'ctf[filter][AuthorMemberID]' : true,
			'BlogFilterButton' : true,
			'Entries[]' : true
		},
		
		// prevent submission of wrong form-button (CommentFilterButton)
		prepareSearch: function(e) {
			// IE6 doesnt send an event-object with onkeypress
			var event = (e) ? e : window.event;
			var keyCode = (event.keyCode) ? event.keyCode : event.which;
			
			if(keyCode == Event.KEY_RETURN) {
				var el = Event.element(event);
				$('BlogFilterButton').onclick(event);
				Event.stop(event);
				return false;
			}
		}
		
	},
	

	'.filterBox input.text' : {
		initialize: function(){
			jQuery(this).addClass('virgin');
		},
		
		onfocus: function(){
			if (this.value.match(/^[A-Z][a-z]+\.\.\.$/)) {
				this.initialVal = this.value;
				jQuery(this).removeClass('virgin').val('');
			}
		},
		
		onblur: function(){
			if (this.value == '') {
				jQuery(this).addClass('virgin').val(this.initialVal);
			}
		}
	},
	
	
	'#Form_EditForm_action_doSavePost' : {
		'onclick' : function() {
			if (typeof tinyMCE != 'undefined') tinyMCE.triggerSave();
		}
	},
	
	
	'.filterLink': {
		'onclick' : function() {
			// update the filter dropdowns
			jQuery('#Form_EditForm select, #Form_ListForm_ctf-filter-Search').val('');

			var parts = this.href.split('?');
			if (parts.length > 1) {
				parts = parts[1].split('&');
				for (var i = 0; i < parts.length; i++) {
					var bits = parts[i].split('=');
					if (bits[0].substr(0,3) == 'ctf') {
						var id = '#Form_ListForm_' + bits[0].replace(/\[/g,'-').replace(/\]/g,'');
						jQuery(id).val(unescape(bits[1]));
					}
				}
			}

			var s = jQuery('#Form_ListForm_ctf-filter-Search');
			if (s.val() == '') {
				s.addClass('virgin').val('Search...');
			} else {
				s.removeClass('virgin');
			}


			// send the request
    		new Ajax.Updater('Form_ListForm_Entries', this.href, {
    			onComplete: function() {
    			    Behaviour.apply($('Form_ListForm_Entries'), true);
    			},
    			onFailure: function( response ) {
    				errorMessage('Could not filter results: ' + response.responseText );
    			}.bind(this)
    		});
    		
			Event.stop(event);
		}
	},
	
	'#BlogFilterButton' : {
		initialize: function() {
			this.inputFields = new Array();
			
			var childNodes = this.parentNode.parentNode.getElementsByTagName('input');
			
			for( var index = 0; index < childNodes.length; index++ ) {
				if( childNodes[index].tagName ) {
					childNodes[index].resetChanged = function() { return false; }
					childNodes[index].isChanged = function() { return false; }
					this.inputFields.push( childNodes[index] );
				}
			}
			
			childNodes = this.parentNode.parentNode.getElementsByTagName('select');
			
			for( var index = 0; index < childNodes.length; index++ ) {
				if( childNodes[index].tagName ) {
					childNodes[index].resetChanged = function() { return false; }
					childNodes[index].field_changed = function() { return false; }
					this.inputFields.push( childNodes[index] );
				}
			}
		},
		
		isChanged: function() {
			return false;
		},
		
		// @todo: lots of hardcoding here - may want to get rid of that
		// @note: there is a conflict here where the form element points to EditForm but
		// all the content points to ListForm (b/c of the way the ajax works). that's why
		// all the hardcoding happens
		onclick: function(e) {
		    try {
	    	    var form = Event.findElement(e,"form");
	    	    var fieldName = 'Entries';
	    	    var fieldID = 'Form_ListForm_Entries'; //form.id + '_' + fieldName;
	
//	    		var updateURL = form.action + '/field/' + fieldName + '?ajax=1';
	    		var updateURL = 'admin/blog/ListForm/field/Entries?ajax=1';
	    		for( var index = 0; index < this.inputFields.length; index++ ) {
	    			if( this.inputFields[index].tagName ) {
	    				updateURL += '&' + this.inputFields[index].name + '=' + encodeURIComponent( this.inputFields[index].value );
	    			}
	    		}
	    		updateURL += ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');
	
	    		new Ajax.Updater( fieldID, updateURL, {
	    			onComplete: function() {
	    			    Behaviour.apply($(fieldID), true);
	    			},
	    			onFailure: function( response ) {
	    				errorMessage('Could not filter results: ' + response.responseText );
	    			}.bind(this)
	    		});
			} catch(er) {
				errorMessage('Error searching');
			}
			
			return false;	
		}
	}
});
