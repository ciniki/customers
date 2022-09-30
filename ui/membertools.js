//
function ciniki_customers_membertools() {
    //
    // Panels
    //
    this.toggleOptions = {'no':'No', 'yes':'Yes'};

    //
    // The tools menu 
    //
    this.menu = new M.panel('Member Tools',
        'ciniki_customers_membertools', 'menu',
        'mc', 'narrow', 'sectioned', 'ciniki.customers.membertools.menu');
    this.menu.data = {};
    this.menu.sections = {
        'tools':{'label':'Downloads', 'list':{
            'directory':{'label':'Directory (Word)', 'fn':'M.ciniki_customers_membertools.downloadDirectory();'},
            'pdfdirectory':{'label':'Directory (PDF)', 'fn':'M.ciniki_customers_membertools.showPDFDirectory(\'M.ciniki_customers_membertools.menu.open();\');'},
//              'phonelist':{'label':'Phone List (PDF)', 'fn':'M.ciniki_customers_membertools.downloadPhoneList();'},
            'memberlist':{'label':'Member List (Excel)', 'fn':'M.startApp(\'ciniki.customers.download\',null,\'M.ciniki_customers_membertools.menu.open();\',\'mc\',{\'membersonly\':\'yes\'});'},
            'membercontactinfo':{'label':'Member Contact Info (PDF)', 'fn':'M.ciniki_customers_membertools.showPDFContactInfo(\'M.ciniki_customers_membertools.menu.open();\');'},
            }},
        'memberlists':{'label':'Season Lists', 
            'visible':function() { return M.modFlagSet('ciniki.customers', 0x02000000); },
            'list':{
            }},
        'other':{'label':'Tools', 
            'visible':function() {return M.modFlagSet('ciniki.customers', 0x04); },
            'list':{
                'categories':{'label':'Update Member Categories', 
                    'visible':function() {return M.modFlagSet('ciniki.customers', 0x04); },
                    'fn':'M.ciniki_customers_membertools.tags.open(\'M.ciniki_customers_membertools.menu.open();\',\'40\',\'Member Categories\');',
                    },
            }},
        };
    this.menu.open = function(cb) {
        this.sections.memberlists.list = {};
        if( M.curTenant.modules['ciniki.customers'].settings != null && M.curTenant.modules['ciniki.customers'].settings.seasons != null ) {
            for(var i in M.curTenant.modules['ciniki.customers'].settings.seasons) {
                this.sections.memberlists.list[i] = {'label':M.curTenant.modules['ciniki.customers'].settings.seasons[i].season.name,
                    'fn':'M.ciniki_customers_membertools.downloadPDFSeasonList(\'' + M.curTenant.modules['ciniki.customers'].settings.seasons[i].season.id + '\',\'' + M.curTenant.modules['ciniki.customers'].settings.seasons[i].season.name + ' Active Members\');'};
            }
        }
        this.refresh();
        this.show(cb);
    };
    this.menu.addClose('Back');

    //
    // The pdf generator menu
    //
    this.pdf = new M.panel('Member Directory',
        'ciniki_customers_membertools', 'pdf',
        'mc', 'medium', 'sectioned', 'ciniki.customers.membertools.pdf');
    this.pdf.data = {};
    this.pdf.forms = {};
    this.pdf.formtab = 'fullpage';
    this.pdf.formtabs = {'label':'', 'field':'layout', 'tabs':{
        'fullpage':{'label':'8.5x11'},
        'halfpage':{'label':'5.5x8.5'},
        }};
    this.pdf.forms.fullpage = {
        'details':{'label':'', 'fields':{
            'coverpage':{'label':'Cover Page', 'type':'toggle', 'none':'yes', 'toggles':this.toggleOptions},
            'title':{'label':'Title', 'type':'text'},
            'toc':{'label':'Table of Contents', 'type':'toggle', 'none':'yes', 'toggles':this.toggleOptions},
            }},
        'categories':{'label':'Categories', 'fields':{
            }},
        '_buttons':{'label':'', 'buttons':{
            'download':{'label':'Download PDF', 'fn':'M.ciniki_customers_membertools.downloadPDFDirectory();'},
            }},
    };
    this.pdf.forms.halfpage = {
        'details':{'label':'', 'fields':{
            'coverpage':{'label':'Cover Page', 'type':'toggle', 'none':'yes', 'toggles':this.toggleOptions},
            'title':{'label':'Title', 'type':'text'},
            'toc':{'label':'Table of Contents', 'type':'toggle', 'none':'yes', 'toggles':this.toggleOptions},
            }},
        'categories':{'label':'Categories', 'fields':{
            }},
        '_buttons':{'label':'', 'buttons':{
            'download':{'label':'Download PDF', 'fn':'M.ciniki_customers_membertools.downloadPDFDirectory();'},
            }},
    };
    this.pdf.fieldValue = function(s, i, d) {
        if( this.data[i] == 'null' ) { return ''; }
        return this.data[i];
    };
    this.pdf.sectionData = function(s) {
        return this.data[s];
    };
    this.pdf.addClose('Cancel');

    //
    // The pdf generator menu
    //
    this.contactinfo = new M.panel('Member Contact Info',
        'ciniki_customers_membertools', 'contactinfo',
        'mc', 'medium', 'sectioned', 'ciniki.customers.membertools.contactinfo');
    this.contactinfo.data = {};
//      this.contactinfo.forms = {};
//      this.pdf.formtab = 'fullpage';
//      this.pdf.formtabs = {'label':'', 'field':'layout', 'tabs':{
//          'fullpage':{'label':'8.5x11'},
//          'halfpage':{'label':'5.5x8.5'},
//          }};
    this.contactinfo.sections = {
        'details':{'label':'', 'fields':{
//              'coverpage':{'label':'Cover Page', 'type':'toggle', 'none':'yes', 'toggles':this.toggleOptions},
            'title':{'label':'Title', 'type':'text'},
//              'toc':{'label':'Table of Contents', 'type':'toggle', 'none':'yes', 'toggles':this.toggleOptions},
            'private':{'label':'Private Phone/Emails', 'type':'toggle', 'default':'no', 'none':'yes', 'toggles':this.toggleOptions},
            }},
//          'categories':{'label':'Categories', 'fields':{
//              }},
        '_buttons':{'label':'', 'buttons':{
            'download':{'label':'Download PDF', 'fn':'M.ciniki_customers_membertools.downloadPDFContactInfo();'},
            }},
    };
    this.contactinfo.fieldValue = function(s, i, d) {
        if( this.data[i] == 'null' ) { return ''; }
        return this.data[i];
    };
    this.contactinfo.sectionData = function(s) {
        return this.data[s];
    };
    this.contactinfo.addClose('Cancel');

    //
    // The member category update
    //
    this.tags = new M.panel('Tags',
        'ciniki_customers_membertools', 'tags',
        'mc', 'medium', 'sectioned', 'ciniki.customers.membertools.tags');
    this.tags.data = {};
    this.tags.fieldname = '';
    this.tags.sections = {
        'items':{'label':'Fields', 'fields':{}},
        'buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_customers_membertools.tags.save();'},
            }},
        };
    this.tags.fieldValue = function(s, i, d) {
        return this.data[i].name;
    }
    this.tags.open = function(cb, tag, tagname) {
        if( tag != null ) {
            this.tag = tag;
        }
        if( tagname != null ) {
            this.tagname = tagname;
            this.title = tagname;
            this.sections.items.label = tagname;
        }
        M.api.getJSONCb('ciniki.customers.tags', {'tnid':M.curTenantID, 'types':'40'}, function(rsp) {
            if( rsp['stat'] != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            var p = M.ciniki_customers_membertools.tags;
            p.data = {};
            p.sections.items.fields = {};
            if( rsp.tag_types != null && rsp.tag_types[0]['type']['tags'] != null ) {
                for(i in rsp.tag_types[0]['type']['tags']) {
                    var tag = rsp.tag_types[0]['type']['tags'][i]['tag'];
                    p.sections.items.fields[tag.permalink] = {
                        'label':tag['name'], 'type':'text',
                        };
                    p.data[tag.permalink] = tag;
                }
            }
            p.refresh();
            p.show(cb);
            });
    }
    this.tags.save = function() {
        var c = this.serializeForm('yes');
        M.api.postJSONCb('ciniki.customers.tagsUpdate', {'tnid':M.curTenantID, 'tag':this.tag}, c,
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_customers_membertools.tags.close();
            });
    };
    this.tags.addButton('save', 'Save', 'M.ciniki_customers_membertools.tags.save();');
    this.tags.addClose('Cancel');


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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_membertools', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        var slabel = 'Member';
        var plabel = 'Members';
