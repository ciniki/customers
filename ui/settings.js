//
function ciniki_customers_settings() {
	//
	// Panels
	//
	this.main = null;
	this.add = null;

	this.toggleOptions = {'no':'Off', 'yes':'On'};
	this.formOptions = {'person':'Person', 'business':'Business'};
	this.typeOptions = {'person':'Person', 'business':'Business'};
	this.businessFormats = {
		'company':'Company',
		'company - person':'Company - Person',
		'person - company':'Person - Company',
		'company [person]':'Company [Person]',
		'person [company]':'Person [Company]',
	};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.main = new M.panel('Settings',
			'ciniki_customers_settings', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.customers.settings.main');
		this.main.sections = {
			'_options':{'label':'Options', 'fields':{
				'use-cid':{'label':'Customer ID', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//				'use-relationships':{'label':'Customer Relationships', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'use-birthdate':{'label':'Birthdays', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
			}},
			'name_options':{'label':'Name Format', 'fields':{
				'display-name-business-format':{'label':'Business', 'type':'select', 'options':this.businessFormats},
			}},
//			'_types':{'label':'Customer Types', 'type':'gridform', 'rows':8, 'cols':3, 
//				'header':['Name', 'Form', 'Type'],
//				'fields':[
//				[	{'id':'types-1-label', 'label':'Name', 'type':'text'},
//					{'id':'types-1-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//					{'id':'types-1-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//				],[ {'id':'types-2-label', 'label':'Name', 'type':'text'},
//					{'id':'types-2-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//					{'id':'types-2-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//				],[ {'id':'types-3-label', 'label':'Name', 'type':'text'},
//					{'id':'types-3-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//					{'id':'types-3-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//				],[ {'id':'types-4-label', 'label':'Name', 'type':'text'},
//					{'id':'types-4-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//					{'id':'types-4-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//				],[ {'id':'types-5-label', 'label':'Name', 'type':'text'},
//					{'id':'types-5-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//					{'id':'types-5-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//				],[ {'id':'types-6-label', 'label':'Name', 'type':'text'},
//					{'id':'types-6-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//					{'id':'types-6-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//				],[ {'id':'types-7-label', 'label':'Name', 'type':'text'},
//					{'id':'types-7-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//					{'id':'types-7-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//				],[ {'id':'types-8-label', 'label':'Name', 'type':'text'},
//					{'id':'types-8-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//					{'id':'types-8-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//				]],
//			},
		};

		this.main.fieldValue = function(s, i, d) { 
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};

		//  
		// Callback for the field history
		//  
		this.main.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.customers.getSettingHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
		};

		this.main.addButton('save', 'Save', 'M.ciniki_customers_settings.saveSettings();');
		this.main.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showMain(cb);
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMain = function(cb) {
		var rsp = M.api.getJSONCb('ciniki.customers.getSettings', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_customers_settings.main;
			p.data = rsp.settings;
			p.refresh();
			p.show(cb);
		});
	}

	this.saveSettings = function() {
		var c = this.main.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSONCb('ciniki.customers.updateSettings', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_customers_settings.main.close();
				});
		} else {
			this.main.close();
		}
	}
}
