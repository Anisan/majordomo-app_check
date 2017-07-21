<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['ch_checks_qry'];
  } else {
   $session->data['ch_checks_qry']=$qry;
  }
  if (!$qry) $qry="";
  $sortby_ch_checks="ID DESC";
  $out['SORTBY']=$sortby_ch_checks;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM ch_checks");
  $total=count($res);
  print_r($total);
  if ($total>0) {
   //paging($res, 100, $out); // search result paging
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
    $res[$i]["totalSum"] = $res[$i]["totalSum"] / 100;
   }
   echo $res;
   $out['RESULT']=$res;
  }
