//
// This app provides the interface to allow the user to upload
// and automerge into the database customer information
//
function ciniki_customers_automerge() {
	//
	// Panels
	//
	this.files = null;
	this.upload = null;
	this.download = null;
	this.file = null;
	this.review = null;
	this.matches = null;

	this.cb = null;

	this.init = function() {
		//
		// files panel
		//
		this.files = new M.panel('Automerge Files',
			'ciniki_customers_automerge', 'files',
			'mc', 'medium', 'sectioned', 'ciniki.customers.automerge.files');
		this.files.sections = {
			'_':{'label':'', 'type':'simplegrid', 'num_cols':2, 
				headerValues:['File', 'Uploaded'],
				},
			};
		this.files.sectionData = function(s) { return this.data; }
		this.files.data = {};

		this.files.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.excel.name;
				case 1: return d.excel.date_added;
			}
			return '';
		};
		this.files.rowFn = function(s, i, d) { return 'M.ciniki_customers_automerge.showFile(' + i + ');' }
		this.files.noData = function(i) { return 'No excel files found'; }
		this.files.addButton('upload', 'upload', 'M.ciniki_customers_automerge.upload.show();');
		this.files.addClose('Back');

		//
		// The upload form panel
		//
		this.upload = new M.panel('Upload Excel',
			'ciniki_customers_automerge', 'upload',
			'mc', 'medium', 'sectioned', 'ciniki.customers.automerge.upload');
		this.upload.data = null;
        this.upload.sections = { 
//			'notes':{'label':'', 'text':'The excel files must be in the Excel 97-2004 Workbook (.xls) format'},
			'file':{'label':'Upload Excel File', 'fields':{
				'excel':{'label':'', 'type':'image'},
                }}, 
            'pname':{'label':'Name', 'fields':{
				//
				// FIXME: Use the file name as the name, unless they change it
				//
                'name':{'label':'', 'hint':'optional', 'type':'text'},
                }}, 
            };  
		this.upload.fieldValue = function(s, i, d) { return ''; }
		this.upload.addButton('add', 'Add', 'M.ciniki_customers_automerge.uploadFile();');
		this.upload.addLeftButton('cancel', 'Cancel', 'M.ciniki_customers_automerge.showFiles();');

		//
		// file panel
		//
		this.file = new M.panel('Excel File',
			'ciniki_customers_automerge', 'file',
			'mc', 'medium', 'sectioned', 'ciniki.customers.automerge.file');
		this.file.sections = {
			'stats':{'label':'', 'list':{
				'rows':{'label':'rows', 'count':0},
				'conflicts':{'label':'conflicts', 'count':0},
				'merged':{'label':'merged', 'count':0},
//				'deleted':{'label':'deleted', 'count':0},
				}},
			'actions':{'label':'', 'list':{
				'matches':{'label':'Find matches', 'fn':'M.ciniki_customers_automerge.findMatches();'},
				'review_autoadv':{'label':'Review conflicts (Auto advance)', 'fn':'M.ciniki_customers_automerge.reviewConflicts(\'yes\');'},
				'review_noadv':{'label':'Review conflicts', 'fn':'M.ciniki_customers_automerge.reviewConflicts(\'no\');'},
				}},
			};
		this.file.automerge_id = 0;
		this.file.listValue = function(s, i, d) { 
			if( s == 'stats' ) { 
				return d['count'] + ' ' + d['label']; 
			} else {
				return d['label'];
			}
		};
		this.file.listFn = function(s, i, d) { 
			if( d['fn'] != null ) { 
				return d['fn']; 
			} 
			return '';
		};
