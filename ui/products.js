//
function ciniki_customers_products() {
    //
    // The panel to list the product
    //
    this.menu = new M.panel('Membership Products', 'ciniki_customers_products', 'menu', 'mc', 'xlarge', 'sectioned', 'ciniki.customers.products.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'products':{'label':'Membership Products', 'type':'simplegrid', 'num_cols':6,
            'headerValues':['Type', 'Code', 'Name', 'Status', 'Online', 'Amount'],
            'noData':'No products',
            'addTxt':'Add Membership Product',
            'addFn':'M.ciniki_customers_products.product.open(\'M.ciniki_customers_products.menu.open();\',0,null);'
            },
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'products' ) {
            switch(j) {
                case 0: return d.type_display;
                case 1: return d.code;
                case 2: return d.name;
                case 3: return d.status_display;
                case 4: return d.online_display;
                case 5: return d.amount_display;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'products' ) {
            return 'M.ciniki_customers_products.product.open(\'M.ciniki_customers_products.menu.open();\',\'' + d.id + '\',M.ciniki_customers_products.product.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.customers.productList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_products.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit Membership Products
    //
    this.product = new M.panel('Membership Products', 'ciniki_customers_products', 'product', 'mc', 'medium', 'sectioned', 'ciniki.customers.products.product');
    this.product.data = null;
    this.product.product_id = 0;
    this.product.nplist = [];
    this.product.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'type':{'label':'Type', 'type':'toggle', 
                'toggles':{'10':'Membership', '20':'Lifetime', '40':'Membership Add-on'},
//                'onchange':'M.ciniki_customers_products.product.changeType',
                },
//            'code':{'label':'Code', 'type':'text', 'size':'small'},
            'code':{'label':'Product Code', 'required':'', 'type':'text', 'size':'small',
                'visible':function() { return M.modFlagSet('ciniki.sapos', 0x0400); },
            },
            'name':{'label':'Product Name', 'required':'yes', 'type':'text'},
            'short_name':{'label':'Internal Name', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '90':'Archived'}},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}, '2':{'name':'Buy Online'}}},
            'months':{'label':'Months', 'type':'text', 'size':'small'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            'unit_amount':{'label':'Amount', 'type':'text', 'size':'small'},
            }},
/*        '_primary_image_id':{'label':'Primary Image', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_customers_products.product.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }}, */
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
//        '_description':{'label':'Description', 'fields':{
//            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
//            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_customers_products.product.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_customers_products.product.product_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_customers_products.product.remove();'},
            }},
        };
    this.product.fieldValue = function(s, i, d) { return this.data[i]; }
    this.product.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.customers.productHistory', 'args':{'tnid':M.curTenantID, 'product_id':this.product_id, 'field':i}};
    }
