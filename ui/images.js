//
// The app to add/edit customers member images
//
function ciniki_customers_images() {
    this.webFlags = {
        '1':{'name':'Visible'},
        '2':{'name':'Sold'},
        };
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Image',
            'ciniki_customers_images', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.customers.images.edit');
        this.edit.data = {};
        this.edit.customer_id = 0;
        this.edit.customer_image_id = 0;
        this.edit.sections = {
            '_image':{'label':'Photo', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
            }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{
                'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_customers_images.saveImage();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_customers_images.deleteImage();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.customers.imageHistory', 'args':{'tnid':M.curTenantID, 
                'customer_image_id':M.ciniki_customers_images.edit.customer_image_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_customers_images.edit.setFieldValue('image_id', iid);
            return true;
        }
        this.edit.addButton('save', 'Save', 'M.ciniki_customers_images.saveImage();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_images', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.customer_id);
        } else if( args.customer_image_id != null && args.customer_image_id > 0 ) {
            this.showEdit(cb, args.customer_image_id);
        }
        return false;
    }

    this.showEdit = function(cb, iid, cid) {
        if( iid != null ) { this.edit.customer_image_id = iid; }
        if( cid != null ) { this.edit.customer_id = cid; }
        if( this.edit.customer_image_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.customers.imageGet', 
                {'tnid':M.curTenantID, 'customer_image_id':this.edit.customer_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_images.edit;
                    p.data = rsp.image;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            this.edit.data = {'webflags':1};
            if( cid != null ) { this.edit.customer_id = cid; }
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveImage = function() {
        if( this.edit.customer_image_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.customers.imageUpdate', 
                    {'tnid':M.curTenantID, 
                    'customer_image_id':this.edit.customer_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            M.ciniki_customers_images.edit.close();
                        });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            c += '&customer_id=' + encodeURIComponent(this.edit.customer_id);
            var rsp = M.api.postJSONFormData('ciniki.customers.imageAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } else {
                        M.ciniki_customers_images.edit.close();
                    }
                });
        }
    };

    this.deleteImage = function() {
        if( confirm('Are you sure you want to delete this image?') ) {
            var rsp = M.api.getJSONCb('ciniki.customers.imageDelete', {'tnid':M.curTenantID, 
                'customer_image_id':this.edit.customer_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_images.edit.close();
                });
        }
    };
}
