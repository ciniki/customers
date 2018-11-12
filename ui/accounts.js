//
function ciniki_customers_accounts() {

    this.customerStatus = {
        '10':'Active', 
        '50':'Suspended', 
        '60':'Deleted', 
        };
    //
    // The main menu panel
    //
    this.menu = new M.panel('Customers', 'ciniki_customers_accounts', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.customers.accounts.menu');
    this.menu.data = {};
    this.menu.sections = {
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'individuals', 'tabs':{
            'individuals':{'label':'Individuals', 'fn':'M.ciniki_customers_accounts.menu.switchTab("individuals");'},
            'families':{'label':'Families', 'fn':'M.ciniki_customers_accounts.menu.switchTab("families");'},
            'businesses':{'label':'Businesses', 'fn':'M.ciniki_customers_accounts.menu.switchTab("businesses");'},
            }},
        'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':2, 
            'hint':'customer name', 'noData':'No customers found',
            'headerValues':['Customer', 'Status'],
            },
        'accounts':{'label':'Accounts', 'num_cols':2, 'type':'simplegrid', 
            'visible':function() {return (M.ciniki_customers_accounts.menu.sections._tabs.selected != 'reports' ? 'yes' : 'no'); },
            'headerValues':['Name', 'Type'],
            'noData':'No accounts',
            'addTxt':'',
            'addFn':'',
            },
        };
    this.menu.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.customers.accountSearch', {'tnid':M.curTenantID, 'start_needle':encodeURIComponent(value), 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_customers_accounts.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_accounts.menu.panelUID + '_' + s), rsp.accounts); 
                });
            return true;
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.type_text;
            }
        }
        return '';
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.ciniki_customers_accounts.account.open(\'M.ciniki_customers_accounts.menu.open();\',\'' + d.id + '\');'; 
    }
