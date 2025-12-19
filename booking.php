<?php
require_once 'util.php';
generate_head('Booking');
generate_header();
?>

<div class="container">
  <h2 class="group-title">Confirm Your Booking</h2>

  <div class="card" id="summaryCard" style="margin-bottom:16px">
    <div id="svcName" class="service-title">Selected service: —</div>
    <div id="svcMeta" class="small-muted"></div>
    <div id="extrasList" class="note" style="margin-top:10px"></div>
    <div id="homeInfo" class="note" style="margin-top:8px;color:#6b3b52"></div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <strong>Number of persons</strong>
    <div class="note" style="margin-bottom:8px">You can book for yourself plus up to 2 additional people (max 3).</div>
    <div style="display:flex;align-items:center;gap:12px">
      <div class="person-controls">
        <button id="minusBtn" onclick="changePersons(-1)">−</button>
        <div class="person-count" id="personCount">1</div>
        <button id="plusBtn" onclick="changePersons(1)">+</button>
      </div>
      <div class="small-muted">Price and time will multiply by number of persons</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:12px">
    <label class="small-muted">Choose Date</label><br>
    <input type="date" id="dateField" style="margin-top:8px">
  </div>

  <div class="card" style="margin-bottom:12px">
    <label class="small-muted">Choose Time</label><br>
    <input type="time" id="timeField" style="margin-top:8px">
  </div>

  <div class="card" style="margin-bottom:12px">
    <label class="small-muted">Notes for the lash tech (optional)</label><br>
    <textarea id="notesField" placeholder="e.g. sensitive eyes, allergies, preferences..." rows="4" style="margin-top:8px"></textarea>
  </div>

  <div style="text-align:center; margin-top:12px">
    <button class="btn" onclick="goToConfirmation()">Continue to Confirmation</button>
    <button class="btn" onclick="window.location.href='index.php'">Cancel</button>
  </div>
</div>

<script>
const SESSION_API = 'api/session.php';

async function migrateLocalStorageToSession(){
  try {
    const lsSvc = localStorage.getItem('selected_service');
    const lsExtras = localStorage.getItem('selected_extras');
    const lsPersons = localStorage.getItem('additional_persons');
    const lsHome = localStorage.getItem('home_service');

    if (lsSvc) {
      let svc = null;
      try { svc = JSON.parse(lsSvc); } catch(e) { svc = null; }
      if (svc && svc.name) {
        await fetch(`${SESSION_API}?action=set_service`, {
          method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ service: svc })
        });
      }
      localStorage.removeItem('selected_service');
    }

    if (lsExtras) {
      try {
        const extras = JSON.parse(lsExtras) || [];
        for (const ex of extras) {
          await fetch(`${SESSION_API}?action=toggle_extra`, {
            method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ extra: ex })
          });
        }
      } catch (e) { /* ignores parse errors */ }
      localStorage.removeItem('selected_extras');
    }

    if (lsPersons) {
      const p = parseInt(lsPersons) || 1;
      await fetch(`${SESSION_API}?action=set_booking_details`, {
        method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ persons: p })
      });
      localStorage.removeItem('additional_persons');
    }

    if (lsHome) {
      await fetch(`${SESSION_API}?action=set_booking_details`, {
        method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ home_service: true })
      });
      localStorage.removeItem('home_service');
    }
  } catch (err) {
    console.warn('Migration to session API failed:', err);
  }
}
async function readStorage(){
  try {
    const response = await fetch(`${SESSION_API}?action=get_all`);
    const data = await response.json();
    
    if (!data.success) {
      console.error('Failed to load session:', data.message);
      return { svc: null, extras: [], persons: 1, home: false };
    }
    
    return {
      svc: data.service,
      extras: data.extras || [],
      persons: data.persons || 1,
      home: data.home_service || false
    };
  } catch (error) {
    console.error('Error reading storage:', error);
    return { svc: null, extras: [], persons: 1, home: false };
  }
}

async function renderSummary(){
  const data = await readStorage();
  if(!data.svc){
    document.getElementById('svcName').innerText = 'No service selected yet.';
    document.getElementById('svcMeta').innerText = 'Please select a service from the Services or Specials page.';
    return;
  }
  let totalPrice = data.svc.price;
  let totalMinutes = data.svc.minutes;
  data.extras.forEach(e => {
    totalPrice += e.price;
    totalMinutes += e.minutes;
  });
  
  document.getElementById('svcName').innerText = data.svc.name + ' — ₵' + totalPrice + (data.extras.length ? ' (incl. extras)' : '');
  document.getElementById('svcMeta').innerText = 'Estimated base time: ' + totalMinutes + ' mins';
  document.getElementById('extrasList').innerText = data.extras.length ? 'Extras: ' + data.extras.map(e=>e.name + ' (₵'+e.price+')').join(', ') : 'Extras: none';
  document.getElementById('homeInfo').innerText = data.home ? 'Home service requested (travel fee ₵400–₵450 will be confirmed).' : '';
}

document.addEventListener('DOMContentLoaded', async ()=>{
  await migrateLocalStorageToSession();
  await renderSummary();
  // init persons
  let cur = 1;
  const data = await readStorage();
  cur = data.persons;
  document.getElementById('personCount').innerText = cur;
  document.getElementById('minusBtn').disabled = (cur===1);
  document.getElementById('plusBtn').disabled = (cur===3);
});

async function changePersons(delta){
  const data = await readStorage();
  let cur = data.persons;
  cur = cur + delta;
  if(cur < 1) cur = 1;
  if(cur > 3) {
    Swal.fire({icon:'warning', title:'Limit', text:'Max 3 persons (you + 2 more).', confirmButtonColor:'#D63384'});
    cur = 3;
  }
  
  try {
    await fetch(`${SESSION_API}?action=set_booking_details`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ persons: cur })
    });
  } catch (error) {
    console.error('Error updating persons:', error);
  }
  
  document.getElementById('personCount').innerText = cur;
  document.getElementById('minusBtn').disabled = (cur===1);
  document.getElementById('plusBtn').disabled = (cur===3);
}

async function goToConfirmation(){
  const date = document.getElementById('dateField').value;
  const time = document.getElementById('timeField').value;
  if(!date || !time){
    Swal.fire({icon:'warning', title:'Missing', text:'Choose a date and time.', confirmButtonColor:'#D63384'});
    return;
  }
  try {
    await fetch(`${SESSION_API}?action=set_booking_details`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        date: date,
        time: time,
        notes: document.getElementById('notesField').value || ''
      })
    });
  } catch (error) {
    console.error('Error saving booking details:', error);
    Swal.fire({icon:'error', title:'Error', text:'Failed to save booking details.', confirmButtonColor:'#D63384'});
    return;
  }
  
  window.location.href = 'confirmation.php';
}
</script>
