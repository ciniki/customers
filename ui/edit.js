//
function ciniki_customers_edit() {
	//
	// Panels
	//
	this.main = null;
	this.dealerinfo = 'yes';

	this.cb = null;
	this.toggleOptions = {'Off':'Off', 'On':'On'};
	this.subscriptionOptions = {'60':'Unsubscribed', '10':'Subscribed'};
	this.memberStatus = {'10':'Active', '60':'Suspended'};
	this.membershipLength = {'10':'Monthly', '20':'Yearly', '60':'Lifetime'};
	this.membershipType = {'10':'Regular', '110':'Complimentary', '150':'Reciprocal'};
	this.memberWebFlags = {'1':{'name':'Visible'}};
	this.memberPhoneFlags = {'4':{'name':'Public'}};
	this.dealerStatus = {'5':'Prospect', '10':'Active', '60':'Suspended'};
	this.dealerWebFlags = {'2':{'name':'Visible'}};
	this.distributorStatus = {'10':'Active', '60':'Suspended'};
	this.seasonStatus = {'0':'Unknown', '10':'Active', '60':'Inactive'};
	this.distributorWebFlags = {'3':{'name':'Visible'}};
	this.addressFlags = {
		'1':{'name':'Shipping'}, 
		'2':{'name':'Billing'}, 
		'3':{'name':'Mailing'},
		};
	this.memberAddressFlags = {
		'1':{'name':'Shipping'}, 
		'2':{'name':'Billing'}, 
		'3':{'name':'Mailing'},
		'4':{'name':'Public'},
		};
	this.emailFlags = {
		'1':{'name':'Web Login'}, 
		'5':{'name':'No Emails'},
//		'6':{'name':'Secondary'},
		};
	this.memberEmailFlags = {
		'1':{'name':'Web Login'}, 
		'4':{'name':'Public'},
		'5':{'name':'No Emails'},
		};
	this.linkFlags = {
		'1':{'name':'Visible'}, 
		};
	this.customerStatus = {
		'10':'Active', 
		'40':'On Hold', 
		'50':'Suspended', 
		'60':'Deleted', 
		};
	this.displayNameFormatOptions = {
		'':'System Settings',
		'company':'Company Name',
		'company - person':'Company Name - Person',
		'person - company':'Person - Company Name',
		};
	this.init = function() {
		//
		// The add/edit form
		//
		this.edit = new M.panel('Customer',
			'ciniki_customers_edit', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.edit');
		this.edit.subscriptions = null;
		this.edit.customer_id = 0;
		this.edit.nextFn = null;
		this.edit.data = {};
		this.edit.formtab = 'person';
		this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{
			'person':{'label':'Person', 'field_id':1, 'form':'person'},
			'business':{'label':'Business', 'field_id':2, 'form':'business'},
			}};
		this.edit.forms = {};
		this.edit.parent_id = 0;
		this.edit.forms.person = {
			'parent':{'label':'', 'active':'no', 'aside':'yes', 'fields':{
				'parent_id':{'label':'Parent', 'type':'fkid', 'livesearch':'yes'},
				}},
			'name':{'label':'Name', 'aside':'yes', 'fields':{
				'status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.customerStatus},
				'eid':{'label':'Customer ID', 'type':'text', 'active':'no', 'livesearch':'yes'},
				'prefix':{'label':'Title', 'type':'text', 'hint':'Mr., Ms., Dr., ...'},
				'first':{'label':'First', 'type':'text', 'livesearch':'yes',},
				'middle':{'label':'Middle', 'type':'text'},
				'last':{'label':'Last', 'type':'text', 'livesearch':'yes',},
				'suffix':{'label':'Degrees', 'type':'text', 'hint':'Ph.D, M.D., Jr., ...'},
				'birthdate':{'label':'Birthday', 'active':'no', 'type':'date'},
			}},
			'account':{'label':'', 'aside':'yes', 'fields':{
				'salesrep_id':{'label':'Sales Rep', 'active':'no', 'type':'select', 'options':{}},
				'pricepoint_id':{'label':'Price Point', 'active':'no', 'type':'select', 'options':{}},
				'tax_number':{'label':'Tax Number', 'active':'no', 'type':'text'},
				'tax_location_id':{'label':'Tax Location', 'active':'no', 'type':'select', 'options':{}},
				'reward_level':{'label':'Reward Teir', 'active':'no', 'type':'text', 'size':'small'},
				'sales_total':{'label':'Current Sales Total', 'active':'no', 'type':'text', 'size':'small'},
				'sales_total_prev':{'label':'Previous Sales', 'active':'no', 'type':'text', 'size':'small'},
				'start_date':{'label':'Start Date', 'active':'yes', 'type':'date'},
				}},
			'_connection':{'label':'How did you hear about us?', 'aside':'yes', 'active':'no', 'fields':{
				'connection':{'label':'', 'hidelabel':'yes', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
				}},
			'_customer_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
				'customer_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
			'_customer_tags':{'label':'Tags', 'aside':'yes', 'active':'no', 'fields':{
				'customer_tags':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new tag:'},
				}},
			'membership':{'label':'Membership', 'aside':'yes', 'active':'no', 'fields':{
				'member_status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.memberStatus},
				'member_lastpaid':{'label':'Last Paid', 'active':'no', 'type':'text', 'size':'medium'},
				'membership_length':{'label':'Length', 'type':'toggle', 'none':'yes', 'toggles':this.membershipLength},
				'membership_type':{'label':'Type', 'type':'toggle', 'none':'yes', 'toggles':this.membershipType},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.memberWebFlags},
				}},
			'_seasons':{'label':'Seasons', 'aside':'yes', 'active':'no', 'fields':{
				}},
			'_member_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
				'member_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
			'dealer':{'label':'Dealer', 'aside':'yes', 'active':'no', 'fields':{
				'dealer_status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.dealerStatus},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.dealerWebFlags},
				}},
			'_dealer_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
				'dealer_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
			'distributor':{'label':'Distributor', 'aside':'yes', 'active':'no', 'fields':{
				'distributor_status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.distributorStatus},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.distributorWebFlags},
				}},
			'_distributor_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
				'distributor_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
			'business':{'label':'Business', 'aside':'yes', 'fields':{
				'company':{'label':'Company', 'type':'text', 'livesearch':'yes'},
				'department':{'label':'Department', 'type':'text'},
				'title':{'label':'Title', 'type':'text'},
				}},
            'simplephone':{'label':'Phone Numbers', 'active':'no', 'fields':{
				'phone_home':{'label':'Home', 'type':'text'},
				'phone_work':{'label':'Work', 'type':'text'},
				'phone_cell':{'label':'Cell', 'type':'text'},
				'phone_fax':{'label':'Fax', 'type':'text'},
                }},
			'phone':{'label':'Phone Numbers', 'active':'no', 'fields':{
				'phone_label_1':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
				'phone_number_1':{'label':'Number', 'type':'text', 'size':'medium'},
				'phone_flags_1':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.memberPhoneFlags},
				'phone_label_2':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
				'phone_number_2':{'label':'Number', 'type':'text', 'size':'medium'},
				'phone_flags_2':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.memberPhoneFlags},
				'phone_label_3':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
				'phone_number_3':{'label':'Number', 'type':'text', 'size':'medium'},
				'phone_flags_3':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.memberPhoneFlags},
				}},
			'phones':{'label':'Phones', 'active':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No phones',
				'addTxt':'Add Phone',
				'addFn':'M.ciniki_customers_edit.showPhoneEdit(\'M.ciniki_customers_edit.updateEditPhones();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'simpleemail':{'label':'Email', 'active':'no', 'fields':{
				'primary_email':{'label':'Primary', 'type':'text'},
				'alternate_email':{'label':'Alternate', 'type':'text'},
				}},
			'email':{'label':'Email', 'active':'no', 'fields':{
				'email_address':{'label':'Primary', 'type':'text'},
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
				'phone':{'label':'Phone', 'type':'text', 'hint':'Helpful for deliveries'},
				'address_flags':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
				}},
			'addresses':{'label':'Addresses', 'active':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No addresses',
				'addTxt':'Add Address',
				'addFn':'M.ciniki_customers_edit.showAddressEdit(\'M.ciniki_customers_edit.updateEditAddresses();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'links':{'label':'Links', 'active':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', ''],
				'noData':'No links',
				'addTxt':'Add Link',
				'addFn':'M.ciniki_customers_edit.showLinkEdit(\'M.ciniki_customers_edit.updateEditLinks();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'subscriptions':{'label':'Subscriptions', 'visible':'no', 'fields':{}},
			'_image':{'label':'', 'active':'no', 'type':'imageform', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'_image_caption':{'label':'', 'active':'no', 'fields':{
				'primary_image_caption':{'label':'Caption', 'type':'text'},
				}},
			'_short_bio':{'label':'Synopsis', 'active':'no', 'fields':{
				'short_bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_full_bio':{'label':'Biography', 'active':'no', 'fields':{
				'full_bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_customers_edit.customerSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_customers_edit.deleteCustomer();'},
				'remove':{'label':'Remove', 'fn':'M.ciniki_customers_edit.removeCustomer();'},	// Used when linked with next button.
				}},
			};
		this.edit.forms.business = {
			'parent':{'label':'', 'active':'no', 'aside':'yes', 'fields':{
				'parent_id':{'label':'Parent', 'type':'fkid', 'livesearch':'yes'},
				}},
			'business':{'label':'Business', 'aside':'yes', 'fields':{
				'status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.customerStatus},
				'eid':{'label':'Customer ID', 'type':'text', 'active':'no', 'livesearch':'yes'},
				'company':{'label':'Name', 'type':'text', 'livesearch':'yes'},
				'display_name_format':{'label':'Display', 'type':'select', 'options':this.displayNameFormatOptions},
				}},
			'account':{'label':'', 'aside':'yes', 'fields':{
				'salesrep_id':{'label':'Sales Rep', 'active':'no', 'type':'select', 'options':{}},
				'pricepoint_id':{'label':'Price Point', 'active':'no', 'type':'select', 'options':{}},
				'tax_number':{'label':'Tax Number', 'active':'no', 'type':'text'},
				'tax_location_id':{'label':'Tax Location', 'active':'no', 'type':'select', 'options':{}},
				'reward_level':{'label':'Reward Teir', 'active':'no', 'type':'text', 'size':'small'},
				'sales_total':{'label':'Sales Total', 'active':'no', 'type':'text', 'size':'small'},
				'sales_total_prev':{'label':'Previous Sales', 'active':'no', 'type':'text', 'size':'small'},
				'start_date':{'label':'Start Date', 'active':'yes', 'type':'date'},
				}},
			'name':{'label':'Contact', 'aside':'yes', 'fields':{
				'prefix':{'label':'Title', 'type':'text', 'hint':'Mr., Ms., Dr., ...'},
				'first':{'label':'First', 'type':'text', 'livesearch':'yes'},
				'middle':{'label':'Middle', 'type':'text'},
				'last':{'label':'Last', 'type':'text', 'livesearch':'yes'},
				'suffix':{'label':'Degrees', 'type':'text', 'hint':'Ph.D, M.D., Jr., ...'},
				'department':{'label':'Department', 'type':'text'},
				'title':{'label':'Title', 'type':'text'},
				'birthdate':{'label':'Birthday', 'active':'no', 'type':'date'},
				}},
			'_connection':{'label':'How did you hear about us?', 'aside':'yes', 'active':'no', 'fields':{
				'connection':{'label':'', 'hidelabel':'yes', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
				}},
			'_customer_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
				'customer_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
			'_customer_tags':{'label':'Tags', 'aside':'yes', 'active':'no', 'fields':{
				'customer_tags':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new tag:'},
				}},
			'membership':{'label':'Membership', 'aside':'yes', 'active':'no', 'fields':{
				'member_status':{'label':'Membership', 'type':'toggle', 'none':'yes', 'toggles':this.memberStatus},
				'member_lastpaid':{'label':'Last Paid', 'active':'no', 'type':'text', 'size':'medium'},
				'membership_length':{'label':'Length', 'type':'toggle', 'none':'yes', 'toggles':this.membershipLength},
				'membership_type':{'label':'Type', 'type':'toggle', 'none':'yes', 'toggles':this.membershipType},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.memberWebFlags},
				}},
			'_seasons':{'label':'Seasons', 'aside':'yes', 'active':'no', 'fields':{
				}},
			'_member_categories':{'label':'Member Categories', 'aside':'yes', 'active':'no', 'fields':{
				'member_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
			'dealer':{'label':'Dealer', 'aside':'yes', 'active':'no', 'fields':{
				'dealer_status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.dealerStatus},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.dealerWebFlags},
				}},
			'_dealer_categories':{'label':'Dealer Categories', 'aside':'yes', 'active':'no', 'fields':{
				'dealer_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
			'distributor':{'label':'Distributor', 'aside':'yes', 'active':'no', 'fields':{
				'distributor_status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.distributorStatus},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.distributorWebFlags},
				}},
			'_distributor_categories':{'label':'Distributor Categories', 'aside':'yes', 'active':'no', 'fields':{
				'distributor_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
            'simplephone':{'label':'Phone Numbers', 'active':'no', 'fields':{
				'phone_home':{'label':'Home', 'type':'text'},
				'phone_work':{'label':'Work', 'type':'text'},
				'phone_cell':{'label':'Cell', 'type':'text'},
				'phone_fax':{'label':'Fax', 'type':'text'},
                }},
			'phone':{'label':'Phone Numbers', 'active':'no', 'fields':{
				'phone_label_1':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
				'phone_number_1':{'label':'Number', 'type':'text', 'size':'medium'},
				'phone_flags_1':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.memberPhoneFlags},
				'phone_label_2':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
				'phone_number_2':{'label':'Number', 'type':'text', 'size':'medium'},
				'phone_flags_2':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.memberPhoneFlags},
				'phone_label_3':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
				'phone_number_3':{'label':'Number', 'type':'text', 'size':'medium'},
				'phone_flags_3':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.memberPhoneFlags},
				}},
			'phones':{'label':'Phones', 'active':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No phones',
				'addTxt':'Add Phone',
				'addFn':'M.ciniki_customers_edit.showPhoneEdit(\'M.ciniki_customers_edit.updateEditPhones();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'simpleemail':{'label':'Email', 'active':'no', 'fields':{
				'primary_email':{'label':'Primary', 'type':'text'},
				'alternate_email':{'label':'Alternate', 'type':'text'},
				}},
			'email':{'label':'Email', 'active':'no', 'fields':{
				'email_address':{'label':'Primary', 'type':'text'},
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
				'phone':{'label':'Phone', 'type':'text', 'hint':'Helpful for deliveries'},
				'address_flags':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
				}},
			'addresses':{'label':'Addresses', 'active':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No addresses',
				'addTxt':'Add Address',
				'addFn':'M.ciniki_customers_edit.showAddressEdit(\'M.ciniki_customers_edit.updateEditAddresses();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'links':{'label':'Links', 'active':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', ''],
				'noData':'No links',
				'addTxt':'Add Link',
				'addFn':'M.ciniki_customers_edit.showLinkEdit(\'M.ciniki_customers_edit.updateEditLinks();\',M.ciniki_customers_edit.edit.customer_id,0);',
				},
			'subscriptions':{'label':'Subscriptions', 'visible':'no', 'fields':{}},
			'_image':{'label':'', 'type':'imageform', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'_image_caption':{'label':'', 'active':'no', 'fields':{
				'primary_image_caption':{'label':'Caption', 'type':'text'},
				}},
			'_short_bio':{'label':'Synopsis', 'active':'no', 'fields':{
				'short_bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_full_bio':{'label':'Biography', 'active':'no', 'fields':{
				'full_bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_customers_edit.customerSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_customers_edit.deleteCustomer();'},
				'remove':{'label':'Remove', 'fn':'M.ciniki_customers_edit.removeCustomer();'},	// Used when linked with next button.
				}},
			};
		this.edit.sectionData = function(s) {
			if( s == 'subscriptions' ) {
				return this.subscriptions;
			}
			return this.data[s];
		};
		this.edit.cellValue = function(s, i, j, d) {
			if( s == 'phones' ) {
				switch (j) {
					case 0: return d.phone.phone_label;
					case 1: return d.phone.phone_number + ((M.ciniki_customers_edit.edit.memberinfo=='yes'&&d.phone.flags&0x08)>0?' <span class="subdue">(Public)</span>':'');
				}
			}
			if( s == 'emails' ) {
				var flags = '';
				if( (d.email.flags&0x08) > 0 ) { flags += (flags!=''?', ':'') + 'Public'; }
				if( (d.email.flags&0x10) > 0 ) { flags += (flags!=''?', ':'') + 'No Emails'; }
				return M.linkEmail(d.email.address) + (flags!=''?' <span class="subdue">(' + flags + ')</span>':'');
			}
			if( s == 'addresses' ) {
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
					if( d.address.province != '' ) { v += ', ' + d.address.province + '  '; }
					else if( d.address.city != '' ) { v += '  '; }
					if( d.address.postal != '' ) { v += d.address.postal + '<br/>'; }
					if( d.address.country != '' ) { v += d.address.country + '<br/>'; }
					if( d.address.phone != '' ) { v += 'Phone: ' + d.address.phone + '<br/>'; }
					return v;
				}
			}
			if( s == 'links' ) {
				if( d.link.name != '' ) {
					return '<span class="maintext">' + d.link.name + ((M.ciniki_customers_edit.edit.memberinfo=='yes'&&d.link.webflags&0x01)>0?' <span class="subdue">(Public)</span>':'') + '</span><span class="subtext">' + M.hyperlink(d.link.url) + '</span>';
				} else {
					return M.hyperlink(d.link.url) + ((M.ciniki_customers_edit.edit.memberinfo=='yes'&&d.link.webflags&0x01)>0?' <span class="subdue">(Public)</span>':'');
				}
			}
		};
		this.edit.rowFn = function(s, i, d) { 
			if( s == 'phones' ) {
				return 'M.ciniki_customers_edit.showPhoneEdit(\'M.ciniki_customers_edit.updateEditPhones();\',M.ciniki_customers_edit.edit.customer_id,\'' + d.phone.id + '\');';
			}
			if( s == 'emails' ) {
				return 'M.ciniki_customers_edit.showEmailEdit(\'M.ciniki_customers_edit.updateEditEmails();\',M.ciniki_customers_edit.edit.customer_id,\'' + d.email.id + '\');';
			}
			if( s == 'addresses' ) {
				return 'M.ciniki_customers_edit.showAddressEdit(\'M.ciniki_customers_edit.updateEditAddresses();\',M.ciniki_customers_edit.edit.customer_id,\'' + d.address.id + '\');';
			}
			if( s == 'links' ) {
				return 'M.ciniki_customers_edit.showLinkEdit(\'M.ciniki_customers_edit.updateEditLinks();\',M.ciniki_customers_edit.edit.customer_id,\'' + d.link.id + '\');';
			}
			return null;
		};
		this.edit.fieldValue = function(s, i, d) { 
			if( i == 'parent_id_fkidstr' ) { return ((this.data.parent!=null&&this.data.parent.display_name!=null)?this.data.parent.display_name:''); }
			if( i == 'parent_id' ) { return ((this.data.parent!=null&&this.data.parent.id!=null)?this.data.parent.id:0); }
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
		this.edit.addDropImage = function(iid) {
			M.ciniki_customers_edit.edit.setFieldValue('primary_image_id', iid);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};

		this.edit.liveSearchCb = function(s, i, value) {
			if( i == 'parent_id' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', 
					{'business_id':M.curBusinessID, 'start_needle':value, 'limit':25}, function(rsp) { 
						M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp['customers']); 
					});
			} else if( i == 'city' ) {
				M.api.getJSONBgCb('ciniki.customers.addressSearchQuick', 
					{'business_id':M.curBusinessID, 'start_needle':value, 'limit':25}, function(rsp) { 
						M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp['cities']); 
					});
			} else if( i == 'eid' || i == 'first' || i == 'last' || i == 'company' ) {
				M.api.getJSONBgCb('ciniki.customers.customerSearch', 
					{'business_id':M.curBusinessID, 'start_needle':value, 'field':i, 'limit':25}, function(rsp) { 
						M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp.customers); 
					});
			} else if( i == 'phone_label_1' || i == 'phone_label_2' || i == 'phone_label_3' ) {
				M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), ['Home','Work','Cell','Fax']);
			} else if( i == 'connection' ) {
				M.api.getJSONBgCb('ciniki.customers.connectionSearch', 
					{'business_id':M.curBusinessID, 'start_needle':value, 'field':i, 'limit':25}, function(rsp) { 
						M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp.connections); 
					});
			}
		};
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'parent_id' || f == 'eid' || f == 'first' || f == 'last' || f == 'company' ) { 
				if( d.customer.eid != null && d.customer.eid != '' ) {
					return d.customer.eid + ' - ' + d.customer.display_name; 
				}
				return d.customer.display_name; 
			}
			else if( f == 'city') { return d.city.name + ',' + d.city.province; }
			else if( f == 'phone_label_1' || f == 'phone_label_2' || f == 'phone_label_3' ) { return d; }
			else if( f == 'connection' ) {
				return d.connection.connection;
			}
			return '';
		};
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'parent_id' ) {
				return 'M.ciniki_customers_edit.edit.updateParent(\'' + s + '\',\'' + escape(d.customer.id) + '\',\'' + escape(d.customer.display_name) + '\');'
			}
			else if( f == 'eid' || f == 'first' || f == 'last' || f == 'company' ) { 
				if( M.ciniki_customers_edit.edit.data.parent != null ) {
					return 'M.ciniki_customers_edit.showEdit(null,\'' + d.customer.id + '\',null,\'' + M.ciniki_customers_edit.edit.data.parent.id + '\',\'' + escape(M.ciniki_customers_edit.edit.data.parent.display_name) + '\');';
				}
				return 'M.ciniki_customers_edit.showEdit(null,' + d.customer.id + ');';
			}
			else if( f == 'city' ) {
				return 'M.ciniki_customers_edit.edit.updateCity(\'' + s + '\',\'' + escape(d.city.name) + '\',\'' + escape(d.city.province) + '\',\'' + escape(d.city.country) + '\');';
			}
			else if( f == 'phone_label_1' || f == 'phone_label_2' || f == 'phone_label_3' ) {
				return 'M.ciniki_customers_edit.edit.updateLabel(\'' + s + '\',\'' + f + '\',\'' + escape(d) + '\');';
			}
			else if( f == 'connection' ) {
				return 'M.ciniki_customers_edit.edit.updateConnection(\'' + s + '\',\'' + escape(d.connection.connection) + '\');';
			}
		};
		this.edit.updateParent = function(s, cid, name) {
			M.gE(this.panelUID + '_parent_id').value = cid;
			M.gE(this.panelUID + '_parent_id_fkidstr').value = unescape(name);
			this.removeLiveSearch(s, 'parent_id');
		};
		this.edit.updateCity = function(s, city, province, country) {
			M.gE(this.panelUID + '_city').value = city;
			M.gE(this.panelUID + '_province').value = province;
			M.gE(this.panelUID + '_country').value = country;
			this.removeLiveSearch(s, 'city');
		};
		this.edit.updateLabel = function(s, i, l) {
			M.gE(this.panelUID + '_' + i).value = l;
			this.removeLiveSearch(s, i);
		};
		this.edit.updateConnection = function(s, connection) {
			M.gE(this.panelUID + '_connection').value = connection;
			this.removeLiveSearch(s, 'connection');
		};
		this.edit.setupStatus = function() {
			if( this.memberinfo == 'yes' && this.data.member_status == 0 ) {
				this.setFieldValue('member_status', 10);
			}
			if( this.dealerinfo == 'yes' && this.data.dealer_status == 0 ) {
				this.setFieldValue('dealer_status', 10);
			}
			if( this.distributorinfo == 'yes' && this.data.distributor_status == 0 ) {
				this.setFieldValue('distributor_status', 10);
			}
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_customers_edit.customerSave();');
		this.edit.addClose('cancel');

		//
		// The form panel to edit an address for a customer 
		//
		this.address = new M.panel('Customer Address',
			'ciniki_customers_edit', 'address',
			'mc', 'medium', 'sectioned', 'ciniki.customers.edit.address');
		this.address.data = {'flags':15};
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
				'phone':{'label':'Phone', 'active':'no', 'type':'text', 'hint':'Helpful for deliveries'},
				'flags':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
				}},
			'_latlong_buttons':{'label':'', 'buttons':{
				'_latlong':{'label':'Lookup Lat/Long', 'fn':'M.ciniki_customers_edit.lookupLatLong();'},
				}},
			'_latlong':{'label':'', 'fields':{
				'latitude':{'label':'Latitude', 'type':'text'},
				'longitude':{'label':'Longitude', 'type':'text'},
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
			'mc', 'medium', 'sectioned', 'ciniki.customers.edit.email');
		this.email.data = {'flags':1};
		this.email.customer_id = 0;
		this.email.email_id = 0;
		this.email.sections = {
			'_email':{'label':'Email', 'fields':{
				'address':{'label':'Address', 'type':'text', 'hint':''},
				'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.emailFlags},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save email', 'fn':'M.ciniki_customers_edit.saveEmail();'},
				'password':{'label':'Set Password', 'fn':'M.ciniki_customers_edit.setPassword();'},
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

		//
		// The form panel to edit an phone for a customer 
		//
		this.phone = new M.panel('Customer Phone Number',
			'ciniki_customers_edit', 'phone',
			'mc', 'narrow', 'sectioned', 'ciniki.customers.edit.phone');
		this.phone.data = {'flags':1};
		this.phone.customer_id = 0;
		this.phone.phone_id = 0;
		this.phone.sections = {
			'_phone':{'label':'Phone', 'fields':{
				'phone_label':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell, Fax', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
				'phone_number':{'label':'Number', 'type':'text', 'size':'medium'},
				'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save phone', 'fn':'M.ciniki_customers_edit.savePhone();'},
				'delete':{'label':'Delete phone', 'fn':'M.ciniki_customers_edit.deletePhone();'},
				}},
			};
		this.phone.fieldValue = function(s, i, d) { return this.data[i]; }
		this.phone.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.customers.phoneHistory', 'args':{'business_id':M.curBusinessID, 
				'customer_id':this.customer_id, 'phone_id':this.phone_id, 'field':i}};
		};
		this.phone.liveSearchCb = function(s, i, value) {
			M.ciniki_customers_edit.phone.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.phone.panelUID + '_' + i), ['Home','Work','Cell','Fax']);
		};
		this.phone.liveSearchResultValue = function(s, f, i, j, d) {
			return d;
		};
		this.phone.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.ciniki_customers_edit.phone.updateLabel(\'' + s + '\',\'' + escape(d) + '\');';
		};
		this.phone.updateLabel = function(s, l) {
			M.gE(this.panelUID + '_phone_label').value = l;
			this.removeLiveSearch(s, 'phone_label');
		};

		this.phone.addButton('save', 'Save', 'M.ciniki_customers_edit.savePhone();');
		this.phone.addClose('cancel');

		//
		// The form panel to edit a link for a customer 
		//
		this.link = new M.panel('Customer Website',
			'ciniki_customers_edit', 'link',
			'mc', 'medium', 'sectioned', 'ciniki.customers.edit.link');
		this.link.data = {'flags':1};
		this.link.customer_id = 0;
		this.link.link_id = 0;
		this.link.sections = {
			'_link':{'label':'Website', 'fields':{
				'name':{'label':'Name', 'type':'text', 'hint':''},
				'url':{'label':'URL', 'type':'text', 'hint':''},
				'webflags':{'label':'Website', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.linkFlags},
				}},
//			'_description':{'label':'Description', 'fields':{
//				'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
//				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save Website', 'fn':'M.ciniki_customers_edit.saveLink();'},
				'delete':{'label':'Delete Website', 'fn':'M.ciniki_customers_edit.deleteLink();'},
				}},
			};
		this.link.fieldValue = function(s, i, d) { return this.data[i]; }
		this.link.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.customers.linkHistory', 'args':{'business_id':M.curBusinessID, 
				'customer_id':this.customer_id, 'link_id':this.link_id, 'field':i}};
		};

		this.link.addButton('save', 'Save', 'M.ciniki_customers_edit.saveLink();');
		this.link.addClose('cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var settings = null;
		if( M.curBusiness.modules['ciniki.customers'] != null
			&& M.curBusiness.modules['ciniki.customers'].settings != null ) {
			settings = M.curBusiness.modules['ciniki.customers'].settings;
		}

        if( (M.curBusiness.modules['ciniki.customers'].flags&0x02) == 0x02 ) {
            this.addressFlags = {
                '1':{'name':'Shipping'}, 
                '2':{'name':'Billing'}, 
                '3':{'name':'Mailing'},
                '4':{'name':'Public'},
            };
        } else {
            this.addressFlags = {
                '1':{'name':'Shipping'}, 
                '2':{'name':'Billing'}, 
                '3':{'name':'Mailing'},
            };
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
		// Turn off account section by default
		var account = 'no';

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
	
		// Birthdate
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x8000) > 0 ) {
			this.edit.forms.person.name.fields.birthdate.active = 'yes';
			this.edit.forms.business.name.fields.birthdate.active = 'yes';
		} else {
			this.edit.forms.person.name.fields.birthdate.active = 'no';
			this.edit.forms.business.name.fields.birthdate.active = 'no';
		}
		// Start date
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x04000000) > 0 ) {
			this.edit.forms.person.account.fields.start_date.active = 'yes';
			this.edit.forms.business.account.fields.start_date.active = 'yes';
			account = 'yes';
		} else {
			this.edit.forms.person.account.fields.start_date.active = 'no';
			this.edit.forms.business.account.fields.start_date.active = 'no';
		}
		// Connection - How did you hear about us?
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x4000) > 0 ) {
			this.edit.forms.person._connection.active = 'yes';
			this.edit.forms.business._connection.active = 'yes';
		} else {
			this.edit.forms.person._connection.active = 'no';
			this.edit.forms.business._connection.active = 'no';
		}
		// eid - customer ID
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x10000) > 0 ) {
			this.edit.forms.person.name.fields.eid.active = 'yes';
			this.edit.forms.business.business.fields.eid.active = 'yes';
		} else {
			this.edit.forms.person.name.fields.eid.active = 'no';
			this.edit.forms.business.business.fields.eid.active = 'no';
		}
		// Tax Number
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x20000) > 0 ) {
			this.edit.forms.person.account.fields.tax_number.active = 'yes';
			this.edit.forms.business.account.fields.tax_number.active = 'yes';
			account = 'yes';
		} else {
			this.edit.forms.person.account.fields.tax_number.active = 'no';
			this.edit.forms.business.account.fields.tax_number.active = 'no';
		}
		// Rewards Level
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x80000) > 0 ) {
			this.edit.forms.person.account.fields.reward_level.active = 'yes';
			this.edit.forms.business.account.fields.reward_level.active = 'yes';
			account = 'yes';
		} else {
			this.edit.forms.person.account.fields.reward_level.active = 'no';
			this.edit.forms.business.account.fields.reward_level.active = 'no';
		}
		// Sales Total
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x100000) > 0 ) {
			this.edit.forms.person.account.fields.sales_total.active = 'yes';
			this.edit.forms.business.account.fields.sales_total.active = 'yes';
			this.edit.forms.person.account.fields.sales_total_prev.active = 'yes';
			this.edit.forms.business.account.fields.sales_total_prev.active = 'yes';
			account = 'yes';
		} else {
			this.edit.forms.person.account.fields.sales_total.active = 'no';
			this.edit.forms.business.account.fields.sales_total.active = 'no';
			this.edit.forms.person.account.fields.sales_total_prev.active = 'no';
			this.edit.forms.business.account.fields.sales_total_prev.active = 'no';
		}
		// Display the address phone number
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x01000000) > 0 ) {
			this.address.sections.address.fields.phone.active = 'yes';
			this.edit.forms.person.address.fields.phone.active = 'yes';
			this.edit.forms.business.address.fields.phone.active = 'yes';
		} else {
			this.address.sections.address.fields.phone.active = 'no';
			this.edit.forms.person.address.fields.phone.active = 'no';
			this.edit.forms.business.address.fields.phone.active = 'no';
		}
		if( M.curBusiness.customers != null && M.curBusiness.customers.settings != null ) {
			// Pricepoints
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x1000) > 0 
				&& M.curBusiness.customers.settings.pricepoints != null 
				) {
				this.edit.forms.person.account.fields.pricepoint_id.active = 'yes';
				this.edit.forms.business.account.fields.pricepoint_id.active = 'yes';
				account = 'yes';
				var pricepoints = {};
				var s_pp = M.curBusiness.customers.settings.pricepoints;
				pricepoints[0] = 'None';
				for(i in s_pp) {
					pricepoints[s_pp[i].pricepoint.id] = s_pp[i].pricepoint.name;
				}
				this.edit.forms.person.account.fields.pricepoint_id.options = pricepoints;
				this.edit.forms.business.account.fields.pricepoint_id.options = pricepoints;
			} else {
				this.edit.forms.person.account.fields.pricepoint_id.active = 'no';
				this.edit.forms.business.account.fields.pricepoint_id.active = 'no';
			}
			// Sales Reps 
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x2000) > 0 ) {
				this.edit.forms.person.account.fields.salesrep_id.active = 'yes';
				this.edit.forms.business.account.fields.salesrep_id.active = 'yes';
				account = 'yes';
			} else {
				this.edit.forms.person.account.fields.salesrep_id.active = 'no';
				this.edit.forms.business.account.fields.salesrep_id.active = 'no';
			}
			// Tax Locations
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x40000) > 0 
				&& M.curBusiness.taxes != null 
				&& M.curBusiness.taxes.settings != null
				&& M.curBusiness.taxes.settings.locations != null
				) {
				this.edit.forms.person.account.fields.tax_location_id.active = 'yes';
				this.edit.forms.business.account.fields.tax_location_id.active = 'yes';
				account = 'yes';
				var locations = {'0':'Use Shipping Address'};

				var locs = M.curBusiness.taxes.settings.locations;
				for(i in locs) {
					locations[locs[i].location.id] = locs[i].location.name + ' [' + (locs[i].location.rates!=null?locs[i].location.rates:'None') + ']';
				}
				this.edit.forms.person.account.fields.tax_location_id.options = locations;
				this.edit.forms.business.account.fields.tax_location_id.options = locations;
			} else {
				this.edit.forms.person.account.fields.tax_location_id.active = 'no';
				this.edit.forms.business.account.fields.tax_location_id.active = 'no';
			}
		} else {
			this.edit.sections = this.edit.forms.person;
			this.edit.forms.person.account.fields.pricepoint_id.active = 'no';
			this.edit.forms.business.account.fields.pricepoint_id.active = 'no';
			this.edit.forms.person.account.fields.tax_location_id.active = 'no';
			this.edit.forms.business.account.fields.tax_location_id.active = 'no';
			this.edit.forms.person.account.fields.salesrep_id.active = 'no';
			this.edit.forms.business.account.fields.salesrep_id.active = 'no';
		}
		this.edit.forms.person.account.active = account;
		this.edit.forms.business.account.active = account;

		if( args.next != null && args.next != '' ) {
			this.edit.nextFn = args.next;
			this.edit.forms.person._buttons.buttons.save.label = 'Next';
			this.edit.forms.business._buttons.buttons.save.label = 'Next';
			this.edit.rightbuttons.save.icon = 'next';
			this.edit.rightbuttons.save.label = 'Next';
		} else {
			this.edit.nextFn = null;
			this.edit.forms.person._buttons.buttons.save.label = 'Save';
			this.edit.forms.business._buttons.buttons.save.label = 'Save';
			this.edit.rightbuttons.save.icon = 'save';
			this.edit.rightbuttons.save.label = 'Save';
		}
		//
		// Set most things to off
		//
