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
				'hint':'name, company or email', 'noData':'No members found',
				},
			'members':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No members',
				'addTxt':'Add Member',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMenu();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});',
				},
			'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
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
				return d.customer.display_name;
			}
			return '';
		}
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.ciniki_customers_members.showMember(\'M.ciniki_customers_members.showMenu();\',\'' + d.customer.id + '\');'; 
		};
		this.menu.cellValue = function(s, i, j, d) {
			if( s == 'members' && j == 0 ) {
				if( d.member.company != null && d.member.company != '' ) {
					return '<span class="maintext">' + d.member.first + ' ' + d.member.last + '</span><span class="subtext">' + d.member.company + '</span>';
				} 
				return '<span class="maintext">' + d.member.display_name + '</span>';
			}
			else if( s == 'categories' && j == 0 ) {
				return d.category.name + '<span class="count">' + d.category.num_members + '</span>';
			}
		};
		this.menu.rowFn = function(s, i, d) { 
			if( s == 'members' ) {
				return 'M.ciniki_customers_members.showMember(\'M.ciniki_customers_members.showMenu();\',\'' + d.member.id + '\');'; 
			} else if( s == 'categories' ) {
				return 'M.ciniki_customers_members.showList(\'M.ciniki_customers_members.showMenu();\',\'' + escape(d.category.name) + '\');'; 
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
				'addTxt':'Add Member',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showList();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});',
				},
			};
		this.list.sectionData = function(s) { return this.data[s]; }
		this.list.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.member.company != null && d.member.company != '' ) {
					return '<span class="maintext">' + d.member.first + ' ' + d.member.last + '</span><span class="subtext">' + d.member.company + '</span>';
				} 
				return '<span class="maintext">' + d.member.display_name + '</span>';
			}
		};
		this.list.rowFn = function(s, i, d) { 
			return 'M.ciniki_customers_members.showMember(\'M.ciniki_customers_members.showList();\',\'' + d.member.id + '\');'; 
		};
		this.list.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showList();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});');
		this.list.addClose('Back');

		//
		// The member panel will show the information for a member/sponsor/organizer
		//
		this.member = new M.panel('Member',
			'ciniki_customers_members', 'member',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.members.member');
		this.member.data = {};
		this.member.customer_id = 0;
		this.member.sections = {
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
				}},
			'info':{'label':'', 'aside':'yes', 'list':{
				'name':{'label':'Name'},
				'company':{'label':'Company', 'visible':'no'},
//				'phone_home':{'label':'Home Phone', 'visible':'no'},
//				'phone_work':{'label':'Work Phone', 'visible':'no'},
//				'phone_cell':{'label':'Cell Phone', 'visible':'no'},
//				'phone_fax':{'label':'Fax', 'visible':'no'},
				'webvisible':{'label':'Web Settings'},
				}},
			'membership':{'label':'Membership', 'aside':'yes', 'list':{
				'member_status_text':{'label':'Status'},
				'member_lastpaid':{'label':'Last Paid'},
				'type':{'label':'Type'},
				'member_categories':{'label':'Categories', 'visible':'no'},
				}},
			'phones':{'label':'Phones', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No phones',
				'addTxt':'Add Phone',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_phone_id\':\'0\',\'member\':\'yes\'});',
				},
			'emails':{'label':'Emails', 'type':'simplegrid', 'num_cols':1,
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
			if( s == 'info' || s == 'membership' ) { return this.sections[s].list; }
			if( s == 'short_bio' || s == 'full_bio' || s == 'notes' ) { return this.data[s].replace(/\n/g, '<br/>'); }
			return this.data[s];
			};
		this.member.listLabel = function(s, i, d) {
			if( s == 'info' || s == 'membership' ) { 
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
						case '20': txt += 'Complimentary'; break;
						case '30': txt += 'Reciprocal'; break;
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
			if( i == 'url' && this.data[i] != '' ) {
				return '<a target="_blank" href="http://' + this.data[i] + '">' + this.data[i] + '</a>';
			}
			if( i == 'name' ) {
				return this.data.first + ' ' + this.data.last;
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
					return '<img width="75px" height="75px" src=\'/ciniki-manage-themes/default/img/noimage_75.jpg\' />';
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
		this.member.thumbSrc = function(s, i, d) {
			if( d.image.image_data != null && d.image.image_data != '' ) {
				return d.image.image_data;
			} else {
				return '/ciniki-manage-themes/default/img/noimage_75.jpg';
			}
		};
		this.member.thumbTitle = function(s, i, d) {
			if( d.image.name != null ) { return d.image.name; }
			return '';
		};
		this.member.thumbID = function(s, i, d) {
			if( d.image.id != null ) { return d.image.id; }
			return 0;
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
	
		this.showMenu(cb);
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

	this.showList = function(cb, c) {
		if( c != null ) { this.list.category = unescape(c); }
		// Get the list of existing customers
		this.list.sections.members.label = this.list.category;
		M.api.getJSONCb('ciniki.customers.memberList', 
			{'business_id':M.curBusinessID, 'category':encodeURIComponent(this.list.category)}, function(rsp) {
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

	this.showMember = function(cb, cid) {
		if( cid != null ) { this.member.customer_id = cid; }
		var rsp = M.api.getJSONCb('ciniki.customers.get',
			{'business_id':M.curBusinessID, 'customer_id':this.member.customer_id, 
				'member_categories':'yes', 'phones':'yes', 'emails':'yes', 'addresses':'yes', 
				'links':'yes', 'images':'yes'}, function(rsp) {
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
