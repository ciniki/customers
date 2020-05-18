//
function ciniki_customers_ifbupgrade() {

    this.customerStatus = {
        '10':'Active', 
        '50':'Suspended', 
        '60':'Deleted', 
        };
    //
    // The main menu panel
    //
    this.menu = new M.panel('Customers', 'ciniki_customers_ifbupgrade', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.customers.ifbupgrade.menu');
    this.menu.data = {};
    this.menu.sections = {
        'too_many_emails':{'label':'Extra Emails', 'num_cols':2, 'type':'simplegrid', 
            'noData':'None found',
            },
        'too_many_phones':{'label':'Extra Phones', 'num_cols':2, 'type':'simplegrid', 
            'noData':'None found',
            },
        'bad_phone_labels':{'label':'Bad Phone Types', 'num_cols':2, 'type':'simplegrid', 
            'noData':'None found',
            },
        'duplicate_phone_labels':{'label':'Duplicate Phone Types', 'num_cols':2, 'type':'simplegrid', 
            'noData':'None found',
            },
        'too_many_addresses':{'label':'Extra Addresses', 'num_cols':2, 'type':'simplegrid', 
            'noData':'None found',
            },
        'too_many_links':{'label':'Extra Websites', 'num_cols':2, 'type':'simplegrid', 
            'noData':'None found',
            },
        '_buttons':{'label':'', 'visible':'no', 'buttons':{
            'upgrade':{'label':'Perform Upgrade', 'fn':'M.ciniki_customers_ifbupgrade.menu.performUpgrade();'},
            }},
        };
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.display_name;
            case 1: return d.num_items;
        }
    };
    this.menu.rowFn = function(s, i, d) { 
        return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_ifbupgrade.menu.open();\',\'mc\',{\'customer_id\':' + d.id + '});';
    };
    this.menu.performUpgrade = function() {
        if( confirm("Are you sure you're ready to upgrade?") ) {
            M.api.getJSONCb('ciniki.customers.ifbUpgrade', {'tnid':M.curTenantID, 'upgrade':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.alert("Your account is upgraded to IFB, please relogin");
            });
        }
    }
    this.menu.open = function(cb) {
        //
        // Grab list of recently updated customers
        //
        M.api.getJSONCb('ciniki.customers.ifbUpgrade', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_ifbupgrade.menu;
            p.data = rsp; 
            if( rsp.num_issues == 0 ) {
                p.sections._buttons.visible = 'yes';
            } else {
                p.sections._buttons.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_ifbupgrade', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menu.open(cb);
    }
}
