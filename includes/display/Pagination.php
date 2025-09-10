<?php
class Pagination 
{	
	private $tableName,$perPage,$totalpage,$fileName,$countRow,$orderId,$startPage,$endPage,$statusId,$pageLink,$galleryid, $morePageLink;	
	function Page($tableName='',$whereCondition='',$morePageLink = '' )
	{	
		 if($tableName != ''){
			$this->tableName = $tableName;
			$this->perPage = $GLOBALS['PerPage'];
			$this->morePageLink = $morePageLink;
			if($GLOBALS['SeoUnabled']){	
			$this->fileName=$GLOBALS['ROOT_LINK'];
			} else {
			$this->fileName=$GLOBALS['ROOT_LINK'].'/index.php';
			}
			$this->endPage = $this->perPage;	
			$this->wCondition = $whereCondition;		
			
			if($this->wCondition == ""){
				$countSql = "SELECT * FROM `".$this->tableName."`";
			} else {
				$countSql = "SELECT * FROM `".$this->tableName."` where ".$this->wCondition."";
			}	
						
			$countQuery = $GLOBALS['CLA_DB']->Query($countSql);			

			$this->countRow = $GLOBALS['CLA_DB']->CountResult($countQuery);		
			$page1= $this->countRow%$this->perPage;
			
			if($page1==0){
				$this->totalpage=($this->countRow/$this->perPage);		
			}	else	{
				$this->totalpage=($this->countRow/$this->perPage)+1;		 
			}
			$GLOBALS['totalpage'] = (int)$this->totalpage;
			
			$pageno=round($this->totalpage,0);			
			if(isset($GLOBALS['PageNo']))
			{
				if($GLOBALS['PageNo']==1)
				{
					$this->startPage=0;
					$this->endPage=$this->perPage;
				}
				else
				{
					$this->startPage=(($GLOBALS['PageNo']*$this->perPage)-$this->perPage);			
				}
			}
			else
			{
				$this->startPage=0;
			}	
			// link
			$addLink = '';
			if(isset($_REQUEST['module'])){
				if($GLOBALS['SeoUnabled']){	
					$addLink .= '/'.$_REQUEST['module'];  
				} else {
					$addLink .= 'module='.$_REQUEST['module'];  
				} 				
				if(isset($_REQUEST['gallery_id'])){
					if($GLOBALS['SeoUnabled']){	
						$addLink .= '/'.$_REQUEST['gallery_id'];  
					} else {
						$addLink .= '&gallery_id='.$_REQUEST['gallery_id'];  
					} 
				}
				if($morePageLink != ''){					
					$addLink .= $morePageLink;						
				}
			}						
			if($addLink != ""){
				$this->pageLink = $addLink;
			}		
		}
	}	
	
	function prevImage()
	{				
		if(isset($GLOBALS['PageNo'])){
			if($GLOBALS['PageNo']!='' && $GLOBALS['PageNo']!=1) {		
				if($GLOBALS['SeoUnabled']){	
					$prevLink = sprintf('<a class="button_txt" href="%s%s/page%d">&lt;&lt; Previous</a>',$this->fileName,$this->pageLink,$GLOBALS['PageNo']-1,$GLOBALS['IMAGE_LINK']);							
				} else {
					$prevLink = sprintf('<a class="button_txt" href="%s?%s&page=%d">&lt;&lt; Previous</a>',$this->fileName,$this->pageLink,$GLOBALS['PageNo']-1,$GLOBALS['IMAGE_LINK']);							
				}
				return $prevLink;
			} else {
				return false;
			}	
		} else {
			return false;
		}
	}
	function nextImage()
	{	
		$splt1=explode('.',$this->totalpage);	
		if(isset($GLOBALS['PageNo'])){
			$pageID = $GLOBALS['PageNo'];
		} else {	
			$pageID = 1;
		}
		if($pageID!=$splt1[0])
		{ 
			if($this->countRow > $this->perPage)
			{ 
				if($GLOBALS['SeoUnabled']){	
					if(isset($GLOBALS['PageNo'])){
						$nextLink = sprintf('<a href="%s%s/page%d" class="button_txt">Next &gt;&gt;</a>',$this->fileName,$this->pageLink,$GLOBALS['PageNo']+1,$GLOBALS['IMAGE_LINK']);		
					}
					else
					{					
						$nextLink = sprintf('<a href="%s%s/page2" class="button_txt">Next &gt;&gt;</a>',$this->fileName,$this->pageLink,$GLOBALS['IMAGE_LINK']);		
					}
				} else {
					if(isset($GLOBALS['PageNo'])){
						$nextLink = sprintf('<a href="%s?%s&page=%d" class="button_txt">Next &gt;&gt;</a>',$this->fileName,$this->pageLink,$GLOBALS['PageNo']+1,$GLOBALS['IMAGE_LINK']);		
					}
					else
					{					
						$nextLink = sprintf('<a href="%s?%s&page=2" class="button_txt">Next &gt;&gt;</a>',$this->fileName,$this->pageLink,$GLOBALS['IMAGE_LINK']);		
					}
				}
				return $nextLink;
			}
		}		
		
	}

	function pageLink()
	{		
		$pageLink = '';
		if($GLOBALS['SeoUnabled']){	
			for($i=1;$i<=$this->totalpage;$i++)
			{		
				if(isset($GLOBALS['PageNo'])){
					if($i!=$GLOBALS['PageNo']){
						$pageLink .= sprintf('<a class="button_txt" href="%s%s/page%d">%d&nbsp;</a>', $this->fileName,$this->pageLink,$i,$i);				
					}else{
						$pageLink .= sprintf('<a class="button_txtactive" href="%s%s/page%d">%d&nbsp;</a>',$this->fileName,$this->pageLink,$i,$i);						
					}					
				} else {
					if($i == 1){
					$pageLink .= sprintf('<a class="button_txtactive" href="%s%s/page%d">%d&nbsp;</a>',$this->fileName,$this->pageLink,$i,$i);						
					} else {
					$pageLink .= sprintf('<a class="button_txt" href="%s%s/page%d">%d&nbsp;</a>',$this->fileName,$this->pageLink,$i,$i);						
					}
				}
			}
		} else {
			for($i=1;$i<=$this->totalpage;$i++)
			{
				if(isset($GLOBALS['PageNo'])){
					if($i!=$GLOBALS['PageNo']){
						$pageLink .= sprintf('<a class="button_txt" href="%s?%s&page=%d">%d&nbsp;</a>',$this->fileName,$this->pageLink,$i,$i);				
					} else {
						$pageLink .= sprintf('<a class="button_txtactive" href="%s?%s&page=%d">%d&nbsp;</a>',$this->fileName,$this->pageLink,$i,$i);						
					}					
				} else {
					if($i == 1){
						$pageLink .= sprintf('<a class="button_txtactive" href="%s?%s&page=%d">%d&nbsp;</a>',$this->fileName,$this->pageLink,$i,$i);						
					} else {
						$pageLink .= sprintf('<a class="button_txt" href="%s?%s&page=%d">%d&nbsp;</a>',$this->fileName,$this->pageLink,$i,$i);						
					}
				}
			}
		}
		return $pageLink;
	}		
}