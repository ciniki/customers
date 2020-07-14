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
//      '6':{'name':'Secondary'},
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

    //
    // The main panel, which lists the options for production
    //
    this.menu = new M.panel('Customers',
        'ciniki_customers_main', 'menu',
        'mc', 'medium narrowaside', 'sectioned', 'ciniki.customers.main.menu');
    this.menu.data = {};
    this.menu.country = null;
    this.menu.province = null;
    this.menu.city = null;
    this.menu.list = 'customers';
    this.menu.country = '';
    this.menu.province = '';
    this.menu.tag = '';
    this.menu.sections = {
//          'tools':{'label':'Tools', 'list':{
//              'duplicates':{'label':'Find Duplicates', 'fn':'M.startApp(\'ciniki.customers.duplicates\', null, \'M.ciniki_customers_main.menu.show();\');'},
//              'automerge':{'label':'Automerge', 'fn':'M.startApp(\'ciniki.customers.automerge\', null, \'M.ciniki_customers_main.menu.show();\');'},
//              }},
        'customer_tags':{'label':'Tags', 'aside':'yes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No tags',
            },
        'customer_categories':{'label':'Categories', 'aside':'yes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No categories',
            },
        'places':{'label':'Countries', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':'no',
            'headerValues':null,
            'noData':'No customers',
            'limit':5,
            'moreTxt':'more',
            'moreFn':'M.startApp(\'ciniki.customers.places\',null,\'M.ciniki_customers_main.menu.open();\',\'mc\',{\'country\':M.ciniki_customers_main.menu.country,\'province\':M.ciniki_customers_main.menu.province});',
            },
        'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':2, 
            'hint':'customer name', 'noData':'No customers found',
            'headerValues':['Customer', 'Status'],
            },
        'recent':{'label':'Recently Updated', 'num_cols':1, 'type':'simplegrid', 
            'visible':function() {return (M.ciniki_customers_main.menu.list == 'recent' ? 'yes' : 'no'); },
            'headerValues':null,
            'noData':'No customers',
            },
        'customers':{'label':'Customers', 'num_cols':2, 'type':'simplegrid', 
            'visible':function() {return (M.ciniki_customers_main.menu.list != 'recent' ? 'yes' : 'no'); },
            'headerValues':['Customer', 'Status'],
            'noData':'No customers',
            },
        };
    this.menu.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 'start_needle':encodeURIComponent(value), 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_customers_main.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_main.menu.panelUID + '_' + s), rsp.customers); 
                });
            return true;
        }
    };
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            switch(j) {
                case 0: return d.display_name + (d.parent_name != null && d.parent_name != "" ? " <span class=\'subdue\'>(" + d.parent_name + ")</span>" : "");
                case 1: return d.status_text;
            }
        }
        return '';
    };
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.ciniki_customers_main.customer.open(\'M.ciniki_customers_main.menu.open();\',\'' + d.id + '\');'; 
    };
    this.menu.liveSearchResultRowStyle = function(s, f, i, d) {
        if( M.curTenant.customers.settings['ui-colours-customer-status-' + d.status] != null ) {
            return 'background: ' + M.curTenant.customers.settings['ui-colours-customer-status-' + d.status];
        }
    };
    this.menu.liveSearchSubmitFn = function(s, search_str) {
        M.ciniki_customers_main.searchCustomers('M.ciniki_customers_main.menu.open();', search_str);
    };
    this.menu.sectionData = function(s) {
        return this.data[s];
    };
    this.menu.noData = function(s) { return 'No customers'; }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'customer_categories' || s == 'customer_tags' ) {
            return d.name + ' <span class="count">' + d.num_customers + '</span>';
        }
        if( s == 'places' ) {
            if( d.place.city != null ) {
                return (d.place.city==''?'No city':d.place.city) + ' <span class="count">' + d.place.num_customers + '</span>';
            } else if( d.place.province != null ) {
                return (d.place.province==''?'No province/state':d.place.province) + ' <span class="count">' + d.place.num_customers + '</span>';
            } else {
                return (d.place.country==''?'No Country':d.place.country) + ' <span class="count">' + d.place.num_customers + '</span>';
            }
        }
        if( s == 'recent' ) {
            switch(j) {
                case 0: return d.customer.display_name;
                case 1: return d.customer.status_text;
            }
        }
        if( s == 'customers' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.status_text;
            }
        }
    };
    this.menu.rowFn = function(s, i, d) { 
        if( s == 'places' ) {
            return 'M.startApp(\'ciniki.customers.places\',null,\'M.ciniki_customers_main.menu.open();\',\'mc\',{\'country\':\'' + escape(d.place.country) + '\'' + (d.place.province!=null?',\'province\':\'' + escape(d.place.province)+'\'':'') + (d.place.city!=null?',\'city\':\''+escape(d.place.city)+'\'':'') + '});';
        }
        if( s == 'customer_categories' ) {
            return 'M.ciniki_customers_main.menu.openCategory(\'' + M.eU(d.name) + '\');';
        }
        if( s == 'customer_tags' ) {
            return 'M.ciniki_customers_main.menu.openCategory(\'' + M.eU(d.name) + '\');';
        }
        if( s == 'recent' ) {
            return 'M.ciniki_customers_main.customer.open(\'M.ciniki_customers_main.menu.open();\',\'' + d.customer.id + '\');'; 
        }
        if( s == 'customers' ) {
            return 'M.ciniki_customers_main.customer.open(\'M.ciniki_customers_main.menu.open();\',\'' + d.id + '\');'; 
        }
    };
    this.menu.openCategory = function(c) {
        this.list = 'category';
        this.category = M.dU(c);
        this.sections.customers.label = this.category;
        this.open();
    }
    this.menu.open = function(cb) {
        //
        // Grab list of recently updated customers
        //
        var args = {'tnid':M.curTenantID};
        if( this.list == 'category' ) {
            args.category = M.eU(this.category);
        }
        if( this.list == 'customers' ) {
            M.api.getJSONCb('ciniki.customers.customerList', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                var p = M.ciniki_customers_main.menu;
                p.data = rsp;
                p.size = 'medium';
                p.sections.places.visible = 'no';
                p.refresh();
                p.show(cb);
            });
        } else {
            M.api.getJSONCb('ciniki.customers.overview', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                var p = M.ciniki_customers_main.menu;
                p.data = {};
                if( rsp.places != null ) {
                    p.sections.places.visible = 'yes';
                    p.size = 'medium narrowaside';
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
                p.data = rsp; 
                p.refresh();
                p.show(cb);
            });
        }
    }
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
            'onhold':{'label':'On Hold', 
                'visible':function() { return M.modOn('ciniki.sapos') || M.modOn('ciniki.poma') || M.modOn('ciniki.products') ? 'yes' : 'no'; },
                'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_main.tools.show();\',\'mc\',{\'status\':\'40\'});',
                },
            'suspended':{'label':'Suspended', 
                'visible':function() { return M.modOn('ciniki.sapos') || M.modOn('ciniki.poma') || M.modOn('ciniki.products') ? 'yes' : 'no'; },
                'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_main.tools.show();\',\'mc\',{\'status\':\'50\'});',
                },
            'deleted':{'label':'Deleted', 'fn':'M.startApp(\'ciniki.customers.reportstatus\',null,\'M.ciniki_customers_main.tools.show();\',\'mc\',{\'status\':\'60\'});'},
            'birthdays':{'label':'Birthdays', 
                'visible':function() {return M.modFlagSet('ciniki.customers', 0x8000); },
                'fn':'M.startApp(\'ciniki.customers.birthdays\',null,\'M.ciniki_customers_main.tools.show();\');'},
            }},
        '_connections':{'label':'', 'list':{
            'connection':{'label':'Connections', 'fn':'M.startApp(\'ciniki.customers.connections\',null,\'M.ciniki_customers_main.tools.show();\')'},
            }},
        'tools':{'label':'Cleanup', 'list':{
            'blank':{'label':'Find Blank Names', 'fn':'M.startApp(\'ciniki.customers.blanks\', null, \'M.ciniki_customers_main.tools.show();\');'},
            'duplicates':{'label':'Find Duplicates', 'fn':'M.startApp(\'ciniki.customers.duplicates\', null, \'M.ciniki_customers_main.tools.show();\');'},
        }},
        'download':{'label':'Export (Advanced)', 'list':{
            'export':{'label':'Export to Excel', 'fn':'M.startApp(\'ciniki.customers.download\',null,\'M.ciniki_customers_main.tools.show();\',\'mc\',{});'},
            'exportcsvcontacts':{'label':'Contacts to Excel', 'fn':'M.ciniki_customers_main.tools.exportCSVContacts();'},
        }},
