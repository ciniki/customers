//
// The members app to manage members for an customers
//
function ciniki_customers_members() {
    this.webFlags = {'1':{'name':'Visible'}};
    this.init = function() {
        //
        // Setup the main panel to list the members 
        //
        this.menu = new M.panel('Members',
            'ciniki_customers_members', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.customers.members.menu');
        this.menu.data = {};
        this.menu.sections = {
            'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 
                'cellClasses':['multiline','multiline'],
                'hint':'name, company or email', 'noData':'No members found',
                },
            'seasons':{'label':'Seasons', 'visible':'no', 'list':{}},
            'members':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline', 'multiline'],
                'noData':'No members',
                'addTxt':'Add',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMenu();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});',
                },
            'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1},
            };
        this.menu.sectionData = function(s) { 
            if( s == 'seasons' ) { return this.sections[s].list; }
            return this.data[s]; 
        }
        this.menu.liveSearchCb = function(s, i, value) {
            if( s == 'search' && value != '' ) {
                M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
                    function(rsp) { 
                        M.ciniki_customers_members.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_members.menu.panelUID + '_' + s), rsp.customers); 
                    });
                return true;
            }
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            if( s == 'search' ) { 
                switch(j) {
                    case 0: return d.customer.display_name;
                    case 1: if( d.customer.membership_type == '20' ) {
                            return d.customer.membership_type_text;
                        } 
                        return '<span class="maintext">' + d.customer.membership_type_text + '</span>' 
                            + (d.customer.member_lastpaid!=''?'<span class="subtext">Paid: ' + d.customer.member_lastpaid + '</span>':'');
                }
            }
            return '';
        }
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
            return 'M.ciniki_customers_members.showMember(\'M.ciniki_customers_members.showMenu();\',\'' + d.customer.id + '\');'; 
        };
        this.menu.liveSearchSubmitFn = function(s, search_str) {
            M.startApp('ciniki.customers.main',null,'M.ciniki_customers_members.showMenu();','mc',{'type':'members', 'search':search_str});
        };
        this.menu.listValue = function(s, i, d) {
            return d.label;
        }
        this.menu.listFn = function(s, i, d) {
            return d.fn;
        }
        this.menu.cellValue = function(s, i, j, d) {
            if( s == 'members' && j == 0 ) {
                switch(j) {
                    case 0: return d.member.display_name;
                    case 1: if( d.member.membership_type == '20' ) {
                            return d.member.membership_type_text;
                        } 
                        return '<span class="maintext">' + d.member.membership_type_text + '</span><span class="subtext">Paid: ' + d.member.member_lastpaid + '</span>';
                }
            }
            else if( s == 'categories' && j == 0 ) {
                return d.category.name + '<span class="count">' + d.category.num_members + '</span>';
            }
        };
        this.menu.rowFn = function(s, i, d) { 
            if( s == 'members' ) {
                return 'M.ciniki_customers_members.showMember(\'M.ciniki_customers_members.showMenu();\',\'' + d.member.id + '\');'; 
            } else if( s == 'categories' ) {
                return 'M.ciniki_customers_members.showList(\'M.ciniki_customers_members.showMenu();\',\'' + escape(d.category.name) + '\',\'' + d.category.permalink + '\');'; 
            }
        };
        this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMenu();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});');
        this.menu.addButton('tools', 'Tools', 'M.startApp(\'ciniki.customers.membertools\',null,\'M.ciniki_customers_members.showMenu();\',\'mc\',{});');
        this.menu.addClose('Back');

        //
        // Setup the main panel to list the members from a category
        //
        this.list = new M.panel('Members',
            'ciniki_customers_members', 'list',
            'mc', 'medium', 'sectioned', 'ciniki.customers.members.list');
        this.list.data = {};
        this.list.sections = {
            'members':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline', 'multiline'],
                'noData':'No members',
                'addTxt':'Add',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showList();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\',\'category\':M.ciniki_customers_members.list.category});',
                },
            };
        this.list.sectionData = function(s) { return this.data[s]; }
        this.list.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.member.display_name;
                case 1: 
                    var subtxt = '';
                    if( (M.curBusiness.modules['ciniki.customers'].flags&0x02000000) > 0 ) {
                        if( d.member.season_name != null && d.member.season_name != '' ) {
                            subtxt = '<span class="subtext">' + d.member.season_name + (d.member.season_status_text!=null&&d.member.season_status_text!=''?' - ' + d.member.season_status_text:'') + (d.member.season_date_paid!=null&&d.member.season_date_paid!=''?' (' + d.member.season_date_paid + ')':'');
                        }
                    } else {
                        subtxt = '<span class="subtext">Paid: ' + d.member.member_lastpaid + '</span>';
                    }

                    return '<span class="maintext">' + d.member.membership_type_text + '</span>' + subtxt;
            }
        };
        this.list.rowFn = function(s, i, d) { 
            return 'M.ciniki_customers_members.showMember(\'M.ciniki_customers_members.showList();\',\'' + d.member.id + '\');'; 
        };
        this.list.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showList();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\',\'category\':M.ciniki_customers_members.list.category});');
        this.list.addClose('Back');

        //
        // Display the information and stats for a season
        //
        this.season = new M.panel('Season',
            'ciniki_customers_members', 'season',
            'mc', 'medium mediumflex', 'sectioned', 'ciniki.customers.members.season');
        this.season.data = {};
        this.season.season_id = 0;
        this.season.sections = {
            'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':2, 
                'cellClasses':['multiline','multiline'],
                'hint':'name, company or email', 'noData':'No members found',
                },
            'tabs':{'label':'', 'selected':'unattached', 'type':'paneltabs', 'tabs':{
                'unattached':{'label':'Unknown', 'visible':'yes', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'unattached\');'},
                'inactive':{'label':'Inactive', 'visible':'yes', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'inactive\');'},
                'regular':{'label':'Paid', 'visible':'no', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'regular\');'},
                'student':{'label':'Paid', 'visible':'no', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'student\');'},
                'student':{'label':'Paid', 'visible':'yes', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'student\');'},
                'individual':{'label':'Paid', 'visible':'yes', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'individual\');'},
                'family':{'label':'Paid', 'visible':'yes', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'family\');'},
                'complimentary':{'label':'Complementary', 'visible':'yes', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'complimentary\');'},
                'reciprocal':{'label':'Reciprocal', 'visible':'yes', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'reciprocal\');'},
                'lifetime':{'label':'Lifetime', 'visible':'yes', 'fn':'M.ciniki_customers_members.showSeason(null, null, \'lifetime\');'},
                }},
            'members':{'label':'', 'type':'simplegrid', 'num_cols':4,
                'headerValues':['Member', 'Type', 'Status', 'Date Paid'],
                'sortable':'yes',
                'sortTypes':['text', 'text', 'text', 'date'],
                'cellClasses':['', '', ''],
                'noData':'No members',
                },
            '_buttons':{'label':'', 'buttons':{
                'download':{'label':'Download Excel', 'fn':'M.startApp(\'ciniki.customers.download\',null,\'M.ciniki_customers_members.showSeason();\',\'mc\',{\'membersonly\':\'yes\', \'selected_season\':M.ciniki_customers_members.season.season_id});'},
                }},
            };
        this.season.sectionData = function(s) { return this.data[s]; }
        this.season.liveSearchCb = function(s, i, value) {
            if( s == 'search' && value != '' ) {
                M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
                    function(rsp) { 
                        M.ciniki_customers_members.season.liveSearchShow('search', null, M.gE(M.ciniki_customers_members.season.panelUID + '_' + s), rsp.customers); 
                    });
                return true;
            }
        };
        this.season.liveSearchResultValue = function(s, f, i, j, d) {
            if( s == 'search' ) { 
                switch(j) {
                    case 0: return d.customer.display_name;
                    case 1: return d.customer.membership_type_text;
                }
            }
            return '';
        }
        this.season.liveSearchResultRowFn = function(s, f, i, j, d) { 
            return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showSeason();\',\'mc\',{\'customer_id\':\'' + d.customer.id + '\',\'member\':\'yes\'});';
        };
        this.season.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.member.display_name;
                case 1: return d.member.membership_type_text;
                case 2: return d.member.member_season_status_text;
                case 3: return d.member.date_paid;
            }
        };
        this.season.rowFn = function(s, i, d) { 
//          return 'M.ciniki_customers_members.showMember(\'M.ciniki_customers_members.showSeason();\',\'' + d.member.id + '\');'; 
            return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showSeason();\',\'mc\',{\'customer_id\':\'' + d.member.id + '\',\'member\':\'yes\'});';
        };
        this.season.addClose('Back');

        //
        // The member panel will show the information for a member/sponsor/organizer
        //
        this.member = new M.panel('Member',
            'ciniki_customers_members', 'member',
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
                'phone_home':{'label':'Home Phone', 'visible':function() {return (M.curBusiness.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
                'phone_work':{'label':'Work Phone', 'visible':function() {return (M.curBusiness.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
                'phone_cell':{'label':'Cell Phone', 'visible':function() {return (M.curBusiness.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
                'phone_fax':{'label':'Fax', 'visible':function() {return (M.curBusiness.modules['ciniki.customers'].flags&0x10000000)>0?'yes':'no';}},
                'primary_email':{'label':'Email', 'visible':function() {return (M.curBusiness.modules['ciniki.customers'].flags&0x20000000)>0?'yes':'no';}},
//              'alternate_email':{'label':'Alternate', 'visible':function() {return (M.curBusiness.modules['ciniki.customers'].flags&0x20000000)==0?'yes':'no';}},
                'webvisible':{'label':'Web Settings'},
                }},
            '_subscriptions':{'label':'', 'aside':'yes', 'visible':'no', 'list':{
                'subscriptions':{'label':'Subscriptions'},
                }},
            'membership':{'label':'Status', 'aside':'yes', 'list':{
                'member_status_text':{'label':'Status'},
                'member_lastpaid':{'label':'Last Paid', 'visible':'no'},
                'type':{'label':'Type'},
                'member_categories':{'label':'Categories', 'visible':'no'},
                'start_date':{'label':'Start', 'visible':'no'},
                }},
            'seasons':{'label':'Seasons', 'visible':'no', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
                'cellClasses':['label', ''],
                },
            'phones':{'label':'Phones', 'type':'simplegrid', 'num_cols':2,
                'visible':function() {return (M.curBusiness.modules['ciniki.customers'].flags&0x10000000)==0?'yes':'no';},
                'headerValues':null,
                'cellClasses':['label', ''],
                'noData':'No phones',
                'addTxt':'Add Phone',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_phone_id\':\'0\',\'member\':\'yes\'});',
                },
            'emails':{'label':'Emails', 'type':'simplegrid', 'num_cols':1,
                'visible':function() {return (M.curBusiness.modules['ciniki.customers'].flags&0x20000000)==0?'yes':'no';},
                'headerValues':null,
                'cellClasses':['', ''],
                'noData':'No emails',
                'addTxt':'Add Email',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_email_id\':\'0\',\'member\':\'yes\'});',
                },
            'addresses':{'label':'Addresses', 'type':'simplegrid', 'num_cols':2,
                'headerValues':null,
                'cellClasses':['label', ''],
                'noData':'No addresses',
                'addTxt':'Add Address',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_address_id\':\'0\',\'member\':\'yes\'});',
                },
            'links':{'label':'Websites', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline', ''],
                'noData':'No websites',
                'addTxt':'Add Website',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_link_id\':\'0\',\'member\':\'yes\'});',
                },
            'images':{'label':'Gallery', 'type':'simplethumbs'},
            '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'addTxt':'Add Image',
                'addFn':'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'add\':\'yes\'});',
                },
            'short_bio':{'label':'Brief Bio', 'type':'htmlcontent'},
            'full_bio':{'label':'Full Bio', 'type':'htmlcontent'},
            'notes':{'label':'Notes', 'type':'htmlcontent'},
            '_buttons':{'label':'', 'buttons':{
                'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'member\':\'yes\'});'},
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
                        return '<img width="75px" height="75px" src=\'' + M.api.getBinaryURL('ciniki.customers.getImage', {'business_id':M.curBusinessID, 'image_id':d.image.image_id, 'version':'thumbnail', 'maxwidth':'75'}) + '\' />'; 
                    }
                } else {
                    return '<img width="75px" height="75px" src=\'/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg\' />';
                }
            }
        };
        this.member.rowFn = function(s, i, d) {
            if( s == 'phones' ) {
                return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_phone_id\':\'' + d.phone.id + '\',\'member\':\'yes\'});';
            }
            if( s == 'emails' ) {
                return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_email_id\':\'' + d.email.id + '\',\'member\':\'yes\'});';
            }
            if( s == 'addresses' ) {
                return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_address_id\':\'' + d.address.id + '\',\'member\':\'yes\'});';
            }
            if( s == 'links' ) {
                return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_link_id\':\'' + d.link.id + '\',\'member\':\'yes\'});';
            }
        };
        this.member.thumbFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_image_id\':\'' + d.image.id + '\'});';
        };
        this.member.addDropImage = function(iid) {
            var rsp = M.api.getJSON('ciniki.customers.imageAdd',
                {'business_id':M.curBusinessID, 'image_id':iid, 'webflags':'1',
                    'customer_id':M.ciniki_customers_members.member.customer_id});
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            return true;
        };
        this.member.addDropImageRefresh = function() {
            if( M.ciniki_customers_members.member.customer_id > 0 ) {
                var rsp = M.api.getJSONCb('ciniki.customers.get', {'business_id':M.curBusinessID, 
                    'customer_id':M.ciniki_customers_members.member.customer_id, 'images':'yes'}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_customers_members.member.data.images = rsp.customer.images;
                        M.ciniki_customers_members.member.refreshSection('images');
                    });
            }
        };
        this.member.addButton('edit', 'Edit', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'member\':\'yes\'});');
        this.member.addClose('Back');
    }
    
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_members', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        // Setup ui labels
        var slabel = 'Member';
        var plabel = 'Members';
        if( M.curBusiness.customers != null ) {
            if( M.curBusiness.customers.settings['ui-labels-member'] != null 
                && M.curBusiness.customers.settings['ui-labels-member'] != ''
                ) {
                slabel = M.curBusiness.customers.settings['ui-labels-member'];
            }
            if( M.curBusiness.customers.settings['ui-labels-members'] != null 
                && M.curBusiness.customers.settings['ui-labels-members'] != ''
                ) {
                plabel = M.curBusiness.customers.settings['ui-labels-members'];
            }
        }
        this.menu.title = plabel;
        this.list.title = plabel;
        this.member.title = slabel;
        this.menu.sections.members.addTxt = 'Add ' + slabel;
        this.list.sections.members.addTxt = 'Add ' + slabel;

        // Decide what's visible
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x08) > 0 ) {
            this.member.sections.membership.list.member_lastpaid.visible = 'yes';
            this.member.sections.membership.list.type.visible = 'yes';
            this.menu.sections.search.livesearchcols = 2;
            this.menu.sections.members.num_cols = 2;
            this.list.sections.members.num_cols = 2;
            this.list.sections.members.headerValues = ['Member', 'Membership'];
        } else {
            this.member.sections.membership.list.member_lastpaid.visible = 'no';
            this.member.sections.membership.list.type.visible = 'no';
            this.menu.sections.search.livesearchcols = 1;
            this.menu.sections.members.num_cols = 1;
            this.list.sections.members.num_cols = 1;
            this.list.sections.members.headerValues = null;
        }

        if( (M.curBusiness.modules['ciniki.customers'].flags&0x04000000) > 0 ) {
            this.member.sections.membership.list.start_date.visible = 'yes';
        } else {
            this.member.sections.membership.list.start_date.visible = 'no';
        }
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x010000) > 0 ) {
            this.member.sections.info.list.eid.visible = 'yes';
        } else {
            this.member.sections.info.list.eid.visible = 'no';
        }

        // Season Memberships
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x02000000) > 0 
            && M.curBusiness.modules['ciniki.customers'].settings != null
            && M.curBusiness.modules['ciniki.customers'].settings.seasons != null
            ) {
            this.menu.sections.seasons.visible = 'yes';
            this.member.sections.seasons.visible = 'yes';
            this.member.sections.membership.list.member_lastpaid.visible = 'no';
            this.menu.sections.seasons.list = {};
            for(i in M.curBusiness.modules['ciniki.customers'].settings.seasons) {
                var season = M.curBusiness.modules['ciniki.customers'].settings.seasons[i].season;
                if( season.open == 'yes' ) {
                    this.menu.sections.seasons.list['season-' + season.id] = {
                        'label':season.name, 
                        'fn':'M.ciniki_customers_members.showSeason(\'M.ciniki_customers_members.showMenu()\',\'' + season.id + '\',\'unattached\');'
                        };
                }
            }
        } else {
            this.menu.sections.seasons.visible = 'no';
            this.member.sections.seasons.visible = 'no';
            this.member.sections.membership.list.member_lastpaid.visible = 'yes';
        }

        //
        // Check if subscriptions module enabled
        //
        if( M.curBusiness.modules['ciniki.subscriptions'] != null ) {
            this.member.sections._subscriptions.visible = 'yes';
        } else {
            this.member.sections._subscriptions.visible = 'no';
        }

        if( args.customer_id != null && args.customer_id > 0 ) {
            this.showMember(cb, args.customer_id);
        } else {
            this.showMenu(cb);
        }
    }

    this.showMenu = function(cb) {
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x04) > 0 ) {
            this.menu.sections.members.visible = 'no';
            this.menu.sections.categories.visible = 'yes';
            M.api.getJSONCb('ciniki.customers.memberCategories', 
                {'business_id':M.curBusinessID}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_members.menu;
                    p.data = {'categories':rsp.categories};
                    p.sections.search.visible = 'yes';
                    p.refresh();
                    p.show(cb);
                }); 
        } else {
            // Get the list of existing customers
            this.menu.sections.members.visible = 'yes';
            this.menu.sections.categories.visible = 'no';
            M.api.getJSONCb('ciniki.customers.memberList', 
                {'business_id':M.curBusinessID}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_members.menu;
                    p.data = {'members':rsp.members};
                    if( rsp.members != null && rsp.members.length > 20 ) {
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
        if( p != null ) { this.list.permalink = unescape(p); }
        // Get the list of existing customers
        this.list.sections.members.label = this.list.category;
        M.api.getJSONCb('ciniki.customers.memberList', 
            {'business_id':M.curBusinessID, 'category':encodeURIComponent(this.list.permalink)}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_members.list;
                p.data = {'members':rsp.members};
                p.refresh();
                p.show(cb);
            }); 
    };

    this.showSeason = function(cb, sid, list, name) {
        if( sid != null ) { this.season.season_id = sid }
        if( list != null ) { this.season.sections.tabs.selected = list; }
        if( name != null ) { this.season.title = unescape(name); }
        // Get the list of existing customers
        M.api.getJSONCb('ciniki.customers.seasonInfo', 
            {'business_id':M.curBusinessID, 'season_id':this.season.season_id, 
                'list':this.season.sections.tabs.selected}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_members.season;
                    var settings = M.curBusiness.modules['ciniki.customers'].settings;
                    p.sections.tabs.tabs.unattached.label = 'Unknown (' + rsp.unattached + ')';
                    p.sections.tabs.tabs.inactive.label = 'Inactive (' + rsp.inactive + ')';
                    p.sections.tabs.tabs.regular.label = 'Regular (' + rsp.regular + ')';
                    p.sections.tabs.tabs.student.label = 'Student (' + rsp.student + ')';
                    p.sections.tabs.tabs.individual.label = 'Individual (' + rsp.individual + ')';
                    p.sections.tabs.tabs.family.label = 'Family (' + rsp.family + ')';
                    p.sections.tabs.tabs.complimentary.label = 'Complimentary (' + rsp.complimentary + ')';
                    p.sections.tabs.tabs.reciprocal.label = 'Reciprocal (' + rsp.reciprocal + ')';
                    p.sections.tabs.tabs.lifetime.label = 'Lifetime (' + rsp.lifetime + ')';
                    p.sections.tabs.tabs.regular.visible = (settings['membership-type-10-active']==null || settings['membership-type-10-active'] == 'yes')?'yes':'no';
                    p.sections.tabs.tabs.student.visible = (settings['membership-type-20-active']!=null && settings['membership-type-20-active'] == 'yes')?'yes':'no';
                    p.sections.tabs.tabs.individual.visible = (settings['membership-type-30-active']!=null && settings['membership-type-30-active'] == 'yes')?'yes':'no';
                    p.sections.tabs.tabs.family.visible = (settings['membership-type-40-active']!=null && settings['membership-type-40-active'] == 'yes')?'yes':'no';
                    p.sections.tabs.tabs.complimentary.visible = (settings['membership-type-110-active']==null || settings['membership-type-110-active'] == 'yes')?'yes':'no';
                    p.sections.tabs.tabs.reciprocal.visible = (settings['membership-type-150-active']==null || settings['membership-type-150-active'] == 'yes')?'yes':'no';
                    p.sections.tabs.tabs.lifetime.visible = 'yes';
                    p.data = rsp;
                    p.refresh();
                    p.show(cb);
            }); 
    };

    this.showMember = function(cb, cid) {
        if( cid != null ) { this.member.customer_id = cid; }
        var rsp = M.api.getJSONCb('ciniki.customers.get',
            {'business_id':M.curBusinessID, 'customer_id':this.member.customer_id, 
                'member_categories':'yes', 'phones':'yes', 'emails':'yes', 'addresses':'yes', 
                'links':'yes', 'images':'yes', 'seasons':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_customers_members.member;
                p.data = rsp.customer;
                if( (rsp.customer.webflags&0x01) == 1 ) {
                    p.data.webvisible = 'Visible';
                } else {
                    p.data.webvisible = 'Hidden';
                }
                
                if( (M.curBusiness.modules['ciniki.customers'].flags&0x04) > 0 ) {
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
