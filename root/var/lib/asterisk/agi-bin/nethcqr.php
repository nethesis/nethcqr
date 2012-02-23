#!/usr/bin/php
<?php
define("AGIBIN_DIR", "/var/lib/asterisk/agi-bin");
define("AMPORTAL_CONF", "/etc/amportal.conf");
define("TESTING", true);
$debug=true;
include(AGIBIN_DIR."/phpagi.php");

require_once('DB.php');

$agi = new AGI();
nethcqr_debug("NethCQR AGI script started!");

$amp_conf=parse_amportal_conf(AMPORTAL_CONF);

$id_cqr = $argv[1];

if ($id_cqr == '') {
	nethcqr_debug("ERROR: id_cqr cannot be empty!");
	exit (1);
}

//Setup database connection:
$db_user = $amp_conf["AMPDBUSER"];
$db_pass = $amp_conf["AMPDBPASS"];
$db_host = 'localhost';
$db_name = 'asterisk';
$db_engine = 'mysql';
$datasource = $db_engine.'://'.$db_user.':'.$db_pass.'@'.$db_host.'/'.$db_name;
$db = new DB();
//$db->connect($datasource);// attempt connection
$db = DB::connect($datasource);
if($db->isError($db)) {
	nethcqr_debug("Error conecting to asterisk database, skipped".$db->getMessage());		
} else {
	nethcqr_debug($db);
	nethcqr_debug(print_r($db,true));
	$cqr = nethcqr_get_details($id_cqr);
}

$variables = array (
                'DATE' => date("Y-m-d G:i:s"),
                'CID' => $agi->request['agi_callerid'],
                );

//USE CODE? 
if ($cqr['use_code']){
	//TODO QUERY FOR CODE 
	if ($variables['CID'] == 0) 
		$variables['CODCLI'] = 0;
	else 
		$variables['CODCLI'] = check_tel($variables['CID']);
	
	//MANUAL CODE
	if ($variables['CODCLI']==0 && $cqr['manual_code'])
		$variables['CODCLI'] = nethcqr_codcli($cqr['code_retry']);
}

$entries = nethcqr_get_entries($id_cqr);

if (!isset($variables['CODCLI']) || $variables['CODCLI'] === 0){ 
	//cliente non trovato. 
	//TODO $variables['CODCLI']=$variables['CID']	?	
	nethcqr_goto_destination($cqr['default_destination'],2);
} else { //CODCLI != 0
	$cqr['query'] = nethcqr_evaluate ($cqr['query'],$variables);
	//setup db connection with user defined data
	$datasource = $cqr['db_type'].'://'.$cqr['db_user'].':'.$cqr['db_pass'].'@'.$cqr['db_url'].'/'.$cqr['db_name'];
	$user_defined_db = new DB();
	$db->connect($datasource);// attempt connection to user defined database;
	if($db->isError($user_defined_db)) {
		nethcqr_debug(__FUNCTION__." error: ".$user_defined_db->getMessage());
		nethcqr_goto_destination($cqr['default_destination'],1);
	} else { //'USER DB CONNECTED'
		$cqr_query_results = $user_defined_db->getCol($cqr['query']); //we expect one column from our query
		if($db->isError($cqr_query_results)) {
                	nethcqr_debug(__FUNCTION__." error: ".$cqr_query_results->getMessage());
			nethcqr_goto_destination($cqr['default_destination'],1);
        	} else //USER QUERY EXECUTED 
			foreach ($cqr_query_results as $cqr_query_result) //search for equal condition in $entries
				foreach ($entries as $entrie)
					if ($cqr_query_results === $entrie['condition']) //WIN
						nethcqr_goto_destination($entrie['destination']);
	}  //END 'USER DB CONNECTED'
} //END 'CODCLI != 0' 

########################################################################################################################################################

function parse_amportal_conf($filename) {
        $file = file($filename);
        foreach ($file as $line) {
                if (preg_match("/^\s*([a-zA-Z0-9]+)\s*=\s*(.*)\s*([;#].*)?/",$line,$matches)) {
                        $conf[ $matches[1] ] = $matches[2];
                }
        }
        return $conf;
}

function agiexit($prio) {
        global $agi;
        $agi->set_priority($prio);
        exit(0);
}

function get_id($tstamp=0,$tn=0) {
//TODO
/*    global $db_ot;
    if ($tstamp==0)
         $query = "Select max(id) from ticket";
    else
         $query = "Select id from ticket where create_time_unix=$tstamp and tn='$tn'";
    nethcqr_debug("$query $tstamp $tn",1);
    $row = $db_ot->getAll($query);
    if (count($row) == 1) {
        $ticket_id = $row[0][0];
    } else {
        nethcqr_debug("Impossibile ottenere crmid",1);
        exit(0);
    }
    return $ticket_id;*/
}