/*      ** Deprecated ui-labels- 2020-07-14 **
        if( M.curTenant.customers != null ) {
            if( M.curTenant.customers.settings['ui-labels-member'] != null 
                && M.curTenant.customers.settings['ui-labels-member'] != ''
                ) {
                slabel = M.curTenant.customers.settings['ui-labels-member'];
            }
            if( M.curTenant.customers.settings['ui-labels-members'] != null 
                && M.curTenant.customers.settings['ui-labels-members'] != ''
                ) {
                plabel = M.curTenant.customers.settings['ui-labels-members'];
            }
        }
        */
        this.menu.title = slabel + ' Tools';
        this.menu.sections.tools.list.memberlist.label = 'Export ' + plabel + ' (Excel)';

        this.menu.open(cb);
    }

    this.downloadDirectory = function() {
        M.api.openFile('ciniki.customers.memberDownloadDirectory', {'tnid':M.curTenantID});
    };

    this.showPDFDirectory = function(cb) {
        this.pdf.reset();
        this.pdf.data = {'layout':'fullpage', 'toc':'no', 'title':'Member Directory', 'coverpage':'no'};
        this.pdf.formtab = 'fullpage';
        if( (M.curTenant.modules['ciniki.customers'].flags&0x04) > 0 ) {
            this.pdf.forms.fullpage.categories.active = 'yes';
            this.pdf.forms.halfpage.categories.active = 'yes';
            M.api.getJSONCb('ciniki.customers.memberCategories', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_membertools.pdf;
                p.forms.fullpage.categories.fields = {};
                p.forms.halfpage.categories.fields = {};
                if( rsp.categories != null ) {
                    for(var i in rsp.categories) {
                        p.forms.fullpage.categories.fields[rsp.categories[i].category.permalink] = {'label':rsp.categories[i].category.name, 'type':'toggle', 'id':rsp.categories[i].category.id, 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}};
                        p.forms.halfpage.categories.fields[rsp.categories[i].category.permalink] = {'label':rsp.categories[i].category.name, 'type':'toggle', 'id':rsp.categories[i].category.id, 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}};
                    }
                }
                p.refresh();
                p.show(cb);
            });
        } else {
            this.pdf.forms.fullpage.categories.active = 'no';
            this.pdf.forms.halfpage.categories.active = 'no';
            this.pdf.refresh();
            this.pdf.show(cb);      
        }
    };

    this.downloadPDFDirectory = function() {
        var args = {'tnid':M.curTenantID};
        args['coverpage'] = this.pdf.formValue('coverpage');
        args['title'] = this.pdf.formValue('title');
        if( args['title'] == '' ) {
            args['title'] = 'Member Directory';
        }
        args['toc'] = this.pdf.formValue('toc');
        args['layout'] = this.pdf.formtab;
        if( (M.curTenant.modules['ciniki.customers'].flags&0x04) > 0 ) {
            var categories = '';
            for(var i in this.pdf.sections.categories.fields) {
                if( this.pdf.formFieldValue(this.pdf.sections.categories.fields[i], i) == 'yes' ) {
                    categories += (categories!=''?',':'') + this.pdf.sections.categories.fields[i].id;
                }
            }
            if( categories != '' ) {
                args['categories'] = categories;
            }
        }
        M.api.openPDF('ciniki.customers.memberPDFDirectory', args);
    };

    this.showPDFContactInfo = function(cb) {
        this.contactinfo.reset();
        this.contactinfo.data = {'title':'Members Contact Information', 'private':'no'};
        this.contactinfo.refresh();
        this.contactinfo.show(cb);
    };

    this.downloadPDFContactInfo = function() {
        var args = {'tnid':M.curTenantID};
//      args['coverpage'] = this.contactinfo.formValue('coverpage');
        args['title'] = this.contactinfo.formValue('title');
        if( args['title'] == '' ) {
            args['title'] = 'Member Directory';
        }
        args['private'] = this.contactinfo.formValue('private');
//      args['toc'] = this.contactinfo.formValue('toc');
        args['layout'] = 'contactinfo';
        M.api.openPDF('ciniki.customers.memberPDFContactInfo', args);
    };

    this.downloadPDFSeasonList = function(sid, title) {
        var args = {'tnid':M.curTenantID, 'season_id':sid};
//      args['coverpage'] = this.contactinfo.formValue('coverpage');
        if( title != null ) {
            args['title'] = title;
        }
        M.api.openPDF('ciniki.customers.memberPDFSeasonList', args);
    };
}
