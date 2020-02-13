<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $this->getConfig();
//houses
if($upd=='all' || $upd='houses') {
	$host='https://api.sst-cloud.com/houses/';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$host");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, false);
	
    //curl_setopt($ch, CURLOPT_COOKIE, 'sessionid='.$this->config['SESSIONID'].'; csrftoken='.$this->config['APITOKEN']);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Accept: application/json', 
				'X-CSRFToken: '.$this->config['APITOKEN'],
				'Authorization: Token '.$this->config['APIKEY']));
	curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
	//curl_setopt($curl, CURLOPT_POST, true);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($ch);
	curl_close($ch);
	$resp_decoded=json_decode($response, TRUE);
	foreach($resp_decoded as $val) {
		$id=$val['id'];
		$rec = SQLSelectOne("SELECT ID FROM dev_sst_cloud_houses WHERE HOUSE_ID='".$id."'");
		$rec['HOUSE_ID']=$id;
		$rec['TITLE']=$val['name'];
		$rec['OWNER']=$val['owner'];
		if(IsSet($rec['ID'])) {
			SQLUpdate('dev_sst_cloud_houses', $rec);
		} else {
			$rec['ID']=SQLInsert('dev_sst_cloud_houses', $rec);
		}		
	}
}
$houses=SQLSelect("SELECT * FROM dev_sst_cloud_houses");
foreach($houses as $house) {
//groups
	if($upd='all' || $upd='groups') {
		$host='https://api.sst-cloud.com/houses/'.$house['HOUSE_ID'].'/groups/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "$host");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		
		//curl_setopt($ch, CURLOPT_COOKIE, 'sessionid='.$this->config['SESSIONID'].'; csrftoken='.$this->config['APITOKEN']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Accept: application/json', 
				'X-CSRFToken: '.$this->config['APITOKEN'],
				'Authorization: Token '.$this->config['APIKEY']));
		curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
		//curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
		//curl_setopt($curl, CURLOPT_POST, true);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		curl_close($ch);
		$resp_decoded=json_decode($response, TRUE);
		foreach($resp_decoded as $val) {
			$id=$val['id'];
			$rec = SQLSelectOne("SELECT ID FROM dev_sst_cloud_groups WHERE GROUP_ID='".$id."'");
			$rec['GROUP_ID']=$id;
			$rec['TITLE']=$val['name'];
			$rec['HOUSE_ID']=$val['house'];
			if(IsSet($rec['ID'])) {
				SQLUpdate('dev_sst_cloud_groups', $rec);
			} else {
				$rec['ID']=SQLInsert('dev_sst_cloud_groups', $rec);
			}		
		}
	}
//devices
	if($upd='all' || $upd='devices') {
		$host='https://api.sst-cloud.com/houses/'.$house['HOUSE_ID'].'/devices/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "$host");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		
		//curl_setopt($ch, CURLOPT_COOKIE, 'sessionid='.$this->config['SESSIONID'].'; csrftoken='.$this->config['APITOKEN']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Accept: application/json', 
				'X-CSRFToken: '.$this->config['APITOKEN'],
				'Authorization: Token '.$this->config['APIKEY']));
		curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
		//curl_setopt($curl, CURLOPT_POST, true);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		curl_close($ch);
		$resp_decoded=json_decode($response, TRUE);
		foreach($resp_decoded as $val) {
			$parsed_conf=json_decode($val['parsed_configuration'], TRUE);
			$id=$val['id'];
			$rec = SQLSelectOne("SELECT ID FROM dev_sst_cloud_devices WHERE CLOUD_ID='".$id."'");
			$rec['CLOUD_ID']=$id;
			$rec['TITLE']=$val['name'];
			$rec['HOUSE']=$val['house'];
			$rec['GROUP']=$val['group'];
			$rec['TYPE']=$val['type'];
			if(IsSet($rec['ID'])) {
				SQLUpdate('dev_sst_cloud_devices', $rec);
			} else {
				$rec['ID']=SQLInsert('dev_sst_cloud_devices', $rec);
			}
			foreach($parsed_conf['settings'] as $k=>$v){
				if(is_array($v)) {
					foreach($v as $ke=>$va){
						$ke="$k.$ke";
						$parsed_array[$ke]=$va;
					}
        				continue;
				}
				$parsed_array[$k]=$v;
			}
			foreach($parsed_conf['current_temperature'] as $k=>$v){
				$k="current.$k";
				$parsed_array[$k]=$v;
			}
			$parsed_array['relay_status']=$parsed_conf['relay_status'];
			foreach($parsed_array as $k=>$v){	
				$datarec=SQLSelectOne("SELECT ID, LINKED_OBJECT, LINKED_PROPERTY FROM dev_sst_cloud_data WHERE DEVICE_ID='".$rec['ID']."' AND TITLE='".$k."'");
				$datarec['TITLE']=$k;
				$datarec['VALUE']=$v;
				$datarec['DEVICE_ID']=$rec['ID'];
				if(IsSet($datarec['ID'])) {
					if(isset($datarec['LINKED_OBJECT']) && $datarec['LINKED_OBJECT']!='' && isset($datarec['LINKED_PROPERTY']) && $datarec['LINKED_PROPERTY']!='') {
						sg($datarec['LINKED_OBJECT'].'.'.$datarec['LINKED_PROPERTY'], $this->metricsModify($k, $v, 'from_device'), array($this->name => '0'));
					}
					SQLUpdate('dev_sst_cloud_data', $datarec);
				} else {
					$datarec['ID']=SQLInsert('dev_sst_cloud_data', $datarec);
				}
			}	
		}
	}
}