//            'logs':{'label':'Other', 'list':{
//                'logs':{'label':'Authentication Logs', 'fn':'M.startApp(\'ciniki.customers.logs\',null,\'M.ciniki_customers_main.tools.show();\',\'mc\',{});'},
//            }},
//          'import':{'label':'Import', 'list':{
//              'automerge':{'label':'Automerge', 'fn':'M.startApp(\'ciniki.customers.automerge\', null, \'M.ciniki_customers_main.menu.show();\');'},
//          }},
        };
    this.tools.exportCSVContacts = function() {
        var args = {'tnid':M.curTenantID, 'columns':'type::status::prefix::first::middle::last::suffix::company::split_phone_labels::split_emails::split_addresses'};
        M.api.openFile('ciniki.customers.customerListExcel', args);
    }
    this.tools.addClose('Back');

    //
    // The search panel will list all search results for a string.  This allows more advanced searching,
    // and will search the entire strings, not just start of the string like livesearch
    //
    this.search = new M.panel('Search Results', 'ciniki_customers_main', 'search', 'mc', 'medium', 'sectioned', 'ciniki.customers.main.search');
    this.search.search_type = 'customers';
    this.search.sections = {
        'main':{'label':'', 'type':'simplegrid', 'num_cols':2, 
            'headerValues':['Name', 'Status'], 
            'sortable':'yes'},
    };
    this.search.noData = function() { return 'No ' + this.search_type + ' found'; }
    this.search.sectionData = function(s) { return this.data; }
    this.search.cellValue = function(s, i, j, d) { 
        switch(j) {
            case 0: return d.display_name + (d.parent_name != null && d.parent_name != "" ? " <span class=\'subdue\'>(" + d.parent_name + ")</span>" : "");
            case 1: return d.status_text;
        }
    };
    this.search.rowFn = function(s, i, d) { 
        if( M.ciniki_customers_main.search.search_type == 'members' ) {
            return 'M.startApp(\'ciniki.customers.members\',null,\'M.ciniki_customers_main.searchCustomers();\',\'mc\',{\'customer_id\':\'' + d.id + '\'});';
        } else if( M.ciniki_customers_main.search.search_type == 'dealers' ) {
            return 'M.startApp(\'ciniki.customers.dealers\',null,\'M.ciniki_customers_main.searchCustomers();\',\'mc\',{\'customer_id\':\'' + d.id + '\'});';
        } else if( M.ciniki_customers_main.search.search_type == 'distributors' ) {
            return 'M.startApp(\'ciniki.customers.distributors\',null,\'M.ciniki_customers_main.searchCustomers();\',\'mc\',{\'customer_id\':\'' + d.id + '\'});';
        } else {
            return 'M.ciniki_customers_main.customer.open(\'M.ciniki_customers_main.searchCustomers(null, M.ciniki_customers_main.search.search_str);\',\'' + d.id + '\');'; 
        }
    }
    this.search.rowStyle = function(s, i, d) {
        if( M.curTenant.customers.settings['ui-colours-customer-status-' + d.status] != null ) {
            return 'background: ' + M.curTenant.customers.settings['ui-colours-customer-status-' + d.status];
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
    this.customer.sections = {};
    this.customer.noData = function(s) {
        return this.sections[s].noData;
    };
    this.customer.sectionData = function(s) {
        if( s == 'membership' ) { return this.sections[s].list; }
        if( s == 'parent' ) { return (this.data.parent!=null&&this.data.parent.details!=null)?this.data.parent.details:{}; }
        return this.data[s];
    };
    this.customer.listLabel = function(s, i, d) {
        if( s == 'membership' ) { 
            return d.label; 
        }
        return null;
    };
    this.customer.listValue = function(s, i, d) {
        if( s == 'membership' && i == 'type' ) {
            var txt = '';
            if( this.data.membership_type != null && this.data.membership_type != '' ) {
                switch(this.data.membership_type) {
                    case '10': txt += 'Regular'; break;
                    case '20': txt += 'Student'; break;
                    case '30': txt += 'Individual'; break;
                    case '40': txt += 'Family'; break;
                    case '110': txt += 'Complimentary'; break;
                    case '150': txt += 'Reciprocal'; break;
                }
            }
            if( this.data.membership_length != null && this.data.membership_length != '' ) {
                switch(this.data.membership_length) {
                    case '10': txt += (txt!=''?', ':'') + 'Monthly'; break;
                    case '20': txt += (txt!=''?', ':'') + 'Yearly'; break;
                    case '60': txt += (txt!=''?', ':'') + 'Lifetime'; break;
                }
            }
            return txt;
        }
        if( i == 'name' ) {
            return this.data.first + ' ' + this.data.last;
        }
        if( i == 'member_status_text' && this.data.member_status == 0 ) {
            return 'Not a member';
        }
        return this.data[i];
    };
/*        this.customer.liveSearchCb = function(s, i, value) {
        if( s == 'order_search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'tnid':M.curTenantID, 'customer_id':M.ciniki_customers_main.customer.customer_id, 'invoice_type':'40', 'start_needle':value, 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_customers_main.customer.liveSearchShow('order_search', null, M.gE(M.ciniki_customers_main.customer.panelUID + '_' + s), rsp.invoices); 
                });
            return true;
        }
    };
    this.customer.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'order_search' ) {
            switch(j) {
                case 0: return d.invoice.invoice_number; // + (d.invoice.po_number!=null&&d.invoice.po_number!=''?'<span class="subtext">PO #:' + d.invoice.po_number + '</span>':'');
                case 1: return d.invoice.po_number;
                case 2: return d.invoice.invoice_date;
                case 3: return d.invoice.total_amount_display;
                case 4: return d.invoice.status_text;
            }
        }
        return '';
    };
    this.customer.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( s == 'order_search' ) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_customers_main.customer.open();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
        }
    }; */
    this.customer.cellColour = function(s, i, j, d) {
        if( this.sections[s].cellColours != null && this.sections[s].cellColours[j] != '' ) {
            return eval(this.sections[s].cellColours[j]);
        }
        return '';
    };
    this.customer.fieldValue = function(s, i, d) {
        if( i == 'notes' && this.data[i] == '' ) { return 'No notes'; }
        return this.data[i];
    };
    this.customer.cellValue = function(s, i, j, d) {
        if( s == 'parent' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
            }
        }
        else if( s == 'details' || s == 'member_details' || s == 'account' ) {
            if( j == 0 ) { return d.label; }
            if( j == 1 ) { return d.value; }
        }
        else if( s == 'children' ) {
            return (d.customer.eid!=null&&d.customer.eid!=''?d.customer.eid+' - ':'') + d.customer.display_name;
        }
        else if( this.sections[s].cellValues != null ) {
            return eval(this.sections[s].cellValues[j]);
        }
        return this.data[s][i];
    };
    this.customer.cellFn = function(s, i, j, d) {
        if( this.sections[s].cellApps != null && this.sections[s].cellApps[j] != null ) {
            return 'M.ciniki_customers_main.customer.openCellApp(\'' + s + '\',\'' + i + '\',\'' + j + '\');';
        }
    }
    this.customer.addDataFn = function(s, i) {
        var args = {};
        if( this.sections[s].addApp.args != null ) {
            for(var j in this.sections[s].addApp.args) {
                args[j] = eval(this.sections[s].addApp.args[j]);
            }
        }
        M.startApp(this.sections[s].addApp.app,null,'M.ciniki_customers_main.customer.open();','mc',args);
    }
    this.customer.moreDataFn = function(s, i) {
        var args = {};
        if( this.sections[s].moreApp.args != null ) {
            for(var j in this.sections[s].moreApp.args) {
                args[j] = eval(this.sections[s].moreApp.args[j]);
            }
        }
        M.startApp(this.sections[s].moreApp.app,null,'M.ciniki_customers_main.customer.open();','mc',args);
    }
    this.customer.openCellApp = function(s, i, j) {
        if( this.sections[s].cellApps[j] == null ) {
            return;
        }
        var args = {};
        var d = this.sections[s].data[i];
        if( this.sections[s].cellApps != null && this.sections[s].cellApps[j].args != null ) {
            for(var k in this.sections[s].cellApps[j].args) {
                args[k] = eval(this.sections[s].cellApps[j].args[k]);
            }
        }
        M.startApp(this.sections[s].cellApps[j].app,null,'M.ciniki_customers_main.customer.open();','mc',args);
    }
    this.customer.openDataApp = function(s, i) {
        var args = {};
        var d = this.sections[s].data[i];
        if( this.sections[s].editApp.args != null ) {
            for(var j in this.sections[s].editApp.args) {
                args[j] = eval(this.sections[s].editApp.args[j]);
            }
        }
        M.startApp(this.sections[s].editApp.app,null,'M.ciniki_customers_main.customer.open();','mc',args);
    }
    this.customer.switchTab = function(t) { 
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
    this.customer.rowStyle = function(s, i, d) {
        if( s == 'details' && d.style != null ) {
            return d.style;
        }
        return '';
    };
    this.customer.open = function(cb, cid, section) {
        if( cid != null ) { this.customer_id = cid; }
        // Reset to not showing all sections
        M.api.getJSONCb('ciniki.customers.getModuleData', 
            {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_main.customer.openFinish(cb, rsp, section);
            });
    }
    this.customer.openFinish = function(cb, rsp, section) {
        var mods = M.curTenant.modules;
        this.data = rsp.customer;
        this.data.data_tabs = rsp.data_tabs;
        console.log(rsp);
        this.data.details = {};

        this.sections = {
            'parent':{'label':'Parent', 'aside':'yes', 'active':'no', 'type':'simplegrid', 'num_cols':2,
                'headerValues':null,
                'cellClasses':['label', ''],
                'dataMaps':['name', 'value'],
                'rowFn':function(i, d) {
                    if( M.ciniki_customers_main.customer.data.parent != null && M.ciniki_customers_main.customer.data.parent.id > 0 ) {
                        return 'M.ciniki_customers_main.customer.open(\'M.ciniki_customers_main.customer.open(null,"' + M.ciniki_customers_main.customer.customer_id + '");\',\'' + M.ciniki_customers_main.customer.data.parent.id + '\');';
                    }},
                },
            'details':{'label':'Customer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
                'headerValues':null,
                'cellClasses':['label', ''],
                'dataMaps':['name', 'value'],
                },
            'membership':{'label':'Membership', 'aside':'yes', 
                'visible':function() { return M.modFlagSet('ciniki.customers', 0x02); },
                'list':{
                    'member_status_text':{'label':'Status'},
                    'member_lastpaid':{'label':'Last Paid', 'visible':function() {
                        return !M.modFlagSet('ciniki.customers', 0x02000000);
                        }},
                    'type':{'label':'Type'},
                    'start_date':{'label':'Start', 'visible':function() {
                        return M.modFlagSet('ciniki.customers', 0x04000000);
                        }},
                }},
            'account':{'label':'', 'aside':'yes', 'visible':'yes', 'type':'simplegrid', 'num_cols':2,
                'headerValues':null,
                'cellClasses':['label', ''],
                'dataMaps':['name', 'value'],
                },
            'children':{'label':'Children', 'aside':'yes', 'type':'simplegrid', 'visible':'no', 'num_cols':1,
                'visible':function() { return M.modFlagSet('ciniki.customers', 0x200000); },
                'headerValues':null,
                'cellClasses':[''],
                'addTxt':'Add',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.updateChildren();\',\'mc\',{\'parent_id\':M.ciniki_customers_main.customer.customer_id,\'customer_id\':0,\'parent_name\':escape(M.ciniki_customers_main.customer.data.display_name)});',
                'rowFn':function(i, d) {
                    return 'M.ciniki_customers_main.customer.open(\'M.ciniki_customers_main.customer.open(null,"' + M.ciniki_customers_main.customer.customer_id + '","children");\',\'' + d.customer.id + '\');';
                },
                },
            '_notes':{'label':'Notes', 'aside':'yes', 'type':'simpleform', 'fields':{'notes':{'label':'', 'type':'noedit', 'hidelabel':'yes'}}},

            'data_tabs':{'label':'', 'type':'paneltabs', 'selected':this.selected_data_tab, 'tabs':{}},
            };
        var num_tabs = 0;
        if( rsp.data_tabs != null ) {
            var found = 'no';
            var first = '';
            for(var i in rsp.data_tabs) {
                if( first == '' ) {
                    first = i;
                }
                if( this.selected_data_tab == '' ) {
                    this.sections.data_tabs.selected = rsp.data_tabs[i].id;
                    this.selected_data_tab = rsp.data_tabs[i].id;
                    found = 'yes';
                } else if( this.selected_data_tab == rsp.data_tabs[i].id ) {
                    found = 'yes';
                }
                this.sections.data_tabs.tabs[rsp.data_tabs[i].id] = {
                    'label':rsp.data_tabs[i].label, 
                    'fn':'M.ciniki_customers_main.customer.switchTab("' + rsp.data_tabs[i].id + '");',
                    };
                for(var j in rsp.data_tabs[i].sections) {
                    rsp.data_tabs[i].sections[j].visible = 'no';
                    if( rsp.data_tabs[i].sections[j].addTxt != null && rsp.data_tabs[i].sections[j].addApp != null ) {
                        rsp.data_tabs[i].sections[j].addFn = 'M.ciniki_customers_main.customer.addDataFn(\'' + j + '\');';
                    }
                    if( rsp.data_tabs[i].sections[j].moreTxt != null && rsp.data_tabs[i].sections[j].moreApp != null ) {
                        rsp.data_tabs[i].sections[j].moreFn = 'M.ciniki_customers_main.customer.moreDataFn(\'' + j + '\');';
                    }
                    if( rsp.data_tabs[i].sections[j].editApp != null ) {
                        rsp.data_tabs[i].sections[j].sct = j;
                        rsp.data_tabs[i].sections[j].rowFn = function(row, d) {
                            return 'M.ciniki_customers_main.customer.openDataApp(\'' + this.sct + '\',\'' + row + '\');';
                        };
                    }
                    this.data[j] = rsp.data_tabs[i].sections[j].data;
                    this.sections[j] = rsp.data_tabs[i].sections[j];
                }
                num_tabs++;
            }
            if( found == 'no' && first != '' ) {
                this.selected_data_tab = rsp.data_tabs[first].id;
                this.sections.data_tabs.selected = rsp.data_tabs[first].id;
            }
        }
        if( num_tabs > 1 ) {
            this.sections.data_tabs.visible = 'yes';
            this.size = 'medium mediumaside';
        } else if( num_tabs == 1 ) {
            this.sections.data_tabs.visible = 'no';
            this.size = 'medium mediumaside';
        } else {
            this.sections.data_tabs.visible = 'no';
            this.size = 'medium';
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x010000) > 0 ) {
            this.data.details.eid = {'label':'ID', 'value':rsp.customer.eid};
        }
        if( rsp.customer.status_text != null ) {
            this.data.details.status_text = {'label':'Status', 'value':rsp.customer.status_text};
            if( M.curTenant.customers.settings['ui-colours-customer-status-' + rsp.customer.status] != null ) {
                this.data.details.status_text.style = 'background: ' + M.curTenant.customers.settings['ui-colours-customer-status-' + rsp.customer.status];
            }
        }
        if( M.modFlagSet('ciniki.customers', 0x10) == 'yes' && rsp.customer.dealer_status_text != null ) {
            this.data.details.dealer_status_text = {'label':'Dealer Status', 'value':rsp.customer.dealer_status_text};
        }
        if( M.modFlagSet('ciniki.customers', 0x0100) == 'yes' && rsp.customer.distributor_status_text != null ) {
            this.data.details.distributor_status_text = {'label':'Distributor Status', 'value':rsp.customer.distributor_status_text};
        }
        if( rsp.customer.type == 2 ) {
            this.data.details.company = {'label':'Name', 'value':rsp.customer.company};
            this.data.details.name = {'label':'Contact', 'value':rsp.customer.first + ' ' + rsp.customer.last};
        } else {
            this.data.details.name = {'label':'Name', 'value':rsp.customer.display_name};
            if( rsp.customer.company != null &&  rsp.customer.company != '' ) {
                this.data.details.company = {'label':'Business', 'value':rsp.customer.company};
            }
            if( rsp.customer.birthdate != '' ) {
                this.data.details.birthdate = {'label':'Birthday', 'value':rsp.customer.birthdate};
            }
            if( rsp.customer.language != '' ) {
                this.data.details.language = {'label':'Language', 'value':rsp.customer.language};
            }
        }
        if( rsp.customer.connection != '' ) {
            this.data.details.connection = {'label':'Connection', 'value':rsp.customer.connection};
        }
        if( rsp.customer.phones != null ) {
            for(i in rsp.customer.phones) {
                this.data.details['phone-'+i] = {'label':rsp.customer.phones[i].phone.phone_label, 'value':rsp.customer.phones[i].phone.phone_number};
            }
        }
        if( rsp.customer.emails != null ) {
            for(i in rsp.customer.emails) {
                var flags = '';
                if( (rsp.customer.emails[i].email.flags&0x08) > 0 ) { flags += (flags!=''?', ':'') + 'Public'; }
                if( (rsp.customer.emails[i].email.flags&0x10) > 0 ) { flags += (flags!=''?', ':'') + 'No Emails'; }
                this.data.details['email-'+i] = {'label':'Email', 'value':M.linkEmail(rsp.customer.emails[i].email.address) + (flags!=''?' <span class="subdue">(' + flags + ')</span>':'')};
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
                
                this.data.details['address-'+i] = {'label':l, 'value':v};
            }
        }
        if( rsp.customer.links != null ) {
            for(i in rsp.customer.links) {
                var url = M.hyperlink(rsp.customer.links[i].link.url);
                this.data.details['link-'+i] = {'label':'Website', 'value':(rsp.customer.links[i].link.name!=''?rsp.customer.links[i].link.name + ' <span class="subdue">' + url + '</span>':url)};
            }
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x400000) > 0 ) {
            this.data.details['customer_categories'] = {'label':'Categories', 'value':(rsp.customer.customer_categories!=null?rsp.customer.customer_categories.replace(/::/g,', '):'')};
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x800000) > 0 ) {
            this.data.details['customer_tags'] = {'label':'Tags', 'value':(rsp.customer.customer_tags!=null?rsp.customer.customer_tags.replace(/::/g,', '):'')};
        }

        this.data.account = {};
        //
        // Build member_details
        //
        this.data.member_details = {};
        if( M.modFlagOn('ciniki.customers', 0x02) && rsp.customer.member_status_text != null && rsp.customer.member_status > 0 ) {
            this.data.member_details.member_status_text = {'label':'Status', 'value':rsp.customer.member_status_text};
        } else {
            this.data.member_details.member_status_text = {'label':'Status', 'value':'Not a member'};
        }

        // Tax Number
        if( (M.curTenant.modules['ciniki.customers'].flags&0x20000) > 0 
            && rsp.customer.tax_number != null && rsp.customer.tax_number != ''
            ) {
            this.sections.account.visible = 'yes';
            this.data.account.tax_number = {'label':'Tax Number', 'value':rsp.customer.tax_number};
        }
        // Tax Location
        if( (M.curTenant.modules['ciniki.customers'].flags&0x40000) > 0 ) {
            var rates = ((rsp.customer.tax_location_id_rates!=null&&rsp.customer.tax_location_id_rates!='')?' <span class="subdue">'+rsp.customer.tax_location_id_rates+'</span>':'');
            this.sections.account.visible = 'yes';
            this.data.account.tax_location_id = {'label':'Taxes', 'value':(rsp.customer.tax_location_id_text!=null?rsp.customer.tax_location_id_text:'Use Shipping Address') + rates};
        }
        // Start Date
        if( (M.curTenant.modules['ciniki.customers'].flags&0x04000000) > 0 
            && rsp.customer.start_date != null && rsp.customer.start_date != ''
            ) {
            this.sections.account.visible = 'yes';
            this.data.account.start_date = {'label':'Start Date', 'value':rsp.customer.start_date};
        }

        this.sections._notes.visible=(rsp.customer.notes=='')?'no':'yes';

        if( this.sections.data_tabs.selected != '' ) {
            this.switchTab(this.sections.data_tabs.selected);
        }

        //
        // Check if there child accounts
        //
        this.sections.parent.active = 'no';
        if( M.modFlagOn('ciniki.customers', 0x200000) && rsp.customer.parent_id != 0 ) {
            this.sections.parent.active = 'yes';
        }

        this.refresh();
        this.show(cb);
    }
    this.customer.addClose('Back');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        // 
        // Check if redirect required to accounts
        //
        if( M.modFlagOn('ciniki.customers', 0x0800) ) {
            return M.startApp('ciniki.customers.accounts',null,cb,appPrefix,aG)
        }
        args = {};
        if( aG != null ) { args = eval(aG); }
        var settings = null;
        if( M.curTenant.modules['ciniki.customers'] != null && M.curTenant.modules['ciniki.customers'].settings != null ) {
            settings = M.curTenant.modules['ciniki.customers'].settings;
        }
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        // Setup ui labels
        this.slabel = 'Contact';
        this.plabel = 'Contacts';
        this.menu.list = 'customers';
        if( M.modOn('ciniki.sapos') || M.modOn('ciniki.poma') || M.modOn('ciniki.products') ) {
            this.slabel = 'Customer';
            this.plabel = 'Customers';
            this.menu.list = 'recent';
        }
        this.childlabel = 'Child';
        this.childrenlabel = 'Children';
        this.parentlabel = 'Parent';
        this.parentslabel = 'Parents';
