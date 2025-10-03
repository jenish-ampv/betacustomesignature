
jQuery.noConflict();
(function ($) {
  $(document).ready(function () {
    var root_link = $('#root_link').val();
    var image_link = $('#image_link').val();

    $('.radio-group .radio').click(function () {
      $(this).parent().find('.radio').removeClass('selected');
      $(this).addClass('selected');

      if ($('#signature_profileanimation').is(':checked')) {
        $('#signature_profile_select_animation_section').removeClass('section_disabled');
      } else {
        $('#signature_profile_select_animation_section').addClass('section_disabled');
      }

    });

    // submit edit form
    // open custome btn
    $(".custom_btn,.custombtntxt").click(function () {
      btnlabel = $(this).attr('data-id');
      if (btnlabel == "custome") {
        $(".signature_customebtn").css('display', 'block');
        $(".custombtntext").css('display', 'block');
      } else {
        $('#signature_custombtntext').val('');
        $(".signature_customebtn").css('display', 'block');
        $(".custombtntext").css('display', 'none');
      }
    });
    // ================START SOCIAL ICON
    // check social icon
    $(document).on('change', '#social_link_box .checkbox', function (e) {
      var checkboxes = $('#social_link_box').find('input:checkbox:checked').length;
      var chboxname = $(this).attr("name");
      var toggleid = chboxname.replace("-icon", "");
      if (!$(this).is(":checked")) {
        $('#collapse-' + toggleid + '-icon').find('input:text').val("");
        setTimeout(function () { $('#' + toggleid).removeClass("show"); }, 500);
      }
      if (checkboxes > 8) {
        this.checked = false;
        $('#' + toggleid).toggle('fast');

        $('#snackbar-info').html('<div class="kt-alert kt-alert-warning items-center mb-2"><i class="fas fa-exclamation-triangle"></i><strong>Warning! </strong>you can\'t select more than eight social icon </div>');
        $('#snackbar-info').show();
        setTimeout(function () { $('#snackbar-info').hide(); $('#' + toggleid).removeClass('show'); $('#' + toggleid).removeAttr('style'); }, 2000);
      }
      // show hide icon on checked
      if ($(this).is(":checked")) {
        var iconclass = 'layout-' + chboxname;
        $("." + iconclass).css('display', 'revert');
      } else {
        var iconclass = 'layout-' + chboxname;
        $("." + iconclass).css('display', 'none');
      }

      //  hide/show social icon border based on selected social icons
      if ($("#social_link_box").find('input[type=checkbox]:checked').length == 0) {
        $(".signature_previous_box").find('.sicon').closest('.layout_socialicon_border').hide();
        $(".signature_previous_box").find('.sicon').closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'none' });
        $(".signature_layot").find('.sicon').closest('.layout_socialicon_border').hide();
        $(".signature_layot").find('.sicon').closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'none' });
      } else {
        $(".signature_previous_box").find('.sicon').closest('.layout_socialicon_border').show();
        $(".signature_previous_box").find('.sicon').closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'revert' });
        $(".signature_layot").find('.sicon').closest('.layout_socialicon_border').show();
        $(".signature_layot").find('.sicon').closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'revert' });
      }
      $('.layout_socialicon_border').each(function () {       //hide/show social icon border based on selected custom icons if its with social icon area
        if ($(this).find('.layout-custombtn').length > 0) {
          if ($(this).find('.layout-custombtn').find('a').length == 0) {
            if ($("#social_link_box").find('input[type=checkbox]:checked').length > 0) {
              $(this).closest('.layout_socialicon_border').show();
              $(this).closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'revert' });
            } else {
              $(this).closest('.layout_socialicon_border').hide();
              $(this).closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'none' });
            }
          } else {
            $(this).closest('.layout_socialicon_border').show();
            $(this).closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'revert' });
          }
        }
      });

    });
    // end social icon

    // change icon Design


    $('input:radio[name="signature_socialdesign"]').change(function () {
      var design = $(this).val();
      $('.social_link_box .iconcheckbox img').map(function () {
        var currentsrc = $(this).attr("src");
        var parts = currentsrc.split("/");
        var replace1 = parts[parts.length - 2];
        var imgname = parts[parts.length - 1];
        var extension = imgname.substr((imgname.lastIndexOf('.') + 1));
        if (imgname != 'right-icon.png') {
          var replace1 = '/' + parts[parts.length - 2] + '/';
          var design1 = '/' + design + '/';
          var newsrc = currentsrc.replace(replace1, design1);
          $(this).attr("src", newsrc);
          $(this).attr("data-src", newsrc);
        }
      });

      $('.sicon img').map(function () {
        var currentsrc = $(this).attr("src");
        var parts = currentsrc.split("/");
        var replace1 = parts[parts.length - 2];
        var imgname = parts[parts.length - 1];
        var extension = imgname.substr((imgname.lastIndexOf('.') + 1));
        if (imgname != 'right-icon.png') {
          var replace1 = '/' + parts[parts.length - 2] + '/';
          var design1 = '/' + design + '/';
          var newsrc = currentsrc.replace(replace1, design1);
          $(this).attr("src", newsrc);
        }
      });
    });

    // chnage animation to static
    $(document).on('change', '#signature_socialanimation', function (e) { // change style bold
      $('.sicon img').map(function () {
        var iconsrc = $(this).attr("src");
        newiconsrc = iconsrc.match(/animation/) ? iconsrc.replace(/animation/, "static") : iconsrc.replace(/static/, "animation");
        newiconsrc = newiconsrc.match(/.gif/) ? newiconsrc.replace(/.gif/, ".png") : newiconsrc.replace(/.png/, ".gif");
        $(this).attr("src", newiconsrc);
      });

    });

    $(document).on('change', '.socialsize', function (e) { // change style bold
      var iconfontsize = $(this).find(":selected").val();
      $('.sicon img').attr('width', iconfontsize);

    });
    // ================END SOCIAL ICON


    // ================START MARKETPLACE BUTTON
    $(document).on('change', '#marketplace_link_box .checkbox', function (e) {
      var checkboxes = $('#marketplace_link_box').find('input:checkbox:checked').length;
      var chboxname = $(this).attr("name");
      var toggleid = chboxname.replace("-btn", "");
      if (!$(this).is(":checked")) {
        $('#' + toggleid).find('input:text').val("");
        setTimeout(function () { $('#' + toggleid).removeClass("show"); }, 500);
      }
      if (checkboxes > 2) {
        this.checked = false;
        $('#' + toggleid).toggle('fast');

        $('#snackbar-info').html('<div class="kt-alert kt-alert-warning items-center mb-2"><i class="fas fa-exclamation-triangle"></i><strong>Warning! </strong>you can\'t select more than three marketplace button </div>');
        $('#snackbar-info').show();
        setTimeout(function () { $('#snackbar-info').hide(); }, 2000);

      }
      // show hide icon on checked
      if ($(this).is(":checked")) {
        var iconclass = 'layout-' + chboxname;
        $("." + iconclass).css('display', 'revert');
      } else {
        var iconclass = 'layout-' + chboxname;
        $("." + iconclass).css('display', 'none');
      }
    });

    // change icon Design
    $('input:radio[name="signature_marketbtndesign"]').change(function () {
      var design = $(this).val();
      $('.marketplace_link_box .iconcheckbox img').map(function () {
        var currentsrc = $(this).attr("src");
        var parts = currentsrc.split("/");
        var replace1 = parts[parts.length - 2];
        var imgname = parts[parts.length - 1];
        var extension = imgname.substr((imgname.lastIndexOf('.') + 1));
        if (imgname != 'right-icon.png') {
          var replace1 = '/' + parts[parts.length - 2] + '/';
          var design1 = '/' + design + '/';
          var newsrc = currentsrc.replace(replace1, design1);
          $(this).attr("src", newsrc);
          $(this).attr("data-src", newsrc);
        }
      });

      $('.mbtn img').map(function () {
        var currentsrc = $(this).attr("src");
        var parts = currentsrc.split("/");
        var replace = parts[parts.length - 2];
        var imgname = parts[parts.length - 1];
        var extension = imgname.substr((imgname.lastIndexOf('.') + 1));
        if (imgname != 'right-icon.png') {
          var replace1 = '/' + parts[parts.length - 2] + '/';
          var design1 = '/' + design + '/';
          var newsrc = currentsrc.replace(replace1, design1);
          $(this).attr("src", newsrc);
        }
      });
    });

    // chnage animation to static
    $(document).on('change', '#signature_marketbtnanimation', function (e) { // change style bold
      $('.mbtn img').map(function () {
        var iconsrc = $(this).attr("src");
        newiconsrc = iconsrc.match(/animation/) ? iconsrc.replace(/animation/, "static") : iconsrc.replace(/static/, "animation");
        newiconsrc = newiconsrc.match(/.gif/) ? newiconsrc.replace(/.gif/, ".png") : newiconsrc.replace(/.png/, ".gif");
        $(this).attr("src", newiconsrc);
      });

    });

    $(document).on('change', '.marketbtnsize', function (e) { // change style bold
      var iconfontsize = $(this).find(":selected").val();
      $('.mbtn img').attr('width', iconfontsize);

    });
    // ================END MARKETPLACE BUTTON

    // change button Design
    $('input:radio[name="signature_btndesign"]').change(function () {
      var design = $(this).val();
      $('#animated_buttons label img').map(function () {
        var currentsrc = $(this).attr("src");
        var parts = currentsrc.split("/");
        var replace1 = parts[parts.length - 2];
        var imgname = parts[parts.length - 1];
        var extension = imgname.substr((imgname.lastIndexOf('.') + 1));
        if (imgname != 'right-icon.png') {
          var replace1 = '/' + parts[parts.length - 2] + '/';
          var design1 = '/' + design + '/';
          var newsrc = currentsrc.replace(replace1, design1);
          $(this).attr("src", newsrc);
          // for data-src as animation btn path
          newiconsrc = newsrc.match(/animation/) ? newsrc.replace(/animation/, "static") : newsrc.replace(/static/, "animation");
          newiconsrc = newiconsrc.match(/.gif/) ? newiconsrc.replace(/.gif/, ".png") : newiconsrc.replace(/.png/, ".gif");
          $(this).attr("data-src", newiconsrc);

        }
      });

      $('.layout-custombtn img').map(function () {
        var currentsrc = $(this).attr("src");
        var parts = currentsrc.split("/");
        var replace1 = parts[parts.length - 2];
        var imgname = parts[parts.length - 1];
        var extension = imgname.substr((imgname.lastIndexOf('.') + 1));
        if (imgname != 'right-icon.png') {
          var replace1 = '/' + parts[parts.length - 2] + '/';
          var design1 = '/' + design + '/';
          var newsrc = currentsrc.replace(replace1, design1);
          var newsrc = currentsrc.replace(replace1, design1);
          $(this).attr("src", newsrc);
        }
      });

      $('.layout-custombtn a.cbtntext').css('background', '#f1f1f1');
      $('.layout-custombtn a.cbtntext').css('color', '#333');
      $('.layout-custombtn a.cbtntext').css('border', 'none');
      $('.layout-custombtn a.cbtntext').css('border-radius', '0px');
      if (design == 1) {
        $('.layout-custombtn a.cbtntext').css('background', '#f1f1f1');
      }
      if (design == 2) {
        $('.layout-custombtn a.cbtntext').css('background', '#ffffff');
        $('.layout-custombtn a.cbtntext').css('border', '1px solid #000');
        $('.layout-custombtn a.cbtntext').css('border-radius', '2px');
      }
      if (design == 3) {
        $('.layout-custombtn a.cbtntext').css('background', '#000000');
        $('.layout-custombtn a.cbtntext').css('color', '#ffffff');
      }
      if (design == 4) {
        $('.layout-custombtn a.cbtntext').css('background', '');
      }

    });

    // chnage animation to static
    $(document).on('change', '#signature_custombtnanimation', function (e) { // change style bold
      $('.layout-custombtn img').map(function () {
        var iconsrc = $(this).attr("src");
        newiconsrc = iconsrc.match(/animation/) ? iconsrc.replace(/animation/, "static") : iconsrc.replace(/static/, "animation");
        newiconsrc = newiconsrc.match(/.gif/) ? newiconsrc.replace(/.gif/, ".png") : newiconsrc.replace(/.png/, ".gif");
        $(this).attr("src", newiconsrc);
      });
      $('#animated_buttons label img').map(function () {
        var iconsrc = $(this).attr('data-src');
        var parts = iconsrc.split("/");
        var replace = parts[parts.length - 2];
        var imgname = parts[parts.length - 1];
        if (imgname != 'right-icon.png') {
          newiconsrc = iconsrc.match(/animation/) ? iconsrc.replace(/animation/, "static") : iconsrc.replace(/static/, "animation");
          newiconsrc = newiconsrc.match(/.gif/) ? newiconsrc.replace(/.gif/, ".png") : newiconsrc.replace(/.png/, ".gif");
          $(this).attr("data-src", newiconsrc);
        }
      });


    });

    $(document).on('change', '.cusbtnsize', function (e) { // change style bold
      var iconfontsize = $(this).find(":selected").val();
      $('.layout-custombtn img').attr('width', iconfontsize);

    });
    // end Icon design

    // remove custom button
    $('input:radio[name="signature_custombtn"]').click(function () {
      var $radio = $(this);
      if ($radio.data('waschecked') == true) {
        $radio.prop('checked', false);
        $(".layout-custombtn").html('');
        $('input:radio[name="signature_custombtn"]').data('waschecked', true); // for check/uncheck other btns
        $radio.data('waschecked', false);
      } else {
        $('input:radio[name="signature_custombtn"]').data('waschecked', false); // for check/uncheck other btns
        $radio.data('waschecked', true);
      }
      //  hide/show social icon border based on selected social icons
      if ($("#social_link_box").find('input[type=checkbox]:checked').length == 0) {
        $(".signature_previous_box").find('.sicon').closest('.layout_socialicon_border').hide();
        $(".signature_previous_box").find('.sicon').closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'none' });
        $(".signature_layot").find('.sicon').closest('.layout_socialicon_border').hide();
        $(".signature_layot").find('.sicon').closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'none' });
      } else {
        $(".signature_previous_box").find('.sicon').closest('.layout_socialicon_border').show();
        $(".signature_previous_box").find('.sicon').closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'revert' });
        $(".signature_layot").find('.sicon').closest('.layout_socialicon_border').show();
        $(".signature_layot").find('.sicon').closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'revert' });
      }

      $('.layout_socialicon_border').each(function () {       //hide/show social icon border based on selected custom icons if its with social icon area
        if ($(this).find('.layout-custombtn').length > 0) {
          if ($(this).find('.layout-custombtn').find('a').length == 0) {
            if ($("#social_link_box").find('input[type=checkbox]:checked').length > 0) {
              $(this).closest('.layout_socialicon_border').show();
              $(this).closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'revert' });
            } else {
              $(this).closest('.layout_socialicon_border').hide();
              $(this).closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'none' });
            }
          } else {
            $(this).closest('.layout_socialicon_border').show();
            $(this).closest('.layout_socialicon_border').siblings('.layout_between_gap').css({ 'display': 'revert' });
          }
        }
      });
    });


    // EDIT SIGNATURE LAYOUT
    $(document).on('click', '.layout_id', function (e) {
      var layput_html = $(this).html();
      $('.signature_previous_box').html(layput_html);
    });

    //$('.inputbox input[type="text"]').keyup(function() { // change textbox value
    //    var  editclass = $(this).attr('data-class');
    //    var editvalue = $(this).val();
    //    $("."+editclass).text(editvalue);
    //});
    $(document).on('keyup', '.inputbox input[type="text"]', function (e) {
      var editclass = $(this).attr('data-class');
      var editvalue = $(this).val();
      $("." + editclass).text(editvalue);
    });

    $(document).on('change', '.style_bold', function (e) { // change style bold
      var editstyleclass = $(this).attr('data-class');
      if ($(this).is(":checked")) {
        $("." + editstyleclass).css('font-weight', 'bold');
      } else {
        $("." + editstyleclass).css('font-weight', 'normal');
      }
    });

    $(document).on('change', '.style_italic', function (e) { // change style italic
      var editstyleclass = $(this).attr('data-class');
      if ($(this).is(":checked")) {
        $("." + editstyleclass).css('font-style', 'italic');
      } else {
        $("." + editstyleclass).css('font-style', 'normal');
      }
    });

    $(document).on('change', '.select_small_box', function (e) { // change font size
      var fontsize = $(this).find(":selected").val();
      var editstyleclass = $(this).attr('data-class');
      $("." + editstyleclass).css('font-size', fontsize);
    });


    $(document).on("change", ".form-control-color", function () { // change style color
      var editcolorclass = $(this).attr('data-class');
      var etitColor = $(this).val();
      if (editcolorclass == 'layout_border' || editcolorclass == 'layout_divider') {
        $("." + editcolorclass).css('border-color', etitColor);
      } else {
        $("." + editcolorclass).css('color', etitColor);
      }
    });

    $(document).on("change", ".selfontfamily", function () { // change font family
      var etitFont = $(this).val();
      $(".layout_maintd").css('font-family', etitFont);
    });

    $(document).on("change", ".sellineheight", function () { // change lineheight
      var etitLineh = $(this).val();
      $(".layout_maintd").css('line-height', etitLineh);
    });

    $(".custom_btn,.custombtntxt").click(function () {  // add custom button
      let btnlabel = $(this).find("input[type=radio]").val(); // get value from input
      let btnimg = "";

      if ($("#signature_custombtnanimation:checked").length > 0) {
        btnimg = $(this).find("img").attr("data-src");
      } else {
        btnimg = $(this).find("img").attr("src");
      }

      let custombtnSize = 80;
      let $select = $("select[name=signature_custombtnsize]");
      // Get value from KTUI select
      if ($select.val()) {
        custombtnSize = $select.val();
      }

      btntext = 'custom';
      if (btnlabel == "custome") {
        $(".layout-custombtn").html('<a class="cbtntext" href="#" style="background: rgb(0, 0, 0); text-align:center; padding:4px 15px; color:rgb(255, 255, 255); text-align:center; text-decoration:none; font-weight:bold; font-size:12px; line-height:15px; display:flex;">' + btntext + '</a>');
      } else {
        $(".layout-custombtn").html('<a href="#"><img alt="" src="' + btnimg + '" width="' + custombtnSize + '" /></a>');
      }
    });

    $('#signature_custombtntext').keyup(function () { // change custom button text
      var cbtntext = $(this).val();
      $(".cbtntext").text(cbtntext);
    });

    ///// Profile on/off js START////

    $('#profile_display').change(function () {
      if (this.checked) {
        $(".signature_profile").css("display", "inline");
        $(".signature_profile").closest('.htmltogifClass').css("display", "revert");
        $(".signature_profile_section").removeClass('section_disabled');
        if ($('#signature_profileanimation').is(':checked')) {
          $('#signature_profile_select_animation_section').removeClass('section_disabled');
        }
      } else {
        $(".signature_profile").css("display", "none");
        $(".signature_profile").closest('.htmltogifClass').css("display", "none");
        $(".signature_profile_section").addClass('section_disabled');
        $('#signature_profile_select_animation_section').addClass('section_disabled');

        // turn off profile animation button when user turn off profile
        if ($(".profile-annimation-gif").is(":visible")) {
          $("#signature_profileanimation").click();
        }
      }
    });

    if ($('#profile_display').is(':checked')) {
      $(".signature_profile_section").removeClass('section_disabled');
      if ($('#signature_profileanimation').is(':checked')) {
        $('#signature_profile_select_animation_section').removeClass('section_disabled');
      }
    } else {
      $(".signature_profile_section").addClass('section_disabled');
      $('#signature_profile_select_animation_section').addClass('section_disabled');
    }

    ///// Profile on/off js END////


    $('#signature_verified').change(function () {
      if (this.checked) {
        $(".layout_verified").css("display", "inline");
      } else {
        $(".layout_verified").css("display", "none");
      }
    });

    // change banner shape
    $('input:radio[name="signature_bannershape"]').change(function () {  // CTA Button Border Change
      var radios = $(this).val();
      $('.layout_banner').css('border-radius', radios);
    });

    // CTA button
    $(document).on("keyup", ".ctabutton", function () {   // CTA Button text Change
      var btntext = $(this).val();
      var btnnumber = $(this).attr('data-number');
      var layoutclass = $(this).attr('data-class');
      var buttonIcon = "";
      if ($("a.layout_ctabtn" + $(this).data('number')).last().find('img').length != 0) {
        buttonIcon = $("a.layout_ctabtn" + $(this).data('number')).last().find('img');
      }

      if (buttonIcon.length != 0) {
        buttonIcon = "<img src=" + $(buttonIcon).attr('src') + " width=" + $(buttonIcon).attr('width') + " style=" + $(buttonIcon).attr('style') + ">";
      }
      $("." + layoutclass).text(btntext);
      if (buttonIcon) {
        $("." + layoutclass).prepend(buttonIcon);
      }
      if (btntext == "") {
        $(".ctaeditpre" + btnnumber).css('display', 'none');
        $("#signature_ctabtndisplay" + btnnumber).val('0');
        $(".layout_ctabtn" + btnnumber).css('display', 'none');
      } else {
        // if($("#signature_ctabtndisplay"+btnnumber+":checked").length > 0){
        //   $(".ctaeditpre"+btnnumber).css('display','inline-flex');
        // }
        $(".ctaeditpre" + btnnumber).css('display', 'inline-flex');
        $("#signature_ctabtndisplay" + btnnumber).val('1');
        $(".layout_ctabtn" + btnnumber).css('display', 'inline-flex');
      }
    });


    // add cta button icon START//
    $(document).on('click', '.addCtaButton', function (e) {
      // // hiding all 3 cta btn sections
      // if (!$('#ctabtn1-tab').is(":visible")) {
      //   $("#ctabtn1").removeClass('active show');
      // }
      // if (!$('#ctabtn2-tab').is(":visible")) {
      //   $("#ctabtn2").removeClass('active show');
      // }
      // if (!$('#ctabtn3-tab').is(":visible")) {
      //   $("#ctabtn3").removeClass('active show');
      // }
      // // hiding all 3 cta btn sections
      // $('#myCtaTab').show();

      // for (let index = 1; index <= 3; index++) {
      //   if (!$("#ctabtn" + index + "-tab").is(":visible")) {
      //     $("#ctabtn" + index + "-tab").closest('li').show();
      //     $("#ctabtn" + index).addClass('active show');
      //     $("#ctabtn" + index + "-tab").click();
      //     break;
      //   }
      // }

      // show container if hidden
      // make sure tab container is visible
      $('[data-kt-tabs="true"]').show();

      // loop through tabs and find the LAST hidden one
      for (let index = 3; index >= 1; index--) {
        let tabToggle = $('[data-kt-tab-toggle="#ctabtn' + index + '"]');
        let tabPane   = $('#ctabtn' + index);

        if (tabToggle.is(':hidden')) {
          // show this tab toggle
          tabToggle.show();

          // deactivate all others
          $('[data-kt-tab-toggle]').removeClass('active selected');
          $('#ctabtn1, #ctabtn2, #ctabtn3').addClass('hidden');

          // activate this one
          tabToggle.addClass('active selected');
          tabPane.removeClass('hidden');

          break;
        }
      }
    });

   $("#marketplace_link_box input[type=checkbox]").each(function () {
      var $chk = $(this);
      // collapse id is based on iconname in checkbox id (before "-btn")
      var iconName = $chk.attr("id").replace("-btn", "");
      var $target = $("#collapse_" + iconName);

      function setOpen(open) {
        if (open) {
          $target.stop(true, true).slideDown(200).removeClass("hidden");
          $chk.attr("aria-expanded", "true");
        } else {
          $target.stop(true, true).slideUp(200, function () {
            $target.addClass("hidden");
          });
          $chk.attr("aria-expanded", "false");
        }
      }

      // On page load, open if pre-checked
      setOpen($chk.is(":checked"));

      // Toggle on change
      $chk.on("change", function () {
        setOpen(this.checked);
      });
    });

    // add cta button icon END//

    // cta button close icon START//
    $(document).on('click', '.ctabtnclose', function (e) {
      var cta_number = $(this).data('cta-number');
      $("#ctabtn" + cta_number + "-tab").closest('[data-kt-tab-toggle]').hide();
      $("#ctabtn" + cta_number).addClass('hidden');
      $('.layout_ctabtn' + cta_number).empty();
      $('.layout_ctabtn' + cta_number).hide();
      $("#signature_ctabtndisplay" + cta_number).val('0');
      $("input[name=signature_ctabtnicon" + cta_number + "]").prop('checked', false);
      $("input[name=signature_ctabtnname" + cta_number + "]").val('');
      $("input[name=signature_ctabtnlink" + cta_number + "]").val('');
      $("input[name=signature_ctabtnbgcolor" + cta_number + "]").val('#000000');

      // if($('#myCtaTab').find('.nav-item:visible').length == '0'){
      //   $('#myCtaTab').hide();
      // }else{
      //   $('#myCtaTab').show();
      // }

      // for (let index = 1; index <= 3; index++) {
      //   if($("#ctabtn"+index+"-tab").is(":visible")){
      //     $("#ctabtn"+index+"-tab").click();
      //     break;
      //   }
      // }
    });

    // cta button close icon END//



    // cta button visibility Start//

    $('.ctabtndisplay').each(function () {
      cta_number = $(this).siblings('.ctabutton').data('number');
      if ($(this).val() == '0') {
        $("#ctabtn" + cta_number + "-tab").closest('li').hide();
      }
    });
    $('#social').addClass('show active');
    if ($('#myCtaTab').find('.nav-item:visible').length == '0') {
      $('#myCtaTab').hide();
    } else {
      $('#myCtaTab').show();
      $($('#myCtaTab').find('.nav-item:visible')[0]).find('.nav-link').click();
    }
    $('#social').removeClass('show active');


    // cta button visibility END//


    // ================START CTA(Custom Buttons) Design BUTTONS
    $('.ctabtndesign').change(function () {
      var design = $(this).val();
      var cta_number = $(this).data('cta-number');
      var cta_color = $("[name=signature_ctabtnbgcolor" + cta_number + "]").val();
      $('.layout_ctabtn' + cta_number).css('border', 'none');
      $('.layout_ctabtn' + cta_number).css('border-radius', '0px');
      if (design == 1) {
        $('.layout_ctabtn' + cta_number).css('background', '#f1f1f1');
        $('.layout_ctabtn' + cta_number).css('color', cta_color);
      }
      if (design == 2) {
        $('.layout_ctabtn' + cta_number).css('background', '#ffffff');
        $('.layout_ctabtn' + cta_number).css('border', '1px solid #000');
        $('.layout_ctabtn' + cta_number).css('border-radius', '2px');
        $('.layout_ctabtn' + cta_number).css('color', cta_color);
      }
      if (design == 3) {
        $('.layout_ctabtn' + cta_number).css('background', cta_color);
        $('.layout_ctabtn' + cta_number).css('color', '#ffffff');
      }
      if (design == 4) {
        $('.layout_ctabtn' + cta_number).css('background', '');
        $('.layout_ctabtn' + cta_number).css('color', cta_color);
      }

    });

    // ================END CTA(Custom Buttons) Design BUTTONS




    $(document).on('change', '.ctabtnsize', function (e) {  // CTA Button Size Change
      var btnsize = $(this).val();
      var layoutclass = $(this).attr('data-class');
      $("." + layoutclass).css('font-size', btnsize);

    });

    $(document).on('click', '.ctabtnshape', function (e) {  // CTA Button Shape Change
      var btnshape = $(this).val();
      var layoutclass = $(this).attr('data-class');
      $("." + layoutclass).css('border-radius', btnshape);

    });

    $(document).on('change', '.ctabtncolor', function (e) {  // CTA Button Color Change
      var btncolor = $(this).val();
      var cta_number = $(this).data('cta-number');
      var layoutclass = $(this).attr('data-class');
      cta_design = $('[name=signature_ctabtndesign' + cta_number + ']').val();
      $("." + layoutclass).css('background-color', btncolor);

    });

    $('.ctabtndis').change(function () {
      var layoutclass = $(this).attr('data-class');
      if (this.checked) {
        $("." + layoutclass).css('display', 'inline-flex');
      } else {
        $("." + layoutclass).css('display', 'none');
      }
    });

    $(document).on('change', '.ctabtnicon1,.ctabtnicon2,.ctabtnicon3', function (e) { // CTA Button Icon Change
      var layoutclass = $(this).attr('data-class');
      var text = $("." + layoutclass).first().text();
      btnnum = layoutclass.substr(-1);
      if ($(this).is(':checked')) {
        $(".ctabtnicon" + btnnum).prop('checked', false);
        $(this).prop('checked', true);
        var btnicon = $(this).val();
        var iconimg = image_link + '/images/buttonicon/' + btnicon;
        $("." + layoutclass).html('');
        $("." + layoutclass).html('<img id="layout_ctaicon" src="' + iconimg + '" width="18" style="margin-right:10px;">' + text);
      } else {
        $("." + layoutclass).html(text);
      }

    });



    // divider and border display

    $('#signature_border').change(function () {
      width = (!isEmpty($("#borderwidth").find("[role='slider']").attr("aria-valuetext"))) ? Math.trunc($("#borderwidth").find("[role='slider']").attr("aria-valuetext")) : "1";
      if (this.checked) {
        $('.layout_border').css('border-width', width + 'px');
        $('#signature_borderwidth').val(width);
      } else {
        $('.layout_border').css('border-width', 0);
        $('#signature_borderwidth').val(0);
      }
    });

    $('#signature_divider').change(function () {
      if (this.checked) {
        width = (!isEmpty($("#dividerwidth").find("[role='slider']").attr("aria-valuetext"))) ? Math.trunc($("#dividerwidth").find("[role='slider']").attr("aria-valuetext")) : "1";
        $('.layout_divider').css('border-left-width', width + 'px');
        $('.layout_divider_bottom').css('border-bottom-width', '1px');
        $('.layout_divider_right').css('border-right-width', '1px');
        $('#signature_dividerwidth').val(width);
      } else {
        $('.layout_divider').css('border-left-width', 0);
        $('.layout_divider_bottom').css('border-bottom-width', 0);
        $('.layout_divider_right').css('border-right-width', 0);
        $('#signature_dividerwidth').val(0);
      }
    });
    function isEmpty(value) {
      return (value == 0 || value == null || (typeof value === "string" && value.trim().length === 0));
    }



    // end CTA button

    // add custom field

    var field_count = 1; var email_count = 1; var phone_count = 1; var text_count = 1; var fax_count = 1; var website_count = 1; var address_count = 1; var hyperlink_count = 1; var disclaimer_count = 1;
    if ($('#fieldcount').val() != 0) {
      var field_count = $('#fieldcount').val();
      var email_count = $('#fieldcount').attr('data-emailcount');
      var phone_count = $('#fieldcount').attr('data-phonecount');
      var text_count = $('#fieldcount').attr('data-textcount');
      var fax_count = $('#fieldcount').attr('data-faxcount');
      var website_count = $('#fieldcount').attr('data-websitecount');
      var address_count = $('#fieldcount').attr('data-addresscount');
      var hyperlink_count = $('#fieldcount').attr('data-hyperlinkcount');
      var disclaimer_count = $('#fieldcount').attr('data-disclaimercount');
    }
    $('.addcustomfield').click(function (e) {
      e.preventDefault();
      var fieldtype = $(this).attr('data-id');
      if (fieldtype == "") { return false; }
      var fieldlabel = fieldtype.substr(0, 1).toUpperCase() + fieldtype.substr(1);
      if (field_count <= 10) {

        switch (fieldtype) {
          case 'email':
            fieldid = 'e' + email_count;
            layout_class = 'layout_email' + email_count;
            layout_labelclass = 'layout_email_label' + email_count;
            fieldno = email_count;
            email_count++; break;
          case 'phone':
            fieldid = 'p' + phone_count;
            layout_class = 'layout_phone' + phone_count;
            fieldno = phone_count;
            layout_labelclass = 'layout_phone_label' + phone_count;
            phone_count++; break;
          case 'text':
            fieldid = 't' + text_count;
            layout_class = 'layout_text' + text_count;
            layout_labelclass = 'layout_text_label' + text_count;
            fieldno = text_count;
            text_count++; break;
          case 'fax':
            fieldid = 'f' + fax_count;
            layout_class = 'layout_fax' + fax_count;
            layout_labelclass = 'layout_fax_label' + fax_count;
            fieldno = fax_count;
            fax_count++; break;
          case 'website':
            fieldid = 'w' + website_count;
            layout_class = 'layout_website' + website_count;
            layout_labelclass = 'layout_website_label' + website_count;
            fieldno = website_count;
            website_count++; break;
          case 'address':
            fieldid = 'a' + address_count;
            layout_class = 'layout_address' + address_count;
            layout_labelclass = 'layout_address_label' + address_count;
            fieldno = address_count;
            address_count++; break;
          case 'hyperlink':
            fieldid = 'h' + hyperlink_count;
            layout_class = 'layout_hyperlink' + hyperlink_count;
            layout_labelclass = 'layout_hyperlink_label' + hyperlink_count;
            fieldno = hyperlink_count;
            hyperlink_count++; break;
          case 'disclaimer':
            fieldid = 'd' + disclaimer_count;
            layout_class = 'layout_disclaimer' + disclaimer_count;
            layout_labelclass = 'layout_disclaimer_label' + disclaimer_count;
            fieldno = disclaimer_count;
            disclaimer_count++; break;
          default:
            break;
        }

        //$("#divGeneratedElements").append('<div class="inputbox"><div class="row"><div class="col-md-8"><div class="form-floating"><input type="text" class="form-control"  name="custom_field[]" value="" data-class=""><label for="">'+fieldlabel+'</label></div><input type="hidden" name="custom_fieldtype[]" value="'+fieldtype+'"></div></div></div>');
        if (fieldtype == 'text') { appendid = 'divGeneratedElementsText'; } else { appendid = 'divGeneratedElements'; }
        $("#" + appendid).append(`
      <div class="flex items-center gap-4 inputbox mt-5">
        <div class="flex items-center gap-2">
          <div class="w-20 flex-none floting-input">
            <input type="text" class="kt-input" name="field_label[]" id="" value="" data-class="${layout_labelclass}">
            <label for="">Title</label>
          </div>
          <div class="flex-1 min-w-28 floting-input">
            <input type="text" class="kt-input" id="" name="custom_field[]" value="" data-class="${layout_class}">
            <label for="">${fieldlabel}</label>
          </div>
        </div>

        <div class="flex gap-2 items-center">
          <label class="cursor-pointer">
            <input type="checkbox" name="field_fontweight[${field_count}]" id="bold-icon-${fieldid}" class="peer hidden style_bold" value="1" data-class="${layout_class}">
            <i class="fas fa-bold text-gray-400 peer-checked:text-gray-950"></i>
          </label>
          <label class="cursor-pointer">
            <input type="checkbox" name="field_fontstyle[${field_count}]" id="italic-icon-${fieldid}" class="peer hidden style_italic" value="1" data-class="${layout_class}">
            <i class="fas fa-italic text-gray-400 peer-checked:text-gray-950"></i>
          </label>
          <div class="color_picker">
            <input type="color" name="field_color[]" class="w-4 h-5 form-control-color" id="exampleColorInput" value="#000000" title="Choose your color" data-class="${layout_class}">
          </div>
          <div class="w-20">
            <select class="kt-select kt-select-sm !leading-normal select_small_box" data-class="${layout_class}" name="field_fontsize[]">
              <option value="10px">Small</option>
              <option value="12px" selected>Normal</option>
              <option value="14px">Large</option>
              <option value="16px">Huge</option>
            </select>
          </div>
          <a href="javascript:void(0);" class="text-danger remove_cusfield" data-id="${fieldtype}" data-number="${fieldno}">
            <i class="hgi hgi-stroke hgi-delete-02"></i>
          </a>
        </div>
        <input type="hidden" name="custom_fieldtype[]" value="${fieldtype}">
      </div>`);
        field_count++;
      } else {
        $('#snackbar-info').html('<div class="kt-alert kt-alert-warning items-center mb-2"><i class="fas fa-exclamation-triangle"></i><strong>Warning! </strong>you can\'t add more than 10 field </div>');
        $('#snackbar-info').show();
        setTimeout(function () { $('#snackbar-info').hide(); }, 2000);
      }
      //$(this).prop("selectedIndex", 0);
      $(".addnew_field").dropdown('toggle');
      return false;

    });

    $(document).on('click', '.remove_cusfield', function (e) {
      var fielddel = $(this).attr('data-id');
      var fielddeln = $(this).attr('data-number');
      if (fielddel == 'email') { email_count--; } if (fielddel == 'phone') { phone_count--; } if (fielddel == 'text') { text_count--; } if (fielddel == 'website') { website_count--; } if (fielddel == 'address') { address_count--; } if (fielddel == 'hyperlink') { hyperlink_count--; } if (fielddel == 'disclaimer') { disclaimer_count--; }
      $(this).parents('.inputbox').remove();
      field_count--;
      if (fielddeln != 0) {
        $('.layout_' + fielddel + fielddeln).text('');
        $('.layout_' + fielddel + '_label' + fielddeln).text('');
      }
    });


    // end custom field


    //////// GIF ANIMATION TASK JS START ////////

    $('#signature_profileanimation').change(function () {
      // if(this.checked) {
      //   if($("#signature_profileanimation_gif1").prop('checked') == false && $("#signature_profileanimation_gif2").prop('checked') == false){
      //     $("#signature_profileanimation_gif1").attr("checked",true);
      //   }
      //   $(".profile-annimation-gif").css({"z-index": "1"});
      //   $(".profile-annimation-gif").css({"display": "block"});
      //   $(".signature_profile").parent().css({"max-height": "0"});
      // }else{
      //   $(".profile-annimation-gif").css({"z-index": "-1"});
      //   $(".profile-annimation-gif").css({"display": "none"});
      //   $(".signature_profile").parent().css({"max-height": "inherit"});
      // }


      if (this.checked) {
        $('#signature_profile_select_animation_section').removeClass('section_disabled');
        if ($("#signature_profileanimation_gif1").prop('checked') == false && $("#signature_profileanimation_gif2").prop('checked') == false) {
          $("#signature_profileanimation_gif1").attr("checked", true);
        }
        gifcreated = $("#signature_profile_data").data("gifcreated");
        if (gifcreated) {
          animationGifShape = $('.signature_profileanimation_gif:checked').data('shape');
          imagePath = $("#signature_profile_data").data(animationGifShape + "-image-path");
          if (!$.trim(imagePath)) {
            imageOldPath = $('.signature_profile').attr("src");
            var imageId = $('input[name="signature_profile"]').val();
            newImageAnimatedId = imageId.replace(".png", "-" + animationGifShape + ".gif");
            var baseUrl = imageOldPath.substring(0, imageOldPath.lastIndexOf('/') + 1);
            var imagePath = baseUrl + newImageAnimatedId;
          }
          $('.signature_profile').attr('src', imagePath + '?t=' + new Date().getTime());
          $("#uploadfile2").find('img').attr("src", imagePath);
        } else {
          // $(".loader-image").show();
          resetCustomLoader();
          updateCustomLoader(10, "Preparing your GIF...");
          imageName = $("#signature_profile_data").data("image");
          imagePathName = $("#signature_profile_data").data("image-path");
          circleJsonName = $("#signature_profile_data").data("circle-json");
          squareJsonName = $("#signature_profile_data").data("square-json");
          if (!$.trim(imageName)) {
            imageName = $('input[name="signature_profile"]').val();
            imagePathName = $('.signature_profile').attr('src');
          }
          (async () => {
            try {
              await exportNodejsGIF('square', squareJsonName, imageName, imagePathName);
              randomNumber = Math.floor(Math.random() * (60 - 50 + 1)) + 50;
              updateCustomLoader(randomNumber, "Processing frames...");
              await exportNodejsGIF('circle', circleJsonName, imageName, imagePathName, true);
              updateCustomLoader(100, 'Finalizing your GIF...');
              console.log('Both GIFs generated successfully!');
            } catch (err) {
              console.error('Error in GIF generation chain:', err);
            }
          })();
          // exportNodejsGIF('square',squareJsonName,imageName,imagePathName);
          // exportNodejsGIF('circle',circleJsonName,imageName,imagePathName);
        }
      } else {
        $('#signature_profile_select_animation_section').addClass('section_disabled');
        imagePath = $("#signature_profile_data").data("image-path");
        if (!$.trim(imagePath)) {
          imageOldPath = $('.signature_profile').attr("src");
          var imageId = $('input[name="signature_profile"]').val();
          var baseUrl = imageOldPath.substring(0, imageOldPath.lastIndexOf('/') + 1);
          var imagePath = baseUrl + imageId;
          $("#signature_profile_data").attr("data-gifcreated", true);
        }
        $('.signature_profile').attr('src', imagePath + '?t=' + new Date().getTime());
        $("#uploadfile2").find('img').attr("src", imagePath);
      }
    });

    // $(document).on('click', '.profile_animation_change_gif', function(e) {
    $('.signature_profileanimation_gif').change(function () {
      // animationGifLink = $(this).find('img').attr('src');
      // animationGifLinkName = animationGifLink.substring(animationGifLink.lastIndexOf("/") + 1)

      // $(".profile-annimation-gif").attr("src",animationGifLink);
      // $("#signature_profileanimation_gif_name").val(animationGifLinkName);
      animationGifShape = $(this).find('img').data('shape');
      imagePath = $("#signature_profile_data").data(animationGifShape + "-image-path");
      if (imagePath) {
        $('.signature_profile').attr('src', imagePath + '?t=' + new Date().getTime());
      } else {
        if ($("#signature_profileanimation:checked")) {
          var url = $('.signature_profile').attr('src');
          if (url.includes('.gif')) {
            if (url.includes('-square.gif')) {
              url = url.replace('-square.gif', '-circle.gif');
            } else if (url.includes('-circle.gif')) {
              url = url.replace('-circle.gif', '-square.gif');
            }
          }
          $('.signature_profile').attr('src', url + '?t=' + new Date().getTime());
        }
      }

    });

    //////// GIF ANIMATION TASK JS END ////////

    //////// Banner on/off TASK JS END ////////
    $('#signature_banner_display').change(function () {
      if (this.checked) {
        $(".layout_banner").css("display", "inline");
        $(".banner_style_section").removeClass('section_disabled');
      } else {
        $(".layout_banner").css("display", "none");
        $(".banner_style_section").addClass('section_disabled');
      }
    });

    if ($('#signature_banner_display').is(':checked')) {
      $(".banner_style_section").removeClass('section_disabled');
    } else {
      $(".banner_style_section").addClass('section_disabled');
    }

    //////// Banner on/off TASK JS END ////////


    //////// Profile Image size JS START ////////
    $('.signature_profile').each(function () {
      dataWidth = $(this).data('width');
      width = $(this).width();
      if (dataWidth != null && (width == null || dataWidth != width)) {
        if (!$(this).closest(".signature_layot").find("input:radio[name='layout_id']").is(":checked")) {
          $(this).attr('width', dataWidth)
        }
      }
    })
    $('input:radio[name="signature_profileshape"]').change(function () {
      var radios = $(this).val();
      $('.signature_profile').css('border-radius', radios);
      $('.profile-annimation-gif').css('border-radius', radios);
    });
    //////// Profile Image size JS END ////////

    //////// Social icon search START ////////

    // When user checks/unchecks a social icon
    // When user checks/unchecks a social icon
    // Toggle show/hide when checkbox changes
    // Toggle collapses when checkbox changes (initial control)
    // Checkbox toggle → show/hide collapse
    // Checkbox toggle → show/hide collapse
    // Checkbox toggle → show/hide collapse
    function updateInputs(filter) {
      $("#social_link_box label").each(function () {
        var $checkbox = $(this).find("input[type='checkbox']");
        var id = $checkbox.attr("id");          // e.g. "web-icon"
        var collapseId = "#collapse-" + id;     // -> "#collapse-web-icon"
        var altText = $(this).find("img").attr("alt").toLowerCase();

        var matchesSearch = !filter || altText.indexOf(filter) > -1;

        if (matchesSearch) {
          $(this).show(); // show icon if it matches search (or no search)
          if ($checkbox.is(":checked")) {
            $(collapseId).removeClass("hidden").addClass("block");
          } else {
            $(collapseId).removeClass("block").addClass("hidden");
          }
        } else {
          $(this).hide(); // hide icon
          $(collapseId).removeClass("block").addClass("hidden"); // hide input if filtered out
        }
      });
    }

    // Checkbox toggle → respect current search filter
    $("#social_link_box input[type='checkbox']").on("change", function () {
      var filter = $("#social_search_input").val().toLowerCase();
      updateInputs(filter);
    });

    // Search filter
    $("#social_search_input").on("keyup", function () {
      var filter = $(this).val().toLowerCase();
      updateInputs(filter);
    });

    // Init state on page load
    updateInputs("");
    //////// Social icon search END ////////


    function exportLottieAsGif(shape, lottiePath, imageName) {
      const container = document.getElementById('lottie-' + shape);
      container.innerHTML = '';

      const anim = lottie.loadAnimation({
        container: container,
        renderer: 'canvas',
        loop: true,
        autoplay: true,
        path: lottiePath
      });

      anim.addEventListener('DOMLoaded', function () {
        const canvas = container.querySelector('canvas');
        if (!canvas) {
          alert('Animation not loaded!');
          return;
        }

        const gif = new GIF({
          workers: 2,
          quality: 10, // Best quality
          workerScript: '/script/gif.worker.js',
          transparent: 'rgba(0,0,0,0)',
          width: 300, // Updated size
          height: 300, // Updated size
          repeat: 0 // Play once — saves space (optional)
        });

        const maxDurationMs = 9000; // 9 seconds max
        const durationMs = Math.min(anim.getDuration() * 1000, maxDurationMs); // Max 9 seconds
        const targetFps = 24; // 24 FPS
        const totalFrames = Math.floor((durationMs / 1000) * targetFps);
        const delay = 1000 / targetFps;

        const offscreenCanvas = document.createElement('canvas');
        offscreenCanvas.width = 300;
        offscreenCanvas.height = 300;
        const ctx = offscreenCanvas.getContext('2d');

        let currentFrame = 0;

        function captureFrame() {
          if (currentFrame >= totalFrames) {
            gif.render();
            return;
          }

          const time = (currentFrame / targetFps) * 1000;
          anim.goToAndStop(time, false);

          setTimeout(() => {
            ctx.clearRect(0, 0, 300, 300); // Clear the canvas before drawing a new frame
            if (shape === 'circle') {
              ctx.save();
              ctx.beginPath();
              ctx.arc(150, 150, 150, 0, Math.PI * 2); // Centered on 100,100 for 200x200 canvas
              ctx.clip();
              ctx.drawImage(canvas, 0, 0, 300, 300);
              ctx.restore();
            } else {
              ctx.drawImage(canvas, 0, 0, 300, 300);
            }

            gif.addFrame(offscreenCanvas, { copy: true, delay: delay });
            currentFrame++;
            captureFrame();
          }, 10); // low timeout for fast capturing
        }

        gif.on('finished', function (blob) {
          const url = URL.createObjectURL(blob);
          document.getElementById('result-' + shape).src = url;

          const formData = new FormData();
          formData.append('file', blob, 'animation.gif');
          formData.append('saveProfileGif', 'true');
          const gifFilename = imageName.replace(/\.png$/, '-' + shape + '.gif');
          formData.append('gif_name', gifFilename);

          fetch('', {
            method: 'POST',
            body: formData
          })
            .then(response => {
              if (response.ok) {
                console.log('GIF uploaded successfully');
                const $img = $('.signature_profile');
                if ($img.length) {
                  const src = $img.attr('src');
                  if (src) {
                    const filename = src.split('/').pop();
                    const userId = filename.split('-')[1].replace('.png', '');
                    const newSrc = root_link + "/upload-beta/signature/profile/" + userId + "/" + gifFilename;
                    $img.attr('src', newSrc);
                    $("#signature_profile_data").attr("data-" + shape + "-image-path", newSrc);
                    // $(".loader-image").hide();
                    $("#signature_profile_data").data("gifcreated", true);

                    const animationGifShape = $('.signature_profileanimation_gif:checked').data('shape');
                    const imagePath = $("#signature_profile_data").data(animationGifShape + "-image-path");
                    $('.signature_profile').attr('src', imagePath + '?t=' + new Date().getTime());
                  }
                }
              } else {
                console.error('Upload failed:', response.statusText);
              }
            })
            .catch(error => {
              console.error('Error uploading GIF:', error);
            });

          setTimeout(() => URL.revokeObjectURL(url), 1000);
        });

        captureFrame();
      });
    }

    // function exportNodejsGIF(shape, lottiePath, imageName,imagePathName){
    //   const formData = new FormData();
    //   formData.append('generateAndSaveGIF', 'true');
    //   const gifFilename = imageName.replace(/\.png$/, '-' + shape + '.gif');
    //   formData.append('gif_name', gifFilename);
    //   formData.append('image_name', imageName);
    //   formData.append('image_path_name', imagePathName);
    //   formData.append('json_path', lottiePath);
    //   formData.append('shape', shape);

    //   fetch('', {
    //     method: 'POST',
    //     body: formData
    //   })
    //   .then(response => response.json())   // parse JSON
    //   .then(data =>  {
    //     // let data = response.json();
    //     console.log(data);
    //     if(data.error == 0){
    //       const $img = $('.signature_profile');
    //       if ($img.length) {
    //         const src = $img.attr('src');
    //         if (src) {
    //           const filename = src.split('/').pop();
    //           const userId = filename.split('-')[1].replace('.png', '');
    //           const newSrc = root_link+"/upload-beta/signature/profile/" + userId + "/" + gifFilename;
    //           $img.attr('src', newSrc);
    //           $("#signature_profile_data").attr("data-" + shape + "-image-path", newSrc);
    //           $(".loader-image").hide();
    //           $("#signature_profile_data").data("gifcreated", true);

    //           const animationGifShape = $('.signature_profileanimation_gif:checked').data('shape');
    //           const imagePath = $("#signature_profile_data").data(animationGifShape + "-image-path");
    //           $('.signature_profile').attr("src", imagePath);
    //         }
    //       }
    //     }else{
    //       console.error('Error uploading GIF:', data);
    //       alert('Something went wrong, while generating GIF. Please try again.');
    //     }
    //   })
    //   .catch(error => {
    //     console.error('Error uploading GIF:', error);
    //     alert('Something went wrong, while generating GIF. Please try again.');
    //   });
    // }

    function exportNodejsGIF(shape, lottiePath, imageName, imagePathName, isLast = false) {
      return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('generateAndSaveGIF', 'true');
        const gifFilename = imageName.replace(/\.png$/, '-' + shape + '.gif');
        formData.append('gif_name', gifFilename);
        formData.append('image_name', imageName);
        formData.append('image_path_name', imagePathName);
        formData.append('json_path', lottiePath);
        formData.append('shape', shape);

        fetch('', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {

            if (data.error == 0) {
              const $img = $('.signature_profile');
              if ($img.length) {
                const src = $img.attr('src');
                if (src) {
                  const filename = $('input[name="signature_profile"]').val();
                  // const userId = filename.split('-')[1].replace('.png', '');
                  const match = filename.match(/-(.*?)\.[^.]+$/);
                  const userId = match ? match[1] : 0;

                  let newSrc =
                    root_link +
                    '/upload-beta/signature/profile/' +
                    userId +
                    '/' +
                    gifFilename;
                  $.ajax({
                    url: newSrc,
                    type: 'HEAD',
                    success: function () {
                      selectedGifShape = $('.signature_profileanimation_gif:checked').data('shape');
                      if (shape == selectedGifShape) {
                        $img.attr('src', newSrc + '?t=' + new Date().getTime());
                      }
                      $('#signature_profile_data').attr(
                        'data-' + shape + '-image-path',
                        newSrc
                      );
                    },
                    error: function () {
                      newSrc = root_link + '/upload-beta/signature/profile/' + gifFilename;
                      selectedGifShape = $('.signature_profileanimation_gif:checked').data('shape');
                      if (shape == selectedGifShape) {
                        $img.attr('src', newSrc + '?t=' + new Date().getTime());
                      }
                      $('#signature_profile_data').attr(
                        'data-' + shape + '-image-path',
                        newSrc
                      );
                    }
                  });
                  if (isLast) {
                    // $('.loader-image').hide();
                  }
                  $('#signature_profile_data').data('gifcreated', true);

                  // const animationGifShape = $('.signature_profileanimation_gif:checked').data('shape');
                  // const imagePath = $('#signature_profile_data').data(animationGifShape + '-image-path');
                  // $('.signature_profile').attr('src', imagePath + '?t=' + new Date().getTime());
                }
              }
              resolve(data); // ✅ success, resolve promise
            } else {
              console.error('Error uploading GIF:', data);
              alert('Something went wrong, while generating GIF. Please try again.');
              // $('.loader-image').hide();
              location.reload();
              reject(data); // ❌ reject on error
            }
          })
          .catch(error => {
            console.error('Error uploading GIF:', error);
            alert('Something went wrong, while generating GIF. Please try again.');
            // $('.loader-image').hide();
            location.reload();
            reject(error);
          });
      });
    }






  });
})(jQuery);
// Step 1: Setup global abort controller
const globalAbortController = new AbortController();

// Step 2: Patch fetch to use it
const originalFetch = window.fetch;
window.fetch = function (...args) {
  const options = args[1] || {};
  options.signal = globalAbortController.signal;
  args[1] = options;
  return originalFetch.apply(this, args);
};

// Step 3 (optional): Track and clear timeouts and intervals
const timeouts = new Set();
const intervals = new Set();

const originalSetTimeout = window.setTimeout;
const originalSetInterval = window.setInterval;

window.setTimeout = function (...args) {
  const id = originalSetTimeout.apply(this, args);
  timeouts.add(id);
  return id;
};

window.setInterval = function (...args) {
  const id = originalSetInterval.apply(this, args);
  intervals.add(id);
  return id;
};

// Step 4: Cancel everything on refresh/unload
window.addEventListener("beforeunload", () => {
  globalAbortController.abort(); // cancel all fetches
  timeouts.forEach(clearTimeout); // clear timeouts
  intervals.forEach(clearInterval); // clear intervals
});
