<div id="feedsSearch">
	
<?php foreach($products as $product): ?>
<div class="row-fluid">
<div class="span3">
<div class="product-image-div"><?php echo $this->Html->image($product['image-url']); ?></div>
</div>

<div class="span9">
<div class="product-details">
	
<h2><?php echo $product['name']; ?></h2>

<p class="product-desc"><?php echo $product['description']; ?></p>

<p>SKU: <?php echo $product['sku']; ?></p>
<p>UPC: <?php echo $product['upc']; ?></p>

<p>Price: <?php echo $product['price']; ?></p>
<p>Retail: <?php echo $product['retail-price']; ?></p>
<p>Sale: <?php echo $product['sale-price']; ?></p>

<?php echo $this->Html->link(__('Buy Now'), $product['buy-url']); ?>
</div>
</div>
</div>
<?php endforeach; ?>

<p>Total Results: <?php echo $totalMatches; ?></p>
<p>Page: <?php echo $pageNumber; ?></p>
<p>Showing: <?php echo $recPerPage; ?></p>
	
</div>

	