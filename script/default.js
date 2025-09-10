var root_link = $('#root_link').val();
var image_link = $('#image_link').val();
document.addEventListener("contextmenu", function (e){
	$('#snackbar-error').html('<div class="alert alert-danger"><img src="'+image_link+'/images/error-message-icon.svg" alt=""><strong>Failure! </strong>Right click disable in this site! </div>');
    $('#snackbar-error').show();
	setTimeout(function(){ $('#snackbar-error').hide(); }, 2000);
    e.preventDefault();
}, false);

function validatePasss(obj){

    var TCode = document.getElementById('address').value;
    if(/[^a-zA-Z0-9\-\/]/.test(TCode)){
        alert('Input is not alphanumeric');
        return false;
    }
    return true;
	   
}


function toggler(divId){
	divId = 'toggler_'+divId
	$("#" + divId).slideToggle( "slow" );		
}
/*_______ Password Lenght Chaking _________*/

/*function validatePass(obj){
	if(obj.value.length < 6) 
	{
		alert("Passwords must be at least six (6) characters long.");
		obj.value = '';
		obj.focus();
		return false;
	}	
	 var re = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/;
	 
	 if(!re.test(obj.value)){
		alert('Your password is weak. Try to use uppercase letters, numbers and special characters.');
		obj.value = '';
		 return false;
	 }
} */

function validatePass(obj){
	if(obj.value.length < 8) 
	{
        alert("Password must be at least eight (8) characters long.");
        obj.value = '';
		//obj.focus();
		setTimeout((function() { obj.focus() }), 0);
        return false;
    }

    var passw = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/;
	if(obj.value.match(passw)) { 
        return true;
	}
	else{ 
        alert('Password must be at least eight (8) characters which contain at least one lowercase letter, one uppercase letter, one numeric digit, and one special character!');
        obj.value = '';
		setTimeout((function() { obj.focus() }), 100);
        return false;
    }

}

function validURL(obj) {
  var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
    '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
    '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
    '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
    '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
    '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
 	 if(obj.value.match(pattern)) { 
        return true;
	}else{
        alert('enter a valid url!');
        obj.value = '';
		setTimeout((function() { obj.focus() }), 100);
        return false;
    }
}

function isNumber(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}

/*______ Phone Number Validation ___________*/

function validatephone(obj){
    var strChar;
    var blnResult = true;
    var i;
    var strValidChars = '0123456789.,-()/""&$:;+';

	//  check for valid numeric strings	
	strString = obj.value

	//  test strString consists of valid characters listed above
	for (i = 0; i < strString.length && blnResult == true; i++)
		{
        strChar = strString.charAt(i);
		if (strValidChars.indexOf(strChar) == -1)
			{
            blnResult = false;
        }
    }

	if (blnResult == false)

		{
			var msg;
			msg = " Enter Only Numbers";
			alert(msg);
			obj.value = ""
			flag = "false";
        return false;

		}
	/*if(obj.value.length < 10) 
	{
		alert("Enter Only Numbers");
		obj.value = '';
		obj.focus();
		return false;
	}*/	
}

function confirmpass(obj1,obj2){
	if(obj1.value != obj2.value) 
	{
        alert("Password and Confirm password Does Not Matched ");
        obj2.value = '';
		setTimeout((function() { obj2.focus() }), 100);
        return false;
    }
}


/*___________ Checkemail ___________________*/

function checkemail(obj){
    var emailAddressEntered = obj.value;
	if ((emailAddressEntered.indexOf('@') < 1) || (emailAddressEntered.lastIndexOf('.') < (emailAddressEntered.indexOf('@') + 2)) || (emailAddressEntered.indexOf('\'') > -1) || (emailAddressEntered.indexOf('"') > -1) ) {
		var msg;
		msg = "Enter valid email address!"
		alert(msg);
		obj.value = ""
		setTimeout((function() { obj.focus() }), 100);

        return false;
    }
    var emailLetter = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
	if(!emailLetter.test(obj.value)){
        alert("Enter valid email address!");
        obj.value = "";
		setTimeout((function() { obj.focus() }), 100);
        return false;
    }
}



