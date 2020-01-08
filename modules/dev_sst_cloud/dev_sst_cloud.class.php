<?php
/**
* SST Cloud 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 20:04:51 [Apr 29, 2019])
*/
//
//
class dev_sst_cloud extends module {
/**
* dev_sst_cloud
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="dev_sst_cloud";
  $this->title="SST Cloud";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();
 if(!$this->config['APIKEY']) $out['LOGINED']='none'; else $out['LOGIN']=$this->config['LOGIN'];
 
 if ($this->view_mode=='update_settings') {
	$login=gr('login');
	$host='https://api.sst-cloud.com/auth/login/';
	$post = [
		'username' => $login,
		'password' => gr('pass'),
		'email'   => $login,
		'language'   => "ru",
	];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$host");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($ch);
	$response=explode("\r\n\r\n", $response);
	for($i=0; $i<=count($response); $i++) {
		if(stripos($response[$i], 'HTTP/1.1 200 OK')!==false) {
			$headers=$response[$i];
			$body=$response[$i+1];
			break;
		}
	}
	//[$headers, $body] = explode("\r\n\r\n", $response, 2);
	curl_close($ch);
	$resp=json_decode($body, TRUE);
	if($resp['key']) {
		$token = substr($headers, strpos($headers, "csrftoken=")+10, 64);
		$sessionid = substr($headers, strpos($headers, "sessionid=")+10, 32);
		$this->config['SESSIONID']=$sessionid;
		$this->config['APITOKEN']=$token;
		$this->config['APIKEY']=$resp['key'];
		$this->config['LOGIN']=$login;
		$this->saveConfig();
		$out['LOGINED']='true';
		$this->cloud_get_all('all');
		$this->redirect("?");
	} else {
		$out['LOGINERROR']=1;
		$i=0;
		foreach($resp as $k => $v) {
			$out['LOGINERRORS'][$i]['ERRKEY']=$k;
			$out['LOGINERRORS'][$i]['ERRVAL']=$v;
			$i++;
		}
	}
 }
 if ($this->view_mode=='logout') {
	$host='https://api.sst-cloud.com/auth/logout/';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$host");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, false);
	//curl_setopt($ch, CURLOPT_COOKIE, 'sessionid='.$this->config['SESSIONID'].'; csrftoken='.$this->config['APITOKEN']);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Accept: application/json', 
				'X-CSRFToken: '.$this->config['APITOKEN'],
				'Authorization: Token '.$this->config['APIKEY']));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($ch);
	curl_close($ch);
	$resp=json_decode($response, TRUE);	
		$out['LOGINERROR']=1;
		$i=0;
		foreach($resp as $k => $v) {
			$out['LOGINERRORS'][$i]['ERRKEY']=$k;
			$out['LOGINERRORS'][$i]['ERRVAL']=$v;
			$i++;
		}
	$out['LOGINED']='none';
	$this->config['SESSIONID']='';
	$this->config['APITOKEN']='';
	$this->config['APIKEY']='';
	$this->config['LOGIN']=''; 
	$this->saveConfig();
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='dev_sst_cloud_devices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_dev_sst_cloud_devices') {
   $this->search_dev_sst_cloud_devices($out);
  }
  if ($this->view_mode=='edit_dev_sst_cloud_devices') {
   $this->edit_dev_sst_cloud_devices($out, $this->id);
  }
  if ($this->view_mode=='delete_dev_sst_cloud_devices') {
   $this->delete_dev_sst_cloud_devices($this->id);
   $this->redirect("?data_source=dev_sst_cloud_devices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='dev_sst_cloud_data') {
  if ($this->view_mode=='' || $this->view_mode=='search_dev_sst_cloud_data') {
   $this->search_dev_sst_cloud_data($out);
  }
  if ($this->view_mode=='edit_dev_sst_cloud_data') {
   $this->edit_dev_sst_cloud_data($out, $this->id);
  }
 }
 
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* dev_sst_cloud_devices search
*
* @access public
*/
 function search_dev_sst_cloud_devices(&$out) {
  require(DIR_MODULES.$this->name.'/dev_sst_cloud_devices_search.inc.php');
 }
/**
* dev_sst_cloud_devices edit/add
*
* @access public
*/
 function edit_dev_sst_cloud_devices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/dev_sst_cloud_devices_edit.inc.php');
 }
/**
* dev_sst_cloud_devices delete record
*
* @access public
*/
 function delete_dev_sst_cloud_devices($id) {
  $rec=SQLSelectOne("SELECT * FROM dev_sst_cloud_devices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM dev_sst_cloud_devices WHERE ID='".$rec['ID']."'");
 }
