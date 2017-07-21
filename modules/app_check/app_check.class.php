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
    $url = "http://proverkacheka.nalog.ru:8888";
    $res = getURL($url."/v1/extract?sendToEmail=0&fileType=json",0,$this->config['API_USERNAME'],$this->config['API_PASSWORD']);
    $data = json_decode($res,true);
    $res = getURL($url.$data['url'],0,$this->config['API_USERNAME'],$this->config['API_PASSWORD']);
    $data = json_decode($res,true);
    foreach($data as $doc) {
        $check = $doc["document"]["receipt"];
        //echo $check["user"];
        $find = SQLSelectOne("SELECT * FROM ch_checks WHERE fiscalSign='" . $check['fiscalSign'] . "';");
        if (!$find)
        {
            $rec = array();
            $rec['dateTime'] = $check['dateTime'];
            $rec['fiscalSign'] = $check["fiscalSign"];
            $rec['user'] = $check["user"];
            $rec['operator'] = $check["operator"];
            $rec['retailPlaceAddress'] = $check["retailPlaceAddress"];
            $rec['totalSum'] = $check["totalSum"];
            $rec['id'] = SQLInsert("ch_checks", $rec);
            $items = $check["items"];
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
 ch_checks: totalSum int(10) DEFAULT '0'
 
 ch_items: id int(10) unsigned NOT NULL auto_increment
 ch_items: id_check int(10) NOT NULL DEFAULT '0'
 ch_items: name varchar(255) NOT NULL DEFAULT ''
 ch_items: price int(10) DEFAULT '0'
 ch_items: quantity int(3) DEFAULT '0'
 ch_items: sum int(10) DEFAULT '0'
 
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
