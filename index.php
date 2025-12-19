<?php
require_once 'util.php';
generate_head('Lash Nouveau — Home');
generate_header();
?>

<section class="hero container" style="min-height: 50vh; padding-bottom: 30px;">
  <div>
    <h1 style="font-size: 40px;">Enhance Your Beauty with Lash Nouveau</h1>
    <p>Luxury Lash Extensions • Brow Services • One-on-One Training</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:18px">
      <a class="btn" href="services.php">View Services</a>
      <a class="btn" href="specials.php">Classes & Home Service</a>
    </div>
  </div>
</section>

<section class="container" style="margin-top:12px">
  <h2 class="group-title">Quick Preview</h2>
  <div class="grid">
    <div class="card"><div class="service-title">Lash Extensions</div><div class="note">Full sets: Classic, Classic Mix, Hybrid, Volume, Wet Set.</div></div>
    <div class="card"><div class="service-title">Refills</div><div class="note">Refill options to keep your lashes full and beautiful.</div></div>
    <div class="card"><div class="service-title">Brow Services</div><div class="note">Tinting, grooming & lamination available.</div></div>
    <div class="card"><div class="service-title">One-on-One Training</div><div class="note">Private 2-week course — with or without kit.</div></div>
  </div>
</section>

<section class="container" style="margin-top:20px">
    <h2 class="group-title" style="text-align:center;">Booking Policies</h2>
    
    <div class="card" style="padding:20px 25px;">
        <ul style="list-style: disc; padding-left: 20px; margin: 0; color: var(--text);">
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Preparation</div>
                <div class="note">Avoid wearing any eye makeup or strip lashes before your appointment.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Guests</div>
                <div class="note">No extra guests allowed at your appointment.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Cancellations</div>
                <div class="note">Cancellation of an appointment should be done at least **12 hours** before the scheduled time.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Refill Requirement</div>
                <div class="note">For refills, at least **30%** of your lashes should be on. Clients unsure about this can send a video or picture for confirmation.</div>
            </li>
            <li style="margin-bottom: 0;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Set Details</div>
                <div class="note">Send inspiration pictures (inspo's) if needed to confirm your desired set details.</div>
            </li>
        </ul>
    </div>
</section>

<?php generate_footer(); ?>