function nethcqr_get_details($id_cqr=false){
	global $db;
        $id_cqr = mysql_real_escape_string($id_cqr);
        $sql = "SELECT * FROM `nethcqr_details`";
        if ($id_cqr) $sql .=" WHERE `id_cqr` = '$id_cqr'";
	$results =& $db->getAll($sql,DB_FETCHMODE_ASSOC);
        if ($db->isError($results)){
		nethcqr_debug(__FUNCTION__." error: ".$results->getMessage());
                return false;
        }
        return $results[0];
}

function check_pin($codcli) {
//TODO
/*    global $db_ot;
    $query = "Select name,city from customer_company WHERE customer_id=$codcli";
    nethcqr_debug("$query",1);
    $row = $db_ot->getAll($query);
    if (count($row) > 0) {
        return $row[0][0]." (".$row[0][1].")";
    } else {
        return "";
    }*/
}

function check_tel($number) {
//TODO
/*    global $db_ot;
    $query = "Select customer_id from customer_custom WHERE value='$number' limit 1";
    $cidresult=$db_ot->getAll($query);
    if (count($cidresult) > 0) {
        return $cidresult[0][0];
    } else {
        return 0;
    }*/
}

function nethcqr_menu($file,$options,$tries=3) {
    global $agi;
    $choice = NULL;
    $nt=1;
    while(is_null($choice) and $nt <= $tries) {
        $ret = $agi->get_data($file,4000,1);
        $nt++;
        if($ret['code'] != AGIRES_OK || $ret['result'] == -1)
           $choice = -1;
        elseif($ret['result'] != '' and $ret['result']<=$options)
           $choice = $ret['result'];
     }
     nethcqr_debug("neth-menu: ".$choice);
     if ($choice >=1 and $choice<=$options)
             return $choice;
     else
             return -1;
}

function nethcqr_codcli($tries=3) {
    global $agi;
    global $cliente;
    $try=1;
    $pinchr='';
    $codcli='';
    while($try <= $tries) {
    # riproduco il messaggio, mi fermo se sento un numero
        $pin=$agi->stream_file("custom/benvenutocodice",'1234567890#');
        if ($pin['result'] >0)
                $codcli=chr($pin['result']);
    # ciclo in attesa di numeri (codcli) fino a che non viene messo #
        while($pinchr != "#") {
            $pin = $agi->wait_for_digit("6000");
            $pinchr=chr($pin['result']);
            if ($pin['code'] != AGIRES_OK || $pin['result'] <= 0 ) { #non funziona dtmf, vado avanti 
                 return false;
            } elseif ($pinchr >= "0" and $pinchr <= "9") {
                $codcli = $codcli.$pinchr;
            }
	nethcqr_debug("Codcli: ".$pin['result']."-".$pin['code']."-".$codcli,1);
        }

        $cliente = check_pin($codcli);
        if ($cliente == "") { # codice inserito, ma inesistente
            $agi->stream_file("custom/cod_errato"); # codice errato o inesistente
            $pin=0;
            $pinchr='';
            $codcli='';
        } else {
            return $codcli; # codcli OK, esco
        }
        $try++;
    }
    return false;
}

function nethcqr_debug($text) {
    global $agi;
    global $debug;
    if ($debug)
        $agi->verbose($text);
    if (TESTING)
	echo "$text\n";
}

function nethcqr_evaluate($msg,$variables=false){
	//$variables = array ('VAR_NAME_IN_MSG' => var_value, ....)
	//VAR_NAME_IN_MSG : NAME, PIPPO,FOOBAR
	//$msg example: "SELECT * FROM '%TABLE%'"
	//var_value : 'pippo'
	//expected return: "SELECT * FROM pippo"
	if (!$variables) return $msg;
	foreach ($variables as $variable_name => $variable_value ){
		echo $variable[0];		
		$msg = preg_replace('/\'%'.$variable_name.'%\'/',$variable_value,$msg);
	}
	return $msg;

}

function nethcqr_query($id_cqr,$variables=false){
	$query = nethcqr_get_query($id_cqr);
	
}

function nethcqr_get_entries($id_cqr){
	global $db;
	$sql = "SELECT * FROM nethcqr_entries WHERE `id_cqr`='$id_cqr' ORDER BY `position` ASC";
	$entries = $db->getAll($sql, DB_FETCHMODE_ASSOC); 
	if($db->IsError($entries)){
        	nethcqr_debug(__FUNCTION__." error ".$entries->getMessage());
		return false;
                }
        return $entries;
}

function nethcqr_goto_destination($destination,$exit=0){
	global $agi;
	nethcqr_debug(__FUNCTION__." goto $destination");
	$agi->exec_goto($destination);	
	exit($exit);
}





