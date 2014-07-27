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
	this.pricepointFlags = {
		'1':{'name':'Flexible'},
		};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.main = new M.panel('Settings',
			'ciniki_customers_settings', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.customers.settings.main');
		this.main.sections = {
//			'_options':{'label':'Options', 'fields':{
//				'use-cid':{'label':'Customer ID', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//				'use-relationships':{'label':'Customer Relationships', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//				'use-reward-teir':{'label':'Reward Teir', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//				'use-sales-total':{'label':'Sales Total', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//				'use-tax-number':{'label':'Tax Number', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//				'use-tax-location-id':{'label':'Tax Location', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//				'use-birthdate':{'label':'Birthdays', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//			}},
			'name_options':{'label':'Name Format', 'fields':{
				'display-name-business-format':{'label':'Business', 'type':'select', 'options':this.businessFormats},
			}},
			'pricepoints':{'label':'Price Points', 'visible':'no', 'type':'simplegrid',
				'num_cols':1,
				'addTxt':'Add Price Point',
				'addFn':'M.ciniki_customers_settings.editPricePoint(\'M.ciniki_customers_settings.showMain();\',0);',
			},
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
		this.main.sectionData = function(s) { 
			if( s == 'pricepoints' ) { return this.data[s]; }
			return this.data; 
		}
		this.main.fieldValue = function(s, i, d) { 
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.main.cellValue = function(s, i, j, d) {
			if( d.pricepoint.code != null && d.pricepoint.code != '' ) { return d.pricepoint.code + ' - ' + d.pricepoint.name; }
			return d.pricepoint.name;
		};
		this.main.rowFn = function(s, i, d) {
			return 'M.ciniki_customers_settings.editPricePoint(\'M.ciniki_customers_settings.showMain();\',\'' + d.pricepoint.id + '\');';
		}
		this.main.fieldHistoryArgs = function(s, i) {
			if( s == 'pricepoints' ) {
				return {'method':'ciniki.customers.pricepointHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
			}
			return {'method':'ciniki.customers.getSettingHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
		};
		this.main.addButton('save', 'Save', 'M.ciniki_customers_settings.saveSettings();');
		this.main.addClose('Cancel');

		//
		// The panel to add/edit a price point
		//
		this.pricepoint = new M.panel('Price Point',
			'ciniki_customers_settings', 'pricepoint',
			'mc', 'medium', 'sectioned', 'ciniki.customers.settings.pricepoint');
		this.pricepoint.pricepoint_id = 0;
		this.pricepoint.sections = {
			'price':{'label':'Price Point', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'code':{'label':'Code', 'type':'text', 'size':'medium'},
				'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
				'flags':{'label':'Options', 'type':'flags', 'flags':this.pricepointFlags},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_customers_settings.savePricePoint();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_customers_settings.deletePricePoint();'},
				}},
		}
		this.pricepoint.fieldValue = function(s, i, d) { 
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.pricepoint.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.customers.pricepointHistory', 
				'args':{'business_id':M.curBusinessID, 'field':i}};
		};
		this.pricepoint.addButton('save', 'Save', 'M.ciniki_customers_settings.savePricePoint();');
		this.pricepoint.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( (M.curBusiness.modules['ciniki.customers'].flags&0x1000) > 0 ) {
			M.ciniki_customers_settings.main.sections.pricepoints.visible = 'yes';
		} else {
			M.ciniki_customers_settings.main.sections.pricepoints.visible = 'no';
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
			if( rsp.pricepoints != null ) {
				p.data.pricepoints = rsp.pricepoints;
			}
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

	this.editPricePoint = function(cb, pid) {
		if( pid != null ) { this.pricepoint.pricepoint_id = pid; }
		if( this.pricepoint.pricepoint_id > 0 ) {
			this.pricepoint.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.customers.pricepointGet', {'business_id':M.curBusinessID, 
				'pricepoint_id':this.pricepoint.pricepoint_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_customers_settings.pricepoint;
					p.data = rsp.pricepoint;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.pricepoint.sections._buttons.buttons.delete.visible = 'no';
			this.pricepoint.data = {};
			this.pricepoint.refresh();
			this.pricepoint.show(cb);
		}
	};

	this.savePricePoint = function() {
		if( this.pricepoint.pricepoint_id > 0 ) {
			var c = this.pricepoint.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.customers.pricepointUpdate', 
					{'business_id':M.curBusinessID, 
					'pricepoint_id':M.ciniki_customers_settings.pricepoint.pricepoint_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_customers_settings.pricepoint.close();
					});
			} else {
				this.pricepoint.close();
			}
		} else {
			var c = this.pricepoint.serializeForm('yes');
			M.api.postJSONCb('ciniki.customers.pricepointAdd', 
				{'business_id':M.curBusinessID, 'pricepoint_id':this.pricepoint.pricepoint_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_customers_settings.pricepoint.close();
				});
		}
	};

	this.deletePricePoint = function() {
		if( confirm("Are you sure you want to remove this price point?") ) {
			M.api.getJSONCb('ciniki.customers.pricepointDelete', 
				{'business_id':M.curBusinessID, 
				'pricepoint_id':this.pricepoint.pricepoint_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_settings.pricepoint.close();	
				});
		}
	};
}