//    this.product.changeType = function(e,s,id) {
//        var t = this.formValue('type');
//    }
    this.product.open = function(cb, pid, list) {
        if( pid != null ) { this.product_id = pid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.customers.productGet', {'tnid':M.curTenantID, 'product_id':this.product_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_products.product;
            p.data = rsp.product;
            p.refresh();
            p.show(cb);
//            p.changeType();
        });
    }
    this.product.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_customers_products.product.close();'; }
        if( this.product_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.customers.productUpdate', {'tnid':M.curTenantID, 'product_id':this.product_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.customers.productAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_products.product.product_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.product.remove = function() {
        if( confirm('Are you sure you want to remove product?') ) {
            M.api.getJSONCb('ciniki.customers.productDelete', {'tnid':M.curTenantID, 'product_id':this.product_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_products.product.close();
            });
        }
    }
    this.product.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.product_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_customers_products.product.save(\'M.ciniki_customers_products.product.open(null,' + this.nplist[this.nplist.indexOf('' + this.product_id) + 1] + ');\');';
        }
        return null;
    }
    this.product.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.product_id) > 0 ) {
            return 'M.ciniki_customers_products.product.save(\'M.ciniki_customers_products.product.open(null,' + this.nplist[this.nplist.indexOf('' + this.product_id) - 1] + ');\');';
        }
        return null;
    }
    this.product.addButton('save', 'Save', 'M.ciniki_customers_products.product.save();');
    this.product.addClose('Cancel');
    this.product.addButton('next', 'Next');
    this.product.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Membership Product Purchases
    //
    this.purchase = new M.panel('Membership Product Purchases', 'ciniki_customers_products', 'purchase', 'mc', 'medium', 'sectioned', 'ciniki.customers.main.purchase');
    this.purchase.data = null;
    this.purchase.purchase_id = 0;
    this.purchase.nplist = [];
    this.purchase.sections = {
        'general':{'label':'', 'fields':{
            'product_id':{'label':'Product', 'required':'yes', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'name'}},
//            'flags':{'label':'Options', 'type':'text'},
            'purchase_date':{'label':'Date Purchased', 'type':'date'},
//            'invoice_id':{'label':'Invoice ID', 'required':'yes', 'type':'text'},
            'start_date':{'label':'Start Date', 'type':'date'},
            'end_date':{'label':'End Date', 'type':'date'},
//            'stripe_customer_id':{'label':'Stripe Customer', 'type':'text'},
//            'stripe_subscription_id':{'label':'Stripe Subscription', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_customers_products.purchase.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_customers_products.purchase.purchase_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_customers_products.purchase.remove();'},
            }},
        };
    this.purchase.fieldValue = function(s, i, d) { return this.data[i]; }
    this.purchase.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.customers.purchaseHistory', 'args':{'tnid':M.curTenantID, 'purchase_id':this.purchase_id, 'field':i}};
    }
    this.purchase.open = function(cb, pid, list) {
        if( pid != null ) { this.purchase_id = pid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.customers.purchaseGet', {'tnid':M.curTenantID, 'purchase_id':this.purchase_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_customers_products.purchase;
            p.data = rsp.purchase;
            p.sections.general.fields.product_id.options = rsp.products;
            p.refresh();
            p.show(cb);
        });
    }
    this.purchase.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_customers_products.purchase.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.purchase_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.customers.purchaseUpdate', {'tnid':M.curTenantID, 'purchase_id':this.purchase_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.customers.purchaseAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_products.purchase.purchase_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.purchase.remove = function() {
        if( confirm('Are you sure you want to remove product_purchase?') ) {
            M.api.getJSONCb('ciniki.customers.purchaseDelete', {'tnid':M.curTenantID, 'purchase_id':this.purchase_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_customers_products.purchase.close();
            });
        }
    }
    this.purchase.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.purchase_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_customers_products.purchase.save(\'M.ciniki_customers_products.purchase.open(null,' + this.nplist[this.nplist.indexOf('' + this.purchase_id) + 1] + ');\');';
        }
        return null;
    }
    this.purchase.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.purchase_id) > 0 ) {
            return 'M.ciniki_customers_products.purchase.save(\'M.ciniki_customers_products.purchase.open(null,' + this.nplist[this.nplist.indexOf('' + this.purchase_id) - 1] + ');\');';
        }
        return null;
    }
    this.purchase.addButton('save', 'Save', 'M.ciniki_customers_products.purchase.save();');
    this.purchase.addClose('Cancel');
    this.purchase.addButton('next', 'Next');
    this.purchase.addLeftButton('prev', 'Prev');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        // 
        // Check if redirect required to accounts
        //
        if( M.modFlagOn('ciniki.customers', 0x0800) ) {
            return M.startApp('ciniki.customers.accounts',null,cb,appPrefix,aG)
        }
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_products', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.purchase_id != null && args.purchase_id > 0 ) {
            this.purchase.open(cb, args.purchase_id);
        } else {
            this.menu.open(cb);
        }
    }
}
