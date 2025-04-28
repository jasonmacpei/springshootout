const express = require('express');
const router = express.Router();
const { insertRegistration } = require('../db/db');
const { sendConfirmationEmail } = require('../mail/mailer');

router.post('/register', async (req, res) => {
  try {
    const registrationData = req.body;
    const dbResult = await insertRegistration(registrationData);
    
    if (dbResult.rowCount > 0) {
      // await sendConfirmationEmail(registrationData.email, 'Registration Successful', 'Thank you for registering!');
      res.json({ message: 'Registration successful' });
    } else {
      res.status(400).json({ message: 'Registration failed' });
    }
  } catch (error) {
    res.status(500).json({ message: 'Server error', error });
  }
});

module.exports = router;
