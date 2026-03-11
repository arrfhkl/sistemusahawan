<?php 
include "connection.php";
include "header.php";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Process</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: auto;
        }

        .card {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        h2 {
            margin-top: 0;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            background: #ffe9a8;
        }

        .info-row {
            margin-bottom: 8px;
        }

        textarea, input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
        }

        .btn-primary { background: #007bff; color: #fff; }
        .btn-success { background: #28a745; color: #fff; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-warning { background: #ffc107; color: #000; }

        .progress-step {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .step {
            text-align: center;
            flex: 1;
            font-size: 12px;
        }

        .step span {
            display: block;
            width: 25px;
            height: 25px;
            margin: auto;
            border-radius: 50%;
            background: #ccc;
            line-height: 25px;
            margin-bottom: 5px;
        }

        .active span {
            background: #28a745;
            color: white;
        }

    </style>
</head>
<body>
<br><br><br><br><br>
<div class="container">

    <!-- SERVICE SUMMARY -->
    <div class="card">
    
        <h2>Service Request Summary</h2>

        <div class="info-row"><strong>Service:</strong> Laptop Repair</div>
        <div class="info-row"><strong>Seller:</strong> Ahmad Tech</div>
        <div class="info-row"><strong>Customer:</strong> Fatin</div>
        <div class="info-row"><strong>Request ID:</strong> SRV-20260223-001</div>
        <div class="info-row"><strong>Status:</strong> 
            <span class="status">WAITING FOR QUOTATION</span>
        </div>

        <!-- PROGRESS TRACKER -->
        <div class="progress-step">
            <div class="step active">
                <span>1</span>
                Requested
            </div>
            <div class="step">
                <span>2</span>
                Quotation
            </div>
            <div class="step">
                <span>3</span>
                In Progress
            </div>
            <div class="step">
                <span>4</span>
                Completed
            </div>
        </div>
    </div>

    <!-- CUSTOMER ISSUE -->
    <div class="card">
        <h2>Customer Description</h2>
        <p>
            My laptop screen flickers and sometimes turns black.
            It happens randomly while working.
        </p>
    </div>

    <!-- SELLER QUOTATION SECTION -->
    <div class="card">
        <h2>Submit Quotation (Seller View)</h2>

        <label>Quotation Amount (RM)</label>
        <input type="number" placeholder="Enter amount">

        <label>Notes</label>
        <textarea rows="4" placeholder="Explain service charges, parts, etc..."></textarea>

        <button class="btn-primary">Submit Quotation</button>
    </div>

    <!-- CUSTOMER ACTION SECTION -->
    <div class="card">
        <h2>Quotation Review (Customer View)</h2>

        <p><strong>Amount:</strong> RM 250</p>
        <p><strong>Notes:</strong> Screen cable replacement + labor charge.</p>

        <button class="btn-success">Accept</button>
        <button class="btn-danger">Reject</button>
    </div>

    <!-- SERVICE PROGRESS SECTION -->
    <div class="card">
        <h2>Service Progress</h2>

        <p>Service is currently in progress...</p>

        <button class="btn-warning">Mark as Completed (Seller)</button>
    </div>

    <!-- COMPLETION CONFIRMATION -->
    <div class="card">
        <h2>Completion Confirmation (Customer)</h2>

        <p>Seller has marked this service as completed.</p>

        <button class="btn-success">Confirm Completed</button>
        <button class="btn-danger">Report Issue</button>
    </div>

</div>

<?php include "footer.php"; ?>
</body>
</html>

