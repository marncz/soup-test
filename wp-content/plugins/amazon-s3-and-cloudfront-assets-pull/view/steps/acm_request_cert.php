<?php
/* @var Amazon_S3_And_CloudFront_Assets_Pull $this */
?>
<p>
	Next, we are going to submit a request for a free wildcard SSL certificate which will be valid for the given domain name as well as any subdomains.
</p>

<ol>
	<li>Choose <strong>Request a public certificate</strong> and then click the <strong>Request a certificate</strong> button.</li>
	<li>
		Enter <code data-as3cf-copy>*.<span data-as3cf-setting="basedomain_ref"></span></code> into the
		<strong>Domain name</strong> field.
	</li>
	<li>Click the <strong>Next</strong> button.</li>
	<li>Choose <strong>DNS validation</strong> and click the <strong>Next</strong> button.</li>
	<li>Skip adding tags unless you use them and click <strong>Review</strong> to proceed.</li>
	<li><em>Confirm the domain is correct</em> (this cannot be changed later).</li>
	<li>Click the <strong>Confirm and request</strong> button.</li>
</ol>

<p>
	<img src="<?php echo esc_url( $this->get_step_media_url( 'acm-request-public-certificate.png' ) ) ?>" alt="Request a Public Certificate">
</p>

<p>
	<img src="<?php echo esc_url( $this->get_step_media_url( 'acm-request-certificate.png' ) ) ?>" alt="Request a Certificate">
</p>

<p>
	<img src="<?php echo esc_url( $this->get_step_media_url( 'acm-choose-dns-validation.png' ) ) ?>" alt="Choose DNS Validation">
</p>

<p>
	<img src="<?php echo esc_url( $this->get_step_media_url( 'acm-add-tags.png' ) ) ?>" alt="Add Tags">
</p>

<p>
	<img src="<?php echo esc_url( $this->get_step_media_url( 'acm-confirm-domain-name.png' ) ) ?>" alt="Confirm Domain Name">
</p>