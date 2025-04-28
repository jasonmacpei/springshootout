const { Pool } = require('pg');

const pool = new Pool({
  user: 'jasonmacdonald', // replace with your database username
  host: 'localhost', // replace with your database server address if different
  database: 'shootout', // replace with your actual database name
  password: 'J0rdan23', // replace with the database user's password
  port: 5432, // the default port for PostgreSQL, change if different
});

const insertRegistration = async (registrationData) => {
  // Insert into database
  const result = await pool.query('INSERT INTO registrations ...', [/* values */]);
  return result;
};

module.exports = {
  insertRegistration,
};
