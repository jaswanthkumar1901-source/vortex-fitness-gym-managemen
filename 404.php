
<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#000000">

    <title>404 - Page Not Found | VORTEX FITNESS</title>

    <link rel="stylesheet" href="/gym-management/css/404.css?v=5">
</head>

<body>
    <main class="error-page">

        <div class="error-content">

            <!-- Animated glitch 404 -->
            <div class="glitch-container" role="img" aria-label="Error 404">
                <h1 class="glitch-number glitch-outline" aria-hidden="true">404</h1>
                <h1 class="glitch-number glitch-top" aria-hidden="true">404</h1>
                <h1 class="glitch-number glitch-bottom" aria-hidden="true">404</h1>
                <h1 class="glitch-number glitch-white" aria-hidden="true">404</h1>
            </div>

            <!-- Error message panel -->
            <section class="error-message">

                <div class="error-heading">
                    <span class="error-indicator"></span>
                    <h2>Page Not Found</h2>
                </div>

                <p>
                    Looks like you've taken a wrong turn.
                    The page you're looking for doesn't exist,
                    may have been moved, or the link may be incorrect.
                    Let's get you back on track.
                </p>

                <div class="error-reference">
                    <span>STATUS</span>
                    <strong>404_NOT_FOUND</strong>
                    <span class="system-status">ROUTE UNAVAILABLE</span>
                </div>

            </section>
            
            <!-- Return to Home button -->
            <div class="error-actions">
                <a href="/gym-management/index.php" class="home-button">
                    <span class="home-arrow" aria-hidden="true">←</span>
                    <span>Return to Home</span>
                </a>
            </div>

            <!-- Footer hint without buttons -->
            <p class="error-hint">
                <span class="hint-dot"></span>
                VORTEX FITNESS · STRENGTH · DISCIPLINE · PROGRESS
            </p>

        </div>

        <!-- Bottom footer -->
        <footer class="error-footer">
            <span class="footer-brand">
                VORTEX <strong>FITNESS</strong>
            </span>

            <span>STRENGTH · DISCIPLINE · PROGRESS</span>

            <span>SYS.ERR_404</span>
        </footer>

    </main>
</body>
</html>
