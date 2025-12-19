<?php
$api_endpoint = 'api/book_appointment.php'; 


require_once 'util.php';
generate_head('Summary');

echo "<script>const BOOKING_API_URL = '{$api_endpoint}';</script>";
generate_header();
?>

<div class="container">
  <h2 class="group-title">Review & Confirm Booking</h2>

  <div class="card" id="summaryCard"></div>

  <div class="card" style="margin-top:12px">
    <label class="small-muted">Your Full Name:</label><br>
    <input id="clientName" type="text" placeholder="Enter your name" style="width:100%;padding:10px;margin-top:6px;border-radius:6px;border:1px solid #ccc">

    <br><br>

    <label class="small-muted">Phone Number:</label><br>
    <input id="clientPhone" type="text" placeholder="Enter your phone number" style="width:100%;padding:10px;margin-top:6px;border-radius:6px;border:1px solid #ccc">
  </div>

  <div class="card" id="locationCard" style="margin-top:12px; display:none;">
    <label class="small-muted">Please enter your location (home service):</label><br>
    <input id="clientLocation" type="text" placeholder="Enter your full address, specific landmarks, or directions" style="width:100%;padding:10px;margin-top:6px;border-radius:6px;border:1px solid #ccc">
  </div>
  
  <div class="card" style="margin-top:12px">
    <label class="small-muted">Notes for the lash tech (optional):</label><br>
    <textarea id="finalNotes" rows="4" style="margin-top:8px;width:100%;padding:10px;border-radius:6px;border:1px solid #ccc"></textarea>
  </div>

  <div style="text-align:center;margin-top:14px">
    <button class="btn" onclick="finalConfirm()">Finalize & Book Now</button>
    <a class="btn" style="background:#fff; color:var(--deep-pink); box-shadow:none; margin-left:10px;" href="booking.php">Go Back / Edit Time</a>
  </div>
</div>

<script>
// Hardcoded lash tech's name my name Danita Sharon
const PROVIDER_NAME = "Danita Sharon";
const SESSION_API = 'api/session.php';

function computeTotals(svc, extras, persons){
  let total = svc.price * persons;
  let totalMinutes = svc.minutes * persons;
  extras.forEach(e=>{
    total += e.price * persons;
    totalMinutes += e.minutes * persons;
  });
  return { total, totalMinutes };
}

function formatDuration(minutes) {
    if (minutes < 60) return `${minutes} mins`;
    const hrs = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (mins === 0) return `${hrs} hr${hrs > 1 ? 's' : ''}`;
    return `${hrs} hr${hrs > 1 ? 's' : ''} ${mins} mins`;
}

async function renderConfirmation(){
  try {
    const response = await fetch(`${SESSION_API}?action=get_all`);
    const sessionData = await response.json();
    
    if (!sessionData.success) {
      document.getElementById('summaryCard').innerHTML = `<p>Error loading session. Please try again.</p>`;
      return;
    }

    const svc = sessionData.service;
    const extras = sessionData.extras || [];
    const persons = sessionData.persons || 1;
    const date = sessionData.date || '—';
    const time = sessionData.time || '—';
    const home = sessionData.home_service || false;
    const notes = sessionData.notes || '';

    if(!svc){
      document.getElementById('summaryCard').innerHTML = `<p>No service selected yet. Please go to <a href="services.php" style="color:var(--deep-pink); font-weight:600;">Services</a>.</p>`;
      return;
    }

    const totals = computeTotals(svc, extras, persons);
    const totalDuration = formatDuration(totals.totalMinutes);
    const baseServicePrice = svc.price; 

    document.getElementById('summaryCard').innerHTML = `
      <div style="font-weight:700; color:#6b2f51; margin-bottom:10px; font-size:20px;">
          ${svc.name}
      </div>
      
      <div class="note">
          <strong>Provider:</strong> ${PROVIDER_NAME}
      </div>
      <div class="note">
          <strong>Service Base Price:</strong> ₵${baseServicePrice}
          ${extras.length ? ` (+ ${extras.map(e => `₵${e.price}`).join(' + ')} extras)` : ''}
      </div>
      <div class="note">
          <strong>Total Persons:</strong> ${persons}
      </div>
      <div class="note" style="margin-top:10px; border-top: 1px solid #eee; padding-top: 8px;">
          <strong>Appointment Time:</strong> ${date} at ${time}
      </div>
      <div class="note">
          <strong>Estimated Total Duration:</strong> ${totalDuration}
      </div>
      
      <div style="font-weight:700; color:var(--deep-pink); margin-top:12px; font-size:18px;">
          GRAND TOTAL (x${persons} person(s)): ₵${totals.total}
      </div>

      <div style="margin-top:12px; padding-top:8px; border-top: 1px solid #eee;">
        ${home ? 'Home service requested (Travel fee ₵400–₵450 confirmed separately).' : 'Studio appointment'}
      </div>
    `;
    
    document.getElementById('finalNotes').value = notes;
    
    // Showing the kocation only if the user books for home service 
    if (home) {
      document.getElementById('locationCard').style.display = 'block';
    } else {
      document.getElementById('locationCard').style.display = 'none';
    }
  } catch (error) {
    console.error('Error rendering confirmation:', error);
    document.getElementById('summaryCard').innerHTML = `<p>Error loading session. Please try again.</p>`;
  }
}

