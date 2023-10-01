kB‘Y<?php exit; ?>a:1:{s:7:"content";a:4:{s:14:"rswp_shortcode";a:1:{i:0;s:6:"recipe";}s:15:"rswp_attributes";a:1:{i:0;s:290:"a:15:{i:0;s:4:"name";i:1;s:10:"recipetype";i:2;s:5:"photo";i:3;s:9:"published";i:4;s:8:"preptime";i:5;s:8:"cooktime";i:6;s:9:"totaltime";i:7;s:9:"nutrition";i:8;s:5:"yield";i:9;s:11:"ingredients";i:10;s:7:"summary";i:11;s:6:"rating";i:12;s:5:"count";i:13;s:6:"author";i:14;s:9:"thumbnail";}";}s:9:"rswp_code";a:1:{i:0;s:1621:"<div itemscope="itemscope" itemtype="http://schema.org/Recipe">
	Product name: <span itemprop="name">[name]</span><br />
	By: <span itemprop="author">[author]</span><br />
	Description: <span itemprop="description">[summary]</span><br />
	Type: <span itemprop="recipeCategory">[recipetype]</span><br />
	Image: <br /><img class="photo" itemprop="image" src="[photo]" alt="[name]" /><br />
	<meta itemprop="thumbnailUrl" content="[thumbnail]" />
	Published:
	<time itemprop="datePublished" datetime="<?php echo date( 'c', strtotime( $published ) ); ?>">[published]</time>
	<br />

	Time to prepare the recipe:
	<meta itemprop="prepTime" content="PT[preptime]M" />
	[preptime] minutes <br />
	Time to cook:
	<meta itemprop="prepTime" content="PT[cooktime]M" />
	[cooktime] minutes<br />
	Duration:
	<meta itemprop="prepTime" content="PT[totaltime]M" />
	[totaltime] minutes<br />

	Nutrition: <span itemprop="nutrition" itemscope="itemscope" itemtype="http://schema.org/NutritionInformation">
			<span itemprop="calories">[nutrition]</span>
		</span><br />

	Servings: <span itemprop="recipeYield">[yield]</span><br />

	Ingredients:
	<?php
	foreach ( explode( ',', $ingredients ) as $ingredient ) {

		echo '<span itemprop="ingredients">' . $ingredient . '</span>, ';
	}
	?>
	<br />

	Instructions:
	<div itemprop="recipeInstructions"><?php echo do_shortcode( $content ); ?></div>
	<br />

	<span itemprop="aggregateRating" itemscope="itemscope" itemtype="http://schema.org/AggregateRating">
		<span itemprop="ratingValue">[rating]</span> stars <br />
		<span itemprop="ratingCount">[count]</span> reviews
	</span>

</div>";}s:8:"rswp_css";a:1:{i:0;s:0:"";}}}