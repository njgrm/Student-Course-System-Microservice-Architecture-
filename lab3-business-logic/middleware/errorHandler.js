function errorHandler(err, req, res, next) {
  const statusCode = err.statusCode || 500;
  let errorLabel = 'INTERNAL_SERVER_ERROR';

  if (statusCode === 404) {
    errorLabel = 'NOT_FOUND';
  } else if (statusCode === 400) {
    errorLabel = 'VALIDATION_ERROR';
  }

  res.status(statusCode).json({
    error: `${statusCode} ${errorLabel}`,
    message: err.message,
  });
}

module.exports = errorHandler;
