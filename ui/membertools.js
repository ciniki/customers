//
function ciniki_customers_membertools() {
	//
	// Panels
	//
	this.toggleOptions = {'no':'No', 'yes':'Yes'};
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
				'directory':{'label':'Directory (Word)', 'fn':'M.ciniki_customers_membertools.downloadDirectory();'},
				'memberlist':{'label':'Member List (Excel)', 'fn':'M.startApp(\'ciniki.customers.download\',null,\'M.ciniki_customers_membertools.showMenu();\',\'mc\',{\'membersonly\':\'yes\'});'},
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
		this.menu.title = slabel + ' Tools';
		this.menu.sections.tools.list.memberlist.label = 'Export ' + plabel + ' (Excel)';

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
		M.api.openFile('ciniki.customers.memberDownloadDirectory', {'business_id':M.curBusinessID});
	};
}