/*    this.menu.liveSearchResultRowStyle = function(s, f, i, d) {
        if( M.curTenant.customers.settings['ui-colours-customer-status-' + d.status] != null ) {
            return 'background: ' + M.curTenant.customers.settings['ui-colours-customer-status-' + d.status];
        }
    } */
    this.menu.liveSearchSubmitFn = function(s, search_str) {
        M.ciniki_customers_accounts.search.open('M.ciniki_customers_accounts.menu.open();', search_str);
    }
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'accounts' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.type_text;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) { 
        if( s == 'accounts' ) {
            return 'M.ciniki_customers_accounts.account.open(\'M.ciniki_customers_accounts.menu.open();\',\'' + d.id + '\');'; 
        }
    }
    this.menu.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.open();
    }
    this.menu.open = function(cb) {
        if( this.sections._tabs.selected == 'individuals' ) {
            this.sections.accounts.addTxt = 'Add Individual';
            this.sections.accounts.addFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.menu.open();\',0,10,0,\'\');';
        } else if( this.sections._tabs.selected == 'families' ) {
            this.sections.accounts.addTxt = 'Add Family';
            this.sections.accounts.addFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.menu.open();\',0,20,0,\'\');';
        } else if( this.sections._tabs.selected == 'businesses' ) {
            this.sections.accounts.addTxt = 'Add Business';
            this.sections.accounts.addFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.menu.open();\',0,30,0,\'\');';
        }
        //
        // Grab list of recently updated customers
        //
        if( this.sections._tabs.selected == 'individuals' || this.sections._tabs.selected == 'families' || this.sections._tabs.selected == 'businesses' ) {
            M.api.getJSONCb('ciniki.customers.accountList', {'tnid':M.curTenantID, 'type':this.sections._tabs.selected}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                var p = M.ciniki_customers_accounts.menu;
                p.data = rsp; 
                p.refresh();
                p.show(cb);
            });
        }
    }
    this.menu.addClose('Back');

    //
    // The account panel
    //
    this.account = new M.panel('Account', 'ciniki_customers_accounts', 'account', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.accounts.account');
    this.account.customer_id = 0;
    this.account.data = {};
    this.account.data_tabs = {};
    this.account.selected_data_tab = '';
    this.account.ctype = 'individual';
    this.account.cbStacked = 'yes';
    this.account.module_data = [];
    //
    // The sections are setup in the open function depending on what
    // additional information should be shown in tabs for the customer.
    //
    this.account.sections = {};
    this.account.cellValue = function(s, i, j, d) {
        if( s == 'account_details' || s == 'customer_details' ) {   
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        } else if( s == 'account_name' || s == 'parents' || s == 'children' ) {
            switch(j) {
                case 0: return d.display_name;
            }
        } else if( this.sections[s].cellValues != null ) {
            return eval(this.sections[s].cellValues[j]);
        }
        return '';
    }
    this.account.rowClass = function(s, i, d) {
        if( (s == 'account_name' || s == 'parents' || s == 'children') && this.customer_id == d.id ) {
            return 'highlight';
        }
        return '';
    }
/*    this.account.editFn = function(s, i, d) {
        return '';
    } */
    this.account.rowFn = function(s, i, d) {
        if( s == 'account_name' || s == 'parents' || s == 'children' ) {
            return 'M.ciniki_customers_accounts.account.open(null,\'' + d.id + '\');';
        } else if( this.sections[s].editApp != null ) {
            return 'M.ciniki_customers_accounts.account.openDataApp(\'' + s + '\',\'' + i + '\');';
        }
        return '';
    }
    this.account.addDataFn = function(s, i) {
        var args = {};
        if( this.sections[s].addApp.args != null ) {
            for(var j in this.sections[s].addApp.args) {
                args[j] = eval(this.sections[s].addApp.args[j]);
            }
        }
        M.startApp(this.sections[s].addApp.app,null,'M.ciniki_customers_accounts.account.open();','mc',args);
    }
    this.account.openDataApp = function(s, i) {
        var args = {};
        var d = this.sections[s].data[i];
        if( this.sections[s].editApp.args != null ) {
            for(var j in this.sections[s].editApp.args) {
                args[j] = eval(this.sections[s].editApp.args[j]);
            }
        }
        M.startApp(this.sections[s].editApp.app,null,'M.ciniki_customers_accounts.account.open();','mc',args);
    }
    this.account.switchTab = function(t) { 
        this.selected_data_tab = t;
        this.sections.data_tabs.selected = t;
        for(var i in this.data.data_tabs) {
            if( this.data.data_tabs[i].id == t ) {
                for(var j in this.data.data_tabs[i].sections) {
                    this.sections[j].visible = 'yes';
                }
            } else {
                for(var j in this.data.data_tabs[i].sections) {
                    this.sections[j].visible = 'no';
                }
            }
        }
        this.refresh();
        this.show();
    }
    this.account.open = function(cb, cid) {
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.customers.accountDetails', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_accounts.account;
            p.data = rsp; 
            p.sections = {
                // Details for the individual, business or family
                'account_name':{'label':'', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
                    'editFn':function(s, i, d) {
                        return 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.account.open();\',' + M.ciniki_customers_accounts.account.data.account.id + ',null,null,\'\');';
                        },
                    },
                'account_details':{'label':'', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
                    'cellClasses':['label', ''],
                    },
                'parents':{'label':'', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
//                    'headerValues':['Admins'],
                    'noData':'No admins',
                    'editFn':function(s, i, d) {
                        return 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.account.open();\',' + d.id + ',null,null,\'\');';
                        },
                    'addTxt':'',
                    'addTopFn':'',
                    },
                'children':{'label':'', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
//                    'headerValues':['Employees'],
                    'noData':'No children',
                    'editFn':function(s, i, d) {
                        return 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.account.open();\',' + d.id + ',null,null,\'\');';
                        },
                    'addTxt':'',
                    'addTopFn':'',
                    },
                'customer_details':{'label':'', 'type':'simplegrid', 'num_cols':2,
                    'cellClasses':['label', ''],
                    },
                'data_tabs':{'label':'', 'type':'paneltabs', 'selected':p.selected_data_tab, 'tabs':{}},
                };
            if( rsp.data_tabs != null ) {   
                var found = 'no';
                for(var i in rsp.data_tabs) {
                    if( p.selected_data_tab == '' ) {
                        p.sections.data_tabs.selected = rsp.data_tabs[i].id;
                        p.selected_data_tab = rsp.data_tabs[i].id;
                        found = 'yes';
                    } else if( p.selected_data_tab == rsp.data_tabs[i].id ) {
                        found = 'yes';
                    }
                    p.sections.data_tabs.tabs[rsp.data_tabs[i].id] = {
                        'label':rsp.data_tabs[i].label, 
                        'fn':'M.ciniki_customers_accounts.account.switchTab("' + rsp.data_tabs[i].id + '");',
                        };
                    for(var j in rsp.data_tabs[i].sections) {
                        rsp.data_tabs[i].sections[j].visible = 'no';
                        if( rsp.data_tabs[i].sections[j].addTxt != null && rsp.data_tabs[i].sections[j].addApp != null ) {
                            rsp.data_tabs[i].sections[j].addFn = 'M.ciniki_customers_accounts.account.addDataFn(\'' + j + '\');';
                        }
                        p.data[j] = rsp.data_tabs[i].sections[j].data;
                        p.sections[j] = rsp.data_tabs[i].sections[j];
                    }
                }
                if( found == 'no' ) {
                    p.selected_data_tab = rsp.data_tabs[0].id;
                    p.sections.data_tabs.selected = rsp.data_tabs[0].id;
                }
            }
            if( rsp.data_tabs.length > 1 ) {
                p.sections.data_tabs.visible = 'yes';
                p.size = 'medium mediumaside';
            } else if( rsp.data_tabs.length == 1 ) {
                p.sections.data_tabs.visible = 'no';
                p.size = 'medium mediumaside';
            } else {
                p.sections.data_tabs.visible = 'no';
                p.size = 'medium';
            }
            if( rsp.account.type == 10 ) {
                p.sections.account_name.label = 'Customer';
                p.sections.parents.visible = 'no';
                p.sections.parents.noData = '';
                p.sections.children.visible = 'no';
                p.sections.children.noData = '';
            } else if( rsp.account.type == 20 ) {
                p.sections.account_name.label = 'Family';
                p.sections.parents.label = 'Parents/Guardians';
                p.sections.parents.visible = 'yes';
                p.sections.parents.noData = 'No parents';
                p.sections.parents.addTxt = 'Add Parent/Guardian';
                p.sections.parents.addTopFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.account.open();\',0,21,' + rsp.account.id + ',\'\');';
                p.sections.children.label = 'Children';
                p.sections.children.visible = 'yes';
                p.sections.children.noData = 'No children';
                p.sections.children.addTxt = 'Add Child';
                p.sections.children.addTopFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.account.open();\',0,22,' + rsp.account.id + ',\'\');';
            } else if( rsp.account.type == 30 ) {
                p.sections.account_name.label = 'Business';
                p.sections.parents.label = 'Admins';
                p.sections.parents.visible = 'yes';
                p.sections.parents.noData = 'No admins';
                p.sections.parents.addTxt = 'Add Admin';
                p.sections.parents.addTopFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.account.open();\',0,31,' + rsp.account.id + ',\'\');';
                p.sections.children.label = 'Employees';
                p.sections.children.visible = 'yes';
                p.sections.children.noData = 'No employees';
                p.sections.children.addTxt = 'Add Employee';
                p.sections.children.addTopFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.account.open();\',0,32,' + rsp.account.id + ',\'\');';
            }
            if( rsp.account.id != rsp.customer.id ) {
                p.sections.customer_details.visible = 'yes';
            } else {
                p.sections.customer_details.visible = 'no';
            }
            if( rsp.customer.type == 21 ) {
                p.sections.customer_details.label = 'Parent';
            } else if( rsp.customer.type == 22 ) {
                p.sections.customer_details.label = 'Child';
            } else if( rsp.customer.type == 31 ) {
                p.sections.customer_details.label = 'Admin';
            } else if( rsp.customer.type == 32 ) {
                p.sections.customer_details.label = 'Employee';
            } 
            p.refresh();
            p.show(cb);
            if( p.sections.data_tabs.selected != '' ) {
                p.switchTab(p.sections.data_tabs.selected);
            }
        });
    }
    this.account.addClose('Back');

    //
    // The customer edit panel
    //
    this.edit = new M.panel('Customers', 'ciniki_customers_accounts', 'edit', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.accounts.edit');
    this.edit.customer_id = 0;
    this.edit.subscriptions = null;
    this.edit.formtab = null;
    this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{
        'individual':{'label':'Individual', 'field_id':10, 'form':'individual'},
        'family':{'label':'Family', 'field_id':20, 'form':'family'},
        'parent':{'label':'Parent', 'field_id':21, 'form':'parent'},
        'child':{'label':'Child', 'field_id':22, 'form':'child'},
        'business':{'label':'Business', 'field_id':30, 'form':'business'},
        'admin':{'label':'Admin', 'field_id':31, 'form':'admin'},
        'employee':{'label':'Employee', 'field_id':32, 'form':'employee'},
        }};
    // The select option values for familys and businesses
    this.edit.families = {};
    this.edit.businesses = {};
    this.edit.forms = {};
    // Individual
    this.edit.forms.individual = {
        'name':{'label':'', 'aside':'yes', 'fields':{
            'status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.customerStatus},
            'eid':{'label':'Customer ID', 'type':'text', 'livesearch':'yes',
                'active':function() {return M.modFlagSet('ciniki.customers', 0x0800); },
                },
            'prefix':{'label':'Title', 'type':'text', 'hint':'Mr., Ms., Dr., ...'},
            'first':{'label':'First', 'type':'text', 'livesearch':'yes', 'livesearchcols':3},
            'middle':{'label':'Middle', 'type':'text'},
            'last':{'label':'Last', 'type':'text', 'livesearch':'yes', 'livesearchcols':3},
            'birthdate':{'label':'Birthday', 'type':'date', 'separator':'yes',
                'active':function() {return M.modFlagSet('ciniki.customers', 0x8000); },
                },
            'link1_url':{'label':'Website', 'active':'yes', 'type':'text'},
            'start_date':{'label':'Start Date', 'type':'date', 
                'active':function() {return M.modFlagSet('ciniki.customers', 0x04000000); },
                },
            }},
        'emails':{'label':'Email', 'aside':'yes', 'fields':{
            'primary_email':{'label':'Primary Email', 'type':'text', 'separator':'yes'},
            'primary_email_flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Web Login'}}},
            'secondary_email':{'label':'Secondary Email', 'type':'text'},
            }},
        'phones':{'label':'Phone Numbers', 'aside':'yes', 'fields':{
            'phone_cell':{'label':'Cell', 'type':'text', 'separator':'yes'},
            'phone_home':{'label':'Home', 'type':'text'},
            'phone_work':{'label':'Work', 'type':'text'},
            'phone_fax':{'label':'Fax', 'type':'text'},
            }},
        'mailing':{'label':'Mailing Address', 'fields':{
            'mailing_address1':{'label':'Street', 'type':'text', 'hint':''},
            'mailing_address2':{'label':'', 'type':'text'},
            'mailing_city':{'label':'City', 'type':'text', 'size':'small', 'livesearch':'yes'},
            'mailing_province':{'label':'Province/State', 'type':'text', 'size':'small'},
            'mailing_postal':{'label':'Postal/Zip', 'type':'text', 'hint':'', 'size':'small'},
            'mailing_country':{'label':'Country', 'type':'text', 'hint':'', 'size':'small'},
            }},
        'billing':{'label':'Billing Address', 'fields':{
            'mailing_flags1':{'label':'Same as Mailing', 'type':'flagtoggle', 'field':'mailing_flags', 'bit':0x02, 'default':'on',
                'off_fields':['billing_address1', 'billing_address2', 'billing_city', 'billing_province', 'billing_postal', 'billing_country'],
                },
            'billing_address1':{'label':'Street', 'type':'text', 'hint':'', 'visible':'no'},
            'billing_address2':{'label':'', 'type':'text', 'visible':'no'},
            'billing_city':{'label':'City', 'type':'text', 'size':'small', 'livesearch':'yes', 'visible':'no'},
            'billing_province':{'label':'Province/State', 'type':'text', 'size':'small', 'visible':'no'},
            'billing_postal':{'label':'Postal/Zip', 'type':'text', 'hint':'', 'size':'small', 'visible':'no'},
            'billing_country':{'label':'Country', 'type':'text', 'hint':'', 'size':'small', 'visible':'no'},
            }},
        '_connection':{'label':'How did you hear about us?', 'aside':'no', 'active':'no', 
            'active':function() {return M.modFlagSet('ciniki.customers', 0x4000); },
            'fields':{
                'connection':{'label':'', 'hidelabel':'yes', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            }},
        'subscriptions':{'label':'Subscriptions', 'visible':'no', 'fields':{},
            },
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'visible':function() { return M.ciniki_customers_accounts.edit.nextFn != '' ? 'no' : 'yes'; }, 'fn':'M.ciniki_customers_accounts.edit.save();'},
            'next':{'label':'Next', 'visible':function() { return M.ciniki_customers_accounts.edit.nextFn != '' ? 'yes' : 'no'; }, 'fn':'M.ciniki_customers_accounts.edit.save();'},
            'setpassword':{'label':'Set Password', 'visible':function() { return M.ciniki_customers_accounts.edit.customer_id > 0 && (M.ciniki_customers_accounts.edit.formtab == 'individual' || M.ciniki_customers_accounts.edit.formtab == 'parent' || M.ciniki_customers_accounts.edit.formtab == 'admin') ? 'yes' : 'no'; }, 'fn':'M.ciniki_customers_accounts.edit.setPassword();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.ciniki_customers_accounts.edit.customer_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_customers_accounts.edit.remove();'},
            }},
        };
    // Families
    this.edit.forms.family = {
        'name':{'label':'Family Name', 'aside':'yes', 
            'fields':{
                'company':{'label':'', 'hidelabel':'', 'type':'text'},
            }},
        'mailing':this.edit.forms.individual.mailing,
        'billing':this.edit.forms.individual.billing,
        '_connection':this.edit.forms.individual._connection,
        '_notes':this.edit.forms.individual._notes,
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.forms.parent = {
        '_family':{'label':'Family', 'aside':'yes', 
            'fields':{
                'parent_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':this.edit.families, 'complex_options':{'value':'id', 'name':'display_name'}},
            }},
        'name':this.edit.forms.individual.name,
        'emails':this.edit.forms.individual.emails,
        'phones':this.edit.forms.individual.phones,
        'mailing':this.edit.forms.individual.mailing,
        'billing':this.edit.forms.individual.billing,
        '_connection':this.edit.forms.individual._connection,
        'subscriptions':this.edit.forms.individual.subscriptions,
        '_notes':this.edit.forms.individual._notes,
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.forms.child = {
        '_family':{'label':'Family', 'aside':'yes', 
            'fields':{
                'parent_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':this.edit.families, 'complex_options':{'value':'id', 'name':'display_name'}},
            }},
        'name':this.edit.forms.individual.name,
        'emails':this.edit.forms.individual.emails,
        'phones':this.edit.forms.individual.phones,
        'mailing':this.edit.forms.individual.mailing,
        'billing':this.edit.forms.individual.billing,
        '_connection':this.edit.forms.individual._connection,
        'subscriptions':this.edit.forms.individual.subscriptions,
        '_notes':this.edit.forms.individual._notes,
        '_buttons':this.edit.forms.individual._buttons,
        };
    // Business Forms
    this.edit.forms.business = {
        'name':{'label':'Business Name', 'aside':'yes', 'fields':{
            'company':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        'emails':{'label':'Email', 'aside':'yes', 'fields':{
            'primary_email':{'label':'Primary Email', 'type':'text'},
            }},
//        '_connection':this.edit.forms.individual._connection,
        'phones':{'label':'Phone Numbers', 'aside':'yes', 'fields':{
            'phone_cell':{'label':'Cell', 'visible':'no', 'type':'text'},
            'phone_home':{'label':'Home', 'visible':'no', 'type':'text'},
            'phone_work':{'label':'Work', 'type':'text'},
            'phone_fax':{'label':'Fax', 'type':'text'},
            }},
        'mailing':this.edit.forms.individual.mailing,
        'billing':this.edit.forms.individual.billing,
        '_connection':this.edit.forms.individual._connection,
        '_notes':this.edit.forms.individual._notes,
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.forms.admin = {
        '_business':{'label':'Employer', 'aside':'yes', 'fields':{
            'parent_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':this.edit.businesses, 'complex_options':{'value':'id', 'name':'display_name'}},
            }},
        '_department':{'label':'', 'aside':'yes', 'fields':{
            'department':{'label':'Department', 'type':'text'},
            'title':{'label':'Title', 'type':'text'},
            }},
        'name':this.edit.forms.individual.name,
        'emails':this.edit.forms.individual.emails,
        'phones':this.edit.forms.individual.phones,
        'mailing':this.edit.forms.individual.mailing,
        'billing':this.edit.forms.individual.billing,
        '_connection':this.edit.forms.individual._connection,
        'subscriptions':this.edit.forms.individual.subscriptions,
        '_notes':this.edit.forms.individual._notes,
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.forms.employee = {
        '_business':{'label':'Employer', 'aside':'yes', 'fields':{
            'parent_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':this.edit.businesses, 'complex_options':{'value':'id', 'name':'display_name'}},
            }},
        '_department':{'label':'', 'aside':'yes', 'fields':{
            'department':{'label':'Department', 'type':'text'},
            'title':{'label':'Title', 'type':'text'},
            }},
        'name':this.edit.forms.individual.name,
        'emails':this.edit.forms.individual.emails,
        'phones':this.edit.forms.individual.phones,
        'mailing':this.edit.forms.individual.mailing,
        'billing':this.edit.forms.individual.billing,
        '_connection':this.edit.forms.individual._connection,
        'subscriptions':this.edit.forms.individual.subscriptions,
        '_notes':this.edit.forms.individual._notes,
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.sectionData = function(s) {
        if( s == 'subscriptions' ) {
            return this.subscriptions;
        }
        return this.data[s];
    }
    this.edit.fieldValue = function(s, i, d) {
        return this.data[i];
    }
    this.edit.liveSearchCb = function(s, i, value) {
        if( i == 'first' || i == 'last' || i == 'company' ) {
            M.api.getJSONBgCb('ciniki.customers.customerSearch', 
                {'tnid':M.curTenantID, 'start_needle':value, 'field':i, 'limit':25}, function(rsp) { 
                    M.ciniki_customers_accounts.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_accounts.edit.panelUID + '_' + i), rsp.customers); 
                });
        } else if( i == 'mailing_city' || i == 'billing_city' ) {
            M.api.getJSONBgCb('ciniki.customers.addressSearchQuick', 
                {'tnid':M.curTenantID, 'start_needle':value, 'limit':25}, function(rsp) { 
                    M.ciniki_customers_accounts.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_accounts.edit.panelUID + '_' + i), rsp.cities); 
                });
        } else if( i == 'connection' ) {
            M.api.getJSONBgCb('ciniki.customers.connectionSearch', 
                {'tnid':M.curTenantID, 'start_needle':value, 'field':i, 'limit':25}, function(rsp) { 
                    M.ciniki_customers_accounts.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_accounts.edit.panelUID + '_' + i), rsp.connections); 
                });
        }
    }
    this.edit.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'first' || f == 'last' || f == 'company' ) { 
            // FIXME: Remove when all searched return no subarray
            if( d.customer != null ) {
                switch(j) {
                    case 0: return d.parent_name;
                    case 1: return d.display_name;
                    case 2: return d.type_text;
                }
            } else {
                switch(j) {
                    case 0: return d.parent_name;
                    case 1: return d.display_name;
                    case 2: return d.type_text;
                }
            }
        }
        if( f == 'mailing_city' || f == 'billing_city' ) { 
            return d.city.name + ',' + d.city.province; 
        } else if( f == 'connection' ) {
            return d.connection.connection;
        }
        return '';
    }
    this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( f == 'eid' || f == 'first' || f == 'last' || f == 'company' ) { 
            if( d.customer != null ) {
                if( this.parent_id != null && this.parent_id > 0 ) {
                    return 'M.ciniki_customers_accounts.edit.open(null,\'' + d.customer.id + '\',null,\'' + this.parent_id + '\');';
                }
                return 'M.ciniki_customers_accounts.edit.open(null,' + d.customer.id + ',null,0);';
            } else {
                if( this.parent_id != null && this.parent_id > 0 ) {
                    return 'M.ciniki_customers_accounts.edit.open(null,\'' + d.id + '\',null,\'' + this.parent_id + '\');';
                }
                return 'M.ciniki_customers_accounts.edit.open(null,' + d.id + ',null,0);';
            }
        }
        else if( f == 'mailing_city' || f == 'billing_city' ) {
            return 'M.ciniki_customers_accounts.edit.updateCity(\'' + s + '\',\'' + escape(d.city.name) + '\',\'' + escape(d.city.province) + '\',\'' + escape(d.city.country) + '\');';
        }
        else if( f == 'connection' ) {
            return 'M.ciniki_customers_accounts.edit.updateConnection(\'' + s + '\',\'' + escape(d.connection.connection) + '\');';
        }
    }
    this.edit.updateCity = function(s, city, province, country) {
        if( s == 'mailing' ) {
            M.gE(this.panelUID + '_mailing_city').value = unescape(city);
            M.gE(this.panelUID + '_mailing_province').value = unescape(province);
            M.gE(this.panelUID + '_mailing_country').value = unescape(country);
            this.removeLiveSearch(s, 'mailing_city');
        } else if( s == 'billing' ) {
            M.gE(this.panelUID + '_billing_city').value = unescape(city);
            M.gE(this.panelUID + '_billing_province').value = unescape(province);
            M.gE(this.panelUID + '_billing_country').value = unescape(country);
            this.removeLiveSearch(s, 'billing_city');
        }
    }
    this.edit.updateConnection = function(s, connection) {
        M.gE(this.panelUID + '_connection').value = unescape(connection);
        this.removeLiveSearch(s, 'connection');
    }
    this.edit.fieldHistoryArgs = function(s, i) {
        if( s == 'mailing' && this.data.mailing_address_id > 0 ) {
            switch(i) {
                case 'mailing_address1': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.mailing_address_id, 'field':'address1'}};
                case 'mailing_address2': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.mailing_address_id, 'field':'address2'}};
                case 'mailing_city': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.mailing_address_id, 'field':'city'}};
                case 'mailing_province': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.mailing_address_id, 'field':'province'}};
                case 'mailing_postal': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.mailing_address_id, 'field':'postal'}};
                case 'mailing_country': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.mailing_address_id, 'field':'country'}};
            }
        } else if( s == 'billing' && this.data.billing_address_id > 0 ) {
            switch(i) {
                case 'billing_address1': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.billing_address_id, 'field':'address1'}};
                case 'billing_address2': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.billing_address_id, 'field':'address2'}};
                case 'billing_city': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.billing_address_id, 'field':'city'}};
                case 'billing_province': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.billing_address_id, 'field':'province'}};
                case 'billing_postal': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.billing_address_id, 'field':'postal'}};
                case 'billing_country': return {'method':'ciniki.customers.addressHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'address_id':this.data.billing_address_id, 'field':'country'}};
            }
        } else if( i.substring(0,13) == 'subscription_' ) {
            return {'method':'ciniki.subscriptions.getCustomerHistory', 'args':{'tnid':M.curTenantID, 
                'subscription_id':i.substring(13), 'customer_id':this.customer_id, 'field':'status'}};
        } else {
            return {'method':'ciniki.customers.customerHistory', 'args':{'tnid':M.curTenantID, 'customer_id':this.customer_id, 'field':i}};
        } 
    }
    this.edit.open = function(cb, cid, type, parent_id, nextFn) {
        if( cid != null ) { this.customer_id = cid; }
        if( nextFn != null ) { this.nextFn = nextFn; }
        var args = {'tnid':M.curTenantID, 'customer_id':this.customer_id}
        if( parent_id != null ) {
            args.parent_id = parent_id;
        }
        M.api.getJSONCb('ciniki.customers.customerGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_accounts.edit;
            p.data = rsp.customer; 
            p.subscriptions = rsp.subscriptions;
            p.formtab = null;
            p.formtab_field_id = null;
            p.forms.parent._family.fields.parent_id.options = rsp.families;
            p.forms.child._family.fields.parent_id.options = rsp.families;
            p.forms.admin._business.fields.parent_id.options = rsp.businesses;
            p.forms.employee._business.fields.parent_id.options = rsp.businesses;
            if( cid != null && cid == 0 && type != null ) {
                p.data.type = type;
                if( parent_id != null ) {
                    p.data.parent_id = parent_id;
                }
            }
            if( p.data.type == 30 ) {
                p.forms.business.phones.fields.phone_cell.visible = (rsp.customer.phone_cell != '' ? 'yes' : 'no');
                p.forms.business.phones.fields.phone_home.visible = (rsp.customer.phone_home != '' ? 'yes' : 'no');
//                p.forms.business.emails.active = (rsp.customer.primary_email != '' ? 'yes' : 'no');
            } else if( p.data.type == 20 ) {
                p.forms.business.emails.active = (rsp.customer.primary_email != '' ? 'yes' : 'no');
            } else {
                p.forms.individual.emails.active = 'yes';
                p.forms.parent.emails.active = 'yes';
                p.forms.child.emails.active = 'yes';
                p.forms.admin.emails.active = 'yes';
                p.forms.employee.emails.active = 'yes';
            }
            if( rsp.subscriptions != null && rsp.subscriptions.length > 0 ) {
                p.forms.individual.subscriptions.visible = 'yes';
                p.forms.parent.subscriptions.visible = 'yes';
                p.forms.child.subscriptions.visible = 'yes';
                p.forms.admin.subscriptions.visible = 'yes';
                p.forms.employee.subscriptions.visible = 'yes';
                p.forms.individual.subscriptions.fields = {};
                var i = 0;
                for(i in rsp.subscriptions) {
                    p.forms.individual.subscriptions.fields['subscription_' + rsp.subscriptions[i].id] = {'label':rsp.subscriptions[i].name, 
                        'type':'toggle', 'toggles':{'10':'Subscribed', '60':'Unsubscribed'}};
                    if( rsp.subscriptions[i].status == null && (rsp.subscriptions[i].flags&0x02) == 0x02 ) {
                        p.forms.individual.subscriptions.fields['subscription_' + rsp.subscriptions[i].id].default = 10;
                    } else {
                        p.data['subscription_' + rsp.subscriptions[i].id] = rsp.subscriptions[i].status;
                    }
                }
                p.forms.parent.subscriptions.fields = p.forms.individual.subscriptions.fields;
                p.forms.child.subscriptions.fields = p.forms.individual.subscriptions.fields;
                p.forms.admin.subscriptions.fields = p.forms.individual.subscriptions.fields;
                p.forms.employee.subscriptions.fields = p.forms.individual.subscriptions.fields;
            } else {
                // Hide the subscriptions section when no business subscription setup
                p.forms.individual.subscriptions.visible = 'no';
                p.forms.parent.subscriptions.visible = 'no';
                p.forms.child.subscriptions.visible = 'no';
                p.forms.admin.subscriptions.visible = 'no';
                p.forms.employee.subscriptions.visible = 'no';
            }
            p.showHideBilling();
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.setPassword = function() {
        var np = prompt("Please enter a new password for the customer: ");
        if( np != null ) {
            if( np.length < 8 ) {
                alert("The password must be a minimum of 8 characters long");
                return false;
            }
            else {
                M.api.postJSONCb('ciniki.customers.customerSetPassword',
                    {'tnid':M.curTenantID, 'customer_id':this.customer_id}, 'newpassword=' + M.eU(np), 
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
    // Setup the billing address based on mailing_flags
    this.edit.showHideBilling = function() {
        for(var i in this.forms) {
            if( this.forms[i].billing != null ) {
                if( (this.data.mailing_flags&0x02) == 0x02 ) {
                    this.forms[i].billing.fields.billing_address1.visible = 'no';
                    this.forms[i].billing.fields.billing_address2.visible = 'no';
                    this.forms[i].billing.fields.billing_city.visible = 'no';
                    this.forms[i].billing.fields.billing_province.visible = 'no';
                    this.forms[i].billing.fields.billing_postal.visible = 'no';
                    this.forms[i].billing.fields.billing_country.visible = 'no';
                } else {
                    this.forms[i].billing.fields.billing_address1.visible = 'yes';
                    this.forms[i].billing.fields.billing_address2.visible = 'yes';
                    this.forms[i].billing.fields.billing_city.visible = 'yes';
                    this.forms[i].billing.fields.billing_province.visible = 'yes';
                    this.forms[i].billing.fields.billing_postal.visible = 'yes';
                    this.forms[i].billing.fields.billing_country.visible = 'yes';
                }
            }
        }
        if( this.sections.billing != null ) {
            if( (this.data.mailing_flags&0x02) == 0x02 ) {
                this.sections.billing.fields.billing_address1.visible = 'no';
                this.sections.billing.fields.billing_address2.visible = 'no';
                this.sections.billing.fields.billing_city.visible = 'no';
                this.sections.billing.fields.billing_province.visible = 'no';
                this.sections.billing.fields.billing_postal.visible = 'no';
                this.sections.billing.fields.billing_country.visible = 'no';
            } else {
                this.sections.billing.fields.billing_address1.visible = 'yes';
                this.sections.billing.fields.billing_address2.visible = 'yes';
                this.sections.billing.fields.billing_city.visible = 'yes';
                this.sections.billing.fields.billing_province.visible = 'yes';
                this.sections.billing.fields.billing_postal.visible = 'yes';
                this.sections.billing.fields.billing_country.visible = 'yes';
            }
        }
    }
    this.edit.save = function(cb) {
        var unsubs = '';
        var subs = '';
        var sc = '';
        var uc = '';
        if( this.subscriptions != null && this.sections.subscriptions != null ) {
            for(i in this.subscriptions) {
                var fname = 'subscription_' + this.subscriptions[i].id;
                var o = this.fieldValue('subscriptions', fname, this.sections.subscriptions.fields[fname]);
                var n = this.formValue(fname);
                if( this.customer_id == 0 || (o != n && n > 0) ) {
                    if( n == 10 ) {
                        subs += sc + this.subscriptions[i].id; sc=',';
                    } else if( n == 60 ) {
                        unsubs += uc + this.subscriptions[i].id; uc=',';
                    }
                }   
            }
        }
        if( this.customer_id > 0 ) {
            var c = this.serializeForm('no');
            if( subs != '' ) { c += 'subscriptions=' + subs + '&'; }
            if( unsubs != '' ) { c += 'unsubscriptions=' + unsubs + '&'; }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.customers.customerUpdate', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    var p = M.ciniki_customers_accounts.edit;
                    if( p.nextFn != null && p.nextFn != '' ) {
                        eval(p.nextFn + '(' + p.customer_id + ');');
                    } else {
                        M.ciniki_customers_accounts.edit.close();
                    }
                });
            } else {
                if( this.nextFn != null && this.nextFn != '' ) {
                    eval(this.nextFn + '(' + this.customer_id + ');');
                } else {
                    this.close();
                }
            }
        } else {
            var c = this.serializeForm('yes');
            if( subs != '' ) { c += 'subscriptions=' + subs + '&'; }
            if( unsubs != '' ) { c += 'unsubscriptions=' + unsubs + '&'; }
            M.api.postJSONCb('ciniki.customers.customerAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                var p = M.ciniki_customers_accounts.edit;
                if( rsp.id > 0 ) {
                    if( p.nextFn != null && p.nextFn != '' ) {
                        // eval(p.next);
                        eval(p.nextFn + '(' + rsp.id + ');');
                    } else {
                        M.ciniki_customers_accounts.account.open(p.cb, rsp.id);
                    }
                } else {
                    p.close();
                }
            });
        }
    }
    this.edit.remove = function() {
        if( this.customer_id > 0 ) {
            if( confirm("Are you sure you want to remove this customer?  This will remove all subscriptions, phone numbers, email addresses, addresses and websites.") ) {
                M.api.getJSONCb('ciniki.customers.delete', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var customer_type = M.ciniki_customers_accounts.edit.formtab;
                    if( customer_type == 'individual' || customer_type == 'family' || customer_type == 'business' ) {
                        M.ciniki_customers_accounts.account.close();
                    } else {
                        M.ciniki_customers_accounts.edit.close();
                    }
                });
            }
        }
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_customers_accounts.edit.save();');
    this.edit.addClose('Cancel');

    //
    // The search results panel
    //
    this.search = new M.panel('Search Results', 'ciniki_customers_accounts', 'search', 'mc', 'medium', 'sectioned', 'ciniki.customers.accounts.search');
    this.search.search_str = '';
    this.search.sections = {
        'customers':{'label':'', 'type':'simplegrid', 'num_cols':3, 
            'headerValues':['Parent/Business', 'Name', 'Status'], 
            'sortable':'yes'},
    }
    this.search.noData = function() { return 'No ' + this.search_type + ' found'; }
    this.search.sectionData = function(s) { return this.data; }
    this.search.cellValue = function(s, i, j, d) { 
        switch(j) {
            case 0: return d.parent_name;
            case 1: return d.display_name;
            case 2: return d.type_text;
        }
    }
    this.search.rowFn = function(s, i, d) { 
        return 'M.ciniki_customers_accounts.account.open(\'M.ciniki_customers_accounts.search.open();\',\'' + d.id + '\');';
    }
    this.search.open = function(cb, ss) {
        if( ss != null ) { this.search_str = ss; }
        M.api.getJSONCb('ciniki.customers.searchFull', {'tnid':M.curTenantID, 'start_needle':this.search_str}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_accounts.search;
            p.data = rsp.customers;
            p.refresh();
            p.show(cb);
        });
    }
    this.search.addClose('Back');

    //
    // The change customer panel
    //
    this.changecustomer = new M.panel('Change Customer', 'ciniki_customers_accounts', 'changecustomer', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.accounts.changecustomer');
    this.changecustomer.customer_id = 0;
    this.changecustomer.cbStacked = 'yes';
    this.changecustomer.sections = {
        'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':3, 'aside':'yes', 
            'hint':'customer name', 'noData':'No customers found',
            'headerValues':['Parent/Business', 'Name', 'Status'], 
            },
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'newcustomer':{'label':'New Customer', 'fn':'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.changecustomer.open();\',0,10,0,M.ciniki_customers_accounts.changecustomer.nextFn);'},
            }},
        'account_name':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':'no', 
            'editFn':function(s, i, d) {
                return 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.changecustomer.open();\',' + M.ciniki_customers_accounts.changecustomer.data.account.id + ',null,null,\'\');';
                },
            },
        'parents':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':'no',
            'noData':'No admins',
            'sortable':'yes',
            'sortTypes':['text'],
            'editFn':function(s, i, d) {
                return 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.changecustomer.open();\',' + d.id + ',null,null,\'\');';
                },
            'addTopTxt':'',
            'addTopFn':'',
            },
        'children':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':'yes',
            'noData':'No children',
            'sortable':'yes',
            'sortTypes':['text'],
            'editFn':function(s, i, d) {
                return 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.changecustomer.open();\',' + d.id + ',null,null,\'\');';
                },
            'addTopTxt':'',
            'addTopFn':'',
            },
        };
    this.changecustomer.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 'start_needle':encodeURIComponent(value), 'limit':'15'}, 
                function(rsp) { 
                    M.ciniki_customers_accounts.changecustomer.liveSearchShow('search', null, M.gE(M.ciniki_customers_accounts.changecustomer.panelUID + '_' + s), rsp.customers); 
                });
            return true;
        }
    }
    this.changecustomer.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            switch(j) {
                case 0: return d.parent_name;
                case 1: return d.display_name;
                case 2: return d.type_text;
            }
        }
        return '';
    }
    this.changecustomer.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return this.nextFn + '(' + d.id + ');';
    }
    this.changecustomer.cellValue = function(s, i, j, d) {
        if( s == 'account_name' || s == 'parents' || s == 'children' ) {
            switch(j) {
                case 0: return d.display_name;
            }
        }
        return '';
    }
    this.changecustomer.rowFn = function(s, i, d) {
        if( s == 'account_name' || s == 'parents' || s == 'children' ) {
            return this.nextFn + '(' + d.id + ');';
        }
        return '';
    }
    this.changecustomer.open = function(cb, cid, nextFn) {
        if( cid != null ) { this.customer_id = cid; }
        if( nextFn != null ) { this.nextFn = nextFn; }
        if( this.customer_id > 0 ) {
            M.api.getJSONCb('ciniki.customers.accountDetails', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                var p = M.ciniki_customers_accounts.changecustomer;
                p.data = rsp; 
                if( rsp.account.type == 20 || rsp.account.type == 30 ) {
                    p.size = 'medium mediumaside';
                    p.sections.parents.visible = 'yes';
                    p.sections.children.visible = 'yes';
                    if( rsp.account.type == 20 ) {
                        p.sections.account_name.visible = 'yes';
                        p.sections.parents.label = 'Parents/Guardians';
                        p.sections.children.label = 'Children';
                        p.sections.parents.addTxt = 'Add Parent/Guardian';
                        p.sections.parents.addTopFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.changecustomer.open();\',0,21,' + rsp.account.id + ',M.ciniki_customers_accounts.changecustomer.nextFn);';
                        p.sections.children.addTxt = 'Add Child';
                        p.sections.children.addTopFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.changecustomer.open();\',0,22,' + rsp.account.id + ',M.ciniki_customers_accounts.changecustomer.nextFn);';
                    } else if( rsp.account.type == 30 ) {
                        p.sections.account_name.visible = 'yes';
                        p.sections.parents.label = 'Admins';
                        p.sections.children.label = 'Employees';
                        p.sections.parents.addTxt = 'Add Admin';
                        p.sections.parents.addTopFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.changecustomer.open();\',0,31,' + rsp.account.id + ',M.ciniki_customers_accounts.changecustomer.nextFn);';
                        p.sections.children.addTxt = 'Add Employee';
                        p.sections.children.addTopFn = 'M.ciniki_customers_accounts.edit.open(\'M.ciniki_customers_accounts.changecustomer.open();\',0,32,' + rsp.account.id + ',M.ciniki_customers_accounts.changecustomer.nextFn);';
                    }
                } else {
                    p.size = 'medium';
                    p.sections.parents.visible = 'no';
                    p.sections.children.visible = 'no';
                }
                p.refresh();
                p.show(cb);
            });
        } else {
            this.size = 'medium';
            this.sections.account_name.visible = 'no';
            this.sections.parents.visible = 'no';
            this.sections.children.visible = 'no';
            this.refresh();
            this.show();
        }
    }
    this.changecustomer.addClose('Cancel');

    //
    // The tools panel
    //
    this.tools = new M.panel('Customer Tools', 'ciniki_customers_accounts', 'tools', 'mc', 'narrow', 'sectioned', 'ciniki.customers.accounts.tools');
    this.tools.data = {};
    this.tools.sections = {
        'reports':{'label':'Reports', 'list':{
            'onhold':{'label':'On Hold', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_accounts.tools.show();\',\'mc\',{\'status\':\'40\'});'},
            'suspended':{'label':'Suspended', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_accounts.tools.show();\',\'mc\',{\'status\':\'50\'});'},
            'deleted':{'label':'Deleted', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_accounts.tools.show();\',\'mc\',{\'status\':\'60\'});'},
            'birthdays':{'label':'Birthdays', 
                'visible':function() {return M.modFlagSet('ciniki.customers', 0x8000); },
                'fn':'M.startApp(\'ciniki.customers.birthdays\',null,\'M.ciniki_customers_accounts.tools.show();\');'},
            }},
        '_connections':{'label':'', 'list':{
            'connection':{'label':'Connections', 'fn':'M.startApp(\'ciniki.customers.connections\',null,\'M.ciniki_customers_accounts.tools.show();\')'},
            }},
        'tools':{'label':'Cleanup', 'list':{
            'blank':{'label':'Find Blank Names', 'fn':'M.startApp(\'ciniki.customers.blanks\', null, \'M.ciniki_customers_accounts.tools.show();\');'},
            'duplicates':{'label':'Find Duplicates', 'fn':'M.startApp(\'ciniki.customers.duplicates\', null, \'M.ciniki_customers_accounts.tools.show();\');'},
            'salesreps':{'label':'Sales Reps', 'visible':'no', 'fn':'M.startApp(\'ciniki.customers.salesreps\', null, \'M.ciniki_customers_accounts.tools.show();\');'},
        }},
        'download':{'label':'Export (Advanced)', 'list':{
            'export':{'label':'Export to Excel', 'fn':'M.startApp(\'ciniki.customers.download\',null,\'M.ciniki_customers_accounts.tools.show();\',\'mc\',{});'},
            'exportcsvcontacts':{'label':'Contacts to Excel', 'fn':'M.ciniki_customers_accounts.tools.exportCSVContacts();'},
        }},
        };
    this.tools.exportCSVContacts = function() {
        var args = {'tnid':M.curTenantID, 'columns':'type::status::prefix::first::middle::last::suffix::company::split_phone_labels::split_emails::split_addresses'};
        M.api.openFile('ciniki.customers.customerListExcel', args);
    }
    this.tools.open = function(cb) {
        this.show(cb);
    }
    this.tools.addClose('Back');


    //
    // The main start function
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_accounts', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 



        if( args.next != null && args.next != '' ) {
            this.edit.rightbuttons.save.icon = 'next';
            this.edit.rightbuttons.save.label = 'Next';
        } else {
            this.edit.rightbuttons.save.icon = 'save';
            this.edit.rightbuttons.save.label = 'Save';
        }
        if( args.search != null && args.search != '' ) {
            this.search.open(cb, args.search);
        } else if( args.action != null && args.action == 'edit' && args.customer_id != null && args.customer_id > 0 ) {
            // Edit a customer record
            this.edit.open(cb, args.customer_id, null, null, args.next);
        } else if( args.action != null && args.action == 'change' && args.customer_id != null && args.customer_id == 0 && args.current_id != null && args.current_id > 0 ) {
            // Changing from a current customer to new customer
            this.changecustomer.open(cb, args.current_id, args.next);
        } else if( args.action != null && args.action == 'choose' ) {
            // Changing from a current customer to new customer
            this.changecustomer.open(cb, 0, args.next);
        } else if( args.action != null && args.action == 'change' && args.customer_id != null && args.customer_id == 0 ) {
            // No existing customer
            this.edit.open(cb, args.customer_id, null, args.parent_id, args.next);
        } else if( args.edit_id != null ) {
            this.edit.open(cb, args.edit_id, null, null, '');
        } else if( args.customer_id != null && args.customer_id == 0 ) {
            this.edit.open(cb, args.customer_id, args.type, null, args.next);
        } else if( args.customer_id != null && args.customer_id > 0 ) {
            this.account.open(cb, args.customer_id);
        } else {
            this.menu.open(cb);
        }
    }
}
