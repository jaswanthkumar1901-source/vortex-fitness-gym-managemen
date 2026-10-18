
<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VORTEX FITNESS | Train Better. Live Stronger.</title>

    <link rel="stylesheet" href="/gym-management/css/home.css">
</head>
<body>

<header class="home-header">
    <a href="#home" class="home-logo">
        VORTEX <span>FITNESS</span>
    </a>

    <nav class="home-nav" aria-label="Main navigation">
        <a href="#home">Home</a>
        <a href="#about">About</a>
        <a href="#plans">Plans</a>
        <a href="#trainers">Trainers</a>
        <a href="#facilities">Facilities</a>
        <a href="#contact">Contact</a>
        <a href="/gym-management/auth/login.php" class="login-button">
            Sign In <span aria-hidden="true">↗</span>
        </a>
    </nav>
</header>

<main>
    <!-- HERO -->
    <section id="home" class="hero-section">
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <p class="hero-eyebrow">
                YOUR FITNESS. YOUR JOURNEY.
            </p>

            <h1>
                BUILD YOUR<br>
                <span>STRONGER</span><br>
                FUTURE.
            </h1>

            <p class="hero-description">
                Train with purpose. Stay consistent. Achieve your
                fitness goals with professional trainers and
                modern facilities.
            </p>

            <div class="hero-actions">
                <a href="#plans" class="button button-primary">
                    Explore Memberships <span aria-hidden="true">↗</span>
                </a>

                <a href="/gym-management/auth/login.php"
                   class="button button-outline">
                    Member Sign In
                </a>
            </div>

            <div class="hero-note">
                <span class="status-dot"></span>
                MAKE EVERY WORKOUT COUNT
            </div>
        </div>

        <div class="hero-bottom-label">
            <span>STRENGTH</span>
            <span class="label-divider"></span>
            <span>DISCIPLINE</span>
            <span class="label-divider"></span>
            <span>PROGRESS</span>
        </div>
    </section>

    <!-- ABOUT -->
    <section id="about" class="section about-section">
        <div class="section-heading">
            <p class="section-kicker">GET TO KNOW US</p>
            <h2>More Than Just <span>A Gym.</span></h2>
            <p class="section-intro">
                A welcoming space to build strength, develop discipline
                and make fitness a part of your everyday life.
            </p>
        </div>

        <div class="about-content">
            <div class="about-visual image-frame">
                <img
                    src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=1200&q=85"
                    alt="Gym interior with fitness equipment"
                    loading="lazy"
                >
                <div class="image-caption">
                    <span class="caption-mark"></span>
                    BUILT FOR YOUR PROGRESS
                </div>
            </div>

            <div class="about-copy">
                <p>
                    At VORTEX FITNESS, we believe fitness is about more
                    than lifting weights. It is about becoming stronger,
                    feeling healthier and building habits that last.
                </p>

                <p>
                    Our gym combines modern equipment, supportive
                    coaching and flexible membership options to help
                    you work towards your individual goals.
                </p>

                <div class="about-cards">
                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">↗</div>
                        <h3>Modern Equipment</h3>
                        <p>Train confidently with equipment for a variety
                           of fitness goals.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">◎</div>
                        <h3>Expert Guidance</h3>
                        <p>Get support and direction throughout your
                           fitness journey.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">✳</div>
                        <h3>Healthy Habits</h3>
                        <p>Build consistency and create a more active
                           lifestyle.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- MEMBERSHIP PLANS -->
    <section id="plans" class="section plans-section">
        <div class="section-heading">
            <p class="section-kicker">MEMBERSHIP OPTIONS</p>
            <h2>Find Your <span>Fit.</span></h2>
            <p class="section-intro">
                Choose a membership that works with your goals and routine.
            </p>
        </div>

        <div class="plans-grid">
            <article class="plan-card">
                <div class="plan-icon" aria-hidden="true">01</div>
                <p class="plan-label">GET STARTED</p>
                <h3>Basic</h3>
                <p class="plan-description">
                    For members building a regular workout routine.
                </p>
                <ul>
                    <li>Gym access</li>
                    <li>Standard equipment</li>
                    <li>Workout space access</li>
                </ul>
                <a href="/gym-management/auth/login.php" class="plan-link">
                    Enquire Now <span aria-hidden="true">↗</span>
                </a>
            </article>

            <article class="plan-card plan-card-featured">
                <div class="plan-badge">POPULAR CHOICE</div>
                <div class="plan-icon" aria-hidden="true">02</div>
                <p class="plan-label">BUILD YOUR ROUTINE</p>
                <h3>Standard</h3>
                <p class="plan-description">
                    For members looking for more structure and support.
                </p>
                <ul>
                    <li>Gym access</li>
                    <li>Fitness guidance</li>
                    <li>Access to training areas</li>
                </ul>
                <a href="/gym-management/auth/login.php" class="plan-link">
                    Enquire Now <span aria-hidden="true">↗</span>
                </a>
            </article>

            <article class="plan-card">
                <div class="plan-icon" aria-hidden="true">03</div>
                <p class="plan-label">GO FURTHER</p>
                <h3>Premium</h3>
                <p class="plan-description">
                    For members who want a more personalised experience.
                </p>
                <ul>
                    <li>Gym access</li>
                    <li>Priority fitness guidance</li>
                    <li>Personal training enquiry</li>
                </ul>
                <a href="/gym-management/auth/login.php" class="plan-link">
                    Enquire Now <span aria-hidden="true">↗</span>
                </a>
            </article>
        </div>

        <p class="section-footnote">
            Membership cards are illustrative. Connect them to your
            database to display actual plans, prices and benefits.
        </p>
    </section>

    <!-- TRAINERS -->
    <section id="trainers" class="section trainers-section">
        <div class="section-heading">
            <p class="section-kicker">SUPPORT THAT MOVES YOU</p>
            <h2>Guidance For <span>Every Goal.</span></h2>
            <p class="section-intro">
                The right support can help you train with confidence
                and stay consistent.
            </p>
        </div>

        <div class="trainer-content">
            <div class="trainer-visual image-frame">
                <img
                    src="https://images.unsplash.com/photo-1616279969722-d81a5a3944ef?auto=format&fit=crop&w=1100&q=85"
                    alt="Trainer coaching a gym member"
                    loading="lazy"
                >
            </div>

            <div class="trainer-copy">
                <span class="round-number">01</span>
                <p class="section-kicker">TRAIN WITH PURPOSE</p>
                <h3>Progress starts with the right guidance.</h3>
                <p>
                    Whether you are starting out or refining your
                    workout routine, structured guidance can help
                    you focus on proper technique, consistency and
                    achievable goals.
                </p>

                <div class="trainer-points">
                    <div><span>✓</span> Goal-focused guidance</div>
                    <div><span>✓</span> Workout technique support</div>
                    <div><span>✓</span> A consistent training routine</div>
                </div>

                <a href="/gym-management/auth/login.php"
                   class="button button-dark">
                    Member Sign In <span aria-hidden="true">↗</span>
                </a>
            </div>
        </div>

        <p class="section-footnote">
            Trainer details and profiles can be added when the trainer
            module is connected to your admin system.
        </p>
    </section>

    <!-- FACILITIES -->
    <section id="facilities" class="section facilities-section">
        <div class="section-heading">
            <p class="section-kicker">YOUR TRAINING SPACE</p>
            <h2>Made For <span>Movement.</span></h2>
            <p class="section-intro">
                Spaces and facilities to support different parts of your
                fitness routine.
            </p>
        </div>

        <div class="facility-grid">
            <article class="facility-card">
                <div class="facility-image">
                    <img
                        src="https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=900&q=85"
                        alt="Strength training equipment"
                        loading="lazy"
                    >
                    <span class="facility-number">01</span>
                </div>
                <div class="facility-copy">
                    <p class="facility-tag">BUILD STRENGTH</p>
                    <h3>Strength Training</h3>
                    <p>Equipment to support strength development and
                       resistance workouts.</p>
                </div>
            </article>

            <article class="facility-card">
                <div class="facility-image">
                    <img
                        src="https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&fit=crop&w=900&q=85"
                        alt="Cardio workout"
                        loading="lazy"
                    >
                    <span class="facility-number">02</span>
                </div>
                <div class="facility-copy">
                    <p class="facility-tag">KEEP MOVING</p>
                    <h3>Cardio Zone</h3>
                    <p>Work on endurance and keep your training routine
                       varied and engaging.</p>
                </div>
            </article>

            <article class="facility-card">
                <div class="facility-image">
                    <img
                        src="https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=900&q=85"
                        alt="Functional exercise and stretching"
                        loading="lazy"
                    >
                    <span class="facility-number">03</span>
                </div>
                <div class="facility-copy">
                    <p class="facility-tag">MOVE BETTER</p>
                    <h3>Functional Training</h3>
                    <p>Space for movement-based exercises, flexibility
                       and conditioning.</p>
                </div>
            </article>
        </div>
    </section>

    <!-- CONTACT -->
    <section id="contact" class="contact-section">
        <div class="contact-panel">
            <div class="contact-content">
                <p class="section-kicker">YOUR NEXT STEP STARTS HERE</p>
                <h2>Ready To Make<br><span>Your Move?</span></h2>
                <p>
                    Take the next step in your fitness journey.
                    Sign in to your account to continue.
                </p>

                <a href="/gym-management/auth/login.php"
                   class="button button-primary">
                    Get Started <span aria-hidden="true">↗</span>
                </a>
            </div>

            <div class="contact-decoration" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </section>
</main>

<footer class="home-footer">
    <div class="footer-brand">
        <a href="#home" class="home-logo footer-logo">
            VORTEX <span>FITNESS</span>
        </a>
        <p>Train Better. Live Stronger.</p>
    </div>

    <div class="footer-links">
        <a href="#about">About</a>
        <a href="#plans">Membership</a>
        <a href="#facilities">Facilities</a>
        <a href="#contact">Contact</a>
    </div>

    <div class="footer-bottom">
        <p>© <?= date("Y") ?> VORTEX FITNESS. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
