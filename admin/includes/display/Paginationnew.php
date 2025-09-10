<?php
class Pagination {	
	private function getPage($tableName='',$whereCondition='',$morePageLink = ''){
        $limit = $GLOBALS['PerPage'];
        // Look for a GET variable page if not found default is 1.
       if(isset($GLOBALS['PageNo'])){
            $pn = $$GLOBALS['PageNo'];
        } else {
            $pn = 1;
        }
        $startFrom = ($pn - 1) * $limit;
		if($whereCondition == ""){
			$countSql = "SELECT * FROM `".$tableName."` LIMIT ".$startFrom.",".$limit."";
		} else {
			$countSql = "SELECT * FROM `".$tableName."` where ".$whereCondition ." LIMIT ".$startFrom.",".$limit."";
		}	
       $result = $GLOBALS['CLA_DB']->Query($countSql);	
        return $result;
    }
	
	private function getAllRecords(){
        $query = 'SELECT * FROM tbl_animal';
        $totalRecords = $this->ds->getRecordCount($query);
        return $totalRecords;
    }
}