//		this.edit.forms.person.account.active = 'no';
//		this.edit.forms.business.account.active = 'no';
//		this.edit.forms.person._customer_categories.active = 'no';
//		this.edit.forms.business._customer_categories.active = 'no';
//		this.edit.forms.person._customer_tags.active = 'no';
//		this.edit.forms.business._customer_tags.active = 'no';
//		this.edit.forms.person._member_categories.active = 'no';
//		this.edit.forms.business._member_categories.active = 'no';
//		this.edit.forms.person._dealer_categories.active = 'no';
//		this.edit.forms.business._dealer_categories.active = 'no';
//		this.edit.forms.person._distributor_categories.active = 'no';
//		this.edit.forms.business._distributor_categories.active = 'no';
	    var membershipType = {};
        if( settings['membership-type-10-active'] == null || settings['membership-type-10-active'] == 'yes' ) {
            membershipType['10'] = 'Regular';
        }
        if( settings['membership-type-20-active'] != null && settings['membership-type-20-active'] == 'yes' ) {
            membershipType['20'] = 'Student';
        }
        if( settings['membership-type-30-active'] != null && settings['membership-type-30-active'] == 'yes' ) {
            membershipType['30'] = 'Individual';
        }
        if( settings['membership-type-40-active'] != null && settings['membership-type-40-active'] == 'yes' ) {
            membershipType['40'] = 'Family';
        }
        if( settings['membership-type-110-active'] == null || settings['membership-type-110-active'] == 'yes' ) {
            membershipType['110'] = 'Complimentary';
        }
        if( settings['membership-type-150-active'] == null || settings['membership-type-150-active'] == 'yes' ) {
            membershipType['150'] = 'Reciprocal';
        }
        this.edit.forms.person.membership.fields.membership_type.toggles = membershipType;
        this.edit.forms.business.membership.fields.membership_type.toggles = membershipType;
		//
		// Setup the member forms
		//
		if( args.member != null && args.member == 'yes' ) {
			this.edit.memberinfo = 'yes';
			this.edit.dealerinfo = 'no';
			this.edit.title = 'Member';
			if( M.curBusiness.customers != null 
				&& M.curBusiness.customers.settings['ui-labels-member'] != null 
				&& M.curBusiness.customers.settings['ui-labels-member'] != '' 
				) {
				this.edit.title = M.curBusiness.customers.settings['ui-labels-member'];
			}
			this.edit.distributorinfo = 'no';
			this.edit.forms.person.membership.active = 'yes';
			this.edit.forms.business.membership.active = 'yes';
//			if( (M.curBusiness.modules['ciniki.customers'].flags&0x04) > 0 ) {
//				this.edit.forms.person._member_categories.active = 'yes';
//				this.edit.forms.business._member_categories.active = 'yes';
//			}
			// Check if membership info collected
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x08) > 0 ) {
				this.edit.forms.person.membership.label = 'Membership';
				this.edit.forms.person.membership.fields.member_lastpaid.active = 'yes';
				this.edit.forms.person.membership.fields.membership_length.active = 'yes';
				this.edit.forms.person.membership.fields.membership_type.active = 'yes';
				this.edit.forms.business.membership.label = 'Membership';
				this.edit.forms.business.membership.fields.member_lastpaid.active = 'yes';
				this.edit.forms.business.membership.fields.membership_length.active = 'yes';
				this.edit.forms.business.membership.fields.membership_type.active = 'yes';
			} else {
				this.edit.forms.person.membership.label = 'Status';
				this.edit.forms.person.membership.fields.member_lastpaid.active = 'no';
				this.edit.forms.person.membership.fields.membership_length.active = 'no';
				this.edit.forms.person.membership.fields.membership_type.active = 'no';
				this.edit.forms.business.membership.label = 'Status';
				this.edit.forms.business.membership.fields.member_lastpaid.active = 'no';
				this.edit.forms.business.membership.fields.membership_length.active = 'no';
				this.edit.forms.business.membership.fields.membership_type.active = 'no';
			}
			this.edit.forms.person.dealer.active = 'no';
			this.edit.forms.business.dealer.active = 'no';
			this.edit.forms.person.distributor.active = 'no';
			this.edit.forms.business.distributor.active = 'no';
			this.edit.forms.person.address.fields.address_flags.flags = this.memberAddressFlags;
			this.edit.forms.business.address.fields.address_flags.flags = this.memberAddressFlags;
			this.edit.forms.person.email.fields.flags.flags = this.memberEmailFlags;
			this.edit.forms.business.email.fields.flags.flags = this.memberEmailFlags;
            this.edit.forms.person.phone.fields.phone_flags_1.active = 'yes';
            this.edit.forms.person.phone.fields.phone_flags_2.active = 'yes';
            this.edit.forms.person.phone.fields.phone_flags_3.active = 'yes';
            this.edit.forms.business.phone.fields.phone_flags_1.active = 'yes';
            this.edit.forms.business.phone.fields.phone_flags_2.active = 'yes';
            this.edit.forms.business.phone.fields.phone_flags_3.active = 'yes';
			this.address.sections.address.fields.flags.flags = this.memberAddressFlags;
			this.address.sections._latlong_buttons.active = 'no';
			this.address.sections._latlong.active = 'no';
			this.phone.sections._phone.fields.flags.active = 'yes';
			this.phone.sections._phone.fields.flags.flags = this.memberPhoneFlags;
			this.email.sections._email.fields.flags.flags = this.memberEmailFlags;
		} else if( args.dealer != null && args.dealer == 'yes' ) {
			this.edit.title = 'Dealer';
			if( M.curBusiness.customers != null 
				&& M.curBusiness.customers.settings['ui-labels-dealer'] != null 
				&& M.curBusiness.customers.settings['ui-labels-dealer'] != '' 
				) {
				this.edit.title = M.curBusiness.customers.settings['ui-labels-dealer'];
			}
			this.edit.memberinfo = 'no';
			this.edit.dealerinfo = 'yes';
			this.dealerinfo = 'yes';
			this.edit.distributorinfo = 'no';
//			this.edit.forms.person._image.active = 'yes';
//			this.edit.forms.business._image.active = 'yes';
//			this.edit.forms.person._image_caption.active = 'yes';
//			this.edit.forms.business._image_caption.active = 'yes';
//			this.edit.forms.person.account.active = 'no';
//			this.edit.forms.business.account.active = 'no';
			this.edit.forms.person.membership.active = 'no';
			this.edit.forms.business.membership.active = 'no';
			this.edit.forms.person.dealer.active = 'yes';
			this.edit.forms.business.dealer.active = 'yes';
//			if( (M.curBusiness.modules['ciniki.customers'].flags&0x20) > 0 ) {
//				this.edit.forms.person._dealer_categories.active = 'yes';
//				this.edit.forms.business._dealer_categories.active = 'yes';
//			}
			this.edit.forms.person.distributor.active = 'no';
			this.edit.forms.business.distributor.active = 'no';
			this.edit.forms.person.address.fields.address_flags.flags = this.memberAddressFlags;
			this.edit.forms.business.address.fields.address_flags.flags = this.memberAddressFlags;
			this.edit.forms.person.email.fields.flags.flags = this.memberEmailFlags;
			this.edit.forms.business.email.fields.flags.flags = this.memberEmailFlags;
			this.edit.forms.person.phone.fields.phone_flags_1.active = 'yes';
			this.edit.forms.person.phone.fields.phone_flags_2.active = 'yes';
			this.edit.forms.person.phone.fields.phone_flags_3.active = 'yes';
			this.edit.forms.business.phone.fields.phone_flags_1.active = 'yes';
			this.edit.forms.business.phone.fields.phone_flags_2.active = 'yes';
			this.edit.forms.business.phone.fields.phone_flags_3.active = 'yes';
			this.address.sections.address.fields.flags.flags = this.memberAddressFlags;
			this.address.sections._latlong_buttons.active = 'yes';
			this.address.sections._latlong.active = 'yes';
			this.phone.sections._phone.fields.flags.active = 'yes';
			this.phone.sections._phone.fields.flags.flags = this.memberPhoneFlags;
			this.email.sections._email.fields.flags.flags = this.memberEmailFlags;
		} else if( args.distributor != null && args.distributor == 'yes' ) {
			this.edit.title = 'Distributor';
			if( M.curBusiness.customers != null 
				&& M.curBusiness.customers.settings['ui-labels-distributor'] != null 
				&& M.curBusiness.customers.settings['ui-labels-distributor'] != '' 
				) {
				this.edit.title = M.curBusiness.customers.settings['ui-labels-distributor'];
			}
			this.edit.memberinfo = 'no';
			this.edit.dealerinfo = 'no';
			this.edit.distributorinfo = 'yes';
//			this.edit.forms.person._image.active = 'yes';
//			this.edit.forms.business._image.active = 'yes';
//			this.edit.forms.person._image_caption.active = 'yes';
//			this.edit.forms.business._image_caption.active = 'yes';
//			this.edit.forms.person.account.active = 'no';
//			this.edit.forms.business.account.active = 'no';
			this.edit.forms.person.membership.active = 'no';
			this.edit.forms.business.membership.active = 'no';
			this.edit.forms.person.dealer.active = 'no';
			this.edit.forms.business.dealer.active = 'no';
			this.edit.forms.person.distributor.active = 'yes';
			this.edit.forms.business.distributor.active = 'yes';
//			if( (M.curBusiness.modules['ciniki.customers'].flags&0x200) > 0 ) {
//				this.edit.forms.person._distributor_categories.active = 'yes';
//				this.edit.forms.business._distributor_categories.active = 'yes';
//			}
			this.edit.forms.person.address.fields.address_flags.flags = this.memberAddressFlags;
			this.edit.forms.business.address.fields.address_flags.flags = this.memberAddressFlags;
			this.edit.forms.person.email.fields.flags.flags = this.memberEmailFlags;
			this.edit.forms.business.email.fields.flags.flags = this.memberEmailFlags;
			this.edit.forms.person.phone.fields.phone_flags_1.active = 'yes';
			this.edit.forms.person.phone.fields.phone_flags_2.active = 'yes';
			this.edit.forms.person.phone.fields.phone_flags_3.active = 'yes';
			this.edit.forms.business.phone.fields.phone_flags_1.active = 'yes';
			this.edit.forms.business.phone.fields.phone_flags_2.active = 'yes';
			this.edit.forms.business.phone.fields.phone_flags_3.active = 'yes';
			this.address.sections.address.fields.flags.flags = this.memberAddressFlags;
			this.address.sections._latlong_buttons.active = 'yes';
			this.address.sections._latlong.active = 'yes';
			this.phone.sections._phone.fields.flags.active = 'yes';
			this.phone.sections._phone.fields.flags.flags = this.memberPhoneFlags;
			this.email.sections._email.fields.flags.flags = this.memberEmailFlags;
		} else {
			this.edit.title = 'Customer';
			if( M.curBusiness.customers != null 
				&& M.curBusiness.customers.settings['ui-labels-customer'] != null 
				&& M.curBusiness.customers.settings['ui-labels-customer'] != '' 
				) {
				this.edit.title = M.curBusiness.customers.settings['ui-labels-customer'];
			}
			if( settings != null && settings['ui-labels-parent'] != null ) {
				this.edit.forms.person.parent.fields.parent_id.label = settings['ui-labels-parent'];
				this.edit.forms.business.parent.fields.parent_id.label = settings['ui-labels-parent'];
			} else {
				this.edit.forms.person.parent.fields.parent_id.label = 'Parent';
				this.edit.forms.business.parent.fields.parent_id.label = 'Parent';
			}
			this.edit.memberinfo = 'no';
			this.edit.dealerinfo = 'no';
			this.edit.distributorinfo = 'no';
//			this.edit.forms.person._image.active = 'no';
//			this.edit.forms.business._image.active = 'no';
//			this.edit.forms.person._image_caption.active = 'no';
//			this.edit.forms.business._image_caption.active = 'no';
//			this.edit.forms.person.account.active = 'yes';
//			this.edit.forms.business.account.active = 'yes';
			this.edit.forms.person.membership.active = 'no';
			this.edit.forms.business.membership.active = 'no';
			this.edit.forms.person.dealer.active = 'no';
			this.edit.forms.business.dealer.active = 'no';
			this.edit.forms.person.distributor.active = 'no';
			this.edit.forms.business.distributor.active = 'no';
//			if( (M.curBusiness.modules['ciniki.customers'].flags&0x400000) > 0 ) {
//				this.edit.forms.person._customer_categories.active = 'no';
//				this.edit.forms.business._customer_categories.active = 'no';
//			}
//			if( (M.curBusiness.modules['ciniki.customers'].flags&0x800000) > 0 ) {
//				this.edit.forms.person._customer_categories.active = 'no';
//				this.edit.forms.business._customer_categories.active = 'no';
//			}
			this.edit.forms.person.address.fields.address_flags.flags = this.addressFlags;
			this.edit.forms.business.address.fields.address_flags.flags = this.addressFlags;
			this.edit.forms.person.email.fields.flags.flags = this.emailFlags;
			this.edit.forms.business.email.fields.flags.flags = this.emailFlags;
			this.edit.forms.person.phone.fields.phone_flags_1.active = 'no';
			this.edit.forms.person.phone.fields.phone_flags_2.active = 'no';
			this.edit.forms.person.phone.fields.phone_flags_3.active = 'no';
			this.edit.forms.business.phone.fields.phone_flags_1.active = 'no';
			this.edit.forms.business.phone.fields.phone_flags_2.active = 'no';
			this.edit.forms.business.phone.fields.phone_flags_3.active = 'no';
			this.address.sections.address.fields.flags.flags = this.addressFlags;
			this.address.sections._latlong_buttons.active = 'no';
			this.address.sections._latlong.active = 'no';
			this.phone.sections._phone.fields.flags.active = 'no';
			this.phone.sections._phone.fields.flags.flags = {};
			this.email.sections._email.fields.flags.flags = this.emailFlags;
		}

		// Season Memberships
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x02000000) > 0 
			&& M.curBusiness.modules['ciniki.customers'].settings != null
			&& M.curBusiness.modules['ciniki.customers'].settings['seasons'] != null
			&& this.edit.memberinfo == 'yes'
			) {
			this.edit.forms.person._seasons.active = 'yes';
			this.edit.forms.business._seasons.active = 'yes';
			this.edit.forms.person._seasons.fields = {};
			this.edit.forms.business._seasons.fields = {};
			this.edit.forms.person.membership.fields.member_lastpaid.active = 'no';
			this.edit.forms.business.membership.fields.member_lastpaid.active = 'no';
			for(i in M.curBusiness.modules['ciniki.customers'].settings.seasons) {
				var season = M.curBusiness.modules['ciniki.customers'].settings.seasons[i].season;
				if( season.open == 'yes' ) {
					this.edit.forms.person._seasons.fields['season-' + season.id + '-status'] = {
						'label':season.name, 'type':'toggle', 'default':'0', 'toggles':this.seasonStatus};
					this.edit.forms.person._seasons.fields['season-' + season.id + '-date_paid'] = {
						'label':'Paid', 'type':'date'};
					this.edit.forms.business._seasons.fields['season-' + season.id + '-status'] = {
						'label':season.name, 'type':'toggle', 'default':'0', 'toggles':this.seasonStatus};
					this.edit.forms.business._seasons.fields['season-' + season.id + '-date_paid'] = {
						'label':'Paid', 'type':'date'};
				}
			}
		} else {
			this.edit.forms.person._seasons.active = 'no';
			this.edit.forms.business._seasons.active = 'no';
		}

		if( args.edit_phone_id != null && args.edit_phone_id != '' 
			&& args.customer_id != null && args.customer_id > 0 ) {
			this.showPhoneEdit(cb, args.customer_id, args.edit_phone_id);
		}
		else if( args.edit_email_id != null && args.edit_email_id != '' 
			&& args.customer_id != null && args.customer_id > 0 ) {
			this.showEmailEdit(cb, args.customer_id, args.edit_email_id);
		}
		else if( args.edit_address_id != null && args.edit_address_id != '' 
			&& args.customer_id != null && args.customer_id > 0 ) {
			this.showAddressEdit(cb, args.customer_id, args.edit_address_id);
		}
		else if( args.edit_link_id != null && args.edit_link_id != '' 
			&& args.customer_id != null && args.customer_id > 0 ) {
			this.showLinkEdit(cb, args.customer_id, args.edit_link_id);
		} else {
			this.showEdit(cb, args.customer_id, args.category, (args.parent_id!=null?args.parent_id:0), args.parent_name);
		}

		return false;
	}

	this.showEdit = function(cb, cid, category, pid, pname) {
		if( pid != null ) { this.edit.parent_id = pid; }
		if( cid != null ) { this.edit.customer_id = cid; }
		this.edit.formtab = null;
		this.edit.formtab_field_id = null;
		this.edit.forms.person._buttons.buttons.delete.visible = 'no';
		this.edit.forms.business._buttons.buttons.delete.visible = 'no';
		this.edit.forms.person._customer_categories.active = 'no';
		this.edit.forms.business._customer_categories.active = 'no';
		this.edit.forms.person._customer_tags.active = 'no';
		this.edit.forms.business._customer_tags.active = 'no';
		this.edit.forms.person._member_categories.active = 'no';
		this.edit.forms.business._member_categories.active = 'no';
		this.edit.forms.person._dealer_categories.active = 'no';
		this.edit.forms.business._dealer_categories.active = 'no';
		this.edit.forms.person._distributor_categories.active = 'no';
		this.edit.forms.business._distributor_categories.active = 'no';
		this.edit.forms.person._customer_categories.fields.customer_categories.tags = [];
		this.edit.forms.business._customer_categories.fields.customer_categories.tags = [];
		this.edit.forms.person._customer_tags.fields.customer_tags.tags = [];
		this.edit.forms.business._customer_tags.fields.customer_tags.tags = [];
		this.edit.forms.person._member_categories.fields.member_categories.tags = [];
		this.edit.forms.business._member_categories.fields.member_categories.tags = [];
		this.edit.forms.person._dealer_categories.fields.dealer_categories.tags = [];
		this.edit.forms.business._dealer_categories.fields.dealer_categories.tags = [];
		this.edit.forms.person._distributor_categories.fields.distributor_categories.tags = [];
		this.edit.forms.business._distributor_categories.fields.distributor_categories.tags = [];

		if( this.edit.memberinfo == 'yes' ) {
			this.edit.forms.person._image.active = 'yes';
			this.edit.forms.business._image.active = 'yes';
			this.edit.forms.person._image_caption.active = 'yes';
			this.edit.forms.business._image_caption.active = 'yes';
			this.edit.forms.person.membership.active = 'yes';
			this.edit.forms.business.membership.active = 'yes';
			this.edit.forms.person._short_bio.active = 'yes';
			this.edit.forms.business._short_bio.active = 'yes';
			this.edit.forms.person._full_bio.active = 'yes';
			this.edit.forms.business._full_bio.active = 'yes';
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x04) > 0 ) {
				this.edit.forms.person._member_categories.active = 'yes';
				this.edit.forms.business._member_categories.active = 'yes';
			}
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x0113) == 0x02 // Member only
				&& this.edit.customer_id > 0 ) {
				this.edit.forms.person._buttons.buttons.delete.visible = 'yes';
				this.edit.forms.business._buttons.buttons.delete.visible = 'yes';
			}
		} else if( this.edit.dealerinfo == 'yes' ) {
			this.edit.forms.person._image.active = 'yes';
			this.edit.forms.business._image.active = 'yes';
			this.edit.forms.person._image_caption.active = 'yes';
			this.edit.forms.business._image_caption.active = 'yes';
			this.edit.forms.person.dealer.active = 'yes';
			this.edit.forms.business.dealer.active = 'yes';
			this.edit.forms.person._short_bio.active = 'yes';
			this.edit.forms.business._short_bio.active = 'yes';
			this.edit.forms.person._full_bio.active = 'yes';
			this.edit.forms.business._full_bio.active = 'yes';
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x20) > 0 ) {
				this.edit.forms.person._dealer_categories.active = 'yes';
				this.edit.forms.business._dealer_categories.active = 'yes';
			}
		} else if( this.edit.distributorinfo == 'yes' ) {
			this.edit.forms.person._image.active = 'yes';
			this.edit.forms.business._image.active = 'yes';
			this.edit.forms.person._image_caption.active = 'yes';
			this.edit.forms.business._image_caption.active = 'yes';
			this.edit.forms.person.distributor.active = 'yes';
			this.edit.forms.business.distributor.active = 'yes';
			this.edit.forms.person._short_bio.active = 'yes';
			this.edit.forms.business._short_bio.active = 'yes';
			this.edit.forms.person._full_bio.active = 'yes';
			this.edit.forms.business._full_bio.active = 'yes';
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x200) > 0 ) {
				this.edit.forms.person._distributor_categories.active = 'yes';
				this.edit.forms.business._distributor_categories.active = 'yes';
			}
		} else {
			this.edit.forms.person._image.active = 'no';
			this.edit.forms.business._image.active = 'no';
			this.edit.forms.person._image_caption.active = 'no';
			this.edit.forms.business._image_caption.active = 'no';
			this.edit.forms.person.membership.active = 'no';
			this.edit.forms.business.membership.active = 'no';
			this.edit.forms.person._short_bio.active = 'no';
			this.edit.forms.business._short_bio.active = 'no';
			this.edit.forms.person._full_bio.active = 'no';
			this.edit.forms.business._full_bio.active = 'no';
			if( this.edit.customer_id > 0 ) {
				this.edit.forms.person._buttons.buttons.delete.visible = 'yes';
				this.edit.forms.business._buttons.buttons.delete.visible = 'yes';
			}
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x400000) > 0 ) {
				this.edit.forms.person._customer_categories.active = 'yes';
				this.edit.forms.business._customer_categories.active = 'yes';
			}
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x800000) > 0 ) {
				this.edit.forms.person._customer_tags.active = 'yes';
				this.edit.forms.business._customer_tags.active = 'yes';
			}
		}

		if( this.edit.nextFn != null && this.edit.customer_id > 0 ) {
			this.edit.forms.person._buttons.buttons.delete.visible = 'no';
			this.edit.forms.business._buttons.buttons.delete.visible = 'no';
			this.edit.forms.person._buttons.buttons.remove.visible = 'yes';
			this.edit.forms.business._buttons.buttons.remove.visible = 'yes';
		} else {
			this.edit.forms.person._buttons.buttons.remove.visible = 'no';
			this.edit.forms.business._buttons.buttons.remove.visible = 'no';
		}

		if( this.edit.customer_id > 0 ) {
			// Edit existing customer
            if( (M.curBusiness.modules['ciniki.customers'].flags&0x10000000) > 0 ) {
                this.edit.forms.person.phones.active = 'yes';
                this.edit.forms.business.phones.active = 'yes';
                this.edit.forms.person.simplephone.active = 'no';
                this.edit.forms.business.simplephone.active = 'no';
            } else {
                this.edit.forms.person.phones.active = 'no';
                this.edit.forms.business.phones.active = 'no';
                this.edit.forms.person.simplephone.active = 'yes';
                this.edit.forms.business.simplephone.active = 'yes';
            }
            if( (M.curBusiness.modules['ciniki.customers'].flags&0x20000000) > 0 ) {
                this.edit.forms.person.emails.active = 'yes';
                this.edit.forms.business.emails.active = 'yes';
                this.edit.forms.person.simpleemail.active = 'no';
                this.edit.forms.business.simpleemail.active = 'no';
            } else {
                this.edit.forms.person.emails.active = 'no';
                this.edit.forms.business.emails.active = 'no';
                this.edit.forms.person.simpleemail.active = 'yes';
                this.edit.forms.business.simpleemail.active = 'yes';
            }
			this.edit.forms.person.email.active = 'no';
			this.edit.forms.person.address.active = 'no';
			this.edit.forms.person.phone.active = 'no';
			this.edit.forms.person.addresses.active = 'yes';
			this.edit.forms.person.links.active = 'yes';
			// Business form
			this.edit.forms.business.email.active = 'no';
			this.edit.forms.business.address.active = 'no';
			this.edit.forms.business.phone.active = 'no';
			this.edit.forms.business.addresses.active = 'yes';
			this.edit.forms.business.links.active = 'yes';
			var rsp = M.api.getJSONCb('ciniki.customers.getFull', {'business_id':M.curBusinessID, 
				'customer_id':this.edit.customer_id, 'tags':'yes', 'customer_categories':'yes', 
					'customer_tags':'yes', 'member_categories':'yes', 
					'dealer_categories':'yes', 'distributor_categories':'yes',
					'sales_reps':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_customers_edit.edit;
					p.data = rsp.customer;
					// Parent
					p.forms.person.parent.active = 'no';
					p.forms.business.parent.active = 'no';
					if( (M.curBusiness.modules['ciniki.customers'].flags&0x200000) > 0 ) {
						if( pid != null && (rsp.customer.parent == null || rsp.customer.parent.id == 0) ) {
							p.data.parent = {'id':pid, 'display_name':(pname!=null?unescape(pname):'')};
						}
						if( rsp.customer.num_children == null || rsp.customer.num_children == 0 || rsp.customer.parent_id > 0 ) {
							p.forms.person.parent.active = 'yes';
							p.forms.business.parent.active = 'yes';
						}
					}
					// Member Categories
//					if( (M.curBusiness.modules['ciniki.customers'].flags&0x04) > 0 && rsp.member_categories != null ) {
//						var tags = [];
//						for(i in rsp.member_categories) {
//							tags.push(rsp.member_categories[i].tag.name);
//						}
//						p.forms.person._member_categories.fields.member_categories.tags = tags;
//						p.forms.business._member_categories.fields.member_categories.tags = tags;
//					}
					for(i in rsp.tag_types) {
						var tags = [];
						for(j in rsp.tag_types[i].type.tags) {
							tags.push(rsp.tag_types[i].type.tags[j].tag.name);
						}
						if( rsp.tag_types[i].type.tag_type == 10 && p.forms.person._customer_categories.active == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x400000) > 0 ) {
							p.forms.person._customer_categories.fields.customer_categories.tags = tags;
							p.forms.business._customer_categories.fields.customer_categories.tags = tags;
						}
						else if( rsp.tag_types[i].type.tag_type == 20 && p.forms.person._customer_tags.active == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x800000) > 0 ) {
							p.forms.person._customer_tags.fields.customer_tags.tags = tags;
							p.forms.business._customer_tags.fields.customer_tags.tags = tags;
						}
						else if( rsp.tag_types[i].type.tag_type == 40 && p.memberinfo == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x04) > 0 ) {
							p.forms.person._member_categories.fields.member_categories.tags = tags;
							p.forms.business._member_categories.fields.member_categories.tags = tags;
						}
						else if( rsp.tag_types[i].type.tag_type == 60 && p.dealerinfo == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x20) > 0 ) {
							p.forms.person._dealer_categories.fields.dealer_categories.tags = tags;
							p.forms.business._dealer_categories.fields.dealer_categories.tags = tags;
						}
						else if( rsp.tag_types[i].type.tag_type == 80 && p.distributorinfo == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x0200) > 0 ) {
							p.forms.person._distributor_categories.fields.distributor_categories.tags = tags;
							p.forms.business._distributor_categories.fields.distributor_categories.tags = tags;
						}
					}
					// Sales Reps
					if( (M.curBusiness.modules['ciniki.customers'].flags&0x2000) > 0 ) {
						if( rsp.salesreps != null ) {
							p.forms.person.account.fields.salesrep_id.active = 'yes';
							p.forms.business.account.fields.salesrep_id.active = 'yes';
							var reps = {'0':'None'};
							for(i in rsp.salesreps) {
								reps[rsp.salesreps[i].user.id] = rsp.salesreps[i].user.name;
							}
							p.forms.person.account.fields.salesrep_id.options = reps;
							p.forms.business.account.fields.salesrep_id.options = reps;
						} else {
							p.forms.person.account.fields.salesrep_id.options = {'0':'None'};
							p.forms.business.account.fields.salesrep_id.options = {'0':'None'};
						}
					} else {
						p.forms.person.account.fields.salesrep_id.active = 'no';
						p.forms.business.account.fields.salesrep_id.active = 'no';
					}
