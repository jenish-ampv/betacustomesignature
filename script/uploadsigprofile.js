var ui = jQuery.noConflict();
var upload_url1 = ui('#form_url').val();
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
    ui('.upload-area1').on('dragenter', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drag over
    ui('.upload-area1').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drop
    ui('.upload-area1').on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();

        // ui("h1").text("Upload");

        var file = e.originalEvent.dataTransfer.files;
        var fd = new FormData();

        fd.append('profile', file[0]);
        uploadData1(fd,true); // true for dragndrop
    });

    // Open file selector on div click
    ui("#uploadfile2").click(function(){
        ui("#profile").click();
    });

    // file selected
    ui("#profile").change(function(){
        var fd = new FormData();

        var files = ui('#profile')[0].files[0];

        fd.append('profile',files);
		
			ui("#img_preview2").html(`
                <div class="flex items-center p-5 gap-2 rounded-lg bg-gray-200 my-3">
                    <span class="text-sm">Uploading...</span>
                    <i class="fas fa-circle-notch fa-spin"></i>
                </div>`);
            ui('.croppie-buttons').addClass('cursor-not-allowed opacity-50');

        uploadData1(fd);
    });
	
	// remove image;
	

});

// Sending AJAX request and upload file
function uploadData1(formdata, isdragndrop = false){

    ui.ajax({
        url: upload_url1,
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            addThumbnail1(response, isdragndrop);
        }
    });
}

// Added thumbnail
function addThumbnail1(data, isdragndrop = false){
	if(data.error == 0){
		ui("#uploadfile2").empty();
		ui("#img_preview2").empty();
		var len = ui("#uploadfile2 div.thumbnail").length;
	
		var num = Number(len);
		num = num + 1;
	
		var name = data.name;
		var displayname = data.displayname;
		var size = convertSize1(data.size);
		var src = data.src;
		var number = Math.random() * 100;
		// Creating an thumbnail
	   // ui("#uploadfile2").append('<div id="thumbnail_'+num+'" class="thumbnail"></div>');
		ui("#uploadfile2").append('<div class="drag_your_image"> <img src="'+src+'?rand='+number+'" width="50%"></div>');
		ui("#img_preview2").append(`
            <div class="flex items-center p-5 gap-2 rounded-lg bg-gray-200 my-3">
              <img src="${image_link}/images/applied-icon.png" alt="">
              <span>${displayname} &nbsp; (${size})</span>
              <a class="ml-auto" href="javascript:void(0);" onclick="removeImage1();removeCropedImage();">
                <i class="hgi hgi-stroke hgi-delete-02 text-danger"></i>
              </a>
              <input type="hidden" name="signature_profile" value="${name}">
            </div>
          `);
          ui('.croppie-buttons').removeClass('cursor-not-allowed opacity-50');
		ui("#nxt2").prop('disabled', false);
        ui('.signature_profile').attr("src",src+'?rand='+number);
		ui("#img_errormsg2").html('');
         
        if(isdragndrop){
            ui("#upload-image").parent().hide();
            ui("#uploadfile2").show();
        } else {
            ui("#upload-image").parent().show();
            ui("#uploadfile2").hide();
        }
        
	}else{
		ui("#img_errormsg2").html('<div class="alert alert-danger" role="alert">'+data.msg+'</div>');
        ui("#img_preview2").empty();
        ui("#upload-image").parent().hide();
        ui("#uploadfile2").show();
	}

}

ui("#signature_profile").bind("focusout",function(){
    ui.ajax({
        url: upload_url1,
        type: 'post',
        data: {moveprofileimage:1,uploadimageurl:ui(this).val()},
        dataType: 'json',
        success: function(response){
            data = response;
            if(data.error == 0){
                var name = data.name;
                var src = data.src;
                var number = Math.random() * 100;
                ui("#img_preview2").html('<img src="'+src+'?rand='+number+'" width="150"><input type="hidden" name="signature_profile" value="'+name+'">');
                ui(".signature_profile").attr("src",src+'?rand='+number);
            }else{
                ui(this).val('');
                ui("#img_errormsg_profile").html('<div class="alert alert-danger" role="alert">'+data.msg+'</div>');
        		ui("#img_preview2").empty();
                ui("#upload-image").parent().hide();
                ui("#uploadfile2").show();
            }
        }
    });
    return false;
});

function removeImage1(){
	ui("#uploadfile2").empty();
	ui("#img_preview2").empty();
	ui("#uploadfile2").append(`
        <div class="drag_your_image border border-dashed border-gray-400 flex items-center justify-center flex-col p-10 rounded-xl">
        <img src="${image_link}/images/img-icon.svg" alt="">
        <p class="text-[#063E76] font-semibold mt-2">Drag your image here, or <a href="#">browse</a></p>
        <p class="text-xs text-gray-400">Supports: PNG, SVG, JPG, JPEG</p>
        </div>
        `);
    ui(".signature_profile").attr("src",image_link+'/images/profile-img1.png');
}

// Bytes conversion
function convertSize1(size) {
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (size == 0) return '0 Byte';
    var i = parseInt(Math.floor(Math.log(size) / Math.log(1024)));
    return Math.round(size / Math.pow(1024, i), 2) + ' ' + sizes[i];
}

function removeCropedImage() {
	ui("#profileCropped").attr("src","");
	ui(".cr-image").attr("src","");
	ui("#upload-image").parent().hide();
	ui("#uploadfile2").show();
  ui('#profile').val('');
}

