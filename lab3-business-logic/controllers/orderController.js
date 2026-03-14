const { validateAndProcessOrder } = require('../logic/orderLogic');

async function placeOrder(req, res, next) {
  try {
    const { productId, quantity } = req.body;
    const updatedProduct = await validateAndProcessOrder(productId, quantity);

    res.status(200).json({
      message: 'Order successful',
      product: updatedProduct.name,
      remainingStock: updatedProduct.stock,
    });
  } catch (err) {
    next(err);
  }
}

module.exports = { placeOrder };
