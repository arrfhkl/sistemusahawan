<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Status Servis</title>

<style>
body{
    font-family:'Segoe UI',sans-serif;
    background:#f4f6f9;
    margin:0;
}

.wrapper{
    max-width:1000px;
    margin:50px auto;
}

.card{
    background:white;
    padding:30px;
    border-radius:18px;
    box-shadow:0 10px 35px rgba(0,0,0,.05);
    margin-bottom:25px;
}

/* ===== STATUS BADGE ===== */
.badge{
    display:inline-block;
    padding:8px 20px;
    border-radius:50px;
    font-size:13px;
    font-weight:600;
}

.pending{background:#fff3cd;color:#856404;}
.approved{background:#d4edda;color:#155724;}
.in_progress{background:#d1ecf1;color:#0c5460;}
.completed{background:#e2e3e5;color:#383d41;}
.rejected{background:#f8d7da;color:#721c24;}

/* ===== PROGRESS ===== */
.progress{
    display:flex;
    justify-content:space-between;
    margin-top:35px;
}

.step{
    flex:1;
    text-align:center;
    position:relative;
}

.step::before{
    content:'';
    position:absolute;
    top:15px;
    left:-50%;
    width:100%;
    height:3px;
    background:#ddd;
    z-index:0;
}

.step:first-child::before{
    display:none;
}

.circle{
    width:36px;
    height:36px;
    border-radius:50%;
    background:#ddd;
    margin:auto;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
    color:#555;
    position:relative;
    z-index:1;
}

.active .circle{
    background:#007bff;
    color:white;
}

.step-label{
    margin-top:10px;
    font-size:12px;
}

/* ===== INFO ===== */
.section-title{
    font-weight:600;
    margin-bottom:20px;
    border-bottom:1px solid #eee;
    padding-bottom:10px;
}

.info-row{
    margin-bottom:18px;
}

.label{
    font-size:13px;
    color:#777;
}

.value{
    font-weight:500;
}

.preview-img{
    max-width:250px;
    border-radius:12px;
    margin-top:10px;
}

/* ===== BUTTON ===== */
.btn{
    display:inline-block;
    padding:12px 25px;
    border:none;
    border-radius:10px;
    background:#20c997;
    color:white;
    font-weight:600;
    cursor:pointer;
    margin-top:25px;
    text-decoration:none;
}

.btn:hover{
    opacity:.9;
}
</style>
</head>
<body>

<div class="wrapper">

<!-- STATUS + PROGRESS -->
<div class="card">

    <div class="badge in_progress">
        Dalam Proses
    </div>

    <div class="progress">

        <div class="step active">
            <div class="circle">1</div>
            <div class="step-label">Diminta</div>
        </div>

        <div class="step active">
            <div class="circle">2</div>
            <div class="step-label">Disahkan</div>
        </div>

        <div class="step active">
            <div class="circle">3</div>
            <div class="step-label">Dalam Proses</div>
        </div>

        <div class="step">
            <div class="circle">4</div>
            <div class="step-label">Selesai</div>
        </div>

    </div>
</div>

<!-- SERVICE INFO -->
<div class="card">

    <div class="section-title">Maklumat Servis</div>

    <div class="info-row">
        <div class="label">Nama Servis</div>
        <div class="value">Baiki Wiring</div>
    </div>

    <div class="info-row">
        <div class="label">Tarikh</div>
        <div class="value">15 Mac 2026</div>
    </div>

    <div class="info-row">
        <div class="label">Masa</div>
        <div class="value">10:00 Pagi</div>
    </div>

    <div class="info-row">
        <div class="label">Masalah Dilaporkan</div>
        <div class="value">
            Lampu ruang tamu tidak menyala dan terdapat percikan kecil pada suis.
        </div>
    </div>

    <div class="info-row">
        <div class="label">Imej Lampiran</div>
        <img src="https://via.placeholder.com/250x160" class="preview-img">
    </div>

    <a href="#" class="btn">
        Chat Usahawan
    </a>

</div>

</div>

</body>
</html>