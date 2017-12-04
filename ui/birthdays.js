//
function ciniki_customers_birthdays() {
    //
    // The main panel
    //
    this.main = new M.panel('On Hold', 'ciniki_customers_birthdays', 'main', 'mc', 'xlarge', 'sectioned', 'ciniki.customers.birthdays.main');
    this.main.data = {};
    this.main.status = 60;
    this.main.sections = {
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'upcoming', 'tabs':{
            'upcoming':{'label':'Upcoming', 'fn':'M.ciniki_customers_birthdays.main.switchTab("upcoming");'},
            'missing':{'label':'Missing', 'fn':'M.ciniki_customers_birthdays.main.switchTab("missing");'},
            'incorrect':{'label':'Incorrect', 'fn':'M.ciniki_customers_birthdays.main.switchTab("incorrect");'},
            }},
        'customers':{'label':'Upcoming Birthdays', 'num_cols':5, 'type':'simplegrid', 
            'headerValues':['Status', 'Name', 'Date of Birth', 'Email', 'Address'],
            'cellClasses':['', '', '', '', ''],
            'noData':'No birthdays found',
            },
        };
    this.main.noData = function(s) { return this.sections[s].noData; }
    this.main.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.open();
    }
    this.main.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.status_text;
            case 1: return d.display_name;
            case 2: return d.birthdate;
            case 3: return d.email;
            case 4: return d.address;
        }
        return '';
    };
    this.main.rowFn = function(s, i, d) { 
        return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_customers_birthdays.main.open();\',\'mc\',{\'customer_id\':\'' + d.id + '\'});';
    };
    this.main.open = function(cb) {
        //
        // Grab list of recently updated customers
        //
        var args = {'tnid':M.curTenantID};
        if( this.sections._tabs.selected == 'upcoming' ) {
            args['query'] = 'upcoming';
            args['days'] = 30;
        } else if( this.sections._tabs.selected == 'missing' ) {
            args['query'] = 'missing';
        } else if( this.sections._tabs.selected == 'incorrect' ) {
            args['query'] = 'incorrect';
        }
        M.api.getJSONCb('ciniki.customers.birthdays', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_birthdays.main;
            p.data.customers = rsp.customers;
            p.refresh();
            p.show(cb);
            });
    };
    this.main.addClose('Back');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_birthdays', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.main.open(cb);
    };
}
