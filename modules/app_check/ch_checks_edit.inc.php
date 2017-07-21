<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='ch_checks';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  $rec["totalSum"] = $rec["totalSum"] / 100;
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
  
   global $name;
   $rec['name']=$name;
   if ($rec['name']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }
  }
  // step: data
  if ($this->tab=='data') {
  }
  //UPDATING RECORD
   if ($ok) {
    if ($rec['id']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['id']=SQLInsert($table_name, $rec); // adding new record
    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  }
  // step: data
  if ($this->tab=='data') {
  }
  if ($this->tab=='data') {
   //dataset2
   $new_id=0;
   global $delete_id;
   if ($delete_id) {
    SQLExec("DELETE FROM ch_items WHERE ID='".(int)$delete_id."'");
   }
   $properties=SQLSelect("SELECT * FROM ch_items WHERE id_check='".$rec['id']."' ORDER BY ID");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    $properties[$i]["sum"] = $properties[$i]["sum"] / 100;
    $properties[$i]["price"] = $properties[$i]["price"] / 100;
   }
   $out['PROPERTIES']=$properties;   
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);
