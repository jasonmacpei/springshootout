import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async redirects() {
    return [
      { source: "/index.html", destination: "/", permanent: true },
      { source: "/contact.html", destination: "/contact", permanent: true },
      { source: "/pages/contact.html", destination: "/contact", permanent: true },
      { source: "/gyms.html", destination: "/gyms", permanent: true },
      { source: "/pages/gyms.html", destination: "/gyms", permanent: true },
      { source: "/hotels.html", destination: "/hotels", permanent: true },
      { source: "/pages/hotels.html", destination: "/hotels", permanent: true },
      { source: "/media.html", destination: "/media", permanent: true },
      { source: "/pages/media.html", destination: "/media", permanent: true },
      { source: "/rules.html", destination: "/rules", permanent: true },
      { source: "/pages/rules.html", destination: "/rules", permanent: true },
      { source: "/registration.php", destination: "/register", permanent: true },
      { source: "/pages/registration.php", destination: "/register", permanent: true },
      { source: "/registration_confirmation.php", destination: "/register", permanent: true },
      { source: "/pages/registration_confirmation.php", destination: "/register", permanent: true },
      { source: "/manage_team_contacts.php", destination: "/additional-contacts", permanent: true },
      { source: "/pages/manage_team_contacts.php", destination: "/additional-contacts", permanent: true },
      { source: "/results.php", destination: "/results", permanent: true },
      { source: "/pages/results.php", destination: "/results", permanent: true },
      { source: "/standings.php", destination: "/standings", permanent: true },
      { source: "/pages/standings.php", destination: "/standings", permanent: true },
      { source: "/schedule.php", destination: "/schedule", permanent: true },
      { source: "/pages/schedule.php", destination: "/schedule", permanent: true },
      { source: "/schedule.html", destination: "/schedule", permanent: true },
      { source: "/pages/schedule.html", destination: "/schedule", permanent: true },
      { source: "/newschedule.php", destination: "/schedule", permanent: true },
      { source: "/pages/newschedule.php", destination: "/schedule", permanent: true },
    ];
  },
};

export default nextConfig;
