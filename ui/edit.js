//
function ciniki_customers_edit() {
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
	this.init = function() {
		//
		// The add/edit form
		//
		this.edit = new M.panel('Customer',
			'ciniki_customers_edit', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.customers.main.edit');
		this.edit.customer_id = 0;
		this.edit.nextFn = null;
		this.edit.data = {};
		this.edit.formtab = 'person';
		this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{
			'person':{'label':'Person', 'field_id':1, 'form':'person'},
			'business':{'label':'Business', 'field_id':2, 'form':'business'},
			}};
		this.edit.forms = {};
		this.edit.forms.person = {
			'name':{'label':'Name', 'fields':{
				'cid':{'label':'Customer ID', 'type':'text', 'active':'no'},
				'prefix':{'label':'Title', 'type':'text', 'hint':'Mr., Ms., Dr., ...'},
				'first':{'label':'First', 'type':'text', 'livesearch':'yes',},
				'middle':{'label':'Middle', 'type':'text'},
				'last':{'label':'Last', 'type':'text', 'livesearch':'yes',},
				'suffix':{'label':'Degrees', 'type':'text', 'hint':'Ph.D, M.D., Jr., ...'},
				'birthdate':{'label':'Birthday', 'active':'no', 'type':'date'},
				}},
			'business':{'label':'Business', 'fields':{
				'company':{'label':'Company', 'type':'text', 'livesearch':'yes'},
				'department':{'label':'Department', 'type':'text'},
				'title':{'label':'Title', 'type':'text'},
				}},
			'phone':{'label':'Phone Numbers', 'fields':{
				'phone_home':{'label':'Home', 'type':'text'},
				'phone_work':{'label':'Work', 'type':'text'},
				'phone_cell':{'label':'Cell', 'type':'text'},
				'phone_fax':{'label':'Fax', 'type':'text'},
				}},
			'email':{'label':'Email', 'active':'no', 'fields':{
				'email':{'label':'Primary', 'type':'text'},
				'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.emailFlags},
				}},
			'emails':{'label':'Emails', 'active':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No emails',
				'addTxt':'Add Email',
				'addFn':'M.ciniki_customers_edit.showEmailEdit(\'M.ciniki_customers_edit.updateEditEmails();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'address':{'label':'Address', 'active':'no', 'fields':{
				'address1':{'label':'Street', 'type':'text', 'hint':''},
				'address2':{'label':'', 'type':'text'},
				'city':{'label':'City', 'type':'text', 'size':'small', 'livesearch':'yes'},
				'province':{'label':'Province/State', 'type':'text', 'size':'small'},
				'postal':{'label':'Postal/Zip', 'type':'text', 'hint':'', 'size':'small'},
				'country':{'label':'Country', 'type':'text', 'hint':'', 'size':'small'},
				'address_flags':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.addressFlags},
				}},
			'addresses':{'label':'Addresses', 'active':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No addresses',
				'addTxt':'Add Address',
				'addFn':'M.ciniki_customers_edit.showAddressEdit(\'M.ciniki_customers_edit.updateEditAddresses();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'subscriptions':{'label':'Subscriptions', 'visible':'no', 'fields':{}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save customer', 'fn':'M.ciniki_customers_edit.saveCustomer();'},
				}},
			};
		this.edit.forms.business = {
			'business':{'label':'Business', 'fields':{
				'cid':{'label':'Customer ID', 'type':'text', 'active':'no'},
				'company':{'label':'Name', 'type':'text', 'livesearch':'yes'},
				}},
			'name':{'label':'Contact', 'fields':{
				'prefix':{'label':'Title', 'type':'text', 'hint':'Mr., Ms., Dr., ...'},
				'first':{'label':'First', 'type':'text', 'livesearch':'yes'},
				'middle':{'label':'Middle', 'type':'text'},
				'last':{'label':'Last', 'type':'text', 'livesearch':'yes'},
				'suffix':{'label':'Degrees', 'type':'text', 'hint':'Ph.D, M.D., Jr., ...'},
				}},
			'phone':{'label':'Phone Numbers', 'fields':{
				'phone_home':{'label':'Home', 'type':'text'},
				'phone_work':{'label':'Work', 'type':'text'},
				'phone_cell':{'label':'Cell', 'type':'text'},
				'phone_fax':{'label':'Fax', 'type':'text'},
				}},
			'email':{'label':'Email', 'active':'no', 'fields':{
				'address':{'label':'Primary', 'type':'text'},
				'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.emailFlags},
				}},
			'emails':{'label':'Emails', 'active':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No emails',
				'addTxt':'Add Email',
				'addFn':'M.ciniki_customers_edit.showEmailEdit(\'M.ciniki_customers_edit.updateEditEmails();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'address':{'label':'Address', 'active':'no', 'fields':{
				'address1':{'label':'Street', 'type':'text', 'hint':''},
				'address2':{'label':'', 'type':'text'},
				'city':{'label':'City', 'type':'text', 'size':'small', 'livesearch':'yes'},
				'province':{'label':'Province/State', 'type':'text', 'size':'small'},
				'postal':{'label':'Postal/Zip', 'type':'text', 'hint':'', 'size':'small'},
				'country':{'label':'Country', 'type':'text', 'hint':'', 'size':'small'},
				'address_flags':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.addressFlags},
				}},
			'addresses':{'label':'Addresses', 'active':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No addresses',
				'addTxt':'Add Address',
				'addFn':'M.ciniki_customers_edit.showAddressEdit(\'M.ciniki_customers_edit.updateEditAddresses();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'subscriptions':{'label':'Subscriptions', 'visible':'no', 'fields':{}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save customer', 'fn':'M.ciniki_customers_edit.saveCustomer();'},
				}},
			};
		this.edit.sectionData = function(s) {
			if( s == 'subscriptions' ) {
				return this.subscriptions;
			}
			return this.data[s];
		};
		this.edit.cellValue = function(s, i, j, d) {
			if( s == 'emails' ) {
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
		};
		this.edit.rowFn = function(s, i, d) { 
			if( s == 'emails' ) {
				return 'M.ciniki_customers_edit.showEmailEdit(\'M.ciniki_customers_edit.updateEditEmails();\',M.ciniki_customers_edit.edit.customer_id,\'' + d.email.id + '\');';
			}
			if( s == 'addresses' ) {
				return 'M.ciniki_customers_edit.showAddressEdit(\'M.ciniki_customers_edit.updateEditAddresses();\',M.ciniki_customers_edit.edit.customer_id,\'' + d.address.id + '\');';
			}
			return null;
		};
		this.edit.fieldID = function(s, i, d) {
			if( s == 'subscriptions' ) {
				return d.subscription.id;
			}
			return i;
		};
		this.edit.fieldValue = function(s, i, d) { 
			if( s == 'subscriptions' ) {
				return d.subscription.name;
			}
			return this.data[i]; 
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			if( i.substring(0,13) == 'subscription_' ) {
				return {'method':'ciniki.subscriptions.getCustomerHistory', 'args':{'business_id':M.curBusinessID, 
					'subscription_id':i.substring(13), 'customer_id':this.customer_id, 'field':'status'}};
			} else {
				return {'method':'ciniki.customers.getHistory', 'args':{'business_id':M.curBusinessID, 
					'customer_id':this.customer_id, 'field':i}};
			}
		};
		this.edit.liveSearchCb = function(s, i, value) {
			if( i == 'city' ) {
				var rsp = M.api.getJSONBgCb('ciniki.customers.addressSearchQuick', 
					{'business_id':M.curBusinessID, 'start_needle':value, 'limit':25}, function(rsp) { 
						M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp['cities']); 
					});
			}
			if( i == 'first' || i == 'last' || i == 'company' ) {
				var rsp = M.api.getJSONBgCb('ciniki.customers.customerSearch', 
					{'business_id':M.curBusinessID, 'start_needle':value, 'field':i, 'limit':25}, function(rsp) { 
						M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp.customers); 
					});
				
			}
		};
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'first' || f == 'last' || f == 'company' ) { return d.customer.name; }
			if( f == 'city') { return d.city.name + ',' + d.city.province; }
			return '';
		};
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'first' || f == 'last' || f == 'company' ) { 
				return 'M.ciniki_customers_edit.showEdit(null,' + d.customer.id + ');';
			}
			if( f == 'city' ) {
				return 'M.ciniki_customers_edit.edit.updateCity(\'' + s + '\',\'' + escape(d.city.name) + '\',\'' + escape(d.city.province) + '\',\'' + escape(d.city.country) + '\');';
			}
		};
		this.edit.updateCity = function(s, city, province, country) {
			M.gE(this.panelUID + '_city').value = city;
			M.gE(this.panelUID + '_province').value = province;
			M.gE(this.panelUID + '_country').value = country;
			this.removeLiveSearch(s, 'city');
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_customers_edit.saveCustomer();');
		this.edit.addClose('cancel');

		//
		// The form panel to edit an address for a customer 
		//
		this.address = new M.panel('Customer Address',
			'ciniki_customers_edit', 'address',
			'mc', 'medium', 'sectioned', 'ciniki.customers.address.edit');
		this.address.data = {'flags':7};
		this.address.customer_id = 0;
		this.address.address_id = 0;
		this.address.sections = {
			'address':{'label':'Address', 'fields':{
				'address1':{'label':'Street', 'type':'text', 'hint':''},
				'address2':{'label':'', 'type':'text'},
				'city':{'label':'City', 'type':'text', 'size':'small', 'livesearch':'yes'},
				'province':{'label':'Province/State', 'type':'text', 'size':'small'},
				'postal':{'label':'Postal/Zip', 'type':'text', 'hint':'', 'size':'small'},
				'country':{'label':'Country', 'type':'text', 'hint':'', 'size':'small'},
				'flags':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.addressFlags},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save address', 'fn':'M.ciniki_customers_edit.saveAddress();'},
				'delete':{'label':'Delete address', 'fn':'M.ciniki_customers_edit.deleteAddress();'},
				}},
			};
		this.address.fieldValue = function(s, i, d) { return this.data[i]; }
		this.address.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.customers.addressHistory', 'args':{'business_id':M.curBusinessID, 
				'customer_id':this.customer_id, 'address_id':this.address_id, 'field':i}};
		};
		this.address.liveSearchCb = function(s, i, value) {
			if( i == 'city' ) {
				var rsp = M.api.getJSONBgCb('ciniki.customers.addressSearchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
					function(rsp) { 
						M.ciniki_customers_edit.address.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.address.panelUID + '_' + i), rsp['cities']); 
					});
			}
		};
		this.address.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'city') { return d.city.name + ',' + d.city.province; }
			return '';
		};
		this.address.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'city' ) {
				return 'M.ciniki_customers_edit.address.updateCity(\'' + s + '\',\'' + escape(d.city.name) + '\',\'' + escape(d.city.province) + '\',\'' + escape(d.city.country) + '\');';
			}
		};
		this.address.updateCity = function(s, city, province, country) {
			M.gE(this.panelUID + '_city').value = city;
			M.gE(this.panelUID + '_province').value = province;
			M.gE(this.panelUID + '_country').value = country;
			this.removeLiveSearch(s, 'city');
		};
		this.address.addButton('save', 'Save', 'M.ciniki_customers_edit.saveAddress();');
		this.address.addClose('cancel');

		//
		// The form panel to edit an email for a customer 
		//
		this.email = new M.panel('Customer Email',
			'ciniki_customers_edit', 'email',
			'mc', 'medium', 'sectioned', 'ciniki.customers.email.edit');
		this.email.data = {'flags':1};
		this.email.customer_id = 0;
		this.address.address_id = 0;
		this.email.sections = {
			'_email':{'label':'Email', 'fields':{
				'email':{'label':'Address', 'type':'text', 'hint':''},
				'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.emailFlags},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save email', 'fn':'M.ciniki_customers_edit.saveEmail();'},
				'delete':{'label':'Delete email', 'fn':'M.ciniki_customers_edit.deleteEmail();'},
				}},
			};
		this.email.fieldValue = function(s, i, d) { return this.data[i]; }
		this.email.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.customers.emailHistory', 'args':{'business_id':M.curBusinessID, 
				'customer_id':this.customer_id, 'email_id':this.email_id, 'field':i}};
		};

		this.email.addButton('save', 'Save', 'M.ciniki_customers_edit.saveEmail();');
		this.email.addClose('cancel');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_edit', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		//
		// Turn on or off the flag for web login based on if the module is enabled
		//
		if( M.curBusiness['modules']['ciniki.subscriptions'] != null ) {
			this.edit.forms.person.subscriptions.visible = 'yes';
		} else {
			this.edit.forms.person.subscriptions.visible = 'no';
		}

		//
		// Turn on or off the flag for web login based on if the module is enabled
		//
		if( M.curBusiness['modules']['ciniki.web'] != null ) {
			this.edit.forms.person.email.fields.flags.active = 'yes';
			this.edit.forms.business.email.fields.flags.active = 'yes';
			this.email.sections._email.fields.flags.active = 'yes';
		} else {
			this.edit.forms.person.email.fields.flags.active = 'no';
			this.edit.forms.business.email.fields.flags.active = 'no';
			this.email.sections._email.fields.flags.active = 'no';
		}

		if( M.curBusiness.customers != null && M.curBusiness.customers.settings != null ) {
//			this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{}};
//			var count=0;
//			for(i=1;i<9;i++) {
//				// Setup the form tabs for the customers edit forms
//				if( M.curBusiness.customers.settings['types-'+i+'-label'] != null && M.curBusiness.customers.settings["types-"+i+"-label"] != '' ) {
//					count++;
//					this.edit.formtabs.tabs[i] = {'label':M.curBusiness.customers.settings['types-'+i+'-label'], 'field_id':i, 'form':'person'};
//					if( M.curBusiness.customers.settings['types-'+i+'-form'] != null && M.curBusiness.customers.settings['types-'+i+'-form'] != '' ) {
//						this.edit.formtabs.tabs[i].form = M.curBusiness.customers.settings['types-'+i+'-form'];
//					}
//				}
//			}
//			if( count == 0 ) {
//				this.edit.formtabs = null;
//				this.edit.sections = this.edit.forms.person;
//			}
			this.edit.forms.person.name.fields.birthdate.active =(M.curBusiness.customers.settings['use-birthdate']=='yes')?'yes':'no';
//			if( M.curBusiness.customers.settings['use-birthdate'] == 'yes' ) {
//				this.edit.forms.person.name.fields.birthdate.active = 'yes';
//			} else {
//				this.edit.forms.person.name.fields.birthdate.active = 'no';
//			}
			if( M.curBusiness.customers.settings['use-cid'] == 'yes' ) {
				this.edit.forms.person.name.fields.cid.active = 'yes';
				this.edit.forms.business.business.fields.cid.active = 'yes';
			} else {
				this.edit.forms.person.name.fields.cid.active = 'no';
				this.edit.forms.business.business.fields.cid.active = 'no';
			}
		} else {
//			this.edit.formtabs = null;
			this.edit.sections = this.edit.forms.person;
			this.edit.forms.person.info.fields.cid.active = 'no';
			this.edit.forms.business.info.fields.cid.active = 'no';
		}

		if( args.next != null && args.next != '' ) {
			this.edit.nextFn = args.next;
		}
		if( args.edit_email_id != null && args.edit_email_id != '' 
			&& args.customer_id != null && args.customer_id > 0 ) {
			this.showEmailEdit(cb, args.customer_id, args.edit_email_id);
		}
		else if( args.edit_address_id != null && args.edit_address_id != '' 
			&& args.customer_id != null && args.customer_id > 0 ) {
			this.showAddressEdit(cb, args.customer_id, args.edit_address_id);
		} else {
			this.showEdit(cb, args.customer_id);
		}

		return false;
	}

	this.showEdit = function(cb, cid) {
		if( cid != null ) {
			this.edit.customer_id = cid;
		}
		this.edit.formtab = null;
		this.edit.formtab_field_id = null;

		if( this.edit.customer_id > 0 ) {
			// Edit existing customer
			this.edit.forms.person.email.active = 'no';
			this.edit.forms.person.address.active = 'no';
			this.edit.forms.person.emails.active = 'yes';
			this.edit.forms.person.addresses.active = 'yes';
			this.edit.forms.business.email.active = 'no';
			this.edit.forms.business.address.active = 'no';
			this.edit.forms.business.emails.active = 'yes';
			this.edit.forms.business.addresses.active = 'yes';
			var rsp = M.api.getJSONCb('ciniki.customers.getFull', {'business_id':M.curBusinessID, 
				'customer_id':this.edit.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.edit.data = rsp.customer;
					M.ciniki_customers_edit.edit.data.emails = rsp.customer.emails;
					M.ciniki_customers_edit.edit.data.addresses = rsp.customer.addresses;
//					if( rsp.customer.type == 0 || rsp.customer.type == 1 ) {
//						M.ciniki_customers_edit.edit.formtab = 'person';
//					}
					M.ciniki_customers_edit.showEditSubscriptions(cb);
				});
		} else {
			this.edit.data = {'type':'1', 'flags':1, 'address_flags':7};
			this.edit.forms.person.email.active = 'yes';
			this.edit.forms.person.address.active = 'yes';
			this.edit.forms.person.emails.active = 'no';
			this.edit.forms.person.addresses.active = 'no';
			this.edit.forms.business.email.active = 'yes';
			this.edit.forms.business.address.active = 'yes';
			this.edit.forms.business.emails.active = 'no';
			this.edit.forms.business.addresses.active = 'no';
			M.ciniki_customers_edit.showEditSubscriptions(cb);
		}
	};

	this.updateEditEmails = function() {
		var rsp = M.api.getJSONCb('ciniki.customers.getFull', {'business_id':M.curBusinessID, 
			'customer_id':this.edit.customer_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_customers_edit.edit.data.emails = rsp.customer.emails;
				M.ciniki_customers_edit.edit.refreshSection('emails');
				M.ciniki_customers_edit.edit.show();
			});
	};
	this.updateEditAddresses = function() {
		var rsp = M.api.getJSONCb('ciniki.customers.getFull', {'business_id':M.curBusinessID, 
			'customer_id':this.edit.customer_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_customers_edit.edit.data.addresses = rsp.customer.addresses;
				M.ciniki_customers_edit.edit.refreshSection('addresses');
				M.ciniki_customers_edit.edit.show();
			});
	};

	this.showEditSubscriptions = function(cb) {
		//
		// Get subscriptions available
		//
		if( M.curBusiness['modules']['ciniki.subscriptions'] != null ) {
			var rsp = M.api.getJSONCb('ciniki.subscriptions.list', {'business_id':M.curBusinessID, 
				'customer_id':this.edit.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					// Reset any existing fields
//					M.ciniki_customers_edit.edit.sections.subscriptions = {'label':'', 'fields':null};
					M.ciniki_customers_edit.edit.subscriptions = rsp.subscriptions;
					// Add subscriptions to the form
//					if( rsp.subscriptions.length > 0 ) {
//						M.ciniki_customers_edit.edit.sections['subscriptions']['label'] = 'Subscriptions';
//						M.ciniki_customers_edit.edit.sections['subscriptions']['fields'] = {};
//						var i = 0;
//						for(i in rsp.subscriptions) {
//							M.ciniki_customers_edit.edit.data['subscription_' + rsp.subscriptions[i].subscription.id] = rsp.subscriptions[i].subscription.status;
//							M.ciniki_customers_edit.edit.sections.subscriptions.fields['subscription_' + rsp.subscriptions[i].subscription.id] = {'label':rsp.subscriptions[i].subscription.name, 
//								'type':'toggle', 'toggles':M.ciniki_customers_edit.subscriptionOptions};
//						}
//					} else {
//						// Hide the subscriptions section when no business subscription setup
//						M.ciniki_customers_edit.edit.forms.person.subscriptions.visible = 'no';
//						M.ciniki_customers_edit.edit.forms.business.subscriptions.visible = 'no';
//					}
					M.ciniki_customers_edit.edit.refresh();
					M.ciniki_customers_edit.edit.show(cb);
				});
		} else {
			M.ciniki_customers_edit.edit.forms.person.subscriptions.visible = 'no';
			M.ciniki_customers_edit.edit.forms.business.subscriptions.visible = 'no';
			M.ciniki_customers_edit.edit.refresh();
			M.ciniki_customers_edit.edit.show(cb);
		}
	};

	this.saveCustomer = function() {
		// Build a list of subscriptions subscribed or unsubscribed
		var unsubs = '';
		var subs = '';
		var sc = '';
		var uc = '';
		var type = 1;
		if( this.edit.formtab == 'business' ) {
			type = 2;
		}
		for(i in this.edit.subscriptions) {
			var fname = 'subscription_' + this.edit.subscriptions[i].subscription.id;
			var o = this.edit.fieldValue('subscriptions', fname, this.edit.sections.subscriptions.fields[fname]);
			var n = this.edit.formValue(fname);
			if( o != n && n > 0 ) {
				if( n == 10 ) {
					subs += sc + this.edit.subscriptions[i].subscription.id; sc=',';
				} else if( n == 60 ) {
					unsubs += uc + this.edit.subscriptions[i].subscription.id; uc=',';
				}
			}	
		}

		if( this.edit.customer_id > 0 ) {
			var c = this.edit.serializeFormSection('no', 'name')
				+ this.edit.serializeFormSection('no', 'business')
				+ this.edit.serializeFormSection('no', 'phone')
				+ this.edit.serializeFormSection('no', '_notes');
			if( subs != '' ) { c += 'subscriptions=' + subs + '&'; }
			if( unsubs != '' ) { c += 'unsubscriptions=' + unsubs + '&'; }
			if( type != this.edit.data.type ) {
				c += 'type=' + type + '&';
			}
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.customers.update', 
					{'business_id':M.curBusinessID, 
					'customer_id':M.ciniki_customers_edit.edit.customer_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_customers_edit.closeEdit();
					});
			} else {
				M.ciniki_customers_edit.closeEdit();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var c = this.edit.serializeFormSection('yes', 'name')
				+ this.edit.serializeFormSection('yes', 'business')
				+ this.edit.serializeFormSection('yes', 'phone')
				+ this.edit.serializeFormSection('yes', 'email')
				+ this.edit.serializeFormSection('yes', 'address')
				+ this.edit.serializeFormSection('yes', '_notes');
			if( subs != '' ) { c += 'subscriptions=' + subs + '&'; }
			if( unsubs != '' ) { c += 'unsubscriptions=' + unsubs + '&'; }
			c += 'type=' + type + '&';
			var rsp = M.api.postJSONCb('ciniki.customers.add', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_customers_edit.edit.customer_id = rsp.id;
					M.ciniki_customers_edit.closeEdit();
			});
		}
	};

	this.closeEdit = function() {
		if( M.ciniki_customers_edit.edit.nextFn != null ) {
			// Check if we should pass customer id to next panel
			eval(M.ciniki_customers_edit.edit.nextFn + '(' + M.ciniki_customers_edit.edit.customer_id + ');');
		} else {
			M.ciniki_customers_edit.edit.close();
		}
	};

