<?php
require_once 'util.php';
generate_head('Classes & Home Service');
generate_header();
?>

<div class="container">
  <h2 class="group-title">One-on-One Lash Training (2 weeks)</h2>
  <div class="grid">
    <div class="card">
      <div class="service-title">With Kit — ₵1500</div>
      <div class="duration">Duration: 2 weeks (private sessions)</div>
      <div class="note">Includes starter kit and tools.</div>
      <div style="margin-top:12px"><a class="btn" onclick="chooseTraining('With Kit',1500)">Book With Kit</a></div>
    </div>

    <div class="card">
      <div class="service-title">Without Kit — ₵900</div>
      <div class="duration">Duration: 2 weeks</div>
      <div class="note">Training only, no equipment.</div>
      <div style="margin-top:12px"><a class="btn" onclick="chooseTraining('Without Kit',900)">Book Without Kit</a></div>
    </div>
  </div>

 <section class="container" style="margin-top:20px">
    <h2 class="group-title" style="text-align:center;">Lash Class</h2>
    
    <div class="card" style="padding:20px 25px;">
        <ul style="list-style: disc; padding-left: 20px; margin: 0; color: var(--text);">
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Lash Anatomy</div>
                <div class="note">Gain a deep understanding of natural lash structure, growth cycles, and healthy lash care.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Eye Shape Recognition</div>
                <div class="note">Learn to analyze eye shapes and customize lash designs that enhance natural beauty.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Lash Curl Types</div>
                <div class="note">Explore a range of lash curls and how to select the perfect curl for refined, flawless results.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Fanning Techniques</div>
                <div class="note">Master professional fanning techniques for creating soft, lightweight  lash sets.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Lash Mapping</div>
                <div class="note">Learn advanced mapping methods to design balanced, elegant, and customized lash looks.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Proper Placement & Isolation</div>
                <div class="note">Perfect your placement and isolation skills for clean application and long-lasting retention.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Safety & Sanitation</div>
                <div class="note">Understand professional hygiene standards to ensure a safe and trusted lash practice.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Live Model Demonstration</div>
                <div class="note">Perform a full lash application on a live model with guided, hands-on instruction.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Publicity & Marketing</div>
                <div class="note">Discover how to build your brand, attract premium clients, and grow a successful lash business.</div>
            </li>
            <li style="margin-bottom: 10px;">
                <div class="service-title" style="font-size:16px; margin-bottom: 2px;">Certificate of Completion</div>
                <div class="note">Receive a certificate upon successful completion of the training program.</div>
            </li>
        </ul>
    </div>
</section>
  <div style="text-align:center;margin-top:18px">
    <a class="btn" href="services.php">Back to Services</a>
  </div>
</div>

<script>
const SESSION_API = 'api/session.php';

async function chooseTraining(option, price){
  //training duration is 2 weeks or 14 days 
  const svc = { name: 'One-on-One Training ('+option+')', price, minutes: 14*24*60 };

  try {
    const resp = await fetch(`${SESSION_API}?action=set_service`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ service: svc })
    });
    const json = await resp.json();
    if (!json.success) throw new Error(json.message || 'Failed to save service');

    await fetch(`${SESSION_API}?action=set_booking_details`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ persons: 1 })
    });

    Swal.fire({icon:'success', title:'Training selected', timer:1000, showConfirmButton:false}).then(()=> window.location.href='booking.php');
  } catch (err) {
    console.error('Error selecting training:', err);
    Swal.fire({icon:'error', title:'Error', text:'Could not select training. Please try again.', confirmButtonColor:'#D63384'});
  }
}


</script>

