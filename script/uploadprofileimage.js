var ui = jQuery.noConflict();
var upload_url = ui('#upload_url').val();
var image_link = ui('#image_link').val();
var root_url = upload_url.replace("/account/uploadimg", "");
ui(function() {

    // preventing page from redirecting
    ui("html").on("dragover", function(e) {
        e.preventDefault();
        e.stopPropagation();
        // ui("h1").text("Drag here");
    });

    ui("html").on("drop", function(e) { e.preventDefault(); e.stopPropagation(); });

    // Drag enter
    ui('.upload-area').on('dragenter', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drag over
    ui('.upload-area').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drop
    ui('.upload-area').on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();

        // ui("h1").text("Upload");

        var file = e.originalEvent.dataTransfer.files;
        var fd = new FormData();

        fd.append('file', file[0]);

        uploadData(fd);
    });

    // Open file selector on div click
    ui("#uploadfile").click(function(){
        ui("#file").click();
    });

    // file selected
    ui("#file").change(function(){
        var fd = new FormData();
        var files = ui('#file')[0].files[0];
        fd.append('file',files);
		ui("#img_preview").html('<div class="img_preview_box"><div class="d-flex align-items-center"><strong>Uploading...</strong><div class="spinner-border ms-auto" role="status" aria-hidden="true"></div></div>');
        uploadData(fd);
    });
	
	// remove image;
	

});

// Sending AJAX request and upload file
function uploadData(formdata){

    ui.ajax({
        url: upload_url,
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            addThumbnail(response);
        }
    });
}

// Added thumbnail
function addThumbnail(data){
	if(data.error == 0){
		ui("#uploadfile").empty();
		ui("#img_preview").empty();
		var len = ui("#uploadfile div.thumbnail").length;
	
		var num = Number(len);
		num = num + 1;
	
		var name = data.name;
		var displayname = data.displayname;
		var size = convertSize(data.size);
		var src = data.src;
		var number = Math.random() * 100;
		// Creating an thumbnail
	   // ui("#uploadfile").append('<div id="thumbnail_'+num+'" class="thumbnail"></div>');
		ui("#uploadfile").append('<div class="edit_profile_img h-full flex items-center justify-center"> <img src="'+src+'?rand='+number+'"></div>');
		ui("#img_preview").append(' <div class="img_preview_box flex gap-2 items-center mb-3"><img src="'+image_link+'/images/applied-icon.png" alt=""> '+displayname+' &nbsp; ('+size+') <a href="javascript:void(0);" onclick="removeImage()"><img class="trash_icon" src="'+image_link+'/images/trash-icon.svg" alt=""></a><input type="hidden" name="profile_image" value="'+name+'"></div>');
		ui("#nxt2").prop('disabled', false);
		ui("#img_errormsg").html('');
	}else{
		ui("#img_errormsg").html('<div class="alert text-danger mb-2" role="alert">'+data.msg+'</div>');
		ui("#img_preview").empty();
		
	}

}

function removeImage(){
	ui("#nxt2").prop('disabled', true);
	ui("#uploadfile").empty();
	ui("#img_preview").empty();
	ui("#uploadfile").append('<div class="edit_profile_img h-full flex items-center justify-center"><img src="'+image_link+'/images/profile-img.png" alt=""><div class="icon"><a href="javascript:void(0);"><img src="'+image_link+'/images/edit-profile-icon.svg" width="24" alt=""></a></div></div>');
}

// Bytes conversion
function convertSize(size) {
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (size == 0) return '0 Byte';
    var i = parseInt(Math.floor(Math.log(size) / Math.log(1024)));
    return Math.round(size / Math.pow(1024, i), 2) + ' ' + sizes[i];
}
