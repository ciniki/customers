//
function ciniki_customers_dealertools() {
    //
    // Panels
    //
    this.init = function() {
        this.toggleOptions = {'no':'No', 'yes':'Yes'};
        //
        // The tools menu 
        //
        this.menu = new M.panel('Dealer Tools',
            'ciniki_customers_dealertools', 'menu',
            'mc', 'narrow', 'sectioned', 'ciniki.customers.dealertools.menu');
        this.menu.data = {};
        this.menu.sections = {
            'tools':{'label':'Downloads', 'list':{
                'dealerlist':{'label':'Export Dealers (Excel)', 'fn':'M.ciniki_customers_dealertools.showMemberList(\'M.ciniki_customers_dealertools.showMenu();\');'},
                }},
            };
        this.menu.addClose('Back');

        //
        // The dealer list fields available to download
        //
        this.dealerlist = new M.panel('Dealer List',
            'ciniki_customers_dealertools', 'dealerlist',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.dealertools.dealerlist');
        this.dealerlist.data = {};
        this.dealerlist.sections = {
            'options':{'label':'Data to include', 'aside':'yes', 'fields':{
                'eid':{'label':'Customer ID', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'type':{'label':'Customer Type', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'prefix':{'label':'Name Prefix', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'first':{'label':'First Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'middle':{'label':'Middle Name', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'last':{'label':'Last Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'suffix':{'label':'Name Suffix', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'company':{'label':'Company', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'department':{'label':'Department', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'title':{'label':'Title', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'visible':{'label':'Web Visible', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                }},
            'options2':{'label':'', 'aside':'yes', 'fields':{
                'tax_number':{'label':'Tax Number', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'tax_location_code':{'label':'Tax Code', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'start_date':{'label':'Start Date', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                }},
            'options3':{'label':'', 'fields':{
                'dealer_status':{'label':'Status', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'dealer_categories':{'label':'Categories', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'phones':{'label':'Phone Numbers', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'emails':{'label':'Emails', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'addresses':{'label':'Addresses', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'links':{'label':'Websites', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'primary_image':{'label':'Image', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'primary_image_caption':{'label':'Image Caption', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'short_description':{'label':'Short Bio', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                'full_bio':{'label':'Full Bio', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
                }},
            '_buttons':{'label':'', 'buttons':{
                'selectall':{'label':'Select All', 'fn':'M.ciniki_customers_dealertools.selectAll();'},
                'download':{'label':'Download Excel', 'fn':'M.ciniki_customers_dealertools.downloadListExcel();'},
                }},
            };
        this.dealerlist.fieldValue = function(s, i, j, d) {
            return M.ciniki_customers_dealertools.dealerlist.sections[s].fields[i].default;
        };
        this.dealerlist.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_dealertools', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        var slabel = 'Dealer';
        var plabel = 'Dealers';
        if( M.curTenant.customers != null ) {
            if( M.curTenant.customers.settings['ui-labels-dealer'] != null 
                && M.curTenant.customers.settings['ui-labels-dealer'] != ''
                ) {
                slabel = M.curTenant.customers.settings['ui-labels-dealer'];
            }
            if( M.curTenant.customers.settings['ui-labels-dealers'] != null 
                && M.curTenant.customers.settings['ui-labels-dealers'] != ''
                ) {
                plabel = M.curTenant.customers.settings['ui-labels-dealers'];
            }
        }
        this.menu.title = slabel + ' Tools';
        this.dealerlist.title = 'Export ' + plabel;
        this.menu.sections.tools.list.dealerlist.label = 'Export ' + plabel + ' (Excel)';

        var flags = M.curTenant.modules['ciniki.customers'].flags;
        this.dealerlist.sections.options2.fields.tax_number.active=((flags&0x20000)>0?'yes':'no');
        this.dealerlist.sections.options2.fields.tax_location_code.active=((flags&0x40000)>0?'yes':'no');
        this.dealerlist.sections.options2.fields.start_date.active='yes';

        this.showMenu(cb);
    }

    //
    // Grab the stats for the tenant from the database and present the list of orders.
    //
    this.showMenu = function(cb) {
        this.menu.refresh();
        this.menu.show(cb);
    };

    this.downloadDirectory = function() {
        M.api.openFile('ciniki.customers.dealerDownloadDirectory', {'tnid':M.curTenantID});
    };

    this.showMemberList = function(cb) {
        this.dealerlist.refresh();
        this.dealerlist.show(cb);
    };

    this.selectAll = function() {
        var fields = this.dealerlist.sections.options.fields;
        for(i in fields) {
            if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
            this.dealerlist.setFieldValue(i, 'yes')
        }
        fields = this.dealerlist.sections.options2.fields;
        for(i in fields) {
            if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
            this.dealerlist.setFieldValue(i, 'yes')
        }
        fields = this.dealerlist.sections.options3.fields;
        for(i in fields) {
            if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
            this.dealerlist.setFieldValue(i, 'yes')
        }
    }

    this.downloadListExcel = function() {   
        var cols = '';
        var fields = this.dealerlist.sections.options.fields;
        for(i in fields) {
            if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
            if( this.dealerlist.formFieldValue(fields[i], i) == 'yes' ) {
                cols += (cols!=''?'::':'') + i;
            }
        }
        fields = this.dealerlist.sections.options2.fields;
        for(i in fields) {
            if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
            if( this.dealerlist.formFieldValue(fields[i], i) == 'yes' ) {
                cols += (cols!=''?'::':'') + i;
            }
        }
        fields = this.dealerlist.sections.options3.fields;
        for(i in fields) {
            if( fields[i].active != null && fields[i].active == 'no' ) { continue; }
            if( this.dealerlist.formFieldValue(fields[i], i) == 'yes' ) {
                cols += (cols!=''?'::':'') + i;
            }
        }
        M.api.openFile('ciniki.customers.dealerDownloadExcel', {'tnid':M.curTenantID, 'columns':cols});
    };
}
