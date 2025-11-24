<?php
session_start();
session_destroy();
echo "<script>
  alert('Anda telah log keluar.');
  window.location.href = 'index.php';
</script>";
?>
