//
function ciniki_customers_download() {
	//
	// Panels
	//
	this.toggleOptions = {'no':'No', 'yes':'Yes'};
	this.init = function() {
		//
		// The member list fields available to download
		//
		this.exportlist = new M.panel('List',
			'ciniki_customers_download', 'exportlist',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.download.exportlist');
		this.exportlist.data = {};
		this.exportlist.sections = {
			'options':{'label':'Data to include', 'aside':'yes', 'fields':{
				'display_name':{'label':'Full Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'prefix':{'label':'Name Prefix', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'first':{'label':'First Name', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'middle':{'label':'Middle Name', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				'last':{'label':'Last Name', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
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
			'seasons':{'label':'Season Status', 'active':'no', 'fields':{}},
			'subscriptions':{'label':'Subscription Status', 'active':'no', 'fields':{}},
			'_buttons':{'label':'', 'buttons':{
				'selectall':{'label':'Select All', 'fn':'M.ciniki_customers_download.selectAll();'},
				'download':{'label':'Download Excel', 'fn':'M.ciniki_customers_download.downloadListExcel();'},
				}},
			};
		this.exportlist.fieldValue = function(s, i, j, d) {
			return M.ciniki_customers_download.exportlist.sections[s].fields[i].default;
		};
		this.exportlist.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_download', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}

		var slabel = 'Customer';
		var plabel = 'Customers';
		if( M.curBusiness.customers != null ) {
			if( M.curBusiness.customers.settings['ui-labels-customer'] != null 
				&& M.curBusiness.customers.settings['ui-labels-customer'] != ''
				) {
				slabel = M.curBusiness.customers.settings['ui-labels-customer'];
			}
			if( M.curBusiness.customers.settings['ui-labels-customers'] != null 
				&& M.curBusiness.customers.settings['ui-labels-customers'] != ''
				) {
				plabel = M.curBusiness.customers.settings['ui-labels-customers'];
			}
		}
		this.exportlist.title = 'Export ' + plabel;

		if( (M.curBusiness.modules['ciniki.customers'].flags&0x08) > 0 ) {
			this.exportlist.sections.options.fields.member_status.active = 'yes';
			this.exportlist.sections.options.fields.member_categories.active = 'yes';
		} else {
			this.exportlist.sections.options.fields.member_status.active = 'no';
			this.exportlist.sections.options.fields.member_categories.active = 'no';
		}
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x08) > 0 ) {
			this.exportlist.sections.options.fields.member_lastpaid.active = 'yes';
			this.exportlist.sections.options.fields.membership_length.active = 'yes';
			this.exportlist.sections.options.fields.membership_type.active = 'yes';
		} else {
			this.exportlist.sections.options.fields.member_lastpaid.active = 'no';
			this.exportlist.sections.options.fields.membership_length.active = 'no';
			this.exportlist.sections.options.fields.membership_type.active = 'no';
		}

		this.exportlist.selected_season = (args.selected_season!=null?args.selected_season:'');
		this.exportlist.membersonly = (args.membersonly!=null?args.membersonly:'');
		this.exportlist.subscription_id = (args.subscription_id!=null?args.subscription_id:'');

		M.ciniki_customers_download.showExportList(cb);
	}

	this.showExportList = function(cb, selected_season, membersonly, subscription_id) {
		//
		// Get seasons if enabled
		//
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x02000000) > 0 
			&& M.curBusiness.modules['ciniki.customers'].settings != null
			&& M.curBusiness.modules['ciniki.customers'].settings.seasons != null
			) {
			this.exportlist.sections.seasons.active = 'yes';
			this.exportlist.sections.seasons.fields = {};
			for(i in M.curBusiness.modules['ciniki.customers'].settings.seasons) {
				var season = M.curBusiness.modules['ciniki.customers'].settings.seasons[i].season;
				if( season.open == 'yes' ) {
					this.exportlist.sections.seasons.fields['season-' + season.id] = {
						'label':season.name, 
						'type':'toggle', 'default':(selected_season!=null&&selected_season==season.id?'yes':'no'), 
						'toggles':this.toggleOptions,
					};
				}
			}
		} else {
			this.exportlist.sections.seasons.active = 'yes';
		}

		//
		// Check if subscriptions are enabled
		//
		if( M.curBusiness.modules['ciniki.subscriptions'] != null ) {
			M.api.getJSONCb('ciniki.subscriptions.subscriptionList', {'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				} 
				var p = M.ciniki_customers_download.exportlist;
				p.subscriptions = rsp.subscriptions;
				if( rsp.subscriptions.length > 0 ) {
					p.sections.subscriptions.active = 'yes';
					p.sections.subscriptions.fields = {};
					for(i in rsp.subscriptions) {
						p.sections.subscriptions.fields['subscription-' + rsp.subscriptions[i].subscription.id] = {
							'label':rsp.subscriptions[i].subscription.name, 
							'type':'toggle', 'default':'no', 'toggles':M.ciniki_customers_download.toggleOptions,
						};
					}
				}
				p.refresh();
				p.show(cb);
			});
		} else {
			this.exportlist.sections.subscriptions.active = 'no';
			this.exportlist.refresh();
			this.exportlist.show(cb);
		}
	};

	this.selectAll = function() {
		var fields = this.exportlist.sections.options.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			this.exportlist.setFieldValue(i, 'yes')
		}
		fields = this.exportlist.sections.options2.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			this.exportlist.setFieldValue(i, 'yes')
		}
	}

	this.downloadListExcel = function() {	
		var cols = '';
		var fields = this.exportlist.sections.options.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			if( this.exportlist.formFieldValue(fields[i], i) == 'yes' ) {
				cols += (cols!=''?'::':'') + i;
			}
		}
		fields = this.exportlist.sections.options2.fields;
		for(i in fields) {
			if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
			if( this.exportlist.formFieldValue(fields[i], i) == 'yes' ) {
				cols += (cols!=''?'::':'') + i;
			}
		}
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x02000000) > 0 
			&& M.curBusiness.modules['ciniki.customers'].settings != null
			&& M.curBusiness.modules['ciniki.customers'].settings.seasons != null
			) {
			fields = this.exportlist.sections.seasons.fields;
			for(i in fields) {
				if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
				if( this.exportlist.formFieldValue(fields[i], i) == 'yes' ) {
					cols += (cols!=''?'::':'') + i;
				}
			}
		}
		if( M.curBusiness.modules['ciniki.subscriptions'] ) {
			fields = this.exportlist.sections.subscriptions.fields;
			for(i in fields) {
				if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
				if( this.exportlist.formFieldValue(fields[i], i) == 'yes' ) {
					cols += (cols!=''?'::':'') + i;
				}
			}
		}
		var args = {'business_id':M.curBusinessID, 'columns':cols};
		if( this.exportlist.membersonly != '' ) { args.membersonly = this.exportlist.membersonly; }
		if( this.exportlist.subscription_id != '' ) { args.subscription_id = this.exportlist.subscription_id; }
		window.open(M.api.getUploadURL('ciniki.customers.customerListExcel', args));
	};
}
