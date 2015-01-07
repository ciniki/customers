//
// This panel contains the management tools for sales reps and their customer relationships
//
function ciniki_customers_salesreps() {
	//
	// Panels
	//
	this.menu = null;

	this.statusOptions = {
		'10':'Ordered',
		'20':'Started',
		'25':'SG Ready',
		'30':'Racked',
		'40':'Filtered',
		'60':'Bottled',
		'100':'Removed',
		'*':'Unknown',
		};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.menu = new M.panel('Sales Reps',
			'ciniki_customers_salesreps', 'menu',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.salesreps.menu');
		this.menu.data = {};
		this.menu.salesrep_id = 0;
		this.menu.sections = {
			'salesreps':{'label':'Sales Reps', 'aside':'yes', 'num_cols':1, 'type':'simplegrid', 
				'noData':'No sales reps',
				},
			'customers':{'label':'Sales Reps', 'num_cols':1, 'type':'simplegrid', 
				'noData':'No customers',
				},
			};
		this.menu.sectionData = function(s) {
			return this.data[s];
		};
		this.menu.noData = function(s) { 
			if( this.sections[s].noData != null ) { return this.sections[s].noData; }
			return ''; 
		}
		this.menu.cellValue = function(s, i, j, d) {
			if( s == 'salesreps' ) {
				return d.salesrep.firstname + ' ' + d.salesrep.lastname + ' <span class="subdue">[' + d.salesrep.display_name + ']</span> <span class="count">' + d.salesrep.num_customers + '</span>';
			} else if( s == 'customers' ) {
				return d.customer.display_name;
			}
			return '';
		};
		this.menu.rowFn = function(s, i, d) { 
			if( s == 'salesreps' ) {
				return 'M.ciniki_customers_salesreps.showReps(null, \'' + d.salesrep.id + '\');';
			} else if( s == 'customers' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_salesreps.showReps();\',\'mc\',{\'customer_id\':\'' + d.customer.id + '\'});';
			}
		};
		this.menu.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_salesreps', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showReps(cb, 0);
	};

	//
	// Grab the stats for the business from the database and present the list of customers.
	//
	this.showReps = function(cb, sid) {
		if( sid != null ) { this.menu.salesrep_id = sid; }
		//
		// Grab list of recently updated customers
		//
		M.api.getJSONCb('ciniki.customers.salesrepList', {'business_id':M.curBusinessID, 
			'salesrep_id':this.menu.salesrep_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				} 
				var p = M.ciniki_customers_salesreps.menu;
				p.data = rsp;
				p.refresh();
				p.show(cb);
			});
	};
}
