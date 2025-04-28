const LocalStrategy = require('passport-local').Strategy;

function initialize(passport, getUserByUsername, getUserById) {
  passport.use(new LocalStrategy(
    async (username, password, done) => {
      try {
        const user = await getUserByUsername(username);
        if (user == null) {
          return done(null, false, { message: 'No user with that username' });
        }

        if (password === user.password) { // Replace this with a proper password check
          return done(null, user);
        } else {
          return done(null, false, { message: 'Password incorrect' });
        }
      } catch (e) {
        return done(e);
      }
    }
  ));

  passport.serializeUser((user, done) => {
    done(null, user.id);
  });

  passport.deserializeUser(async (id, done) => {
    try {
      const user = await getUserById(id);
      done(null, user);
    } catch (e) {
      done(new Error('User not found'));
    }
  });
}

module.exports = initialize;
