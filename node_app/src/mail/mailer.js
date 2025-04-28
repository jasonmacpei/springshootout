const nodemailer = require('nodemailer');

const sendConfirmationEmail = async (to, subject, text) => {
  // Set up nodemailer transport here
  const transporter = nodemailer.createTransport({
    // ... your nodemailer config
  });

  // Send an email
  const mailOptions = {
    from: 'you@example.com',
    to,
    subject,
    text,
  };

  await transporter.sendMail(mailOptions);
};

module.exports = {
  sendConfirmationEmail,
};