function ApplyPromoCode(url){
		var code = 	$("#promocode").val();
		if(code == ""){ return false; }
    jQuery.ajax({
        url: url,
			data:'promocode='+$("#promocode").val(),
        type: "POST",
        dataType: "json",
        async: false,
			success:function(data){ 
				if(data["amount"] > 0 ){
                $('#promocode').val(data["code"]);
                $("#haveacode").css("display", "none");
                $("#pcode").css("display", "none");
					if(data["amount_type"] == 0 ){
						var success_text =  'Congratulation! you get $'+data["amount"]+' discount';
					}else{
						var success_text =  'Congratulation! you get '+data["amount"]+'% discount';
					}
                $("#pcode_success").css("display", "block");
					$("#pcode_success").html(success_text+'<a href="javascript:void(0)" style="float:right; position:absolute; font-size:30px; background:#fff; color:#000;"  onclick="RemovePromoCode();">&times;</a>');
				}else{
                $('#promocode').val('');
                alert('enter valid promo code!');
                return false;
            }
        },
			error:function (){ alert('enter valid promo code!'); }
    });
    return false;
}

function RemovePromoCode(){
    $("#pcode_success").css("display", "none");
    $('#promocode').val('');
    $("#haveacode").css("display", "block");

}

function ForgetPassword() {
	
	var fpwemail =  document.getElementById('fpw_email');
	document.getElementById('wrongfp').style.display='none';
	document.getElementById('successfp').style.display='none';
    var re = /\S+@\S+\.\S+/;
	if(fpwemail.value == "" || !re.test(fpwemail.value)){
        fpwemail.focus();
	}else{
			document.getElementById('chekfpw').style.display='block';
        var fpredirect = document.getElementById('redirectfpw').value;
			var url= document.getElementById('ajaxloginlinkfpw').value;	
        jQuery.ajax({
            url: url,
			data:'fpwemail='+$("#fpw_email").val(),
            type: "POST",
            dataType: "text",
            async: false,
			success:function(data){ 
                document.getElementById('chekfpw').style.display = 'none';
			 if(data == 1){
				 document.getElementById('successfp').style.display='block';
				 //document.getElementById('fp_form').style.display='none';
				 //window.location = fpredirect;
				 setTimeout((function() { window.location = fpredirect; }), 1500);
				}else if(data == 3){
					document.getElementById('failfp').style.display='block';
				}else{
					document.getElementById('wrongfp').style.display='block';
                }
				
            },
			error:function (){ }
        });
    }
	
}

function jsConfirm(item_name, action){
	if (action == 'delete'){
		if(item_name !=""){
			return confirm ('Are you sure you want to delete '+item_name+' ?');
		}else{
			return confirm ('Are you sure you want to delete it ?');
        }
    }
}

// $(document).ready(function(){
//   $('.signature_profile').each(function(){
//     dataWidth = $(this).data('width');
//     width = $(this).width();
//     if (dataWidth != null && (width == null || width > dataWidth)) {
//       $(this).attr('width',dataWidth)
//     }
//   })
// });


// For pending import process popup

function processPendingImportedSignature() {
    document.getElementById("overlay").style.display = "block";
    document.getElementById("popupDialog").style.display = "block";

    url = $("#process_pending_imported_signature").data('url');
    $.ajax({
        type: 'POST',
        url: url
	}).done(function(data) {
        $("#popupDialog").html(data);
        jQuery.ready();
	}).fail(function(data) {
		// return false;
        document.getElementById("overlay").style.display = "none";
        document.getElementById("popupDialog").style.display = "none";
    });
}

// For pending import process popup


// For redirect url with ajax to handle error messages ONLY FOR NEW SIGNATURE & CREATE MASTER SIGNATURE buttons

function redirectUrlWithAjax(url) {
    $.ajax({
        url: url
	}).done(function(response) {
        var data = jQuery.parseJSON(response);

		if(data.error){
			$('#snackbar-info').html('<div class="alert alert-warning"><img src="'+image_link+'/images/warning-message-icon.svg" alt=""><strong>Warning! </strong>'+data.msg+'</div>');
            $('#snackbar-info').show();
            $('html, body').animate({
                scrollTop: $("#snackbar-info").offset().top
            }, 500);
      		setTimeout(function(){ $('#snackbar-info').hide(); }, 2000);
        }
	}).fail(function(response) {
        var data = jQuery.parseJSON(response);

		if(data.error){
            $('#snackbar-info').focus();
			$('#snackbar-info').html('<div class="alert alert-warning"><img src="'+image_link+'/images/warning-message-icon.svg" alt=""><strong>Warning! </strong>'+data.msg+'</div>');
            $('#snackbar-info').show();
            $('html, body').animate({
                scrollTop: $("#snackbar-info").offset().top
            }, 500);
      		setTimeout(function(){ $('#snackbar-info').hide(); }, 2000);
        }
		
    });
}

