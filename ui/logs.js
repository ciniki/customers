//
// The panel to display the logs for customers
//
function ciniki_customers_logs() {
    //
    // Setup the main panel to list the members 
    //
    this.menu = new M.panel('Members', 'ciniki_customers_logs', 'menu', 'mc', 'full', 'sectioned', 'ciniki.customers.logs.menu');
    this.menu.data = {};
    this.menu.sections = {
//        'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 
//            'cellClasses':['multiline','multiline'],
//            'hint':'name, company or email', 'noData':'No members found',
//            },
        'logs':{'label':'', 'type':'simplegrid', 'num_cols':7,
            'headerValues':['Date', 'Status', 'IP', 'Action', 'Email', 'Code', 'Msg'],
            'cellClasses':['', ''],
            'noData':'No logs',
            },
        };
    this.menu.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.customers.logSearch', {'tnid':M.curTenantID, 'start_needle':value, 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_customers_logs.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_logs.menu.panelUID + '_' + s), rsp.customers); 
                });
            return true;
        }
    };
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            return this.cellValue(s, i, j, d);
        }
        return '';
    }
//    this.menu.liveSearchSubmitFn = function(s, search_str) {
//        M.startApp('ciniki.customers.main',null,'M.ciniki_customers_logs.showMenu();','mc',{'type':'members', 'search':search_str});
//    };
    this.menu.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.log_date;
            case 1: return d.status_text;
            case 2: return d.ip_address;
            case 3: return d.action;
            case 4: return d.email;
            case 5: return d.error_code;
            case 6: return d.error_msg;
        }
    };
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.customers.logList', {'tnid':M.curTenantID, 'limit':'100'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_logs.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('refresh', 'Refresh', 'M.ciniki_customers_logs.menu.open();');
    this.menu.addClose('Back');

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_logs', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        this.menu.open(cb);
    }
}
