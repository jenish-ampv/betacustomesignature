var ui = jQuery.noConflict();
var upload_signature_logourl = ui('#form_url').val();
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
    ui('.upload-area-logo').on('dragenter', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drag over
    ui('.upload-area-logo').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
        // ui("h1").text("Drop");
    });

    // Drop
    ui('.upload-area-logo').on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();

        // ui("h1").text("Upload");

        var file = e.originalEvent.dataTransfer.files;
        var fd = new FormData();

        fd.append('signature_logo', file[0]);

        uploadDatalogo(fd);
    });

    // Open file selector on div click
    ui("#uploadfilelogo").click(function(){
        ui("#signature_logo").click();
    });

    // file selected
    ui("#signature_logo").change(function(){
      var fd = new FormData();
      var files = ui('#signature_logo')[0].files[0];

      fd.append('signature_logo',files);

      ui("#img_preview_logo").html('<div class="img_preview_box"><div class="d-flex align-items-center"><strong>Uploading...</strong><div class="spinner-border ms-auto" role="status" aria-hidden="true"></div></div>');
      setTimeout(function(){ ui("#img_preview_logo").html(""); }, 3000);

      uploadDatalogo(fd);
    });


    // Open file selector on div click for department logo
    ui("#uploadfiledepartmentlogo").click(function(){
      ui("#signature_department_logo").click();
    });

    // Drag enter
    ui('.upload-area-department-logo').on('dragenter', function (e) {
      e.stopPropagation();
      e.preventDefault();
      // ui("h1").text("Drop");
    });

    // Drag over
    ui('.upload-area-department-logo').on('dragover', function (e) {
      e.stopPropagation();
      e.preventDefault();
      // ui("h1").text("Drop");
    });

    // Drop
    ui('.upload-area-department-logo').on('drop', function (e) {
      e.stopPropagation();
      e.preventDefault();

      // ui("h1").text("Upload");

      var file = e.originalEvent.dataTransfer.files;
      var fd = new FormData();

      fd.append('signature_department_logo', file[0]);

      uploadDatalogo(fd);
    });
    // file selected
    ui("#signature_department_logo").change(function(){
      var fd = new FormData();
      var files = ui('#signature_department_logo')[0].files[0];

      fd.append('signature_department_logo',files);

			ui("#img_preview_logo").html('<div class="img_preview_box"><div class="d-flex align-items-center"><strong>Uploading...</strong><div class="spinner-border ms-auto" role="status" aria-hidden="true"></div></div>');
      setTimeout(function(){ ui("#img_preview_logo").html(""); }, 3000);
      uploadDatalogo(fd);
    });



    ui("#reuploadLogo").click(function(){
      url = $(this).data('url');
      console.log('asdasdsad');
      ui.ajax({
          url: url,
          type: 'post',
          dataType: 'json',
          data:{
            'reuploadLogo' : true,
          },
          success: function(response){
            window.location.replace(response.url);
          },
          failure: function (response) {
            console.log('failed')
          }
      });
    });

});

// Sending AJAX request and upload file
function uploadDatalogo(formdata){
  console.log('fdggfg');

    ui.ajax({
        url: upload_signature_logourl,
        type: 'post',
        data: formdata,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response){
            addThumbnaillogo(response);
            $('#signature_department_image').val(response.name);
            $('#signature_image').val(response.name);
        },
        failure: function (response) {
          console.log('failed');
        }
    });
}

// Added thumbnail
function addThumbnaillogo(data){
	if(data.error == 0){
		ui("#uploadfilelogo").empty();
		ui("#img_preview_logo").empty();
		var len = ui("#uploadfilelogo div.thumbnail").length;

		var num = Number(len);
		num = num + 1;

		var name = data.name;
		var displayname = data.displayname;
		var src = data.src;
		var number = Math.random() * 100;
		// Creating an thumbnail
    ui("#uploadfilelogo").append('<div id="thumbnail_'+num+'" class="thumbnail"></div>');
		ui("#uploadfilelogo").append(`
      <div class="drag_your_image border border-dashed border-gray-400 flex items-center justify-center flex-col p-10 rounded-xl">
      <img src="${src+'?rand='+number}" class="max-w-36 max-h-36 object-cover">
      <p class="text-[#063E76] font-semibold mt-2">Drag your image here, or <a href="#">browse</a></p>
      <p class="text-xs text-gray-400">Supports: PNG, SVG, JPG, JPEG</p>
      </div>`);
    // if(ui("#logo_change_done").length == 0){
    //   ui('.change-logo-reason').after('<div id="logo_change_done" style="text-align: center;margin-top: 20px;"><button type="button" class="btn btn-primary" id="logo_change_done_btn" onclick="logoChanged();">Done</button>');
    //   ui("#logo_change_done").parent(".modal-footer").css('display','grid')
    // }

		ui("#nxt2").prop('disabled', false);
    ui('.signature_logo').attr("src",src+'?rand='+number);
    ui('.signature_department_logo').attr("src",src+'?rand='+number);
    ui('.signature_department_logo').attr("data-logo-name",name);
		ui("#img_errormsg_logo").html('');
	}else{
		ui("#img_errormsg_logo").html('<div class="alert alert-danger" role="alert">'+data.msg+'</div>');
    setTimeout(function(){ ui("#img_errormsg_logo").html(""); }, 3000);
	}

}
function logoChanged(){
  var reason = ui("input[name=change_reason]").val();
  var logo_id = ui("input[name=logo_id]").val();
  if(!reason){
    ui("#img_errormsg_logo").html('<div class="alert alert-danger" role="alert">Please enter feedback to change logo</div>');
    setTimeout(function(){ ui("#img_errormsg_logo").html(""); }, 3000);
    
    return false;
  }
  console.log('bdf');

  ui.ajax({
    type: 'POST',
    url: upload_signature_logourl,
    data: {reasonMessage: true, reason: reason, logo_id: logo_id},
    dataType: "text",
    success: function(response){
      // window.location.replace(upload_signature_logourl);
      // console.log('success');
      ui(".btn-close").click();
      var response = JSON.parse(response);
      if(response.success){
        $('#snackbar').html('<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><img src="'+image_link+'/images/success-message-icon.svg" alt=""><strong>Success! </strong>'+response.msg+' </div>');
				$('#snackbar').show();
				setTimeout(function(){ $('#snackbar').hide(); }, 2000);
      }
    },
    failure: function (response) {
      console.log('failed');
    }
  });
};

function departmentLogoChanged(){
  var logo_id = ui("input[name=logo_id]").val();
  const urlParams = new URLSearchParams(window.location.search);
  const department_id = urlParams.get('department_id'); 
  logo_name = ui('.signature_department_logo').data("logo-name");
  console.log('h');

  ui.ajax({
    type: 'POST',
    url: upload_signature_logourl,
    data: {departmentLogoChanged: true, department_id: department_id,logo_name: logo_name},
    dataType: "text",
    success: function(response){
      ui(".btn-close").click();
      var response = JSON.parse(response);
      if(response.success){
        $('#snackbar').html('<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><img src="'+image_link+'/images/success-message-icon.svg" alt=""><strong>Success! </strong>'+response.msg+' </div>');
				$('#snackbar').show();
				setTimeout(function(){ $('#snackbar').hide(); location.reload();}, 2000);
      }
    },
    failure: function (response) {
      console.log('failed');
    }
  });
};