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
    this.memberStatus = {'10':'Active', '60':'Inactive'};
    this.membershipLength = {'20':'Yearly', '60':'Lifetime'};
    this.membershipType = {'10':'Regular', '110':'Complimentary', '150':'Reciprocal'};
    this.memberWebFlags = {'1':{'name':'Visible'}};
    this.dealerStatus = {'5':'Prospect', '10':'Active', '40':'Previous', '60':'Closed'};
    this.distributorStatus = {'5':'Prospect', '10':'Active', '40':'Previous', '60':'Closed'};
    this.seasonStatus = {'0':'Unknown', '10':'Active', '60':'Inactive'};
    this.emailFlags = {};
    this.phoneFlags = {};
    this.linkFlags = {};
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

    //
    // The add/edit form
    //
    this.edit = new M.panel('Contact',
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
            'eid':{'label':'External ID', 'type':'text', 'active':'no', 'livesearch':'yes'},
            'callsign':{'label':'Callsign', 'type':'text', 'size':'small',
                'visible':function() { return M.modFlagSet('ciniki.customers', 0x0400); },
                },
            'prefix':{'label':'Title', 'type':'text', 'hint':'Mr., Ms., Dr., ...'},
            'first':{'label':'First', 'type':'text', 'livesearch':'yes',},
            'middle':{'label':'Middle', 'type':'text'},
            'last':{'label':'Last', 'type':'text', 'livesearch':'yes',},
            'suffix':{'label':'Degrees', 'type':'text', 'hint':'Ph.D, M.D., Jr., ...'},
            'birthdate':{'label':'Birthday', 'active':'no', 'type':'date'},
            'language':{'label':'Language', 'active':'no', 'type':'text'},
        }},
        'account':{'label':'', 'aside':'yes', 'fields':{
            'tax_number':{'label':'Tax Number', 'active':'no', 'type':'text'},
            'tax_location_id':{'label':'Tax Location', 'active':'no', 'type':'select', 'options':{}},
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
            'member_lastpaid':{'label':'Last Paid', 'active':'no', 'type':'date', 'size':'medium'},
            'member_expires':{'label':'Expires', 'active':'no', 'type':'date', 'size':'medium'},
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
            'webflags_2':{'label':'Website', 'type':'flagtoggle', 'bit':0x02, 'field':'webflags', 'default':'off'},
            }},
        '_dealer_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
            'dealer_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
            }},
        'distributor':{'label':'Distributor', 'aside':'yes', 'active':'no', 'fields':{
            'distributor_status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.distributorStatus},
            'webflags_3':{'label':'Website', 'type':'flagtoggle', 'bit':0x04, 'field':'webflags', 'default':'off'},
//              'webflags_3':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.distributorWebFlags},
            }},
        '_distributor_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
            'distributor_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
            }},
        'business':{'label':'Business', 'aside':'yes', 'fields':{
            'company':{'label':'Company', 'type':'text', 'livesearch':'yes'},
            'department':{'label':'Department', 'type':'text'},
            'title':{'label':'Title', 'type':'text'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'contact', 'tabs':{
            'contact':{'label':'Contact', 'fn':'M.ciniki_customers_edit.edit.switchTab("contact");'},
            'subscriptions':{'label':'Subscriptions', 'fn':'M.ciniki_customers_edit.edit.switchTab("subscriptions");'},
            'website':{'label':'Website', 
                'visible':function() { return M.modFlagAny('ciniki.customers', 0x0112); },
                'fn':'M.ciniki_customers_edit.edit.switchTab("website");'},
            'notes':{'label':'Notes', 'fn':'M.ciniki_customers_edit.edit.switchTab("notes");'},
            }},
        'simplephone':{'label':'Phone Numbers', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'fields':{
                'phone_home':{'label':'Home', 'type':'text'},
                'phone_work':{'label':'Work', 'type':'text'},
                'phone_cell':{'label':'Cell', 'type':'text'},
                'phone_fax':{'label':'Fax', 'type':'text'},
            }},
        '_phone':{'label':'Phone Numbers', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'fields':{
                'phone_label_1':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
                'phone_number_1':{'label':'Number', 'type':'text', 'size':'medium'},
                'phone_flags_1':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
                'phone_label_2':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
                'phone_number_2':{'label':'Number', 'type':'text', 'size':'medium'},
                'phone_flags_2':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
                'phone_label_3':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
                'phone_number_3':{'label':'Number', 'type':'text', 'size':'medium'},
                'phone_flags_3':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
            }},
        'phones':{'label':'Phones', 'active':'no', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No phones',
            'addTxt':'Add Phone',
            'addTopFn':'M.ciniki_customers_edit.showPhoneEdit(\'M.ciniki_customers_edit.updateEditPhones();\',M.ciniki_customers_edit.edit.customer_id,0);',
            },
        'email':{'label':'Email', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'fields':{
                'email_address':{'label':'Primary', 'type':'text'},
                'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.emailFlags},
            }},
        'emails':{'label':'Emails', 'active':'no', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No emails',
            'addTxt':'Add Email',
            'addTopFn':'M.ciniki_customers_edit.showEmailEdit(\'M.ciniki_customers_edit.updateEditEmails();\',M.ciniki_customers_edit.edit.customer_id,0);',
            },
        'address':{'label':'Address', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'fields':{
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
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No addresses',
            'addTxt':'Add Address',
            'addTopFn':'M.ciniki_customers_edit.showAddressEdit(\'M.ciniki_customers_edit.updateEditAddresses();\',M.ciniki_customers_edit.edit.customer_id,0);',
            },
        'links':{'label':'Links', 'active':'no', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['multiline', ''],
            'noData':'No links',
            'addTxt':'Add Link',
            'addTopFn':'M.ciniki_customers_edit.showLinkEdit(\'M.ciniki_customers_edit.updateEditLinks();\',M.ciniki_customers_edit.edit.customer_id,0);',
            },
        'subscriptions':{'label':'Subscriptions', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'subscriptions' ? 'yes' : 'hidden'); },
            'fields':{},
            },
        '_image':{'label':'Website Details', 'type':'imageform', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_customers_edit.edit.setFieldValue('primary_image_id', iid, null, null);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                            M.ciniki_customers_edit.edit.setFieldValue('primary_image_id', 0, null, null);
                            return true;
                        },
                    },
            }},
        '_image_caption':{'label':'', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'fields':{
                'primary_image_caption':{'label':'Caption', 'type':'text'},
            }},
        '_image_intro':{'label':'Intro Image', 'type':'imageform', 
            'visible':function() { return ((M.modSetting('ciniki.customers', 'intro-photo') == 'yes' && M.ciniki_customers_edit.edit.sections._tabs.selected == 'website') ? 'yes' : 'hidden'); },
            'fields':{
                'intro_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_customers_edit.edit.setFieldValue('intro_image_id', iid, null, null);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                            M.ciniki_customers_edit.edit.setFieldValue('intro_image_id', 0, null, null);
                            return true;
                        },
                    },
            }},
        '_image_intro_caption':{'label':'', 'active':'no', 
            'visible':function() { return ((M.modSetting('ciniki.customers', 'intro-photo') == 'yes' && M.ciniki_customers_edit.edit.sections._tabs.selected == 'website') ? 'yes' : 'hidden'); },
            'fields':{
                'intro_image_caption':{'label':'Caption', 'type':'text'},
            }},
        '_short_bio':{'label':'Synopsis', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'fields':{
                'short_bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_full_bio':{'label':'Biography', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'fields':{
                'full_bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        'images':{'label':'Gallery', 'type':'simplethumbs',
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            },
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'addTxt':'Add Image',
            'addFn':'M.ciniki_customers_edit.edit.saveFirst("M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_edit.edit.refreshImages();\',\'mc\',{\'customer_id\':M.ciniki_customers_edit.edit.customer_id,\'add\':\'yes\'});");'
            },
        '_notes':{'label':'Notes', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'notes' ? 'yes' : 'hidden'); },
            'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_customers_edit.customerSave();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_customers_edit.deleteCustomer();'},
            'remove':{'label':'Remove', 'fn':'M.ciniki_customers_edit.removeCustomer();'},  // Used when linked with next button.
            }},
        };
    this.edit.forms.business = {
        'parent':{'label':'', 'active':'no', 'aside':'yes', 'fields':{
            'parent_id':{'label':'Parent', 'type':'fkid', 'livesearch':'yes'},
            }},
        'business':{'label':'Business', 'aside':'yes', 'fields':{
            'status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.customerStatus},
            'eid':{'label':'External ID', 'type':'text', 'active':'no', 'livesearch':'yes'},
            'company':{'label':'Name', 'type':'text', 'livesearch':'yes'},
            'display_name_format':{'label':'Display', 'type':'select', 'options':this.displayNameFormatOptions},
            }},
        'account':{'label':'', 'aside':'yes', 'fields':{
            'tax_number':{'label':'Tax Number', 'active':'no', 'type':'text'},
            'tax_location_id':{'label':'Tax Location', 'active':'no', 'type':'select', 'options':{}},
            'start_date':{'label':'Start Date', 'active':'yes', 'type':'date'},
            }},
        'name':{'label':'Contact Person', 'aside':'yes', 'fields':{
            'callsign':{'label':'Callsign', 'type':'text', 'size':'small',
                'visible':function() { return M.modFlagSet('ciniki.customers', 0x0400); },
                },
            'prefix':{'label':'Title', 'type':'text', 'hint':'Mr., Ms., Dr., ...'},
            'first':{'label':'First', 'type':'text', 'livesearch':'yes'},
            'middle':{'label':'Middle', 'type':'text'},
            'last':{'label':'Last', 'type':'text', 'livesearch':'yes'},
            'suffix':{'label':'Degrees', 'type':'text', 'hint':'Ph.D, M.D., Jr., ...'},
            'department':{'label':'Department', 'type':'text'},
            'title':{'label':'Title', 'type':'text'},
            'birthdate':{'label':'Birthday', 'active':'no', 'type':'date'},
            'language':{'label':'Language', 'active':'no', 'type':'text'},
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
            'member_lastpaid':{'label':'Last Paid', 'active':'no', 'type':'date', 'size':'medium'},
            'member_expires':{'label':'Expires', 'active':'no', 'type':'date', 'size':'medium'},
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
            'webflags_2':{'label':'Website', 'type':'flagtoggle', 'bit':0x02, 'field':'webflags', 'default':'off'},
//              'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.dealerWebFlags},
            }},
        '_dealer_categories':{'label':'Dealer Categories', 'aside':'yes', 'active':'no', 'fields':{
            'dealer_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
            }},
        'distributor':{'label':'Distributor', 'aside':'yes', 'active':'no', 'fields':{
            'distributor_status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.distributorStatus},
            'webflags_3':{'label':'Website', 'type':'flagtoggle', 'bit':0x04, 'field':'webflags', 'default':'off'},
//              'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.distributorWebFlags},
            }},
        '_distributor_categories':{'label':'Distributor Categories', 'aside':'yes', 'active':'no', 'fields':{
            'distributor_categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'contact', 'tabs':{
            'contact':{'label':'Contact', 'fn':'M.ciniki_customers_edit.edit.switchTab("contact");'},
            'subscriptions':{'label':'Subscriptions', 'fn':'M.ciniki_customers_edit.edit.switchTab("subscriptions");'},
            'website':{'label':'Website', 
                'visible':function() { return M.modFlagAny('ciniki.customers', 0x0112); },
                'fn':'M.ciniki_customers_edit.edit.switchTab("website");'},
            'notes':{'label':'Notes', 'fn':'M.ciniki_customers_edit.edit.switchTab("notes");'},
            }},
        'simplephone':{'label':'Phone Numbers', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'fields':{
                'phone_home':{'label':'Home', 'type':'text'},
                'phone_work':{'label':'Work', 'type':'text'},
                'phone_cell':{'label':'Cell', 'type':'text'},
                'phone_fax':{'label':'Fax', 'type':'text'},
            }},
        '_phone':{'label':'Phone Numbers', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'fields':{
                'phone_label_1':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
                'phone_number_1':{'label':'Number', 'type':'text', 'size':'medium'},
                'phone_flags_1':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
                'phone_label_2':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
                'phone_number_2':{'label':'Number', 'type':'text', 'size':'medium'},
                'phone_flags_2':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
                'phone_label_3':{'label':'Type', 'type':'text', 'hint':'Home, Work, Cell', 'size':'medium', 'livesearch':'yes', 'livesearchempty':'yes'},
                'phone_number_3':{'label':'Number', 'type':'text', 'size':'medium'},
                'phone_flags_3':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
            }},
        'phones':{'label':'Phones', 'active':'no', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No phones',
            'addTxt':'Add Phone',
            'addFn':'M.ciniki_customers_edit.showPhoneEdit(\'M.ciniki_customers_edit.updateEditPhones();\',M.ciniki_customers_edit.edit.customer_id,0);',
            },
        'email':{'label':'Email', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'fields':{
                'email_address':{'label':'Primary', 'type':'text'},
                'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.emailFlags},
            }},
        'emails':{'label':'Emails', 'active':'no', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No emails',
            'addTxt':'Add Email',
            'addFn':'M.ciniki_customers_edit.showEmailEdit(\'M.ciniki_customers_edit.updateEditEmails();\',M.ciniki_customers_edit.edit.customer_id,0);',
            },
        'address':{'label':'Address', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'fields':{
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
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No addresses',
            'addTxt':'Add Address',
            'addFn':'M.ciniki_customers_edit.showAddressEdit(\'M.ciniki_customers_edit.updateEditAddresses();\',M.ciniki_customers_edit.edit.customer_id,0);',
            },
        'links':{'label':'Links', 'active':'no', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'contact' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['multiline', ''],
            'noData':'No links',
            'addTxt':'Add Link',
            'addFn':'M.ciniki_customers_edit.showLinkEdit(\'M.ciniki_customers_edit.updateEditLinks();\',M.ciniki_customers_edit.edit.customer_id,0);',
            },
        'subscriptions':{'label':'Subscriptions',
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'subscriptions' ? 'yes' : 'hidden'); },
            'fields':{},
            },
        '_image':{'label':'Website Details', 'type':'imageform', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_customers_edit.edit.setFieldValue('primary_image_id', iid, null, null);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                            M.ciniki_customers_edit.edit.setFieldValue(fid, 0, null, null);
                            return true;
                        },
                    },
            }},
        '_image_caption':{'label':'', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'fields':{
                'primary_image_caption':{'label':'Caption', 'type':'text'},
            }},
        '_image_intro':{'label':'Intro Image', 'type':'imageform', 
            'visible':function() { return ((M.modSetting('ciniki.customers', 'intro-photo') == 'yes' && M.ciniki_customers_edit.edit.sections._tabs.selected == 'website') ? 'yes' : 'hidden'); },
            'fields':{
                'intro_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_customers_edit.edit.setFieldValue('intro_image_id', iid, null, null);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                            M.ciniki_customers_edit.edit.setFieldValue('intro_image_id', 0, null, null);
                            return true;
                        },
                    },
            }},
        '_image_intro_caption':{'label':'', 'active':'no', 
            'visible':function() { return ((M.modSetting('ciniki.customers', 'intro-photo') == 'yes' && M.ciniki_customers_edit.edit.sections._tabs.selected == 'website') ? 'yes' : 'hidden'); },
            'fields':{
                'intro_image_caption':{'label':'Caption', 'type':'text'},
            }},
        '_short_bio':{'label':'Synopsis', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'fields':{
                'short_bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_full_bio':{'label':'Biography', 'active':'no', 
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'fields':{
                'full_bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        'images':{'label':'Gallery', 'type':'simplethumbs',
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            },
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'); },
            'addTxt':'Add Image',
            'addFn':'M.ciniki_customers_edit.edit.saveFirst("M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_edit.edit.refreshImages();\',\'mc\',{\'customer_id\':M.ciniki_customers_edit.edit.customer_id,\'add\':\'yes\'});");'
            },
        '_notes':{'label':'Notes', 'active':'yes',
            'visible':function() { return (M.ciniki_customers_edit.edit.sections._tabs.selected == 'notes' ? 'yes' : 'hidden'); },
            'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_customers_edit.customerSave();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_customers_edit.deleteCustomer();'},
            'remove':{'label':'Remove', 'fn':'M.ciniki_customers_edit.removeCustomer();'},  // Used when linked with next button.
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
                case 1: return d.phone.phone_number + ((d.phone.flags&0x08)>0?' <span class="subdue">(Public)</span>':'');
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
                return '<span class="maintext">' + d.link.name + ((d.link.webflags&0x01)>0?' <span class="subdue">(Public)</span>':'') + '</span><span class="subtext">' + M.hyperlink(d.link.url) + '</span>';
            } else {
                return M.hyperlink(d.link.url) + ((d.link.webflags&0x01)>0?' <span class="subdue">(Public)</span>':'');
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
    this.edit.thumbFn = function(s, i, d) {
        return 'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_edit.edit.refreshImages();\',\'mc\',{\'customer_image_id\':\'' + d.image.id + '\'});';
    };
    this.edit.fieldValue = function(s, i, d) { 
        if( i == 'parent_id_fkidstr' ) { return ((this.data.parent!=null&&this.data.parent.display_name!=null)?this.data.parent.display_name:''); }
        if( i == 'parent_id' ) { return ((this.data.parent!=null&&this.data.parent.id!=null)?this.data.parent.id:0); }
        return this.data[i]; 
    };
    this.edit.fieldHistoryArgs = function(s, i) {
        if( i.substring(0,13) == 'subscription_' ) {
            return {'method':'ciniki.subscriptions.getCustomerHistory', 'args':{'tnid':M.curTenantID, 
                'subscription_id':i.substring(13), 'customer_id':this.customer_id, 'field':'status'}};
        } else {
            return {'method':'ciniki.customers.getHistory', 'args':{'tnid':M.curTenantID, 
                'customer_id':this.customer_id, 'field':i}};
        }
    };
    this.edit.liveSearchCb = function(s, i, value) {
        if( i == 'parent_id' ) {
            M.api.getJSONBgCb('ciniki.customers.searchQuick', 
                {'tnid':M.curTenantID, 'start_needle':value, 'limit':25}, function(rsp) { 
                    M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp['customers']); 
                });
        } else if( i == 'city' ) {
            M.api.getJSONBgCb('ciniki.customers.addressSearchQuick', 
                {'tnid':M.curTenantID, 'start_needle':value, 'limit':25}, function(rsp) { 
                    M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp['cities']); 
                });
        } else if( i == 'eid' || i == 'first' || i == 'last' || i == 'company' ) {
            M.api.getJSONBgCb('ciniki.customers.customerSearch', 
                {'tnid':M.curTenantID, 'start_needle':value, 'field':i, 'limit':25}, function(rsp) { 
                    M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp.customers); 
                });
        } else if( i == 'phone_label_1' || i == 'phone_label_2' || i == 'phone_label_3' ) {
            M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), ['Home','Work','Cell','Fax']);
        } else if( i == 'connection' ) {
            M.api.getJSONBgCb('ciniki.customers.connectionSearch', 
                {'tnid':M.curTenantID, 'start_needle':value, 'field':i, 'limit':25}, function(rsp) { 
                    M.ciniki_customers_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_edit.edit.panelUID + '_' + i), rsp.connections); 
                });
        }
    };
    this.edit.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'parent_id' || f == 'eid' || f == 'first' || f == 'last' || f == 'company' ) { 
            // FIXME: Remove when all searched return no subarray
            if( d.customer != null ) {
                if( d.customer.eid != null && d.customer.eid != '' ) {
                    return d.customer.eid + ' - ' + d.customer.display_name; 
                }
                return d.customer.display_name; 
            } else {
                if( d.eid != null && d.eid != '' ) {
                    return d.eid + ' - ' + d.display_name; 
                }
                return d.display_name; 
            }
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
            if( d.customer != null ) {
                return 'M.ciniki_customers_edit.edit.updateParent(\'' + s + '\',\'' + escape(d.customer.id) + '\',\'' + escape(d.customer.display_name) + '\');'
            } else {
                return 'M.ciniki_customers_edit.edit.updateParent(\'' + s + '\',\'' + escape(d.id) + '\',\'' + escape(d.display_name) + '\');'
            }
        }
        else if( f == 'eid' || f == 'first' || f == 'last' || f == 'company' ) { 
            if( d.customer != null ) {
                if( this.parent_id != null && this.parent_id > 0 ) {
                    return 'M.ciniki_customers_edit.showEdit(null,\'' + d.customer.id + '\',null,\'' + this.parent_id + '\',\'' + escape(this.parent_name) + '\');';
                }
                return 'M.ciniki_customers_edit.showEdit(null,' + d.customer.id + ',null,0,\'\');';
            } else {
                if( this.parent_id != null && this.parent_id > 0 ) {
                    return 'M.ciniki_customers_edit.showEdit(null,\'' + d.id + '\',null,\'' + this.parent_id + '\',\'' + escape(this.parent_name) + '\');';
                }
                return 'M.ciniki_customers_edit.showEdit(null,' + d.id + ',null,0,\'\');';
            }
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
        M.gE(this.panelUID + '_city').value = unescape(city);
        M.gE(this.panelUID + '_province').value = unescape(province);
        M.gE(this.panelUID + '_country').value = unescape(country);
        this.removeLiveSearch(s, 'city');
    };
    this.edit.updateLabel = function(s, i, l) {
        M.gE(this.panelUID + '_' + i).value = l;
        this.removeLiveSearch(s, i);
    };
    this.edit.updateConnection = function(s, connection) {
        M.gE(this.panelUID + '_connection').value = unescape(connection);
        this.removeLiveSearch(s, 'connection');
    };
    this.edit.switchTab = function(tab) {
        M.ciniki_customers_edit.edit.forms.person._tabs.selected = tab;
        M.ciniki_customers_edit.edit.forms.business._tabs.selected = tab;
        var p = M.ciniki_customers_edit.edit;
        p.sections._tabs.selected = tab;
        p.refreshSection('_tabs');
        p.showHideSection('simplephone');
        p.showHideSection('phones');
        p.showHideSection('email');
        p.showHideSection('emails');
        p.showHideSection('address');
        p.showHideSection('addresses');
        p.showHideSection('links');
        p.showHideSection('subscriptions');
        p.showHideSection('_image');
        p.showHideSection('_image_caption');
        p.showHideSection('_image_intro');
        p.showHideSection('_image_intro_caption');
        p.showHideSection('_short_bio');
        p.showHideSection('_full_bio');
        p.showHideSection('images');
        p.showHideSection('_images');
        p.showHideSection('_notes');
    };
    this.edit.saveFirst = function(nc) {
        if( this.customer_id == 0 ) {
            var c = this.serializeForm('yes');
            if( this.subscriptions != null ) {
                for(i in this.subscriptions) {
                    var fname = 'subscription_' + this.subscriptions[i].subscription.id;
                    var o = this.fieldValue('subscriptions', fname, this.sections.subscriptions.fields[fname]);
                    var n = this.formValue(fname);
                    if( o != n && n > 0 ) {
                        if( n == 10 ) {
                            subs += sc + this.subscriptions[i].subscription.id; sc=',';
                        } else if( n == 60 ) {
                            unsubs += uc + this.subscriptions[i].subscription.id; uc=',';
                        }
                    }   
                }
            }
            if( subs != '' ) { c += 'subscriptions=' + subs + '&'; }
            if( unsubs != '' ) { c += 'unsubscriptions=' + unsubs + '&'; }
            c += 'type=' + type + '&';
            M.api.postJSONCb('ciniki.customer.add', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_customers_edit.edit.customer_id = rsp.id;
                    eval(nc);
                });
        } else {
            eval(nc);
        }
    }
    this.edit.addDropImage = function(iid) {
        if( this.customer_id == 0 ) {
            var c = this.serializeForm('yes');
            if( this.subscriptions != null ) {
                for(i in this.subscriptions) {
                    var fname = 'subscription_' + this.subscriptions[i].subscription.id;
                    var o = this.fieldValue('subscriptions', fname, this.sections.subscriptions.fields[fname]);
                    var n = this.formValue(fname);
                    if( o != n && n > 0 ) {
                        if( n == 10 ) {
                            subs += sc + this.subscriptions[i].subscription.id; sc=',';
                        } else if( n == 60 ) {
                            unsubs += uc + this.subscriptions[i].subscription.id; uc=',';
                        }
                    }   
                }
            }
            if( subs != '' ) { c += 'subscriptions=' + subs + '&'; }
            if( unsubs != '' ) { c += 'unsubscriptions=' + unsubs + '&'; }
            c += 'type=' + type + '&';
            M.api.postJSONCb('ciniki.customers.add', {'tnid':M.curTenantID, 'image_id':iid}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_customers_edit.edit.customer_id = rsp.id;
                    M.ciniki_customers_edit.edit.refreshImages();
                });
        } else {
            M.api.getJSONCb('ciniki.customers.imageAdd', {'tnid':M.curTenantID, 'image_id':iid, 'name':'', 'customer_id':this.customer_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_edit.edit.refreshImages();
            });
        }
        return true;
    };
    this.edit.refreshImages = function() {
        if( M.ciniki_customers_edit.edit.customer_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.customers.getFull', {'tnid':M.curTenantID, 'customer_id':this.customer_id, 'images':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_edit.edit;
                p.data.images = rsp.customer.images;
                p.refreshSection('images');
                p.show();
            });
        }
    }
    this.edit.setupStatus = function() {
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_customers_edit.customerSave();');
    this.edit.addClose('cancel');

    //
    // The form panel to edit an address for a customer 
    //
    this.address = new M.panel('Address',
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
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save address', 'fn':'M.ciniki_customers_edit.saveAddress();'},
            'delete':{'label':'Delete address', 'fn':'M.ciniki_customers_edit.deleteAddress();'},
            }},
        };
    this.address.fieldValue = function(s, i, d) { return this.data[i]; }
    this.address.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 
            'customer_id':this.customer_id, 'address_id':this.address_id, 'field':i}};
    };
    this.address.liveSearchCb = function(s, i, value) {
        if( i == 'city' ) {
            var rsp = M.api.getJSONBgCb('ciniki.customers.addressSearchQuick', {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
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
        M.gE(this.panelUID + '_city').value = unescape(city);
        M.gE(this.panelUID + '_province').value = unescape(province);
        M.gE(this.panelUID + '_country').value = unescape(country);
        this.removeLiveSearch(s, 'city');
    };
    this.address.addButton('save', 'Save', 'M.ciniki_customers_edit.saveAddress();');
    this.address.addClose('cancel');

    //
    // The form panel to edit an email for a customer 
    //
    this.email = new M.panel('Email',
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
        return {'method':'ciniki.customers.emailHistory', 'args':{'tnid':M.curTenantID, 
            'customer_id':this.customer_id, 'email_id':this.email_id, 'field':i}};
    };

    this.email.addButton('save', 'Save', 'M.ciniki_customers_edit.saveEmail();');
    this.email.addClose('cancel');

    //
    // The form panel to edit an phone for a customer 
    //
    this.phone = new M.panel('Phone Number',
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
        return {'method':'ciniki.customers.phoneHistory', 'args':{'tnid':M.curTenantID, 
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
        M.gE(this.panelUID + '_phone_label').value = unescape(l);
        this.removeLiveSearch(s, 'phone_label');
    };

    this.phone.addButton('save', 'Save', 'M.ciniki_customers_edit.savePhone();');
    this.phone.addClose('cancel');

    //
    // The form panel to edit a link for a customer 
    //
    this.link = new M.panel('Website',
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
//          '_description':{'label':'Description', 'fields':{
//              'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
//              }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save Website', 'fn':'M.ciniki_customers_edit.saveLink();'},
            'delete':{'label':'Delete Website', 'fn':'M.ciniki_customers_edit.deleteLink();'},
            }},
        };
    this.link.fieldValue = function(s, i, d) { return this.data[i]; }
    this.link.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.customers.linkHistory', 'args':{'tnid':M.curTenantID, 
            'customer_id':this.customer_id, 'link_id':this.link_id, 'field':i}};
    };

    this.link.addButton('save', 'Save', 'M.ciniki_customers_edit.saveLink();');
    this.link.addClose('cancel');
    

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }
        var settings = null;
        if( M.curTenant.modules['ciniki.customers'] != null
            && M.curTenant.modules['ciniki.customers'].settings != null ) {
            settings = M.curTenant.modules['ciniki.customers'].settings;
        }

        if( M.modFlagOn('ciniki.customers', 0x02) ) {
            this.edit.forms.person.name.fields.status.label = 'Customer Status';
            this.edit.forms.business.business.fields.status.label = 'Customer Status';
            this.edit.forms.person.membership.fields.member_status.label = 'Member Status';
            this.edit.forms.business.membership.fields.member_status.label = 'Member Status';
        } else {
            this.edit.forms.person.name.fields.status.label = 'Status';
            this.edit.forms.business.business.fields.status.label = 'Status';
            this.edit.forms.person.membership.fields.member_status.label = 'Member Status';
            this.edit.forms.business.membership.fields.member_status.label = 'Member Status';
        }

        if( (M.curTenant.modules['ciniki.customers'].flags&0x0112) > 0 ) {
            this.addressFlags = {
                '1':{'name':'Shipping'}, 
                '2':{'name':'Billing'}, 
                '3':{'name':'Mailing'},
                '4':{'name':'Public'},
            };
            this.emailFlags = {
                '1':{'name':'Web Login'}, 
                '4':{'name':'Public'},
                '5':{'name':'No Emails'},
                };
            this.linkFlags = {
                '1':{'name':'Public'}, 
                };
            this.phoneFlags = {
                '4':{'name':'Public'},
                };
            this.phone.sections._phone.fields.flags.active = 'yes';
            this.phone.sections._phone.fields.flags.flags = this.phoneFlags;
            this.link.sections._link.fields.webflags.flags = this.linkFlags;
            this.edit.forms.person._phone.fields.phone_flags_1.active = 'yes'
            this.edit.forms.person._phone.fields.phone_flags_1.flags = this.phoneFlags;
            this.edit.forms.person._phone.fields.phone_flags_2.active = 'yes'
            this.edit.forms.person._phone.fields.phone_flags_2.flags = this.phoneFlags;
            this.edit.forms.person._phone.fields.phone_flags_3.active = 'yes'
            this.edit.forms.person._phone.fields.phone_flags_3.flags = this.phoneFlags;
            this.edit.forms.business._phone.fields.phone_flags_1.active = 'yes'
            this.edit.forms.business._phone.fields.phone_flags_1.flags = this.phoneFlags;
            this.edit.forms.business._phone.fields.phone_flags_2.active = 'yes'
            this.edit.forms.business._phone.fields.phone_flags_2.flags = this.phoneFlags;
            this.edit.forms.business._phone.fields.phone_flags_3.active = 'yes'
            this.edit.forms.business._phone.fields.phone_flags_3.flags = this.phoneFlags;
            this.edit.forms.person._image.active = 'yes';
            this.edit.forms.business._image.active = 'yes';
            this.edit.forms.person._image_caption.active = 'yes';
            this.edit.forms.business._image_caption.active = 'yes';
            this.edit.forms.person._image_intro_caption.active = 'yes';
            this.edit.forms.business._image_intro_caption.active = 'yes';
            this.edit.forms.person.distributor.active = 'yes';
            this.edit.forms.business.distributor.active = 'yes';
            this.edit.forms.person._short_bio.active = 'yes';
            this.edit.forms.business._short_bio.active = 'yes';
            this.edit.forms.person._full_bio.active = 'yes';
            this.edit.forms.business._full_bio.active = 'yes';
        } else {
            this.addressFlags = {
                '1':{'name':'Shipping'}, 
                '2':{'name':'Billing'}, 
                '3':{'name':'Mailing'},
            };
            this.emailFlags = {
                '1':{'name':'Web Login'}, 
                '5':{'name':'No Emails'},
                };
            this.linkFlags = {
                };
            this.phoneFlags = {
                };
            this.phone.sections._phone.fields.flags.active = 'no';
            this.link.sections._link.fields.webflags.flags = this.linkFlags;
            this.edit.forms.person._phone.fields.phone_flags_1.active = 'no'
            this.edit.forms.person._phone.fields.phone_flags_2.active = 'no'
            this.edit.forms.person._phone.fields.phone_flags_3.active = 'no'
            this.edit.forms.business._phone.fields.phone_flags_1.active = 'no'
            this.edit.forms.business._phone.fields.phone_flags_2.active = 'no'
            this.edit.forms.business._phone.fields.phone_flags_3.active = 'no'
            this.edit.forms.person._image.active = 'no';
            this.edit.forms.business._image.active = 'no';
            this.edit.forms.person._image_caption.active = 'no';
            this.edit.forms.business._image_caption.active = 'no';
            this.edit.forms.person.distributor.active = 'no';
            this.edit.forms.business.distributor.active = 'no';
            this.edit.forms.person._short_bio.active = 'no';
            this.edit.forms.business._short_bio.active = 'no';
            this.edit.forms.person._full_bio.active = 'no';
            this.edit.forms.business._full_bio.active = 'no';
        }

        this.edit.forms.person.address.fields.address_flags.flags = this.addressFlags;
        this.edit.forms.business.address.fields.address_flags.flags = this.addressFlags;
        this.edit.forms.person.email.fields.flags.flags = this.emailFlags;
        this.edit.forms.business.email.fields.flags.flags = this.emailFlags;
        this.address.sections.address.fields.flags.flags = this.addressFlags;
        this.email.sections._email.fields.flags.flags = this.emailFlags;

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_edit', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 
        // Turn off account section by default
        var account = 'no';

        if( M.modOn('ciniki.sapos') || M.modOn('ciniki.poma') || M.modOn('ciniki.products') ) {
            this.edit.title = 'Customers';
        }
        if( M.modOn('ciniki.sapos') || M.modOn('ciniki.poma') ) {
            this.edit.forms.person.name.fields.status.toggles = {
                '10':'Active', 
                '40':'On Hold', 
                '50':'Suspended', 
                '60':'Deleted', 
                };
        } else {
            this.edit.forms.person.name.fields.status.toggles = {
                '10':'Active', 
                '60':'Deleted', 
                };
        }
        this.edit.forms.business.business.fields.status.toggles = this.edit.forms.person.name.fields.status.toggles;
        if( M.curTenant.customers.settings != null 
            && M.curTenant.customers.settings['defaults-edit-person-hide-company'] != null
            && M.curTenant.customers.settings['defaults-edit-person-hide-company'] == 'yes' ) {
            this.edit.forms.person.business.active = 'no';
        } else {
            this.edit.forms.person.business.active = 'yes';
        }
        //
        // Turn on or off the flag for web login based on if the module is enabled
        //
        if( M.modOn('ciniki.web') || M.modOn('ciniki.wng') ) {
            this.edit.forms.person.email.fields.flags.active = 'yes';
            this.edit.forms.business.email.fields.flags.active = 'yes';
            this.email.sections._email.fields.flags.active = 'yes';
        } else {
            this.edit.forms.person.email.fields.flags.active = 'no';
            this.edit.forms.business.email.fields.flags.active = 'no';
            this.email.sections._email.fields.flags.active = 'no';
        }
    
        // Birthdate
        if( (M.curTenant.modules['ciniki.customers'].flags&0x8000) > 0 ) {
            this.edit.forms.person.name.fields.birthdate.active = 'yes';
            this.edit.forms.business.name.fields.birthdate.active = 'yes';
        } else {
            this.edit.forms.person.name.fields.birthdate.active = 'no';
            this.edit.forms.business.name.fields.birthdate.active = 'no';
        }
        // Language
        if( M.modFlagOn('ciniki.customers', 0x0200000000) ) {
            this.edit.forms.person.name.fields.language.active = 'yes';
            this.edit.forms.business.name.fields.language.active = 'yes';
        } else {
            this.edit.forms.person.name.fields.language.active = 'no';
            this.edit.forms.business.name.fields.language.active = 'no';
        }
        // Start date
        if( (M.curTenant.modules['ciniki.customers'].flags&0x04000000) > 0 ) {
            this.edit.forms.person.account.fields.start_date.active = 'yes';
            this.edit.forms.business.account.fields.start_date.active = 'yes';
            account = 'yes';
        } else {
            this.edit.forms.person.account.fields.start_date.active = 'no';
            this.edit.forms.business.account.fields.start_date.active = 'no';
        }
        // Connection - How did you hear about us?
        if( (M.curTenant.modules['ciniki.customers'].flags&0x4000) > 0 ) {
            this.edit.forms.person._connection.active = 'yes';
            this.edit.forms.business._connection.active = 'yes';
        } else {
            this.edit.forms.person._connection.active = 'no';
            this.edit.forms.business._connection.active = 'no';
        }
        // eid - customer ID
        if( (M.curTenant.modules['ciniki.customers'].flags&0x10000) > 0 ) {
            this.edit.forms.person.name.fields.eid.active = 'yes';
            this.edit.forms.business.business.fields.eid.active = 'yes';
        } else {
            this.edit.forms.person.name.fields.eid.active = 'no';
            this.edit.forms.business.business.fields.eid.active = 'no';
        }
        // Tax Number
        if( (M.curTenant.modules['ciniki.customers'].flags&0x20000) > 0 ) {
            this.edit.forms.person.account.fields.tax_number.active = 'yes';
            this.edit.forms.business.account.fields.tax_number.active = 'yes';
            account = 'yes';
        } else {
            this.edit.forms.person.account.fields.tax_number.active = 'no';
            this.edit.forms.business.account.fields.tax_number.active = 'no';
        }
        // Display the address phone number
        if( (M.curTenant.modules['ciniki.customers'].flags&0x01000000) > 0 ) {
            this.address.sections.address.fields.phone.active = 'yes';
            this.edit.forms.person.address.fields.phone.active = 'yes';
            this.edit.forms.business.address.fields.phone.active = 'yes';
        } else {
            this.address.sections.address.fields.phone.active = 'no';
            this.edit.forms.person.address.fields.phone.active = 'no';
            this.edit.forms.business.address.fields.phone.active = 'no';
        }
        if( M.curTenant.customers != null && M.curTenant.customers.settings != null ) {
            // Tax Locations
            if( (M.curTenant.modules['ciniki.customers'].flags&0x40000) > 0 
                && M.curTenant.taxes != null 
                && M.curTenant.taxes.settings != null
                && M.curTenant.taxes.settings.locations != null
                ) {
                this.edit.forms.person.account.fields.tax_location_id.active = 'yes';
                this.edit.forms.business.account.fields.tax_location_id.active = 'yes';
                account = 'yes';
                var locations = {'0':'Use Shipping Address'};

                var locs = M.curTenant.taxes.settings.locations;
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
            this.edit.forms.person.account.fields.tax_location_id.active = 'no';
            this.edit.forms.business.account.fields.tax_location_id.active = 'no';
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

        if( M.modFlagOn('ciniki.customers', 0x02) ) {
            this.edit.forms.person.membership.active = 'yes';
            this.edit.forms.business.membership.active = 'yes';
        } else {
            this.edit.forms.person.membership.active = 'no';
            this.edit.forms.business.membership.active = 'no';
        }

        //
        // Setup the member forms
        //
        if( args.member != null && args.member == 'yes' ) {
            this.edit.memberinfo = 'yes';
            this.edit.dealerinfo = 'no';
//          this.edit.title = 'Member';
/*          ** Deprecated ui-labels- 2020-07-14 **
            if( M.curTenant.customers != null 
                && M.curTenant.customers.settings['ui-labels-member'] != null 
                && M.curTenant.customers.settings['ui-labels-member'] != '' 
                ) {
                this.edit.title = M.curTenant.customers.settings['ui-labels-member'];
            } */
            this.edit.distributorinfo = 'no';
//          this.edit.forms.person.dealer.active = 'no';
//          this.edit.forms.business.dealer.active = 'no';
//          this.edit.forms.person.distributor.active = 'no';
//          this.edit.forms.business.distributor.active = 'no';
//          this.edit.forms.person.address.fields.address_flags.flags = this.memberAddressFlags;
//          this.edit.forms.business.address.fields.address_flags.flags = this.memberAddressFlags;
//          this.edit.forms.person.email.fields.flags.flags = this.memberEmailFlags;
//          this.edit.forms.business.email.fields.flags.flags = this.memberEmailFlags;
//            this.edit.forms.person.phone.fields.phone_flags_1.active = 'yes';
//            this.edit.forms.person.phone.fields.phone_flags_2.active = 'yes';
//            this.edit.forms.person.phone.fields.phone_flags_3.active = 'yes';
//            this.edit.forms.business.phone.fields.phone_flags_1.active = 'yes';
//            this.edit.forms.business.phone.fields.phone_flags_2.active = 'yes';
//            this.edit.forms.business.phone.fields.phone_flags_3.active = 'yes';
//          this.address.sections.address.fields.flags.flags = this.memberAddressFlags;
            this.address.sections._latlong_buttons.active = 'no';
            this.address.sections._latlong.active = 'no';
//          this.phone.sections._phone.fields.flags.active = 'yes';
//          this.phone.sections._phone.fields.flags.flags = this.memberPhoneFlags;
//          this.email.sections._email.fields.flags.flags = this.memberEmailFlags;
        } else {
            this.edit.title = 'Contact';
            if( M.modOn('ciniki.sapos') || M.modOn('ciniki.poma') || M.modOn('ciniki.products') ) {
                this.edit.title = 'Customer';
            }
/*            if( M.curTenant.customers != null 
                && M.curTenant.customers.settings['ui-labels-customer'] != null 
                && M.curTenant.customers.settings['ui-labels-customer'] != '' 
                ) {
                this.edit.title = M.curTenant.customers.settings['ui-labels-customer'];
            } */
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
            this.address.sections._latlong_buttons.active = 'no';
            this.address.sections._latlong.active = 'no';
        }
    
        // Check if membership info collected
        if( M.modFlagOn('ciniki.customers', 0x08) ) {
            this.edit.forms.person.membership.active = 'yes';
            this.edit.forms.person.membership.label = 'Membership';
            this.edit.forms.person.membership.fields.member_lastpaid.active = 'no';
            this.edit.forms.person.membership.fields.member_expires.active = 'no';
            this.edit.forms.person.membership.fields.membership_length.active = 'no';
            this.edit.forms.person.membership.fields.membership_type.active = 'no';
            this.edit.forms.business.membership.active = 'yes';
            this.edit.forms.business.membership.label = 'Membership';
            this.edit.forms.business.membership.fields.member_lastpaid.active = 'no';
            this.edit.forms.business.membership.fields.member_expires.active = 'no';
            this.edit.forms.business.membership.fields.membership_length.active = 'no';
            this.edit.forms.business.membership.fields.membership_type.active = 'no';
        } else if( M.modFlagOn('ciniki.customers', 0x02) ) {
            this.edit.forms.person.membership.active = 'yes';
            this.edit.forms.person.membership.label = 'Membership';
            this.edit.forms.person.membership.fields.member_lastpaid.active = 'yes';
            this.edit.forms.person.membership.fields.member_expires.active = 'yes';
            this.edit.forms.person.membership.fields.membership_length.active = 'yes';
            this.edit.forms.person.membership.fields.membership_type.active = 'yes';
            this.edit.forms.business.membership.active = 'yes';
            this.edit.forms.business.membership.label = 'Membership';
            this.edit.forms.business.membership.fields.member_lastpaid.active = 'yes';
            this.edit.forms.business.membership.fields.member_expires.active = 'yes';
            this.edit.forms.business.membership.fields.membership_length.active = 'yes';
            this.edit.forms.business.membership.fields.membership_type.active = 'yes';
        } else {
            this.edit.forms.person.membership.active = 'no';
            this.edit.forms.person.membership.label = 'Status';
            this.edit.forms.person.membership.fields.member_lastpaid.active = 'no';
            this.edit.forms.person.membership.fields.member_expires.active = 'no';
            this.edit.forms.person.membership.fields.membership_length.active = 'no';
            this.edit.forms.person.membership.fields.membership_type.active = 'no';
            this.edit.forms.business.membership.active = 'no';
            this.edit.forms.business.membership.label = 'Status';
            this.edit.forms.business.membership.fields.member_lastpaid.active = 'no';
            this.edit.forms.business.membership.fields.member_expires.active = 'no';
            this.edit.forms.business.membership.fields.membership_length.active = 'no';
            this.edit.forms.business.membership.fields.membership_type.active = 'no';
        }

        // Season Memberships
        if( (M.curTenant.modules['ciniki.customers'].flags&0x02000000) > 0 
            && M.curTenant.modules['ciniki.customers'].settings != null
            && M.curTenant.modules['ciniki.customers'].settings['seasons'] != null
            ) {
            this.edit.forms.person._seasons.active = 'yes';
            this.edit.forms.business._seasons.active = 'yes';
            this.edit.forms.person._seasons.fields = {};
            this.edit.forms.business._seasons.fields = {};
            this.edit.forms.person.membership.fields.member_lastpaid.active = 'no';
            this.edit.forms.person.membership.fields.member_expires.active = 'no';
            this.edit.forms.business.membership.fields.member_lastpaid.active = 'no';
            this.edit.forms.business.membership.fields.member_expires.active = 'no';
            for(i in M.curTenant.modules['ciniki.customers'].settings.seasons) {
                var season = M.curTenant.modules['ciniki.customers'].settings.seasons[i].season;
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

        if( (M.curTenant.modules['ciniki.customers'].flags&0x04) > 0 ) {
            this.edit.forms.person._member_categories.active = 'yes';
            this.edit.forms.business._member_categories.active = 'yes';
        } else {
            this.edit.forms.person._member_categories.active = 'no';
            this.edit.forms.business._member_categories.active = 'no';
        }

        //
        // Dealers
        //
        if( M.modFlagSet('ciniki.customers', 0x10) == 'yes' ) {
            this.edit.forms.person.dealer.active = 'yes';
            this.edit.forms.business.dealer.active = 'yes';
        } else {
            this.edit.forms.person.dealer.active = 'no';
            this.edit.forms.business.dealer.active = 'no';
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x20) > 0 ) {
            this.edit.forms.person._dealer_categories.active = 'yes';
            this.edit.forms.business._dealer_categories.active = 'yes';
        }

        //
        // Distributors
        //
        if( M.modFlagSet('ciniki.customers', 0x0100) == 'yes' ) {
            this.edit.forms.person.distributor.active = 'yes';
            this.edit.forms.business.distributor.active = 'yes';
        } else {
            this.edit.forms.person.distributor.active = 'no';
            this.edit.forms.business.distributor.active = 'no';
        } 
        if( (M.curTenant.modules['ciniki.customers'].flags&0x200) > 0 ) {
            this.edit.forms.person._distributor_categories.active = 'yes';
            this.edit.forms.business._distributor_categories.active = 'yes';
        }

        //
        // Customer Categories and Tags
        //
        if( (M.curTenant.modules['ciniki.customers'].flags&0x400000) > 0 ) {
            this.edit.forms.person._customer_categories.active = 'yes';
            this.edit.forms.business._customer_categories.active = 'yes';
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x800000) > 0 ) {
            this.edit.forms.person._customer_tags.active = 'yes';
            this.edit.forms.business._customer_tags.active = 'yes';
        }

        if( args.edit_phone_id != null && args.edit_phone_id != '' && args.customer_id != null && args.customer_id > 0 ) {
            this.showPhoneEdit(cb, args.customer_id, args.edit_phone_id);
        }
        else if( args.edit_email_id != null && args.edit_email_id != '' && args.customer_id != null && args.customer_id > 0 ) {
            this.showEmailEdit(cb, args.customer_id, args.edit_email_id);
        }
        else if( args.edit_address_id != null && args.edit_address_id != '' && args.customer_id != null && args.customer_id > 0 ) {
            this.showAddressEdit(cb, args.customer_id, args.edit_address_id);
        }
        else if( args.edit_link_id != null && args.edit_link_id != '' && args.customer_id != null && args.customer_id > 0 ) {
            this.showLinkEdit(cb, args.customer_id, args.edit_link_id);
        } 
        else {
            this.showEdit(cb, args.customer_id, args.category, (args.parent_id!=null?args.parent_id:0), args.parent_name, args.type);
        }

        return false;
    }

    this.showEdit = function(cb, cid, category, pid, pname, type) {
        if( pid != null ) { this.edit.parent_id = pid; }
        if( pname != null ) { this.edit.parent_name = unescape(pname); }
        if( cid != null ) { this.edit.customer_id = cid; }
        this.edit.formtab = null;
        this.edit.formtab_field_id = null;
        this.edit.forms.person._buttons.buttons.delete.visible = 'no';
        this.edit.forms.business._buttons.buttons.delete.visible = 'no';
//      this.edit.forms.person._customer_categories.active = 'no';
//      this.edit.forms.business._customer_categories.active = 'no';
//      this.edit.forms.person._customer_tags.active = 'no';
//      this.edit.forms.business._customer_tags.active = 'no';
//      this.edit.forms.person._member_categories.active = 'no';
//      this.edit.forms.business._member_categories.active = 'no';
//      this.edit.forms.person._dealer_categories.active = 'no';
//      this.edit.forms.business._dealer_categories.active = 'no';
//      this.edit.forms.person._distributor_categories.active = 'no';
//      this.edit.forms.business._distributor_categories.active = 'no';
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

        if( this.edit.customer_id > 0 ) {
            this.edit.forms.person._buttons.buttons.delete.visible = 'yes';
            this.edit.forms.business._buttons.buttons.delete.visible = 'yes';
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
            if( (M.curTenant.modules['ciniki.customers'].flags&0x10000000) == 0 ) {
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
            this.edit.forms.person.emails.active = 'yes';
            this.edit.forms.business.emails.active = 'yes';
            this.edit.forms.person.email.active = 'no';
            this.edit.forms.person.address.active = 'no';
            this.edit.forms.person._phone.active = 'no';
            this.edit.forms.person.addresses.active = 'yes';
            this.edit.forms.person.links.active = 'yes';
            // Tenant form
            this.edit.forms.business.email.active = 'no';
            this.edit.forms.business.address.active = 'no';
            this.edit.forms.business._phone.active = 'no';
            this.edit.forms.business.addresses.active = 'yes';
            this.edit.forms.business.links.active = 'yes';
            M.api.getJSONCb('ciniki.customers.getFull', {'tnid':M.curTenantID, 'customer_id':this.edit.customer_id, 
                'tags':'yes', 'customer_categories':'yes', 'customer_tags':'yes', 'member_categories':'yes', 
                'dealer_categories':'yes', 'distributor_categories':'yes', 'images':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_edit.edit;
                    p.data = rsp.customer;
                    // Parent
                    p.forms.person.parent.active = 'no';
                    p.forms.business.parent.active = 'no';
                    if( (M.curTenant.modules['ciniki.customers'].flags&0x200000) > 0 ) {
                        if( pid != null && (rsp.customer.parent == null || rsp.customer.parent.id == 0) ) {
                            p.data.parent = {'id':0, 'display_name':(pname!=null?unescape(pname):'')};
                        }
                        if( rsp.customer.num_children == null || rsp.customer.num_children == 0 || rsp.customer.parent_id > 0 ) {
                            p.forms.person.parent.active = 'yes';
                            p.forms.business.parent.active = 'yes';
                        }
                    }
                    for(i in rsp.tag_types) {
                        var tags = [];
                        for(j in rsp.tag_types[i].type.tags) {
                            tags.push(rsp.tag_types[i].type.tags[j].tag.name);
                        }
                        if( rsp.tag_types[i].type.tag_type == 10 && p.forms.person._customer_categories.active == 'yes' && (M.curTenant.modules['ciniki.customers'].flags&0x400000) > 0 ) {
                            p.forms.person._customer_categories.fields.customer_categories.tags = tags;
                            p.forms.business._customer_categories.fields.customer_categories.tags = tags;
                        }
                        else if( rsp.tag_types[i].type.tag_type == 20 && p.forms.person._customer_tags.active == 'yes' && (M.curTenant.modules['ciniki.customers'].flags&0x800000) > 0 ) {
                            p.forms.person._customer_tags.fields.customer_tags.tags = tags;
                            p.forms.business._customer_tags.fields.customer_tags.tags = tags;
                        }
                        else if( rsp.tag_types[i].type.tag_type == 40 && (M.curTenant.modules['ciniki.customers'].flags&0x04) > 0 ) {
                            p.forms.person._member_categories.fields.member_categories.tags = tags;
                            p.forms.business._member_categories.fields.member_categories.tags = tags;
                        }
                        else if( rsp.tag_types[i].type.tag_type == 60 && (M.curTenant.modules['ciniki.customers'].flags&0x20) > 0 ) {
                            p.forms.person._dealer_categories.fields.dealer_categories.tags = tags;
                            p.forms.business._dealer_categories.fields.dealer_categories.tags = tags;
                        }
                        else if( rsp.tag_types[i].type.tag_type == 80 && (M.curTenant.modules['ciniki.customers'].flags&0x0200) > 0 ) {
                            p.forms.person._distributor_categories.fields.distributor_categories.tags = tags;
                            p.forms.business._distributor_categories.fields.distributor_categories.tags = tags;
                        }
                    }
                    // Display the email add
                    if( (M.curTenant.modules['ciniki.customers'].flags&0x20000000) == 0 || rsp.customer.emails == null || rsp.customer.emails.length == 0 ) {
                        p.forms.person.emails.addTxt = 'Add Email';
                        p.forms.business.emails.addTxt = 'Add Email';
                    } else {
                        p.forms.person.emails.addTxt = '';
                        p.forms.business.emails.addTxt = '';
                    }
                    // Display the address add
                    if( (M.curTenant.modules['ciniki.customers'].flags&0x40000000) == 0 || rsp.customer.addresses == null || rsp.customer.addresses.length == 0 ) {
                        p.forms.person.addresses.addTxt = 'Add Address';
                        p.forms.business.addresses.addTxt = 'Add Address';
                    } else {
                        // FIXME: Change to allow for 2 addresses (Shipping/Billing)
                        p.forms.person.addresses.addTxt = 'Add Address';
                        p.forms.business.addresses.addTxt = 'Add Address';
                    }
                    M.ciniki_customers_edit.showEditSubscriptions(cb);
                });
        } else {
            this.edit.data = {'status':'10', 'type':'1', 'flags':1, 'address_flags':15, 'phone_label_1':'Home', 'phone_label_2':'Work', 'phone_label_3':'Cell'};
            if( (M.curTenant.modules['ciniki.customers'].flags&0x200000) > 0 ) {
                this.edit.forms.person.parent.active = 'yes';
//                this.edit.forms.business.parent.active = 'yes';
                if( pid != null ) {
                    this.edit.data.parent = {'id':pid, 'display_name':(pname!=null?unescape(pname):'')};
                } else {
                    this.edit.data.parent = {'id':'', 'display_name':''};
                }
            } else {
                this.edit.forms.person.parent.active = 'no';
                this.edit.forms.business.parent.active = 'no';
            }
            if( (M.curTenant.customers.settings != null 
                && M.curTenant.customers.settings['defaults-edit-form'] != null
                && M.curTenant.customers.settings['defaults-edit-form'] == 'business') 
                || (type != null && type == 2) ) {
                this.edit.data.type = 2;
            } else {
                this.edit.data.type = 1;
            }
            if( this.edit.memberinfo == 'yes' ) {
//              this.edit.data.member_status = 10;
//              this.edit.data.membership_length = 20;
//              this.edit.data.membership_type = 10;
                if( category != null ) { this.edit.data.member_categories = category; }
            } else if( this.edit.dealerinfo == 'yes' ) {
                this.edit.data.dealer_status = 10;
                if( category != null ) { this.edit.data.dealer_categories = category; }
            } else if( this.edit.distributorinfo == 'yes' ) {
                this.edit.data.distributor_status = 10;
                if( category != null ) { this.edit.data.distributor_categories = category; }
            }
            if( (M.curTenant.modules['ciniki.customers'].flags&0x10000000) == 0 ) {
                this.edit.forms.person._phone.active = 'yes';
                this.edit.forms.business._phone.active = 'yes';
                this.edit.forms.person.simplephone.active = 'no';
                this.edit.forms.business.simplephone.active = 'no';
            } else {
                this.edit.forms.person._phone.active = 'no';
                this.edit.forms.business._phone.active = 'no';
                this.edit.forms.person.simplephone.active = 'yes';
                this.edit.forms.business.simplephone.active = 'yes';
            }
            this.edit.forms.person.email.active = 'yes';
            this.edit.forms.business.email.active = 'yes';
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
            this.edit.forms.person._customer_categories.fields.customer_categories.tags = [];
            if( (M.curTenant.modules['ciniki.customers'].flags&0xC00224) > 0 ) {
                M.api.getJSONCb('ciniki.customers.tags', {'tnid':M.curTenantID}, function(rsp) {
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
                        if( rsp.tag_types[i].type.tag_type == 10 && p.forms.person._customer_categories.active == 'yes' && (M.curTenant.modules['ciniki.customers'].flags&0x400000) > 0 ) {
                            p.forms.person._customer_categories.fields.customer_categories.tags = tags;
                            p.forms.business._customer_categories.fields.customer_categories.tags = tags;
                        }
                        if( rsp.tag_types[i].type.tag_type == 20 && p.forms.person._customer_tags.active == 'yes' && (M.curTenant.modules['ciniki.customers'].flags&0x800000) > 0 ) {
                            p.forms.person._customer_tags.fields.customer_tags.tags = tags;
                            p.forms.business._customer_tags.fields.customer_tags.tags = tags;
                        }
                        if( rsp.tag_types[i].type.tag_type == 40 && (M.curTenant.modules['ciniki.customers'].flags&0x04) > 0 ) {
                            p.forms.person._member_categories.fields.member_categories.tags = tags;
                            p.forms.business._member_categories.fields.member_categories.tags = tags;
                        }
                        if( rsp.tag_types[i].type.tag_type == 60 && (M.curTenant.modules['ciniki.customers'].flags&0x20) > 0 ) {
                            p.forms.person._dealer_categories.fields.dealer_categories.tags = tags;
                            p.forms.business._dealer_categories.fields.dealer_categories.tags = tags;
                        }
                        if( rsp.tag_types[i].type.tag_type == 80 && (M.curTenant.modules['ciniki.customers'].flags&0x0200) > 0 ) {
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
        var rsp = M.api.getJSONCb('ciniki.customers.get', {'tnid':M.curTenantID, 
            'customer_id':this.edit.customer_id, 'phones':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_edit.edit;
                p.data.phones = rsp.customer.phones;
                p.refreshSection('phones');
                p.show();
            });
    };

    this.updateEditEmails = function() {
        var rsp = M.api.getJSONCb('ciniki.customers.get', {'tnid':M.curTenantID, 
            'customer_id':this.edit.customer_id, 'emails':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_edit.edit;
                p.data.emails = rsp.customer.emails;
                if( (M.curTenant.modules['ciniki.customers'].flags&0x20000000) == 0 || rsp.customer.emails == null || rsp.customer.emails.length == 0 ) {
                    p.forms.person.emails.addTxt = 'Add Email';
                    p.forms.business.emails.addTxt = 'Add Email';
                } else {
                    p.forms.person.emails.addTxt = '';
                    p.forms.business.emails.addTxt = '';
                }
                p.refreshSection('emails');
                p.show();
            });
    };

    this.updateEditAddresses = function() {
        var rsp = M.api.getJSONCb('ciniki.customers.get', {'tnid':M.curTenantID, 
            'customer_id':this.edit.customer_id, 'addresses':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_edit.edit;
                p.data.addresses = rsp.customer.addresses;
                if( (M.curTenant.modules['ciniki.customers'].flags&0x40000000) == 0 || rsp.customer.addresses == null || rsp.customer.addresses.length == 0 ) {
                    p.forms.person.addresses.addTxt = 'Add Address';
                    p.forms.business.addresses.addTxt = 'Add Address';
                } else {
                    // FIXME: Change to allow for 2 addresses (Shipping/Billing)
                    p.forms.person.addresses.addTxt = 'Add Address';
                    p.forms.business.addresses.addTxt = 'Add Address';
                }
                p.refreshSection('addresses');
                p.show();
            });
    };

    this.updateEditLinks = function() {
        var rsp = M.api.getJSONCb('ciniki.customers.get', {'tnid':M.curTenantID, 
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
        this.edit.forms.person._tabs.tabs.subscriptions.visible = 'no';
        this.edit.forms.business._tabs.tabs.subscriptions.visible = 'no';
        if( M.curTenant['modules']['ciniki.subscriptions'] != null ) {
            M.api.getJSONCb('ciniki.subscriptions.subscriptionList', {'tnid':M.curTenantID, 
                'customer_id':this.edit.customer_id, 'status':'10'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    // Reset any existing fields
                    var p = M.ciniki_customers_edit.edit;
//                  M.ciniki_customers_edit.edit.sections.subscriptions = {'label':'', 'fields':null};
                    p.subscriptions = rsp.subscriptions;
                    // Add subscriptions to the form
                    if( rsp.subscriptions.length > 0 ) {
                        p.forms.person._tabs.tabs.subscriptions.visible = 'yes';
                        p.forms.business._tabs.tabs.subscriptions.visible = 'yes';
//                      p.forms.person.subscriptions.visible = 'yes';
//                      p.forms.business.subscriptions.visible = 'yes'; 
                        p.forms.person.subscriptions.fields = {};
                        var i = 0;
                        for(i in rsp.subscriptions) {
                            p.forms.person.subscriptions.fields['subscription_' + rsp.subscriptions[i].subscription.id] = {'label':rsp.subscriptions[i].subscription.name, 
                                'type':'toggle', 'toggles':M.ciniki_customers_edit.subscriptionOptions};
                            if( rsp.subscriptions[i].subscription.status == null && (rsp.subscriptions[i].subscription.flags&0x02) == 0x02 ) {
                                p.forms.person.subscriptions.fields['subscription_' + rsp.subscriptions[i].subscription.id].default = 10;
                            } else {
                                p.data['subscription_' + rsp.subscriptions[i].subscription.id] = rsp.subscriptions[i].subscription.status;
                            }
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
                    if( p.parent_id > 0 && p.data.parent_id == 0 ) { 
                        p.setFieldValue('parent_id', p.parent_id);
                    }
                });
        } else {
            var p = M.ciniki_customers_edit.edit;
            p.subscriptions = null;
//            p.forms.person.subscriptions.visible = 'no';
//            p.forms.business.subscriptions.visible = 'no';
            p.refresh();
            p.show(cb);
            p.setupStatus();
            if( p.parent_id > 0 && p.data.parent_id == 0 ) { 
                p.setFieldValue('parent_id', p.parent_id);
            }
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
            var c = this.edit.serializeForm('no');
            if( subs != '' ) { c += 'subscriptions=' + subs + '&'; }
            if( unsubs != '' ) { c += 'unsubscriptions=' + unsubs + '&'; }
            if( type != this.edit.data.type ) {
                c += 'type=' + type + '&';
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.customers.update', {'tnid':M.curTenantID, 'customer_id':M.ciniki_customers_edit.edit.customer_id}, c, function(rsp) {
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
            var c = this.edit.serializeForm('yes');
            if( subs != '' ) { c += 'subscriptions=' + subs + '&'; }
            if( unsubs != '' ) { c += 'unsubscriptions=' + unsubs + '&'; }
            c += 'type=' + type + '&';
            M.api.postJSONCb('ciniki.customers.add', {'tnid':M.curTenantID}, c, function(rsp) {
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

    this.deleteCustomer = function() {
        if( this.edit.customer_id > 0 ) {
            M.confirm("Are you sure you want to remove this customer?  This will remove all subscriptions, phone numbers, email addresses, addresses and websites.",null,function() {
                M.api.getJSONCb('ciniki.customers.delete', {'tnid':M.curTenantID, 
                    'customer_id':M.ciniki_customers_edit.edit.customer_id}, function(rsp) {
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
            });
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

        if( (M.modFlagSet('ciniki.customers', 0x0010) == 'yes' && this.edit.formValue('webflags_2') == 'on')
            || (M.modFlagSet('ciniki.customers', 0x0100) == 'yes' && this.edit.formValue('webflags_3') == 'on')
            ) {
            this.address.sections._latlong_buttons.active = 'yes';
            this.address.sections._latlong.active = 'yes';
        } else {
            this.address.sections._latlong_buttons.active = 'no';
            this.address.sections._latlong.active = 'no';
        }

        if( this.address.address_id > 0 ) {
            this.address.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.customers.addressGet', 
                {'tnid':M.curTenantID, 'customer_id':this.address.customer_id, 
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
                    {'tnid':M.curTenantID, 
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
                {'tnid':M.curTenantID, 
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
        M.confirm("Are you sure you want to remove this address?",null,function() {
            var rsp = M.api.getJSONCb('ciniki.customers.addressDelete', 
                {'tnid':M.curTenantID, 
                    'customer_id':M.ciniki_customers_edit.address.customer_id, 
                    'address_id':M.ciniki_customers_edit.address.address_id}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_customers_edit.address.close();
                    });
        });
    };

    this.showEmailEdit = function(cb, cid, eid) {
        if( cid != null ) { this.email.customer_id = cid; }
        if( eid != null ) { this.email.email_id = eid; }
        if( this.email.email_id > 0 ) {
            this.email.sections._buttons.buttons.delete.visible = 'yes';
            this.email.sections._buttons.buttons.password.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.customers.emailGet', 
                {'tnid':M.curTenantID, 'customer_id':this.email.customer_id, 
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
            M.alert("Invalid email address");
            return false;
        }
        // Check if email address changed
//      if( e != this.email.fieldValue('emails', 'address', this.email.sections._email.fields.address) ) {
//          var rsp = M.api.getJSONCb('ciniki.customers.emailSearch', {'tnid':M.curTenantID, 
//              'customer_id':M.ciniki_customers_edit.email.customer_id, 'email':e}, function(rsp) {
//                  if( rsp.stat != 'ok' ) {
//                      M.api.err(rsp);
//                      return false;
//                  } 
//                  if( rsp.email != null ) {
//                      M.alert("Email address already exists");
//                      return false;
//                  }
//                  M.ciniki_customers_edit.saveEmailFinish();
//              });
//      } else {
            this.saveEmailFinish();
//      }
    };

    this.saveEmailFinish = function() {
        if( this.email.email_id > 0 ) {
            var c = this.email.serializeForm('no');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.customers.emailUpdate', 
                    {'tnid':M.curTenantID, 
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
                {'tnid':M.curTenantID, 
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
                M.alert("The password must be a minimum of 8 characters long");
                return false;
            }
            else {
                M.api.postJSONCb('ciniki.customers.customerSetPassword',
                    {'tnid':M.curTenantID, 'customer_id':this.email.customer_id,
                        'email_id':this.email.email_id}, 'newpassword=' + encodeURIComponent(np), 
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {    
                            M.api.err(rsp);
                            return false;
                        }
                        M.alert("Password has been set");
                    });
            }
        }
    };

    this.deleteEmail = function(customerID, emailID) {
        M.confirm("Are you sure you want to remove this email?",null,function() {
            var rsp = M.api.getJSONCb('ciniki.customers.emailDelete', 
                {'tnid':M.curTenantID, 'customer_id':M.ciniki_customers_edit.email.customer_id, 
                'email_id':M.ciniki_customers_edit.email.email_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_edit.email.close();
                });
        });
    };

    this.showPhoneEdit = function(cb, cid, pid) {
        if( cid != null ) { this.phone.customer_id = cid; }
        if( pid != null ) { this.phone.phone_id = pid; }
        if( this.phone.phone_id > 0 ) {
            this.phone.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.customers.phoneGet', 
                {'tnid':M.curTenantID, 'customer_id':this.phone.customer_id, 
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
                    {'tnid':M.curTenantID, 
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
                {'tnid':M.curTenantID, 
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
        M.confirm("Are you sure you want to remove this phone number?",null,function() {
            var rsp = M.api.getJSONCb('ciniki.customers.phoneDelete', 
                {'tnid':M.curTenantID, 'customer_id':M.ciniki_customers_edit.phone.customer_id, 
                'phone_id':M.ciniki_customers_edit.phone.phone_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_edit.phone.close();
                });
        });
    };

    this.showLinkEdit = function(cb, cid, eid) {
        if( cid != null ) { this.link.customer_id = cid; }
        if( eid != null ) { this.link.link_id = eid; }
        if( this.link.link_id > 0 ) {
            this.link.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.customers.linkGet', 
                {'tnid':M.curTenantID, 'customer_id':this.link.customer_id, 
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
                    {'tnid':M.curTenantID, 
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
                {'tnid':M.curTenantID, 
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
        M.confirm("Are you sure you want to remove this link?",null,function() {
            var rsp = M.api.getJSONCb('ciniki.customers.linkDelete', 
                {'tnid':M.curTenantID, 'customer_id':M.ciniki_customers_edit.link.customer_id, 
                'link_id':M.ciniki_customers_edit.link.link_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_edit.link.close();
                });
        });
    };

    this.lookupLatLong = function() {
        M.startLoad();
        if( document.getElementById('googlemaps_js') == null) {
            var script = document.createElement("script");
            script.id = 'googlemaps_js';
            script.type = "text/javascript";
            script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curTenant.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_customers_edit.lookupGoogleLatLong";
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
                M.alert('Geocode was not successful for the following reason: ' + status);
            }
        }); 
        M.stopLoad();
    };
}
