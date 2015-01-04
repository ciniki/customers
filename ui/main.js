//
function ciniki_customers_main() {
	//
	// Panels
	//
	this.menu = null;

	this.toggleOptions = {'Off':'Off', 'On':'On'};
	this.subscriptionOptions = {'60':'Unsubscribed', '10':'Subscribed'};
	this.addressFlags = {'1':{'name':'Shipping'}, '2':{'name':'Billing'}, '3':{'name':'Mailing'}};
	this.emailFlags = {
		'1':{'name':'Web Login'}, 
		'5':{'name':'No Emails'},
//		'6':{'name':'Secondary'},
		};

	this.statusOptions = {
		'10':'Ordered',
		'20':'Started',
		'25':'SG Ready',
		'30':'Racked',
		'40':'Filtered',
		'60':'Bottled',
		'100':'Removed',
		'*':'Unknown',
		};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.menu = new M.panel('Customers',
			'ciniki_customers_main', 'menu',
			'mc', 'medium mediumflex', 'sectioned', 'ciniki.customers.main.menu');
		this.menu.data = {};
		this.menu.country = null;
		this.menu.province = null;
		this.menu.city = null;
		this.menu.sections = {
			'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':2, 
				'hint':'customer name', 'noData':'No customers found',
				'headerValues':['Customer', 'Status'],
				},
//			'tools':{'label':'Tools', 'list':{
//				'duplicates':{'label':'Find Duplicates', 'fn':'M.startApp(\'ciniki.customers.duplicates\', null, \'M.ciniki_customers_main.menu.show();\');'},
//				'automerge':{'label':'Automerge', 'fn':'M.startApp(\'ciniki.customers.automerge\', null, \'M.ciniki_customers_main.menu.show();\');'},
//				}},
			'places':{'label':'Countries', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'noData':'No customers',
				'limit':5,
				'moreTxt':'more',
				'moreFn':'M.startApp(\'ciniki.customers.places\',null,\'M.ciniki_customers_main.showMenu();\',\'mc\',{\'country\':M.ciniki_customers_main.menu.country,\'province\':M.ciniki_customers_main.menu.province});',
				},
			'customer_categories':{'label':'Categories', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'noData':'No categories',
				},
			'customer_tags':{'label':'tags', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'noData':'No tags',
				},
			'recent':{'label':'Recently Updated', 'num_cols':1, 'type':'simplegrid', 
				'headerValues':null,
				'noData':'No customers',
				},
			};
		this.menu.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_customers_main.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_main.menu.panelUID + '_' + s), rsp.customers); 
					});
				return true;
			}
		};
		this.menu.liveSearchResultValue = function(s, f, i, j, d) {
			if( s == 'search' ) { 
				switch(j) {
					case 0: return d.customer.display_name;
					case 1: return d.customer.status_text;
				}
			}
			return '';
		};
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.ciniki_customers_main.showCustomer(\'M.ciniki_customers_main.showMenu();\',\'' + d.customer.id + '\');'; 
		};
		this.menu.liveSearchResultRowStyle = function(s, f, i, d) {
			if( M.curBusiness.customers.settings['ui-colours-customer-status-' + d.customer.status] != null ) {
				return 'background: ' + M.curBusiness.customers.settings['ui-colours-customer-status-' + d.customer.status];
			}
		};
		this.menu.liveSearchSubmitFn = function(s, search_str) {
			M.ciniki_customers_main.searchCustomers('M.ciniki_customers_main.showMenu();', search_str);
		};
		this.menu.sectionData = function(s) {
			return this.data[s];
		};
		this.menu.noData = function(s) { return 'No customers'; }
		this.menu.cellValue = function(s, i, j, d) {
			if( s == 'places' ) {
				if( d.place.city != null ) {
					return (d.place.city==''?'No city':d.place.city) + ' <span class="count">' + d.place.num_customers + '</span>';
				} else if( d.place.province != null ) {
					return (d.place.province==''?'No province/state':d.place.province) + ' <span class="count">' + d.place.num_customers + '</span>';
				} else {
					return (d.place.country==''?'No Country':d.place.country) + ' <span class="count">' + d.place.num_customers + '</span>';
				}
			}
			if( s == 'customer_categories' || s == 'customer_tags' ) {
				return d.tag.name;
			}
			if( s == 'recent' ) {
				return d.customer.display_name;
			}
		};
		this.menu.rowFn = function(s, i, d) { 
			if( s == 'places' ) {
				return 'M.startApp(\'ciniki.customers.places\',null,\'M.ciniki_customers_main.showMenu();\',\'mc\',{\'country\':\'' + escape(d.place.country) + '\'' + (d.place.province!=null?',\'province\':\'' + escape(d.place.province)+'\'':'') + (d.place.city!=null?',\'city\':\''+escape(d.place.city)+'\'':'') + '});';
			}
			if( s == 'customer_categories' ) {
			}
			if( s == 'customer_tags' ) {
			}
			if( s == 'recent' ) {
				return 'M.ciniki_customers_main.showCustomer(\'M.ciniki_customers_main.showMenu();\',\'' + d.customer.id + '\');'; 
			}
		};

		this.menu.addClose('Back');

		//
		// The tools available to work on customer records
		//
		this.tools = new M.panel('Customer Tools',
			'ciniki_customers_main', 'tools',
			'mc', 'narrow', 'sectioned', 'ciniki.customers.main.tools');
		this.tools.data = {};
		this.tools.sections = {
			'reports':{'label':'Reports', 'list':{
				'onhold':{'label':'On Hold', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_main.tools.show();\',\'mc\',{\'status\':\'40\'});'},
				'suspended':{'label':'Suspended', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_main.tools.show();\',\'mc\',{\'status\':\'50\'});'},
				'deleted':{'label':'Deleted', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_main.tools.show();\',\'mc\',{\'status\':\'60\'});'},
				}},
			'tools':{'label':'Cleanup', 'list':{
				'blank':{'label':'Find Blank Names', 'fn':'M.startApp(\'ciniki.customers.blanks\', null, \'M.ciniki_customers_main.tools.show();\');'},
				'duplicates':{'label':'Find Duplicates', 'fn':'M.startApp(\'ciniki.customers.duplicates\', null, \'M.ciniki_customers_main.tools.show();\');'},
			}},
//			'import':{'label':'Import', 'list':{
//				'automerge':{'label':'Automerge', 'fn':'M.startApp(\'ciniki.customers.automerge\', null, \'M.ciniki_customers_main.menu.show();\');'},
//			}},
			};
		this.tools.addClose('Back');

		//
		// The search panel will list all search results for a string.  This allows more advanced searching,
		// and will search the entire strings, not just start of the string like livesearch
		//
		this.search = new M.panel('Search Results',
			'ciniki_customers_main', 'search',
			'mc', 'medium', 'sectioned', 'ciniki.customers.main.search');
		this.search.search_type = 'customers';
		this.search.sections = {
			'main':{'label':'', 'type':'simplegrid', 'num_cols':2, 
				'headerValues':['Name', 'Status'], 
				'dataMaps':['display_name', 'status_text'],
				'sortable':'yes'},
		};
		this.search.noData = function() { return 'No ' + this.search_type + ' found'; }
		this.search.sectionData = function(s) { return this.data; }
		this.search.cellValue = function(s, i, j, d) { 
			return d.customer[this.sections[s].dataMaps[j]];
		};
		this.search.rowFn = function(s, i, d) { 
			if( M.ciniki_customers_main.search.search_type == 'members' ) {
				return 'M.startApp(\'ciniki.customers.members\',null,\'M.ciniki_customers_main.searchCustomers();\',\'mc\',{\'customer_id\':\'' + d.customer.id + '\'});';
			} else if( M.ciniki_customers_main.search.search_type == 'dealers' ) {
				return 'M.startApp(\'ciniki.customers.dealers\',null,\'M.ciniki_customers_main.searchCustomers();\',\'mc\',{\'customer_id\':\'' + d.customer.id + '\'});';
			} else if( M.ciniki_customers_main.search.search_type == 'distributors' ) {
				return 'M.startApp(\'ciniki.customers.distributors\',null,\'M.ciniki_customers_main.searchCustomers();\',\'mc\',{\'customer_id\':\'' + d.customer.id + '\'});';
			} else {
				return 'M.ciniki_customers_main.showCustomer(\'M.ciniki_customers_main.searchCustomers(null, M.ciniki_customers_main.search.search_str);\',\'' + d.customer.id + '\');'; 
			}
		}
		this.search.rowStyle = function(s, i, d) {
			if( M.curBusiness.customers.settings['ui-colours-customer-status-' + d.customer.status] != null ) {
				return 'background: ' + M.curBusiness.customers.settings['ui-colours-customer-status-' + d.customer.status];
			}
		}
		this.search.addClose('Back');

		//
		// Show the customer information overview
		//
		this.customer = new M.panel('Customer',
			'ciniki_customers_main', 'customer',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.customer');
		this.customer.cbStacked = 'yes';
		this.customer.customer_id = 0;
		this.customer.data = {};
		this.customer.sections = {
			'details':{'label':'Customer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'dataMaps':['name', 'value'],
				},
			'account':{'label':'', 'aside':'yes', 'visible':'yes', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'dataMaps':['name', 'value'],
				},
//			'phones':{'label':'', 'type':'simplegrid', 'num_cols':2, 'visible':'no',
//				'headerValues':null,
//				'cellClasses':['label', ''],
//				},
//			'phones':{'label':'Phones', 'type':'simplegrid', 'num_cols':2,
//				'headerValues':null,
//				'cellClasses':['label', ''],
//				'addTxt':'Add Phone',
//				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_phone_id\':\'0\'});',
//				},
//			'emails':{'label':'Emails', 'type':'simplegrid', 'num_cols':1,
//				'headerValues':null,
//				'cellClasses':['', ''],
//				'addTxt':'Add Email',
//				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_email_id\':\'0\'});',
//				},
//			'addresses':{'label':'Addresses', 'type':'simplegrid', 'num_cols':2,
//				'headerValues':null,
//				'cellClasses':['label', ''],
//				'addTxt':'Add Address',
//				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_address_id\':\'0\'});',
//				},
//			'links':{'label':'Websites', 'type':'simplegrid', 'num_cols':1,
//				'headerValues':null,
//				'cellClasses':['multiline', ''],
//				'addTxt':'Add Website',
//				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_link_id\':\'0\'});',
//				},
			'relationships':{'label':'Relationships', 'aside':'yes', 'type':'simplegrid', 'visible':'no', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['', ''],
//				'noData':'No relationships',
				'addTxt':'Add Relationship',
				'addFn':'M.startApp(\'ciniki.customers.relationships\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});',
				},
			'_notes':{'label':'Notes', 'aside':'yes', 'type':'simpleform', 'fields':{'notes':{'label':'', 'type':'noedit', 'hidelabel':'yes'}}},
			'_tabs':{'label':'', 'visible':'no', 'selected':'', 'type':'paneltabs', 'tabs':{
				'children':{'label':'Children', 'visible':'no', 'fn':'M.ciniki_customers_main.showCustomerTab(null,"children",\'yes\');'},
				'wine':{'label':'Wine', 'visible':'no', 'fn':'M.ciniki_customers_main.showCustomerTab(null,"wine",\'yes\');'},
				'invoices':{'label':'Invoices', 'visible':'no', 'fn':'M.ciniki_customers_main.showCustomerTab(null,"invoices",\'yes\');'},
				'orders':{'label':'Orders', 'visible':'no', 'fn':'M.ciniki_customers_main.showCustomerTab(null,"orders",\'yes\');'},
				'pos':{'label':'Sales', 'visible':'no', 'fn':'M.ciniki_customers_main.showCustomerTab(null,"pos",\'yes\');'},
				'carts':{'label':'Carts', 'visible':'no', 'fn':'M.ciniki_customers_main.showCustomerTab(null,"carts",\'yes\');'},
				'shipments':{'label':'Shipments', 'visible':'no', 'fn':'M.ciniki_customers_main.showCustomerTab(null,"shipments",\'yes\');'},
				'subscriptions':{'label':'Subscriptions', 'visible':'no', 'fn':'M.ciniki_customers_main.showCustomerTab(null,"subscriptions",\'yes\');'},
				}},
			'subscriptions':{'label':'', 'type':'simplegrid', 'visible':'no', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No subscriptions',
				},
			'services':{'label':'Services', 'type':'simplegrid', 'visible':'no', 'num_cols':2, 'class':'simplegrid services border',
				'headerValues':null,
				'cellClasses':['multiline', 'multiline jobs'],
				'noData':'No services',
				'addTxt':'Add Service',
				'addFn':'M.startApp(\'ciniki.services.customer\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});'
				},
			'children':{'label':'Children', 'type':'simplegrid', 'visible':'no', 'num_cols':1,
				'headerValues':null,
				'cellClasses':[''],
				'addTxt':'Add',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'parent_id\':M.ciniki_customers_main.customer.customer_id,\'customer_id\':0});',
				},
			'invoices':{'label':'', 'type':'simplegrid', 'visible':'no', 'num_cols':4, 
				'headerValues':['Invoice #', 'Date', 'Amount', 'Status'],
				'cellClasses':['','','',''],
				'limit':10,
				'moreTxt':'More',
				'moreFn':'M.startApp(\'ciniki.sapos.customer\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});',
				'addTxt':'Add',
				'addFn':'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'invoice_type\':10});',
				},
			'orders':{'label':'', 'type':'simplegrid', 'visible':'no', 'num_cols':4, 
				'headerValues':['Invoice #', 'Date', 'Amount', 'Status'],
				'cellClasses':['','','',''],
				'limit':10,
				'moreTxt':'More',
				'moreFn':'M.startApp(\'ciniki.sapos.customer\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});',
				'addTxt':'Add',
				'addFn':'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'invoice_type\':40});',
				},
			'pos':{'label':'', 'type':'simplegrid', 'visible':'no', 'num_cols':4, 
				'headerValues':['Invoice #', 'Date', 'Amount', 'Status'],
				'cellClasses':['','','',''],
				'limit':10,
				'moreTxt':'More',
				'moreFn':'M.startApp(\'ciniki.sapos.customer\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});',
				'addTxt':'Add',
				'addFn':'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'invoice_type\:30});',
				},
			'carts':{'label':'', 'type':'simplegrid', 'visible':'no', 'num_cols':4, 
				'headerValues':['Invoice #', 'Date', 'Amount', 'Status'],
				'cellClasses':['','','',''],
				'limit':10,
				'moreTxt':'More',
				'moreFn':'M.startApp(\'ciniki.sapos.customer\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});',
				'addTxt':'Add',
				'addFn':'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'invoice_type\:20});',
				},
			'appointments':{'label':'Appointments', 'type':'simplegrid', 'visible':'no', 
				'num_cols':2, 'class':'dayschedule',
				'headerValues':null,
				'cellClasses':['multiline slice_0', 'schedule_appointment'],
				'noData':'No upcoming appointments',
				},
			'currentwineproduction':{'label':'Current Orders', 'type':'simplegrid', 'visible':'no', 'num_cols':7,
				'sortable':'yes',
				'headerValues':['INV#', 'Wine', 'OD', 'SD', 'RD', 'FD', 'BD'], 
				'cellClasses':['multiline', 'multiline', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter'],
				'dataMaps':['invoice_number', 'wine_name', 'order_date', 'start_date', 'racking_date', 'filtering_date', 'bottling_date'],
				'noData':'No current orders',
				},
			'pastwineproduction':{'label':'Past Orders', 'type':'simplegrid', 'visible':'no', 'num_cols':7,
				'sortable':'yes',
				'cellClasses':['multiline', 'multiline', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter'],
				'headerValues':['INV#', 'Wine', 'OD', 'SD', 'RD', 'FD', 'BD'], 
				'dataMaps':['invoice_number', 'wine_name', 'order_date', 'start_date', 'racking_date', 'filtering_date', 'bottle_date'],
				'noData':'No past orders',
				'limit':'5',
				'moreTxt':'More',
				'moreFn':'M.startApp(\'ciniki.wineproduction.customer\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});',
				},
			'_buttons':{'label':'', 'buttons':{
//				'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});'},
//				'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_customers_main.deleteCustomer(M.ciniki_customers_main.customer.customer_id);'},
				}},
			};
		this.customer.noData = function(s) {
			return this.sections[s].noData;
		};
		this.customer.sectionData = function(s) {
			return this.data[s];
		};
		this.customer.cellColour = function(s, i, j, d) {
			if( s == 'appointments' && j == 1 ) { 
				if( d.appointment != null && d.appointment.colour != null && d.appointment.colour != '' ) {
					return d.appointment.colour;
				}
				return '#77ddff';
			}
			return '';
		};
		this.customer.fieldValue = function(s, i, d) {
			if( i == 'notes' && this.data[i] == '' ) { return 'No notes'; }
			return this.data[i];
		};
		this.customer.cellValue = function(s, i, j, d) {
			if( s == 'details' || s == 'account' ) {
				if( j == 0 ) { return d.label; }
				if( j == 1 ) { return d.value; }
			}
			else if( s == 'phones' ) {
				switch(j) {
					case 0: return d.phone.phone_label;
					case 1: return d.phone.phone_number;
				}
			}
			else if( s == 'emails' ) {
				var flags = '';
				if( (d.email.flags&0x08) > 0 ) { flags += (flags!=''?', ':'') + 'Public'; }
				if( (d.email.flags&0x10) > 0 ) { flags += (flags!=''?', ':'') + 'No Emails'; }
				return M.linkEmail(d.email.address) + (flags!=''?' <span class="subdue">(' + flags + ')</span>':'');
//				if( j == 0 ) { return M.linkEmail(d.email.address); }
			}
			else if( s == 'addresses' ) {
				if( j == 0 ) { 
					var l = '';
					var cm = '';
					if( (d.address.flags&0x01) ) { l += cm + 'shipping'; cm =',<br/>';}
					if( (d.address.flags&0x02) ) { l += cm + 'billing'; cm =',<br/>';}
					if( (d.address.flags&0x04) ) { l += cm + 'mailing'; cm =',<br/>';}
					if( (d.address.flags&0x08) ) { l += cm + 'public'; cm =',<br/>';}
					return l;
				} 
				if( j == 1 ) {
					var v = '';
					if( d.address.address1 != '' ) { v += d.address.address1 + '<br/>'; }
					if( d.address.address2 != '' ) { v += d.address.address2 + '<br/>'; }
					if( d.address.city != '' ) { v += d.address.city + ''; }
					if( d.address.province != '' ) { v += ', ' + d.address.province + '<br/>'; }
					else { v += '<br/>'; }
					if( d.address.postal != '' ) { v += d.address.postal + '<br/>'; }
					if( d.address.country != '' ) { v += d.address.country + '<br/>'; }
					if( d.address.phone != '' ) { v += 'Phone: ' + d.address.phone + '<br/>'; }
					return v;
				}
			}
			else if( s == 'links' ) {
				if( d.link.name != '' ) {
					return '<span class="maintext">' + d.link.name + '</span><span class="subtext">' + M.hyperlink(d.link.url) + '</span>';
				} else {
					return M.hyperlink(d.link.url);
				}
			}
			else if( s == 'services' ) {
				if( j == 0 ) { return '<span class="maintext clickable">' + d.subscription.name + '</span><span class="subtext">' + d.subscription.date_started + '</span>'; }
				if( j == 1 ) { 
					var str = '';
					var count = 0;
					for(i in d.subscription.jobs) {
						var job = d.subscription.jobs[i].job;
						str += '<span';
						if( M.curBusiness.services.settings != null && M.curBusiness.services.settings['job-status-'+job.status+'-colour']) {
							str += ' style="background:' + M.curBusiness.services.settings['job-status-'+job.status+'-colour'] + '"';
						}
						if( job.status == 1 || job.status == 2 ) {
							str += ' onclick="event.stopPropagation();M.startApp(\'ciniki.services.job\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'subscription_id\':\'' + d.subscription.id + '\',\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'service_id\':\'' + d.subscription.service_id + '\',\'name\':\'' + job.name + '\',\'pstart\':\'' + job.pstart_date + '\',\'pend\':\'' + job.pend_date + '\',\'date_due\':\'' + job.date_due + '\'});"';
						} else {
							str += ' onclick="event.stopPropagation();M.startApp(\'ciniki.services.job\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'job_id\':\'' + job.id + '\'});"';
						}
						str += ' class="job"><span class="maintext">' + job.name + '</span><span class="subtext">' + job.status_text + '</span></span>';
						count++;
						if( d.subscription.repeat_type == 30 && d.subscription.repeat_interval == 3 && count > 0 && (count%4) == 0 ) {
							str += '<br/>';
						}
					}
					return str;
				}
			}
			else if( s == 'relationships' ) {
				if( j == 0 ) { return d.relationship.type_name + ' ' + d.relationship.name; }
//				if( j == 1 ) { return d.relationship.name; }
			}
			else if( s == 'subscriptions' ) {
				if( j == 0 ) { return 'subscribed'; }
				if( j == 1 ) { return d.subscription.name; }
			}
			else if( s == 'invoices' || s == 'carts' || s == 'pos' || s == 'orders' ) {
				switch(j) {
					case 0: return d.invoice.invoice_number;
					case 1: return d.invoice.invoice_date;
					case 2: return d.invoice.total_amount_display;
					case 3: return d.invoice.status_text;
				}
			}
			else if( s == 'appointments' ) {
				if( j == 0 ) {
					if( d.appointment.start_ts == 0 ) {
						return 'unscheduled';
					}
					if( d.appointment.allday == 'yes' ) {
						return d.appointment.start_date.split(/ [0-9]+:/)[0];
					}
					return '<span class="maintext">' + d.appointment.start_date.split(/ [0-9]+:/)[0] + '</span><span class="subtext">' + d.appointment.start_date.split(/, [0-9][0-9][0-9][0-9] /)[1] + '</span>';
				}
				if( j == 1 ) { 
					var t = '';
					if( d.appointment.secondary_colour != null && d.appointment.secondary_colour != '' ) {
						t += '<span class="colourswatch" style="background-color:' + d.appointment.secondary_colour + '">&nbsp;</span> '
					}
					t += d.appointment.subject;
					if( d.appointment.secondary_text != null && d.appointment.secondary_text != '' ) {
						t += ' <span class="secondary">' + d.appointment.secondary_text + '</span>';
					}
					return t;
				}
			} 
			else if( s == 'currentwineproduction' || s == 'pastwineproduction' ) {
				if( j == 0 ) {
					return '<span class="maintext">' + d.order.invoice_number + '</span><span class="subtext">' + M.ciniki_customers_main.statusOptions[d.order.status] + '</span>';
				} else if( (s == 'currentwineproduction' || s == 'pastwineproduction') && j > 1 && j < 7 ) {
					var dt = d.order[this.sections[s].dataMaps[j]];
					// Check for missing filter date, and try to take a guess
					if( dt == null && j == 6 ) {
						var dt = d.order.approx_filtering_date;
						if( dt != null ) {	
							return dt.replace(/(...)\s([0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>~$2<\/span>");
						}
						return '';
					}
					if( dt != null && dt != '' ) {
						return dt.replace(/(...)\s([0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>$2<\/span>");
					} else {
						return '';
					}
				}
				return d.order[this.sections[s].dataMaps[j]];
			}
			else if( s == 'children' ) {
				return (d.customer.eid!=null&&d.customer.eid!=''?d.customer.eid+' - ':'') + d.customer.display_name;
			}
			return this.data[s][i];
		};
		this.customer.cellFn = function(s, i, j, d) {
			if( s == 'appointments' && j == 1 ) {
				if( d.appointment.module == 'ciniki.wineproduction' ) {
					return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'appointment_id\':\'' + d.appointment.id + '\'});';
				}
			}
			return '';
		};
		this.customer.rowFn = function(s, i, d) {
			if( s == 'phones' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_phone_id\':\'' + d.phone.id + '\'});';
			}
			if( s == 'emails' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_email_id\':\'' + d.email.id + '\'});';
			}
			if( s == 'addresses' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_address_id\':\'' + d.address.id + '\'});';
			}
			if( s == 'links' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_link_id\':\'' + d.link.id + '\'});';
			}
			if( s == 'invoices' || s == 'carts' || s == 'pos' || s == 'orders' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\',\'list\':M.ciniki_customers_main.customer.data.orders});';
			}
			if( s == 'currentwineproduction' || s == 'pastwineproduction' ) {
				return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'order_id\':' + d.order.id + '});';
			}
			if( s == 'services' ) {
				return 'M.startApp(\'ciniki.services.customer\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'subscription_id\':\'' + d.subscription.id + '\'});';
			}
			if( s == 'children' ) {
				return 'M.ciniki_customers_main.showCustomer(\'M.ciniki_customers_main.showCustomer(null,' + M.ciniki_customers_main.customer.customer_id + ');\',\'' + d.customer.id + '\');';
//				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'parent_id\':M.ciniki_customers_main.customer.customer_id,\'customer_id\':\'' + d.customer.id + '\'});';
			}
			if( s == 'relationships' ) {
				return 'M.startApp(\'ciniki.customers.relationships\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'relationship_id\':\'' + d.relationship.id + '\'});';
			}
			return d.Fn;
		};
		this.customer.rowStyle = function(s, i, d) {
			if( s == 'details' && d.style != null ) {
				return d.style;
			}
			return '';
		};
		this.customer.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		// Setup ui labels
		this.slabel = 'Customer';
		this.plabel = 'Customers';
		this.childlabel = 'Child';
		this.childrenlabel = 'Children';
		if( M.curBusiness.customers != null ) {
			if( M.curBusiness.customers.settings['ui-labels-child'] != null 
				&& M.curBusiness.customers.settings['ui-labels-child'] != ''
				) {
				this.childlabel = M.curBusiness.customers.settings['ui-labels-child'];
			}
			if( M.curBusiness.customers.settings['ui-labels-children'] != null 
				&& M.curBusiness.customers.settings['ui-labels-children'] != ''
				) {
				this.childrenlabel = M.curBusiness.customers.settings['ui-labels-children'];
			}
			if( M.curBusiness.customers.settings['ui-labels-customer'] != null 
				&& M.curBusiness.customers.settings['ui-labels-customer'] != ''
				) {
				this.slabel = M.curBusiness.customers.settings['ui-labels-customer'];
			}
			if( M.curBusiness.customers.settings['ui-labels-customers'] != null 
				&& M.curBusiness.customers.settings['ui-labels-customers'] != ''
				) {
				this.plabel = M.curBusiness.customers.settings['ui-labels-customers'];
			}
		}
		this.menu.title = this.plabel;
		this.customer.title = this.slabel;
//		this.customer.sections._buttons.buttons.delete.label = 'Delete ' + this.slabel;
		this.customer.sections._tabs.tabs['children'].label = this.childrenlabel;
		this.customer.sections.children.label = this.childrenlabel;
		this.customer.sections.children.addTxt = 'Add ' + this.childlabel;

		if( (M.curBusiness.modules['ciniki.customers'].flags&0x010000) > 0 ) {
			this.search.sections.main.num_cols = 3;
			this.search.sections.main.headerValues = ['ID', 'Name', 'Status'];
			this.search.sections.main.dataMaps = ['eid', 'display_name', 'status_text'];
		} else {
			this.search.sections.main.num_cols = 2;
			this.search.sections.main.headerValues = ['Name', 'Status'];
			this.search.sections.main.dataMaps = ['display_name', 'status_text'];
		}
	
		//
		// Setup the buttons based on who is asking
		//
		this.menu.rightbuttons = {};
		this.customer.rightbuttons = {};
		if( M.curBusiness.permissions.owners != null 
			|| M.curBusiness.permissions.employees != null 
			|| M.userPerms&0x01 == 1	// sysadmins
			) {
			this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showMenu();\',\'mc\',{\'customer_id\':0});');
			this.menu.addButton('tools', 'Tools', 'M.ciniki_customers_main.tools.show(\'M.ciniki_customers_main.showMenu();\');');
			this.customer.addButton('edit', 'Edit', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});');
		} 

		//
		// Setup the menu buttons to make this the home screen,
		// if the main business menu had only one menu option available.
		// This is used for sales reps when they login and can only see customers
		//
		if( M.ciniki_businesses_main.menu.autoopen == 'skipped' ) {
			this.menu.leftbuttons = {};
			this.menu.rightbuttons = {};
			M.menuHome = this.menu;
			this.menu.leftbuttons = M.ciniki_businesses_main.menu.leftbuttons;
			this.menu.rightbuttons = M.ciniki_businesses_main.menu.rightbuttons;
		} else {
			this.menu.addClose('Back');
		}

		if( args.search != null && args.search != '' ) {
			this.searchCustomers(cb, args.search, args.type);
		} else if( args.customer_id != null && args.customer_id > 0 ) {
			this.showCustomer(cb, args.customer_id);
		} else {
			this.showMenu(cb);
		}


	}

	//
	// Grab the stats for the business from the database and present the list of customers.
	//
	this.showMenu = function(cb) {
		//
		// Grab list of recently updated customers
		//
		var rsp = M.api.getJSONCb('ciniki.customers.overview', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			} 
			var p = M.ciniki_customers_main.menu;
			p.data = {};
			if( rsp.places != null ) {
				p.sections.places.visible = 'yes';
				p.data.places = rsp.places;
				p.place_level = rsp.place_level;
				switch(rsp.place_level) {
					case 'country': p.sections.places.label = 'Countries'; 
						p.country = null;
						p.province = null;
						p.city = null;
						break;
					case 'province': p.sections.places.label = 'Provinces/States'; 
						p.province = null;
						p.city = null;
						break;
					case 'city': p.sections.places.label = 'Cities'; 
						p.city = null;
						break;
				}
			} else {
				p.sections.places.visible = 'no';
			}
			if( rsp.customer_categories != null ) {
				p.sections.customer_categories.visible = 'yes';
				p.data.customer_categories = rsp.customer_categories;
			} else {
				p.sections.customer_categories.visible = 'no';
			}
			if( rsp.customer_tags != null ) {
				p.sections.customer_tags.visible = 'yes';
				p.data.customer_tags = rsp.customer_tags;
			} else {
				p.sections.customer_tags.visible = 'no';
			}
			p.data.recent = rsp.recent;	
			p.refresh();
			p.show(cb);
		});
	}

	this.showCustomer = function(cb, cid) {
		if( cid != null ) { this.customer.customer_id = cid; }
		// Reset to not showing all sections
//		this.customer.sections.phones.visible = 'no';
		this.customer.sections.subscriptions.visible = 'no';
		this.customer.sections.invoices.visible = 'no';
		this.customer.sections.appointments.visible = 'no';
		this.customer.sections.currentwineproduction.visible = 'no';
		this.customer.sections.pastwineproduction.visible = 'no';
//		this.customer.sections._buttons.buttons.delete.visible = 'yes';

		M.api.getJSONCb('ciniki.customers.getModuleData', 
			{'business_id':M.curBusinessID, 
				'customer_id':M.ciniki_customers_main.customer.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_main.showCustomerFinish(cb, rsp);
				});
	}

	this.showCustomerFinish = function(cb, rsp) {
		var mods = M.curBusiness.modules;
		this.customer.data = rsp.customer;
		this.customer.data.details = {};
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x010000) > 0 ) {
			this.customer.data.details.eid = {'label':'ID', 'value':rsp.customer.eid};
		}
//		if( M.curBusiness.customers != null && M.curBusiness.customers.settings['types-'+rsp.customer.type+'-label'] != null ) {
//			this.customer.data.details.type = {'label':'Type', 'value':M.curBusiness.customers.settings['types-'+rsp.customer.type+'-label']};
//		}
		if( rsp.customer.status_text != null ) {
			this.customer.data.details.status_text = {'label':'Status', 'value':rsp.customer.status_text};
			if( M.curBusiness.customers.settings['ui-colours-customer-status-' + rsp.customer.status] != null ) {
				this.customer.data.details.status_text.style = 'background: ' + M.curBusiness.customers.settings['ui-colours-customer-status-' + rsp.customer.status];
			}
//			if( rsp.customer.status > 10 ) {
//				this.customer.data.details.status_text.style = 'background: #FFD0D0;';
//			}
		}
		if( rsp.customer.dealer_status_text != null ) {
			this.customer.data.details.dealer_status_text = {'label':'Dealer Status', 'value':rsp.customer.dealer_status_text};
		}
		if( rsp.customer.type == 2 ) {
			this.customer.data.details.company = {'label':'Name', 'value':rsp.customer.company};
			this.customer.data.details.name = {'label':'Contact', 'value':rsp.customer.first + ' ' + rsp.customer.last};
		} else {
			this.customer.data.details.name = {'label':'Name', 'value':rsp.customer.display_name};
			if( rsp.customer.company != null &&  rsp.customer.company != '' ) {
				this.customer.data.details.company = {'label':'Business', 'value':rsp.customer.company};
			}
			if( rsp.customer.birthdate != '' ) {
				this.customer.data.details.birthdate = {'label':'Birthday', 'value':rsp.customer.birthdate};
			}
		}
		if( rsp.customer.phones != null ) {
			for(i in rsp.customer.phones) {
				this.customer.data.details['phone-'+i] = {'label':rsp.customer.phones[i].phone.phone_label, 'value':rsp.customer.phones[i].phone.phone_number};
			}
		}
		if( rsp.customer.emails != null ) {
			for(i in rsp.customer.emails) {
				var flags = '';
				if( (rsp.customer.emails[i].email.flags&0x08) > 0 ) { flags += (flags!=''?', ':'') + 'Public'; }
				if( (rsp.customer.emails[i].email.flags&0x10) > 0 ) { flags += (flags!=''?', ':'') + 'No Emails'; }
				this.customer.data.details['email-'+i] = {'label':'Email', 'value':M.linkEmail(rsp.customer.emails[i].email.address) + (flags!=''?' <span class="subdue">(' + flags + ')</span>':'')};
			}
		}
		if( rsp.customer.addresses != null ) {
			for(i in rsp.customer.addresses) {
				var l = '';
				var cm = '';
				var d = rsp.customer.addresses[i];
				if( (d.address.flags&0x01) ) { l += cm + 'shipping'; cm =',<br/>';}
				if( (d.address.flags&0x02) ) { l += cm + 'billing'; cm =',<br/>';}
				if( (d.address.flags&0x04) ) { l += cm + 'mailing'; cm =',<br/>';}
				if( (d.address.flags&0x08) ) { l += cm + 'public'; cm =',<br/>';}
				var v = '';
				if( d.address.address1 != '' ) { v += d.address.address1 + '<br/>'; }
				if( d.address.address2 != '' ) { v += d.address.address2 + '<br/>'; }
				if( d.address.city != '' ) { v += d.address.city + ''; }
				if( d.address.province != '' ) { v += ', ' + d.address.province + '  '; }
				else if( d.address.city != '' ) { v += '  '; }
				if( d.address.postal != '' ) { v += d.address.postal + '<br/>'; }
				if( d.address.country != '' ) { v += d.address.country + '<br/>'; }
				if( d.address.phone != '' ) { v += 'Phone: ' + d.address.phone + '<br/>'; }
				
				this.customer.data.details['address-'+i] = {'label':l, 'value':v};
			}
		}
		if( rsp.customer.links != null ) {
			for(i in rsp.customer.links) {
				var url = M.hyperlink(rsp.customer.links[i].link.url);
				this.customer.data.details['link-'+i] = {'label':'Website', 'value':(rsp.customer.links[i].link.name!=''?rsp.customer.links[i].link.name + ' <span class="subdue">' + url + '</span>':url)};
			}
		}
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x400000) > 0 ) {
			this.customer.data.details['customer_categories'] = {'label':'Categories', 'value':(rsp.customer.customer_categories!=null?rsp.customer.customer_categories.replace(/::/g,', '):'')};
		}
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x800000) > 0 ) {
			this.customer.data.details['customer_tags'] = {'label':'Tags', 'value':(rsp.customer.customer_tags!=null?rsp.customer.customer_tags.replace(/::/g,', '):'')};
		}
		this.customer.data.account = {};
		// Sales Rep
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x2000) > 0 
			&& rsp.customer.salesrep_id_text != null && rsp.customer.salesrep_id_text != ''
			) {
			this.customer.sections.account.visible = 'yes';
			this.customer.data.account.salesrep_id = {'label':'Sales Rep', 'value':rsp.customer.salesrep_id_text};
		}
		// Pricepoint
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x1000) > 0 
			&& M.curBusiness.customers.settings.pricepoints != null
			) {
			this.customer.sections.account.visible = 'yes';
			for(i in M.curBusiness.customers.settings.pricepoints) {
				if( M.curBusiness.customers.settings.pricepoints[i].pricepoint.id == rsp.customer.pricepoint_id ) {
					this.customer.data.account.pricepoint_id = {'label':'Price Point', 
						'value':M.curBusiness.customers.settings.pricepoints[i].pricepoint.name};
					break;
				}
			}
			if( this.customer.data.account.pricepoint_id == null ) {
				this.customer.data.account.pricepoint_id = {'label':'Price Point', 'value':'None'};
			}
		}
		// Tax Number
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x20000) > 0 
			&& rsp.customer.tax_number != null && rsp.customer.tax_number != ''
			) {
			this.customer.sections.account.visible = 'yes';
			this.customer.data.account.tax_number = {'label':'Tax Number', 'value':rsp.customer.tax_number};
		}
		// Tax Location
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x40000) > 0 ) {
			var rates = ((rsp.customer.tax_location_id_rates!=null&&rsp.customer.tax_location_id_rates!='')?' <span class="subdue">'+rsp.customer.tax_location_id_rates+'</span>':'');
			this.customer.sections.account.visible = 'yes';
			this.customer.data.account.tax_location_id = {'label':'Taxes', 'value':(rsp.customer.tax_location_id_text!=null?rsp.customer.tax_location_id_text:'Use Shipping Address') + rates};
		}
		// Reward Level
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x80000) > 0 
			&& rsp.customer.reward_level != null && rsp.customer.reward_level != ''
			) {
			this.customer.sections.account.visible = 'yes';
			this.customer.data.account.reward_level = {'label':'Reward Teir', 'value':rsp.customer.reward_level};
		}
		// Sales Total
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x100000) > 0 
			&& rsp.customer.sales_total != null && rsp.customer.sales_total != ''
			) {
			this.customer.sections.account.visible = 'yes';
			this.customer.data.account.sales_total = {'label':'Sales Total', 'value':rsp.customer.sales_total};
		}
		// Start Date
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x100000) > 0 
			&& rsp.customer.sales_total != null && rsp.customer.sales_total != ''
			) {
			this.customer.sections.account.visible = 'yes';
			this.customer.data.account.sales_total = {'label':'Sales Total', 'value':rsp.customer.sales_total};
		}

