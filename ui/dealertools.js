//
function ciniki_customers_dealertools() {
	//
	// Panels
	//
	this.init = function() {
		this.toggleOptions = {'no':'No', 'yes':'Yes'};
		//
		// The tools menu 
		//
		this.menu = new M.panel('Dealer Tools',
			'ciniki_customers_dealertools', 'menu',
			'mc', 'narrow', 'sectioned', 'ciniki.customers.dealertools.menu');
		this.menu.data = {};
		this.menu.sections = {
			'tools':{'label':'Downloads', 'list':{
				'dealerlist':{'label':'Export Dealers (Excel)', 'fn':'M.ciniki_customers_dealertools.showMemberList(\'M.ciniki_customers_dealertools.showMenu();\');'},
				}},
			};
		this.menu.addClose('Back');

		//
		// The dealer list fields available to download
		//
		this.dealerlist = new M.panel('Dealer List',
			'ciniki_customers_dealertools', 'dealerlist',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.dealertools.dealerlist');
		this.dealerlist.data = {};
		this.dealerlist.sections = {
			'options':{'label':'Data to include', 'aside':'yes', 'fields':{
				'type':{'label':'Customer Type', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'prefix':{'label':'Name Prefix', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'first':{'label':'First Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'middle':{'label':'Middle Name', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'last':{'label':'Last Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'suffix':{'label':'Name Suffix', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'company':{'label':'Company', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'department':{'label':'Department', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'title':{'label':'Title', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'visible':{'label':'Web Visible', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				}},
			'options2':{'label':'', 'aside':'yes', 'fields':{
				'salesrep':{'label':'Sales Rep', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'pricepoint_name':{'label':'Pricepoint', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'pricepoint_code':{'label':'Pricepoint Code', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'tax_number':{'label':'Tax Number', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'tax_location_code':{'label':'Tax Code', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'reward_level':{'label':'Reward Level', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'sales_total':{'label':'Sales Total', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'start_date':{'label':'Start Date', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				}},
			'options3':{'label':'', 'fields':{
				'dealer_status':{'label':'Status', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'dealer_categories':{'label':'Categories', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'phones':{'label':'Phone Numbers', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'emails':{'label':'Emails', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'addresses':{'label':'Addresses', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'links':{'label':'Websites', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'primary_image':{'label':'Image', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'primary_image_caption':{'label':'Image Caption', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'short_description':{'label':'Short Bio', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'full_bio':{'label':'Full Bio', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				}},
			'_buttons':{'label':'', 'buttons':{
				'selectall':{'label':'Select All', 'fn':'M.ciniki_customers_dealertools.selectAll();'},
				'download':{'label':'Download Excel', 'fn':'M.ciniki_customers_dealertools.downloadListExcel();'},
				}},
			};
		this.dealerlist.fieldValue = function(s, i, j, d) {
			return M.ciniki_customers_dealertools.dealerlist.sections[s].fields[i].default;
		};
		this.dealerlist.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_dealertools', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}

		var slabel = 'Dealer';
		var plabel = 'Dealers';
		if( M.curBusiness.customers != null ) {
			if( M.curBusiness.customers.settings['ui-labels-dealer'] != null 
				&& M.curBusiness.customers.settings['ui-labels-dealer'] != ''
				) {
				slabel = M.curBusiness.customers.settings['ui-labels-dealer'];
			}
			if( M.curBusiness.customers.settings['ui-labels-dealers'] != null 
				&& M.curBusiness.customers.settings['ui-labels-dealers'] != ''
				) {
				plabel = M.curBusiness.customers.settings['ui-labels-dealers'];
			}
		}
		this.menu.title = slabel + ' Tools';
		this.dealerlist.title = 'Export ' + plabel;
		this.menu.sections.tools.list.dealerlist.label = 'Export ' + plabel + ' (Excel)';

		var flags = M.curBusiness.modules['ciniki.customers'].flags;
		this.dealerlist.sections.options2.fields.salesrep.active=((flags&0x2000)>0?'yes':'no');
		this.dealerlist.sections.options2.fields.pricepoint_name.active=((flags&0x1000)>0?'yes':'no');
		this.dealerlist.sections.options2.fields.pricepoint_code.active=((flags&0x1000)>0?'yes':'no');
		this.dealerlist.sections.options2.fields.tax_number.active=((flags&0x20000)>0?'yes':'no');
		this.dealerlist.sections.options2.fields.tax_location_code.active=((flags&0x40000)>0?'yes':'no');
		this.dealerlist.sections.options2.fields.reward_level.active=((flags&0x80000)>0?'yes':'no');
		this.dealerlist.sections.options2.fields.sales_total.active=((flags&0x100000)>0?'yes':'no');
		this.dealerlist.sections.options2.fields.start_date.active='yes';

		this.showMenu(cb);
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb) {
		this.menu.refresh();
		this.menu.show(cb);
	};

	this.downloadDirectory = function() {
		window.open(M.api.getUploadURL('ciniki.customers.dealerDownloadDirectory', 
			{'business_id':M.curBusinessID}));
	};

	this.showMemberList = function(cb) {
		this.dealerlist.refresh();
		this.dealerlist.show(cb);
	};

	this.selectAll = function() {
		var fields = this.dealerlist.sections.options.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			this.dealerlist.setFieldValue(i, 'yes')
		}
		fields = this.dealerlist.sections.options2.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			this.dealerlist.setFieldValue(i, 'yes')
		}
		fields = this.dealerlist.sections.options3.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			this.dealerlist.setFieldValue(i, 'yes')
		}
	}

	this.downloadListExcel = function() {	
		var cols = '';
		var fields = this.dealerlist.sections.options.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			if( this.dealerlist.formFieldValue(fields[i], i) == 'yes' ) {
				cols += (cols!=''?'::':'') + i;
			}
		}
		fields = this.dealerlist.sections.options2.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			if( this.dealerlist.formFieldValue(fields[i], i) == 'yes' ) {
				cols += (cols!=''?'::':'') + i;
			}
		}
		fields = this.dealerlist.sections.options3.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			if( this.dealerlist.formFieldValue(fields[i], i) == 'yes' ) {
				cols += (cols!=''?'::':'') + i;
			}
		}
		window.open(M.api.getUploadURL('ciniki.customers.dealerDownloadExcel', 
			{'business_id':M.curBusinessID, 'columns':cols}));
	};
}