var ui = jQuery.noConflict();
var upload_url = ui('#upload_url').val();
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

    // // Open file selector on div click
    // ui("#uploadfile").click(function(){
    //     ui("#file").click();
    // });

    // file selected
    ui("#file").change(function(){
        var fd = new FormData();

        var files = ui('#file')[0].files[0];
        if(!files){
            return false;
        }
        fd.append('file',files);
        ui("#img_preview").html('<div class="img_preview_box"><div class="d-flex align-items-center"><strong>Uploading...</strong><div class="spinner-border ms-auto" role="status" aria-hidden="true"></div></div>');

        uploadData(fd);
    });
	
	// remove image;
	

});

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
    return false;
};

// Sending AJAX request and upload file
function uploadData(formdata){
    var department_id = getUrlParameter('department_id');

    if(department_id){
        ui.ajax({
            url: upload_url+'?department_id='+department_id,
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
    else{
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
		ui("#uploadfile").append('<div class="cursor-pointer p-6 upload-box flex item-center justify-center min-h-[225px] relative drag_your_image"><span class="kt-btn kt-btn-primary kt-btn-outline absolute top-4 right-4"><i class="hgi hgi-stroke hgi-edit-02"></i> Edit Logo</span><img src="'+src+'?rand='+number+'" class="max-w-[150px] object-contain max-h-[150px]"></div>');
		ui("#img_preview").append(` <div class="flex items-center p-5 gap-2 rounded-lg bg-gray-200 my-3"><img src="${image_link}/images/applied-icon.png" alt=""> ${displayname} &nbsp; (${size}) <a class="ml-auto text-lg" href="javascript:void(0);" onclick="removeImage()"><i class="hgi hgi-stroke hgi-delete-02 text-danger"></i></a><input type="hidden" name="signature_image" value="${name}"></div>`);
        ui('input[name=signature_image]').val(name);
		ui("#nxt2").prop('disabled', false);
        ui('.layout_logo').attr("src",src+'?rand='+number);
		ui("#img_errormsg").html('');
	}else{
		ui("#img_errormsg").html('<div class="alert alert-danger" role="alert">'+data.msg+'</div>');
        setTimeout(function(){ ui("#img_preview").empty();ui("#img_errormsg").html('');}, 3000);
		
	}

}

function removeImage(){
	ui("#nxt2").prop('disabled', true);
	ui("#uploadfile").empty();
	ui("#img_preview").empty();
	ui("#uploadfile").append('<div class="cursor-pointer p-6 upload-box drag_your_image"> <span class="kt-btn kt-btn-white kt-btn-icon"><i class="fas fa-upload"></i></span><p class="text-gray-500 my-4">Choose a file or drag &amp; drop it here.<br>PNG, JPG or SVG</p><span class="kt-btn kt-btn-primary kt-btn-outline">Browse File</span></div>');
}

// Bytes conversion
function convertSize(size) {
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (size == 0) return '0 Byte';
    var i = parseInt(Math.floor(Math.log(size) / Math.log(1024)));
    return Math.round(size / Math.pow(1024, i), 2) + ' ' + sizes[i];
}