/**
* dev_sst_cloud_data search
*
* @access public
*/
 function search_dev_sst_cloud_data(&$out) {
  require(DIR_MODULES.$this->name.'/dev_sst_cloud_data_search.inc.php');
 }
/**
* dev_sst_cloud_data edit/add
*
* @access public
*/
 function edit_dev_sst_cloud_data(&$out, $id) {
  require(DIR_MODULES.$this->name.'/dev_sst_cloud_data_edit.inc.php');
 }
 
 function cloud_get_all($upd='all') {
  require(DIR_MODULES.$this->name.'/dev_sst_cloud_get.inc.php');
 }
 
 function propertySetHandle($object, $property, $value) {
   $this->getConfig();
   $table='dev_sst_cloud_data';
   $properties=SQLSelect("SELECT ID, TITLE, DEVICE_ID FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
		$id=$properties[$i]['DEVICE_ID'];
		$device=SQLSelectOne("SELECT CLOUD_ID, HOUSE FROM dev_sst_cloud_devices WHERE ID='".$id."'");
		$host='https://api.sst-cloud.com/houses/'.$device['HOUSE'].'/devices/'.$device['CLOUD_ID'] .'/';
     switch ($properties[$i]['TITLE']) {
		 case 'mode':
			$host.='mode/';
			break;
		 case 'status':
			$host.='status/';
			break;
		 case 'temperature_air':
		 case 'temperature_manual':
		 case 'temperature_correction_air':
			$host.='temperature/';
			break;			
	 }
		$post = [
			$properties[$i]['TITLE'] => $this->metricsModify($properties[$i]['TITLE'], $value, 'to_device'),
		];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "$host");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		//curl_setopt($ch, CURLOPT_COOKIE, 'sessionid='.$this->config['SESSIONID'].'; csrftoken='.$this->config['APITOKEN']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Accept: application/json', 
				'X-CSRFToken: '.$this->config['APITOKEN'],
				'Authorization: Token '.$this->config['APIKEY']));
		//curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		curl_close($ch);
    }
   }
 }
 
 
  function metricsModify($param, $val, $out) {
	if($out=='to_device') { 
		if($param=='status' || $param=='relay_status') {
			$val=($val)? 'on' : 'off';
		} 
	} elseif($out=='from_device') {
		if($param=='status' || $param=='relay_status') {
			$val=($val=='on')? 1 : 0;
		} 
	}
	return $val;
 }
 
 
 function processCycle() {
  $this->cloud_get_all('devices');
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS dev_sst_cloud_devices');
  SQLExec('DROP TABLE IF EXISTS dev_sst_cloud_data');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
dev_sst_cloud_devices - 
dev_sst_cloud_data - 
*/
  $data = <<<EOD
 dev_sst_cloud_devices: ID int(10) unsigned NOT NULL auto_increment
 dev_sst_cloud_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 dev_sst_cloud_devices: CLOUD_ID varchar(255) NOT NULL DEFAULT ''
 dev_sst_cloud_devices: TYPE varchar(255) NOT NULL DEFAULT ''
 dev_sst_cloud_devices: HOUSE varchar(255) NOT NULL DEFAULT ''
 dev_sst_cloud_devices: GROUP varchar(255) NOT NULL DEFAULT ''
 dev_sst_cloud_devices: UPDATED datetime
 dev_sst_cloud_data: ID int(10) unsigned NOT NULL auto_increment
 dev_sst_cloud_data: TITLE varchar(100) NOT NULL DEFAULT ''
 dev_sst_cloud_data: VALUE varchar(255) NOT NULL DEFAULT ''
 dev_sst_cloud_data: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 dev_sst_cloud_data: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 dev_sst_cloud_data: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 dev_sst_cloud_data: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 dev_sst_cloud_data: UPDATED datetime
 dev_sst_cloud_houses: ID int(10) unsigned NOT NULL auto_increment
 dev_sst_cloud_houses: TITLE varchar(100) NOT NULL DEFAULT ''
 dev_sst_cloud_houses: HOUSE_ID varchar(255) NOT NULL DEFAULT ''
 dev_sst_cloud_houses: OWNER varchar(255) NOT NULL DEFAULT ''
 dev_sst_cloud_groups: ID int(10) unsigned NOT NULL auto_increment
 dev_sst_cloud_groups: TITLE varchar(100) NOT NULL DEFAULT ''
 dev_sst_cloud_groups: GROUP_ID varchar(255) NOT NULL DEFAULT ''
 dev_sst_cloud_groups: HOUSE_ID varchar(255) NOT NULL DEFAULT ''
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDI5LCAyMDE5IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
