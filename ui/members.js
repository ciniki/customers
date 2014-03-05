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
			'_':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No members',
				'addTxt':'Add Member',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMenu();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});',
				},
			};
		this.menu.sectionData = function(s) { return this.data; }
		this.menu.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.member.company != null && d.member.company != '' ) {
					return '<span class="maintext">' + d.member.first + ' ' + d.member.last + '</span><span class="subtext">' + d.member.company + '</span>';
				} 
				return '<span class="maintext">' + d.member.display_name + '</span>';
			}
//			if( j == 1 ) {
//				return '<span class="maintext">' + d.member.member_status_text + '</span><span class="subtext">' + d.member.member_lastpaid + '</span>';
//			}
		};
		this.menu.rowFn = function(s, i, d) { 
			return 'M.ciniki_customers_members.showMember(\'M.ciniki_customers_members.showMenu();\',\'' + d.member.id + '\');'; 
		};
		this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMenu();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});');
		this.menu.addClose('Back');

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
			'info':{'label':'', 'list':{
				'name':{'label':'Name'},
				'company':{'label':'Company', 'visible':'no'},
//				'phone_home':{'label':'Home Phone', 'visible':'no'},
//				'phone_work':{'label':'Work Phone', 'visible':'no'},
//				'phone_cell':{'label':'Cell Phone', 'visible':'no'},
//				'phone_fax':{'label':'Fax', 'visible':'no'},
				'webvisible':{'label':'Web Settings'},
				}},
			'membership':{'label':'Membership', 'aside':'no', 'list':{
				'member_status_text':{'label':'Status'},
				'member_lastpaid':{'label':'Last Paid'},
				'type':{'label':'Type'},
				}},
			'phones':{'label':'Phones', 'aside':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No phones',
				'addTxt':'Add Phone',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_phone_id\':\'0\',\'member\':\'yes\'});',
				},
			'emails':{'label':'Emails', 'aside':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No emails',
				'addTxt':'Add Email',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_email_id\':\'0\',\'member\':\'yes\'});',
				},
			'addresses':{'label':'Addresses', 'aside':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['label', ''],
				'noData':'No addresses',
				'addTxt':'Add Address',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_customers_members.showMember();\',\'mc\',{\'customer_id\':M.ciniki_customers_members.member.customer_id,\'edit_address_id\':\'0\',\'member\':\'yes\'});',
				},
			'links':{'label':'Websites', 'aside':'no', 'type':'simplegrid', 'num_cols':1,
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
						case '20': txt += 'Complementary'; break;
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
					case 1: return d.phone.phone_number;
				}
			}
			if( s == 'emails' ) {
				return d.email.address;
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
					return '<span class="maintext">' + d.link.name + '</span><span class="subtext">' + d.link.url + '</span>';
				} else {
					return d.link.url;
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

		//
		// The edit panel for member
		//
		this.edit = new M.panel('Edit',
			'ciniki_customers_members', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.customers.members.edit');
		this.edit.data = {};
		this.edit.member_id = 0;
		this.edit.sections = {
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
			}},
			'name':{'label':'', 'fields':{
				'first':{'label':'First Name', 'type':'text'},
				'last':{'label':'Last Name', 'type':'text'},
				'company':{'label':'Business', 'type':'text'},
				'webflags':{'label':'Website', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.webFlags},
				}},
			'contact':{'label':'Contact Info', 'fields':{
				'email':{'label':'Email', 'type':'text'},
				'phone_home':{'label':'Home Phone', 'type':'text'},
				'phone_work':{'label':'Work Phone', 'type':'text'},
				'phone_cell':{'label':'Cell Phone', 'type':'text'},
				'phone_fax':{'label':'Fax Phone', 'type':'text'},
				'url':{'label':'Website', 'type':'text'},
				}},
			'_short_description':{'label':'Brief Description', 'fields':{
				'short_description':{'label':'', 'hidelabel':'yes', 'size':'small', 'type':'textarea'},
				}},
			'_description':{'label':'Bio', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_customers_members.saveMember();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_customers_members.deleteMember();'},
				}},
		};
		this.edit.fieldValue = function(s, i, d) {
			if( this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.customers.memberHistory', 'args':{'business_id':M.curBusinessID, 
				'member_id':M.ciniki_customers_members.edit.member_id, 'field':i}};
		};
		this.edit.addDropImage = function(iid) {
			M.ciniki_customers_members.edit.setFieldValue('primary_image_id', iid);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_customers_members.saveMember();');
		this.edit.addClose('Cancel');
	}
	
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

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
		// Get the list of existing customers
		var rsp = M.api.getJSONCb('ciniki.customers.memberList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_customers_members.menu.data = rsp.members;
				M.ciniki_customers_members.menu.refresh();
				M.ciniki_customers_members.menu.show(cb);
			});	
	};

	//
	// The edit form takes care of editing existing, or add new.
	// It can also be used to add the same person to an customers
	// as an member and sponsor and organizer, etc.
	//
	this.showEdit = function(cb, mid) {
		
		if( mid != null ) {
			this.edit.member_id = mid;
		}
		if( this.edit.member_id > 0 ) {
			var rsp = M.api.getJSONCb('ciniki.customers.memberGet',
				{'business_id':M.curBusinessID, 'member_id':this.edit.member_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_members.edit.data = rsp.member;
					M.ciniki_customers_members.edit.refresh();
					M.ciniki_customers_members.edit.show(cb);
				});
		} else {
			this.edit.data = {};
			if( this.edit.membership == 'yes' ) {
				this.edit.data = {'member_status':'10', 'membership_length':'20', 'membership_type':'10'};
			}
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.showMember = function(cb, cid) {
		if( cid != null ) { this.member.customer_id = cid; }
		var rsp = M.api.getJSONCb('ciniki.customers.get',
			{'business_id':M.curBusinessID, 'customer_id':this.member.customer_id, 
				'phones':'yes', 'emails':'yes', 'addresses':'yes', 'links':'yes', 'images':'yes'}, function(rsp) {
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

	this.saveMember = function() {
		//
		// Depending on if there was a contact loaded, or member
		// loaded for editing, determine what should be sent back
		// to the server
		//
		if( this.edit.customer_id > 0 ) {
			// Update contact
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.customers.memberUpdate', 
					{'business_id':M.curBusinessID, 'customer_id':this.edit.customer_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_customers_members.edit.close();
					});
			} else {
				M.ciniki_customers_members.edit.close();
			}
		} else {
			// Add contact
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.customers.memberAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_customers_members.edit.close();
				});
		}
	};

	this.deleteMember = function() {
		if( confirm('Are you sure you want to delete this member and all their photos?') ) {
			var rsp = M.api.getJSONCb('ciniki.customers.memberDelete', {'business_id':M.curBusinessID, 
				'customer_id':this.edit.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_customers_members.member.close();
					M.ciniki_customers_members.edit.reset();
				});
		}
	};
}
