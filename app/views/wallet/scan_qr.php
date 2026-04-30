s<?php 
$content = ob_start();
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-qrcode me-2"></i>Scan QR Code</h4>
            </div>
            <div class="card-body text-center">
                <div id="qr-reader" style="width:100%;max-width:400px;margin:0 auto;"></div>
                <div id="scan-result" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;

function onScanSuccess(decodedText) {
    document.getElementById('scan-result').innerHTML = `
        <div class="alert alert-success">
            <h6>QR Scanned: ${decodedText}</h6>
            <button class="btn btn-primary" onclick="payWithQR('${decodedText}')">Pay Now</button>
        </div>
    `;
    html5QrCode.stop();
}

function payWithQR(qrString) {
    // AJAX scanQR
    $.post('<?php echo APP_URL; ?>/index.php?page=scan_qr', {qr_string: qrString}, function(response) {
        if (response.success) {
            // Redirect to pay
            window.location = '<?php echo APP_URL; ?>/index.php?page=send_money&receiver=' + response.wallet_number;
        } else {
            alert(response.message);
        }
    });
}

html5QrCode = new Html5Qrcode("qr-reader");
html5QrCode.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: {width: 250, height: 250} },
    onScanSuccess,
    undefined
).catch(err => console.error(err));
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