/*      ** Deprecated ui-labels- 2020-07-14 **
        if( settings != null ) {
            if( settings['ui-labels-parent'] != null && settings['ui-labels-parent'] != '') {
                this.parentLabel = settings['ui-labels-parent'];
            }
            if( settings['ui-labels-parents'] != null && settings['ui-labels-parents'] != '') {
                this.parentslabel = settings['ui-labels-parents'];
            }
            if( settings['ui-labels-child'] != null && settings['ui-labels-child'] != '') {
                this.childlabel = settings['ui-labels-child'];
            }
            if( settings['ui-labels-children'] != null && settings['ui-labels-children'] != '') {
                this.childrenlabel = settings['ui-labels-children'];
            }
        } */
        this.menu.sections.search.noData = 'No ' + this.plabel.toLowerCase() + ' found';
        this.menu.sections.search.headerValues[0] = this.slabel;
        this.menu.sections.recent.noData = 'No ' + this.plabel.toLowerCase();
        this.menu.sections.customers.label = this.plabel;
        this.menu.sections.customers.noData = 'No ' + this.plabel.toLowerCase();
        this.menu.sections.customers.headerValues[0] = this.slabel;
        this.menu.title = this.plabel;
        this.menu.size = 'medium';
        if( (M.curTenant.modules['ciniki.customers'].flags&0x400000) > 0 
            || (M.curTenant.modules['ciniki.customers'].flags&0x800000) > 0
            ) {
            this.menu.size = 'medium narrowaside';
        }
        // Check if sidebar places should be shown
        if( M.curTenant.modules['ciniki.customers'].settings != null 
            && M.curTenant.modules['ciniki.customers'].settings['ui-show-places'] != null
            && M.curTenant.modules['ciniki.customers'].settings['ui-show-places'] == 'yes'
            ) {
            this.menu.sections.places.visible = 'yes';
            this.menu.size = 'medium narrowaside';
        }
        this.customer.title = this.slabel;
