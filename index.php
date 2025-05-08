<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendly Clone - Easy Scheduling Ahead</title>
    <style>
        :root {
            --primary-color: #0069ff;
            --primary-dark: #0053cc;
            --secondary-color: #00a2ff;
            --text-color: #4f5e71;
            --dark-text: #1a1a1a;
            --light-text: #6e7582;
            --light-bg: #f8f8fa;
            --white: #ffffff;
            --success: #00c389;
            --border-color: #e1e1e1;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            color: var(--text-color);
            line-height: 1.6;
            background-color: var(--white);
        }

        header {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .auth-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-outline {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline:hover {
            background-color: rgba(0, 105, 255, 0.05);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .hero {
            padding: 150px 0 100px;
            background: linear-gradient(to bottom, #f9f9ff, #ffffff);
            text-align: center;
        }

        .hero h1 {
            font-size: 48px;
            color: var(--dark-text);
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero p {
            font-size: 20px;
            color: var(--light-text);
            max-width: 700px;
            margin: 0 auto 40px;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 60px;
        }

        .hero-image {
            max-width: 900px;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .hero-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .features {
            padding: 100px 0;
            background-color: var(--light-bg);
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 36px;
            color: var(--dark-text);
            margin-bottom: 15px;
        }

        .section-title p {
            font-size: 18px;
            color: var(--light-text);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 30px;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(0, 105, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .feature-icon i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .feature-card h3 {
            font-size: 20px;
            color: var(--dark-text);
            margin-bottom: 15px;
        }

        .feature-card p {
            color: var(--light-text);
        }

        .how-it-works {
            padding: 100px 0;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            flex-wrap: wrap;
        }

        .step {
            flex: 1;
            min-width: 250px;
            text-align: center;
            padding: 0 20px;
            position: relative;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 20px;
        }

        .step h3 {
            font-size: 20px;
            color: var(--dark-text);
            margin-bottom: 15px;
        }

        .step p {
            color: var(--light-text);
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -30px;
            width: 60px;
            height: 2px;
            background-color: var(--border-color);
        }

        .testimonials {
            padding: 100px 0;
            background-color: var(--light-bg);
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .testimonial-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }

        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .author-info h4 {
            font-size: 16px;
            color: var(--dark-text);
            margin-bottom: 5px;
        }

        .author-info p {
            font-size: 14px;
            color: var(--light-text);
        }

        .cta {
            padding: 100px 0;
            text-align: center;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: var(--white);
        }

        .cta h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }

        .cta p {
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }

        .btn-white {
            background-color: var(--white);
            color: var(--primary-color);
        }

        .btn-white:hover {
            background-color: rgba(255, 255, 255, 0.9);
        }

        footer {
            background-color: var(--dark-text);
            color: var(--white);
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--white);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--white);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            color: rgba(255, 255, 255, 0.7);
            font-size: 20px;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: var(--white);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 18px;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .step:not(:last-child)::after {
                display: none;
            }

            .steps {
                flex-direction: column;
                gap: 30px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">Calendly</a>
                <div class="nav-links">
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="#testimonials">Testimonials</a>
                </div>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">Log In</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up Free</a>
                </div>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Easy scheduling <br>ahead</h1>
            <p>Calendly is your scheduling automation platform for eliminating the back-and-forth emails for finding the perfect time — and so much more.</p>
            <div class="hero-buttons">
                <a href="signup.php" class="btn btn-primary">Sign Up Free</a>
                <a href="login.php" class="btn btn-outline">Log In</a>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1600267204091-5c1ab8b10c02?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Calendar scheduling">
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Simplified scheduling for more than 10,000,000 users worldwide</h2>
                <p>Calendly helps you schedule meetings without the back-and-forth emails</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i>📅</i>
                    </div>
                    <h3>Easy Scheduling</h3>
                    <p>Share your Calendly link and let others pick a time that works for their schedule, eliminating the back-and-forth emails.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i>🔄</i>
                    </div>
                    <h3>Automated Notifications</h3>
                    <p>Send automatic confirmations and reminders to keep everyone on the same page and reduce no-shows.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i>🌐</i>
                    </div>
                    <h3>Time Zone Detection</h3>
                    <p>Calendly automatically detects your invitees' time zones and displays available times accordingly.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Calendly makes scheduling meetings easy and efficient</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Create your schedule</h3>
                    <p>Set your availability preferences and create different meeting types.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Share your link</h3>
                    <p>Send your Calendly link to anyone you want to meet with.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Get booked</h3>
                    <p>They pick a time, and the event is added to your calendar.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>What Our Users Say</h2>
                <p>Thousands of people use Calendly to streamline their scheduling</p>
            </div>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">"Calendly has changed the way I schedule meetings. No more back-and-forth emails trying to find a time that works for everyone."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="Sarah Johnson">
                        </div>
                        <div class="author-info">
                            <h4>Sarah Johnson</h4>
                            <p>Marketing Director</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"As a freelancer, Calendly has saved me countless hours. My clients love how easy it is to book time with me."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Michael Chen">
                        </div>
                        <div class="author-info">
                            <h4>Michael Chen</h4>
                            <p>Freelance Designer</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Our team's productivity has increased significantly since we started using Calendly for all our meeting scheduling."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Jessica Williams">
                        </div>
                        <div class="author-info">
                            <h4>Jessica Williams</h4>
                            <p>Team Lead</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>Ready to simplify your scheduling?</h2>
            <p>Sign up for free and start scheduling meetings in minutes.</p>
            <a href="signup.php" class="btn btn-white">Sign Up Free</a>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Calendly</h3>
                    <ul class="footer-links">
                        <li><a href="#">About</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Security</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Solutions</h3>
                    <ul class="footer-links">
                        <li><a href="#">Sales</a></li>
                        <li><a href="#">Marketing</a></li>
                        <li><a href="#">Customer Success</a></li>
                        <li><a href="#">Recruiting</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Webinars</a></li>
                        <li><a href="#">Developers</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Connect With Us</h3>
                    <div class="social-links">
                        <a href="#"><span>📱</span></a>
                        <a href="#"><span>📘</span></a>
                        <a href="#"><span>🐦</span></a>
                        <a href="#"><span>📸</span></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Calendly Clone. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // JavaScript for smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
