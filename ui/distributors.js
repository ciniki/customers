//
// The distributors app to manage distributors for an customers
//
function ciniki_customers_distributors() {
	this.webFlags = {'3':{'name':'Visible'}};
	this.init = function() {
		//
		// Setup the main panel to list the distributors 
		//
		this.menu = new M.panel('Distributors',
			'ciniki_customers_distributors', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.customers.distributors.menu');
		this.menu.data = {};
		this.menu.sections = {
			'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 
				'hint':'name, company or email', 'noData':'No distributors found',
				},
			'distributors':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No distributors',
				'addTxt':'Add Distributor',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showMenu();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});',
				},
			'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_customers_distributors.menu.liveSearchShow('search', null, M.gE(M.ciniki_customers_distributors.menu.panelUID + '_' + s), rsp.customers); 
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
			return 'M.ciniki_customers_distributors.showDistributor(\'M.ciniki_customers_distributors.showMenu();\',\'' + d.customer.id + '\');'; 
		};
		this.menu.cellValue = function(s, i, j, d) {
			if( s == 'distributors' && j == 0 ) {
				if( d.distributor.company != null && d.distributor.company != '' ) {
					return '<span class="maintext">' + d.distributor.first + ' ' + d.distributor.last + '</span><span class="subtext">' + d.distributor.company + '</span>';
				} 
				return '<span class="maintext">' + d.distributor.display_name + '</span>';
			}
			else if( s == 'categories' && j == 0 ) {
				return d.category.name + '<span class="count">' + d.category.num_distributors + '</span>';
			}
		};
		this.menu.rowFn = function(s, i, d) { 
			if( s == 'distributors' ) {
				return 'M.ciniki_customers_distributors.showDistributor(\'M.ciniki_customers_distributors.showMenu();\',\'' + d.distributor.id + '\');'; 
			} else if( s == 'categories' ) {
				return 'M.ciniki_customers_distributors.showList(\'M.ciniki_customers_distributors.showMenu();\',\'' + escape(d.category.name) + '\',\'' + d.category.permalink + '\');'; 
			}
		};
		this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showMenu();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});');
		this.menu.addButton('tools', 'Tools', 'M.startApp(\'ciniki.customers.distributortools\',null,\'M.ciniki_customers_distributors.showMenu();\',\'mc\',{});');
		this.menu.addClose('Back');

		//
		// Setup the main panel to list the distributors from a category
		//
		this.list = new M.panel('Distributors',
			'ciniki_customers_distributors', 'list',
			'mc', 'medium', 'sectioned', 'ciniki.customers.distributors.list');
		this.list.data = {};
		this.list.sections = {
			'distributors':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No distributors',
				'addTxt':'Add Distributor',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showList();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});',
				},
			};
		this.list.sectionData = function(s) { return this.data[s]; }
		this.list.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.distributor.company != null && d.distributor.company != '' ) {
					return '<span class="maintext">' + d.distributor.first + ' ' + d.distributor.last + '</span><span class="subtext">' + d.distributor.company + '</span>';
				} 
				return '<span class="maintext">' + d.distributor.display_name + '</span>';
			}
		};
		this.list.rowFn = function(s, i, d) { 
			return 'M.ciniki_customers_distributors.showDistributor(\'M.ciniki_customers_distributors.showList();\',\'' + d.distributor.id + '\');'; 
		};
		this.list.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showList();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});');
		this.list.addClose('Back');

		//
		// The distributor panel will show the information for a distributor/sponsor/organizer
		//
		this.distributor = new M.panel('Distributor',
			'ciniki_customers_distributors', 'distributor',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.distributors.distributor');
		this.distributor.data = {};
		this.distributor.customer_id = 0;
		this.distributor.sections = {
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
				}},
			'info':{'label':'', 'list':{
				'name':{'label':'Name'},
				'company':{'label':'Company', 'visible':'no'},
				'webvisible':{'label':'Web Settings'},
				'distributor_status_text':{'label':'Status'},
				'distributor_categories':{'label':'Categories', 'visible':'no'},
				}},
			'account':{'label':'', 'aside':'yes', 'visible':'yes', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'dataMaps':['name', 'value'],
				},
			'phones':{'label':'Phones', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No phones',
				'addTxt':'Add Phone',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_phone_id\':\'0\',\'distributor\':\'yes\'});',
				},
			'emails':{'label':'Emails', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No emails',
				'addTxt':'Add Email',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_email_id\':\'0\',\'distributor\':\'yes\'});',
				},
			'addresses':{'label':'Addresses', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No addresses',
				'addTxt':'Add Address',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_address_id\':\'0\',\'distributor\':\'yes\'});',
				},
			'links':{'label':'Websites', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', ''],
				'noData':'No websites',
				'addTxt':'Add Website',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_link_id\':\'0\',\'distributor\':\'yes\'});',
				},
			'images':{'label':'Gallery', 'type':'simplethumbs'},
			'_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Image',
				'addFn':'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'add\':\'yes\'});',
				},
			'short_bio':{'label':'Brief Bio', 'type':'htmlcontent'},
			'full_bio':{'label':'Full Bio', 'type':'htmlcontent'},
			'notes':{'label':'Notes', 'type':'htmlcontent'},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'distributor\':\'yes\'});'},
				}},
		};
		this.distributor.sectionData = function(s) {
			if( s == 'info' || s == 'distributorship' ) { return this.sections[s].list; }
			if( s == 'short_bio' || s == 'full_bio' || s == 'notes' ) { return this.data[s].replace(/\n/g, '<br/>'); }
			return this.data[s];
			};
		this.distributor.listLabel = function(s, i, d) {
			if( s == 'info' || s == 'distributorship' ) { 
				return d.label; 
			}
			return null;
		};
		this.distributor.listValue = function(s, i, d) {
			if( s == 'distributorship' && i == 'type' ) {
				var txt = '';
				if( this.data.distributorship_type != null && this.data.distributorship_type != '' ) {
					switch(this.data.distributorship_type) {
						case '10': txt += 'Regular'; break;
						case '20': txt += 'Complimentary'; break;
						case '30': txt += 'Reciprocal'; break;
					}
				}
				if( this.data.distributorship_length != null && this.data.distributorship_length != '' ) {
					switch(this.data.distributorship_length) {
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
		this.distributor.fieldValue = function(s, i, d) {
			if( i == 'description' || i == 'notes' ) { 
				return this.data[i].replace(/\n/g, '<br/>');
			}
			return this.data[i];
		};
		this.distributor.cellValue = function(s, i, j, d) {
			if( s == 'account' ) {
				if( j == 0 ) { return d.label; }
				if( j == 1 ) { return d.value; }
			}
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
		this.distributor.rowFn = function(s, i, d) {
			if( s == 'phones' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_phone_id\':\'' + d.phone.id + '\',\'distributor\':\'yes\'});';
			}
			if( s == 'emails' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_email_id\':\'' + d.email.id + '\',\'distributor\':\'yes\'});';
			}
			if( s == 'addresses' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_address_id\':\'' + d.address.id + '\',\'distributor\':\'yes\'});';
			}
			if( s == 'links' ) {
				return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'edit_link_id\':\'' + d.link.id + '\',\'distributor\':\'yes\'});';
			}
		};
		this.distributor.thumbSrc = function(s, i, d) {
			if( d.image.image_data != null && d.image.image_data != '' ) {
				return d.image.image_data;
			} else {
				return '/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg';
			}
		};
		this.distributor.thumbTitle = function(s, i, d) {
			if( d.image.name != null ) { return d.image.name; }
			return '';
		};
		this.distributor.thumbID = function(s, i, d) {
			if( d.image.id != null ) { return d.image.id; }
			return 0;
		};
		this.distributor.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.customers.images\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_image_id\':\'' + d.image.id + '\'});';
		};
		this.distributor.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.customers.imageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'webflags':'1',
					'customer_id':M.ciniki_customers_distributors.distributor.customer_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.distributor.addDropImageRefresh = function() {
			if( M.ciniki_customers_distributors.distributor.customer_id > 0 ) {
				var rsp = M.api.getJSONCb('ciniki.customers.get', {'business_id':M.curBusinessID, 
					'customer_id':M.ciniki_customers_distributors.distributor.customer_id, 'images':'yes'}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_customers_distributors.distributor.data.images = rsp.customer.images;
						M.ciniki_customers_distributors.distributor.refreshSection('images');
					});
			}
		};
		this.distributor.addButton('edit', 'Edit', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_distributors.showDistributor();\',\'mc\',{\'customer_id\':M.ciniki_customers_distributors.distributor.customer_id,\'distributor\':\'yes\'});');
		this.distributor.addClose('Back');
	}
	
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create container
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_distributors', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}
	
		// Setup ui labels
		var slabel = 'Distributor';
		var plabel = 'Distributors';
		if( M.curBusiness.customers != null ) {
			if( M.curBusiness.customers.settings['ui-labels-distributor'] != null 
				&& M.curBusiness.customers.settings['ui-labels-distributor'] != ''
				) {
				slabel = M.curBusiness.customers.settings['ui-labels-distributor'];
			}
			if( M.curBusiness.customers.settings['ui-labels-distributors'] != null 
				&& M.curBusiness.customers.settings['ui-labels-distributors'] != ''
				) {
				plabel = M.curBusiness.customers.settings['ui-labels-distributors'];
			}
		}
		this.menu.title = plabel;
		this.list.title = plabel;
		this.distributor.title = slabel;
		this.menu.sections.distributors.addTxt = 'Add ' + slabel;
		this.list.sections.distributors.addTxt = 'Add ' + slabel;

		if( args.customer_id != null && args.customer_id > 0 ) {
			this.showDistributor(cb, args.customer_id);
		} else {
			this.showMenu(cb);
		}
	}

	this.showMenu = function(cb) {
		if( (M.curBusiness.modules['ciniki.customers'].flags&0x20) > 0 ) {
			this.menu.sections.distributors.visible = 'no';
			this.menu.sections.categories.visible = 'yes';
			M.api.getJSONCb('ciniki.customers.distributorCategories', 
				{'business_id':M.curBusinessID}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_customers_distributors.menu;
					p.data = {'categories':rsp.categories};
					p.refresh();
					p.show(cb);
				});	
		} else {
			// Get the list of existing customers
			this.menu.sections.distributors.visible = 'yes';
			this.menu.sections.categories.visible = 'no';
			M.api.getJSONCb('ciniki.customers.distributorList', 
				{'business_id':M.curBusinessID}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_customers_distributors.menu;
					p.data = {'distributors':rsp.distributors};
					if( rsp.distributors != null && rsp.distributors.length > 20 ) {
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
		this.list.sections.distributors.label = this.list.category;
		M.api.getJSONCb('ciniki.customers.distributorList', 
			{'business_id':M.curBusinessID, 'category':encodeURIComponent(this.list.permalink)}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_customers_distributors.list;
				p.data = {'distributors':rsp.distributors};
				p.refresh();
				p.show(cb);
			});	
	};

	this.showDistributor = function(cb, cid) {
		if( cid != null ) { this.distributor.customer_id = cid; }
		var rsp = M.api.getJSONCb('ciniki.customers.get',
			{'business_id':M.curBusinessID, 'customer_id':this.distributor.customer_id, 
				'distributor_categories':'yes', 'phones':'yes', 'emails':'yes', 'addresses':'yes', 
				'links':'yes', 'images':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_customers_distributors.distributor;
				p.data = rsp.customer;
				if( (rsp.customer.webflags&0x04) > 0 ) {
					p.data.webvisible = 'Visible';
				} else {
					p.data.webvisible = 'Hidden';
				}
				
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x20) > 0 ) {
					p.sections.info.list.distributor_categories.visible = 'yes';
					if( rsp.customer.distributor_categories != null && rsp.customer.distributor_categories != '' ) {
						p.data.distributor_categories = rsp.customer.distributor_categories.replace(/::/g, ', ');
					}
				} else {
					p.sections.info.list.distributor_categories.visible = 'no';
				}

				p.data.account = {};
				// Sales Rep
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x2000) > 0 
					&& rsp.customer.salesrep_id_text != null && rsp.customer.salesrep_id_text != ''
					) {
					p.sections.account.visible = 'yes';
					p.data.account.salesrep_id = {'label':'Sales Rep', 'value':rsp.customer.salesrep_id_text};
				}
				// Pricepoint
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x1000) > 0 
					&& M.curBusiness.customers.settings.pricepoints != null
					) {
					p.sections.account.visible = 'yes';
					for(i in M.curBusiness.customers.settings.pricepoints) {
						if( M.curBusiness.customers.settings.pricepoints[i].pricepoint.id == rsp.customer.pricepoint_id ) {
							p.data.account.pricepoint_id = {'label':'Price Point', 
								'value':M.curBusiness.customers.settings.pricepoints[i].pricepoint.name};
							break;
						}
					}
					if( p.data.account.pricepoint_id == null ) {
						p.data.account.pricepoint_id = {'label':'Price Point', 'value':'None'};
					}
				}
				// Tax Number
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x20000) > 0 
					&& rsp.customer.tax_number != null && rsp.customer.tax_number != ''
					) {
					p.sections.account.visible = 'yes';
					p.data.account.tax_number = {'label':'Tax Number', 'value':rsp.customer.tax_number};
				}
				// Tax Location
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x40000) > 0 ) {
					var rates = ((rsp.customer.tax_location_id_rates!=null&&rsp.customer.tax_location_id_rates!='')?' <span class="subdue">'+rsp.customer.tax_location_id_rates+'</span>':'');
					p.sections.account.visible = 'yes';
					p.data.account.tax_location_id = {'label':'Taxes', 'value':(rsp.customer.tax_location_id_text!=null?rsp.customer.tax_location_id_text:'Use Shipping Address') + rates};
				}
				// Reward Level
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x80000) > 0 
					&& rsp.customer.reward_level != null && rsp.customer.reward_level != ''
					) {
					p.sections.account.visible = 'yes';
					p.data.account.reward_level = {'label':'Reward Teir', 'value':rsp.customer.reward_level};
				}
				// Sales Total
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x100000) > 0 
					&& rsp.customer.sales_total != null && rsp.customer.sales_total != ''
					) {
					p.sections.account.visible = 'yes';
					p.data.account.sales_total = {'label':'Sales Total', 'value':rsp.customer.sales_total};
				}
				// Start Date
				if( (M.curBusiness.modules['ciniki.customers'].flags&0x100000) > 0 
					&& rsp.customer.sales_total != null && rsp.customer.sales_total != ''
					) {
					p.sections.account.visible = 'yes';
					p.data.account.sales_total = {'label':'Sales Total', 'value':rsp.customer.sales_total};
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
