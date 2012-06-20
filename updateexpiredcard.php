<?php
/**
 * go through all memberships and update any expired cards
 *
 * @author Tanguy de Courson <tanguy@0x7a69.com>
 **/

define( 'NETBILLING_API_URL', 'https://secure.netbilling.com:1402/gw/sas/direct3.0');
define( 'NETBILLING_ACCOUNT_ID', '110047505390' );
define('NETBILLING_CONTROL_KEYWORD', 'BLAH');
define('NETBILLING_TRANSACTION_API_URL', 'https://secure.netbilling.com/gw/reports/transaction1.5');
define('NETBILLING_MEMBER_API_URL', 'https://secure.netbilling.com/gw/reports/member1.5');
define('NETBILLING_MEMBER_UPDATE_API_URL', 'https://secure.netbilling.com/gw/native/mupdate1.1');


//get all the transactions and store member id and expiration date
//

$expmembers = get_expiredcard_transactions();
//now have array with teh key member id and the value the transactions id
//get all members and check for ones in our expmembers array

$updatemembers = get_members_to_update($expmembers);
foreach($updatemembers as $mem_id => $trans_id)
{
	//preauth $1
	//post to member update api with new template id
	update_member($mem_id, $new_trans_id);
}

function update_member($mem_id, $new_trans_id)
{
	$namevalue_pairs = array(
		'C_ACCOUNT'=>NETBILLING_ACCOUNT_ID,
		'C_MEMBER_ID'=>$mem_id,
		'C_MEMBER_LOGIN'=>TODO,
		'C_CONTROL_KEYWORD'=>NETBILLING_CONTROL_KEYWORD,
		'C_COMMAND'=>'SET',
		'C_WRITABLE_FIELDS'=>'R_TEMPLATE_TRANS_ID',
		'R_TEMPLATE_TRANS_ID'=>$new_trans_id
	);
	$curl = curl_init( NETBILLING_TRANSACTION_API_URL );
	curl_setopt( $curl, CURLOPT_POST, 1 );
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $namevalue_pairs );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
	curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 1 );
	//netbilling tells us things via header unfortunately
	curl_setopt( $curl, CURLOPT_HEADER, 1 );
	$data = curl_exec( $curl );
	list($header, $content) = preg_split("/\r\n\r\n/", $data, 2);
	$header_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	curl_close( $curl );
}
function get_expiredcard_transactions()
{
	$namevalue_pairs = array(
		'account_id'=>NETBILLING_ACCOUNT_ID,
		'expire_before'	=> date("Y-m-d")
	);
	$curl = curl_init( NETBILLING_TRANSACTION_API_URL );
	curl_setopt( $curl, CURLOPT_POST, 1 );
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $namevalue_pairs );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
	curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 1 );
	//netbilling tells us things via header unfortunately
	curl_setopt( $curl, CURLOPT_HEADER, 1 );
	$data = curl_exec( $curl );
	list($header, $content) = preg_split("/\r\n\r\n/", $data, 2);
	$header_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	curl_close( $curl );
	$return = array();
	//$return['http_code'] = $header_code;
	//$return['response'] = $content;
	$ret = arary();
	foreach(explode("\n", $content) as $line)
	{
			$row = str_getcsv($line);
			$expdate = $row[11];
			$member_id = $row[6];
			if($expdate < date("my")
			{
				$ret[$member_id] = $row[0];
			}
	}
	return $ret;
}
function get_members_to_update($expmembers)
{	
	$namevalue_pairs = array(
		'account_id'=>NETBILLING_ACCOUNT_ID,
		'expire_before'	=> date("Y-m-d")
	);
	$curl = curl_init( NETBILLING_MEMBER_API_URL );
	curl_setopt( $curl, CURLOPT_POST, 1 );
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $namevalue_pairs );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
	curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 1 );
	//netbilling tells us things via header unfortunately
	curl_setopt( $curl, CURLOPT_HEADER, 1 );
	$data = curl_exec( $curl );
	list($header, $content) = preg_split("/\r\n\r\n/", $data, 2);
	$header_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	curl_close( $curl );
	$update_status = array('BROKEN: INVALID TEMPLATE ID', 'BROKEN: TOO MANY FAILURES', 'RUNNING: INVALID TEMPLATE ID', 'RUNNING: RETRYING FAILED ATTEMPT', 'RUNNING: RETRYING AFTER DECLINE', 'RUNNING: RETRYING AFTER EXCEPTION', 'STOPPED: INVALID TEMPLATE ID');
	$ret  = array();
	foreach(explode("\n", $content) as $line)
	{
			$row = str_getcsv($line);
			if($expmembers[$row[0]] && $update_status[$row[9])
			{
				$ret[$row[0]] = $expmembers[$row[0]];
			}
	}
	return $ret;
}
