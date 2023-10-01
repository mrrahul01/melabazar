kB‘Y<?php exit; ?>a:1:{s:7:"content";a:4:{s:14:"rswp_shortcode";a:1:{i:0;s:7:"product";}s:15:"rswp_attributes";a:1:{i:0;s:114:"a:6:{i:0;s:4:"name";i:1;s:5:"image";i:2;s:11:"description";i:3;s:5:"brand";i:4;s:10:"identifier";i:5;s:5:"price";}";}s:9:"rswp_code";a:1:{i:0;s:747:"<span itemscope="itemscope" itemtype="http://schema.org/Product">
	Product name: <span itemprop="name">[name]</span><br />
	Product image: <img itemprop="image" src="[image]" alt="[name]" /><br />
	Brand: <span itemprop="brand" itemscope="itemscope" itemtype="http://schema.org/Brand">
		<span itemprop="name">[brand]</span>
	</span><br />
	Identifier: <span itemprop="productID">[identifier]</span><br />
	Description: <span itemprop="description">[description]</span><br />
	<span itemprop="offers" itemscope="itemscope" itemtype="http://schema.org/Offer">
		Price: <span itemprop="price">[price]</span>
		- <link itemprop="availability" href="http://schema.org/InStock" />In stock
	</span><br />
	<?php echo do_shortcode( $content ); ?>
</span>";}s:8:"rswp_css";a:1:{i:0;s:0:"";}}}