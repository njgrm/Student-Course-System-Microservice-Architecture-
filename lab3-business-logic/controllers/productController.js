const productModel = require('../models/productModel');

async function getAllProducts(req, res, next) {
  try {
    const products = await productModel.findAll();
    res.status(200).json(products);
  } catch (err) {
    next(err);
  }
}

async function getProductById(req, res, next) {
  try {
    const product = await productModel.findById(Number(req.params.id));
    if (!product) {
      const err = new Error('Product not found');
      err.statusCode = 404;
      throw err;
    }

    res.status(200).json(product);
  } catch (err) {
    next(err);
  }
}

module.exports = { getAllProducts, getProductById };
