function ciniki_customers_reminders() {
    //
    // The panel to list the reminder
    //
    /*
    this.menu = new M.panel('reminder', 'ciniki_customers_reminders', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.customers.reminders.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search reminder',
            'noData':'No reminder found',
            },
        'reminders':{'label':'Reminders', 'type':'simplegrid', 'num_cols':3,
            'noData':'No reminder',
            'addTxt':'Add Reminders',
            'addFn':'M.ciniki_customers_reminders.reminder.open(\'M.ciniki_customers_reminders.menu.open();\',0);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.customers.reminderSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_customers_reminders.menu.liveSearchShow('search',null,M.gE(M.ciniki_customers_reminders.menu.panelUID + '_' + s), rsp.reminders);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_customers_reminders.reminder.open(\'M.ciniki_customers_reminders.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'reminders' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'reminders' ) {
            return 'M.ciniki_customers_reminders.reminder.open(\'M.ciniki_customers_reminders.menu.open();\',\'' + d.id + '\',M.ciniki_customers_reminders.reminder.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.customers.reminderList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_reminders.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');
    */

    //
    // The panel to edit Reminders
    //
    this.reminder = new M.panel('Reminders', 'ciniki_customers_reminders', 'reminder', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.main.reminder');
    this.reminder.data = null;
    this.reminder.source = null;
    this.reminder.reminder_id = 0;
    this.reminder.customer_id = 0;
    this.reminder.nplist = [];
    this.reminder.sections = {
        'customer_details':{'label':'Customer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2, 
            'visible':'no',
            'cellClasses':['label', ''],
            'changeTxt':'View Customer',
            'changeFn':'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_customers_reminders.reminder.open();\',\'mc\',{\'customer_id\':M.ciniki_customers_reminders.reminder.data.customer_id});',
            },
        'general':{'label':'Reminder', 'aside':'yes', 'fields':{
            'reminder_date':{'label':'Date', 'required':'yes', 'type':'date'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'description':{'label':'Description', 'required':'yes', 'type':'text'},
            }},
        '_repeat':{'label':'Repeat', 'aside':'yes', 'fields':{
            'repeat_type':{'label':'', 'type':'select', 'none':'yes', 
                'options':{'0':'None', '10':'Daily', '20':'Weekly', '30':'Monthly by Date', '31':'Monthly by Weekday','40':'Yearly'},
                'onchangeFn':'M.ciniki_customers_reminders.reminder.updateInterval',
                },
            'repeat_interval':{'label':'Every', 'type':'multitoggle', 'visible':'no',
                'toggles':{'1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', '7':'7', '8':'8'},
                'hint':' ',
                },
            'repeat_end':{'label':'End Date', 'type':'date', 'hint':'never', 'visible':'no'},
            }},
        '_email':{'label':'Email', 'fields':{
            'flags1':{'label':'Auto Send Email', 'type':'flagtoggle', 'bit':0x01, 'field':'flags', 'default':'no',
                'on_fields':['email_time'],
                },
            'email_time':{'label':'Email Time', 'visible':'yes', 'type':'text', 'size':'small'},
            'email_subject':{'label':'Email Subject', 'visible':'yes', 'type':'text'},
            'email_html':{'label':'Email Content', 'visible':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_customers_reminders.reminder.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_customers_reminders.reminder.reminder_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_customers_reminders.reminder.remove();'},
            }},
        };
//    this.reminder.fieldValue = function(s, i, d) { return this.data[i]; }
    this.reminder.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.customers.reminderHistory', 'args':{'tnid':M.curTenantID, 'reminder_id':this.reminder_id, 'field':i}};
    }
    this.reminder.cellValue = function(s, i, j, d) {
        switch(j) { 
            case 0: return d.label;
            case 1: return (d.label == 'Email' ? M.linkEmail(d.value):d.value);
        }
    }
    this.reminder.liveSearchCb = function(s, i, v) {
        if( i == 'category' ) {
            M.api.getJSONBgCb('ciniki.customers.reminderCategorySearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_customers_reminders.reminder.liveSearchShow(s,i,M.gE(M.ciniki_customers_reminders.reminder.panelUID + '_' + i), rsp.categories);
                });
        }
    }
    this.reminder.liveSearchResultValue = function(s, f, i, j, d) {
        return d.category;
    }
    this.reminder.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_customers_reminders.reminder.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.category) + '\');';
    }
    this.reminder.updateField = function(s, fid, v) {
        M.gE(this.panelUID + '_' + fid).value = unescape(v);
        this.removeLiveSearch(s, fid);
    }
    this.reminder.rowFn = function(s, i, d) {
        return '';
    }
    this.reminder.updateInterval = function(s, i) {
        var rt = this.formValue('repeat_type');
        if( rt == 0 ) {
            this.sections._repeat.fields.repeat_interval.visible = 'no';
            this.sections._repeat.fields.repeat_end.visible = 'no';
        } else {
            this.sections._repeat.fields.repeat_interval.visible = 'yes';
            this.sections._repeat.fields.repeat_end.visible = 'yes';
            switch(rt) {
                case '10': M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'days'; break;
                case '20': M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'weeks'; break;
                case '30': M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'months'; break;
                case '31': M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'months'; break;
                case '40': M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'years'; break;
            }
        }
        this.showHideFormField('_repeat', 'repeat_interval');
        this.showHideFormField('_repeat', 'repeat_end');
    };
    this.reminder.open = function(cb, rid, cid, source, list) {
        if( rid != null ) { this.reminder_id = rid; }
        if( cid != null ) { this.customer_id = cid; }
        if( list != null ) { this.nplist = list; }
        if( source != null ) { 
            this.sections.customer_details.visible = (source != 'customer' ? 'yes' : 'no');
        }
        M.api.getJSONCb('ciniki.customers.reminderGet', {'tnid':M.curTenantID, 'reminder_id':this.reminder_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_reminders.reminder;
            p.data = rsp.reminder;
            p.sections._email.fields.email_time.visible = ((rsp.reminder.flags&0x01) == 0x01 ? 'yes' : 'no');
            p.refresh();
            p.show(cb);
            p.updateInterval();
        });
    }
    this.reminder.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_customers_reminders.reminder.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.reminder_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.customers.reminderUpdate', {'tnid':M.curTenantID, 'reminder_id':this.reminder_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.customers.reminderAdd', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_reminders.reminder.reminder_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.reminder.remove = function() {
        if( confirm('Are you sure you want to remove reminder?') ) {
            M.api.getJSONCb('ciniki.customers.reminderDelete', {'tnid':M.curTenantID, 'reminder_id':this.reminder_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_reminders.reminder.close();
            });
        }
    }
    this.reminder.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.reminder_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_customers_reminders.reminder.save(\'M.ciniki_customers_reminders.reminder.open(null,null,null,null,' + this.nplist[this.nplist.indexOf('' + this.reminder_id) + 1] + ');\');';
        }
        return null;
    }
    this.reminder.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.reminder_id) > 0 ) {
            return 'M.ciniki_customers_reminders.reminder.save(\'M.ciniki_customers_reminders.reminder.open(null,null,null,null,' + this.nplist[this.nplist.indexOf('' + this.reminder_id) - 1] + ');\');';
        }
        return null;
    }
    this.reminder.addButton('save', 'Save', 'M.ciniki_customers_reminders.reminder.save();');
    this.reminder.addClose('Cancel');
    this.reminder.addButton('next', 'Next');
    this.reminder.addLeftButton('prev', 'Prev');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_reminders', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.reminder_id != null && args.reminder_id > 0 ) {
            this.reminder.open(cb, args.reminder_id, null, (args.source == null ? '' : args.source));
        } else if( args.appointment_id != null && args.appointment_id > 0 ) {
            this.reminder.open(cb, args.appointment_id, null, (args.source == null ? '' : args.source));
        } else if( args.customer_id != null && args.customer_id > 0 ) {
            this.reminder.open(cb, 0, args.customer_id);
        } else {
            this.menu.open(cb);
        }
    }
}
