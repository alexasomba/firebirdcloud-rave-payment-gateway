<?php
/**
 * For Payments
 * 
 * PHP version 7
 * 
 * @category Payment
 * @package  Memcachier
 * @author   Alexander Asomba <alex@asomba.com>
 * @license  MIT License
 * @link     http://alexasomba.com
 */

require 'vendor/autoload.php';

// create a new persistent client
$m = new Memcached("memcached_pool");
$m->setOption(Memcached::OPT_BINARY_PROTOCOL, TRUE);

// some nicer default options
// - nicer TCP options
$m->setOption(Memcached::OPT_TCP_NODELAY, TRUE);
$m->setOption(Memcached::OPT_NO_BLOCK, FALSE);
// - timeouts
$m->setOption(Memcached::OPT_CONNECT_TIMEOUT, 2000);    // ms
$m->setOption(Memcached::OPT_POLL_TIMEOUT, 2000);       // ms
$m->setOption(Memcached::OPT_RECV_TIMEOUT, 750 * 1000); // us
$m->setOption(Memcached::OPT_SEND_TIMEOUT, 750 * 1000); // us
// - better failover
$m->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
$m->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, TRUE);
$m->setOption(Memcached::OPT_RETRY_TIMEOUT, 2);
$m->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 1);
$m->setOption(Memcached::OPT_AUTO_EJECT_HOSTS, TRUE);

// setup authentication
$m->setSaslAuthData( getenv("MEMCACHIER_USERNAME")
                   , getenv("MEMCACHIER_PASSWORD") );

// We use a consistent connection to memcached, so only add in the
// servers first time through otherwise we end up duplicating our
// connections to the server.
if (!$m->getServerList()) {
    // parse server config
    $servers = explode(",", getenv("MEMCACHIER_SERVERS"));
    foreach ($servers as $s) {
        $parts = explode(":", $s);
        $m->addServer($parts[0], $parts[1]);
    }
}

ini_set('session.save_handler', 'memcached');
ini_set('session.save_path', getenv('MEMCACHIER_SERVERS'));
if(version_compare(phpversion('memcached'), '3', '>=')) {
    ini_set('memcached.sess_persistent', 1);
    ini_set('memcached.sess_binary_protocol', 1);
} else {
    ini_set('session.save_path', 'PERSISTENT=myapp_session ' . ini_get('session.save_path'));
    ini_set('memcached.sess_binary', 1);
}
ini_set('memcached.sess_sasl_username', getenv('MEMCACHIER_USERNAME'));
ini_set('memcached.sess_sasl_password', getenv('MEMCACHIER_PASSWORD'));

// Enable MemCachier session support
session_start();
$_SESSION['test'] = 42;

require "functions.php";//file which has required functions
?><html>
<head><title>Payment Page </title>

</head>
<body bgcolor="white">

<?php

$key = getenv('SECURE-RESELLER-PAYMENT-KEY'); //replace ur 32 bit secure key , Get your secure key from your Reseller Control panel

//This filter removes data that is potentially harmful for your application. It is used to strip tags and remove or encode unwanted characters.
$_GET = filter_var_array($_GET, FILTER_SANITIZE_STRING);

//Below are the  parameters which will be passed from foundation as http GET request
$paymentTypeId   = $_GET["paymenttypeid"]; //payment type id
$transId         = $_GET["transid"]; //This refers to a unique transaction ID which we generate for each transaction
$userId          = $_GET["userid"]; //userid of the user who is trying to make the payment
$userType        = $_GET["usertype"]; //This refers to the type of user perofrming this transaction. The possible values are "Customer" or "Reseller"
$transactionType = $_GET["transactiontype"]; //Type of transaction (ResellerAddFund/CustomerAddFund/ResellerPayment/CustomerPayment)

$invoiceIds   = $_GET["invoiceids"]; //comma separated Invoice Ids, This will have a value only if the transactiontype is "ResellerPayment" or "CustomerPayment"
$debitNoteIds = $_GET["debitnoteids"]; //comma separated DebitNotes Ids, This will have a value only if the transactiontype is "ResellerPayment" or "CustomerPayment"

$description = $_GET["description"];

$sellingCurrencyAmount    = $_GET["sellingcurrencyamount"]; //This refers to the amount of transaction in your Selling Currency
$accountingCurrencyAmount = $_GET["accountingcurrencyamount"]; //This refers to the amount of transaction in your Accounting Currency

$redirectUrl = $_GET["redirecturl"]; //This is the URL on our server, to which you need to send the user once you have finished charging him

$checksum = $_GET["checksum"]; //checksum for validation

//Other variables.

$name                = $_GET['name'];
$company             = $_GET['company'];
$emailAddr           = $_GET['emailAddr'];
$address1            = $_GET['address1'];
$address2            = $_GET['address2'];
$address3            = $_GET['address3'];
$city                = $_GET['city'];
$state               = $_GET['state'];
$country             = $_GET['country'];
$zip                 = $_GET['zip'];
$telNoCc             = $_GET['telNoCc'];
$telNo               = $_GET['telNo'];
$faxNoCc             = $_GET['faxNoCc'];
$faxNo               = $_GET['faxNo'];
$resellerEmail       = $_GET['resellerEmail'];
$resellerURL         = $_GET['resellerURL'];
$resellerCompanyName = $_GET['resellerCompanyName'];

