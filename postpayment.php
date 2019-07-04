<?php

require 'vendor/autoload.php';
use MemCachier\MemcacheSASL;

// Make MemCachier connection
// ==========================

// parse config
$servers = explode(",", getenv("MEMCACHIER_SERVERS"));
for ($i = 0; $i < count($servers); $i++) {
  $servers[$i] = explode(":", $servers[$i]);
}

// Using Memcached client (recommended)
// ------------------------------------
$m = new Memcached("memcached_pool");
$m->setOption(Memcached::OPT_BINARY_PROTOCOL, TRUE);
// Enable no-block for some performance gains but less certainty that data has
// been stored.
$m->setOption(Memcached::OPT_NO_BLOCK, TRUE);
// Failover automatically when host fails.
$m->setOption(Memcached::OPT_AUTO_EJECT_HOSTS, TRUE);
// Adjust timeouts.
$m->setOption(Memcached::OPT_CONNECT_TIMEOUT, 2000);
$m->setOption(Memcached::OPT_POLL_TIMEOUT, 2000);
$m->setOption(Memcached::OPT_RETRY_TIMEOUT, 2);

$m->setSaslAuthData(getenv("MEMCACHIER_USERNAME"), getenv("MEMCACHIER_PASSWORD"));
if (!$m->getServerList()) {
  // We use a consistent connection to memcached, so only add in the servers
  // first time through otherwise we end up duplicating our connections to the
  // server.
  $m->addServers($servers);
}

// Enable MemCachier session support
session_start();
$_SESSION['test'] = 42;

//session_save_path("./");//path on your server where you are storing session

//file which has required functions
require "functions.php";
?>
<html>
<head><title>Post Payment</title></head>
<body bgcolor="white">
<font size=4>

<?php

$key = getenv('SECURE-RESELLER-PAYMENT-KEY');//replace ur 32 bit secure key , Get your secure key from your Reseller Control panel

$redirectUrl              = $_SESSION['redirecturl'];// redirectUrl received from foundation
$transId                  = $_SESSION['transid'];//Pass the same transid which was passsed to your Gateway URL at the beginning of the transaction.
$sellingCurrencyAmount    = $_SESSION['sellingcurrencyamount'];
$accountingCurrencyAmount = $_SESSION['accountingcurencyamount'];

$status = $_REQUEST["status"];// Transaction status received from your Payment Gateway
//This can be either 'Y' or 'N'. A 'Y' signifies that the Transaction went through SUCCESSFULLY and that the amount has been collected.
//An 'N' on the other hand, signifies that the Transaction FAILED.

/** HERE YOU HAVE TO VERIFY THAT THE STATUS PASSED FROM YOUR PAYMENT GATEWAY IS VALID.
 * And it has not been tampered with. The data has not been changed since it can * easily be done with HTTP request.
 *
 **/


//Alex starts here
$status = $_GET["status"];


srand((double) microtime()*1000000);
$rkey = rand();

$checksum = generateChecksum($transId, $sellingCurrencyAmount, $accountingCurrencyAmount, $status, $rkey, $key);


?>
		<form name="f1" action="<?php echo $redirectUrl;?>" id="finalForm">
			<input type="hidden" name="transid" value="<?php echo $transId;?>">
		    <input type="hidden" name="status" value="<?php echo $status;?>">
			<input type="hidden" name="rkey" value="<?php echo $rkey;?>">
		    <input type="hidden" name="checksum" value="<?php echo $checksum;?>">
		    <input type="hidden" name="sellingamount" value="<?php echo $sellingCurrencyAmount;?>">
			<input type="hidden" name="accountingamount" value="<?php echo $accountingCurrencyAmount;?>">
		</form>
		<script type="text/javascript">
			window.onload = function()
			{
				document.querySelector("#finalForm").submit();
			}
		</script>
		</script>
</font>
</body>
</html>