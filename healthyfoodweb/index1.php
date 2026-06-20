<?php
require_once __DIR__ . '/init.php';
include __DIR__ . '/header.php';
?>

<!--Mesh Hero -->
<section class="mesh-gradient"
    style="min-height: 95vh; display: flex; align-items: center; position: relative; overflow: hidden; padding: 100px 0;">
    <div class="floating-shape"
        style="width: 500px; height: 500px; top: -100px; right: -100px; background: #ff6b35; opacity: 0.2;"></div>
    <div class="floating-shape"
        style="width: 400px; height: 400px; bottom: -100px; left: -100px; background: #ff9f1c; opacity: 0.1; animation-delay: -5s;">
    </div>

    <div class="container" style="position: relative; z-index: 10;">
        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 60px; align-items: center;">
            <div class="animate-up">
                <span class="section-tag"
                    style="background: rgba(255,107,53,0.15); border: 1px solid rgba(255,107,53,0.3); color: #ff6b35;">Future
                    of Nutrition</span>
                <h1 class="text-gradient"
                    style="font-size: 5rem; line-height: 1; font-weight: 900; margin-bottom: 25px;">Evolve Your
                    Diet,<br>Master Your Body.</h1>
                <p style="font-size: 1.4rem; color: #94a3b8; max-width: 600px; margin-bottom: 45px; line-height: 1.6;">
                    The world's most advanced organic meal delivery system. Combining algorithmic nutrition with
                    professional chef craft.</p>

                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="shop.php" class="btn-action">
                        Enter Shop <i class="fas fa-shopping-basket"></i>
                    </a>
                    <a href="recommendations.php" class="btn-action">
                        Analyze Macros <i class="fas fa-calculator"></i>
                    </a>
                    <a href="chat.php" class="btn-action">
                        Get Help <i class="fas fa-robot"></i>
                    </a>
                </div>
            </div>

            <div class="hero-visual-box animate-up"
                style="animation-delay: 0.2s; padding: 0; overflow: hidden; border: none;">
                <img src="https://images.unsplash.com/photo-1547592166-23ac45744acd?q=80&w=1471&auto=format&fit=crop"
                    alt="Dietify Nutrition" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.8;">

                <div
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(15, 23, 42, 0.4), transparent);">
                </div>
                <div
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, #ff6b35, transparent); animation: scan 3s infinite linear;">
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    @keyframes scan {
        0% {
            top: 0;
        }

        100% {
            top: 100%;
        }
    }
</style>

<!-- Capabilities Section -->
<section style="padding: 120px 0; background: #fafafa;">
    <div class="container" style="text-align: center;">
        <div style="margin-bottom: 80px;">
            <span class="section-tag">Capabilities</span>
            <h2 style="font-size: 3rem; color: #0f172a; font-weight: 800;">Powerful Integration</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <div class="glow-on-hover animate-up"
                style="background: white; padding: 45px; border-radius: 25px; border: 1px solid #eee; text-align: left;">
                <div style="font-size: 2.5rem; color: #ff6b35; margin-bottom: 25px;"><i class="fas fa-bolt"></i></div>
                <h3 style="color: #0f172a; font-size: 1.6rem; margin-bottom: 15px;">Lightning Delivery</h3>
                <p style="color: #64748b; line-height: 1.6;">Our logistics engine predicts traffic patterns to get your
                    food hot exactly when you need it.</p>
            </div>

            <div class="glow-on-hover animate-up"
                style="background: white; padding: 45px; border-radius: 25px; border: 1px solid #eee; text-align: left; animation-delay: 0.1s;">
                <div style="font-size: 2.5rem; color: #ff6b35; margin-bottom: 25px;"><i class="fas fa-search"></i></div>
                <h3 style="color: #0f172a; font-size: 1.6rem; margin-bottom: 15px;">Fresh & Balanced</h3>
                <p style="color: #64748b; line-height: 1.6;">Discover a better way to eat with fresh, wholesome meals
                    made from carefully selected ingredients that support your healthy lifestyle every single day.</p>
            </div>

            <div class="glow-on-hover animate-up"
                style="background: white; padding: 45px; border-radius: 25px; border: 1px solid #eee; text-align: left; animation-delay: 0.2s;">
                <div style="font-size: 2.5rem; color: #ff6b35; margin-bottom: 25px;"><i class="fas fa-shield-alt"></i>
                </div>
                <h3 style="color: #0f172a; font-size: 1.6rem; margin-bottom: 15px;">Zero-Waste Ops</h3>
                <p style="color: #64748b; line-height: 1.6;">Our system minimizes organic waste through advanced demand
                    forecasting and precision prep.</p>
            </div>
        </div>
    </div>
