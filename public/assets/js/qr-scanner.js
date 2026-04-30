
// QR Scanner Enhancement
document.addEventListener('DOMContentLoaded', function() {
  if (document.getElementById('qr-reader')) {
    const html5QrCode = new Html5Qrcode('qr-reader');
    html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: {width: 250, height: 250} },
      (decodedText) => {
        $('#scan-result').html(`
          <div class="alert alert-success">
            <h6>Scanned: ${decodedText}</h6>
            <button class="btn btn-primary" onclick="processQR('${decodedText}')">Pay</button>
          </div>
        `);
        html5QrCode.stop();
      },
      (err) => {}
    ).catch(err => console.log('Scanner error', err));
  }
});

function processQR(qrString) {
  $.post('<?php echo APP_URL; ?>/index.php?page=scan_qr', {qr_string: qrString}, function(res) {
    if (res.success) {
      window.location = '<?php echo APP_URL; ?>/index.php?page=send_money&receiver=' + (res.wallet_number || res.phone);
    } else {
      alert(res.message);
    }
  });
}