//					M.ciniki_customers_edit.edit.data.emails = rsp.customer.emails;
//					M.ciniki_customers_edit.edit.data.addresses = rsp.customer.addresses;
//					if( rsp.customer.type == 0 || rsp.customer.type == 1 ) {
//						M.ciniki_customers_edit.edit.formtab = 'person';
//					}
					M.ciniki_customers_edit.showEditSubscriptions(cb);
				});
		} else {
			this.edit.data = {'status':'10', 'type':'1', 'flags':1, 'address_flags':15, 'phone_label_1':'Home', 'phone_label_2':'Work', 'phone_label_3':'Cell'};
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x200000) > 0 ) {
				this.edit.forms.person.parent.active = 'yes';
				this.edit.forms.business.parent.active = 'yes';
				if( pid != null ) {
					this.edit.data.parent = {'id':pid, 'display_name':(pname!=null?unescape(pname):'')};
				} else {
					this.edit.data.parent = {'id':'', 'display_name':''};
				}
			} else {
				this.edit.forms.person.parent.active = 'no';
				this.edit.forms.business.parent.active = 'no';
			}
			if( M.curBusiness.customers.settings != null 
				&& M.curBusiness.customers.settings['defaults-edit-form'] != null
				&& M.curBusiness.customers.settings['defaults-edit-form'] == 'business' ) {
				this.edit.data.type = 2;
			} else {
				this.edit.data.type = 1;
			}
			if( this.edit.memberinfo == 'yes' ) {
				this.edit.data.member_status = 10;
				this.edit.data.membership_length = 20;
				this.edit.data.membership_type = 10;
				if( category != null ) { this.edit.data.member_categories = category; }
			} else if( this.edit.dealerinfo == 'yes' ) {
				this.edit.data.dealer_status = 10;
				if( category != null ) { this.edit.data.dealer_categories = category; }
			} else if( this.edit.distributorinfo == 'yes' ) {
				this.edit.data.distributor_status = 10;
				if( category != null ) { this.edit.data.distributor_categories = category; }
			}
            if( (M.curBusiness.modules['ciniki.customers'].flags&0x10000000) > 0 ) {
                this.edit.forms.person.phone.active = 'yes';
                this.edit.forms.business.phone.active = 'yes';
                this.edit.forms.person.simplephone.active = 'no';
                this.edit.forms.business.simplephone.active = 'no';
            } else {
                this.edit.forms.person.phone.active = 'no';
                this.edit.forms.business.phone.active = 'no';
                this.edit.forms.person.simplephone.active = 'yes';
                this.edit.forms.business.simplephone.active = 'yes';
            }
            if( (M.curBusiness.modules['ciniki.customers'].flags&0x20000000) > 0 ) {
                this.edit.forms.person.email.active = 'yes';
                this.edit.forms.business.email.active = 'yes';
                this.edit.forms.person.simpleemail.active = 'no';
                this.edit.forms.business.simpleemail.active = 'no';
            } else {
                this.edit.forms.person.email.active = 'no';
                this.edit.forms.business.email.active = 'no';
                this.edit.forms.person.simpleemail.active = 'yes';
                this.edit.forms.business.simpleemail.active = 'yes';
            }
			this.edit.forms.person.address.active = 'yes';
			this.edit.forms.person.phones.active = 'no';
			this.edit.forms.person.emails.active = 'no';
			this.edit.forms.person.addresses.active = 'no';
			this.edit.forms.person.links.active = 'no';
			this.edit.forms.business.address.active = 'yes';
			this.edit.forms.business.phones.active = 'no';
			this.edit.forms.business.emails.active = 'no';
			this.edit.forms.business.addresses.active = 'no';
			this.edit.forms.business.links.active = 'no';
			this.edit.forms.person._customer_categories.fields.customer_categories.tags = {};
			if( (M.curBusiness.modules['ciniki.customers'].flags&0xC00224) > 0 ) {
				M.api.getJSONCb('ciniki.customers.tags', {'business_id':M.curBusinessID}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_customers_edit.edit;
					for(i in rsp.tag_types) {
						var tags = [];
						for(j in rsp.tag_types[i].type.tags) {
							tags.push(rsp.tag_types[i].type.tags[j].tag.name);
						}
						if( rsp.tag_types[i].type.tag_type == 10 && p.forms.person._customer_categories.active == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x400000) > 0 ) {
							p.forms.person._customer_categories.fields.customer_categories.tags = tags;
							p.forms.business._customer_categories.fields.customer_categories.tags = tags;
						}
						if( rsp.tag_types[i].type.tag_type == 20 && p.forms.person._customer_tags.active == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x800000) > 0 ) {
							p.forms.person._customer_tags.fields.customer_tags.tags = tags;
							p.forms.business._customer_tags.fields.customer_tags.tags = tags;
						}
						if( rsp.tag_types[i].type.tag_type == 40 && p.memberinfo == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x04) > 0 ) {
							p.forms.person._member_categories.fields.member_categories.tags = tags;
							p.forms.business._member_categories.fields.member_categories.tags = tags;
						}
						if( rsp.tag_types[i].type.tag_type == 60 && p.dealerinfo == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x20) > 0 ) {
							p.forms.person._dealer_categories.fields.dealer_categories.tags = tags;
							p.forms.business._dealer_categories.fields.dealer_categories.tags = tags;
						}
						if( rsp.tag_types[i].type.tag_type == 80 && p.distributorinfo == 'yes' && (M.curBusiness.modules['ciniki.customers'].flags&0x0200) > 0 ) {
							p.forms.person._distributor_categories.fields.distributor_categories.tags = tags;
							p.forms.business._distributor_categories.fields.distributor_categories.tags = tags;
						}
					}
					M.ciniki_customers_edit.showEditSubscriptions(cb);
				});
			} else {
				M.ciniki_customers_edit.showEditSubscriptions(cb);
			}
		}
	};

	this.updateEditPhones = function() {
		var rsp = M.api.getJSONCb('ciniki.customers.get', {'business_id':M.curBusinessID, 
			'customer_id':this.edit.customer_id, 'phones':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_customers_edit.edit.data.phones = rsp.customer.phones;
				M.ciniki_customers_edit.edit.refreshSection('phones');
				M.ciniki_customers_edit.edit.show();
			});
	};

	this.updateEditEmails = function() {
		var rsp = M.api.getJSONCb('ciniki.customers.get', {'business_id':M.curBusinessID, 
			'customer_id':this.edit.customer_id, 'emails':'yes'}, function(rsp) {
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
		var rsp = M.api.getJSONCb('ciniki.customers.get', {'business_id':M.curBusinessID, 
			'customer_id':this.edit.customer_id, 'addresses':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_customers_edit.edit.data.addresses = rsp.customer.addresses;
				M.ciniki_customers_edit.edit.refreshSection('addresses');
				M.ciniki_customers_edit.edit.show();
			});
	};

	this.updateEditLinks = function() {
		var rsp = M.api.getJSONCb('ciniki.customers.get', {'business_id':M.curBusinessID, 
			'customer_id':this.edit.customer_id, 'links':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_customers_edit.edit.data.links = rsp.customer.links;
				M.ciniki_customers_edit.edit.refreshSection('links');
				M.ciniki_customers_edit.edit.show();
			});
	};

	this.showEditSubscriptions = function(cb) {
		//
		// Get subscriptions available
		//
		if( M.curBusiness['modules']['ciniki.subscriptions'] != null ) {
			var rsp = M.api.getJSONCb('ciniki.subscriptions.subscriptionList', {'business_id':M.curBusinessID, 
				'customer_id':this.edit.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					// Reset any existing fields
					var p = M.ciniki_customers_edit.edit;
//					M.ciniki_customers_edit.edit.sections.subscriptions = {'label':'', 'fields':null};
					p.subscriptions = rsp.subscriptions;
					// Add subscriptions to the form
					if( rsp.subscriptions.length > 0 ) {
						p.forms.person.subscriptions.visible = 'yes';
						p.forms.business.subscriptions.visible = 'yes'; 
						p.forms.person.subscriptions.fields = {};
						var i = 0;
						for(i in rsp.subscriptions) {
							
							p.data['subscription_' + rsp.subscriptions[i].subscription.id] = rsp.subscriptions[i].subscription.status;
							p.forms.person.subscriptions.fields['subscription_' + rsp.subscriptions[i].subscription.id] = {'label':rsp.subscriptions[i].subscription.name, 
								'type':'toggle', 'toggles':M.ciniki_customers_edit.subscriptionOptions};
						}
						p.forms.business.subscriptions.fields = p.forms.person.subscriptions.fields;
					} else {
						// Hide the subscriptions section when no business subscription setup
						p.forms.person.subscriptions.visible = 'no';
						p.forms.business.subscriptions.visible = 'no';
					}
					p.refresh();
					p.show(cb);
					p.setupStatus();
				});
		} else {
			var p = M.ciniki_customers_edit.edit;
			p.subscriptions = null;
			p.forms.person.subscriptions.visible = 'no';
			p.forms.business.subscriptions.visible = 'no';
			p.refresh();
			p.show(cb);
			p.setupStatus();
		}
	};
	

	this.customerSave = function() {
		// Build a list of subscriptions subscribed or unsubscribed
		var unsubs = '';
		var subs = '';
		var sc = '';
		var uc = '';
		var type = 1;
		if( this.edit.formtab == 'business' ) {
			type = 2;
		}
		if( this.edit.subscriptions != null ) {
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
		}

		if( this.edit.customer_id > 0 ) {
			var c = this.edit.serializeFormSection('no', 'name')
				+ this.edit.serializeFormSection('no', 'business')
				+ this.edit.serializeFormSection('no', '_notes');
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x200000) > 0 
				&& this.edit.sections.parent.active == 'yes' 
				) {
                if( this.edit.parent_id > 0 ) {
                    c += this.edit.serializeFormSection('yes', 'parent');
                } else {
                    c += this.edit.serializeFormSection('no', 'parent');
                }
			}
			if( this.edit.sections._connection.active == 'yes' ) {
				c += this.edit.serializeFormSection('no', '_connection');
			}
			if( this.edit.sections.simplephone.active == 'yes' ) {
				c += this.edit.serializeFormSection('no', 'simplephone');
			}
			if( this.edit.sections.simpleemail.active == 'yes' ) {
				c += this.edit.serializeFormSection('no', 'simpleemail');
			}
			if( this.edit.memberinfo != null && this.edit.memberinfo == 'yes' ) {
				c += this.edit.serializeFormSection('no', '_image')
					+ this.edit.serializeFormSection('no', '_image_caption')
					+ this.edit.serializeFormSection('no', 'membership')
					+ this.edit.serializeFormSection('no', '_member_categories')
					+ this.edit.serializeFormSection('no', '_short_bio')
					+ this.edit.serializeFormSection('no', '_full_bio');
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x02000000) > 0 ) {
					c += this.edit.serializeFormSection('yes', '_seasons');
				}
			} else if( this.edit.dealerinfo != null && this.edit.dealerinfo == 'yes' ) {
				c += this.edit.serializeFormSection('no', '_image')
					+ this.edit.serializeFormSection('no', '_image_caption')
					+ this.edit.serializeFormSection('no', 'dealer')
					+ this.edit.serializeFormSection('no', '_dealer_categories')
					+ this.edit.serializeFormSection('no', '_short_bio')
					+ this.edit.serializeFormSection('no', '_full_bio');
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x200) > 0 ) {
					c += this.edit.serializeFormSection('no', '_dealer_categories');
				}
			} else if( this.edit.distributorinfo != null && this.edit.distributorinfo == 'yes' ) {
				c += this.edit.serializeFormSection('no', '_image')
					+ this.edit.serializeFormSection('no', '_image_caption')
					+ this.edit.serializeFormSection('no', 'distributor')
					+ this.edit.serializeFormSection('no', '_distributor_categories')
					+ this.edit.serializeFormSection('no', '_short_bio')
					+ this.edit.serializeFormSection('no', '_full_bio');
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x200) > 0 ) {
					c += this.edit.serializeFormSection('no', '_distributor_categories');
				}
			} else {
				c += this.edit.serializeFormSection('no', 'account');
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x400000) > 0 ) {
					c += this.edit.serializeFormSection('no', '_customer_categories');
				}
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x800000) > 0 ) {
					c += this.edit.serializeFormSection('no', '_customer_tags');
				}
			}
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
						M.ciniki_customers_edit.closeEdit(rsp);
					});
			} else {
				M.ciniki_customers_edit.closeEdit(null);
			}
		} else {
			var c = this.edit.serializeFormSection('yes', 'name')
				+ this.edit.serializeFormSection('yes', 'business')
				+ this.edit.serializeFormSection('yes', 'address')
				+ this.edit.serializeFormSection('yes', '_notes');
			if( (M.curBusiness.modules['ciniki.customers'].flags&0x200000) > 0 
				&& this.edit.sections.parent.active == 'yes' 
				) {
				c += this.edit.serializeFormSection('yes', 'parent');
			}
			if( this.edit.sections._connection.active == 'yes' ) {
				c += this.edit.serializeFormSection('yes', '_connection');
			}
			if( this.edit.sections.simplephone.active == 'yes' ) {
				c += this.edit.serializeFormSection('yes', 'simplephone');
			} else if( this.edit.sections.phone.active == 'yes' ) {
				c += this.edit.serializeFormSection('yes', 'phone');
            }
			if( this.edit.sections.simpleemail.active == 'yes' ) {
				c += this.edit.serializeFormSection('yes', 'simpleemail');
			} else if( this.edit.sections.email.active == 'yes' ) {
				c += this.edit.serializeFormSection('yes', 'email');
			}
			if( this.edit.memberinfo != null && this.edit.memberinfo == 'yes' ) {
				c += this.edit.serializeFormSection('yes', '_image')
					+ this.edit.serializeFormSection('yes', '_image_caption')
					+ this.edit.serializeFormSection('yes', 'membership')
					+ this.edit.serializeFormSection('yes', '_member_categories')
					+ this.edit.serializeFormSection('yes', '_short_bio')
					+ this.edit.serializeFormSection('yes', '_full_bio');
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x02000000) > 0 ) {
					c += this.edit.serializeFormSection('yes', '_seasons');
				}
			} else if( this.edit.dealerinfo != null && this.edit.dealerinfo == 'yes' ) {
				c += this.edit.serializeFormSection('yes', '_image')
					+ this.edit.serializeFormSection('yes', '_image_caption')
					+ this.edit.serializeFormSection('yes', 'dealer')
					+ this.edit.serializeFormSection('yes', '_dealer_categories')
					+ this.edit.serializeFormSection('yes', '_short_bio')
					+ this.edit.serializeFormSection('yes', '_full_bio');
			} else if( this.edit.distributorinfo != null && this.edit.distributorinfo == 'yes' ) {
				c += this.edit.serializeFormSection('yes', '_image')
					+ this.edit.serializeFormSection('yes', '_image_caption')
					+ this.edit.serializeFormSection('yes', 'distributor')
					+ this.edit.serializeFormSection('yes', '_distributor_categories')
					+ this.edit.serializeFormSection('yes', '_short_bio')
					+ this.edit.serializeFormSection('yes', '_full_bio');
			} else {
				c += this.edit.serializeFormSection('yes', 'account');
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x400000) > 0 ) {
					c += this.edit.serializeFormSection('yes', '_customer_categories');
				}
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x800000) > 0 ) {
					c += this.edit.serializeFormSection('yes', '_customer_tags');
				}
			}
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
					M.ciniki_customers_edit.closeEdit(rsp);
			});
		}
	};

	this.closeEdit = function(rsp) {
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

	this.deleteCustomer = function() {
		if( this.edit.customer_id > 0 ) {
			if( confirm("Are you sure you want to remove this customer?  This will remove all subscriptions, phone numbers, email addresses, addresses and websites.") ) {
				M.api.getJSONCb('ciniki.customers.delete', {'business_id':M.curBusinessID, 
					'customer_id':this.edit.customer_id}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						if( M.ciniki_customers_edit.edit.cb.match(/ciniki_customers_members/) ) {
							M.ciniki_customers_edit.edit.destroy();
							M.ciniki_customers_members.member.close();
						} else {
							M.ciniki_customers_edit.edit.destroy();
							M.ciniki_customers_main.customer.close();
						}
					});
			}
		}
	}

	this.removeCustomer = function() {
		if( M.ciniki_customers_edit.edit.nextFn != null ) {
			// Check if we should pass customer id to next panel
			eval(M.ciniki_customers_edit.edit.nextFn + '(0);');
		} else {
			M.ciniki_customers_edit.edit.close();
		}
	}

	this.showAddressEdit = function(cb, cid, aid) {
		if( cid != null ) { this.address.customer_id = cid; }
		if( aid != null ) { this.address.address_id = aid; }

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
		if( cid != null ) { this.email.customer_id = cid; }
		if( eid != null ) { this.email.email_id = eid; }
		if( this.email.email_id > 0 ) {
			this.email.sections._buttons.buttons.delete.visible = 'yes';
			this.email.sections._buttons.buttons.password.visible = 'yes';
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
			this.email.sections._buttons.buttons.password.visible = 'no';
			this.email.refresh();
			this.email.show(cb);
		}
	};

	this.saveEmail = function() {
		// Check if email address exists already
		var e = this.email.formFieldValue(this.email.sections._email.fields.address, 'address');
		if( e == '' ) {
			alert("Invalid email address");
			return false;
		}
		// Check if email address changed
//		if( e != this.email.fieldValue('emails', 'address', this.email.sections._email.fields.address) ) {
//			var rsp = M.api.getJSONCb('ciniki.customers.emailSearch', {'business_id':M.curBusinessID, 
//				'customer_id':M.ciniki_customers_edit.email.customer_id, 'email':e}, function(rsp) {
//					if( rsp.stat != 'ok' ) {
//						M.api.err(rsp);
//						return false;
//					} 
//					if( rsp.email != null ) {
//						alert("Email address already exists");
//						return false;
//					}
//					M.ciniki_customers_edit.saveEmailFinish();
//				});
//		} else {
			this.saveEmailFinish();
//		}
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

	this.setPassword = function() {
		var np = prompt("Please enter a new password for the customer: ");
		if( np != null ) {
			if( np.length < 8 ) {
				alert("The password must be a minimum of 8 characters long");
				return false;
			}
			else {
				M.api.postJSONCb('ciniki.customers.customerSetPassword',
					{'business_id':M.curBusinessID, 'customer_id':this.email.customer_id,
						'email_id':this.email.email_id}, 'newpassword=' + encodeURIComponent(np), 
					function(rsp) {
						if( rsp.stat != 'ok' ) {	
							M.api.err(rsp);
							return false;
						}
						alert("Password has been set");
					});
			}
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

	this.showPhoneEdit = function(cb, cid, pid) {
		if( cid != null ) { this.phone.customer_id = cid; }
		if( pid != null ) { this.phone.phone_id = pid; }
		if( this.phone.phone_id > 0 ) {
			this.phone.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.customers.phoneGet', 
				{'business_id':M.curBusinessID, 'customer_id':this.phone.customer_id, 
				'phone_id':this.phone.phone_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.phone.data = rsp.phone;
					M.ciniki_customers_edit.phone.refresh();
					M.ciniki_customers_edit.phone.show(cb);
				});
		} else {
			this.phone.data = {'flags':1};
			this.phone.sections._buttons.buttons.delete.visible = 'no';
			this.phone.refresh();
			this.phone.show(cb);
		}
	};

	this.savePhone = function() {
		if( this.phone.phone_id > 0 ) {
			var c = this.phone.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.customers.phoneUpdate', 
					{'business_id':M.curBusinessID, 
					'customer_id':M.ciniki_customers_edit.phone.customer_id,
					'phone_id':M.ciniki_customers_edit.phone.phone_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_customers_edit.phone.close();
					});
			} else {
				M.ciniki_customers_edit.phone.close();
			}
		} else {
			var c = this.phone.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.customers.phoneAdd', 
				{'business_id':M.curBusinessID, 
				'customer_id':M.ciniki_customers_edit.phone.customer_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_customers_edit.phone.close();
				});
		}
	};

	this.deletePhone = function(customerID, pid) {
		if( confirm("Are you sure you want to remove this phone number?") ) {
			var rsp = M.api.getJSONCb('ciniki.customers.phoneDelete', 
				{'business_id':M.curBusinessID, 'customer_id':this.phone.customer_id, 
				'phone_id':this.phone.phone_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.phone.close();
				});
		}
	};

	this.showLinkEdit = function(cb, cid, eid) {
		if( cid != null ) { this.link.customer_id = cid; }
		if( eid != null ) { this.link.link_id = eid; }
		if( this.link.link_id > 0 ) {
			this.link.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.customers.linkGet', 
				{'business_id':M.curBusinessID, 'customer_id':this.link.customer_id, 
				'link_id':this.link.link_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.link.data = rsp.link;
					M.ciniki_customers_edit.link.refresh();
					M.ciniki_customers_edit.link.show(cb);
				});
		} else {
			this.link.data = {'flags':1};
			this.link.sections._buttons.buttons.delete.visible = 'no';
			this.link.refresh();
			this.link.show(cb);
		}
	};

	this.saveLink = function() {
		if( this.link.link_id > 0 ) {
			var c = this.link.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.customers.linkUpdate', 
					{'business_id':M.curBusinessID, 
					'customer_id':M.ciniki_customers_edit.link.customer_id,
					'link_id':M.ciniki_customers_edit.link.link_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_customers_edit.link.close();
					});
			} else {
				M.ciniki_customers_edit.link.close();
			}
		} else {
			var c = this.link.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.customers.linkAdd', 
				{'business_id':M.curBusinessID, 
				'customer_id':M.ciniki_customers_edit.link.customer_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_customers_edit.link.close();
				});
		}
	};

	this.deleteLink = function(customerID, linkID) {
		if( confirm("Are you sure you want to remove this link?") ) {
			var rsp = M.api.getJSONCb('ciniki.customers.linkDelete', 
				{'business_id':M.curBusinessID, 'customer_id':this.link.customer_id, 
				'link_id':this.link.link_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_edit.link.close();
				});
		}
	};

	this.lookupLatLong = function() {
		M.startLoad();
		if( document.getElementById('googlemaps_js') == null) {
			var script = document.createElement("script");
			script.id = 'googlemaps_js';
			script.type = "text/javascript";
			script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curBusiness.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_customers_edit.lookupGoogleLatLong";
			document.body.appendChild(script);
		} else {
			this.lookupGoogleLatLong();
		}
	};

	this.lookupGoogleLatLong = function() {
		var address = M.ciniki_customers_edit.address.formFieldValue(M.ciniki_customers_edit.address.sections.address.fields.address1, 'address1')
			+ ', ' + M.ciniki_customers_edit.address.formFieldValue(M.ciniki_customers_edit.address.sections.address.fields.address2, 'address2')
			+ ', ' + M.ciniki_customers_edit.address.formFieldValue(M.ciniki_customers_edit.address.sections.address.fields.city, 'city')
			+ ', ' + M.ciniki_customers_edit.address.formFieldValue(M.ciniki_customers_edit.address.sections.address.fields.province, 'province')
			+ ', ' + M.ciniki_customers_edit.address.formFieldValue(M.ciniki_customers_edit.address.sections.address.fields.country, 'country');
		var geocoder = new google.maps.Geocoder();
		geocoder.geocode( { 'address': address}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				M.ciniki_customers_edit.address.setFieldValue('latitude', results[0].geometry.location.lat());
				M.ciniki_customers_edit.address.setFieldValue('longitude', results[0].geometry.location.lng());
			} else {
				alert('Geocode was not successful for the following reason: ' + status);
			}
		});	
		M.stopLoad();
	};
}
