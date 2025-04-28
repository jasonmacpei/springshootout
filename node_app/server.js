const express = require('express');
const session = require('express-session');
const passport = require('passport');
const bodyParser = require('body-parser');
const LocalStrategy = require('passport-local').Strategy;
const initializePassport = require('./passport-config');
const registrationRoutes = require('./src/routes/registration');


const app = express();

// Set view engine
app.set('view engine', 'ejs');

app.use(express.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use('/api', registrationRoutes);

// Middleware for body parsing

app.use(bodyParser.json());

// Express session
app.use(session({
  secret: 'your_secret_key', // Replace 'your_secret_key' with an actual secret key
  resave: false,
  saveUninitialized: true
}));

initializePassport(
  passport,
  username => {
    // Replace with logic to find user by username from your database
    // Return user object or null
  },
  id => {
    // Replace with logic to find user by id from your database
    // Return user object or null
  }
);

// Passport initialization
app.use(passport.initialize());
app.use(passport.session());

// Define routes here...
// Example route for home page
app.get('/', (req, res) => {
  res.render('index'); // Make sure you have an 'index.ejs' file in your views directory
});

//route for login page
app.get('/login', (req, res) => {
  res.render('login', { messages: {} }); // Make sure to pass an object with a 'messages' key
});



// Route for handling login submissions
app.post('/login', 
  passport.authenticate('local', { failureRedirect: '/login' }),
  (req, res) => {
    res.redirect('/admin');
  }
);

// Route for the admin dashboard
app.get('/admin', (req, res) => {
  if (req.isAuthenticated()) {
    res.render('admin'); // admin.ejs should be in your views directory
  } else {
    res.redirect('/login');
  }
});

// Start the server
const port = process.env.PORT || 3000;
app.listen(port, () => {
  console.log(`Server running on port ${port}`);
});
