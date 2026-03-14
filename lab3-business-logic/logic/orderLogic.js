const productModel = require('../models/productModel');

async function validateAndProcessOrder(productId, quantity) {
  // Rule 1: Product must exist
  const product = await productModel.findById(productId);
  if (!product) {
    const err = new Error('Product not found');
    err.statusCode = 404;
    throw err;
  }

  // Rule 2: Quantity is required
  if (quantity === undefined || quantity === null) {
    const err = new Error('Quantity is required');
    err.statusCode = 400;
    throw err;
  }

  // Rule 3: Quantity must be positive
  if (quantity <= 0) {
    const err = new Error('Quantity must be a positive number');
    err.statusCode = 400;
    throw err;
  }

  // Rule 4: Quantity must be a whole number (integer)
  if (!Number.isInteger(quantity)) {
    const err = new Error('Quantity must be a whole number');
    err.statusCode = 400;
    throw err;
  }

  // Rule 5: Stock must not be zero
  if (product.stock === 0) {
    const err = new Error('Product is out of stock');
    err.statusCode = 400;
    throw err;
  }

  // Rule 6: Quantity must not exceed available stock
  if (quantity > product.stock) {
    const err = new Error(`Insufficient stock. Available: ${product.stock}`);
    err.statusCode = 400;
    throw err;
  }

  // All rules passed — update stock and return updated product
  const newStock = product.stock - quantity;
  return productModel.updateStock(productId, newStock);
}

module.exports = { validateAndProcessOrder };
