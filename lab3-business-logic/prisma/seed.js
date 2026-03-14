const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient();

async function main() {
  await prisma.product.deleteMany();

  await prisma.product.createMany({
    data: [
      { id: 1, name: 'Laptop', description: 'High-performance', price: 999.99, stock: 5 },
      { id: 2, name: 'Mouse', description: 'Wireless optical', price: 29.99, stock: 20 },
      { id: 3, name: 'Keyboard', description: 'Mechanical RGB', price: 79.99, stock: 10 },
      { id: 4, name: 'Monitor', description: '27-inch 4K display', price: 399.99, stock: 3 },
      { id: 5, name: 'Headset', description: 'Noise-cancelling', price: 149.99, stock: 0 },
    ],
  });
}

main()
  .then(async () => {
    await prisma.$disconnect();
  })
  .catch(async (error) => {
    console.error(error);
    await prisma.$disconnect();
    process.exit(1);
  });