document.addEventListener('DOMContentLoaded', renderConfirmation);

async function finalConfirm(){
  try {
    const response = await fetch(`${SESSION_API}?action=get_all`);
    const sessionData = await response.json();
    
    if (!sessionData.success) {
      Swal.fire({icon:'error', title:'Error', text:'Could not load session data.', confirmButtonColor:'#D63384'});
      return;
    }

    const finalNotes = document.getElementById('finalNotes').value || '';
    const clientName = document.getElementById('clientName').value.trim();
    const clientPhone = document.getElementById('clientPhone').value.trim();
    const homeServiceRequested = sessionData.home_service || false;
    const clientLocation = document.getElementById('clientLocation').value.trim();

    if(!clientName || !clientPhone){
      Swal.fire({
        icon:'warning',
        title:'Missing Details',
        text:'Please enter your name and phone number.',
        confirmButtonColor:'#D63384'
      });
      return;
    }

    if (homeServiceRequested && !clientLocation) {
       Swal.fire({
        icon:'warning',
        title:'Location Required',
        text:'Since you requested home service, please enter your full address or directions.',
        confirmButtonColor:'#D63384'
      });
      return;
    }

    Swal.fire({
      title:'Confirm Appointment',
      html:`<p>Your appointment details are complete. We will contact you for final confirmation.</p>`,
      showCancelButton:true,
      confirmButtonText:'Finalize & Book',
      confirmButtonColor:'#D63384'
    }).then(async (res)=>{
      if(res.isConfirmed){
        
        Swal.fire({
          title: 'Processing Booking...',
          text: 'Checking time slot and submitting.',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        const svc = sessionData.service;
        const extras = sessionData.extras || [];
        const persons = sessionData.persons || 1;
        const totals = computeTotals(svc, extras, persons);
        
        const booking_data = {
            clientName,
            clientPhone,
            booking: {
                service: svc,
                extras: extras,
                persons: persons,
                date: sessionData.date,
                time: sessionData.time,
                notes: finalNotes,
                home: homeServiceRequested,
                location: homeServiceRequested ? clientLocation : 'Studio Appointment',
                total_price: totals.total,
                duration_minutes: totals.totalMinutes,
                provider: PROVIDER_NAME 
            }
        };
        
        fetch(BOOKING_API_URL, { 
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(booking_data)
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response from API:', text);
                    throw new Error('API returned non-JSON response: ' + text.substring(0, 200));
                });
            }
            return response.json().then(data => ({ status: response.status, body: data }));
        })
        .then(({ status, body }) => {
            if (body.success) {
                fetch(`${SESSION_API}?action=clear_session`, { method: 'POST' });

                Swal.fire({
                    icon:'success',
                    title:'Booked!',
                    text: 'Your appointment has been submitted. We will contact you for confirmation.',
                    confirmButtonColor:'#D63384'
                }).then(()=>{
                    window.location.href='index.php';
                });
            } else {
                let title = (status === 409) ? 'Time Conflict' : 'Booking Failed';
                Swal.fire({
                    icon:'error',
                    title: title,
                    text: body.message || 'An unknown error occurred on the server.',
                    confirmButtonColor:'#D63384'
                });
            }
        })
        .catch(error => {
            console.error('Network Error:', error);
            Swal.fire({
                icon:'error',
                title:'Connection Error',
                text:'Could not reach the server. Please try again.',
                confirmButtonColor:'#D63384'
            });
        });
      }
    });
  } catch (error) {
    console.error('Error in finalConfirm:', error);
    Swal.fire({icon:'error', title:'Error', text:'An error occurred. Please try again.', confirmButtonColor:'#D63384'});
  }
}
</script>

<?php generate_footer(); ?>