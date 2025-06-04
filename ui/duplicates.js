//
function ciniki_customers_duplicates() {
    //
    // Panels
    //
    this.menu = null;

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
    this.list = new M.panel('Duplicate Customers',
        'ciniki_customers_duplicates', 'list',
        'mc', 'medium', 'sectioned', 'ciniki.customers.duplicates.list');
    this.list.data = {};
    this.list.matchon = 'soundex';
    this.list.sections = {
        'matches':{'label':'Duplicate Customers', 'num_cols':4, 'type':'simplegrid', 
            'headerValues':['ID', 'Name', 'ID', 'Name'],
            'noData':'No potential customer matches found',
            },
        };
    this.list.sectionData = function(s) {
        return this.data[s];
    };
    this.list.noData = function(s) { return 'No potential matches found'; }
    this.list.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.match.c1_id;
            case 1: return d.match.c1_display_name;
            case 2: return d.match.c2_id;
            case 3: return d.match.c2_display_name;
        }
        return '';
    };
    this.list.rowFn = function(s, i, d) { 
        return 'M.ciniki_customers_duplicates.duplicate.open(\'M.ciniki_customers_duplicates.list.open();\',\'' + d.match.c1_id + '\',\'' + d.match.c2_id + '\');'; 
    };
    this.list.open = function(cb, matchon) {
        if( matchon != null && matchon != '' ) {
            this.matchon = matchon;
        }
        //
        // Grab list of recently updated customers
        //
        M.api.getJSONCb('ciniki.customers.duplicatesFind', {'tnid':M.curTenantID, 'matchon':this.matchon}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_duplicates.list;
            p.data.matches = rsp.matches;
            p.refresh();
            p.show(cb);
        });
    };
    this.list.addClose('Back');

    //
    // The duplicates panel is a combination of 2 customers
    //
    this.duplicate = new M.panel('Customer Match',
        'ciniki_customers_duplicates', 'duplicate',
        'mc', 'fiftyfifty', 'sectioned', 'ciniki.customers.tools.duplicate');
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
            return 'M.ciniki_customers_duplicates.duplicate.openDataApp(\'' + s + '\',\'' + i + '\');';
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
        M.startApp(this.sections[s].addApp.app,null,'M.ciniki_customers_duplicates.duplicate.open();','mc',args);
    } 
    this.duplicate.openDataApp = function(s, i) {
        var args = {};
        var d = this.sections[s].data[i];
        if( this.sections[s].editApp.args != null ) {
            for(var j in this.sections[s].editApp.args) {
                args[j] = eval(this.sections[s].editApp.args[j]);
            }
        }
        M.startApp(this.sections[s].editApp.app,null,'M.ciniki_customers_duplicates.duplicate.open();','mc',args);
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
            var p = M.ciniki_customers_duplicates.duplicate;
            p.data = rsp;
            p.sections = {
                'details1':{'label':'Customer', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
                    'headerValues':null,
                    'cellClasses':['label', ''],
                    'dataMaps':['name', 'value'],
                    },
                'details2':{'label':'Customer', 'type':'simplegrid', 'num_cols':2, 'aside':'no',
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
                        'fn':'M.ciniki_customers_duplicates.duplicate.switchTab("' + rsp.data_tabs[i].id + '");',
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
                    'fn':'M.ciniki_customers_duplicates.duplicate.mergeCustomers(M.ciniki_customers_duplicates.duplicate.customer2_id, M.ciniki_customers_duplicates.duplicate.customer1_id);'},
                'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_duplicates.duplicate.open();\',\'mc\',{\'customer_id\':M.ciniki_customers_duplicates.duplicate.customer1_id});'},
                'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_customers_duplicates.duplicate.deleteCustomer(M.ciniki_customers_duplicates.duplicate.customer1_id);'},
                }};
            p.sections._buttons2 = {'label':'', 'aside':'no', 'buttons':{
                'merge':{'label':'< Merge', 
                    'fn':'M.ciniki_customers_duplicates.duplicate.mergeCustomers(M.ciniki_customers_duplicates.duplicate.customer1_id, M.ciniki_customers_duplicates.duplicate.customer2_id);'},
                'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_duplicates.duplicate.open();\',\'mc\',{\'customer_id\':M.ciniki_customers_duplicates.duplicate.customer2_id});'},
                'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_customers_duplicates.duplicate.deleteCustomer(M.ciniki_customers_duplicates.duplicate.customer2_id);'},
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
                M.ciniki_customers_duplicates.duplicate.open();
            });
    }
    this.duplicate.deleteCustomer = function(cid) {
        if( cid != null && cid > 0 ) {
            M.confirm("Are you sure you want to remove this customer?",null,function() {
                var rsp = M.api.getJSONCb('ciniki.customers.delete', {'tnid':M.curTenantID, 'customer_id':cid}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_duplicates.duplicate.close();
                });
            });
        }
    }
    this.duplicate.addClose('Cancel');

    //
    // The match2 panel is the second customer record that matches.
    // It must be listed first, as it's referenced by match1.
    //
    this.match2 = new M.panel('Customer Match',
        'ciniki_customers_duplicates', 'match2',
        'mc', 'medium', 'sectioned', 'ciniki.customers.duplicates.match');
    this.match2.customer_id = 0;
    this.match2.data = {};
    this.match2.sections = {
        'details':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['label', ''],
            'dataMaps':['name', 'value'],
            },
        'business':{'label':'Business', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No business details',
            },
        'phones':{'label':'', 'type':'simplegrid', 'num_cols':2, 'visible':'yes',
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No phone numbers',
            },
        'emails':{'label':'Emails', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No email addresses',
//              'addTxt':'Add Email',
//              'addFn':'M.ciniki_customers_main.showEmailAdd(M.ciniki_customers_main.customer.customer_id,\'M.ciniki_customers_main.showCustomer();\');',
            },
        'addresses':{'label':'Addresses', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No addresses',
            },
        '_notes':{'label':'Notes', 'type':'simpleform', 'fields':{'notes':{'label':'', 'type':'noedit', 'hidelabel':'yes'}}},
        'subscriptions':{'label':'Subscriptions', 'type':'simplegrid', 'visible':'yes', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No subscriptions',
            },
        'appointments':{'label':'Appointments', 'type':'simplegrid', 'visible':'no', 'num_cols':2, 'class':'dayschedule',
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
            },
        '_buttons':{'label':'', 'buttons':{}},
        };
    this.match2.noData = function(s) {
        return this.sections[s].noData;
    };
    this.match2.sectionData = function(s) {
        return this.data[s];
    };
    this.match2.cellColour = function(s, i, j, d) {
        if( s == 'appointments' && j == 1 ) { 
            if( d.appointment != null && d.appointment.colour != null && d.appointment.colour != '' ) {
                return d.appointment.colour;
            }
            return '#77ddff';
        }
        return '';
    };
    this.match2.fieldValue = function(s, i, d) {
        if( i == 'notes' && this.data[i] == '' ) { return 'No notes'; }
        return this.data[i];
    };
    this.match2.cellValue = function(s, i, j, d) {
        if( s == 'details' || s == 'business' || s == 'phones' ) {
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
                if( d.address.phone != '' ) { v += 'Phone: ' + d.address.phone + '<br/>'; }
                return v;
            }
        }
        else if( s == 'subscriptions' ) {
            if( j == 0 ) { return 'subscribed'; }
            if( j == 1 ) { return d.subscription.name; }
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
                var dt = d['order'][this.sections[s].dataMaps[j]];
                // Check for missing filter date, and try to take a guess
                if( dt == null && j == 6 ) {
                    var dt = d['order']['approx_filtering_date'];
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
            return d['order'][this.sections[s].dataMaps[j]];
        }
        return this.data[s][i];
    };
    this.match2.cellFn = function(s, i, j, d) {
        if( s == 'appointments' && j == 1 ) {
            if( d.appointment.module == 'ciniki.wineproduction' ) {
                return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_customers_duplicates.showMatch();\',\'mc\',{\'appointment_id\':\'' + d.appointment.id + '\'});';
            }
        }
        return '';
    };
    this.match2.rowFn = function(s, i, d) {
        if( s == 'emails' ) {
            return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_duplicates.showMatch();\',\'mc\',{\'edit_email_id\':' + d.email.id + ',\'customer_id\':' + d.email.customer_id + '});';
        }
        if( s == 'addresses' ) {
            return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_duplicates.showMatch();\',\'mc\',{\'edit_address_id\':' + d.address.id + ',\'customer_id\':' + d.address.customer_id + '});';
        }
        if( s == 'currentwineproduction' || s == 'pastwineproduction' ) {
            return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_customers_duplicates.showMatch();\',\'mc\',{\'order_id\':' + d.order.id + '});';
        }
        return d['Fn'];
    };
