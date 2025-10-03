jQuery.noConflict();
(function($) {
$(document).ready(function(){
var root_link = $('#root_link').val();

$('.radio-group .radio').click(function(){
$(this).parent().find('.radio').removeClass('selected');
$(this).addClass('selected');
});

$(document).ready(function(){    
	$image_crop = $('#upload-image').croppie({
		enableExif: true,
		viewport: {
			width: 300,
			height: 300,
			type: 'square'
		},
		boundary: {
			width: 400,
			height: 400
		}
	});
	$('#profile').on('change', function () {
		supportFileTypes = ['image/png', 'image/svg', 'image/jpg', 'image/jpeg']

		dataFile = this.files[0]; 
		var reader = new FileReader();
		if(jQuery.inArray(dataFile.type, supportFileTypes) !== -1){
			reader.onload = function (e) {
				$image_crop.croppie('bind', {
					url: e.target.result
				}).then(function(){
					console.log('jQuery bind complete');
	              	$(".profile_animation_section").addClass('cursor-not-allowed opacity-50 pointer-events-none');
				});			
			}
		}
		if(jQuery.inArray(dataFile.type, supportFileTypes) === -1){
			$('#upload-image').croppie('destroy');
			$('input[name=profileCropped').val('');
			$('.cropped_image').hide();
			$('.cropped_image_change').hide();
		}else{
      		$(".profile_animation_section").addClass('cursor-not-allowed opacity-50 pointer-events-none');
      		$(".signature_profile_shape_section").removeClass('cursor-not-allowed opacity-50 pointer-events-none');
			$('.cropped_image').show();
			$('.cropped_image_change').show();
			if(!$('#upload-image').data('croppie')){
				$image_crop = $('#upload-image').croppie({
					enableExif: true,
					viewport: {
						width: 300,
						height: 300,
						type: 'square'
					},
					boundary: {
						width: 400,
						height: 400
					}
				});
			}
			setTimeout(function () {
				reader.readAsDataURL(dataFile);
				if(dataFile.type != "image/gif"){
					setTimeout(function () {
						// $(".cropped_image").click();
				   }, 300);
				}
				ui("#upload-image").parent().show();
				ui("#upload-image").siblings().removeAttr("style");
				ui("#uploadfile2").hide();
			}, 1000);
		}
		
		
	});
	// $('#profileImage').on('change', function () {
	// 	supportFileTypes = ['image/png', 'image/svg', 'image/jpg', 'image/jpeg']

	// 	dataFile = this.files[0]; 
	// 	var reader = new FileReader();
	// 	if(jQuery.inArray(dataFile.type, supportFileTypes) !== -1){
	// 		reader.onload = function (e) {
	// 			$image_crop.croppie('bind', {
	// 				url: e.target.result
	// 			}).then(function(){
	// 				console.log('jQuery bind complete');
	//               	$(".profile_animation_section").addClass('cursor-not-allowed opacity-50 pointer-events-none');
	// 			});			
	// 		}
	// 	}
	// 	if(jQuery.inArray(dataFile.type, supportFileTypes) === -1){
	// 		$('#upload-image').croppie('destroy');
	// 		$('input[name=profileCropped').val('');
	// 		$('.cropped_image').hide();
	// 		$('.cropped_image_change').hide();
	// 	}else{
    //   		$(".profile_animation_section").addClass('cursor-not-allowed opacity-50 pointer-events-none');
    //   		$(".signature_profile_shape_section").removeClass('cursor-not-allowed opacity-50 pointer-events-none');
	// 		$('.cropped_image').show();
	// 		$('.cropped_image_change').show();
	// 		if(!$('#upload-image').data('croppie')){
	// 			$image_crop = $('#upload-image').croppie({
	// 				enableExif: true,
	// 				viewport: {
	// 					width: 300,
	// 					height: 300,
	// 					type: 'square'
	// 				},
	// 				boundary: {
	// 					width: 400,
	// 					height: 400
	// 				}
	// 			});
	// 		}
	// 		setTimeout(function () {
	// 			reader.readAsDataURL(dataFile);
	// 			if(dataFile.type != "image/gif"){
	// 				setTimeout(function () {
	// 					// $(".cropped_image").click();
	// 			   }, 300);
	// 			}
	// 			ui("#upload-image").parent().show();
	// 			ui("#upload-image").siblings().removeAttr("style");
	// 			ui("#uploadfile2").hide();
	// 		}, 1000);
	// 	}
		
		
	// });

	$('.cropped_image').on('click', function (e) {
		e.preventDefault();
		$image_crop.croppie('result', {
			type: 'canvas',
			size: 'viewport'
		}).then(function (response) {
			html = '<img src="' + response + '" />';
			$(".signature_profile").attr("src",response);
			$("#profileCropped").val(response);
			$.ajax({
			    type: "POST",
			    url: $('#form_url').val(),
			    data: {
			        saveCroppedImage: response
			    },
			    dataType: "json",  // expecting JSON response
			    success: function(data) {
		        path = root_link+'/upload-beta/signature/profile/'+data.user_id+'/'+data.img;
		        imageName = data.img;
		        if (path) {
		        	$("#signature_profile_data").remove();
		            $('<div>', {id: 'signature_profile_data'}).appendTo('body');
		            $("#signature_profile_data").attr("data-image-path",path);
		            $("#signature_profile_data").attr("data-image",imageName);
		            $("#signature_profile_data").attr("data-circle-json",data.circleJsonName);
		            $("#signature_profile_data").attr("data-square-json",data.squareJsonName);
	              	$("#signature_profile_data").data("gifcreated", false);
	              	$(".profile_animation_section").removeClass('cursor-not-allowed opacity-50 pointer-events-none');
	      			$(".signature_profile_shape_section").addClass('cursor-not-allowed opacity-50 pointer-events-none');
	            	$('#signature_profileanimation').prop('checked', false).trigger('change');
					removeImage(path);
					removeCropedImage();
					ui("#profileCropped").attr("src","");
					ui(".cr-image").attr("src","");
					ui("#upload-image").parent().hide();
					ui("#uploadfile2").show();
					ui("#img_preview2").append('<input type="hidden" name="signature_profile" value="'+imageName+'">');

					// exportLottieAsGif('circle',data.circleJsonName,imageName);
					// exportLottieAsGif('square',data.squareJsonName,imageName);

		        } else {
		            console.error("Image Not Found");
		        }
			    },
			    error: function(err) {
		        console.error("Error saving image:", err);
			    }
			});

		});
	});	
	$('.cropped_image_change').on('click', function (e) {
		e.preventDefault();
		removeImage1();
		removeCropedImage();
		ui("#profileCropped").attr("src","");
		ui(".cr-image").attr("src","");
		ui("#upload-image").parent().hide();
		ui("#uploadfile2").show();
	});	


	// FirstTimeImage
	$image_crop_first_time = $('#upload-profile-image-first').croppie({
		enableExif: true,
		viewport: {
			width: 300,
			height: 300,
			type: 'square'
		},
		boundary: {
			width: 400,
			height: 400
		}
	});
	$('#profileImage').on('change', function () {
		supportFileTypes = ['image/png', 'image/svg', 'image/jpg', 'image/jpeg']

		dataFile = this.files[0]; 
		var reader = new FileReader();
		if(jQuery.inArray(dataFile.type, supportFileTypes) !== -1){
			reader.onload = function (e) {
				$image_crop_first_time.croppie('bind', {
					url: e.target.result
				}).then(function(){
					console.log('jQuery bind complete');
	              	
				});			
			}
		}
		if(jQuery.inArray(dataFile.type, supportFileTypes) === -1){
			$('#upload-profile-image-first').croppie('destroy');
			$('input[name=profileCropped').val('');
			$('.cropped_image_first_time').hide();
			$('.cropped_image_change_first_time').hide();
		}else{
      		
      		$(".signature_profile_shape_section").removeClass('cursor-not-allowed opacity-50 pointer-events-none');
			$('.cropped_image_first_time').show();
			$('.cropped_image_change_first_time').show();
			if(!$('#upload-profile-image-first').data('croppie')){
				$image_crop_first_time = $('#upload-profile-image-first').croppie({
					enableExif: true,
					viewport: {
						width: 300,
						height: 300,
						type: 'square'
					},
					boundary: {
						width: 400,
						height: 400
					}
				});
			}
			setTimeout(function () {
				reader.readAsDataURL(dataFile);
				if(dataFile.type != "image/gif"){
					setTimeout(function () {
						// $(".cropped_image").click();
				   }, 300);
				}
				ui("#upload-profile-image-first").parent().show();
				ui("#upload-profile-image-first").siblings().removeAttr("style");
				ui("#uploadProfileImage").hide();
			}, 1000);
		}
		
		
	});
	
	$('.cropped_image_first_time').on('click', function (e) {
		e.preventDefault();
		$image_crop_first_time.croppie('result', {
			type: 'canvas',
			size: 'viewport'
		}).then(function (response) {
			html = '<img src="' + response + '" />';
			$(".signature_profile").attr("src",response);
			$("#profileCroppedFirstTime").val(response);
			$.ajax({
			    type: "POST",
			    url: $('#form_url').val(),
			    data: {
			        saveCroppedImageFirstTime: response
			    },
			    dataType: "json",  // expecting JSON response
			    success: function(data) {
		        path = root_link+'/upload-beta/signature/profile/'+data.user_id+'/'+data.img;
		        imageName = data.img;
		        src = data.src;
		        if (path) {
		        	$("#signature_profile_data").remove();
		            $('<div>', {id: 'signature_profile_data'}).appendTo('body');
		            $("#signature_profile_data").attr("data-image-path",path);
		            $("#signature_profile_data").attr("data-image",imageName);
		            $("#signature_profile_data").attr("data-circle-json",data.circleJsonName);
		            $("#signature_profile_data").attr("data-square-json",data.squareJsonName);
	              	$("#signature_profile_data").data("gifcreated", false);
					// removeImage(path);
					// removeCropedImage();
					ui("#profileCroppedFirstTime").attr("src","");
					ui(".cr-image").attr("src","");
					ui("#upload-profile-image-first").parent().hide();
					ui("#uploadProfileImage").show();
					ui("#uploadProfileImage").empty();
					var number = Math.random() * 100;
					ui("#uploadProfileImage").append('<div class="edit_profile_img flex items-center justify-center"> <img class="max-h-[150px] max-w-[150px] object-cover" src="'+src+'?rand='+number+'"></div>');
					// ui("#img_preview2").append('<input type="hidden" name="signature_profile" value="'+imageName+'">');

					// exportLottieAsGif('circle',data.circleJsonName,imageName);
					// exportLottieAsGif('square',data.squareJsonName,imageName);

		        } else {
		            console.error("Image Not Found");
		        }
			    },
			    error: function(err) {
		        console.error("Error saving image:", err);
			    }
			});

		});
	});	
	$('.cropped_image_change').on('click', function (e) {
		e.preventDefault();
		removeImage1();
		removeCropedImage();
		ui("#profileCroppedFirstTime").attr("src","");
		ui(".cr-image").attr("src","");
		ui("#upload-profile-image-first").parent().hide();
		ui("#uploadProfileImage").show();
	});	
});
function removeImage(image_src){
	ui("#uploadfile2").empty();
	ui("#img_preview2").empty();
	ui("#uploadfile2").append(`
		<div class="drag_your_image border border-dashed border-gray-400 flex items-center justify-center flex-col p-10 rounded-xl">
			<img class="max-w-24 max-h-24" src="${image_src}?${new Date().getTime()}" alt="">
			<p class="text-[#063E76] font-semibold mt-2">Drag your image here, or <a href="#">browse</a></p>
			<p class="text-xs text-gray-400">Supports: PNG, SVG, JPG, JPEG</p>
		</div>
		`);
    ui(".signature_profile").attr("src",image_src +'?'+ new Date().getTime());
}
function removeCropedImage() {
	ui("#profileCropped").attr("src","");
	ui(".cr-image").attr("src","");
	ui("#upload-image").parent().hide();
	ui("#uploadfile2").show();
	$('#profile').val('');
}


// function exportLottieAsGif(shape,lottiePath,imageName) {
//   const container = document.getElementById('lottie-'+shape);
//   container.innerHTML = ''; // Clear previous canvas

//   const anim = lottie.loadAnimation({
//     container: container,
//     renderer: 'canvas',
//     loop: true,
//     autoplay: true,
//     path: lottiePath
//   });

//   anim.addEventListener('DOMLoaded', function () {
//     const canvas = container.querySelector('canvas');

//     if (!canvas) {
//       alert('Animation not loaded!');
//       return;
//     }

//     const gif = new GIF({
//       workers: 2,
//       quality: 1,
//       workerScript: '/script/gif.worker.js',
//       transparent: 'rgba(0,0,0,0)',
//       width: 300,
//       height: 300
//     });

//     const durationFrames = anim.getDuration(true);
//     const totalFrames = Math.floor(durationFrames);
//     const fps = anim.frameRate || 30;
//     const targetFps = 12;
//     const frameStep = Math.max(1, Math.floor(fps / targetFps));
//     const delay = 1000 / targetFps;

//     const offscreenCanvas = document.createElement('canvas');
//     offscreenCanvas.width = 300;
//     offscreenCanvas.height = 300;
//     const ctx = offscreenCanvas.getContext('2d');

//     let currentFrame = 0;

//     function captureFrame() {
//       if (currentFrame > totalFrames) {
//         gif.render();
//         return;
//       }

//       anim.goToAndStop(currentFrame, true);

//       setTimeout(() => {
//         ctx.clearRect(0, 0, 300, 300);

//         if (shape === 'circle') {
//           ctx.save();
//           ctx.beginPath();
//           ctx.arc(150, 150, 150, 0, Math.PI * 2);
//           ctx.clip();
//           ctx.drawImage(canvas, 0, 0, 300, 300);
//           ctx.restore();
//         } else {
//           ctx.drawImage(canvas, 0, 0, 300, 300);
//         }

//         gif.addFrame(offscreenCanvas, { copy: true, delay: delay });
//         currentFrame += frameStep;
//         captureFrame();
//       }, 30);
//     }

//     gif.on('finished', function (blob) {
//       const url = URL.createObjectURL(blob);
//       document.getElementById('result-'+shape).src = url;

//       const formData = new FormData();
//       formData.append('file', blob, 'animation.gif');
//       formData.append('saveProfileGif', 'true');
//       const gifFilename = imageName.replace(/\.png$/, '-'+shape+'.gif');
//       formData.append('gif_name', gifFilename);

//       fetch('', {
//         method: 'POST',
//         body: formData
//       })
//         .then(response => {
//           if (response.ok) {
//             console.log('GIF uploaded successfully');
//             $('#signature_profileanimation').prop('checked', true).trigger('change');
//             const $img = $('.signature_profile');
//             if ($img.length) {
//               var src = $img.attr('src');
//               if (src) {
// 				// Extract the filename from the original URL
// 				var filename = src.split('/').pop(); // "1752760882-211.png"

// 				// Remove the ".png" extension and append "-square.gif"
// 				var newFilename = filename.replace('.png', '-square.gif');

// 				// Extract the user ID (in this case: "211") from the filename
// 				var userId = filename.split('-')[1].replace('.png', '');

// 				// Construct the new URL
// 				var newSrc = "https://betaapp.customesignature.com/upload-beta/signature/" + userId + "/" + newFilename;

// 				console.log(newSrc);
//                 // var newSrc = src.replace(/\.\w+$/, '-square.gif');
//                 $img.attr('src', newSrc);
// 				$(".loader-image").hide();

//               }
//             }
//           } else {
//             console.error('Upload failed:', response.statusText);
//           }
//         })
//         .catch(error => {
//           console.error('Error uploading GIF:', error);
//         });

//       setTimeout(() => URL.revokeObjectURL(url), 1000);
//     });

//     captureFrame();
//   });
// }



});
})(jQuery);