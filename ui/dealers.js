//
// The dealers app to manage dealers for an customers
//
function ciniki_customers_dealers() {
	this.webFlags = {'2':{'name':'Visible'}};
	this.init = function() {
		//
		// Setup the main panel to list the dealers 
		//
		this.menu = new M.panel('Dealers',
			'ciniki_customers_dealers', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.customers.dealers.menu');
		this.menu.data = {};
		this.menu.sections = {
			'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 
				'hint':'name, company or email', 'noData':'No dealers found',
				},
			'dealers':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No dealers',
				'addTxt':'Add Dealer',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showMenu();\',\'mc\',{\'customer_id\':0,\'dealer\':\'yes\'});',
				},
			'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_customers_dealers.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_dealers.menu.panelUID + '_' + s), rsp.customers); 
					});
				return true;
			}
		};
		this.menu.liveSearchResultValue = function(s, f, i, j, d) {
			if( s == 'search' ) { 
				return d.customer.display_name;
			}
			return '';
		}
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.ciniki_customers_dealers.showDealer(\'M.ciniki_customers_dealers.showMenu();\',\'' + d.customer.id + '\');'; 
		};
		this.menu.cellValue = function(s, i, j, d) {
			if( s == 'dealers' && j == 0 ) {
				if( d.dealer.company != null && d.dealer.company != '' ) {
					return '<span class="maintext">' + d.dealer.first + ' ' + d.dealer.last + '</span><span class="subtext">' + d.dealer.company + '</span>';
				} 
				return '<span class="maintext">' + d.dealer.display_name + '</span>';
			}
			else if( s == 'categories' && j == 0 ) {
				return d.category.name + '<span class="count">' + d.category.num_dealers + '</span>';
			}
		};
		this.menu.rowFn = function(s, i, d) { 
			if( s == 'dealers' ) {
				return 'M.ciniki_customers_dealers.showDealer(\'M.ciniki_customers_dealers.showMenu();\',\'' + d.dealer.id + '\');'; 
			} else if( s == 'categories' ) {
				return 'M.ciniki_customers_dealers.showList(\'M.ciniki_customers_dealers.showMenu();\',\'' + escape(d.category.name) + '\',\'' + d.category.permalink + '\');'; 
			}
		};
		this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showMenu();\',\'mc\',{\'customer_id\':0,\'dealer\':\'yes\'});');
		this.menu.addButton('tools', 'Tools', 'M.startApp(\'ciniki.customers.dealertools\',null,\'M.ciniki_customers_dealers.showMenu();\',\'mc\',{});');
		this.menu.addClose('Back');

		//
		// Setup the main panel to list the dealers from a category
		//
		this.list = new M.panel('Dealers',
			'ciniki_customers_dealers', 'list',
			'mc', 'medium', 'sectioned', 'ciniki.customers.dealers.list');
		this.list.data = {};
		this.list.sections = {
			'dealers':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No dealers',
				'addTxt':'Add Dealer',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showList();\',\'mc\',{\'customer_id\':0,\'dealer\':\'yes\'});',
				},
			};
		this.list.sectionData = function(s) { return this.data[s]; }
		this.list.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.dealer.company != null && d.dealer.company != '' ) {
					return '<span class="maintext">' + d.dealer.first + ' ' + d.dealer.last + '</span><span class="subtext">' + d.dealer.company + '</span>';
				} 
				return '<span class="maintext">' + d.dealer.display_name + '</span>';
			}
		};
		this.list.rowFn = function(s, i, d) { 
			return 'M.ciniki_customers_dealers.showDealer(\'M.ciniki_customers_dealers.showList();\',\'' + d.dealer.id + '\');'; 
		};
		this.list.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showList();\',\'mc\',{\'customer_id\':0,\'dealer\':\'yes\'});');
		this.list.addClose('Back');

		//
		// The dealer panel will show the information for a dealer/sponsor/organizer
		//
		this.dealer = new M.panel('Dealer',
			'ciniki_customers_dealers', 'dealer',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.dealers.dealer');
		this.dealer.data = {};
		this.dealer.customer_id = 0;
		this.dealer.sections = {
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
				}},
			'info':{'label':'', 'aside':'yes', 'list':{
				'name':{'label':'Name'},
				'company':{'label':'Company', 'visible':'no'},
				'webvisible':{'label':'Web Settings'},
				'dealer_status_text':{'label':'Status'},
				'dealer_categories':{'label':'Categories', 'visible':'no'},
				}},
			'phones':{'label':'Phones', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No phones',
				'addTxt':'Add Phone',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'edit_phone_id\':\'0\',\'dealer\':\'yes\'});',
				},
			'emails':{'label':'Emails', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No emails',
				'addTxt':'Add Email',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'edit_email_id\':\'0\',\'dealer\':\'yes\'});',
				},
			'addresses':{'label':'Addresses', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No addresses',
				'addTxt':'Add Address',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'edit_address_id\':\'0\',\'dealer\':\'yes\'});',
				},
			'links':{'label':'Websites', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', ''],
				'noData':'No websites',
				'addTxt':'Add Website',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'edit_link_id\':\'0\',\'dealer\':\'yes\'});',
				},
			'images':{'label':'Gallery', 'type':'simplethumbs'},
			'_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Image',
				'addFn':'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'add\':\'yes\'});',
				},
			'short_bio':{'label':'Brief Bio', 'type':'htmlcontent'},
			'full_bio':{'label':'Full Bio', 'type':'htmlcontent'},
			'notes':{'label':'Notes', 'type':'htmlcontent'},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'dealer\':\'yes\'});'},
				}},
		};
		this.dealer.sectionData = function(s) {
			if( s == 'info' || s == 'dealership' ) { return this.sections[s].list; }
			if( s == 'short_bio' || s == 'full_bio' || s == 'notes' ) { return this.data[s].replace(/\n/g, '<br/>'); }
			return this.data[s];
			};
		this.dealer.listLabel = function(s, i, d) {
			if( s == 'info' || s == 'dealership' ) { 
				return d.label; 
			}
			return null;
		};
		this.dealer.listValue = function(s, i, d) {
			if( s == 'dealership' && i == 'type' ) {
				var txt = '';
				if( this.data.dealership_type != null && this.data.dealership_type != '' ) {
					switch(this.data.dealership_type) {
						case '10': txt += 'Regular'; break;
						case '20': txt += 'Complimentary'; break;
						case '30': txt += 'Reciprocal'; break;
					}
				}
				if( this.data.dealership_length != null && this.data.dealership_length != '' ) {
					switch(this.data.dealership_length) {
						case '10': txt += (txt!=''?', ':'') + 'Monthly'; break;
						case '20': txt += (txt!=''?', ':'') + 'Yearly'; break;
						case '60': txt += (txt!=''?', ':'') + 'Lifetime'; break;
					}
				}
				return txt;
			}
			if( i == 'url' && this.data[i] != '' ) {
				return '<a target="_blank" href="http://' + this.data[i] + '">' + this.data[i] + '</a>';
			}
			if( i == 'name' ) {
				return this.data.first + ' ' + this.data.last;
			}
			return this.data[i];
		};
		this.dealer.fieldValue = function(s, i, d) {
			if( i == 'description' || i == 'notes' ) { 
				return this.data[i].replace(/\n/g, '<br/>');
			}
			return this.data[i];
		};
		this.dealer.cellValue = function(s, i, j, d) {
			if( s == 'phones' ) {
				switch(j) {
					case 0: return d.phone.phone_label;
					case 1: return d.phone.phone_number + ((d.phone.flags&0x08)>0?' <span class="subdue">(Public)</span>':'');
				}
			}
			if( s == 'emails' ) {
				return d.email.address + ((d.email.flags&0x08)>0?' <span class="subdue">(Public)</span>':'');
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
					return v;
				}
			}
			if( s == 'links' ) {
				if( d.link.name != '' ) {
					return '<span class="maintext">' + d.link.name + ((d.link.webflags&0x01)>0?' <span class="subdue">(Public)</span>':'') + '</span><span class="subtext">' + d.link.url + '</span>';
				} else {
					return d.link.url + ((d.link.webflags&0x01)>0?' <span class="subdue">(Public)</span>':'');
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
		this.dealer.rowFn = function(s, i, d) {
			if( s == 'phones' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'edit_phone_id\':\'' + d.phone.id + '\',\'dealer\':\'yes\'});';
			}
			if( s == 'emails' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'edit_email_id\':\'' + d.email.id + '\',\'dealer\':\'yes\'});';
			}
			if( s == 'addresses' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'edit_address_id\':\'' + d.address.id + '\',\'dealer\':\'yes\'});';
			}
			if( s == 'links' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'edit_link_id\':\'' + d.link.id + '\',\'dealer\':\'yes\'});';
			}
		};
		this.dealer.thumbSrc = function(s, i, d) {
			if( d.image.image_data != null && d.image.image_data != '' ) {
				return d.image.image_data;
			} else {
				return '/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg';
			}
		};
		this.dealer.thumbTitle = function(s, i, d) {
			if( d.image.name != null ) { return d.image.name; }
			return '';
		};
		this.dealer.thumbID = function(s, i, d) {
			if( d.image.id != null ) { return d.image.id; }
			return 0;
		};
		this.dealer.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_image_id\':\'' + d.image.id + '\'});';
		};
		this.dealer.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.customers.imageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'webflags':'1',
					'customer_id':M.ciniki_customers_dealers.dealer.customer_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.dealer.addDropImageRefresh = function() {
			if( M.ciniki_customers_dealers.dealer.customer_id > 0 ) {
				var rsp = M.api.getJSONCb('ciniki.customers.get', {'business_id':M.curBusinessID, 
					'customer_id':M.ciniki_customers_dealers.dealer.customer_id, 'images':'yes'}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_customers_dealers.dealer.data.images = rsp.customer.images;
						M.ciniki_customers_dealers.dealer.refreshSection('images');
					});
			}
		};
		this.dealer.addButton('edit', 'Edit', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_dealers.showDealer();\',\'mc\',{\'customer_id\':M.ciniki_customers_dealers.dealer.customer_id,\'dealer\':\'yes\'});');
		this.dealer.addClose('Back');
	}
	
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create container
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_dealers', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}
	
		if( args.customer_id != null && args.customer_id > 0 ) {
			this.showDealer(cb, args.customer_id);
		} else {
			this.showMenu(cb);
		}
	}

	this.showMenu = function(cb) {
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x20) > 0 ) {
			this.menu.sections.dealers.visible = 'no';
			this.menu.sections.categories.visible = 'yes';
			M.api.getJSONCb('ciniki.customers.dealerCategories', 
				{'business_id':M.curBusinessID}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_customers_dealers.menu;
					p.data = {'categories':rsp.categories};
					p.refresh();
					p.show(cb);
				});	
		} else {
			// Get the list of existing customers
			this.menu.sections.dealers.visible = 'yes';
			this.menu.sections.categories.visible = 'no';
			M.api.getJSONCb('ciniki.customers.dealerList', 
				{'business_id':M.curBusinessID}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_customers_dealers.menu;
					p.data = {'dealers':rsp.dealers};
					if( rsp.dealers != null && rsp.dealers.length > 20 ) {
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
		this.list.sections.dealers.label = this.list.category;
		M.api.getJSONCb('ciniki.customers.dealerList', 
			{'business_id':M.curBusinessID, 'category':encodeURIComponent(this.list.permalink)}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_customers_dealers.list;
				p.data = {'dealers':rsp.dealers};
				p.refresh();
				p.show(cb);
			});	
	};

	this.showDealer = function(cb, cid) {
		if( cid != null ) { this.dealer.customer_id = cid; }
		var rsp = M.api.getJSONCb('ciniki.customers.get',
			{'business_id':M.curBusinessID, 'customer_id':this.dealer.customer_id, 
				'dealer_categories':'yes', 'phones':'yes', 'emails':'yes', 'addresses':'yes', 
				'links':'yes', 'images':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_customers_dealers.dealer;
				p.data = rsp.customer;
				if( (rsp.customer.webflags&0x02) > 0 ) {
					p.data.webvisible = 'Visible';
				} else {
					p.data.webvisible = 'Hidden';
				}
				
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x20) > 0 ) {
					p.sections.info.list.dealer_categories.visible = 'yes';
					if( rsp.customer.dealer_categories != null && rsp.customer.dealer_categories != '' ) {
						p.data.dealer_categories = rsp.customer.dealer_categories.replace(/::/g, ', ');
					}
				} else {
					p.sections.info.list.dealer_categories.visible = 'no';
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
