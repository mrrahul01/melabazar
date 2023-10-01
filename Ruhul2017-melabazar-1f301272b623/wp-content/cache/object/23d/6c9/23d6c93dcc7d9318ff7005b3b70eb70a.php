kB‘Y<?php exit; ?>a:1:{s:7:"content";a:4:{s:14:"rswp_shortcode";a:1:{i:0;s:5:"event";}s:15:"rswp_attributes";a:1:{i:0;s:226:"a:12:{i:0;s:4:"name";i:1;s:6:"street";i:2;s:3:"zip";i:3;s:8:"locality";i:4;s:7:"country";i:5;s:3:"url";i:6;s:10:"start_date";i:7;s:8:"end_date";i:8;s:5:"image";i:9;s:8:"latitude";i:10;s:9:"longitude";i:11;s:12:"organization";}";}s:9:"rswp_code";a:1:{i:0;s:1345:"<div class="rswp-event" itemscope="itemscope" itemtype="http://schema.org/Event">
	<a href="'<?php echo $url; ?>" itemprop="url"><h5 itemprop="name"><?php echo $name; ?></h5></a>

	<p itemprop="description"><?php echo do_shortcode( $content ); ?></p>

	<h6>Location</h6>
	<span itemprop="location" itemscope="itemscope" itemtype="http://schema.org/Place">
	â€‹<p>
			<span itemprop="name">[organization]</span><br />
			<span itemprop="address" itemscope="itemscope" itemtype="http://schema.org/PostalAddress">
				<span itemprop="streetAddress">[street]</span><br />
				<span itemprop="postalCode">[zip]</span>
				<span itemprop="addressLocality">[locality]</span><br />
				<span itemprop="addressCountry">[country]</span><br />
				</span>
		</p>
		<span itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinateso">
			<meta itemprop="latitude" content="[latitude]" />
			<meta itemprop="longitude" content="[latitude]" />
			</span>
	</span>

	<h6>Date and Time</h6>

	<p>
		Start Date:
		<time itemprop="startDate" datetime="<?php echo date( 'c', strtotime( $start_date ) ); ?>">[start_date]</time>
		<?php if ( ! empty( $end_date ) ) {
		    printf('<br />End Date: <time itemprop="endDate" datetime="%s">[end_date]</time>', date( 'c', strtotime( $end_date ) ));
		} ?>
	</p>
	<meta itemprop="image" content="[image]" />
</div>";}s:8:"rswp_css";a:1:{i:0;s:0:"";}}}