//      this.match2.addButton('edit', 'Edit', 'M.ciniki_customers_main.editCustomer(M.ciniki_customers_main.customer.customer_id, \'M.ciniki_customers_main.showCustomer();\');');

    //
    // The first customer record, will be displayed on the left or top
    //
    this.match1 = new M.panel('Customer Match',
        'ciniki_customers_duplicates', 'match1',
        'mc', 'medium', 'sectioned', 'ciniki.customers.duplicates.match');
    this.match1.data = {};
    this.match1.sections = {};
    for(var attr in this.match2.sections) {
        this.match1.sections[attr] = this.match2.sections[attr];
    }
//      this.match1.sections = this.match2.sections;
    this.match1.noData = this.match2.noData;
    this.match1.sectionData = this.match2.sectionData;
    this.match1.cellColour = this.match2.cellColour;
    this.match1.fieldValue = this.match2.fieldValue;
    this.match1.cellValue = this.match2.cellValue;
    this.match1.cellFn = this.match2.cellFn;
    this.match1.rowFn = this.match2.rowFn;
    this.match1.sidePanel = this.match2;
    this.match1.addClose('Back');

    //
    // Setup buttons
    //
    this.match2.sections._buttons = {'buttons':{
        'merge':{'label':'< Merge', 
            'fn':'M.ciniki_customers_duplicates.mergeCustomers(M.ciniki_customers_duplicates.match1.customer_id, M.ciniki_customers_duplicates.match2.customer_id);'},
        'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_duplicates.showMatch();\',\'mc\',{\'customer_id\':M.ciniki_customers_duplicates.match2.customer_id});'},
        'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_customers_duplicates.deleteCustomer(M.ciniki_customers_duplicates.match2.customer_id);'},
        }};
    this.match1.sections._buttons = {'buttons':{
        'merge':{'label':'Merge >',
            'fn':'M.ciniki_customers_duplicates.mergeCustomers(M.ciniki_customers_duplicates.match2.customer_id, M.ciniki_customers_duplicates.match1.customer_id);'},
        'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_duplicates.showMatch();\',\'mc\',{\'customer_id\':M.ciniki_customers_duplicates.match1.customer_id});'},
        'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_customers_duplicates.deleteCustomer(M.ciniki_customers_duplicates.match1.customer_id);'},
        }};

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_duplicates', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.matchon != null && args.matchon != '' ) {
            this.list.open(cb, args.matchon);
        }

        this.list.open(cb);
    };


    this.showMatch = function(cb, cid1, cid2) {
        
        var mods = M.curTenant.modules;
        if( cid1 != null ) {
            this.match1.customer_id = cid1;
            this.match2.customer_id = cid2;
        }
        this.match1.sections._buttons.buttons.delete.visible = 'yes';
        this.match2.sections._buttons.buttons.delete.visible = 'yes';
        var rsp = M.api.getJSON('ciniki.customers.getFull', {'tnid':M.curTenantID, 'customer_id':this.match1.customer_id});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        this.match1.data = rsp.customer;
        this.match1.data.details = {
            'prefix':{'label':'Title', 'value':rsp.customer.prefix},
            'first':{'label':'First', 'value':rsp.customer.first},
            'middle':{'label':'Middle', 'value':rsp.customer.middle},
            'last':{'label':'Last', 'value':rsp.customer.last},
            'suffix':{'label':'Degrees', 'value':rsp.customer.suffix},
            };
        this.match1.data.business = {
            'company':{'label':'Company', 'value':rsp.customer.company},
            'department':{'label':'Department', 'value':rsp.customer.department},
            'title':{'label':'Title', 'value':rsp.customer.title},
            };
        this.match1.data.phones = {
            'home':{'label':'Home', 'value':rsp.customer.phone_home},
            'work':{'label':'Work', 'value':rsp.customer.phone_work},
            'cell':{'label':'Cell', 'value':rsp.customer.phone_cell},
            'fax':{'label':'Fax', 'value':rsp.customer.phone_fax},
            };
        if( (rsp.customer.emails != null && rsp.customer.emails.length > 0)
            || (rsp.customer.addresses != null && rsp.customer.addresses.length > 0)
            || (rsp.customer.subscriptions != null && rsp.customer.subscriptions.length > 0)
            ) {
            this.match1.sections._buttons.buttons.delete.visible = 'no';
        }
        var rsp = M.api.getJSON('ciniki.customers.getFull', {'tnid':M.curTenantID, 'customer_id':this.match2.customer_id});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        this.match2.data = rsp.customer;
        this.match2.data.details = {
            'prefix':{'label':'Title', 'value':rsp.customer.prefix},
            'first':{'label':'First', 'value':rsp.customer.first},
            'middle':{'label':'Middle', 'value':rsp.customer.middle},
            'last':{'label':'Last', 'value':rsp.customer.last},
            'suffix':{'label':'Degrees', 'value':rsp.customer.suffix},
            };
        this.match2.data.business = {
            'company':{'label':'Company', 'value':rsp.customer.company},
            'department':{'label':'Department', 'value':rsp.customer.department},
            'title':{'label':'Title', 'value':rsp.customer.title},
            };
        this.match2.data.phones = {
            'home':{'label':'Home', 'value':rsp.customer.phone_home},
            'work':{'label':'Work', 'value':rsp.customer.phone_work},
            'cell':{'label':'Cell', 'value':rsp.customer.phone_cell},
            'fax':{'label':'Fax', 'value':rsp.customer.phone_fax},
            };
        if( (rsp.customer.emails != null && rsp.customer.emails.length > 0)
            || (rsp.customer.addresses != null && rsp.customer.addresses.length > 0)
            || (rsp.customer.subscriptions != null && rsp.customer.subscriptions.length > 0)
            ) {
            this.match2.sections._buttons.buttons.delete.visible = 'no';
        }


        // Reset visible sections
        this.match1.sections.subscriptions.visible = 'no';
        this.match2.sections.subscriptions.visible = 'no';
        this.match1.sections.appointments.visible = 'no';
        this.match2.sections.appointments.visible = 'no';
        this.match1.sections.currentwineproduction.visible = 'no';
        this.match2.sections.currentwineproduction.visible = 'no';
        this.match1.sections.pastwineproduction.visible = 'no';
        this.match2.sections.pastwineproduction.visible = 'no';

        // Show sections for activated modules
        if( mods['ciniki.subscriptions'] != null ) {
            this.match1.sections.subscriptions.visible = 'yes';
            this.match2.sections.subscriptions.visible = 'yes';
            if( this.match1.data.subscriptions.length > 0 ) {
                this.match1.sections._buttons.buttons.delete.visible = 'no';
            }
            if( this.match2.data.subscriptions.length > 0 ) {
                this.match2.sections._buttons.buttons.delete.visible = 'no';
            }
        }
        if( mods['ciniki.wineproduction'] != null ) {
            this.match1.sections.appointments.visible = 'yes';
            this.match2.sections.appointments.visible = 'yes';
            this.match1.sections.currentwineproduction.visible = 'yes';
            this.match2.sections.currentwineproduction.visible = 'yes';
            this.match1.sections.pastwineproduction.visible = 'yes';
            this.match2.sections.pastwineproduction.visible = 'yes';
            // Get appointments
            var rsp = M.api.getJSON('ciniki.wineproduction.appointments', {'tnid':M.curTenantID, 'customer_id':this.match1.customer_id, 'status':'unbottled'});
            if( rsp['stat'] != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            this.match1.data.appointments = rsp.appointments;
            var rsp = M.api.getJSON('ciniki.wineproduction.appointments', {'tnid':M.curTenantID, 'customer_id':this.match2.customer_id, 'status':'unbottled'});
            if( rsp['stat'] != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            this.match2.data.appointments = rsp.appointments;
            // Get wine production
            var rsp = M.api.getJSON('ciniki.wineproduction.list', {'tnid':M.curTenantID, 'customer_id':this.match1.customer_id});
            if( rsp['stat'] != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            this.match1.data.currentwineproduction = [];
            this.match1.data.pastwineproduction = [];
            var i = 0;
            for(i in rsp['orders']) {
                var order = rsp['orders'][i]['order'];
                if( order['status'] < 50 ) {
                    this.match1.data.currentwineproduction.push(rsp['orders'][i]);
                } else  {
                    this.match1.data.pastwineproduction.push(rsp['orders'][i]);
                }
            }
            if( rsp.orders != null && rsp.orders.length > 0 ) {
                this.match1.sections._buttons.buttons.delete.visible = 'no';
            }

            // Get second customer wine production
            var rsp = M.api.getJSON('ciniki.wineproduction.list', {'tnid':M.curTenantID, 'customer_id':this.match2.customer_id});
            if( rsp['stat'] != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            this.match2.data.currentwineproduction = [];
            this.match2.data.pastwineproduction = [];
            var i = 0;
            if( rsp.orders != null ) {
                for(i in rsp.orders) {
                    var order = rsp.orders[i].order;
                    if( order['status'] < 50 ) {
                        this.match2.data.currentwineproduction.push(rsp.orders[i]);
                    } else  {
                        this.match2.data.pastwineproduction.push(rsp.orders[i]);
                    }
                }
                if( rsp.orders.length > 0 ) {
                    this.match2.sections._buttons.buttons.delete.visible = 'no';
                }
            }
        }

        this.match1.refresh();
        this.match1.show(cb);
    };

    this.mergeCustomers = function(cid1, cid2) {
        var rsp = M.api.getJSONCb('ciniki.customers.merge', {'tnid':M.curTenantID, 
            'primary_customer_id':cid1, 'secondary_customer_id':cid2}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_duplicates.showMatch();
            });
    };

    this.deleteCustomer = function(cid) {
        if( cid != null && cid > 0 ) {
            M.confirm("Are you sure you want to remove this customer?",null,function() {
                var rsp = M.api.getJSONCb('ciniki.customers.delete', {'tnid':M.curTenantID, 'customer_id':cid}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_duplicates.match1.close();
                });
            });
        }
    }

}
