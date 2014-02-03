//
function ciniki_customers_main() {
	//
	// Panels
	//
	this.main = null;

	this.cb = null;
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
		this.main = new M.panel('Customers',
			'ciniki_customers_main', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.customers.main');
		this.main.data = {};
		this.main.sections = {
			'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'customer name', 'noData':'No customers found',
				},
//			'tools':{'label':'Tools', 'list':{
//				'duplicates':{'label':'Find Duplicates', 'fn':'M.startApp(\'ciniki.customers.duplicates\', null, \'M.ciniki_customers_main.main.show();\');'},
//				'automerge':{'label':'Automerge', 'fn':'M.startApp(\'ciniki.customers.automerge\', null, \'M.ciniki_customers_main.main.show();\');'},
//				}},
			'recent':{'label':'Recently Updated', 'num_cols':1, 'type':'simplegrid', 
				'headerValues':null,
				'noData':'No customers',
				},
			};
		this.main.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_customers_main.main.liveSearchShow('search', null, M.gE(M.ciniki_customers_main.main.panelUID + '_' + s), rsp.customers); 
					});
				return true;
			}
		};
		this.main.liveSearchResultValue = function(s, f, i, j, d) {
			if( s == 'search' ) { 
				return d.customer.display_name;
			}
			return '';
		}
		this.main.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.ciniki_customers_main.showCustomer(\'M.ciniki_customers_main.showMain();\',\'' + d.customer.id + '\');'; 
		};
		this.main.liveSearchSubmitFn = function(s, search_str) {
			M.ciniki_customers_main.searchCustomers('M.ciniki_customers_main.showMain();', search_str);
		};
		this.main.sectionData = function(s) {
			if( s == 'recent' ) {	
				return this.data[s];
			}
		};
		this.main.noData = function(s) { return 'No customers'; }
		this.main.cellValue = function(s, i, j, d) {
			return d.customer.display_name;
		};
		this.main.rowFn = function(s, i, d) { 
			return 'M.ciniki_customers_main.showCustomer(\'M.ciniki_customers_main.showMain();\',\'' + d.customer.id + '\');'; 
		};

		this.main.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showMain();\',\'mc\',{\'customer_id\':0});');
		this.main.addButton('tools', 'Tools', 'M.ciniki_customers_main.tools.show(\'M.ciniki_customers_main.showMain();\');');
		this.main.addClose('Back');

		//
		// The tools available to work on customer records
		//
		this.tools = new M.panel('Customer Tools',
			'ciniki_customers_main', 'tools',
			'mc', 'narrow', 'sectioned', 'ciniki.customers.main.tools');
		this.tools.data = {};
		this.tools.sections = {
			'tools':{'label':'Cleanup', 'list':{
				'blank':{'label':'Find Blank Names', 'fn':'M.startApp(\'ciniki.customers.blanks\', null, \'M.ciniki_customers_main.tools.show();\');'},
				'duplicates':{'label':'Find Duplicates', 'fn':'M.startApp(\'ciniki.customers.duplicates\', null, \'M.ciniki_customers_main.tools.show();\');'},
			}},
//			'import':{'label':'Import', 'list':{
//				'automerge':{'label':'Automerge', 'fn':'M.startApp(\'ciniki.customers.automerge\', null, \'M.ciniki_customers_main.main.show();\');'},
//			}},
			};
		this.tools.addClose('Back');

		//
		// The search panel will list all search results for a string.  This allows more advanced searching,
		// and will search the entire strings, not just start of the string like livesearch
		//
		this.search = new M.panel('Search Results',
			'ciniki_customers_main', 'search',
			'mc', 'medium', 'sectioned', 'ciniki.customers.search');
		this.search.sections = {
			'main':{'label':'', 'headerValues':[], 'num_cols':1, 'type':'simplegrid', 'sortable':'yes'},
		};   
		this.search.noData = function() { return 'No customers found'; }
		this.search.sectionData = function(s) { return this.data; }
		this.search.cellValue = function(s, i, j, d) { 
			return d.customer.display_name;
		};
		this.search.rowFn = function(s, i, d) { 
			return 'M.ciniki_customers_main.showCustomer(\'M.ciniki_customers_main.searchCustomers(null, M.ciniki_customers_main.search.search_str);\',\'' + d.customer.id + '\');'; 
			}
		this.search.addClose('Back');

		//
		// Show the customer information overview
		//
		this.customer = new M.panel('Customer',
			'ciniki_customers_main', 'customer',
			'mc', 'medium', 'sectioned', 'ciniki.customers.customer');
		this.customer.customer_id = 0;
		this.customer.data = {};
		this.customer.sections = {
			'details':{'label':'', 'aside':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'dataMaps':['name', 'value'],
				},
			'phones':{'label':'', 'aside':'no', 'type':'simplegrid', 'num_cols':2, 'visible':'no',
				'headerValues':null,
				'cellClasses':['label', ''],
				},
			'emails':{'label':'Emails', 'aside':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No emails',
				'addTxt':'Add Email',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_email_id\':\'0\'});',
				},
			'addresses':{'label':'Addresses', 'aside':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No addresses',
				'addTxt':'Add Address',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_address_id\':\'0\'});',
				},
			'relationships':{'label':'Relationships', 'aside':'no', 'type':'simplegrid', 'visible':'no', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No relationships',
				'addTxt':'Add Relationship',
				'addFn':'M.startApp(\'ciniki.customers.relationships\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});',
				},
			'subscriptions':{'label':'Subscriptions', 'aside':'no', 'type':'simplegrid', 'visible':'no', 'num_cols':2,
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
			'invoices':{'label':'Invoices', 'type':'simplegrid', 'visible':'no', 'num_cols':4, 
				'headerValues':['Invoice #', 'Date', 'Amount', 'Status'],
				'cellClasses':['','','',''],
				'limit':5,
				'moreTxt':'More',
				'moreFn':'M.startApp(\'ciniki.sapos.customerinvoices\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});',
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
			'_notes':{'label':'Notes', 'type':'simpleform', 'fields':{'notes':{'label':'', 'type':'noedit', 'hidelabel':'yes'}}},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});'},
				'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_customers_main.deleteCustomer(M.ciniki_customers_main.customer.customer_id);'},
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
			if( s == 'details' || s == 'phones' ) {
				if( j == 0 ) { return d.label; }
				if( j == 1 ) { return d.value; }
			}
			else if( s == 'emails' ) {
				if( j == 0 ) { return d.email.address; }
			}
			else if( s == 'addresses' ) {
				if( j == 0 ) { 
					var l = '';
					var cm = '';
					if( (d.address.flags&0x01) ) { l += cm + 'shipping'; cm =',<br/>';}
					if( (d.address.flags&0x02) ) { l += cm + 'billing'; cm =',<br/>';}
					if( (d.address.flags&0x04) ) { l += cm + 'mailing'; cm =',<br/>';}
					return l;
				} 
				if( j == 1 ) {
					var v = '';
					if( d.address.address1 != '' ) { v += d.address.address1 + '<br/>'; }
					if( d.address.address2 != '' ) { v += d.address.address2 + '<br/>'; }
					if( d.address.city != '' ) { v += d.address.city + ''; }
					if( d.address.province != '' ) { v += ', ' + d.address.province + '<br/>'; }
					if( d.address.postal != '' ) { v += d.address.postal + '<br/>'; }
					if( d.address.country != '' ) { v += d.address.country + '<br/>'; }
					return v;
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
			else if( s == 'invoices' ) {
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
			if( s == 'emails' ) {
//				return 'M.ciniki_customers_main.showEmailEdit(\'M.ciniki_customers_main.showCustomer();\',M.ciniki_customers_main.customer.customer_id,\'' + d.email.id + '\');';
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_email_id\':\'' + d.email.id + '\'});';
			}
			if( s == 'addresses' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'edit_address_id\':\'' + d.address.id + '\'});';
//				return 'M.ciniki_customers_main.showAddressEdit(\'M.ciniki_customers_main.showCustomer();\',M.ciniki_customers_main.customer.customer_id,\'' + d.address.id + '\');';
			}
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
			if( s == 'currentwineproduction' || s == 'pastwineproduction' ) {
				return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'order_id\':' + d.order.id + '});';
			}
			if( s == 'services' ) {
				return 'M.startApp(\'ciniki.services.customer\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'subscription_id\':\'' + d.subscription.id + '\'});';
			}
			if( s == 'relationships' ) {
				return 'M.startApp(\'ciniki.customers.relationships\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id,\'relationship_id\':\'' + d.relationship.id + '\'});';
			}
			return d.Fn;
		};
		this.customer.addButton('edit', 'Edit', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.showCustomer();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});');
		this.customer.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.cb = cb;
		if( args.search != null && args.search != '' ) {
			this.searchCustomers(cb, args.search);
		} else if( args.customer_id != null && args.customer_id > 0 ) {
			this.showCustomer(cb, args.customer_id);
		} else {
			this.showMain(cb);
		}
	}

	//
	// Grab the stats for the business from the database and present the list of customers.
	//
	this.showMain = function(cb) {
		//
		// Grab list of recently updated customers
		//
		var rsp = M.api.getJSONCb('ciniki.customers.recent', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			} 
			var p = M.ciniki_customers_main.main;
			p.data.recent = rsp.customers;	
			p.refresh();
			p.show(cb);
		});
	}

	this.showCustomer = function(cb, cid) {
		if( cid != null ) { this.customer.customer_id = cid; }
		// Reset to not showing all sections
		this.customer.sections.phones.visible = 'no';
		this.customer.sections.subscriptions.visible = 'no';
		this.customer.sections.invoices.visible = 'no';
		this.customer.sections.appointments.visible = 'no';
		this.customer.sections.currentwineproduction.visible = 'no';
		this.customer.sections.pastwineproduction.visible = 'no';
		this.customer.sections._buttons.buttons.delete.visible = 'yes';

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
		if( M.curBusiness.customers != null && M.curBusiness.customers.settings['use-cid'] != null && M.curBusiness.customers.settings['use-cid'] == 'yes' ) {
			this.customer.data.details.cid = {'label':'ID', 'value':rsp.customer.cid};
		}
//		if( M.curBusiness.customers != null && M.curBusiness.customers.settings['types-'+rsp.customer.type+'-label'] != null ) {
//			this.customer.data.details.type = {'label':'Type', 'value':M.curBusiness.customers.settings['types-'+rsp.customer.type+'-label']};
//		}
		if( rsp.customer.type == 2 ) {
			this.customer.data.details.company = {'label':'Name', 'value':rsp.customer.company};
			this.customer.data.details.name = {'label':'Contact', 'value':rsp.customer.display_name};
		} else {
			this.customer.data.details.name = {'label':'Name', 'value':rsp.customer.display_name};
			if( rsp.customer.company != null &&  rsp.customer.company != '' ) {
				this.customer.data.details.company = {'label':'Business', 'value':rsp.customer.company};
			}
			if( rsp.customer.birthdate != '' ) {
				this.customer.data.details.birthdate = {'label':'Birthday', 'value':rsp.customer.birthdate};
			}
		}
		this.customer.data.phones = {};
		if(  rsp.customer.phone_home != null && rsp.customer.phone_home != '' ) {
			this.customer.sections.phones.visible = 'yes';
			this.customer.data.phones.home = {'label':'Home', 'value':rsp.customer.phone_home};
		}
		if(  rsp.customer.phone_work != null && rsp.customer.phone_work != '' ) {
			this.customer.sections.phones.visible = 'yes';
			this.customer.data.phones.work = {'label':'Work', 'value':rsp.customer.phone_work};
		}
		if(  rsp.customer.phone_cell != null && rsp.customer.phone_cell != '' ) {
			this.customer.sections.phones.visible = 'yes';
			this.customer.data.phones.cell = {'label':'Cell', 'value':rsp.customer.phone_cell};
		}
		if(  rsp.customer.phone_fax != null && rsp.customer.phone_fax != '' ) {
			this.customer.sections.phones.visible = 'yes';
			this.customer.data.phones.fax = {'label':'Fax', 'value':rsp.customer.phone_fax};
		}
		this.customer.sections._notes.visible=(rsp.customer.notes=='')?'no':'yes';

		if( (rsp.customer.emails != null && rsp.customer.emails.length > 0)
			|| (rsp.customer.addresses != null && rsp.customer.addresses.length > 0)
			|| (rsp.customer.subscriptions != null && rsp.customer.subscriptions.length > 0)
			|| (rsp.customer.services != null && rsp.customer.services.length > 0)
			|| (rsp.customer.relationships != null && rsp.customer.relationships.length > 0)
			) {
			this.customer.sections._buttons.buttons.delete.visible = 'no';
		}

		//
		// make subscriptions available
		//
		this.customer.sections.subscriptions.visible=(mods['ciniki.subscriptions']!=null)?'yes':'no';

		//
		// Make relationships visible if setup for business
		//
		if( M.curBusiness.customers != null && M.curBusiness.customers.settings['use-relationships'] != null && M.curBusiness.customers.settings['use-relationships'] == 'yes' ) {
			this.customer.sections.relationships.visible = 'yes';
		} else {
			this.customer.sections.relationships.visible = 'no';
		}

		//
		// Make services available
		//
//		if( mods['ciniki.services'] != null ) {
//			var rsp = M.api.getJSON('ciniki.services.customerSubscriptions', {'business_id':M.curBusinessID, 
//				'customer_id':this.customer.customer_id, 'jobs':'yes', 'projections':'P4M'});
//			if( rsp.stat != 'ok' ) {
//				M.stopLoad();
//				M.api.err(rsp);
//				return false;
//			} 
//			this.customer.data.services = rsp.subscriptions;
//			this.customer.sections.services.visible = 'yes';
//		} else {
//			this.customer.sections.services.visible = 'no';
//		}
	
		//
		// Get the customer wineproduction
		//
		if( mods['ciniki.wineproduction'] != null ) {
//			var rsp = M.api.getJSON('ciniki.wineproduction.appointments', {'business_id':M.curBusinessID, 'customer_id':this.customer.customer_id, 'status':'unbottled'});
//			if( rsp.stat != 'ok' ) {
//				M.stopLoad();
//				M.api.err(rsp);
//				return false;
//			} 
//			this.customer.data.appointments = rsp.appointments;
			this.customer.sections.appointments.visible = 'yes';
			this.customer.sections.currentwineproduction.visible = 'yes';
			this.customer.sections.pastwineproduction.visible = 'yes';
			
//			var rsp = M.api.getJSON('ciniki.wineproduction.list', {'business_id':M.curBusinessID, 'customer_id':this.customer.customer_id});
//			if( rsp.stat != 'ok' ) {
//				M.stopLoad();
//				M.api.err(rsp);
//				return false;
//			} 
//			this.customer.data.currentwineproduction = [];
//			this.customer.data.pastwineproduction = [];
//			var i = 0;
//			for(i in rsp.orders) {
//				var order = rsp.orders[i].order;
//				if( order.status < 50 ) {
//					this.customer.data.currentwineproduction.push(rsp.orders[i]);
//				} else  {
//					this.customer.data.pastwineproduction.push(rsp.orders[i]);
//				}
//			}
			if( rsp.currenttwineproduction != null && rsp.currentwineproduction.length > 0 ) {
				this.customer.sections._buttons.buttons.delete.visible = 'no';
			}
			if( rsp.pastwineproduction != null && rsp.pastwineproduction.length > 0 ) {
				this.customer.sections._buttons.buttons.delete.visible = 'no';
			}
		}

		this.customer.sections.invoices.visible=(rsp.customer.invoices!=null&&rsp.customer.invoices.length>0)?'yes':'no';
		this.customer.refresh();
		this.customer.show(cb);
	}

	this.searchCustomers = function(cb, search_str) {
		this.search.search_str = search_str;
		var rsp = M.api.getJSONBg('ciniki.customers.searchFull', 
			{'business_id':M.curBusinessID, 'start_needle':search_str, 'limit':100});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.search.data = rsp.customers;
		this.search.refresh();
		this.search.show(cb);
	}
}
