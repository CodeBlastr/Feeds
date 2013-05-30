
<div class="category">Category: </div>
<div class="category-value"><?php echo $product['advertiser-category']; ?></div>

<?php echo $this->Html->image($product['image-url']); ?>

<?php echo $product['name']; ?>

<?php echo $product['description']; ?>

<?php if($product['sku'] !== ''): ?>
	<p>SKU: <?php echo $product['sku']; ?></p>
<?php endif; ?>
<?php if($product['upc'] !== ''): ?>
	<p>UPC: <?php echo $product['upc']; ?></p>
<?php endif; ?>

<?php if($product['price'] !== ''): ?>
		<p>Price: $<?php echo ZuhaInflector::pricify($product['price']); ?></p>
	<?php endif; ?>
	
	<?php if($product['retail-price'] !== ''): ?>
		<p>Retail: $<?php echo ZuhaInflector::pricify($product['retail-price']); ?></p>
	<?php endif; ?>
	<?php echo 
		$this->Rating->display(array('item' => $product['sku'])); ?>
	<?php if($product['sale-price'] !== ''): ?>
		<p>Sale: $<?php echo ZuhaInflector::pricify($product['sale-price']); ?></p>
	<?php endif; ?>
	<div class="add-to-cart">
		<a href="<?php echo $product['buy-url']; ?>" class="btn btn-success" type="submit">
		<img src="/theme/default/upload/1/img/cart.png" /> Buy Now</a>
	</div>
	<div class="row-fluid pull-right">
		<div class="pull-right" style="margin:10px 0;">
		<?php echo $this->Favorites->toggleFavorite('closet', $product['id'], 'Save to Closet', 'Remove From Closet', array('class' => 'btn'), $userFavorites); ?>
		</div>
	</div>