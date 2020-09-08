<?php
/* @var Amazon_S3_And_CloudFront_Assets_Pull $this */
?>
<p>
	After submitting your request in the previous step you will be given the details you need for DNS validation with a CNAME record.
</p>

<ol>
	<li>
		Click the triangle next to the wildcard domain to expand it and view the required CNAME entry details, or download the CSV file. In the CSV file the fields you need are called
		<strong>Record Name</strong> and <strong>Record Value</strong>.
	</li>
	<li>
		Add a new CNAME record for your domain wherever you manage its DNS.
	</li>
	<li>
		For most domain hosts you will use just the first part of the
		<strong>Record Name</strong>, everything before your domain. In the example screenshot below this is seen as the "Host Name".
	</li>
	<li>
		The entire
		<strong>Record Value</strong> is used as the CNAME target. In the example screenshot below this is the "Points to".
	</li>
	<li>
		Once you have created the CNAME record in the DNS settings for your domain, click <strong>Continue</strong>.
	</li>
</ol>

<p>
	<img src="<?php echo esc_url( $this->get_step_media_url( 'acm-dns-validation.png' ) ) ?>" alt="Validate domain name ownership">
</p>

<p>
	<img src="<?php echo esc_url( $this->get_step_media_url( 'acm-cname-entry.png' ) ) ?>" alt="Enter CNAME details in DNS">
</p>