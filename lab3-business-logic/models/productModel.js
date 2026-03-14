const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient();

async function findAll() {
  return prisma.product.findMany();
}

async function findById(id) {
  const numericId = Number(id);
  if (!Number.isInteger(numericId) || numericId <= 0) {
    return null;
  }

  return prisma.product.findUnique({
    where: { id: numericId },
  });
}

async function updateStock(id, newStock) {
  const numericId = Number(id);
  if (!Number.isInteger(numericId) || numericId <= 0) {
    return null;
  }

  return prisma.product.update({
    where: { id: numericId },
    data: { stock: newStock },
  });
}

module.exports = { findAll, findById, updateStock };
