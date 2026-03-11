<?php 
include 'connection.php';
include 'header.php';
?>
<br>
<br>
<br>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perjanjian Caj Servis Makeup – Simulasi Masa Nyata</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body{
  margin:0;
  font-family:'Inter',sans-serif;
  background:#f4f6f9;
}

.container{
  max-width:1100px;
  margin:40px auto;
  background:#fff;
  padding:30px;
  border-radius:8px;
  box-shadow:0 8px 25px rgba(0,0,0,.08);
}

h2{margin-bottom:5px;}

.badge{
  padding:6px 14px;
  border-radius:20px;
  font-size:13px;
  font-weight:600;
}

.pending{background:#fff4e5;color:#b26a00;}
.approved{background:#e6f6ec;color:#1e7d3b;}

table{
  width:100%;
  border-collapse:collapse;
  margin-top:20px;
}

th,td{
  padding:12px;
  border-bottom:1px solid #eee;
  text-align:left;
}

th{background:#f9fafc;}

.summary{
  text-align:right;
  margin-top:20px;
  font-weight:600;
  font-size:16px;
}

button{
  padding:8px 14px;
  border-radius:4px;
  border:none;
  cursor:pointer;
  font-weight:600;
}

.btn-primary{
  background:#9b174d;
  color:#fff;
}

.btn-danger{
  background:#fff;
  border:1px solid #b91c1c;
  color:#b91c1c;
}

.btn-success{
  background:#1e7d3b;
  color:#fff;
  padding:12px 20px;
}

.btn-disabled{
  background:#ccc !important;
  cursor:not-allowed;
}

.audit{
  margin-top:20px;
  font-size:14px;
  line-height:1.6;
}

/* Modal */
.modal{
  position:fixed;
  top:0;left:0;
  width:100%;height:100%;
  background:rgba(0,0,0,.45);
  display:none;
  justify-content:center;
  align-items:center;
}

.modal-content{
  background:#fff;
  width:420px;
  padding:25px;
  border-radius:6px;
  box-shadow:0 10px 30px rgba(0,0,0,.15);
}

.modal h3{
  margin-top:0;
}

.modal-footer{
  text-align:right;
  margin-top:15px;
}
</style>
</head>

<body>

<div class="container">

<h2>Perjanjian Caj Servis Makeup</h2>
<div id="agreementStatus" class="badge approved">Semua Caj Telah Diluluskan</div>

<table>
<thead>
<tr>
<th>Fasa</th>
<th>Keterangan</th>
<th>Jumlah (RM)</th>
<th>Status</th>
</tr>
</thead>
<tbody id="chargeTable"></tbody>
</table>

<div class="summary">
Jumlah Diluluskan: RM <span id="approvedTotal">0</span>
</div>

<div style="margin-top:20px;">
<strong>Simulasi Makeup Artist:</strong><br><br>
<button class="btn-primary" onclick="hantarCajBaru()">
+ Hantar Caj Tambahan (RM300 Touch-Up & Bulu Mata)
</button>
</div>

<div class="audit" id="auditLog"></div>

<div style="text-align:right;margin-top:30px;">
<button id="payBtn" class="btn-success">
Teruskan ke Pembayaran
</button>
</div>

</div>

<!-- Modal -->
<div class="modal" id="approvalModal">
  <div class="modal-content">
    <h3>Caj Tambahan Dihantar</h3>
    <p><strong id="modalStage"></strong></p>
    <p>Jumlah: RM <strong id="modalAmount"></strong></p>
    <div>
      <input type="checkbox" id="confirmChk">
      <label for="confirmChk">Saya faham dan bersetuju dengan caj ini</label>
    </div>
    <div class="modal-footer">
      <button class="btn-danger" onclick="tolakCaj()">Tolak</button>
      <button class="btn-primary" onclick="luluskanCaj()">Luluskan</button>
    </div>
  </div>
</div>

<script>
let charges = [
  {id:1,fasa:"Deposit Tempahan",desc:"Tempahan tarikh majlis",amount:200,status:"approved"},
  {id:2,fasa:"Makeup Utama",desc:"Makeup pengantin termasuk asas premium",amount:800,status:"approved"}
];

let pendingCharge = null;

function render(){
  const table = document.getElementById("chargeTable");
  table.innerHTML="";
  let total=0;
  let pendingExists=false;

  charges.forEach(c=>{
    if(c.status==="approved") total+=c.amount;
    if(c.status==="pending") pendingExists=true;

    const row=document.createElement("tr");
    row.innerHTML=`
      <td>${c.fasa}</td>
      <td>${c.desc}</td>
      <td>${c.amount}</td>
      <td>${c.status.toUpperCase()}</td>
    `;
    table.appendChild(row);
  });

  document.getElementById("approvedTotal").innerText=total;

  const badge=document.getElementById("agreementStatus");
  const payBtn=document.getElementById("payBtn");

  if(pendingExists){
    badge.className="badge pending";
    badge.innerText="Menunggu Kelulusan Pelanggan";
    payBtn.disabled=true;
    payBtn.classList.add("btn-disabled");
  }else{
    badge.className="badge approved";
    badge.innerText="Semua Caj Telah Diluluskan";
    payBtn.disabled=false;
    payBtn.classList.remove("btn-disabled");
  }
}

function hantarCajBaru(){
  const newCharge={
    id:Date.now(),
    fasa:"Touch-Up & Bulu Mata Palsu",
    desc:"Touch-up tambahan semasa majlis + bulu mata premium",
    amount:300,
    status:"pending"
  };

  charges.push(newCharge);
  pendingCharge=newCharge;

  document.getElementById("modalStage").innerText=newCharge.fasa;
  document.getElementById("modalAmount").innerText=newCharge.amount;

  document.getElementById("approvalModal").style.display="flex";
  tambahLog("Makeup Artist menghantar caj tambahan RM300.");
  render();
}

function luluskanCaj(){
  if(!document.getElementById("confirmChk").checked){
    alert("Sila tandakan persetujuan terlebih dahulu.");
    return;
  }

  pendingCharge.status="approved";
  tambahLog("Pelanggan meluluskan caj RM"+pendingCharge.amount+".");
  tutupModal();
  render();
}

function tolakCaj(){
  pendingCharge.status="rejected";
  tambahLog("Pelanggan menolak caj RM"+pendingCharge.amount+".");
  tutupModal();
  render();
}

function tutupModal(){
  document.getElementById("approvalModal").style.display="none";
  document.getElementById("confirmChk").checked=false;
}

function tambahLog(text){
  const log=document.getElementById("auditLog");
  const now=new Date().toLocaleString();
  log.innerHTML+="• "+text+" ("+now+")<br>";
}

render();
tambahLog("Deposit tempahan telah diluluskan.");
tambahLog("Caj makeup utama telah diluluskan.");
</script>

</body>
</html>