</section>
<section style="background: #0f172a; padding: 120px 0; color: white;">
    <div class="container" style="text-align: center;">
        <span class="section-tag" style="background: rgba(255,255,255,0.1); color: #fff;">Workflow</span>
        <h2 style="font-size: 3rem; font-weight: 800; margin-top: 15px; margin-bottom: 60px;">The Advanced Cycle</h2>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;">
            <div class="glow-on-hover animate-up"
                style="background: rgba(255,255,255,0.03); padding: 50px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="font-size: 3rem; font-weight: 800; color: #ff6b35; margin-bottom: 20px;">01</div>
                <h3 style="font-size: 1.5rem; margin-bottom: 15px;">Algorithmic Prep</h3>
                <p style="color: #94a3b8; line-height: 1.6;">We calculate your macros to help you eat smarter every day.
                </p>
            </div>
            <div class="glow-on-hover animate-up"
                style="background: rgba(255,255,255,0.03); padding: 50px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.05); animation-delay: 0.1s;">
                <div style="font-size: 3rem; font-weight: 800; color: #ff6b35; margin-bottom: 20px;">02</div>
                <h3 style="font-size: 1.5rem; margin-bottom: 15px;">Molecular Control</h3>
                <p style="color: #94a3b8; line-height: 1.6;">Every ingredient is verified for organic purity and
                    nutritional density.</p>
            </div>
            <div class="glow-on-hover animate-up"
                style="background: rgba(255,255,255,0.03); padding: 50px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.05); animation-delay: 0.2s;">
                <div style="font-size: 3rem; font-weight: 800; color: #ff6b35; margin-bottom: 20px;">03</div>
                <h3 style="font-size: 1.5rem; margin-bottom: 15px;">Smart Logistics</h3>
                <p style="color: #94a3b8; line-height: 1.6;">Direct-to-door ecosystem bypasses traditional retail
                    delays.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section style="padding: 100px 20px; background: white;">
    <div class="container animate-up"
        style="text-align: center; background: #ff6b35; padding: 100px 40px; border-radius: 50px; color: white;">
        <h2 style="font-size: 3.5rem; margin-bottom: 20px; font-weight: 900;">Ready for the Future?</h2>
        <p style="font-size: 1.3rem; opacity: 0.9; max-width: 600px; margin: 0 auto 40px;">Stop eating for a past
            version of yourself. Start eating for the future you.</p>
        <div style="display: flex; gap: 20px; justify-content: center;">
            <a href="register.php" class="btn-primary"
                style="background: white; color: #ff6b35; padding: 20px 50px; font-size: 1.1rem; border-radius: 15px; text-decoration: none; font-weight: 800;">Join
                Free Membership</a>
            <a href="shop.php" class="btn-primary"
                style="background: white; color: #ff6b35; padding: 20px 50px; font-size: 1.1rem; border-radius: 15px; text-decoration: none; font-weight: 800;">Explore
                Menu</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer style="background: #111; color: #777; padding: 60px 20px; text-align: center; border-top: 1px solid #222;">
    <div class="container">
        <h2 style="color: #ff6b35; margin-bottom: 20px;"><?php echo SITE_NAME; ?></h2>
        <p style="max-width: 600px; margin: 0 auto 30px;">The world's most advanced nutritional platform for
            professionals and health enthusiasts alike.</p>
        <div style="margin-bottom: 30px; display: flex; justify-content: center; gap: 25px; font-size: 1.2rem;">
            <a href="#" style="color: inherit;"><i class="fab fa-facebook"></i></a>
            <a href="#" style="color: inherit;"><i class="fab fa-instagram"></i></a>
            <a href="#" style="color: inherit;"><i class="fab fa-twitter"></i></a>
        </div>
        <div style="margin-bottom: 20px; display: flex; justify-content: center; gap: 20px; font-size: 0.85rem;">
            <a href="tos.php" style="color: #ff6b35; text-decoration: none;">Terms of Service</a>
            <span style="color: #333;">|</span>
            <a href="privacy.php" style="color: #ff6b35; text-decoration: none;">Privacy Policy</a>
        </div>
        <p style="font-size: 0.9rem;">Copyrights &copy; 2026 <?php echo SITE_NAME; ?>. All rights reserved.</p>
    </div>
</footer>

</main>

<script>
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.animate-up').forEach((el) => observer.observe(el));
</script>

</body>

</html>