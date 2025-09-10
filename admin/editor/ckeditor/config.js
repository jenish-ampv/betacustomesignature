/**
 * @license Copyright (c) 2003-2016, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	
	// Define changes to default configuration here.
	// For complete reference see:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config
	
		

	// The toolbar groups arrangement, optimized for two toolbar rows.
	/*filebrowserBrowseUrl : '/ckfinder/ckfinder.html';
	filebrowserImageBrowseUrl : '/ckfinder/ckfinder.html?type=Images';
	filebrowserFlashBrowseUrl : '/ckfinder/ckfinder.html?type=Flash';
	filebrowserUploadUrl : '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
	filebrowserImageUploadUrl : '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
	filebrowserFlashUploadUrl : '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';
	CKFinder.setupCKEditor( editor, '' );*/
	
   
   
	
	config.allowedContent = true;
	config.extraAllowedContent = 'div(*)';
	config.extraAllowedContent = 'div(col-md-*,container-fluid,row)';
	config.extraAllowedContent = 'dl; dt dd[dir]';
	config.extraAllowedContent = 'span;ul;li;table;td;style;*[id];*(*);*{*}';
};