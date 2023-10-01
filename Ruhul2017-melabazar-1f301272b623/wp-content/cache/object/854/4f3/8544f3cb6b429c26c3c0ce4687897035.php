kB‘Y<?php exit; ?>a:1:{s:7:"content";a:4:{s:14:"rswp_shortcode";a:1:{i:0;s:6:"rating";}s:15:"rswp_attributes";a:1:{i:0;s:119:"a:6:{i:0;s:12:"itemreviewed";i:1;s:6:"rating";i:2;s:8:"reviewer";i:3;s:10:"dtreviewed";i:4;s:4:"best";i:5;s:5:"worst";}";}s:9:"rswp_code";a:1:{i:0;s:797:"<div itemprop="review" itemscope="itemscope" itemtype="http://schema.org/CreativeWork">
	<span itemprop="name"> [itemreviewed]</span><br />

	<p>Reviewed by
		<span itemprop="author" itemscope="itemscope" itemtype="http://schema.org/Person"><span itemprop="name">[reviewer]</span></span> on
		<time itemprop="datePublished" datetime="<?php echo date( 'c', strtotime( $dtreviewed ) ); ?>">[dtreviewed]</time>
	</p>

	<p><span itemprop="description">[content]</span></p>

	<p itemprop="aggregateRating" itemscope="itemscope" itemtype="http://schema.org/AggregateRating">Rating:
		<span itemprop="ratingValue">[rating]</span> out of [best]
		<meta itemprop="bestRating" content="[best]" />
		<meta itemprop="worstRating" content="[worst]" />
		<meta itemprop="ratingCount" content="1" />
	</p>
</div>";}s:8:"rswp_css";a:1:{i:0;s:0:"";}}}