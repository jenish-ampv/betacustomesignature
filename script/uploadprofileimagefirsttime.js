var ui = jQuery.noConflict();
var upload_profile_url = ui('#upload_profile_url').val();
var image_link = ui('#image_link').val();
var root_url = upload_url.replace("/account/uploadprofileimg", "");
ui(function() {

    // Profile Upload Code START//

    ui("html").on("dragover", function(e) {
        e.preventDefault();
        e.stopPropagation();
        // ui("h1").text("Drag here");
    });

    ui("html").on("drop", function(e) { e.preventDefault(); e.stopPropagation(); });

    // Drag enter
    ui('.upload-area-profile').on('dragenter', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drag over
    ui('.upload-area-profile').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drop
    ui('.upload-area-profile').on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();

        // ui("h1").text("Upload");

        var file = e.originalEvent.dataTransfer.files;
        if(!file){
            return false;
        }
        var fd = new FormData();

        fd.append('profileImage', file[0]);
        
        uploadProfileData(fd);
    });

    // // Open profile file selector on div click
    // ui("#uploadProfileImage").click(function(){
    //     ui("#profileImage").click();
    // });

    // profile file selected
    ui("#profileImage").change(function(){
        var fd = new FormData();

        var files = ui('#profileImage')[0].files[0];

        fd.append('profileImage',files);
		ui("#profile_img_preview").html('<div class="img_preview_box"><div class="d-flex align-items-center"><strong>Uploading...</strong><div class="spinner-border ms-auto" role="status" aria-hidden="true"></div></div>');

        uploadProfileData(fd);
    });



    // Profile Upload Code END//
	

});

// Sending AJAX request and upload file
function uploadProfileData(formdata){

    ui.ajax({
        url: upload_profile_url,
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            addProfileThumbnail(response);
        }
    });
}

// Added thumbnail
function addProfileThumbnail(data){
	if(data.error == 0){
		ui("#uploadProfileImage").empty();
		ui("#profile_img_preview").empty();
		var len = ui("#uploadProfileImage div.thumbnail").length;
	
		var num = Number(len);
		num = num + 1;
	
		var name = data.name;
		var displayname = data.displayname;
		var size = convertSize(data.size);
		var src = data.src;
		var number = Math.random() * 100;
		// Creating an thumbnail
	   // ui("#uploadProfileImage").append('<div id="thumbnail_'+num+'" class="thumbnail"></div>');
		ui("#uploadProfileImage").append('<div class="edit_profile_img"> <img src="'+src+'?rand='+number+'"></div>');
		ui("#profile_img_preview").append(' <div class="img_preview_box flex items-center gap-2 mt-2"><img src="'+image_link+'/images/applied-icon.png" alt=""> '+displayname+' &nbsp; ('+size+') <a href="javascript:void(0);" onclick="removeProfileImage()"><img class="trash_icon" src="'+image_link+'/images/trash-icon.svg" alt=""></a><input type="hidden" name="profile_image" value="'+name+'"></div>');
		ui("#nxt2").prop('disabled', false);
		ui("#profile_img_errormsg").html('');
	}else{
		ui("#profile_img_errormsg").html('<div class="alert alert-danger" role="alert">'+data.msg+'</div>');
		ui("#profile_img_preview").empty();
		
	}

}

function removeProfileImage(){
	ui("#nxt2").prop('disabled', true);
	ui("#uploadProfileImage").empty();
	ui("#profile_img_preview").empty();
	ui("#uploadProfileImage").append('<div class="edit_profile_img"><img src="'+image_link+'/images/profile-img.png" alt=""><div class="icon"><a href="javascript:void(0);"><img src="'+image_link+'/images/edit-profile-icon.svg" width="24" alt=""></a></div></div>');
}

// Bytes conversion
function convertSize(size) {
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (size == 0) return '0 Byte';
    var i = parseInt(Math.floor(Math.log(size) / Math.log(1024)));
    return Math.round(size / Math.pow(1024, i), 2) + ' ' + sizes[i];
}