const swalClasses = {
    htmlContainer: 'font-semibold text-base text-gray-500 pt-1 px-0',
    popup: '!p-8 rounded-xl border-none w-full max-w-[350px] dark:bg-black',
    title: 'font-bold text-xl text-gray-900 p-0',
    closeButton: 'btn btn-xl btn-icon btn-light absolute top-2 right-2',
    confirmButton: 'btn btn-sm btn-primary',
    cancelButton: 'btn btn-sm btn-dark',
    denyButton: 'btn btn-sm btn-light',
    icon:'mt-0 w-10 h-10 border-none bg-danger-20 text-danger mb-6',
};

function showConfirmationAlert({
        title = 'Are you sure?',
        confirmButtonText = 'Yes',
        cancelButtonText = 'No',
        text = 'You won\'t be able to revert this!'
    } = {}) {
        Swal.fire({
            title: title,
            text: text,
            iconHtml:'<i class="fa-duotone fa-solid fa-trash-can text-5xl"></i>',
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            customClass: swalClasses,
        });
    }
    
/*status-selector*/
$('.status-selector').click(function () {
    $(this).attr('tabindex', 1).focus();
    $(this).toggleClass('active');
    $(this).find('.status-selector-menu').slideToggle(300);
});
$('.status-selector').focusout(function () {
    $(this).removeClass('active');
    $(this).find('.status-selector-menu').slideUp(300);
});
$('.status-selector .status-selector-menu li').click(function () {
    $(this).parents('.status-selector').find('span').text($(this).text());
    $(this).parents('.status-selector').find('input').attr('value', $(this).attr('id'));
});
/*End status-selector Menu*/
$('#signature-status-selector-menu li').on('click', function() {
    const status = $(this).attr('id');
    console.log(status)
    $('.search-container ').hide();
    $('.search-container.' + status).show();
});


$(document).ready(function () {
    let scrollTrigger = $('.hover_previous_right_bg').outerHeight();
    // Real-time resize tracking using requestAnimationFrame
    let lastHeight = scrollTrigger;
    function updateHeight() {
        const currentHeight = $('.hover_previous_right_bg').outerHeight();
        if (currentHeight !== lastHeight) {
            scrollTrigger = currentHeight;
            lastHeight = currentHeight;
        }
        requestAnimationFrame(updateHeight);
    }
    requestAnimationFrame(updateHeight); // start tracking height
    $(window).scroll(function () {
        if ($(this).scrollTop() > scrollTrigger) {
            $('.nav-tabs-head').addClass("menutop");
            $('#myTabContent').addClass("padding30");
        } else {
            $('.nav-tabs-head').removeClass("menutop");
            $('#myTabContent').removeClass("padding30");
        }
    });

	
    let currentPath = window.location.pathname.split("/").filter(Boolean).pop();
    if (!currentPath) currentPath = "dashboard";

    // reset all li
    $("#mega_menu li").removeClass("active");
    // mark the current path li as active
    $("#mega_menu li." + currentPath).addClass("active");

    var $menu = $("#mega_menu");
    var $links = $menu.find("li > a");
    var $indicator = $("#mainMenuActive");

    // run only if screen width > 1080
    function initMenuIndicator() {
        function moveIndicator($el) {
            if (!$el.length) return;
            var pos = $el.position();
            var width = $el.outerWidth();
            var height = $el.outerHeight();
            $indicator.stop().animate({
                left: pos.left,
                top: pos.top,
                width: width,
                height: height
            }, 300);
        }

        // function to get the current active link
        function getActiveLink() {
            return $menu.find("li.active > a").last(); // if multiple, pick the last one
        }

        // initial position
        var $active = getActiveLink();
        if ($active.length) {
            moveIndicator($active);
        }

        // hover effect
        $links.on("mouseenter", function () {
            moveIndicator($(this));
        });

        // restore to active on leave
        $menu.on("mouseleave", function () {
            var $active = getActiveLink();
            if ($active.length) {
                moveIndicator($active);
            }
        });

        // optional: watch for dynamic active changes
        const observer = new MutationObserver(() => {
            var $active = getActiveLink();
            if ($active.length) {
                moveIndicator($active);
            }
        });

        observer.observe($menu[0], { attributes: true, subtree: true, attributeFilter: ["class"] });
    }

    // // check screen size
    // if (window.innerWidth > 1080) {
    //     initMenuIndicator();
    // }

    // // also handle window resize (optional)
    // $(window).on("resize", function () {
    //     if (window.innerWidth > 1080 && !$menu.data("indicator-init")) {
    //         initMenuIndicator();
    //         $menu.data("indicator-init", true);
    //     }
    // });

});

