//
function ciniki_customers_membertools() {
	//
	// Panels
	//
	this.init = function() {
		this.toggleOptions = {'no':'No', 'yes':'Yes'};
		//
		// The tools menu 
		//
		this.menu = new M.panel('Member Tools',
			'ciniki_customers_membertools', 'menu',
			'mc', 'narrow', 'sectioned', 'ciniki.customers.membertools.menu');
		this.menu.data = {};
		this.menu.sections = {
			'tools':{'label':'Downloads', 'list':{
				'directory':{'label':'Directory (Word)', 'fn':'M.ciniki_customers_membertools.downloadDirectory();'},
				'memberlist':{'label':'Member List (Excel)', 'fn':'M.ciniki_customers_membertools.showMemberList(\'M.ciniki_customers_membertools.showMenu();\');'},
				}},
			};
		this.menu.addClose('Back');

		//
		// The member list fields available to download
		//
		this.memberlist = new M.panel('Member List',
			'ciniki_customers_membertools', 'memberlist',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.membertools.memberlist');
		this.memberlist.data = {};
		this.memberlist.sections = {
			'options':{'label':'Data to include', 'aside':'yes', 'fields':{
				'prefix':{'label':'Name Prefix', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'first':{'label':'First Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'middle':{'label':'Middle Name', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'last':{'label':'Last Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'suffix':{'label':'Name Suffix', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'company':{'label':'Company', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'department':{'label':'Department', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'title':{'label':'Title', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'type':{'label':'Customer Type', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'visible':{'label':'Web Visible', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'member_status':{'label':'Status', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'member_lastpaid':{'label':'Last Paid Date', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'membership_length':{'label':'Membership Length', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'membership_type':{'label':'Membership Type', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'member_categories':{'label':'Categories', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
			}},
			'options2':{'label':'More Options', 'fields':{
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
				'selectall':{'label':'Select All', 'fn':'M.ciniki_customers_membertools.selectAll();'},
				'download':{'label':'Download Excel', 'fn':'M.ciniki_customers_membertools.downloadListExcel();'},
				}},
			};
		this.memberlist.fieldValue = function(s, i, j, d) {
			return M.ciniki_customers_membertools.memberlist.sections[s].fields[i].default;
		};
		this.memberlist.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_membertools', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}

		var slabel = 'Member';
		var plabel = 'Members';
		if( M.curBusiness.customers != null ) {
			if( M.curBusiness.customers.settings['ui-labels-member'] != null 
				&& M.curBusiness.customers.settings['ui-labels-member'] != ''
				) {
				slabel = M.curBusiness.customers.settings['ui-labels-member'];
			}
			if( M.curBusiness.customers.settings['ui-labels-members'] != null 
				&& M.curBusiness.customers.settings['ui-labels-members'] != ''
				) {
				plabel = M.curBusiness.customers.settings['ui-labels-members'];
			}
		}
		this.menu.title = slabel + ' Tools';
		this.memberlist.title = 'Export ' + plabel;
		this.menu.sections.tools.list.memberlist.label = 'Export ' + plabel + ' (Excel)';

		if( (M.curBusiness.modules['ciniki.customers'].flags&0x08) > 0 ) {
			this.memberlist.sections.options.fields.member_lastpaid.active = 'yes';
			this.memberlist.sections.options.fields.membership_length.active = 'yes';
			this.memberlist.sections.options.fields.membership_type.active = 'yes';
		} else {
			this.memberlist.sections.options.fields.member_lastpaid.active = 'no';
			this.memberlist.sections.options.fields.membership_length.active = 'no';
			this.memberlist.sections.options.fields.membership_type.active = 'no';
		}

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
		window.open(M.api.getUploadURL('ciniki.customers.memberDownloadDirectory', 
			{'business_id':M.curBusinessID}));
	};

	this.showMemberList = function(cb) {
		this.memberlist.refresh();
		this.memberlist.show(cb);
	};

	this.selectAll = function() {
		var fields = this.memberlist.sections.options.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			this.memberlist.setFieldValue(i, 'yes')
		}
		fields = this.memberlist.sections.options2.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			this.memberlist.setFieldValue(i, 'yes')
		}
	}

	this.downloadListExcel = function() {	
		var cols = '';
		var fields = this.memberlist.sections.options.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			if( this.memberlist.formFieldValue(fields[i], i) == 'yes' ) {
				cols += (cols!=''?'::':'') + i;
			}
		}
		fields = this.memberlist.sections.options2.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			if( this.memberlist.formFieldValue(fields[i], i) == 'yes' ) {
				cols += (cols!=''?'::':'') + i;
			}
		}
		window.open(M.api.getUploadURL('ciniki.customers.memberDownloadExcel', 
			{'business_id':M.curBusinessID, 'columns':cols}));
	};
}
