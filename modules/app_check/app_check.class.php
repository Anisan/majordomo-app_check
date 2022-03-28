<?php
/**
* Check 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 13:07:24 [Jul 20, 2017])
*/
//
//
class app_check extends module {
/**
* app_check
*
* Module class constructor
*
* @access private
*/
function app_check() {
  $this->name="app_check";
  $this->title="Check";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
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
 $out['API_USERNAME']=$this->config['API_USERNAME'];
 $out['API_PASSWORD']=$this->config['API_PASSWORD'];
 if ($this->view_mode=='update_settings') {
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;
   $this->saveConfig();
   $this->redirect("?");
 }
 if ($this->mode=='update_checks') {
    $this->updateChecks();
    //$this->redirect("?");
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='ch_checks' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_ch_checks') {
   $this->search_ch_checks($out);
  }
  if ($this->view_mode=='edit_ch_checks') {
   $this->edit_ch_checks($out, $this->id);
  }
  if ($this->view_mode=='delete_ch_checks') {
   $this->delete_ch_checks($this->id);
   $this->redirect("?data_source=ch_checks");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='ch_items') {
  if ($this->view_mode=='' || $this->view_mode=='search_ch_items') {
   $this->search_ch_items($out);
  }
  if ($this->view_mode=='edit_ch_items') {
   $this->edit_ch_items($out, $this->id);
  }
 }
}

function updateChecks() {
    $url="https://lkdr.nalog.ru/api/v1/receipt";
    $content = [];
    $content['dateFrom'] = null;
    $content['dateTo'] = null;
    $content['inn'] = null;
    $content['kktOwner'] = null;
    $content['limit'] = 10;
    $content['offset'] = 0;
    $content['orderBy'] = "RECEIVE_DATE:DESC";

    $res = $this->getData($url, $content);

    $data = json_decode($res,true);
    //registerError('check_app', $res);
    //$res = getURL($url.$data['url'],0,$this->config['API_USERNAME'],$this->config['API_PASSWORD']);
    //$data = json_decode($res,true);
    foreach($data["receipts"] as $check) {
        //$check = $doc["document"]["receipt"];
        //echo $check["buyer"];
        $find = SQLSelectOne("SELECT * FROM ch_checks WHERE fiscalSign='" . $check['key'] . "';");
        if (!$find)
        {
            $url = "https://lkdr.nalog.ru/api/v1/receipt/fiscal_data";
            $content = [];
            $content["key"] = $check["key"];
            $fiscal = $this->getData($url, $content);
            $fiscal = json_decode($fiscal,true);
    
            $rec = array();
            $rec['dateTime'] = $check['createdDate'];
            $rec['fiscalSign'] = $check["key"];
            $rec['user'] = $fiscal["retailPlace"];
            $rec['operator'] = $fiscal["operator"];
            $rec['retailPlaceAddress'] = $fiscal["retailPlaceAddress"];
            $rec['totalSum'] = $check["totalSum"];
            $rec['id'] = SQLInsert("ch_checks", $rec);
            
            
            $items = $fiscal["items"];
            foreach($items as $item) {
                $item_rec = array();
                $item_rec['id_check'] = $rec['id'];
                $item_rec['name'] = $item['name'];
                $item_rec['price'] = $item['price'];
                $item_rec['quantity'] = $item['quantity'];
                $item_rec['sum'] = $item['sum'];
                SQLInsert("ch_items", $item_rec);
                
            }
    
        }
    }
        

}

function getData($url, $content)
{
    $token = $this->config['API_USERNAME'];
    $ch = curl_init($url);
    // Returns the data/output as a string instead of raw data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //Set your auth headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
       'Content-Type: application/json',
       'Authorization: Bearer ' . $token,
       'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.109 Safari/537.36'
       ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
    // get stringified data/output. See CURLOPT_RETURNTRANSFER
    $res = curl_exec($ch);
    DebMes($res,'check_app');
    
    // get info about the request
    $info = curl_getinfo($ch);
    // close curl resource to free up system resources
    curl_close($ch);
    return $res;
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
* ch_checks search
*
* @access public
*/
 function search_ch_checks(&$out) {
  require(DIR_MODULES.$this->name.'/ch_checks_search.inc.php');
 }
/**
* ch_checks edit/add
*
* @access public
*/
 function edit_ch_checks(&$out, $id) {
  require(DIR_MODULES.$this->name.'/ch_checks_edit.inc.php');
 }
/**
* ch_checks delete record
*
* @access public
*/
 function delete_ch_checks($id) {
  $rec=SQLSelectOne("SELECT * FROM ch_checks WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM ch_items WHERE id_check='".$rec['ID']."'");
  SQLExec("DELETE FROM ch_checks WHERE ID='".$rec['ID']."'");
 }
/**
* ch_items search
*
* @access public
*/
 function search_ch_items(&$out) {
  require(DIR_MODULES.$this->name.'/ch_items_search.inc.php');
 }
/**
* ch_items edit/add
*
* @access public
*/
 function edit_ch_items(&$out, $id) {
  require(DIR_MODULES.$this->name.'/ch_items_edit.inc.php');
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
  SQLExec('DROP TABLE IF EXISTS ch_checks');
  SQLExec('DROP TABLE IF EXISTS ch_items');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data = '') {
/*
ch_checks - 
ch_items - 
*/
  $data = <<<EOD
 ch_checks: id int(10) unsigned NOT NULL auto_increment
 ch_checks: fiscalSign varchar(255) DEFAULT ''
 ch_checks: dateTime datetime
 ch_checks: user varchar(255) DEFAULT ''
 ch_checks: operator varchar(255) NOT NULL DEFAULT ''
 ch_checks: retailPlaceAddress varchar(255) NOT NULL DEFAULT ''
 ch_checks: totalSum float DEFAULT '0'
 
 ch_items: id int(10) unsigned NOT NULL auto_increment
 ch_items: id_check int(10) NOT NULL DEFAULT '0'
 ch_items: name varchar(255) NOT NULL DEFAULT ''
 ch_items: price float DEFAULT '0'
 ch_items: quantity float DEFAULT '0'
 ch_items: sum float DEFAULT '0'
 
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgSnVsIDIwLCAyMDE3IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
