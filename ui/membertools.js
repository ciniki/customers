//
function ciniki_customers_membertools() {
	//
	// Panels
	//
	this.init = function() {
		//
		// The tools menu 
		//
		this.menu = new M.panel('Member Tools',
			'ciniki_customers_membertools', 'menu',
			'mc', 'narrow', 'sectioned', 'ciniki.customers.membertools.menu');
		this.menu.data = {};
		this.menu.sections = {
			'tools':{'label':'Downloads', 'list':{
				'directory':{'label':'Directory', 'fn':'M.ciniki_customers_membertools.downloadDirectory();'},
				}},
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_membertools', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showMenu(cb);
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb) {
		this.menu.refresh();
		this.menu.show(cb);
	};

	this.downloadDirectory = function() {
		window.open(M.api.getUploadURL('ciniki.customers.memberDownloadDirectory', 
			{'business_id':M.curBusinessID}));
	};

}
