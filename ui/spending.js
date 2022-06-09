//
// The following tools are for the programs and offerings
//
function ciniki_customers_spending() {
    //
    // The panel to display the student report
    //
    this.spending = new M.panel('Customer Spending', 'ciniki_customers_spending', 'spending', 'mc', 'full', 'sectioned', 'ciniki.customers.spending.spending');
    this.spending.data = {};  
    this.spending.start_date = '';
    this.spending.end_date = '';
    this.spending.sections = {
        'dates':{'label':'Date Range', 'aside':'yes', 'fields':{
            'start_date':{'label':'Start Date', 'type':'date', 'onchangeFn':'M.ciniki_customers_spending.spending.updateDate();'},
            'end_date':{'label':'End Date', 'type':'date', 'onchangeFn':'M.ciniki_customers_spending.spending.updateDate();'},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'size':'half', 'buttons':{
            'refresh':{'label':'Refresh', 'fn':'M.ciniki_customers_spending.spending.open();'},
            'download':{'label':'Download Excel', 'fn':'M.ciniki_customers_spending.spending.downloadExcel();'},
            }},
        'customers':{'label':'Customers',
            'type':'simplegrid', 'num_cols':3,
            'headerValues':['Customer', 'Member', 'Total'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'date', 'date', 'number'],
            'cellClasses':['', '', '', '', ''],
            }
    }
    this.spending.cellValue = function(s, i, j, d) {
        if( d[this.sections[s].dataMaps[j]] != null ) {
            if( j > 1 ) {
                return M.formatDollar(d[this.sections[s].dataMaps[j]]);
            } else {
                return d[this.sections[s].dataMaps[j]];
            }
        } 
        return '';
    }
    this.spending.fieldValue = function(s, i, d) {
        if( i == 'start_date' ) { return this.start_date; }
        if( i == 'end_date' ) { return this.end_date; }
    }
    this.spending.updateDate = function() {
        this.start_date = this.formValue('start_date');
        this.end_date = this.formValue('end_date');
    }
    this.spending.open = function(cb) {
        if( this.start_date != '' ) {
            M.api.getJSONCb('ciniki.customers.reportCustomerCategories', {'tnid':M.curTenantID, 'start_date':this.start_date, 'end_date':this.end_date}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_spending.spending;
                p.data = rsp;
                p.sections.customers.headerValues = ['Customer', 'Member'];
                p.sections.customers.cellClasses = ['', ''];
                p.sections.customers.sortTypes = ['text', 'text'];
                p.sections.customers.dataMaps = ['display_name', 'member_status'];
                for(var i in rsp.categories) {
                    p.sections.customers.headerValues.push(rsp.categories[i]);
                    p.sections.customers.cellClasses.push('alignright');
                    p.sections.customers.sortTypes.push('number');
                    p.sections.customers.dataMaps.push(rsp.categories[i]);
                }
                p.sections.customers.headerValues.push('Total');
                p.sections.customers.cellClasses.push('alignright');
                p.sections.customers.headerClasses = p.sections.customers.cellClasses;
                p.sections.customers.sortTypes.push('number');
                p.sections.customers.dataMaps.push('total_amount');
                p.sections.customers.num_cols = p.sections.customers.headerValues.length;
                p.refresh();
                p.show(cb);
            });
        } else {
            this.data = {};
            this.refresh();
            this.show(cb);
        }
    }
    this.spending.downloadExcel = function() {
        M.api.openFile('ciniki.customers.reportCustomerCategories', {'tnid':M.curTenantID, 'start_date':this.start_date, 'end_date':this.end_date, 'output':'excel'});
    }
    this.spending.addClose('Back');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_spending', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.spending.open(cb);
    }
}
