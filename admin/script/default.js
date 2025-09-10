function jsConfirm(item_name, action){
	if (action == 'delete'){
		if(item_name !=""){
			return confirm ('Are you sure you want to delete '+item_name+' ?');
		}else{
			return confirm ('Are you sure you want to delete it ?');
		}
	}else if(action == 'restore'){
		return confirm ('This will erase current database and restore selected database. Are you sure you want to do this?');
	}else if(action == 'gold'){
		return confirm ('Are you sure you want to upgrade '+item_name+' free to gold membership?');
	}else{
		return true;
	} //end if
}

function ValidateAmount(obj){
	var strChar;
	var blnResult = true;
	var i;
	var strValidChars = '0123456789.';
	strString = obj.value
	for (i = 0; i < strString.length && blnResult == true; i++){
		strChar = strString.charAt(i);
		if (strValidChars.indexOf(strChar) == -1){
			blnResult = false;
		}
	}
	if (blnResult == false){
		var msg;
		msg = "please enter only amount number! ex.123";
		alert(msg);
		obj.value = ""
		flag = "false";
		return false;
	}
}

function validatephone(obj){
	var strChar;
	var blnResult = true;
	var i;
	var strValidChars = '0123456789.,-()/""&$:;+';
	//  check for valid numeric strings	
	strString = obj.value
	//  test strString consists of valid characters listed above
	for (i = 0; i < strString.length && blnResult == true; i++){
		strChar = strString.charAt(i);
		if (strValidChars.indexOf(strChar) == -1){
			blnResult = false;
		}
	}

	if (blnResult == false){
		var msg;
		msg = " Enter Only Numbers";
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

function validation(arrayTitle,arrayId){
	var counter = arrayTitle.length;		
	var i;
	for(i=0;i<=counter;i++){			
		if(document.getElementById(arrayId[i]).value=='' || document.getElementById(arrayId[i]).value=='0'){	
			alert(capitaliseFirstLetter(arrayTitle[i]) + ' is required');
			document.getElementById(arrayId[i]).focus();
			return false;
		}		
	}	
	return true;
}

function SearchProduct() { 
	var d= document.getElementById('search').value;
	var url = document.getElementById('ajax').value;
	var addbutton = 'add_'+d;
	var addedbutton = 'added_'+d;
	var url = url+"/"+d;
	jQuery.ajax({
		url: url,
		type: "POST",
		success:function(data){		
			$("#mail-status").html(data);	
		},
		error:function (){alert('d');}
	});
}

function capitaliseFirstLetter(string){
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function resize(image,dimension,dimension2){
	var customImage=new Image();
	if (dimension == 'undefined'){
		dimension=96;	
	}
	customImage.src=image.src;
	var imw = customImage.width;
	var imh = customImage.height;
	var tmp1=0;
	var tmp2=0;
	var rh;
	rh = imh / dimension2;
	var rw = imw / dimension;
	var ratio = (rw > rh) ? rw : rh;
	if (ratio >= 1) {
		tmp1 = imw / ratio;
		tmp2 = imh / ratio; 
	}else{
		tmp1 = imw;
		tmp2 = imh; 
	} 
	if (tmp2 <= dimension2){
		image.width = tmp1;		
		image.height = tmp2;
	}else{
		var rw = imw / dimension;
		rh = imh / dimension2;
		var ratio = (rw > rh) ? rw : rh;
		if (ratio >= 1) {
			image.width = imw / ratio;
			image.height = imh / ratio; 
		} else {
			image.width = imw;
			image.height = imh; 
		}
	}
}

function checkEmail(obj){
	var emailLetter = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
	if(!emailLetter.test(obj.value)){
		alert("enter the validate email address..");
		obj.value = "";
		obj.focus();
		return false;
	}
}

function classesValidate(obj){ 
	if(obj.clsname.value == ''){
		alert("Please Enter Class");
		obj.clsname.focus();
		return false;
	}
}

function LocalValidate(obj){
	if(obj.cat_id.value == ''){
		alert("Please Enter Category name");
		obj.cat_id.focus();
		return false;
	}
	if(obj.shop_title.value == ''){
		alert("Please Enter Product name");
		obj.shop_title.focus();
		return false;
	}
	if(obj.shop_seourl.value == ''){
		alert("Please Enter Product name");
		obj.shop_seourl.focus();
		return false;
	}
}

function OnlineValidate(obj){
	if(obj.shop_title.value == ''){
		alert("Please Enter Product name");
		obj.shop_title.focus();
		return false;
	}
	if(obj.shop_free.value == ''){
		alert("Please Enter Product Free");
		obj.shop_free.focus();
		return false;
	}
	if(isNaN(obj.shop_free.value)){
		alert("Please Enter only Number Free Valid Saving Type");
		obj.shop_free.value="";
		obj.shop_free.focus();
		return false;
	}
	if(obj.shop_vip.value == ''){
		alert("Please Enter Product VIP");
		obj.shop_vip.focus();
		return false;
	}
	if(isNaN(obj.shop_vip.value)){
		alert("Please Enter only Number VIP Valid Saving Type");
		obj.shop_vip.value="";
		obj.shop_vip.focus();
		return false;
	}
	if(obj.shop_link.value == ''){
		alert("Please Enter Store Link");
		obj.shop_link.focus();
		return false;
	}		
}

function savingtype(obj){
	var type = document.getElementById('type');
	var type1 = document.getElementById('type1');
	if(obj.value==0){
		type.innerHTML='%';
		type1.innerHTML='%';
	}else{
		type.innerHTML='$';
		type1.innerHTML='$';
	}
}

function ecardsValidate(obj){ 
	if(obj.ecard_name.value == ''){
		alert("Please Enter Ecard name");
		obj.ecard_name.focus();
		return false;
	}
	if(obj.ecard_seo.value == ''){
		alert("Please Enter Ecard SeoUrl");
		obj.ecard_seo.focus();
		return false;
	}
	if(obj.ecard_qty.value == ''){
		alert("Please Enter Ecard Quantity");
		obj.ecard_qty.focus();
		return false;
	}
	if(obj.ecard_price.value == ''){
		alert("Please Enter Ecard Price");
		obj.ecard_price.focus();
		return false;
	}
	if(obj.ecard_splprice.value == ''){
		alert("Please Enter Ecard Price");
		obj.ecard_splprice.focus();
		return false;
	}
	if(obj.shop_sku.value == ''){
		alert("Please Enter Uniq Sku");
		obj.shop_sku.focus();
		return false;
	}
}

function subjectValidate(obj){
	if(obj.subject.value == ''){
		alert("Please Enter Subject");
		obj.subject.focus();
		return false;
	}
	if(obj.subimage.value == ''){
		alert("Please Enter Subject Image");
		obj.subimage.focus();
		return false;
	}
}

function categoryValidate(obj){
	if(obj.title.value == ''){
		alert("Please Enter Title");
		obj.title.focus();
		return false;
	}
	if(obj.seo.value == ''){
		alert("Please Enter Seo Url");
		obj.seo.focus();
		return false;
	}
}

function subcategoryValidate(obj){
	if(obj.cat_name.value == ''){
		alert("Please Enter  Category");
		obj.cat_name.focus();
		return false;
	}
	if(obj.title.value == ''){
		alert("Please Enter Sub Category Title");
		obj.title.focus();
		return false;
	}
}

function subcategoryValidate(obj){
	if(obj.cat_name.value == ''){
		alert("Please Enter  Category");
		obj.cat_name.focus();
		return false;
	}
	if(obj.title.value == ''){
		alert("Please Enter Sub Category Title");
		obj.title.focus();
		return false;
	}
}

function userValidate(obj){
	if(obj.uname.value == ''){
		alert("Enter Usernmae");
		obj.uname.focus();
		return false;
	}
	if(obj.password.value == ''){
		alert("Enter password");
		obj.password.focus();
		return false;
	}
	if(obj.password.value.length < 6) { 
		alert(" Password must contain at least six characters!"); 
		obj.password.focus(); 
		return false; 
	}
	if(obj.cpassword.value == ''){
		alert("Enter Confirm password");
		obj.cpassword.focus();
		return false;
	}
	if(obj.cpassword.value.length < 6) { 
		alert("Confirm Password must contain at least six characters!"); 
		obj.cpassword.focus(); 
		return false; 
	}
	if(obj.password.value != obj.cpassword.value) { 
		alert("Password And Confirm password Do Not Matched. !"); 
		obj.cpassword.focus(); 
		return false; 
	}
	if(obj.email.value == ''){
		alert("Enter email address");
		obj.email.focus();
		return false;
	}
	if(obj.email.value != ''){
		var emailLetter = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
		if(!emailLetter.test(obj.email.value)){
			alert("enter the validate email address..");
			obj.email.value = "";
			obj.email.focus();
			return false;
		}
	}	   
}

function usereditValidate(obj){
	if(obj.uname.value == ''){
		alert("Enter Usernmae");
		obj.uname.focus();
		return false;
	}
	if(obj.pass.value == ''){
		alert("Enter Password");
		obj.pass.focus();
		return false;
	}
	if(obj.pass.value.length < 6){ 
		alert(" Password must contain at least six characters!"); 
		obj.pass.focus(); 
		return false; 
	}
	if(obj.cpass.value == ''){
		alert("Enter Confirm password");
		obj.cpass.focus();
		return false;
	}
	if(obj.cpass.value.length < 6) { 
		alert("Confirm Password must contain at least six characters!"); 
		obj.cpass.focus(); 
		return false; 
	}	
}

function loginValidate(obj){
	if(obj.email.value == ''){
		alert("Enter email address");
		obj.email.focus();
		return false;
	}
	if(obj.email.value != ''){
		var emailLetter = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
		if(!emailLetter.test(obj.email.value)){
			alert("enter the validate email address..");
			obj.email.value = "";
			obj.email.focus();
			return false;
		}
	}
	if(obj.password.value == ''){
		alert("Enter password");
		obj.password.focus();
		return false;
	}
	if(obj.cit_security.value == ''){
		alert("Enter security code");
		obj.cit_security.focus();
		return false;
	}
}

function emailsenderValidate(obj){
	if(obj.contact_name.value == ""){
		alert("please enter contact sender name!");
		obj.contact_name.focus();
		return false;
	}
	if(obj.contact_email.value == ""){
		alert("please enter contact email address!");
		obj.contact_email.focus();
		return false;
	}
	
	if(obj.sales_name.value == ""){
		alert("please enter sales sender name!");
		obj.sales_name.focus();
		return false;
	}
	if(obj.sales_email.value == ""){
		alert("please enter sales email address!");
		obj.sales_email.focus();
		return false;
	}
	
	
	if(obj.support_name.value == ""){
		alert("please enter support sender name!");
		obj.support_name.focus();
		return false;
	}
	if(obj.support_email.value == ""){
		alert("please enter support email address!");
		obj.support_email.focus();
		return false;
	}
	
	if(obj.emailsender_from.value == ""){
		alert("Enter from email address");
		obj.emailsender_from.focus();
		return false;
	}
	if(obj.emailsender_from.value != ""){
		var emailLetter = /^[a-zA-Z0-9.-_]+@[a-zA-Z0-9.-]+\.[a-zA-z]{2,4}$/;
		if(!emailLetter.test(obj.emailsender_from.value)){
			alert("From email address is not valid.");
			obj.emailsender_from.value = "";
			obj.emailsender_from.focus();
			return false;
		}
	}
}

function checkModuleChecked(){
	if(document.getElementById('module').checked){		
		document.getElementById('modulename').style.display = "flex";
		return false;
	} else {
		document.getElementById('modulename').style.display = "none";
	}
	if(document.getElementById('blank').checked){		
		document.getElementById('blankPageField').style.display = "none";
		// document.getElementById('blankDescContent').style.display = "none";
		// document.getElementById('blankDesc').style.display = "none";	
		document.getElementById('seourl').value = document.getElementById('editid').value;
		return false;			
	} else {
		document.getElementById('blankPageField').style.display = "inline";				
		document.getElementById('blankDescContent').style.display = "inline";
		document.getElementById('blankDesc').style.display = "flex";		
		document.getElementById('seourl').value = document.getElementById('hseourl').value;
		return false;
	}
}

/*_______________________________________ ADD NEWSLETTER _______________________________________*/

function pageNewslettterAdd(obj){
	if(obj.category.value == "Select one..." || obj.category.value == ""){
		alert("Select Category");
		obj.category.focus();
		return false;
	}
	if(obj.deals.value == "" || obj.deals.value== "-- Select one.. --"){
		alert("Select Sub Category");
		obj.deals.focus();
		return false;
	}
	if(obj.fromname.value == ""){
		alert("Enter From name");
		obj.fromname.focus();
		return false;
	}
	if(obj.fromemail.value == ""){
		alert("Enter from email");
		obj.fromemail.focus();
		return false;
	}
	var email_format = /^[a-zA-Z0-9.-_]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
	if(!email_format.test(obj.fromemail.value)){
		alert("Enter from email in proper format");
		obj.fromemail.focus();
		return false;
	}
	if(obj.newsletterlayout_id.value == "--layout--" || obj.newsletterlayout_id.value == ""){
		alert("Select Layout");
		obj.newsletterlayout_id.focus();
		return false;
	}
}

function pageNewsletterLayout(obj){
	if(obj.name.value == ""){
		alert("Enter layout name");
		obj.name.focus();
		return false;
	}
}

function newsletterCheckAll(){	
	var emaillist = document.getElementsByName('userid[]');
	var emaillistchecked = false;
	for(var i  = 0; i < emaillist.length ; i++){		
		emaillist[i].checked = true;
	}
}

function newsletterUnCheckAll(){	
	var emaillist = document.getElementsByName('userid[]');
	var emaillistchecked = false;
	for(var i  = 0; i < emaillist.length ; i++){		
		emaillist[i].checked = false;	
	}
}

function pageGalleryImages(obj){
	if(obj.imageno.value == ''){
		alert("Select image no.");
		obj.imageno.focus();
		return false;
	}
}

function pageNewsletterUser(obj){
	if(obj.email.value == ''){
		alert("Enter email address.");
		obj.email.focus();
		return false;
	}
	if(obj.email.value != ''){
		var emailLetter = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
		if(!emailLetter.test(obj.email.value)){
			alert("Enter the validate email address..");
			obj.email.value = "";
			obj.email.focus();
			return false;
		}	
	}
}

function filterNumeric(obj, fieldName){
	if(isNaN(obj.value)){
		alert("Please enter valid " + fieldName );
		obj.value = '';
	} 
}

function CategoryAdd(obj){
	if(obj.cat_name.value==""){	
		alert("Please Enter category Name.");
		obj.cat_name.focus();
		return false;
	}
}

function CategoryAddpair(obj){
	if(obj.cat_subid.value==""){	
		alert("Please Select Pair category.");
		obj.cat_name.focus();
		return false;
	}
	if(obj.cat_name.value==""){	
		alert("Please Enter category Name.");
		obj.cat_name.focus();
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



