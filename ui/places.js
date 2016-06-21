//
function ciniki_customers_places() {
    //
    // Panels
    //
    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.main = new M.panel('Places',
            'ciniki_customers_places', 'main',
            'mc', 'medium', 'sectioned', 'ciniki.customers.places.main');
        this.main.data = {};
        this.main.cbStacked = 'yes';
        this.main.sections = {
            'places':{'label':'Countries', 'visible':'no', 'type':'simplegrid', 'num_cols':1},
            'noaddr':{'label':'', 'visible':'no', 'type':'simplegrid', 'num_cols':1},
            'customers':{'label':'Customers', 'visible':'no', 'type':'simplegrid', 'num_cols':1},
            };
        this.main.sectionData = function(s) {
            return this.data[s];
        };
        this.main.noData = function(s) { return 'No customers'; }
        this.main.cellValue = function(s, i, j, d) {
            if( s == 'places' ) {
                if( d.place.city != null ) {
                    return (d.place.city==''?'No city':d.place.city) + ' <span class="count">' + d.place.num_customers + '</span>';
                } else if( d.place.province != null ) {
                    return (d.place.province==''?'No province/state':d.place.province) + ' <span class="count">' + d.place.num_customers + '</span>';
                } else {
                    return (d.place.country==''?'No Country':d.place.country) + ' <span class="count">' + d.place.num_customers + '</span>';
                }
            }
            if( s == 'noaddr' ) {
                return 'Customer with no address <span class="count">' + d.place.num_customers + '</span>';
            }
            if( s == 'customers' ) {
                return d.customer.display_name;
            }
        };
        this.main.rowFn = function(s, i, d) { 
            if( s == 'places' ) {
                if( d.place.city != null ) {
                    return 'M.ciniki_customers_places.showMain(\'M.ciniki_customers_places.showMain(null,\\\'' + escape(d.place.country) + '\\\',\\\'' + escape(d.place.province) + '\\\');\',\'' + escape(d.place.country) + '\',\'' + escape(d.place.province) + '\',\'' + escape(d.place.city) + '\');';
                } else if( d.place.province != null ) {
                    return 'M.ciniki_customers_places.showMain(\'M.ciniki_customers_places.showMain(null,\\\'' + escape(d.place.country) + '\\\');\',\'' + escape(d.place.country) + '\',\'' + escape(d.place.province) + '\');';
                } else {
                    return 'M.ciniki_customers_places.showMain(\'M.ciniki_customers_places.showMainReset();\',\'' + escape(d.place.country) + '\');';
                }
            }
            if( s == 'noaddr' ) {
                return 'M.ciniki_customers_places.showMainNoAddr(\'M.ciniki_customers_places.showMainReset();\');';
            }
            if( s == 'customers' ) {
                return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_customers_places.showMain();\',\'mc\',{\'customer_id\':\'' + d.customer.id + '\'});';
            }
        };

        this.main.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_places', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        // Setup ui labels
        this.slabel = 'Customer';
        this.plabel = 'Customers';
        this.childlabel = 'Child';
        this.childrenlabel = 'Children';
        if( M.curBusiness.customers != null ) {
            if( M.curBusiness.customers.settings['ui-labels-child'] != null 
                && M.curBusiness.customers.settings['ui-labels-child'] != ''
                ) {
                this.childlabel = M.curBusiness.customers.settings['ui-labels-child'];
            }
            if( M.curBusiness.customers.settings['ui-labels-children'] != null 
                && M.curBusiness.customers.settings['ui-labels-children'] != ''
                ) {
                this.childrenlabel = M.curBusiness.customers.settings['ui-labels-children'];
            }
            if( M.curBusiness.customers.settings['ui-labels-customer'] != null 
                && M.curBusiness.customers.settings['ui-labels-customer'] != ''
                ) {
                this.slabel = M.curBusiness.customers.settings['ui-labels-customer'];
            }
            if( M.curBusiness.customers.settings['ui-labels-customers'] != null 
                && M.curBusiness.customers.settings['ui-labels-customers'] != ''
                ) {
                this.plabel = M.curBusiness.customers.settings['ui-labels-customers'];
            }
        }
        this.main.title = this.plabel;
        this.main.sections.customers.label = this.plabel;
        
        this.main.country = null;
        this.main.province = null;
        this.main.city = null;
        this.showMain(cb, args.country, args.province, args.city);
    }

    this.showMainReset = function() {
        this.main.country = null;
        this.main.province = null;
        this.main.city = null;
        this.showMain();
    }
    
    this.showMain = function(cb, country, province, city) {
        if( country != null ) { this.main.country = unescape(country); this.main.province = null; this.main.city = null;}
        if( province != null ) { this.main.province = unescape(province); this.main.city = null; }
        if( city != null ) { this.main.city = unescape(city); }
        var args = {'business_id':M.curBusinessID};
        if( this.main.country != null ) { args.country = this.main.country; }
        if( this.main.province != null ) { args.province = this.main.province; }
        if( this.main.city != null ) { args.city = this.main.city; }
        M.api.getJSONCb('ciniki.customers.placeDetails', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_places.main;
            p.data = {};
            if( rsp.places != null ) {
                p.sections.places.visible = 'yes';
                p.data.places = rsp.places;
                p.place_level = rsp.place_level;
                switch(rsp.place_level) {
                    case 'country': p.sections.places.label = 'Countries'; break;
                    case 'province': p.sections.places.label = 'Provinces/States'; break;
                    case 'city': p.sections.places.label = 'Cities'; break;
                }
            } else {
                p.sections.places.visible = 'no';
            }
            if( rsp.place_level == 'country' && rsp.no_addresses != null && rsp.no_addresses > 0 ) {
                p.sections.noaddr.visible = 'yes';
            }
            if( rsp.customers != null ) {
                p.sections.customers.visible = 'yes';
                p.data.customers = rsp.customers;
            } else {
                p.sections.customers.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
}
