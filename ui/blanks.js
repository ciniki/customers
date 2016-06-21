//
function ciniki_customers_blanks() {
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

    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.list = new M.panel('Blank Customers',
            'ciniki_customers_blanks', 'list',
            'mc', 'medium', 'sectioned', 'ciniki.customers.blanks.list');
        this.list.data = {};
        this.list.sections = {
            'customers':{'label':'Blank Customers', 'num_cols':3, 'type':'simplegrid', 
                'headerValues':['ID', 'Firstname', 'Lastname'],
                'noData':'No blank customers found',
                },
            };
        this.list.sectionData = function(s) {
            return this.data[s];
        };
        this.list.noData = function(s) { return this.sections[s].noData; }
        this.list.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.customer.id;
                case 1: return d.customer.first;
                case 2: return d.customer.last;
            }
            return '';
        };
        this.list.rowFn = function(s, i, d) { 
            return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_customers_blanks.showList();\',\'mc\',{\'customer_id\':\'' + d.customer.id + '\'});';
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_blanks', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.showList(cb);
    };

    //
    // Grab the stats for the business from the database and present the list of customers.
    //
    this.showList = function(cb) {
        //
        // Grab list of recently updated customers
        //
        var rsp = M.api.getJSON('ciniki.customers.blankFind', {'business_id':M.curBusinessID});
        if( rsp['stat'] != 'ok' ) {
            M.api.err(rsp);
            return false;
        } 
        this.list.data.customers = rsp.customers;
        this.list.refresh();
        this.list.show(cb);
    };
}
