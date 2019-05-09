<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['dev_sst_cloud_devices_qry'];
  } else {
   $session->data['dev_sst_cloud_devices_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_dev_sst_cloud_devices="HOUSE DESC";
  $out['SORTBY']=$sortby_dev_sst_cloud_devices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM dev_sst_cloud_devices WHERE $qry ORDER BY ".$sortby_dev_sst_cloud_devices);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
	if ($res[$i]['HOUSE'] != $new_house) {
		$res[$i]['NEWHOUSE']=1;
		$houseid=$res[$i]['HOUSE'];
		$housename=SQLSelectOne("SELECT TITLE FROM dev_sst_cloud_houses WHERE HOUSE_ID='$houseid'");
		$res[$i]['HOUSENAME']=$housename['TITLE'];
		$new_house=$res[$i]['HOUSE'];
	}

      /*if ($res[$i]['GROUP'] != $new_group)
      {
         $res[$i]['NEWGROUP']=1;
         $new_group=$res[$i]['GROUP_ID'];
      }*/
    $tmp=explode(' ', $res[$i]['UPDATED']);
    $res[$i]['UPDATED']=fromDBDate($tmp[0])." ".$tmp[1];
   }
   $out['RESULT']=$res;
  }