//	this.updateSubscriptions = function() {
//		// Save subscription information
//		if( M.curBusiness.modules['ciniki.subscriptions'] != null ) { 
//			var i = 0;
////			for(i in this.edit.subscriptions) {
//				var fname = 'subscription_' + this.edit.subscriptions[i].subscription.id;
//				var o = this.edit.fieldValue('subscriptions', fname, this.edit.sections.subscriptions.fields[fname]);
//				var n = this.edit.formValue(fname);
////				if( o != n && n > 0 ) {
//					var rsp = M.api.getJSON('ciniki.subscriptions.updateSubscriber', 
//						{'business_id':M.curBusinessID, 'subscription_id':this.edit.subscriptions[i].subscription.id, 
//						'customer_id':M.ciniki_customers_edit.edit.customer_id, 'status':n});
//					if( rsp.stat != 'ok' ) {
//						M.stopLoad();
//						M.api.err(rsp);
//						return false;
//					} 
//				}
//			}
//		}
//		M.stopLoad();
//		if( M.ciniki_customers_edit.edit.nextFn != null ) {
//			// Check if we should pass customer id to next panel
////			eval(M.ciniki_customers_edit.edit.nextFn + '(' + M.ciniki_customers_edit.edit.customer_id + ');');
//		} else {
//			M.ciniki_customers_edit.edit.close();
////		}
//	};

	this.deleteCustomer = function(cid) {
		if( cid != null && cid > 0 ) {
			if( confirm("Are you sure you want to remove this customer?") ) {
				var rsp = M.api.postJSONCb('ciniki.customers.delete', {'business_id':M.curBusinessID, 'customer_id':cid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.customer.close();
				});
			}
		}
	}

	this.showAddressEdit = function(cb, cid, aid) {
		if( cid != null ) {
			this.address.customer_id = cid;
		}
		if( aid != null ) {
			this.address.address_id = aid;
		}
		if( this.address.address_id > 0 ) {
			this.address.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.customers.addressGet', 
				{'business_id':M.curBusinessID, 'customer_id':this.address.customer_id, 
				'address_id':this.address.address_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.address.data = rsp.address;
					M.ciniki_customers_edit.address.refresh();
					M.ciniki_customers_edit.address.show(cb);
				});
		} else {
			this.address.data = {'flags':7};
			this.address.sections._buttons.buttons.delete.visible = 'no';
			this.address.refresh();
			this.address.show(cb);
		}
	};

	this.saveAddress = function() {
		if( this.address.address_id > 0 ) {
			var c = this.address.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.customers.addressUpdate', 
					{'business_id':M.curBusinessID, 
					'customer_id':M.ciniki_customers_edit.address.customer_id,
					'address_id':M.ciniki_customers_edit.address.address_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_customers_edit.address.close();
					});
			} else {
				M.ciniki_customers_edit.address.close();
			}
		} else {
			var c = this.address.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.customers.addressAdd', 
				{'business_id':M.curBusinessID, 
				'customer_id':M.ciniki_customers_edit.address.customer_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_customers_edit.address.close();
				});
		}
	};

	this.deleteAddress = function(customerID, addressID) {
		if( confirm("Are you sure you want to remove this address?") ) {
			var rsp = M.api.getJSONCb('ciniki.customers.addressDelete', 
				{'business_id':M.curBusinessID, 
					'customer_id':M.ciniki_customers_edit.address.customer_id, 
					'address_id':M.ciniki_customers_edit.address.address_id}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_customers_edit.address.close();
					});
		}
	};

	this.showEmailEdit = function(cb, cid, eid) {
		if( cid != null ) {
			this.email.customer_id = cid;
		}
		if( eid != null ) {
			this.email.email_id = eid;
		}
		if( this.email.email_id > 0 ) {
			this.email.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.customers.emailGet', 
				{'business_id':M.curBusinessID, 'customer_id':this.email.customer_id, 
				'email_id':this.email.email_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.email.data = rsp.email;
					M.ciniki_customers_edit.email.refresh();
					M.ciniki_customers_edit.email.show(cb);
				});
		} else {
			this.email.data = {'flags':1};
			this.email.sections._buttons.buttons.delete.visible = 'no';
			this.email.refresh();
			this.email.show(cb);
		}
	};

	this.saveEmail = function() {
		// Check if email address exists already
		var e = this.email.formFieldValue(this.email.sections._email.fields.email, 'email');
		if( e == '' ) {
			alert("Invalid email address");
			return false;
		}
		// Check if email address changed
		if( e != this.email.fieldValue('emails', 'email', this.email.sections._email.fields.email) ) {
			var rsp = M.api.getJSONCb('ciniki.customers.emailSearch', {'business_id':M.curBusinessID, 
				'customer_id':M.ciniki_customers_edit.email.customer_id, 'email':e}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					if( rsp.email != null ) {
						alert("Email address already exists");
						return false;
					}
					M.ciniki_customers_edit.saveEmailFinish();
				});
		} else {
			this.saveEmailFinish();
		}
	};

	this.saveEmailFinish = function() {
		if( this.email.email_id > 0 ) {
			var c = this.email.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.customers.emailUpdate', 
					{'business_id':M.curBusinessID, 
					'customer_id':M.ciniki_customers_edit.email.customer_id, 
					'email_id':M.ciniki_customers_edit.email.email_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_customers_edit.email.close();
					});
			} else {
				M.ciniki_customers_edit.email.close();
			}
		} else {
			var c = M.ciniki_customers_edit.email.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.customers.emailAdd', 
				{'business_id':M.curBusinessID, 
				'customer_id':M.ciniki_customers_edit.email.customer_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_customers_edit.email.close();
				});
		}
	};

	this.deleteEmail = function(customerID, emailID) {
		if( confirm("Are you sure you want to remove this email?") ) {
			var rsp = M.api.getJSONCb('ciniki.customers.emailDelete', 
				{'business_id':M.curBusinessID, 'customer_id':this.email.customer_id, 
				'email_id':this.email.email_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.email.close();
				});
		}
	};
}
