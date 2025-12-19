<?php

/**
 * Output the HTML head for a page.
 *
 * @param string $title The page title.
 * @param bool $include_sweetalert Whether to include SweetAlert2 script.
 */
function generate_head(string $title, bool $include_sweetalert = true): void {
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="utf-8" />
      <title>{$title} — Lash Nouveau</title>
      <meta name="viewport" content="width=device-width,initial-scale=1" />
      <link rel="stylesheet" href="style.css">
    HTML;
    if ($include_sweetalert) {
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    }
    if (str_contains($title, 'Home')) {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cookie&display=swap" rel="stylesheet">';
    }
    echo <<<HTML
    </head>
    <body>
    HTML;
}


/**
 * Output the site header/navigation.
 */
function generate_header(): void {
        // Ensure session is started so we can show login state
        if (session_status() === PHP_SESSION_NONE) {
                @session_start();
        }

        $authLinks = '';

        if (!empty($_SESSION['user_name'])) {
                $name = htmlspecialchars($_SESSION['user_name']);
                $authLinks = "<a href=\"profile.php\">{$name}</a> <a href=\"logout.php\">Logout</a>";
        } else {
                $authLinks = "<a href=\"login2.php\">Login</a> <a href=\"signup.php\">Sign up</a>";
        }

        echo <<<HTML
        <header>
            <div class="logo">Lash Nouveau</div>
            <nav>
                <a href="index.php">Home</a>
                <a href="services.php">Services</a>
                <a href="specials.php">Training</a>
                <a href="profile.php">Profile</a>
                
            </nav>
        </header>
        HTML;
}

/**
 * Output the site footer.
 */
/**
 * Output the site footer with embedded CSS.
 */
function generate_footer(): void {
    echo <<<HTML
    <style>
        .site-footer {
            background: linear-gradient(to right, #e43771ff, #921758ff);
            color: white;
            padding: 50px 20px 20px;
            margin-top: 30px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 40px;
            margin-bottom: 10px;


        }

        .footer-logo {
            color: white;
            font-weight: 700;
            font-size: 24px;
            letter-spacing: 1px;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;

        }
        .footer-contact,p{
            font-family: 'Playfair Display', serif;
            margin-bottom: 20px;
        }

        .footer-nav {
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .footer-nav a {
            color: var(--deep-pink);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .footer-nav a:hover {
            color: var(--deep-pink-dark);
            transform: translateY(-2px);
        }

        .footer-info {
            color: #f0f0f0;
            font-size: 13px;
            line-height: 1.8;
            max-width: 600px;
            margin: 0 auto;
            font-family: 'Playfair Display', serif;

        }

        .instagram-link {
            display: inline-block;
            background: var(--deep-pink);
            color: white !important;
            padding: 8px 20px;
            border-radius: 28px; /* Pill shape matching your buttons */
            text-decoration: none;
            margin-top: 15px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(214, 51, 132, 0.15);
            transition: all 0.3s ease;
        }

        .instagram-link:hover {
            background: var(--deep-pink-dark);
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(214, 51, 132, 0.25);
        }
    </style>

    <footer class="site-footer">
        <div class="footer-logo">Lash Nouveau</div>
        <p>Pretty Lashes Pretty You🌸</p>

        <div class="footer-contact">
          <h4>Contact Us</h4>
          <p>Email: support@kingsprincess.com</p>
          <p>Phone: +233 55 123 4567</p>
          <p>Address: Accra, Ghana</p>
      </div>


        <div class="footer-info">
            <p><strong>Contact Us:</strong> 0544126384</p>
            <a href="https://www.instagram.com/lashh_nouveau?igsh=bXY2YXAxeWRxZm1w" target="_blank" class="instagram-link">
                Follow @lashh_nouveau
            </a>
            <p style="margin-top: 25px; opacity: 0.7;">© 2025 Lash Nouveau. Professional Lash Artistry & Training.</p>
        </div>
    </footer>
    </body>
    </html>
    HTML;
}

?>