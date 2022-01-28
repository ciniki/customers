//
function ciniki_customers_settings() {
    //
    // Panels
    //
    this.main = null;
    this.add = null;

    this.toggleOptions = {'no':'Off', 'yes':'On'};
    this.formOptions = {'person':'Person', 'business':'Business'};
    this.typeOptions = {'person':'Person', 'business':'Business'};
    this.businessFormats = {
        'company':'Company',
        'company - person':'Company - Person',
        'person - company':'Person - Company',
        'company [person]':'Company [Person]',
        'person [company]':'Person [Company]',
    };
    this.callsignFormats = {
        'beforedash':'CALLSIGN - person',
        'afterdash':'person - CALLSIGN',
    };
    this.seasonFlags = {
        '1':{'name':'Current'},
        '2':{'name':'Active'},
        };

    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.main = new M.panel('Settings',
            'ciniki_customers_settings', 'main',
            'mc', 'medium', 'sectioned', 'ciniki.customers.settings.main');
        this.main.sections = {
            '_options':{'label':'Options', 
                'visible':function() { return (M.userPerms&0x01) == 0x01 ? 'yes' : 'hidden'; },
                'fields':{
                    'intro-photo':{'label':'Intro Photo', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
            }},
            'name_options':{'label':'Name Format', 'fields':{
                'display-name-business-format':{'label':'Business', 'type':'select', 'options':this.businessFormats},
                'display-name-callsign-format':{'label':'Business', 'type':'select', 'options':this.callsignFormats,
                    'visible': function() { return M.modFlagSet('ciniki.customers', 0x0400); },
                    },
            }},
            'defaults':{'label':'Defaults', 'visible':'yes', 'fields':{
                'defaults-edit-form':{'label':'Edit Form', 'type':'toggle', 'toggles':{'person':'Person', 'business':'Business'}},
                'defaults-edit-person-hide-company':{'label':'Hide Company', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'ui-show-places':{'label':'Show Places', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            }},
/*            
            
            'ui_labels':{'label':'Labels', 'visible':'no', 'fields':{
                'ui-labels-parent':{'label':'Parent Name', 'type':'text', 'hint':'Parent'},
                'ui-labels-parents':{'label':'Parent Plural', 'type':'text', 'hint':'Parents'},
                'ui-labels-child':{'label':'Child Name', 'type':'text', 'hint':'Child'},
                'ui-labels-children':{'label':'Child Name Plural', 'type':'text', 'hint':'Children'},
                'ui-labels-customer':{'label':'Customer Name', 'type':'text', 'hint':'Customer'},
                'ui-labels-customers':{'label':'Customer Plural', 'type':'text', 'hint':'Customers'},
                'ui-labels-member':{'label':'Member Name', 'type':'text', 'hint':'Member'},
                'ui-labels-members':{'label':'Member Plural', 'type':'text', 'hint':'Members'},
                'ui-labels-dealer':{'label':'Dealer Name', 'type':'text', 'hint':'Dealer'},
                'ui-labels-dealers':{'label':'Dealer Plural', 'type':'text', 'hint':'Dealers'},
                'ui-labels-distributor':{'label':'Distributor Name', 'type':'text', 'hint':'Distributor'},
                'ui-labels-distributors':{'label':'Distributor Plural', 'type':'text', 'hint':'Distributors'},
            }}, */
            'seasons':{'label':'Membership Seasons', 'visible':'no', 'type':'simplegrid',
                'num_cols':1,
                'addTxt':'Add Seasons',
                'addFn':'M.ciniki_customers_settings.editSeason(\'M.ciniki_customers_settings.showMain();\',0);',
            },
            'membership_types':{'label':'Membership Types', 
                'active':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x0a)==0x02?'yes':'no'; },
                'fields':{
                    'membership-type-10-active':{'label':'Regular', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-20-active':{'label':'Student', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-30-active':{'label':'Individual', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-40-active':{'label':'Family', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-110-active':{'label':'Complimentary', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-150-active':{'label':'Reciprocal', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-lifetime-active':{'label':'Lifetime', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                 },
            },
            'membership_prices':{'label':'Online Membership Sales', 
                'active':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x0a)==0x02&&M.curTenant.modules['ciniki.sapos']!=null&&(M.curTenant.modules['ciniki.sapos'].flags&0x08)>0?'yes':'no'; },
                'fields':{
                    'membership-type-10-online':{'label':'Regular', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-10-name':{'label':'Regular Name', 'type':'text'},
                    'membership-type-10-price':{'label':'Regular Price', 'type':'text', 'size':'small'},
                    'membership-type-20-online':{'label':'Student', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-20-name':{'label':'Student Name', 'type':'text'},
                    'membership-type-20-price':{'label':'Student Price', 'type':'text', 'size':'small'},
                    'membership-type-30-online':{'label':'Individual', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-30-name':{'label':'Individual Name', 'type':'text'},
                    'membership-type-30-price':{'label':'Individual Price', 'type':'text', 'size':'small'},
                    'membership-type-40-online':{'label':'Family', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-40-name':{'label':'Family Name', 'type':'text'},
                    'membership-type-40-price':{'label':'Family Price', 'type':'text', 'size':'small'},
                    'membership-type-lifetime-online':{'label':'Lifetime', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                    'membership-type-lifetime-name':{'label':'Lifename Name', 'type':'text'},
                    'membership-type-lifetime-price':{'label':'Lifetime Price', 'type':'text', 'size':'small'},
                 },
            },
            'ui_colours':{'label':'Status Colours', 'visible':'yes', 'fields':{
                'ui-colours-customer-status-10':{'label':'Active', 'type':'colour'},
                'ui-colours-customer-status-40':{'label':'On Hold', 'type':'colour'},
                'ui-colours-customer-status-50':{'label':'Suspended', 'type':'colour'},
                'ui-colours-customer-status-60':{'label':'Deleted', 'type':'colour'},
            }},
            'dropbox':{'label':'Dropbox Profiles', 
                'visible':function() { return (M.modFlagOn('ciniki.customers', 0x0800000000) ? 'yes' : 'hidden'); },
                'fields':{
                    'dropbox-customerprofiles':{'label':'Directory', 'type':'text'},
                }},
            'revertSeasons':{'label':'Revert From Seasons', 
                'visible':function() { return (M.userPerms&0x01) == 0x01 && (M.modFlagOn('ciniki.customers', 0x02000000) ? 'yes' : 'hidden'); },
                'buttons':{
                    'revert':{'label':'Update Last Paid Dates', 'fn':'M.ciniki_customers_settings.revertFromSeasons();'},
                }},
//          '_types':{'label':'Customer Types', 'type':'gridform', 'rows':8, 'cols':3, 
//              'header':['Name', 'Form', 'Type'],
//              'fields':[
//              [   {'id':'types-1-label', 'label':'Name', 'type':'text'},
//                  {'id':'types-1-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//                  {'id':'types-1-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//              ],[ {'id':'types-2-label', 'label':'Name', 'type':'text'},
//                  {'id':'types-2-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//                  {'id':'types-2-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//              ],[ {'id':'types-3-label', 'label':'Name', 'type':'text'},
//                  {'id':'types-3-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//                  {'id':'types-3-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//              ],[ {'id':'types-4-label', 'label':'Name', 'type':'text'},
//                  {'id':'types-4-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//                  {'id':'types-4-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//              ],[ {'id':'types-5-label', 'label':'Name', 'type':'text'},
//                  {'id':'types-5-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//                  {'id':'types-5-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//              ],[ {'id':'types-6-label', 'label':'Name', 'type':'text'},
//                  {'id':'types-6-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//                  {'id':'types-6-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//              ],[ {'id':'types-7-label', 'label':'Name', 'type':'text'},
//                  {'id':'types-7-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//                  {'id':'types-7-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//              ],[ {'id':'types-8-label', 'label':'Name', 'type':'text'},
//                  {'id':'types-8-form', 'label':'Form', 'type':'select', 'options':this.formOptions},
//                  {'id':'types-8-type', 'label':'Form', 'type':'select', 'options':this.typeOptions},
//              ]],
//          },
        };
        this.main.sectionData = function(s) { 
            if( s == 'seasons' ) { return this.data[s]; }
            return this.data; 
        }
        this.main.fieldValue = function(s, i, d) { 
            if( this.data[i] == null ) {    
                if( s == 'ui_colours' ) { return '#FFFFFF'; }
                return ''; 
            }
            return this.data[i];
        };
        this.main.cellValue = function(s, i, j, d) {
            if( s == 'seasons' ) {
                return d.season.name + ((d.season.flags&0x02)>0?' <span class="subdue">[Active]</span>':'');
            }
        };
        this.main.rowFn = function(s, i, d) {
            if( s == 'seasons' ) {
                return 'M.ciniki_customers_settings.editSeason(\'M.ciniki_customers_settings.showMain();\',\'' + d.season.id + '\');';
            }
        }
        this.main.fieldHistoryArgs = function(s, i) {
            if( s == 'seasons' ) {
                return {'method':'ciniki.customers.seasonHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
            }
            return {'method':'ciniki.customers.getSettingHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
        };
        this.main.addButton('save', 'Save', 'M.ciniki_customers_settings.saveSettings();');
        this.main.addClose('Cancel');

        //
        // The panel to add/edit a season
        //
        this.season = new M.panel('Membership Season',
            'ciniki_customers_settings', 'season',
            'mc', 'medium', 'sectioned', 'ciniki.customers.settings.season');
        this.season.sections = {
            'season':{'label':'Season', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'start_date':{'label':'Start Date', 'type':'date'},
                'end_date':{'label':'End Date', 'type':'date'},
                'flags':{'label':'Options', 'type':'flags', 'flags':this.seasonFlags},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_customers_settings.saveSeason();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_customers_settings.deleteSeason();'},
                }},
        }
        this.season.fieldValue = function(s, i, d) { 
            if( this.data[i] == null ) { return ''; }
            return this.data[i];
        };
        this.season.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.customers.seasonHistory', 
                'args':{'tnid':M.curTenantID, 'field':i}};
        };
        this.season.addButton('save', 'Save', 'M.ciniki_customers_settings.saveSeason();');
        this.season.addClose('Cancel');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( (M.curTenant.modules['ciniki.customers'].flags&0x02000000) > 0 ) {
            M.ciniki_customers_settings.main.sections.seasons.visible = 'yes';
        } else {
            M.ciniki_customers_settings.main.sections.seasons.visible = 'no';
        }
        
/*      ** Deprecated ui-labels- 2020-07-14 **

        M.ciniki_customers_settings.main.sections.ui_labels.visible = 'no';

        if( (M.curTenant.modules['ciniki.customers'].flags&0x01) > 0 ) {
            M.ciniki_customers_settings.main.sections.ui_labels.visible = 'yes';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-customer'].active = 'yes';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-customers'].active = 'yes';
        } else {
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-customer'].active = 'no';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-customers'].active = 'no';
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x02) > 0 ) {
            M.ciniki_customers_settings.main.sections.ui_labels.visible = 'yes';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-member'].active = 'yes';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-members'].active = 'yes';
        } else {
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-member'].active = 'no';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-members'].active = 'no';
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x10) > 0 ) {
            M.ciniki_customers_settings.main.sections.ui_labels.visible = 'yes';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-dealer'].active = 'yes';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-dealers'].active = 'yes';
        } else {
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-dealer'].active = 'no';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-dealers'].active = 'no';
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x0100) > 0 ) {
            M.ciniki_customers_settings.main.sections.ui_labels.visible = 'yes';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-distributor'].active = 'yes';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-distributors'].active = 'yes';
        } else {
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-distributor'].active = 'no';
            M.ciniki_customers_settings.main.sections.ui_labels.fields['ui-labels-distributors'].active = 'no';
        }
*/
        this.showMain(cb);
    }

    //
    // Grab the stats for the business from the database and present the list of orders.
    //
    this.showMain = function(cb) {
        var rsp = M.api.getJSONCb('ciniki.customers.getSettings', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_settings.main;
            p.data = rsp.settings;
            if( rsp.seasons != null ) {
                p.data.seasons = rsp.seasons;
            }
            p.refresh();
            p.show(cb);
        });
    }

    this.revertFromSeasons = function(cb) {
        M.confirm('Are you sure you want to update last paid date?',null,function() {
            M.api.getJSONCb('ciniki.customers.revertFromSeasons', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert('Done');
            });
        });
    }

    this.saveSettings = function() {
        var c = this.main.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.customers.updateSettings', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_customers_settings.main.close();
            });
        } else {
            this.main.close();
        }
    }

    this.editSeason = function(cb, pid) {
        if( pid != null ) { this.season.season_id = pid; }
        if( this.season.season_id > 0 ) {
            this.season.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.customers.seasonGet', {'tnid':M.curTenantID, 
                'season_id':this.season.season_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_settings.season;
                    p.data = rsp.season;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.season.sections._buttons.buttons.delete.visible = 'no';
            this.season.data = {};
            this.season.refresh();
            this.season.show(cb);
        }
    };

    this.saveSeason = function() {
        if( this.season.season_id > 0 ) {
            var c = this.season.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.customers.seasonUpdate', 
                    {'tnid':M.curTenantID, 
                    'season_id':M.ciniki_customers_settings.season.season_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_customers_settings.season.close();
                    });
            } else {
                this.season.close();
            }
        } else {
            var c = this.season.serializeForm('yes');
            M.api.postJSONCb('ciniki.customers.seasonAdd', 
                {'tnid':M.curTenantID, 'season_id':this.season.season_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_customers_settings.season.close();
                });
        }
    };

    this.deleteSeason = function() {
        M.confirm("Are you sure you want to remove this season?",null,function() {
            M.api.getJSONCb('ciniki.customers.seasonDelete', 
                {'tnid':M.curTenantID, 
                'season_id':M.ciniki_customers_settings.season.season_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_settings.season.close(); 
                });
        });
    };
}
