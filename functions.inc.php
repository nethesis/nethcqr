<?php
#return a list of saved query routes
function cqr_list(){
	global $db;
	$sql = 'SELECT id,name,description,db_type,db_url,db_name,db_user,db_pass,query FROM cqr';
	$results = $db->getAll($sql);
        if(DB::IsError($results)) {
                $results = null;
        }
	foreach ($results as $val) {
                $tmparray[] = array('id' => $val[0],'name' => $val[1],'description' => $val[2],'db_type' => $val[3],'db_url' => $val[4],'db_name' => $val[5],'db_user' => $val[6],'db_pass,query' => $val[7]);
        }
        return $tmparray;
}
#add a new route
function cqr_add($cqr_entry){
	global $db;
	$name=$cqr_entry['name'];
	$description=$cqr_entry['description'];
	$db_type=$cqr_entry['db_type'];
	$db_url=$cqr_entry['db_url'];
	$db_name=$cqr_entry['db_name'];
	$db_user=$cqr_entry['db_user'];
	$db_pass=$cqr_entry['db_pass'];
	$query=$cqr_entry['query'];
	$sql = "INSERT INTO cqr (name,description,db_type,db_url,db_name,db_user,db_pass,query) VALUES ('$name','$description','$db_type','$db_url','$db_name','$db_user','$db_pass','$query')";
	echo $sql;
	$db->query($sql);
}
#delete existing route
function cqr_del($id){
	global $db;
	$sql = "DELETE FROM cqr WHERE id = '$id'";
	echo $sql;
	$db->query($sql);
}
#modify existing route
function cqr_edit(){
	echo "TEST";
}
#provides destinations that other modules can use
function cqr_destination(){
	return array(
	    array(
	      'destination' => 'app-blackhole,hangup,1', 
	      'description' => 'Hangup',
	    ),
	    array(
	      'destination' => 'app-blackhole,congestion,1', 
	      'description' => 'Congestion'
	    ),
	 );
}

function cqr_get_config($engine) {
  global $ext;
  
  switch($engine) {
    case 'asterisk':
      // "blackhole" destinations
      $ext->add('app-blackhole', 'hangup', '', new ext_noop('Blackhole Dest: Hangup'));
      $ext->add('app-blackhole', 'hangup', '', new ext_hangup());

      $ext->add('app-blackhole', 'congestion', '', new ext_noop('Blackhole Dest: Congestion'));
      $ext->add('app-blackhole', 'congestion', '', new ext_answer());
      $ext->add('app-blackhole', 'congestion', '', new ext_playtones('congestion'));
      $ext->add('app-blackhole', 'congestion', '', new ext_congestion());
      $ext->add('app-blackhole', 'congestion', '', new ext_hangup());
    break;
  }
}



?>

