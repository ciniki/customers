//
function ciniki_customers_ifb() {

    this.customerStatus = {
        '10':'Active', 
        '50':'Suspended', 
        '60':'Deleted', 
        };
    //
    // The main menu panel
    //
    this.menu = new M.panel('Customers', 'ciniki_customers_ifb', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.customers.ifb.menu');
    this.menu.data = {};
    this.menu.sections = {
        'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':2, 
            'hint':'customer name', 'noData':'No customers found',
            'headerValues':['Customer', 'Status'],
            },
        'customers':{'label':'Customers', 'num_cols':2, 'type':'simplegrid', 
            'headerValues':['Customer', 'Type'],
            'visible':function() {return (M.ciniki_customers_ifb.menu.list != 'recent' ? 'yes' : 'no'); },
            'headerValues':['Name', 'Type'],
            'noData':'No customers',
            },
        };
    this.menu.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 'start_needle':encodeURIComponent(value), 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_customers_ifb.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_ifb.menu.panelUID + '_' + s), rsp.customers); 
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
        return 'M.ciniki_customers_ifb.customer.open(\'M.ciniki_customers_ifb.menu.open();\',\'' + d.id + '\');'; 
    }
/*    this.menu.liveSearchResultRowStyle = function(s, f, i, d) {
        if( M.curTenant.customers.settings['ui-colours-customer-status-' + d.status] != null ) {
            return 'background: ' + M.curTenant.customers.settings['ui-colours-customer-status-' + d.status];
        }
    } */
    this.menu.liveSearchSubmitFn = function(s, search_str) {
        M.ciniki_customers_ifb.search.open('M.ciniki_customers_ifb.menu.open();', search_str);
    }
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'customers' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.type_text;
            }
        }
    };
    this.menu.rowFn = function(s, i, d) { 
        if( s == 'customers' ) {
            return 'M.ciniki_customers_ifb.customer.open(\'M.ciniki_customers_ifb.menu.open();\',\'' + d.id + '\');'; 
        }
    };
    this.menu.open = function(cb) {
        //
        // Grab list of recently updated customers
        //
        M.api.getJSONCb('ciniki.customers.customerList', {'tnid':M.curTenantID, 'latest':'yes', 'limit':25}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_ifb.menu;
            p.data = rsp; 
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The customer view panel
    //
    this.customer = new M.panel('Customers', 'ciniki_customers_ifb', 'customer', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.ifb.customer');
    this.customer.customer_id = 0;
    this.customer.data = {};
    this.customer.ctype = 'individual';
    this.customer.module_data = [];
    this.customer.sections = {
        'details1':{'label':'', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            },
        'details2':{'label':'', 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'cellClasses':['label', ''],
            },
        'details3':{'label':'', 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'cellClasses':['label', ''],
            },
        'children':{'label':'Employees', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            },
        'data_tabs':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
        'data_records':{'label':'', 'type':'simplegrid'},
        'data_buttons':{'label':'', 'buttons':{}},
        };
    this.customer.cellValue = function(s, i, j, d) {
        if( s == 'details1' || s == 'details2' || s == 'details3' ) {   
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'data_records' ) {
            return eval(this.module_data[this.sections.data_tabs.selected].cellValues[j]);
        }
    }
    this.customer.rowFn = function(s, i, d) {
        if( s == 'data_records' ) {
            return eval(this.module_data[this.sections.data_tabs.selected].rowFn);
        }
        return '';
    }
    this.customer.switchTab = function(t) { 
        this.sections.data_tabs.selected = t;
        this.sections.data_records = this.module_data[t];
        this.sections.data_buttons.buttons = this.module_data[t].buttons != null ? this.module_data[t].buttons : {};
        this.sections.data.data_records = this.module_data[i].records;

        this.sections.refreshSection('data_records');
        this.sections.refreshSection('data_buttons');
    }
    this.customer.open = function(cb, cid) {
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            console.log(rsp);
            var p = M.ciniki_customers_ifb.customer;
            p.data = rsp; 
            if( rsp.customer.type == 10 ) {
                p.sections.details1.label = 'Customer';
                p.sections.details1.visible = 'yes';
                p.sections.details2.visible = 'no';
                p.sections.details3.visible = 'no';
                p.sections.children.visible = 'no';
                p.data.details1 = rsp.customer_details;
                p.data.details2 = {};
            } 
            else if( rsp.customer.type == 20 ) {
                p.sections.details1.label = 'Family';
                p.sections.details1.visible = 'yes';
                p.sections.details2.label = 'Parents/Guardians';
                p.sections.details2.visible = 'yes';
                p.sections.details3.visible = 'no';
                p.sections.children.label = 'Children';
                p.sections.children.visible = 'yes';
                p.data.details1 = rsp.family_details;
                p.data.details2 = rsp.parent_details;
            }
            else if( rsp.customer.type == 21 ) {
                p.sections.details1.label = 'Parent';
                p.sections.details1.visible = 'yes';
                p.sections.details2.label = 'Family';
                p.sections.details2.visible = 'yes';
                p.sections.details3.visible = 'no';
                p.data.details1 = rsp.customer_details;
                p.data.details2 = rsp.family_details;
            }
            else if( rsp.customer.type == 22 ) {
                p.sections.details1.label = 'Child';
                p.sections.details1.visible = 'yes';
                p.sections.details2.label = 'Family';
                p.sections.details2.visible = 'yes';
                p.sections.details3.label = 'Parents';
                p.sections.details3.visible = 'yes';
                p.data.details1 = rsp.customer_details;
                p.data.details2 = rsp.family_details;
                p.data.details3 = rsp.parent_details;
            }
            else if( rsp.customer.type == 30 ) {
                p.sections.details1.label = 'Business';
                p.sections.details1.visible = 'yes';
                p.sections.details2.label = 'Administrator';
                p.sections.details2.visible = 'yes';
                p.sections.details3.visible = 'no';
                p.sections.children.label = 'Employees';
                p.sections.children.visible = 'yes';
                p.data.details1 = rsp.customer_details;
                p.data.details2 = rsp.admin_details;
            }
            else if( rsp.customer.type == 31 ) {
                p.sections.details1.label = 'Administrator';
                p.sections.details1.visible = 'yes';
                p.sections.details2.label = 'Business';
                p.sections.details2.visible = 'yes';
                p.sections.details3.visible = 'no';
                p.data.details1 = rsp.customer_details;
                p.data.details2 = rsp.business_details;
            }
            else if( rsp.customer.type == 32 ) {
                p.sections.details1.label = 'Employee';
                p.sections.details1.visible = 'yes';
                p.sections.details2.label = 'Business';
                p.sections.details2.visible = 'yes';
                p.sections.details3.visible = 'no';
                p.data.details1 = rsp.customer_details;
                p.data.details2 = rsp.business_details;
            }
            p.sections.data_tabs.visible = 'no';
            p.sections.data_tabs.visible = 'no';
            if( p.module_data != null && p.module_data.length > 0 ) {
                p.sections.data_tabs.selected = '';
                p.sections.data_tabs.tabs = {};
                for(var i in rsp.module_data) {
                    if( p.sections.data_tabs.selected == '' ) {
                        p.sections.data_tabs.selected = i;
                    }
                    p.sections.data_tabs.tabs[i] = {'label':rsp.module_data[i].tab, 'fn':'M.ciniki_customers_ifb.customer.switchTab(' + i + ');'};
                }
            }
            p.refresh();
            p.show(cb);
            if( p.sections.data_tabs.selected != '' ) {
                p.switchTab(p.sections.data_tabs.selected);
            }
        });
    }
    this.customer.addButton('edit', 'Edit', 'M.ciniki_customers_ifb.edit.open(\'M.ciniki_customers_ifb.customer.open();\',M.ciniki_customers_ifb.customer.customer_id);');
    this.customer.addClose('Back');

    //
    // The customer edit panel
    //
    this.edit = new M.panel('Customers', 'ciniki_customers_ifb', 'edit', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.ifb.edit');
    this.edit.customer_id = 0;
    this.edit.formtab = '10';
    this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{
        'individual':{'label':'Individual', 'field_id':10, 'form':'individual'},
        'family':{'label':'Family', 'field_id':20, 'form':'family'},
        'parent':{'label':'Parent', 'field_id':21, 'form':'parent'},
        'child':{'label':'Child', 'field_id':22, 'form':'child'},
        'business':{'label':'Business', 'field_id':30, 'form':'business'},
        'admin':{'label':'Admin', 'field_id':31, 'form':'admin'},
        'employee':{'label':'Employee', 'field_id':32, 'form':'employee'},
//        'person':{'label':'Person', 'field_id':1, 'form':'person'},
//        'business':{'label':'Business', 'field_id':2, 'form':'business'},
        }};
    this.edit.forms = {};
    // Individual
    this.edit.forms.individual = {
        'name':{'label':'', 'aside':'yes', 'fields':{
            'status':{'label':'Status', 'type':'toggle', 'none':'yes', 'toggles':this.customerStatus},
            'eid':{'label':'Customer ID', 'type':'text', 'livesearch':'yes',
                'active':function() {return M.modFlagSet('ciniki.customers', 0x0800); },
                },
            'prefix':{'label':'Title', 'type':'text', 'hint':'Mr., Ms., Dr., ...'},
            'first':{'label':'First', 'type':'text', 'livesearch':'yes',},
            'middle':{'label':'Middle', 'type':'text'},
            'last':{'label':'Last', 'type':'text', 'livesearch':'yes',},
            'birthdate':{'label':'Birthday', 'type':'date', 'separator':'yes',
                'active':function() {return M.modFlagSet('ciniki.customers', 0x8000); },
                },
            'link1_url':{'label':'Website', 'active':'yes', 'type':'text'},
            }},
        'emails':{'label':'Email', 'aside':'yes', 'fields':{
            'primary_email':{'label':'Primary Email', 'type':'text', 'separator':'yes'},
            'primary_email_flags':{'label':'Options', 'type':'flags', 'flags':{}},
            'secondary_email':{'label':'Secondary Email', 'type':'text'},
            }},
        'phones':{'label':'Phone Numbers', 'aside':'yes', 'fields':{
            'cell_phone':{'label':'Cell', 'type':'text', 'separator':'yes'},
            'home_phone':{'label':'Home', 'type':'text'},
            'work_phone':{'label':'Work', 'type':'text'},
            'fax_phone':{'label':'Fax', 'type':'text'},
            }},
        '_subscriptions':{'label':'Subscriptions', 'aside':'yes', 'fields':{
            'subscriptions':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':{}},
            }},
        '_connection':{'label':'How did you hear about us?', 'aside':'yes', 'active':'no', 'fields':{
            'connection':{'label':'', 'hidelabel':'yes', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
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
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_customers_ifb.customer.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.ciniki_customers_ifb.edit.customer_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_customers_ifb.customer.remove();'},
            }},
        };
    // Families
    this.edit.forms.family = {
        'name':{'label':'Family Name', 'aside':'yes', 
            'fields':{
                'company':{'label':'', 'hidelabel':'', 'type':'text'},
            }},
        '_connection':this.edit.forms.individual._connection,
        'parents':{'label':'Parents/Guardians', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Parent',
            'addFn':'M.ciniki_customers_ifb.customer.open("M.ciniki_customer_ifb.customer.open();",0,21);',
            },
        'children':{'label':'Children', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Children',
            'addFn':'M.ciniki_customers_ifb.customer.open("M.ciniki_customer_ifb.customer.open();",0,22);',
            },
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.forms.parent = {
        '_family':{'label':'Family', 'aside':'yes', 
            'addTxt':'Add Family',
            'addFn':'console.log("TEST");',
            'fields':{
                'family_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':{}},
            }},
        'name':this.edit.forms.individual.name,
        'address1':this.edit.forms.individual.address1,
        'address2':this.edit.forms.individual.address2,
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.forms.child = {
        '_family':{'label':'Family', 'aside':'yes', 
            'addTxt':'Add Family',
            'addFn':'console.log("TEST");',
            'fields':{
                'family_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':{}},
            }},
        'name':this.edit.forms.individual.name,
        'address1':this.edit.forms.individual.address1,
        'address2':this.edit.forms.individual.address2,
        '_buttons':this.edit.forms.individual._buttons,
        };
    // Business Forms
    this.edit.forms.business = {
        'name':{'label':'Business Name', 'aside':'yes', 'fields':{
            'company':{'label':'', 'hidelabel':'', 'type':'text'},
            }},
        '_connection':this.edit.forms.individual._connection,
        'address1':this.edit.forms.individual.address1,
        'address2':this.edit.forms.individual.address2,
        'parents':{'label':'Admins', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Admin',
            'addFn':'M.ciniki_customers_ifb.customer.open("M.ciniki_customer_ifb.customer.open();",0,31);',
            },
        'children':{'label':'Employees', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Employee',
            'addFn':'M.ciniki_customers_ifb.customer.open("M.ciniki_customer_ifb.customer.open();",0,32);',
            },
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.forms.admin = {
        '_business':{'label':'Business', 'aside':'yes', 'fields':{
            'business_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':{}},
            'department':{'label':'Department', 'type':'text'},
            'title':{'label':'Title', 'type':'text'},
            }},
        'name':this.edit.forms.individual.name,
        'address1':this.edit.forms.individual.address1,
        'address2':this.edit.forms.individual.address2,
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.forms.employee = {
        '_business':{'label':'Business', 'aside':'yes', 'fields':{
            'business_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':{}},
            'department':{'label':'Department', 'type':'text'},
            'title':{'label':'Title', 'type':'text'},
            }},
        'name':this.edit.forms.individual.name,
        'address1':this.edit.forms.individual.address1,
        'address2':this.edit.forms.individual.address2,
        '_buttons':this.edit.forms.individual._buttons,
        };
    this.edit.sections = this.edit.forms.individual;
    this.edit.fieldValue = function(s, i, d) {
        return this.data[i];
    }
    this.edit.open = function(cb, cid, type) {
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.customers.customerGet', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_ifb.edit;
            p.data = rsp.customer; 
            if( cid != null && cid == 0 && type != null ) {
                p.data.type = type;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function(cb) {
        
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_customers_ifb.edit.save();');
    this.edit.addClose('Cancel');

    //
    // The search results panel
    //
    this.search = new M.panel('Search Results', 'ciniki_customers_ifb', 'search', 'mc', 'medium', 'sectioned', 'ciniki.customers.ifb.search');
    this.search.search_str = '';
    this.search.sections = {
        'customers':{'label':'', 'type':'simplegrid', 'num_cols':2, 
            'headerValues':['Name', 'Type'], 
            'sortable':'yes'},
    }
    this.search.noData = function() { return 'No ' + this.search_type + ' found'; }
    this.search.sectionData = function(s) { return this.data; }
    this.search.cellValue = function(s, i, j, d) { 
        switch(j) {
            case 0: return d.display_name;
            case 1: return d.type;
        }
    }
    this.search.rowFn = function(s, i, d) { 
        return 'M.ciniki_customers_ifb.customer.open(\'M.ciniki_customers_ifb.search.open();\',\'' + d.id + '\');';
    }
    this.search.open = function(cb, ss) {
        this.search_str = ss;
        M.api.getJSONCb('ciniki.customers.searchFull', {'tnid':M.curTenantID, 'start_needle':this.search_str}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_ifb.search;
            p.data = rsp.customers;
            p.refresh();
            p.show(cb);
        });
    }
    this.search.addClose('Back');


    //
    // The tools panel
    //
    this.tools = new M.panel('Customer Tools', 'ciniki_customers_ifb', 'tools', 'mc', 'narrow', 'sectioned', 'ciniki.customers.ifb.tools');
    this.tools.data = {};
    this.tools.sections = {
        'reports':{'label':'Reports', 'list':{
            'onhold':{'label':'On Hold', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_ifb.tools.show();\',\'mc\',{\'status\':\'40\'});'},
            'suspended':{'label':'Suspended', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_ifb.tools.show();\',\'mc\',{\'status\':\'50\'});'},
            'deleted':{'label':'Deleted', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_ifb.tools.show();\',\'mc\',{\'status\':\'60\'});'},
            'birthdays':{'label':'Birthdays', 
                'visible':function() {return M.modFlagSet('ciniki.customers', 0x8000); },
                'fn':'M.startApp(\'ciniki.customers.birthdays\',null,\'M.ciniki_customers_ifb.tools.show();\');'},
            }},
        '_connections':{'label':'', 'list':{
            'connection':{'label':'Connections', 'fn':'M.startApp(\'ciniki.customers.connections\',null,\'M.ciniki_customers_ifb.tools.show();\')'},
            }},
        'tools':{'label':'Cleanup', 'list':{
            'blank':{'label':'Find Blank Names', 'fn':'M.startApp(\'ciniki.customers.blanks\', null, \'M.ciniki_customers_ifb.tools.show();\');'},
            'duplicates':{'label':'Find Duplicates', 'fn':'M.startApp(\'ciniki.customers.duplicates\', null, \'M.ciniki_customers_ifb.tools.show();\');'},
            'salesreps':{'label':'Sales Reps', 'visible':'no', 'fn':'M.startApp(\'ciniki.customers.salesreps\', null, \'M.ciniki_customers_ifb.tools.show();\');'},
        }},
        'download':{'label':'Export (Advanced)', 'list':{
            'export':{'label':'Export to Excel', 'fn':'M.startApp(\'ciniki.customers.download\',null,\'M.ciniki_customers_ifb.tools.show();\',\'mc\',{});'},
            'exportcsvcontacts':{'label':'Contacts to Excel', 'fn':'M.ciniki_customers_ifb.tools.exportCSVContacts();'},
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_ifb', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        if( args.next != null && args.next != '' ) {
            this.customer.nextFn = args.next;
        //    this.customer.sections._buttons.buttons.save.label = 'Next';
            this.edit.rightbuttons.save.icon = 'next';
            this.edit.rightbuttons.save.label = 'Next';
        } else {
            this.customer.nextFn = null;
        //    this.customer.sections._buttons.buttons.save.label = 'Save';
            this.edit.rightbuttons.save.icon = 'save';
            this.edit.rightbuttons.save.label = 'Save';
        }
        if( args.search != null && args.search != '' ) {
            this.search.open(cb, args.search);
        } else if( args.edit_id != null ) {
            this.edit.open(cb, args.edit_id);
        } else if( args.customer_id != null && args.customer_id > 0 ) {
            this.customer.open(cb, args.customer_id);
        } else {
            this.menu.open(cb);
        }
    }
}
