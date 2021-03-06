//
// The distributors app to manage distributors for an customers
//
function ciniki_customers_distributors() {
    this.webFlags = {'3':{'name':'Visible'}};
    this.init = function() {
        //
        // Setup the main panel to list the distributors 
        //
        this.menu = new M.panel('Distributors',
            'ciniki_customers_distributors', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.customers.distributors.menu');
        this.menu.data = {};
        this.menu.sections = {
            'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 
                'hint':'name, company or email', 'noData':'No distributors found',
                },
            'distributors':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline', 'multiline'],
                'noData':'No distributors',
                'addTxt':'Add Distributor',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showMenu();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});',
                },
            'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1},
            };
        this.menu.sectionData = function(s) { return this.data[s]; }
        this.menu.liveSearchCb = function(s, i, value) {
            if( s == 'search' && value != '' ) {
                M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 'start_needle':value, 'limit':'10', 'distributors':'yes'}, 
                    function(rsp) { 
                        M.ciniki_customers_distributors.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_distributors.menu.panelUID + '_' + s), rsp.customers); 
                    });
                return true;
            }
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            if( s == 'search' ) { 
                return d.display_name;
            }
            return '';
        }
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
            return 'M.ciniki_customers_distributors.showDistributor(\'M.ciniki_customers_distributors.showMenu();\',\'' + d.id + '\');'; 
        };
        this.menu.liveSearchSubmitFn = function(s, search_str) {
            M.startApp('ciniki.customers.main',null,'M.ciniki_tenants_main.showMenu();','mc',{'search': search_str,'type':'distributors'});
        };
        this.menu.cellValue = function(s, i, j, d) {
            if( s == 'distributors' && j == 0 ) {
                if( d.distributor.company != null && d.distributor.company != '' ) {
                    return '<span class="maintext">' + d.distributor.first + ' ' + d.distributor.last + '</span><span class="subtext">' + d.distributor.company + '</span>';
                } 
                return '<span class="maintext">' + d.distributor.display_name + '</span>';
            }
            else if( s == 'categories' && j == 0 ) {
                return d.category.name + '<span class="count">' + d.category.num_distributors + '</span>';
            }
        };
        this.menu.rowFn = function(s, i, d) { 
            if( s == 'distributors' ) {
                return 'M.ciniki_customers_distributors.showDistributor(\'M.ciniki_customers_distributors.showMenu();\',\'' + d.distributor.id + '\');'; 
            } else if( s == 'categories' ) {
                return 'M.ciniki_customers_distributors.showList(\'M.ciniki_customers_distributors.showMenu();\',\'' + escape(d.category.name) + '\',\'' + d.category.permalink + '\');'; 
            }
        };
        this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showMenu();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});');
        this.menu.addButton('tools', 'Tools', 'M.startApp(\'ciniki.customers.distributortools\',null,\'M.ciniki_customers_distributors.showMenu();\',\'mc\',{});');
        this.menu.addClose('Back');

        //
        // Setup the main panel to list the distributors from a category
        //
        this.list = new M.panel('Distributors',
            'ciniki_customers_distributors', 'list',
            'mc', 'medium', 'sectioned', 'ciniki.customers.distributors.list');
        this.list.data = {};
        this.list.sections = {
            'distributors':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline', 'multiline'],
                'noData':'No distributors',
                'addTxt':'Add Distributor',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showList();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});',
                },
            };
        this.list.sectionData = function(s) { return this.data[s]; }
        this.list.cellValue = function(s, i, j, d) {
            if( j == 0 ) {
                if( d.distributor.company != null && d.distributor.company != '' ) {
                    return '<span class="maintext">' + d.distributor.first + ' ' + d.distributor.last + '</span><span class="subtext">' + d.distributor.company + '</span>';
                } 
                return '<span class="maintext">' + d.distributor.display_name + '</span>';
            }
        };
        this.list.rowFn = function(s, i, d) { 
            return 'M.ciniki_customers_distributors.showDistributor(\'M.ciniki_customers_distributors.showList();\',\'' + d.distributor.id + '\');'; 
        };
        this.list.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showList();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});');
        this.list.addClose('Back');

        //
        // The distributor panel will show the information for a distributor/sponsor/organizer
        //
        this.distributor = new M.panel('Distributor',
            'ciniki_customers_distributors', 'distributor',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.distributors.distributor');
        this.distributor.data = {};
        this.distributor.customer_id = 0;
        this.distributor.sections = {
            '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
                }},
            'info':{'label':'', 'list':{
                'name':{'label':'Name'},
                'company':{'label':'Company', 'visible':'no'},
                'phone_home':{'label':'Home Phone', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
                'phone_work':{'label':'Work Phone', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
                'phone_cell':{'label':'Cell Phone', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
                'phone_fax':{'label':'Fax', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
                'primary_email':{'label':'Email', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x20000000)>0?'yes':'no';}},
//              'alternate_email':{'label':'Alternate', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x20000000)>0?'yes':'no';}},
                'webvisible':{'label':'Web Settings'},
                'distributor_status_text':{'label':'Status'},
                'distributor_categories':{'label':'Categories', 'visible':'no'},
                }},
            'account':{'label':'', 'aside':'yes', 'visible':'yes', 'type':'simplegrid', 'num_cols':2,
                'headerValues':null,
                'cellClasses':['label', ''],
                'dataMaps':['name', 'value'],
                },
            'phones':{'label':'Phones', 'type':'simplegrid', 'num_cols':2,
                'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';},
                'headerValues':null,
                'cellClasses':['label', ''],
                'noData':'No phones',
                'addTxt':'Add Phone',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_phone_id\':\'0\',\'distributor\':\'yes\'});',
                },
            'emails':{'label':'Emails', 'type':'simplegrid', 'num_cols':1,
                'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x20000000)>0?'yes':'no';},
                'headerValues':null,
                'cellClasses':['', ''],
                'noData':'No emails',
                'addTxt':'Add Email',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_email_id\':\'0\',\'distributor\':\'yes\'});',
                },
            'addresses':{'label':'Addresses', 'type':'simplegrid', 'num_cols':2,
                'headerValues':null,
                'cellClasses':['label', ''],
                'noData':'No addresses',
                'addTxt':'Add Address',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_address_id\':\'0\',\'distributor\':\'yes\'});',
                },
            'links':{'label':'Websites', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline', ''],
                'noData':'No websites',
                'addTxt':'Add Website',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_link_id\':\'0\',\'distributor\':\'yes\'});',
                },
            'images':{'label':'Gallery', 'type':'simplethumbs'},
            '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'addTxt':'Add Image',
                'addFn':'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'add\':\'yes\'});',
                },
            'short_bio':{'label':'Brief Bio', 'type':'htmlcontent'},
            'full_bio':{'label':'Full Bio', 'type':'htmlcontent'},
            'notes':{'label':'Notes', 'type':'htmlcontent'},
            '_buttons':{'label':'', 'buttons':{
                'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'distributor\':\'yes\'});'},
                }},
        };
        this.distributor.sectionData = function(s) {
            if( s == 'info' || s == 'distributorship' ) { return this.sections[s].list; }
            if( s == 'short_bio' || s == 'full_bio' || s == 'notes' ) { return this.data[s].replace(/\n/g, '<br/>'); }
            return this.data[s];
            };
        this.distributor.listLabel = function(s, i, d) {
            if( s == 'info' || s == 'distributorship' ) { 
                return d.label; 
            }
            return null;
        };
        this.distributor.listValue = function(s, i, d) {
            if( s == 'distributorship' && i == 'type' ) {
                var txt = '';
                if( this.data.distributorship_type != null && this.data.distributorship_type != '' ) {
                    switch(this.data.distributorship_type) {
                        case '10': txt += 'Regular'; break;
                        case '20': txt += 'Complimentary'; break;
                        case '30': txt += 'Reciprocal'; break;
                    }
                }
                if( this.data.distributorship_length != null && this.data.distributorship_length != '' ) {
                    switch(this.data.distributorship_length) {
                        case '10': txt += (txt!=''?', ':'') + 'Monthly'; break;
                        case '20': txt += (txt!=''?', ':'') + 'Yearly'; break;
                        case '60': txt += (txt!=''?', ':'') + 'Lifetime'; break;
                    }
                }
                return txt;
            }
            if( i == 'name' ) {
                return this.data.first + ' ' + this.data.last;
            }
            return this.data[i];
        };
        this.distributor.fieldValue = function(s, i, d) {
            if( i == 'description' || i == 'notes' ) { 
                return this.data[i].replace(/\n/g, '<br/>');
            }
            return this.data[i];
        };
        this.distributor.cellValue = function(s, i, j, d) {
            if( s == 'account' ) {
                if( j == 0 ) { return d.label; }
                if( j == 1 ) { return d.value; }
            }
            if( s == 'phones' ) {
                switch(j) {
                    case 0: return d.phone.phone_label;
                    case 1: return d.phone.phone_number + ((d.phone.flags&0x08)>0?' <span class="subdue">(Public)</span>':'');
                }
            }
            if( s == 'emails' ) {
                return M.linkEmail(d.email.address) + ((d.email.flags&0x08)>0?' <span class="subdue">(Public)</span>':'');
            }
            if( s == 'addresses' ) {
                if( j == 0 ) { 
                    var l = '';
                    var cm = '';
                    if( (d.address.flags&0x01) ) { l += cm + 'shipping'; cm =',<br/>';}
                    if( (d.address.flags&0x02) ) { l += cm + 'billing'; cm =',<br/>';}
                    if( (d.address.flags&0x04) ) { l += cm + 'mailing'; cm =',<br/>';}
                    if( (d.address.flags&0x08) ) { l += cm + 'public'; cm =',<br/>';}
                    return l;
                } 
                if( j == 1 ) {
                    var v = '';
                    if( d.address.address1 != '' ) { v += d.address.address1 + '<br/>'; }
                    if( d.address.address2 != '' ) { v += d.address.address2 + '<br/>'; }
                    if( d.address.city != '' ) { v += d.address.city + ''; }
                    if( d.address.province != '' ) { v += ', ' + d.address.province + '<br/>'; }
                    if( d.address.postal != '' ) { v += d.address.postal + '<br/>'; }
                    if( d.address.country != '' ) { v += d.address.country + '<br/>'; }
                    if( d.address.phone != '' ) { v += 'Phone: ' + d.address.phone + '<br/>'; }
                    return v;
                }
            }
            if( s == 'links' ) {
                if( d.link.name != '' ) {
                    return '<span class="maintext">' + d.link.name + ((d.link.webflags&0x01)>0?' <span class="subdue">(Public)</span>':'') + '</span><span class="subtext">' + M.hyperlink(d.link.url) + '</span>';
                } else {
                    return M.hyperlink(d.link.url) + ((d.link.webflags&0x01)>0?' <span class="subdue">(Public)</span>':'');
                }
            }
            if( s == 'images' && j == 0 ) { 
                if( d.image.image_id > 0 ) {
                    if( d.image.image_data != null && d.image.image_data != '' ) {
                        return '<img width="75px" height="75px" src=\'' + d.image.image_data + '\' />'; 
                    } else {
                        return '<img width="75px" height="75px" src=\'' + M.api.getBinaryURL('ciniki.customers.getImage', {'tnid':M.curTenantID, 'image_id':d.image.image_id, 'version':'thumbnail', 'maxwidth':'75'}) + '\' />'; 
                    }
                } else {
                    return '<img width="75px" height="75px" src=\'/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg\' />';
                }
            }
        };
        this.distributor.rowFn = function(s, i, d) {
            if( s == 'phones' ) {
                return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_phone_id\':\'' + d.phone.id + '\',\'distributor\':\'yes\'});';
            }
            if( s == 'emails' ) {
                return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_email_id\':\'' + d.email.id + '\',\'distributor\':\'yes\'});';
            }
            if( s == 'addresses' ) {
                return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_address_id\':\'' + d.address.id + '\',\'distributor\':\'yes\'});';
            }
            if( s == 'links' ) {
                return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_link_id\':\'' + d.link.id + '\',\'distributor\':\'yes\'});';
            }
        };
        this.distributor.thumbFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_image_id\':\'' + d.image.id + '\'});';
        };
        this.distributor.addDropImage = function(iid) {
            var rsp = M.api.getJSON('ciniki.customers.imageAdd',
                {'tnid':M.curTenantID, 'image_id':iid, 'webflags':'1',
                    'customer_id':M.ciniki_customers_distributors.distributor.customer_id});
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            return true;
        };
        this.distributor.addDropImageRefresh = function() {
            if( M.ciniki_customers_distributors.distributor.customer_id > 0 ) {
                var rsp = M.api.getJSONCb('ciniki.customers.get', {'tnid':M.curTenantID, 
                    'customer_id':M.ciniki_customers_distributors.distributor.customer_id, 'images':'yes'}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_customers_distributors.distributor.data.images = rsp.customer.images;
                        M.ciniki_customers_distributors.distributor.refreshSection('images');
                    });
            }
        };
        this.distributor.addButton('edit', 'Edit', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'distributor\':\'yes\'});');
        this.distributor.addClose('Back');
    }
    
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_distributors', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }
    
        // Setup ui labels
        var slabel = 'Distributor';
        var plabel = 'Distributors';