//		this.file.sectionList = function(s, i, d) { return d['list']; }
		
		
		this.file.noData = function(i) { return 'No excel files found'; }
		this.file.addButton('reset', 'Reset', 'M.ciniki_customers_automerge.resetFile();');
		this.file.addButton('delete', 'Delete', 'M.ciniki_customers_automerge.deleteFile();');
		this.file.addLeftButton('back', 'Back', 'M.ciniki_customers_automerge.showFiles();');

		//
		// find matches panel
		//
		this.matches = new M.panel('Find Matches',
			'ciniki_toolbox_excel', 'matches',
			'mc', 'medium', 'sectioned', 'ciniki.toolbox.excel.matches');
		this.matches.data = {};
		this.matches.sections = {
			'columns':{'label':'', 'hidelabel':'yes', 'fields':{}},
			};
		// this.matches.fieldsID = function(i, d) { return d['col']; }
		this.matches.fieldValue = function(s, i, d) { return 0; }
		this.matches.addButton('find', 'Find', 'M.ciniki_toolbox_excel.find();');
		this.matches.addLeftButton('back', 'Back', 'M.ciniki_toolbox_excel.file.show();');

		//
		// review matches panel
		//
		this.review = new M.panel('Review Matches',
			'ciniki_toolbox_excel', 'review',
			'mc', 'wide', 'sectioned', 'ciniki.toolbox.excel.review');
		this.review.sections = {
			'_':{'label':'', 'type':'simplegrid', 'num_cols':2, 
				},
			};
		this.review.sectionData = function(s) { return this.data; }
		this.review.data = null;
		this.review.matches = null;
		this.review.rows = null;
		this.review.autoAdvance = 'yes';
		this.review.cellClass = function(s, r, c, d) { 
			if( c == 0 ) { return 'label border'; }
			else if( c > 0 && this.rows != null && this.rows[(c-1)] != null && this.rows[(c-1)]['row']['cells'] != null && this.rows[(c-1)]['row']['cells'][1] != null 
				&& this.rows[(c-1)]['row']['cells'][1]['cell']['status'] == '2' ) { return 'border center excel_deleted'; }
			else if( c > 0 && this.rows != null && this.rows[c-1] != null && this.rows[c-1]['row']['cells'] != null && this.rows[c-1]['row']['cells'][1] != null 
				&& this.rows[(c-1)]['row']['cells'][1]['cell']['status'] == '3' ) { return 'border center excel_keep'; }
			else { return 'border center'; }
		}
		this.review.cellValue = function(s, r, c, d) { 
			if( c == 0 ) { 
				return d['cell']['data']; 
			} else if( this.rows != null && this.rows[c-1] != null && this.rows[c-1]['row'] != null && this.rows[c-1]['row']['cells'][r] != null ) { 
				// return 'cell ' + r + ',' + c;
				return this.rows[c-1]['row']['cells'][r]['cell']['data']; 
			} else if( r == this.action_row ) {
				if( c == 0 ) {
					return 'Actions'; 
//				} else if( c == 1 ) {
//					return "<button onclick=\"M.ciniki_toolbox_excel.deleteMatchesOnRows(" + this.rows[0]['row']['id'] + "," + this.rows[(c-1)]['row']['id'] + ");\">Unique</button>";
				} else if( c > 0 ) {
					if( this.autoAdvance == 'yes' ) {
						if( c == 1 ) {
							return "<button onclick=\"M.ciniki_toolbox_excel.deleteRow(" + this.rows[(c-1)]['row']['id'] + ");\">Delete</button>";
						} else {
							return "<button onclick=\"M.ciniki_toolbox_excel.deleteMatchesOnRows(" + this.rows[0]['row']['id'] + "," + this.rows[(c-1)]['row']['id'] + ");\">Unique</button>" + "<button onclick=\"M.ciniki_toolbox_excel.deleteRow(" + this.rows[(c-1)]['row']['id'] + ");\">Delete</button>";
						}
					} else {
						if( this.rows != null && this.rows[c-1] != null && this.rows[c-1]['row']['cells'][0]['cell']['status'] == '2' ) {
							return "<button onclick=\"M.ciniki_toolbox_excel.keepRow(" + this.rows[c-1]['row']['id'] + ");\">Keep</button>";
						} else if( this.rows != null && this.rows[c-1] != null && this.rows[c-1]['row']['cells'][0]['cell']['status'] == '3' ) {
							return "<button onclick=\"M.ciniki_toolbox_excel.deleteRow(" + this.rows[c-1]['row']['id'] + ");\">Delete</button>";
						} else {
							return "<button onclick=\"M.ciniki_toolbox_excel.keepRow(" + this.rows[c-1]['row']['id'] + ");\">Keep</button> <button onclick=\"M.ciniki_toolbox_excel.deleteRow(" + this.rows[c-1]['row']['id'] + ");\">Delete</button>";
						}
					}
				}
			} else { 
				return '';
			}
		}

		this.review.cellUpdateFn = function(s, r, c, d) {
			if( c > 0 && r < this.action_row ) {
				return M.ciniki_toolbox_excel.updateCell;
			}
			return null;
		}
		this.review.addButton('next', 'Next', 'M.ciniki_toolbox_excel.nextMatch(\'fwd\');');
		this.review.addLeftButton('close', 'Close', 'M.ciniki_toolbox_excel.showFile(null);');
	}

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
		var appContainer = M.createContainer(appPrefix, 'ciniki_customers_automerge', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.cb = cb;
		this.showFiles(cb);
	}

	//
	// Get the list of files uploaded, and display a list.
	//
	this.showFiles = function(cb) {
		if( cb != null ) {
			this.files.cb = cb;
		}
		var rsp = M.api.getJSON('ciniki.customers.automergeList', {'business_id':M.curBusinessID});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.files.data = rsp['files'];
		this.files.refresh();
		this.files.show();
	}

	//
	// Upload an excel spreadsheet
	//
	this.uploadFile = function() {
		var file = document.getElementById(M.ciniki_customers_automerge.upload.panelUID + '_excel');
		var name = document.getElementById(M.ciniki_customers_automerge.upload.panelUID + '_name');
		var rsp = M.api.postJSONFile('ciniki.customers.automergeUploadXLS', 
			{'business_id':M.curBusinessID, 'name':name.value}, file.files[0], 
			function(rsp) {
				if( rsp['stat'] != 'ok' ) {
					M.api.err(rsp);
					return false;
				} 
				else if( rsp['id'] > 0 ) {
					M.ciniki_customers_automerge.parseFile(rsp['id'], 1);
				} else {
					M.ciniki_customers_automerge.showFiles();
				}
			});
		name.value = '';
		file.value = '';
	}

	//
	// Parse uploaded excel spreadsheet
	//
	this.parseFile = function(id, start) {
		var rsp = M.api.getJSONCb('ciniki.customers.automergeUploadXLSParse', 
			{'business_id':M.curBusinessID, 'automerge_id':id, 'start':start, 'size':50000},
			function(rsp) {
				if( rsp['stat'] != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				if( rsp['last_row'] > 0 && rsp['last_row'] < rsp['rows']) {
					M.ciniki_customers_automerge.parseFile(rsp['id'], rsp['last_row']+1);
				} else {
					M.ciniki_customers_automerge.finishParse(rsp['id']);
					M.ciniki_customers_automerge.showFiles();
				}
			});
	}

	this.finishParse = function(id) {
		var rsp = M.api.getJSON('ciniki.customers.automergeUploadXLSDone', 
			{'business_id':M.curBusinessID, 'automerge_id':id});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		return true;
	}

	//
	// Open file
	//
	// arguments:
	// i - the index number of the file to open from the M.ciniki_toolbox_excel.files.data[] array.
	//
	this.showFile = function(i) {
		//
		// Get file information include statistics
		//
		if( i != null && this.files.data[i] != null ) {
			this.file.automerge_id = this.files.data[i]['excel']['id'];
		}

		var rsp = M.api.getJSON('ciniki.customers.automergeStats', 
			{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_customers_automerge.file.automerge_id});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		M.ciniki_customers_automerge.file.sections['stats']['list']['rows']['count'] = rsp['stats']['rows'];
		M.ciniki_customers_automerge.file.sections['stats']['list']['conflicts']['count'] = rsp['stats']['conflicts'];
		M.ciniki_customers_automerge.file.sections['stats']['list']['merged']['count'] = rsp['stats']['merged'];
//		M.ciniki_customers_automerge.file.sections['stats']['list']['deleted']['count'] = rsp['stats']['deleted'];

		//
		// Refresh the panel
		//
		M.ciniki_customers_automerge.file.refresh();
		M.ciniki_customers_automerge.file.show();
	}

	//
	// Remove the file from the database
	//
	this.deleteFile = function() {
		var r = confirm("Are you sure you want to delete this file?");
		if( r == true ) {
			var rsp = M.api.getJSON('ciniki.customers.automergeDelete', 
				{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_customers_automerge.file.automerge_id});
			if( rsp['stat'] != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			
			M.ciniki_customers_automerge.showFiles();
		}
	}






	

	//
	// Display the match for this file open by this.file
	// 
	this.showMatch = function(i) {
		var rsp = M.api.getJSON('ciniki.toolbox.excelNextMatch', 
			{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'last_row':i});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.review.data = rsp['matches'];
		this.review.rows = rsp['rows'];
		this.review.refresh();
	}

	//
	// Display the matches panel
	//
	this.findMatches = function() {
		var rsp = M.api.getJSON('ciniki.toolbox.excelGetRows', 
			{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'rows':1});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		if( rsp['rows'] == null || rsp['rows'][0] == null || rsp['rows'][0]['row']['columns'] != null ) {
			alert("No rows found");
			return false;
		}
		var cells = rsp['rows'][0]['row']['cells'];
		this.matches.sections['columns']['fields'] = {};
		for(i in cells) {
			this.matches.sections['columns']['fields'][i] = {'label':cells[i]['cell']['data'], 'col':cells[i]['cell']['col'], 'none':'yes', 'type':'toggle', 'toggles':{'1':'include'}};
		}
		M.ciniki_toolbox_excel.matches.refresh();
		M.ciniki_toolbox_excel.matches.show();
	}

	this.find = function() {
		var fields = M.ciniki_toolbox_excel.matches.sections['columns']['fields'];
		var c = '';
		var columns = '';
		for(i in fields) {
			if( this.matches.formFieldValue(fields[i], i) == 1 ) {
				columns += c + fields[i]['col'];
				c = ',';
			}
		}
		var rsp = M.api.getJSONCb('ciniki.toolbox.excelFindMatches', 
			{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'columns':columns, 'match_blank':'no'},
			function(rsp) { 
				// alert('test');
				alert(' Found ' + rsp['matches'] + ' matches ' + rsp['duplicates'] + ' duplicate matches'); 
				// M.ciniki_toolbox_excel.file.show();
				M.ciniki_toolbox_excel.showFile(null);
				}
			);
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
	}

	this.reviewMatches = function(advance) {
		if( advance == 'yes' ) {
			this.review.autoAdvance = 'yes';
			this.review.last_row = 0;
			if( this.review.leftbuttons['prev'] != null ) {
				delete this.review.leftbuttons['prev'];
				delete this.review.leftbuttons['rewind'];
			}
		} else if( advance == 'rewind' ) {
			// Reset the position back to the beginning, if this is a no auto advance review
			var rsp = M.api.getJSON('ciniki.toolbox.excelPositionSet', 
				{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'row':0});
			if( rsp['stat'] != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			this.review.last_row = 0;
		} else {
			this.review.addLeftButton('rewind', 'Rewind', 'M.ciniki_toolbox_excel.reviewMatches(\'rewind\');');
			this.review.addButton('prev', 'Prev', 'M.ciniki_toolbox_excel.nextMatch(\'rev\');');

			this.review.autoAdvance = 'no';
			// Get the last position
			var rsp = M.api.getJSON('ciniki.toolbox.excelPositionGet', 
				{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id});
			if( rsp['stat'] != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			this.review.last_row = Number(rsp['cur_review_row']) - 1;
		}
		// Get the header row
		var rsp = M.api.getJSON('ciniki.toolbox.excelGetRows', 
			{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'rows':'1'});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.review.data = rsp['rows'][0]['row']['cells'];
		this.review.action_row = this.review.data.length;
		this.review.data[this.review.data.length] = {'cell':{'data':'Actions'}};

		this.nextMatch('fwd');
	}

	this.nextMatch = function(direction) {
		if( this.review.autoAdvance == 'yes' ) {
			var rsp = M.api.getJSON('ciniki.toolbox.excelNextMatch', 
				{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'last_row':M.ciniki_toolbox_excel.review.last_row, 'status':'noreview', 'direction':direction});
		} else {
			var rsp = M.api.getJSON('ciniki.toolbox.excelNextMatch', 
				{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'last_row':M.ciniki_toolbox_excel.review.last_row, 'status':'any', 'direction':direction});
		}
		if( rsp['stat'] != 'ok' && rsp['err']['code'] == '96' ) {
			if( this.review.autoAdvance == 'yes' ) {
				alert('No more matches found');
				this.showFile(null);
			} else {
				
			}
			return false;
		} else if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}

		//
		// Set the number of columns for these matches, and include 1 extra for header
		//
		M.ciniki_toolbox_excel.review.sections._.num_cols = rsp['rows'].length + 1;
		M.ciniki_toolbox_excel.review.last_row = rsp['rows'][0]['row']['id'];

		M.ciniki_toolbox_excel.review.matches = rsp['matches'];
		M.ciniki_toolbox_excel.review.rows = rsp['rows'];

		// Set the last position
		var rsp = M.api.getJSON('ciniki.toolbox.excelPositionSet', 
			{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'row':M.ciniki_toolbox_excel.review.last_row});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}

		M.ciniki_toolbox_excel.review.refresh();
		M.ciniki_toolbox_excel.review.show();
	}

	this.deleteRow = function(row) {
		if( this.review.autoAdvance == 'yes' ) {
			var rsp = M.api.getJSON('ciniki.toolbox.excelDeleteMatchRow', 
				{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'row':row});
		} else {
			var rsp = M.api.getJSON('ciniki.toolbox.excelSetRowStatus', 
				{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'row':row, 'status':'delete'});
		}
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		
		M.ciniki_toolbox_excel.review.last_row--;
		M.ciniki_toolbox_excel.nextMatch('fwd');
	}

	this.keepRow = function(row) {
		var rsp = M.api.getJSON('ciniki.toolbox.excelSetRowStatus', 
			{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'row':row, 'status':'keep'});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		
		M.ciniki_toolbox_excel.review.last_row--;
		M.ciniki_toolbox_excel.nextMatch('fwd');
	}

	this.deleteMatchesOnRows = function(row1, row2) {
		var rsp = M.api.getJSON('ciniki.toolbox.excelDeleteMatchesOnRows', 
			{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 'row1':row1, 'row2':row2});
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		
		M.ciniki_toolbox_excel.review.last_row--;
		M.ciniki_toolbox_excel.nextMatch('fwd');
	}

	this.updateCell = function(s, r, c, d) {
		var rsp = M.api.getJSON('ciniki.toolbox.excelUpdateCell', 
			{
				'business_id':M.curBusinessID, 
				'automerge_id':M.ciniki_toolbox_excel.file.automerge_id, 
				'row':M.ciniki_toolbox_excel.review.rows[(c-1)]['row']['id'], 
				'col':M.ciniki_toolbox_excel.review.rows[(c-1)]['row']['cells'][r]['cell']['col'], 
				'data':encodeURIComponent(d)
			});
			
		if( rsp['stat'] != 'ok' ) {
			M.api.err(rsp);
			return false;
		}

		M.ciniki_toolbox_excel.review.last_row--;
		M.ciniki_toolbox_excel.nextMatch('fwd');
	}

	//
	// Remove the file from the database
	//
	this.resetFile = function() {
		var r = confirm("Are you sure you want to reset this file?");
		if( r == true ) {
			var rsp = M.api.getJSON('ciniki.toolbox.excelReset', 
				{'business_id':M.curBusinessID, 'automerge_id':M.ciniki_toolbox_excel.file.automerge_id});
			if( rsp['stat'] != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			M.ciniki_toolbox_excel.showFile(null);
		}
	}

}
