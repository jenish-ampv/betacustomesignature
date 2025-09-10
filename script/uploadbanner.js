var ui = jQuery.noConflict();
var upload_bannerurl = ui('#form_url').val();
var root_link = ui('#root_link').val();
var image_link = ui('#image_link').val();
ui(function() {

    // preventing page from redirecting
    ui("html").on("dragover", function(e) {
        e.preventDefault();
        e.stopPropagation();
        // ui("h1").text("Drag here");
    });

    ui("html").on("drop", function(e) { e.preventDefault(); e.stopPropagation(); });

    // Drag enter
    ui('.upload-area2').on('dragenter', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drag over
    ui('.upload-area2').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drop
    ui('.upload-area2').on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();

        // ui("h1").text("Upload");

        var file = e.originalEvent.dataTransfer.files;
        var fd = new FormData();

        fd.append('banner', file[0]);

        uploadData2(fd);
    });

    // Open file selector on div click
    ui("#uploadfile3").click(function(){
        ui("#banner").click();
    });

    // file selected
    ui("#banner").change(function(){
        var fd = new FormData();

        var files = ui('#banner')[0].files[0];

        fd.append('banner',files);
		
			// ui("#img_preview3").html('<div class="img_preview_box"><div class="d-flex align-items-center"><strong>Uploading...</strong><div class="spinner-border ms-auto" role="status" aria-hidden="true"></div></div>');

        uploadData2(fd);
    });
	
	// remove image;
	

});

// Sending AJAX request and upload file
function uploadData2(formdata){

    ui.ajax({
        url: upload_bannerurl,
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            addThumbnail2(response);
        }
    });
}

// Added thumbnail
function addThumbnail2(data){
	if(data.error == 0){
		ui("#uploadfile3").empty();
		ui("#img_preview3").empty();
		var len = ui("#uploadfile3 div.thumbnail").length;
	
		var num = Number(len);
		num = num + 1;
	
		var name = data.name;
		var displayname = data.displayname;
		var size = convertSize2(data.size);
		var src = data.src;
		var number = Math.random() * 100;
		// Creating an thumbnail
	   // ui("#uploadfile3").append('<div id="thumbnail_'+num+'" class="thumbnail"></div>');
		ui("#uploadfile3").append('<div class="drag_your_image"> <img src="'+src+'?rand='+number+'" width="50%"></div>');
		ui("#img_preview3").append(' <div class="img_preview_box"><img src="'+image_link+'/images/applied-icon.png" alt=""> '+displayname+' &nbsp; ('+size+') <a href="javascript:void(0);" onclick="removeImage2()"><img class="trash_icon" src="'+image_link+'/images/trash-icon.svg" alt=""></a><input type="hidden" name="signature_banner" value="'+name+'"></div>');
		ui("#nxt2").prop('disabled', false);
        ui('.layout_banner').attr("src",src+'?rand='+number);
		ui("#img_errormsg3").html('');
	}else{
		ui("#img_errormsg3").html('<div class="alert alert-danger" role="alert">'+data.msg+'</div>');
		
	}

}

function removeImage2(){
	ui("#uploadfile3").empty();
	ui("#img_preview3").empty();
	ui("#uploadfile3").append('<div class="drag_your_image"> <img src="'+image_link+'/images/img-icon.svg" alt=""><h4>Drag your image here, or <a href="#">browse</a></h4><p>Supports: PNG, SVG, JPG, JPEG</p></div>');
    ui(".layout_banner").attr("src",image_link+'/images/banner-img1.png');
    $("#banner").val('');
}

// Bytes conversion
function convertSize2(size) {
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (size == 0) return '0 Byte';
    var i = parseInt(Math.floor(Math.log(size) / Math.log(1024)));
    return Math.round(size / Math.pow(1024, i), 2) + ' ' + sizes[i];
}
