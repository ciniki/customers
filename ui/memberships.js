//
// The members app to manage members for an customers
//
function ciniki_customers_memberships() {
    this.webFlags = {'1':{'name':'Visible'}};
    //
    // Setup the main panel to list the members 
    //
    this.menu = new M.panel('Members',
        'ciniki_customers_memberships', 'menu',
        'mc', 'large mediumaside', 'sectioned', 'ciniki.customers.members.menu');
    this.menu.filterby = '';
    this.menu.membertype = '';
    this.menu.category = '';
    this.menu.data = {};
    this.menu.sections = {
        'membertypes':{'label':'Memberships', 'type':'simplegrid', 'num_cols':1, 'aside':'yes'},
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.modFlagSet('ciniki.customers', 0x04); },
            },
        'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':4, 
            'headerValues':['Name', 'Membership', 'Paid', 'Expires'],
            'cellClasses':['', '', '', ''],
            'hint':'name, company or email', 'noData':'No members found',
            },
        'members':{'label':'', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Name', 'Membership', 'Paid', 'Expires'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'date', 'date'],
            'cellClasses':['', '', '', ''],
            'noData':'No members',
            'addTxt':'Add',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.menu.open();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});',
            },
        };
//    this.menu.sectionData = function(s) { 
//        return this.data[s]; 
//    }
    this.menu.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 'start_needle':value, 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_customers_memberships.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_memberships.menu.panelUID + '_' + s), rsp.customers); 
                });
            return true;
        }
    };
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            switch(j) {
                case 0: return d.display_name;
                case 1: //if( d.membership_type == '20' ) {
                        return d.membership_type_text;
                    //} 
//                    return '<span class="maintext">' + d.membership_type_text + '</span>' 
//                        + (d.member_lastpaid!=''?'<span class="subtext">Paid: ' + d.member_lastpaid + '</span>':'');
                case 2: return d.member_lastpaid;
                case 3: return d.member_expires;
            }
        }
        return '';
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_customers_memberships.menu.open();\',\'mc\',{\'customer_id\':' + d.id + '});';
        //return 'M.ciniki_customers_memberships.showMember(\'M.ciniki_customers_memberships.menu.open();\',\'' + d.id + '\');'; 
    };
    this.menu.liveSearchSubmitFn = function(s, search_str) {
        M.startApp('ciniki.customers.main',null,'M.ciniki_customers_memberships.menu.open();','mc',{'type':'members', 'search':search_str});
    }
    this.menu.rowClass = function(s, i, d) {
        if( s == 'categories' ) {
            //console.log(s + '::' + unescape(this.category) + '::' + d.name);
        }
        if( s == 'membertypes' && this.filterby == 'type' && this.membertype == d.membership_type ) {
            return 'highlight';
        } else if( s == 'categories' && this.filterby == 'category' && unescape(this.category) == d.name ) {
            return 'highlight';
        }
        return '';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'membertypes' || s == 'categories' ) {
            return d.name + '<span class="count">' + d.num_members + '</span>';
        }
        if( s == 'members' && M.modFlagOn('ciniki.customers', 0x08) ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.member_lastpaid;
                case 2: return d.member_expires;
            }
        }
        if( s == 'members' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: //if( d.membership_type == '20' ) {
                    return d.membership_type_text;
                    //} 
                    //return '<span class="maintext">' + d.membership_type_text + '</span><span class="subtext">Paid: ' + d.member_lastpaid + '</span>';
                case 2: return d.member_lastpaid;
                case 3: return d.member_expires;
            }
        }
    };
    this.menu.rowFn = function(s, i, d) { 
        if( s == 'members' ) {
            return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_customers_memberships.menu.open();\',\'mc\',{\'customer_id\':' + d.id + '});';
            //return 'M.ciniki_customers_memberships.showMember(\'M.ciniki_customers_memberships.menu.open();\',\'' + d.id + '\');'; 
        } else if( s == 'membertypes' ) {
            return 'M.ciniki_customers_memberships.menu.showType(' + d.membership_type + ');';
        } else if( s == 'categories' ) {
            return 'M.ciniki_customers_memberships.menu.showCategory("' + escape(d.name) + '");';
        }
    }
    this.menu.showType = function(t) {
        this.filterby = 'type';
        this.membertype = t;
        this.open();
    }
    this.menu.showCategory = function(c) {
        this.filterby = 'category';
        this.category = c;
        this.open();
    }
    this.menu.open = function(cb) {
        var args = {'tnid':M.curTenantID};
        if( this.filterby == 'type' ) {
            args['type'] = this.membertype;
        } else if( this.filterby == 'category' ) {
            args['category'] = this.category;
        }
        M.api.getJSONCb('ciniki.customers.memberships', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_memberships.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        }); 
    };
    this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.menu.open();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});');
    this.menu.addButton('tools', 'Tools', 'M.startApp(\'ciniki.customers.membertools\',null,\'M.ciniki_customers_memberships.menu.open();\',\'mc\',{});');
    this.menu.addClose('Back');

    //
    // The member panel will show the information for a member/sponsor/organizer
    //
    this.member = new M.panel('Member',
        'ciniki_customers_memberships', 'member',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.members.member');
    this.member.data = {};
    this.member.customer_id = 0;
    this.member.sections = {
        '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
            }},
        'info':{'label':'', 'aside':'yes', 'list':{
            'eid':{'label':'Member #', 'visible':'no'},
            'name':{'label':'Name'},
            'company':{'label':'Company', 'visible':'no'},
            'phone_home':{'label':'Home Phone', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
            'phone_work':{'label':'Work Phone', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
            'phone_cell':{'label':'Cell Phone', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
            'phone_fax':{'label':'Fax', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
            'primary_email':{'label':'Email', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x20000000)>0?'yes':'no';}},
//              'alternate_email':{'label':'Alternate', 'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x20000000)==0?'yes':'no';}},
            'webvisible':{'label':'Web Settings'},
            }},
        '_subscriptions':{'label':'', 'aside':'yes', 'visible':'no', 'list':{
            'subscriptions':{'label':'Subscriptions'},
            }},
        'membership':{'label':'Status', 'aside':'yes', 'list':{
            'member_status_text':{'label':'Status'},
            'member_lastpaid':{'label':'Last Paid', 'visible':'no'},
            'member_expires':{'label':'Expires', 'visible':'no'},
            'type':{'label':'Type'},
            'member_categories':{'label':'Categories', 'visible':'no'},
            'start_date':{'label':'Start', 'visible':'no'},
            }},
        'seasons':{'label':'Seasons', 'visible':'no', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            },
        'phones':{'label':'Phones', 'type':'simplegrid', 'num_cols':2,
            'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x10000000)==0?'yes':'no';},
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No phones',
            'addTxt':'Add Phone',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'edit_phone_id\':\'0\',\'member\':\'yes\'});',
            },
        'emails':{'label':'Emails', 'type':'simplegrid', 'num_cols':1,
            'visible':function() {return (M.curTenant.modules['ciniki.customers'].flags&0x20000000)==0?'yes':'no';},
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No emails',
            'addTxt':'Add Email',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'edit_email_id\':\'0\',\'member\':\'yes\'});',
            },
        'addresses':{'label':'Addresses', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['label', ''],
            'noData':'No addresses',
            'addTxt':'Add Address',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'edit_address_id\':\'0\',\'member\':\'yes\'});',
            },
        'links':{'label':'Websites', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline', ''],
            'noData':'No websites',
            'addTxt':'Add Website',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'edit_link_id\':\'0\',\'member\':\'yes\'});',
            },
        'images':{'label':'Gallery', 'type':'simplethumbs'},
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Image',
            'addFn':'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'add\':\'yes\'});',
            },
        'short_bio':{'label':'Brief Bio', 'type':'htmlcontent'},
        'full_bio':{'label':'Full Bio', 'type':'htmlcontent'},
        'notes':{'label':'Notes', 'type':'htmlcontent'},
        '_buttons':{'label':'', 'buttons':{
            'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'member\':\'yes\'});'},
            }},
    };
    this.member.sectionData = function(s) {
        if( s == 'info' || s == 'membership' || s == '_subscriptions' ) { return this.sections[s].list; }
        if( s == 'short_bio' || s == 'full_bio' || s == 'notes' ) { return this.data[s].replace(/\n/g, '<br/>'); }
        return this.data[s];
        };
    this.member.listLabel = function(s, i, d) {
        if( s == 'info' || s == 'membership' || s == '_subscriptions' ) { 
            return d.label; 
        }
        return null;
    };
    this.member.listValue = function(s, i, d) {
        if( s == 'membership' && i == 'type' ) {
            var txt = '';
            if( this.data.membership_type != null && this.data.membership_type != '' ) {
                switch(this.data.membership_type) {
                    case '10': txt += 'Regular'; break;
                    case '20': txt += 'Student'; break;
                    case '30': txt += 'Individual'; break;
                    case '40': txt += 'Family'; break;
                    case '110': txt += 'Complimentary'; break;
                    case '150': txt += 'Reciprocal'; break;
                    case '200': txt += 'Products'; break;
                }
            }
            if( this.data.membership_length != null && this.data.membership_length != '' ) {
                switch(this.data.membership_length) {
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
        if( s == '_subscriptions' && i == 'subscriptions' ) {
            if( this.data.subscriptions == null ) { return 'None'; }
            var subs = '';
            var k = 0;
            for(k in this.data.subscriptions) {
                subs += (subs!=''?', ':'') + this.data.subscriptions[k].subscription.name;
            }
            if( subs == '' ) { return 'None'; }
            return subs;
        }
        return this.data[i];
    };
    this.member.fieldValue = function(s, i, d) {
        if( i == 'description' || i == 'notes' ) { 
            return this.data[i].replace(/\n/g, '<br/>');
        }
        return this.data[i];
    };
    this.member.cellValue = function(s, i, j, d) {
        if( s == 'seasons' ) {
            switch(j) {
                case 0: return d.season.name;
                case 1: return d.season.status_text + ((d.season.date_paid!=null&&d.season.date_paid!='0000-00-00'&&d.season.date_paid!='')?' <span class="subdue">(' + d.season.date_paid + ')</span>':'');
//                  case 1: return d.season.phone_number + ((d.phone.flags&0x08)>0?' <span class="subdue">(Public)</span>':'');
            }
        }
        if( s == 'phones' ) {
            switch(j) {
                case 0: return d.phone.phone_label;
                case 1: return d.phone.phone_number + ((d.phone.flags&0x08)>0?' <span class="subdue">(Public)</span>':'');
            }
        }
        if( s == 'emails' ) {
            var flags = '';
            if( (d.email.flags&0x08) > 0 ) { flags += (flags!=''?', ':'') + 'Public'; }
            if( (d.email.flags&0x10) > 0 ) { flags += (flags!=''?', ':'') + 'No Emails'; }
            return M.linkEmail(d.email.address) + (flags!=''?' <span class="subdue">(' + flags + ')</span>':'');
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
    this.member.rowFn = function(s, i, d) {
        if( s == 'phones' ) {
            return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'edit_phone_id\':\'' + d.phone.id + '\',\'member\':\'yes\'});';
        }
        if( s == 'emails' ) {
            return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'edit_email_id\':\'' + d.email.id + '\',\'member\':\'yes\'});';
        }
        if( s == 'addresses' ) {
            return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'edit_address_id\':\'' + d.address.id + '\',\'member\':\'yes\'});';
        }
        if( s == 'links' ) {
            return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'edit_link_id\':\'' + d.link.id + '\',\'member\':\'yes\'});';
        }
    };
    this.member.thumbFn = function(s, i, d) {
        return 'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_image_id\':\'' + d.image.id + '\'});';
    };
    this.member.addDropImage = function(iid) {
        var rsp = M.api.getJSON('ciniki.customers.imageAdd',
            {'tnid':M.curTenantID, 'image_id':iid, 'webflags':'1',
                'customer_id':M.ciniki_customers_memberships.member.customer_id});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    };
    this.member.addDropImageRefresh = function() {
        if( M.ciniki_customers_memberships.member.customer_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.customers.get', {'tnid':M.curTenantID, 
                'customer_id':M.ciniki_customers_memberships.member.customer_id, 'images':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_memberships.member.data.images = rsp.customer.images;
                    M.ciniki_customers_memberships.member.refreshSection('images');
                });
        }
    };
    this.member.addButton('edit', 'Edit', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_memberships.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_memberships.member.customer_id,\'member\':\'yes\'});');
    this.member.addClose('Back');
   

    //
    // Start the app
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_memberships', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        // Setup ui labels
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
        } */
        this.menu.title = plabel;
        this.member.title = slabel;
        this.menu.sections.members.addTxt = 'Add ' + slabel;

        // Decide what's visible
        if( (M.curTenant.modules['ciniki.customers'].flags&0x08) > 0 ) {
            this.member.sections.membership.list.member_lastpaid.visible = 'yes';
            this.member.sections.membership.list.member_expires.visible = 'yes';
            this.member.sections.membership.list.type.visible = 'yes';
            this.menu.sections.members.num_cols = 3;
            this.menu.sections.members.headerValues = ['Name', 'Paid', 'Expires'];
            this.menu.sections.members.sortTypes = ['text', 'date', 'date'];
        } else {
            this.member.sections.membership.list.member_lastpaid.visible = 'no';
            this.member.sections.membership.list.member_expires.visible = 'no';
            this.member.sections.membership.list.type.visible = 'no';
            this.menu.sections.members.num_cols = 4;
            this.menu.sections.members.headerValues = ['Name', 'Membership', 'Paid', 'Expires'];
            this.menu.sections.members.sortTypes = ['text', 'text', 'date', 'date'];
        }

        if( (M.curTenant.modules['ciniki.customers'].flags&0x04000000) > 0 ) {
            this.member.sections.membership.list.start_date.visible = 'yes';
        } else {
            this.member.sections.membership.list.start_date.visible = 'no';
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x010000) > 0 ) {
            this.member.sections.info.list.eid.visible = 'yes';
        } else {
            this.member.sections.info.list.eid.visible = 'no';
        }

        //
        // Check if subscriptions module enabled
        //
        if( M.curTenant.modules['ciniki.subscriptions'] != null ) {
            this.member.sections._subscriptions.visible = 'yes';
        } else {
            this.member.sections._subscriptions.visible = 'no';
        }

        if( args.customer_id != null && args.customer_id > 0 ) {
            this.showMember(cb, args.customer_id);
        } else {
            this.menu.open(cb);
        }
    }


    this.showMember = function(cb, cid) {
        if( cid != null ) { this.member.customer_id = cid; }
        var rsp = M.api.getJSONCb('ciniki.customers.get',
            {'tnid':M.curTenantID, 'customer_id':this.member.customer_id, 
                'member_categories':'yes', 'phones':'yes', 'emails':'yes', 'addresses':'yes', 
                'links':'yes', 'images':'yes', 'seasons':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_memberships.member;
                p.data = rsp.customer;
                if( (rsp.customer.webflags&0x01) == 1 ) {
                    p.data.webvisible = 'Visible';
                } else {
                    p.data.webvisible = 'Hidden';
                }
                
                if( (M.curTenant.modules['ciniki.customers'].flags&0x04) > 0 ) {
                    p.sections.membership.list.member_categories.visible = 'yes';
                    if( rsp.customer.member_categories != null && rsp.customer.member_categories != '' ) {
                        p.data.member_categories = rsp.customer.member_categories.replace(/::/g, ', ');
                    }
                } else {
                    p.sections.membership.list.member_categories.visible = 'no';
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
