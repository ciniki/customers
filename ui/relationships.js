//
function ciniki_customers_relationships() {
    //
    // Panels
    //
    this.main = null;

    this.relationshipOptions = {
        '10':'tenant owner of',
        '-10':'owned by',
        '11':'a tenant partner of',
        '30':'a friend of',
        '40':'a relative of',
        '41':'a parent to',
        '-41':'a child of',
        '42':'a step-parent to',
        '-42':'a step-child of',
        '43':'a parent-in-law to',
        '-43':'a child-in-law of',
        '44':'a spouse of',
        '45':'a sibling of',
        '46':'a step-sibling of',
        '47':'a sibling-in-law of',
        };

    this.init = function() {
        //
        // The panel to edit an existing relationship
        //
        this.edit = new M.panel('Relationship',
            'ciniki_customers_relationships', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.customers.relationships.edit');
        this.edit.data = {};
        this.edit.sections = {
            'relationship':{'label':'Relationship', 'fields':{
                'relationship_type':{'label':'Type', 'type':'select', 'options':this.relationshipOptions},
                'related_id':{'label':'', 'type':'fkid', 'livesearch':'yes'},
                'date_started':{'label':'Started', 'type':'date'},
                'date_ended':{'label':'Ended', 'type':'date'},
                }},
            '_notes':{'label':'Notes', 'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save Relationship', 'fn':'M.ciniki_customers_relationships.saveRelationship();'},
                'delete':{'label':'Delete Relationship', 'fn':'M.ciniki_customers_relationships.deleteRelationship();'},
                }},
            };
        this.edit.fieldValue = function(s, i, d) { 
            if( i == 'related_id_fkidstr' ) { return this.data['customer_name']; }
            return this.data[i]; 
        };
        this.edit.liveSearchCb = function(s, i, value) {
            if( i == 'related_id' ) {
                var rsp = M.api.getJSONBgCb('ciniki.customers.searchQuick',
                    {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
                    function(rsp) {
                        M.ciniki_customers_relationships.edit.liveSearchShow(s, i, M.gE(M.ciniki_customers_relationships.edit.panelUID + '_' + i), rsp.customers);
                    });
            }
        };
        this.edit.liveSearchResultValue = function(s, f, i, j, d) {
            if( f == 'related_id' ) { return d.name; }
            return '';
        };
        this.edit.liveSearchResultRowFn = function(s, f, i, j, d) {
            if( f == 'related_id' ) {
                return 'M.ciniki_customers_relationships.edit.updateCustomer(\'' + s + '\',\'' + escape(d.name) + '\',\'' + d.id + '\');';
            }
        };
        this.edit.updateCustomer = function(s, customer_name, customer_id) {
            M.gE(this.panelUID + '_related_id').value = customer_id;
            M.gE(this.panelUID + '_related_id_fkidstr').value = unescape(customer_name);
            this.removeLiveSearch(s, 'related_id');
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.customers.relationshipHistory', 'args':{'tnid':M.curTenantID, 
                'customer_id':this.customer_id, 'relationship_id':this.relationship_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_customers_relationships.saveRelationship();');
        this.edit.addClose('cancel');
    };

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_customers_relationships', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.relationship_id != null && args.relationship_id > 0 
            && args.customer_id != null && args.customer_id > 0 ) {
            // Edit an existing relationship
            this.showEdit(cb, args.customer_id, args.relationship_id);
        } else if( args.customer_id != null && args.customer_id > 0 ) {
            // Add a new relationship for a customer
            this.showEdit(cb, args.customer_id, 0);
        }
    };

    this.showEdit = function(cb, cid, rid) {
        if( cid != null ) {
            this.edit.customer_id = cid;
        }
        if( rid != null ) {
            this.edit.relationship_id = rid;
        }
        if( this.edit.relationship_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.customers.relationshipGet', 
                {'tnid':M.curTenantID, 'customer_id':this.edit.customer_id, 
                'relationship_id':this.edit.relationship_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_customers_relationships.edit;
                    p.data = rsp.relationship;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveRelationship = function() {
        if( this.edit.relationship_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.customers.relationshipUpdate', 
                    {'tnid':M.curTenantID, 'customer_id':this.edit.customer_id, 
                    'relationship_id':this.edit.relationship_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_customers_relationships.edit.close();
                    });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.customers.relationshipAdd', 
                    {'tnid':M.curTenantID, 'customer_id':this.edit.customer_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_customer_relationships.edit.close();
                    });
            } else {
                this.edit.close();
            }
        }
    };

    this.deleteRelationship = function() {
        if( confirm("Are you sure you want to remove this relationship?") ) {
            var rsp = M.api.getJSONCb('ciniki.customers.relationshipDelete', 
                {'tnid':M.curTenantID, 'relationship_id':this.edit.relationship_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_customers_relationships.edit.close();
                });
        }   
    };
}