//		this.customer.data.phones = {};
//		if(  rsp.customer.phone_home != null && rsp.customer.phone_home != '' ) {
//			this.customer.sections.phones.visible = 'yes';
//			this.customer.data.phones.home = {'label':'Home', 'value':rsp.customer.phone_home};
//		}
//		if(  rsp.customer.phone_work != null && rsp.customer.phone_work != '' ) {
//			this.customer.sections.phones.visible = 'yes';
//			this.customer.data.phones.work = {'label':'Work', 'value':rsp.customer.phone_work};
//		}
//		if(  rsp.customer.phone_cell != null && rsp.customer.phone_cell != '' ) {
//			this.customer.sections.phones.visible = 'yes';
//			this.customer.data.phones.cell = {'label':'Cell', 'value':rsp.customer.phone_cell};
//		}
//		if(  rsp.customer.phone_fax != null && rsp.customer.phone_fax != '' ) {
//			this.customer.sections.phones.visible = 'yes';
//			this.customer.data.phones.fax = {'label':'Fax', 'value':rsp.customer.phone_fax};
//		}
		this.customer.sections._notes.visible=(rsp.customer.notes=='')?'no':'yes';

//		if( (rsp.customer.emails != null && rsp.customer.emails.length > 0)
//			|| (rsp.customer.addresses != null && rsp.customer.addresses.length > 0)
//			|| (rsp.customer.subscriptions != null && rsp.customer.subscriptions.length > 0)
//			|| (rsp.customer.services != null && rsp.customer.services.length > 0)
//			|| (rsp.customer.relationships != null && rsp.customer.relationships.length > 0)
//			) {
//			this.customer.sections._buttons.buttons.delete.visible = 'no';
//		}

		//
		// make subscriptions available
		//
		var pt_count = 0;
		var paneltab = '';
		if( mods['ciniki.subscriptions'] != null ) {
			this.customer.sections._tabs.tabs['subscriptions'].visible = 'yes';
			pt_count++;
			paneltab = 'subscriptions';
		} else {
			this.customer.sections._tabs.tabs['subscriptions'].visible = 'no';
		}

		//
		// Make relationships visible if setup for business
		//
		if( M.curBusiness.customers != null && M.curBusiness.customers.settings['use-relationships'] != null && M.curBusiness.customers.settings['use-relationships'] == 'yes' ) {
			this.customer.sections.relationships.visible = 'yes';
		} else {
			this.customer.sections.relationships.visible = 'no';
		}

		//
		// Check if there child accounts
		//
		if( rsp.customer.parent_id == 0 && ((rsp.customer.children != null && rsp.customer.children.length > 0)
			|| (M.curBusiness.modules['ciniki.customers'].flags&0x200000) > 0) ) {
			this.customer.sections._tabs.tabs['children'].visible = 'yes';
//			this.customer.sections._buttons.buttons.delete.visible = 'no';
			paneltab = 'children';
			pt_count++;
		} else {
			this.customer.sections._tabs.tabs['children'].visible = 'no';
		}

		if( rsp.customer.status > 10 
			&& M.curBusiness.permissions.salesreps != null
			&& M.curBusiness.permissions.owners == null
			&& M.curBusiness.permissions.employees == null
			) {
			this.customer.sections.invoices.addTxt = '';
			this.customer.sections.orders.addTxt = '';
			this.customer.sections.carts.addTxt = '';
			this.customer.sections.pos.addTxt = '';
		} else {
			this.customer.sections.invoices.addTxt = 'Add Invoice';
			this.customer.sections.orders.addTxt = 'Add Order';
			this.customer.sections.carts.addTxt = 'Add Cart';
			this.customer.sections.pos.addTxt = 'Add';
		}

		if( rsp.customer.carts != null ) {
			this.customer.sections._tabs.tabs['carts'].visible = 'yes';
			paneltab = 'carts';
			pt_count++;
		} else {
			this.customer.sections._tabs.tabs['carts'].visible = 'no';
		}

		if( rsp.customer.invoices != null ) {
			this.customer.sections._tabs.tabs['invoices'].visible = 'yes';
			paneltab = 'invoices';
			pt_count++;
		} else {
			this.customer.sections._tabs.tabs['invoices'].visible = 'no';
		}

		if( rsp.customer.pos != null ) {
			this.customer.sections._tabs.tabs['pos'].visible = 'yes';
			paneltab = 'pos';
			pt_count++;
		} else {
			this.customer.sections._tabs.tabs['pos'].visible = 'no';
		}

		if( rsp.customer.orders != null ) {
			this.customer.sections._tabs.tabs['orders'].visible = 'yes';
			paneltab = 'orders';
			pt_count++;
		} else {
			this.customer.sections._tabs.tabs['orders'].visible = 'no';
		}

		//
		// Get the customer wineproduction
		//
		if( mods['ciniki.wineproduction'] != null ) {
//			this.customer.sections.appointments.visible = 'yes';
//			this.customer.sections.currentwineproduction.visible = 'yes';
//			this.customer.sections.pastwineproduction.visible = 'yes';
			this.customer.sections._tabs.tabs['wine'].visible = 'yes';
			pt_count++;
			if( (rsp.currenttwineproduction != null && rsp.currentwineproduction.length > 0)
				|| (rsp.pastwineproduction != null && rsp.pastwineproduction.length > 0)
				|| (rsp.appointments != null && rsp.appointments.length > 0)
				) {
//				this.customer.sections._buttons.buttons.delete.visible = 'no';
			}
			paneltab = 'wine';
		} else {
			this.customer.sections._tabs.tabs['wine'].visible = 'no';
		}

		if( this.customer.sections._tabs.selected == '' ) { this.customer.sections._tabs.selected = paneltab; }

		if( pt_count > 1 ) {
			this.customer.sections._tabs.visible = 'yes';
			this.customer.sections.children.label = '';
			this.customer.sections.subscriptions.label = '';
			this.customer.sections.invoices.label = '';
			this.customer.sections.orders.label = '';
			this.customer.sections.pos.label = '';
			this.customer.sections.carts.label = '';
		} else {
			this.customer.sections._tabs.visible = 'no';
			this.customer.sections.children.label = this.childrenlabel;
			this.customer.sections.subscriptions.label = 'Subscriptions';
			this.customer.sections.invoices.label = 'Invoices';
			this.customer.sections.orders.label = 'Orders';
			this.customer.sections.pos.label = 'Sales';
			this.customer.sections.carts.label = 'Carts';
		}
		M.ciniki_customers_main.showCustomerTab(cb, paneltab, 'no');
	}

	this.showCustomerTab = function(cb, tab, scroll) {
		if( tab != null ) { this.customer.paneltab = tab; }
		var p = M.ciniki_customers_main.customer;
		// Turn everything off
		p.sections.appointments.visible = 'no';
		p.sections.currentwineproduction.visible = 'no';
		p.sections.pastwineproduction.visible = 'no';
		p.sections.invoices.visible = 'no';
		p.sections.orders.visible = 'no';
		p.sections.pos.visible = 'no';
		p.sections.carts.visible = 'no';
		p.sections.subscriptions.visible = 'no';
		p.sections.children.visible = 'no';
		// decide what is visible
		if( p.paneltab == 'wine' ) {
			p.sections.appointments.visible = 'yes';
			p.sections.currentwineproduction.visible = 'yes';
			p.sections.pastwineproduction.visible = 'yes';
		} else if( p.paneltab == 'invoices' ) {
			p.sections.invoices.visible = 'yes';
		} else if( p.paneltab == 'orders' ) {
			p.sections.orders.visible = 'yes';
		} else if( p.paneltab == 'pos' ) {
			p.sections.pos.visible = 'yes';
		} else if( p.paneltab == 'carts' ) {
			p.sections.carts.visible = 'yes';
		} else if( p.paneltab == 'subscriptions' ) {
			this.customer.sections.subscriptions.visible='yes';
		} else if( p.paneltab == 'children' ) {
			this.customer.sections.children.visible='yes';
		}
		p.sections._tabs.selected = tab;
		p.refresh();
		p.show(cb);
		if( p.sections._tabs.visible == 'yes' && scroll == 'yes' ) {
			var e = M.gE(M.ciniki_customers_main.customer.panelUID + '__tabs');
			if( e.offsetTop > 100 ) { window.scrollTo(0, e.offsetTop); }
		}
	}

	this.searchCustomers = function(cb, search_str, type) {
		if( search_str != null ) { this.search.search_str = search_str; }
		if( type != null ) { this.search.search_type = type; }
		M.api.getJSONCb('ciniki.customers.searchFull', {'business_id':M.curBusinessID, 
			'start_needle':this.search.search_str, 'type':this.search.search_type, 'limit':100}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_customers_main.search;
				p.data = rsp.customers;
				p.refresh();
				p.show(cb);
			});
	}

	this.deleteCustomer = function(cid) {
		if( cid != null && cid > 0 ) {
			if( confirm("Are you sure you want to remove this " + this.slabel + "?") ) {
				M.api.getJSONCb('ciniki.customers.delete', {'business_id':M.curBusinessID, 'customer_id':cid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_main.customer.close();
				});
			}
		}
	}
}
