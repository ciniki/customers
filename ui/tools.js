//
// The following tools are originally designed to work with the new customer accounts (IFB)
//
function ciniki_customers_tools() {
    //
    // The main menu panel
    //
    this.menu = new M.panel('Customer Tools', 'ciniki_customers_tools', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.customers.tools.menu');
    this.menu.data = {};
    this.menu.sections = {
        'reports':{'label':'Reports', 'list':{
//            'onhold':{'label':'On Hold', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_tools.menu.show();\',\'mc\',{\'status\':\'40\'});'},
//            'suspended':{'label':'Suspended', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_tools.menu.show();\',\'mc\',{\'status\':\'50\'});'},
//            'deleted':{'label':'Deleted', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_tools.menu.show();\',\'mc\',{\'status\':\'60\'});'},
            'birthdays':{'label':'Birthdays', 
                'visible':function() {return M.modFlagSet('ciniki.customers', 0x8000); },
                'fn':'M.startApp(\'ciniki.customers.birthdays\',null,\'M.ciniki_customers_tools.menu.show();\');',
                },
            'connection':{'label':'Connections', 
                'visible':function() {return M.modFlagSet('ciniki.customers', 0x4000); },
                'fn':'M.startApp(\'ciniki.customers.connections\',null,\'M.ciniki_customers_tools.menu.show();\')',
                },
            }},
        'menu':{'label':'Cleanup', 'list':{
//            'blank':{'label':'Find Blank Names', 'fn':'M.startApp(\'ciniki.customers.blanks\', null, \'M.ciniki_customers_tools.menu.show();\');'},
            'duplicates':{'label':'Find Duplicates', 'fn':'M.ciniki_customers_tools.duplicates.open();'},
//            'salesreps':{'label':'Sales Reps', 'visible':'no', 'fn':'M.startApp(\'ciniki.customers.salesreps\', null, \'M.ciniki_customers_tools.menu.show();\');'},
        }},
//        'download':{'label':'Export (Advanced)', 'list':{
//            'export':{'label':'Export to Excel', 'fn':'M.startApp(\'ciniki.customers.download\',null,\'M.ciniki_customers_tools.menu.show();\',\'mc\',{});'},
//            'exportcsvcontacts':{'label':'Contacts to Excel', 'fn':'M.ciniki_customers_tools.menu.exportCSVContacts();'},
//        }},
        };
    this.menu.exportCSVContacts = function() {
        var args = {'tnid':M.curTenantID, 'columns':'type::status::prefix::first::middle::last::suffix::company::split_phone_labels::split_emails::split_addresses'};
        M.api.openFile('ciniki.customers.customerListExcel', args);
    }
    this.menu.open = function(cb) {
        this.show(cb);
    }
    this.menu.addClose('Back');

    //
    // The main panel, which lists the options for production
    //
    this.duplicates = new M.panel('Duplicate Customers', 'ciniki_customers_tools', 'duplicates', 'mc', 'medium', 'sectioned', 'ciniki.customers.tools.duplicates');
    this.duplicates.data = {};
    this.duplicates.sections = {
        'matches':{'label':'Duplicate Customers', 'num_cols':4, 'type':'simplegrid', 
            'headerValues':['ID', 'Name', 'ID', 'Name'],
            'noData':'No potential customer matches found',
            },
        };
    this.duplicates.sectionData = function(s) {
        return this.data[s];
    };
    this.duplicates.noData = function(s) { return 'No potential matches found'; }
    this.duplicates.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.match.c1_id;
            case 1: return d.match.c1_display_name;
            case 2: return d.match.c2_id;
            case 3: return d.match.c2_display_name;
        }
        return '';
    };
    this.duplicates.rowFn = function(s, i, d) { 
        return 'M.ciniki_customers_tools.duplicate.open(\'M.ciniki_customers_tools.duplicates.open();\',\'' + d.match.c1_id + '\',\'' + d.match.c2_id + '\');'; 
    };
    this.duplicates.open = function(cb) {
        //
        // Grab list of recently updated customers
        //
        M.api.getJSONCb('ciniki.customers.duplicatesFind', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_tools.duplicates;
            p.data.matches = rsp.matches;
            p.refresh();
            p.show(cb);
        });
    };
    this.duplicates.addClose('Back');


    //
    // The duplicates panel is a combination of 2 customers
    //
    this.duplicate = new M.panel('Customer Match',
        'ciniki_customers_tools', 'duplicate',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.tools.duplicate');
    this.duplicate.customer1_id = 0;
    this.duplicate.customer2_id = 0;
    this.duplicate.data = {};
    this.duplicate.data_tabs = {};
    this.duplicate.selected_data_tab = '';
    this.duplicate.sections = {};
    this.duplicate.cellValue = function(s, i, j, d) {
        if( s == 'details1' || s == 'details2' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        } else if( this.sections[s].cellValues != null ) {
            return eval(this.sections[s].cellValues[j]);
        }
        return '';
    }
    this.duplicate.rowFn = function(s, i, d) {
        if( this.sections[s].editApp != null ) {
            return 'M.ciniki_customers_tools.duplicate.openDataApp(\'' + s + '\',\'' + i + '\');';
        }
        return '';
    }
    this.duplicate.addDataFn = function(s, i) {
        var args = {};
        if( this.sections[s].addApp.args != null ) {
            for(var j in this.sections[s].addApp.args) {
                args[j] = eval(this.sections[s].addApp.args[j]);
            }
        }
        M.startApp(this.sections[s].addApp.app,null,'M.ciniki_customers_tools.duplicate.open();','mc',args);
    } 
    this.duplicate.openDataApp = function(s, i) {
        var args = {};
        var d = this.sections[s].data[i];
        if( this.sections[s].editApp.args != null ) {
            for(var j in this.sections[s].editApp.args) {
                args[j] = eval(this.sections[s].editApp.args[j]);
            }
        }
        M.startApp(this.sections[s].editApp.app,null,'M.ciniki_customers_tools.duplicate.open();','mc',args);
    }
    this.duplicate.switchTab = function(t) { 
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
    this.duplicate.open = function(cb, c1, c2) {
        if( c1 != null ) { this.customer1_id = c1; }
        if( c2 != null ) { this.customer2_id = c2; }
        M.api.getJSONCb('ciniki.customers.duplicateDetails', {'tnid':M.curTenantID, 'customer1_id':this.customer1_id, 'customer2_id':this.customer2_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_tools.duplicate;
            p.data = rsp;
            p.sections = {
                'details1':{'label':'', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
                    'headerValues':null,
                    'cellClasses':['label', ''],
                    'dataMaps':['name', 'value'],
                    },
                'details2':{'label':'', 'type':'simplegrid', 'num_cols':2, 'aside':'no',
                    'headerValues':null,
                    'cellClasses':['label', ''],
                    'dataMaps':['name', 'value'],
                    },
                'data_tabs':{'label':'', 'type':'menutabs', 'selected':p.selected_data_tab, 'tabs':{}},
            }
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
                        'fn':'M.ciniki_customers_tools.duplicate.switchTab("' + rsp.data_tabs[i].id + '");',
                        };
                    for(var j in rsp.data_tabs[i].sections) {
                        rsp.data_tabs[i].sections[j].visible = 'no';
                        p.data[j] = rsp.data_tabs[i].sections[j].data;
                        p.sections[j] = rsp.data_tabs[i].sections[j];
                    }
                }
                if( found == 'no' ) {
                    p.selected_data_tab = rsp.data_tabs[0].id;
                    p.sections.data_tabs.selected = rsp.data_tabs[0].id;
                }
            }
            p.sections._buttons1 = {'label':'', 'aside':'yes', 'buttons':{
                'merge':{'label':'Merge >',
                    'fn':'M.ciniki_customers_tools.duplicate.mergeCustomers(M.ciniki_customers_tools.duplicate.customer2_id, M.ciniki_customers_tools.duplicate.customer1_id);'},
                'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.accounts\',null,\'M.ciniki_customers_tools.duplicate.open();\',\'mc\',{\'action\':\'edit\',\'customer_id\':M.ciniki_customers_tools.duplicate.customer1_id});'},
                'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_customers_tools.duplicate.deleteCustomer(M.ciniki_customers_tools.duplicate.customer1_id);'},
                }};
            p.sections._buttons2 = {'label':'', 'aside':'no', 'buttons':{
                'merge':{'label':'< Merge', 
                    'fn':'M.ciniki_customers_tools.duplicate.mergeCustomers(M.ciniki_customers_tools.duplicate.customer1_id, M.ciniki_customers_tools.duplicate.customer2_id);'},
                'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.accounts\',null,\'M.ciniki_customers_tools.duplicate.open();\',\'mc\',{\'action\':\'edit\',\'customer_id\':M.ciniki_customers_tools.duplicate.customer2_id});'},
                'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_customers_tools.duplicate.deleteCustomer(M.ciniki_customers_tools.duplicate.customer2_id);'},
                }};
            p.refresh();
            p.show(cb);
            if( p.sections.data_tabs.selected != '' ) {
                p.switchTab(p.sections.data_tabs.selected);
            }
        });
    }
    this.duplicate.mergeCustomers = function(cid1, cid2) {
        M.api.getJSONCb('ciniki.customers.merge', {'tnid':M.curTenantID, 
            'primary_customer_id':cid1, 'secondary_customer_id':cid2}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_tools.duplicate.open();
            });
    }
    this.duplicate.deleteCustomer = function(cid) {
        if( cid != null && cid > 0 ) {
            M.confirm("Are you sure you want to remove this customer?",null,function() {
                M.api.getJSONCb('ciniki.customers.delete', {'tnid':M.curTenantID, 'customer_id':cid}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_tools.duplicate.close();
                });
            });
        }
    }
    this.duplicate.addClose('Cancel');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_tools', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menu.open(cb);
    }
}