//        this.customer.sections.details.label = this.slabel;
/* 20200709
        this.customer.sections._tabs.tabs['children'].label = this.childrenlabel;
        this.customer.sections.children.label = this.childrenlabel;
        this.customer.sections.children.addTxt = 'Add ' + this.childlabel;
        this.customer.sections.parent.label = this.parentLabel;
        this.customer.sections.parent.addTxt = 'Add ' + this.parentLabel;
        this.customer.sections.parent.changeTxt = 'Change ' + this.parentLabel;
*/
        if( (M.curTenant.modules['ciniki.customers'].flags&0x4000) > 0 ) {
            this.tools.sections._connections.active = 'yes';
        } else {
            this.tools.sections._connections.active = 'no';
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x010000) > 0 ) {
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
        if( M.curTenant.permissions.owners != null 
            || M.curTenant.permissions.employees != null 
            || M.userPerms&0x01 == 1    // sysadmins
            ) {
            this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.menu.open();\',\'mc\',{\'customer_id\':0});');
            this.menu.addButton('tools', 'Tools', 'M.ciniki_customers_main.tools.show(\'M.ciniki_customers_main.menu.open();\');');
            this.customer.addButton('edit', 'Edit', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_main.customer.open();\',\'mc\',{\'customer_id\':M.ciniki_customers_main.customer.customer_id});');
        } 

        //
        // Setup the menu buttons to make this the home screen,
        // if the main business menu had only one menu option available.
        // This is used for sales reps when they login and can only see customers
        //
        if( M.ciniki_tenants_main.menu.autoopen == 'skipped' ) {
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
            this.customer.open(cb, args.customer_id);
        } else {
            this.menu.open(cb);
        }
    }


    this.updateChildren = function() {
        M.api.getJSONCb('ciniki.customers.getModuleData', 
            {'tnid':M.curTenantID, 
                'customer_id':M.ciniki_customers_main.customer.customer_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_main.customer;
                    p.data.children = rsp.customer.children;
                    p.refreshSection('children');
                    p.show();
                });
    }

/*    this.customer.openTab = function(cb, tab, scroll) {
        if( tab != null ) { this.customer.paneltab = tab; }
        p.sections._tabs.selected = tab;
        p.refresh();
        p.show(cb);
        if( p.sections._tabs.visible == 'yes' && scroll == 'yes' ) {
            var e = M.gE(M.ciniki_customers_main.customer.panelUID + '__tabs');
            if( e.offsetTop > 100 ) { window.scrollTo(0, e.offsetTop); }
        }
    } */

    this.searchCustomers = function(cb, search_str, type) {
        if( search_str != null ) { this.search.search_str = search_str; }
        if( type != null ) { this.search.search_type = type; }
        M.api.getJSONCb('ciniki.customers.searchFull', {'tnid':M.curTenantID, 
            'start_needle':this.search.search_str, 'type':this.search.search_type}, function(rsp) {
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
            M.confirm("Are you sure you want to remove this " + this.slabel + "?",null,function() {
                M.api.getJSONCb('ciniki.customers.delete', {'tnid':M.curTenantID, 'customer_id':cid}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_main.customer.close();
                });
            });
        }
    }
}
