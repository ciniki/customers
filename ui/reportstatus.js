//
function ciniki_customers_reportstatus() {
    //
    // Panels
    //
    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.list = new M.panel('On Hold',
            'ciniki_customers_reportstatus', 'list',
            'mc', 'medium', 'sectioned', 'ciniki.customers.reportstatus.list');
        this.list.data = {};
        this.list.status = 60;
        this.list.sections = {
            'customers':{'label':'Customers', 'num_cols':3, 'type':'simplegrid', 
                'headerValues':['ID', 'Name', 'Status'],
                'noData':'No customers found',
                },
            };
        this.list.sectionData = function(s) {
            return this.data[s];
        };
        this.list.noData = function(s) { return this.sections[s].noData; }
        this.list.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.id;
                case 1: return d.display_name;
                case 2: return d.status_text;
            }
            return '';
        };
        this.list.rowFn = function(s, i, d) { 
            return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_customers_reportstatus.showList();\',\'mc\',{\'customer_id\':\'' + d.id + '\'});';
        };
        this.list.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_reportstatus', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.showList(cb, args.status);
    };

    //
    // Grab the stats for the tenant from the database and present the list of customers.
    //
    this.showList = function(cb, s) {
        if( s != null ) { this.list.status = s; }
        //
        // Grab list of recently updated customers
        //
        M.api.getJSONCb('ciniki.customers.customerList', {'tnid':M.curTenantID,
            'status':this.list.status}, function(rsp) {
            if( rsp['stat'] != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_reportstatus.list;
            p.data.customers = rsp.customers;
            p.refresh();
            p.show(cb);
            });
    };
}
