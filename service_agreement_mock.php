<?php
include "connection.php";
include "header.php";
?>

<br><br><br>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perjanjian Caj Servis</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body{
  margin:0;
  font-family:'Inter',sans-serif;
  background:#f4f6f9;
  color:#2c3e50;
}

.container{
  max-width:1100px;
  margin:40px auto;
  background:#fff;
  padding:30px;
  border-radius:8px;
  box-shadow:0 8px 25px rgba(0,0,0,.08);
}

.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:30px;
}

.badge{
  padding:6px 14px;
  border-radius:20px;
  font-size:13px;
  font-weight:600;
}

.pending{ background:#fff4e5; color:#b26a00; }
.approved{ background:#e6f6ec; color:#1e7d3b; }

.section{
  margin-bottom:30px;
}

.section h3{
  margin-bottom:15px;
  font-size:18px;
  font-weight:600;
  border-bottom:1px solid #eee;
  padding-bottom:8px;
}

.grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
}

.card{
  background:#fafafa;
  padding:15px;
  border-radius:6px;
  border:1px solid #eee;
}

table{
  width:100%;
  border-collapse:collapse;
}

th,td{
  padding:12px;
  border-bottom:1px solid #eee;
  text-align:left;
}

th{
  background:#f9fafc;
  font-weight:600;
  font-size:14px;
}

.status{
  font-size:13px;
  font-weight:600;
}

.status-approved{ color:#1e7d3b; }
.status-pending{ color:#b26a00; }
.status-rejected{ color:#b91c1c; }

.summary{
  text-align:right;
  font-size:16px;
  font-weight:600;
}

button{
  padding:8px 14px;
  border-radius:4px;
  border:none;
  cursor:pointer;
  font-weight:600;
}

.btn-lulus{
  background:#1f3c88;
  color:#fff;
}

.btn-tolak{
  background:#fff;
  border:1px solid #b91c1c;
  color:#b91c1c;
}

.btn-bayar{
  background:#1e7d3b;
  color:#fff;
  padding:12px 20px;
  font-size:16px;
}

.btn-disabled{
  background:#ccc !important;
  cursor:not-allowed;
}

.audit{
  font-size:14px;
  line-height:1.6;
}

.checkbox{
  margin-top:10px;
}

@media(max-width:768px){
  .grid{
    grid-template-columns:1fr;
  }
}
</style>
</head>

<body>

<div class="container">

<div class="header">
  <div>
    <h2>Perjanjian Caj Servis</h2>
    <small>ID Perjanjian: AGR-2026-0001</small>
  </div>
  <div id="agreementStatus" class="badge pending">
    Menunggu Kelulusan Pelanggan
  </div>
</div>

<div class="section grid">
  <div class="card">
    <strong>Pelanggan</strong><br>
    Fatin<br>
    ID Pelanggan: C-1023
  </div>
  <div class="card">
    <strong>Penyedia Servis</strong><br>
    ABC Plumbing Services<br>
    ID Penyedia: S-4551
  </div>
</div>

<div class="section">
<h3>Pecahan Caj</h3>

<table>
<thead>
<tr>
<th>Peringkat</th>
<th>Keterangan</th>
<th>Jumlah (RM)</th>
<th>Status</th>
<th>Tindakan</th>
</tr>
</thead>
<tbody id="chargesTable">
</tbody>
</table>
</div>

<div class="section">
<h3>Ringkasan Kewangan</h3>
<div class="summary">
Jumlah Diluluskan: RM <span id="approvedTotal">0</span><br>
Jumlah Menunggu Kelulusan: RM <span id="pendingTotal">0</span><br>
Jumlah Perlu Dibayar (Diluluskan Sahaja): RM <span id="finalTotal">0</span>
</div>
</div>

<div class="section">
<h3>Rekod Tindakan</h3>
<div class="audit" id="auditLog"></div>
</div>

<div class="section" style="text-align:right;">
<button id="payBtn" class="btn-bayar btn-disabled" disabled>
Teruskan ke Pembayaran
</button>
</div>

</div>

<script>
const charges = [
  {id:1, stage:"Pemeriksaan", desc:"Lawatan awal ke lokasi", amount:200, status:"approved"},
  {id:2, stage:"Kerja Pembetulan", desc:"Penggantian paip rosak", amount:400, status:"approved"},
  {id:3, stage:"Peralatan & Bahan", desc:"Bahan dan peralatan tambahan", amount:850, status:"pending"}
];

function render(){
  const table = document.getElementById("chargesTable");
  table.innerHTML = "";

  let approved = 0;
  let pending = 0;

  charges.forEach(c=>{
    if(c.status==="approved") approved += c.amount;
    if(c.status==="pending") pending += c.amount;

    const row = document.createElement("tr");

    let statusClass="";
    if(c.status==="approved") statusClass="status-approved";
    if(c.status==="pending") statusClass="status-pending";
    if(c.status==="rejected") statusClass="status-rejected";

    let actionHTML="-";

    if(c.status==="pending"){
      actionHTML = `
        <button class="btn-lulus" onclick="lulus(${c.id})">Luluskan</button>
        <button class="btn-tolak" onclick="tolak(${c.id})">Tolak</button>
        <div class="checkbox">
          <input type="checkbox" id="chk${c.id}">
          <label for="chk${c.id}">Saya faham dan bersetuju dengan caj ini</label>
        </div>
      `;
    }

    row.innerHTML = `
      <td>${c.stage}</td>
      <td>${c.desc}</td>
      <td>${c.amount}</td>
      <td class="status ${statusClass}">${c.status.toUpperCase()}</td>
      <td>${actionHTML}</td>
    `;

    table.appendChild(row);
  });

  document.getElementById("approvedTotal").innerText = approved;
  document.getElementById("pendingTotal").innerText = pending;
  document.getElementById("finalTotal").innerText = approved;

  updateStatus();
}

function lulus(id){
  const checkbox = document.getElementById("chk"+id);
  if(!checkbox || !checkbox.checked){
    alert("Sila sahkan persetujuan sebelum meluluskan.");
    return;
  }

  const charge = charges.find(c=>c.id===id);
  charge.status="approved";
  addLog("Caj '"+charge.stage+"' telah diluluskan.");
  render();
}

function tolak(id){
  const charge = charges.find(c=>c.id===id);
  charge.status="rejected";
  addLog("Caj '"+charge.stage+"' telah ditolak.");
  render();
}

function addLog(text){
  const log = document.getElementById("auditLog");
  const now = new Date().toLocaleString();
  log.innerHTML += "• " + text + " (" + now + ")<br>";
}

function updateStatus(){
  const pendingExists = charges.some(c=>c.status==="pending");
  const approvedExists = charges.some(c=>c.status==="approved");

  const badge = document.getElementById("agreementStatus");
  const payBtn = document.getElementById("payBtn");

  if(pendingExists){
    badge.className="badge pending";
    badge.innerText="Menunggu Kelulusan Pelanggan";
    payBtn.disabled=true;
    payBtn.classList.add("btn-disabled");
  }else if(approvedExists){
    badge.className="badge approved";
    badge.innerText="Semua Caj Telah Diluluskan";
    payBtn.disabled=false;
    payBtn.classList.remove("btn-disabled");
  }else{
    badge.className="badge pending";
    badge.innerText="Tiada Caj Diluluskan";
    payBtn.disabled=true;
    payBtn.classList.add("btn-disabled");
  }
}

render();
addLog("Caj pemeriksaan telah diluluskan.");
addLog("Caj kerja pembetulan telah diluluskan.");
</script>


<?php include "footer.php"; ?>

</body>
</html>