if (verifyChecksum($paymentTypeId, $transId, $userId, $userType, $transactionType, $invoiceIds, $debitNoteIds, $description, $sellingCurrencyAmount, $accountingCurrencyAmount, $key, $checksum)) {
	//YOUR CODE GOES HERE

	/**
	 * since all these data has to be passed back to foundation after making the payment you need to save these data
	 *
	 * You can make a database entry with all the required details which has been passed from foundation.
	 *
	 *							OR
	 *
	 * keep the data to the session which will be available in postpayment.php as we have done here.
	 *
	 * It is recommended that you make database entry.
	 **/

	$_SESSION['redirecturl']             = $redirectUrl;
	$_SESSION['transid']                 = $transId;
	$_SESSION['sellingcurrencyamount']   = $sellingCurrencyAmount;
	$_SESSION['accountingcurencyamount'] = $accountingCurrencyAmount;
	
	//Keep more data on session
	$_SESSION['emailAddr'] 	= $emailAddr;
	$_SESSION['name'] 	= $name;
	$_SESSION['telNo'] 	= $telNo;
	$_SESSION['txref'] 	= $transId;

	//Insert the values into the database.
/** 
	if ($conn = mysqli_connect("db", "user", "password", "table")) { // replace the DB parameters with yours
		if ($query = mysqli_query($conn, "INSERT INTO records_r_ng (paymenttypeid, transid, userid, usertype, transactiontype, invoiceids, debitnoteids, description, sellingcurrencyamount, accountingcurrencyamount, redirecturl, checksum, name, company, emailAddr, address1, address2, address3, city, state, country, zip, telNoCc, telNo, faxNoCc, faxNo, resellerEmail, resellerURL, resellerCompanyName) VALUES ('$paymentTypeId', '$transId', '$userId', '$userType', '$transactionType', '$invoiceIds', '$debitNoteIds', '$description', '$sellingCurrencyAmount', '$accountingCurrencyAmount', '$redirectUrl', '$checksum', '$name', '$company', '$emailAddr','$address1', '$address2', '$address3', '$city', '$state', '$country', '$zip', '$telNoCc', '$telNo', '$faxNoCc', '$faxNo', '$resellerEmail', '$resellerURL', '$resellerCompanyName')")) {
			//Do nothing it worked.
		} else {
			die("Failed to record data".mysqli_error($conn));
		}
	} else {
		die("Could not connect to mysql");
	} // replace the DB parameters with yours
	*/
?>
<script type="text/javascript" src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function(event) {
	addEventListener("load", function(e) {
		var PBFKey = "<?php $envPBFKey = getenv('RAVE-PUBLIC-KEY'); echo $envPBFKey; ?>"; // replace with your rave public key, Get your public key from your rave account's dashboard
		getpaidSetup({
			PBFPubKey: PBFKey,
			amount: <?php $sellingCurrencyAmount = $_GET['sellingcurrencyamount']; echo $sellingCurrencyAmount;?>,
			country: "<?php $envCountry = getenv('COUNTRY'); echo $envCountry;?>", // replace with your country
			currency: "<?php $envCurrency = getenv('CURRENCY'); echo $envCurrency;?>", // replace with your currency
			custom_logo: "<?php $envCustomLogo = getenv('CUSTOM-LOGO-URL'); echo $envCustomLogo;?>", // replace with link to your custom logo
			custom_title: "<?php $envCustomTitle = getenv('CUSTOM-MODAL-TITLE'); echo $envCustomTitle;?>", // replace with link to your custom tittle
			custom_description: "<?php $envCustomDescription = getenv('CUSTOM-MODAL-DESCRIPTION'); echo $envCustomDescription;?>", // replace with your custom description
			customer_email: "<?php $emailAddr = $_GET['emailAddr']; echo $emailAddr;?>",
			customer_firstname: "<?php $name = $_GET['name']; echo $name;?>",
			customer_phone: "<?php $telNo = $_GET['telNo']; echo $telNo;?>",
			txref: "<?php $transId = $_GET['transid']; echo $transId;?>",
			//integrity_hash: "<?php include 'integrityhash.php';?>", // commented out due to error
			onclose: function closeCurrentWindow() {
				window.close();
			},
			callback: function(response) {
        			var flw_ref = response.tx.flwRef; // collect flwRef returned and pass to a server page to complete status check.
        			console.log("This is the response returned after a charge", response);
        			if (response.tx.chargeResponseCode == "00" || response.tx.chargeResponseCode == "0") {
          			// redirect to a success page:
          			window.location = "postpayment.php?status=Y";
        				} else {
          					// redirect to a failure page:
          					window.location = "postpayment.php?status=N";          			
        					}
      					}
		});
	});
});
</script>

	<?php

} else {
    /** 
    * This message will be dispayed in any of the following case
    *
	* 1. You are not using a valid 32 bit secure key from your Reseller Control panel
	* 2. The data passed from foundation has been tampered.
	 *
	 * In both these cases the customer has to be shown error message and shound not
	 * be allowed to proceed  and do the payment.
	 **/

	echo "Checksum mismatch !<br>";
}




?>