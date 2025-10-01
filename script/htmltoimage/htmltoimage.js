$(document).ready(function(){
  var root_link = $('#root_link').val();
  var beforewidth = [];
  var beforeheight = [];
  if(!$('.imagetopngClass').length){
    $('.signature_use_box').removeAttr('style');
    $('.overlay-loader').hide();
  }
  
////////////////  Pause Loop of CTA Button END //////////////////////
// var items = ['item1', 'item2', 'item3'];

  var currentIndex = 0;
  var isPaused = false; // Initially not paused
  
  function iterate() {
    totalIterationLimit = $(".imagetopngClass").length;

    // For outlook import signature purpose
    const sigId = sessionStorage.getItem("sig_preview_signature_id");
    if (sigId !== null && sigId !== "undefined") {
      totalIterationLimit = $('div[data-signature-id='+sigId+']').find(".imagetopngClass").length;
    }
    // For outlook import signature purpose

    console.log(currentIndex)
    console.log(totalIterationLimit)
      if (currentIndex < totalIterationLimit) {
          processItem('imagetopngClass',currentIndex);
          currentIndex++;
      } else {
          // console.log("Loop completed");
          pauseLoop(); // Pause the loop when all items are processed
          ctabuttonimageformat(); // For removing unwanted html from cta button's html
          return;
      }
      if (!isPaused) {
          setTimeout(iterate, 1000); // Continue iterating after a delay
      }
  }
  
  // Start the iteration
  iterate();
  
  // Function to pause the loop
  function pauseLoop() {
      isPaused = true;
  }
  
  // Function to resume the loop
  function resumeLoop() {
      isPaused = false;
      iterate();
  }

  
  
  function processItem(elementClass, index) {
    element = $('.'+elementClass)[index];
    if(!$(element).is(':visible')){
      return;
    }
    var imageLink = $(element).find('a').attr('href');
    var imageName = $(element).find('a').attr('class');
    if(imageName == null) {
      imageName = $(element).data('image-name');
    }
    var canvasImageLink = "";
    $($('.imagetopngClass')[index]).css('font-weight',"bold");
    beforewidth[index] = Math.ceil($($('.imagetopngClass')[index]).innerWidth());
    beforeheight[index] = Math.ceil($($('.imagetopngClass')[index]).innerHeight());
    beforeBorderRadius = $($('.imagetopngClass')[index]).css('border-radius');
    if(beforeBorderRadius == '200px'){
      $($('.imagetopngClass')[index]).css('border-radius','15px');
    }

    $('.signature_tbl_main img').each(function() {
  $(this).css('display', 'inline-block');
});


    var styleTag = `
  <style id="custom-image-style">
    img, canvas {
      display: inline-block;
    }
    img {
      max-width: inherit;
      height: auto;
    }
  </style>
`;
$('head').append(styleTag);
    html2canvas($('.imagetopngClass')[index], {
        scale:3,
        scrollX: -window.scrollX,
        scrollY: -window.scrollY,
        // windowWidth: document.documentElement.offsetWidth,
        // windowHeight: document.documentElement.offsetHeight,
        backgroundColor: null,
        // width: beforewidth[index],
        // height: beforeheight[index],
      }).then(function(canvas) {
        // window.scrollTo(0, 0);
        canvas.style.position = 'absolute';  // Right Here!
        canvas.style.left = "0px";
        canvas.style.top = "0px";
        var img = document.createElement('img');
        img.src = canvas.toDataURL("image/png");
        $($('.imagetopngClass')[index]).html(img);
        $($('.imagetopngClass')[index]).removeAttr("style");
        $($('.imagetopngClass')[index]).removeAttr("bgcolor");
        upload_url = root_link+"/includes/classes/htmltoimage.php/";
        imageData = $($(".imagetopngClass")[index]).find('img').attr('src');
        var signatureId = null;
        if($($('.imagetopngClass')[index]).closest('.sin_dashboard_box').length > 0 ){
          signatureId = $($('.imagetopngClass')[index]).closest('.sin_dashboard_box').find("#Share-signature").data('id');
        }
        else if(sessionStorage.getItem("sig_preview_signature_id")){ // For outlook import signature purpose
          signatureId = sessionStorage.getItem("sig_preview_signature_id");
        }
        $.ajax({
            url: upload_url,
            type: 'POST',
            data: {image: imageData, image_name: imageName+".png", root_link: root_link, signature_id: signatureId},
            dataType: 'html',
            success: function(response){
              $('#custom-image-style').remove();
              var data = jQuery.parseJSON(response);
              if(data.error){
                console.log(data.msg);
              }else{
                imageSrcPost = data.image_path;
                const currentDateWithImageStr = imageName+Math.floor(Math.random() * 9999);
                imageSrc = imageSrcPost+"?q="+currentDateWithImageStr;
                if(imageLink == null) {
                  $($('.imagetopngClass')[index]).html('<img width="'+beforewidth[index]+'" height="'+beforeheight[index]+'" style="vertical-align: middle;" src="'+imageSrc+'"/>');
                }else {
                  $($('.imagetopngClass')[index]).html('<a href="'+imageLink+'"><img width="'+beforewidth[index]+'" height="'+beforeheight[index]+'" style="vertical-align: middle;" src="'+imageSrc+'"/></a>');
                }
              }
            }
        });
    });
  }
  
  
  ////////////////  Pause Loop of CTA Button END //////////////////////

  var timer = setInterval(function () {
      if($('.imagetopngClass').length){
          // $('.imagetopngClass').hide();
          clearInterval(timer);
      }
  }, 1000);
  function ctabuttonimageformat() {

    // Formating to overcome gmail issue 'Signature is too long'
    /* tdsMain = $($('.imagetopngClass').first()).parents('table').first().parents('table').first().find('td:even');
    tdsImage = $($('.imagetopngClass').first()).parents('table').first().parents('table').first().find('td:odd');
    $(tdsMain).each(function(index, element) {
      $(tdsMain[index]).attr('style','padding: 10px 5px 0 0;');
    }); */
    $('.signature_use_box').removeAttr('style');
    $('.overlay-loader').hide();
    if(window.location.href.indexOf("receivesignature") > -1) {
       sendSignature();
    }
  }
  function bannertoimg() {
    beforeLink = [];
    if(!$('.bannertoimg').length){
      $('.signature_use_box').removeAttr('style');
      $('.overlay-loader').hide();
      removeClass();
    }
    $('.bannertoimg').each(function(index, element) {
      beforewidth[index] = Math.ceil($($('.bannertoimg')[index]).innerWidth());
      beforeheight[index] = Math.ceil($($('.bannertoimg')[index]).innerHeight());
      beforeLink[index] = $($('.bannertoimg')[index]).find('a').attr('href');
      beforeBorderRadius = $($('.bannertoimg')[index]).find('img').css('border-radius');
      if(beforeBorderRadius == '200px'){
        $($('.bannertoimg')[index]).find('img').css('border-radius','90px');
      }
      html2canvas($('.bannertoimg')[index], {
          scale:3,
          scrollX: -window.scrollX,
          scrollY: -window.scrollY,
          windowWidth: document.documentElement.offsetWidth,
          windowHeight: document.documentElement.offsetHeight,
          width: beforewidth[index],
        }).then(function(canvas) {
          window.scrollTo(0, 0);
          canvas.style.position = 'absolute';  // Right Here!
          canvas.style.left = "0px";
          canvas.style.top = "0px";
          var img = document.createElement('img');
          img.src = canvas.toDataURL("image/png");
          $($('.bannertoimg')[index]).html(img);
          $($('.bannertoimg')[index]).removeAttr("style");
          $($('.bannertoimg')[index]).find("img").attr("width", beforewidth[index]);

          setTimeout(function () {
            upload_url = root_link+"/includes/classes/htmltoimage.php/";
            imageData = $($(".bannertoimg")[index]).find('img').attr('src');
            imageName = "banner"+index;
            $.ajax({
                url: upload_url,
                type: 'POST',
                data: {image: imageData, image_name: imageName+".png", root_link: root_link},
                dataType: 'html',
                success: function(imageSrcPost){
                  const currentDateWithImageStr = new Date().toISOString().replace(/[\-\.\:ZT]/g,"").substr(2,10)+imageName+Math.random();
                  const randomStr = imageName+Math.floor(Math.random() * 9999);
                  imageSrc = root_link+imageSrcPost+"?q="+randomStr;
                  if(beforeLink.length > 0){
                      if(beforeLink[index]){
                        $($('.bannertoimg')[index]).html('<a href="'+beforeLink[index]+'"><img width="'+beforewidth[index]+'" height="'+beforeheight[index]+'" src="'+imageSrc+'"/></a>');
                      }else{
                        $($('.bannertoimg')[index]).html('<img width="'+beforewidth[index]+'" height="'+beforeheight[index]+'" src="'+imageSrc+'"/>');
                      }
                  }else{
                    $($('.bannertoimg')[index]).html('<img width="'+beforewidth[index]+'" height="'+beforeheight[index]+'" src="'+imageSrc+'"/>');
                  }
                  $('.signature_use_box').removeAttr('style');
                  $('.overlay-loader').hide();
                  if(window.location.href.indexOf("receivesignature") > -1) {
                     sendSignature();
                  }
                }
            });
          }, 500);
      });

    });
  }
  function sendSignature() {
    content = $('#signature_source').html()
    $.ajax({
        url: root_link+"/includes/classes/sendsignature.php",
        type: 'POST',
        beforeSend: function(request) {
          request.setRequestHeader("http-equiv", "X-FRAME-Options");
          request.setRequestHeader("content", "SameOrigin");
          request.setRequestHeader("Access-Control-Allow-Origin", "https://outlook.office.com/");
        },
        data: {content: content},
        dataType: 'html',
        success: function(imageSrcPost){
          console.log("sabass")
        }
    });
  }
});
