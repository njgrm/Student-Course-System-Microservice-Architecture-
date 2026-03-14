const express = require('express');
const app = express();

const productRoutes = require('./routes/products');
const orderRoutes = require('./routes/orders');
const errorHandler = require('./middleware/errorHandler');

// Middleware
app.use(express.json());
app.set('json spaces', 2);

// Routes
app.use('/api/products', productRoutes);
app.use('/api/orders', orderRoutes);

// Error handling middleware (must be registered after routes)
app.use(errorHandler);

const PORT = 3000;
app.listen(PORT, () => {
  console.log(`Server is running on http://localhost:${PORT}`);
});
