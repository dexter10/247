<?php

// Not working - porque?
$xml = '
	<?xml version="1.0"?>
	<orderdetails>
		<customerdetails>'.$customerDetails.'</customerdetails>
		<receiver>'.$receiver.'</receiver>
		<products>'.implode($products, "\n").'</products>
	</orderdetails>
';

// Working - go figure!
$xml = '<?xml version="1.0"?>
	<orderdetails>
	<customerdetails>'.$customerDetails.'</customerdetails>
	<receiver>'.$receiver.'</receiver>
	<products>'.implode($products, '').'</products>
	</orderdetails>
';