/*      ** Deprecated ui-labels- 2020-07-14 **
        if( M.curTenant.customers != null ) {
            if( M.curTenant.customers.settings['ui-labels-distributor'] != null 
                && M.curTenant.customers.settings['ui-labels-distributor'] != ''
                ) {
                slabel = M.curTenant.customers.settings['ui-labels-distributor'];
            }
            if( M.curTenant.customers.settings['ui-labels-distributors'] != null 
                && M.curTenant.customers.settings['ui-labels-distributors'] != ''
                ) {
                plabel = M.curTenant.customers.settings['ui-labels-distributors'];
            }
        } */
        this.menu.title = plabel;
        this.list.title = plabel;
        this.distributor.title = slabel;
        this.menu.sections.distributors.addTxt = 'Add ' + slabel;
        this.list.sections.distributors.addTxt = 'Add ' + slabel;

        if( args.customer_id != null && args.customer_id > 0 ) {
            this.showDistributor(cb, args.customer_id);
        } else {
            this.showMenu(cb);
        }
    }

    this.showMenu = function(cb) {
        if( (M.curTenant.modules['ciniki.customers'].flags&0x20) > 0 ) {
            this.menu.sections.distributors.visible = 'no';
            this.menu.sections.categories.visible = 'yes';
            M.api.getJSONCb('ciniki.customers.distributorCategories', 
                {'tnid':M.curTenantID}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_distributors.menu;
                    p.data = {'categories':rsp.categories};
                    p.refresh();
                    p.show(cb);
                }); 
        } else {
            // Get the list of existing customers
            this.menu.sections.distributors.visible = 'yes';
            this.menu.sections.categories.visible = 'no';
            M.api.getJSONCb('ciniki.customers.distributorList', 
                {'tnid':M.curTenantID}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_distributors.menu;
                    p.data = {'distributors':rsp.distributors};
                    if( rsp.distributors != null && rsp.distributors.length > 20 ) {
                        p.sections.search.visible = 'yes';
                    } else {
                        p.sections.search.visible = 'no';
                    }
                    p.refresh();
                    p.show(cb);
                }); 
        }
    };

    this.showList = function(cb, c, p) {
        if( c != null ) { this.list.category = unescape(c); }
        if( p != null ) { this.list.permalink = p; }
        // Get the list of existing customers
        this.list.sections.distributors.label = this.list.category;
        M.api.getJSONCb('ciniki.customers.distributorList', 
            {'tnid':M.curTenantID, 'category':encodeURIComponent(this.list.permalink)}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_distributors.list;
                p.data = {'distributors':rsp.distributors};
                p.refresh();
                p.show(cb);
            }); 
    };

    this.showDistributor = function(cb, cid) {
        if( cid != null ) { this.distributor.customer_id = cid; }
        var rsp = M.api.getJSONCb('ciniki.customers.get',
            {'tnid':M.curTenantID, 'customer_id':this.distributor.customer_id, 
                'distributor_categories':'yes', 'phones':'yes', 'emails':'yes', 'addresses':'yes', 
                'links':'yes', 'images':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_distributors.distributor;
                p.data = rsp.customer;
                if( (rsp.customer.webflags&0x04) > 0 ) {
                    p.data.webvisible = 'Visible';
                } else {
                    p.data.webvisible = 'Hidden';
                }
                
                if( (M.curTenant.modules['ciniki.customers'].flags&0x20) > 0 ) {
                    p.sections.info.list.distributor_categories.visible = 'yes';
                    if( rsp.customer.distributor_categories != null && rsp.customer.distributor_categories != '' ) {
                        p.data.distributor_categories = rsp.customer.distributor_categories.replace(/::/g, ', ');
                    }
                } else {
                    p.sections.info.list.distributor_categories.visible = 'no';
                }

                p.data.account = {};
                // Tax Number
                if( (M.curTenant.modules['ciniki.customers'].flags&0x20000) > 0 
                    && rsp.customer.tax_number != null && rsp.customer.tax_number != ''
                    ) {
                    p.sections.account.visible = 'yes';
                    p.data.account.tax_number = {'label':'Tax Number', 'value':rsp.customer.tax_number};
                }
                // Tax Location
                if( (M.curTenant.modules['ciniki.customers'].flags&0x40000) > 0 ) {
                    var rates = ((rsp.customer.tax_location_id_rates!=null&&rsp.customer.tax_location_id_rates!='')?' <span class="subdue">'+rsp.customer.tax_location_id_rates+'</span>':'');
                    p.sections.account.visible = 'yes';
                    p.data.account.tax_location_id = {'label':'Taxes', 'value':(rsp.customer.tax_location_id_text!=null?rsp.customer.tax_location_id_text:'Use Shipping Address') + rates};
                }
                p.sections.notes.visible=(rsp.customer.notes!=null&&rsp.customer.notes!='')?'yes':'no';
                p.sections.full_bio.visible=(rsp.customer.full_bio!=null&&rsp.customer.full_bio!='')?'yes':'no';
                p.sections.short_bio.visible=(rsp.customer.short_bio!=null&&rsp.customer.short_bio!='')?'yes':'no';

                var fields = ['company'];
                for(i in fields) {
                    if( rsp.customer[fields[i]] != null && rsp.customer[fields[i]] != '' ) {
                        p.sections.info.list[fields[i]].visible = 'yes';
                    } else {
                        p.sections.info.list[fields[i]].visible = 'no';
                    }
                }
                p.refresh();
                p.show(cb);
            });
    };
}
