//
function ciniki_customers_connections() {
    //
    // Panels
    //
    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.list = new M.panel('Connections',
            'ciniki_customers_connections', 'list',
            'mc', 'medium', 'sectioned', 'ciniki.customers.connections.list');
        this.list.data = {};
        this.list.sections = {
            'connections':{'label':'', 'num_cols':2, 'type':'simplegrid', 
                'headerValues':['Connection', 'Customers'],
                'sortTypes':['text', 'number'],
                'noData':'No connections found',
                },
            };
        this.list.sectionData = function(s) {
            return this.data[s];
        };
        this.list.noData = function(s) { return this.sections[s].noData; }
        this.list.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.connection.connection;
                case 1: return d.connection.num_customers;
            }
            return '';
        };
        this.list.rowFn = function(s, i, d) { 
//          return 'M.ciniki_customers_connection.showConnection(\'' + d.connection.connection + '\');';
            return '';
        };
        this.list.addClose('Back');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_connections', 'yes');
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
        M.api.getJSONCb('ciniki.customers.connectionList', {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_connections.list;
            p.data.connections = rsp.connections;
            p.refresh();
            p.show(cb);
        });
    };
}
